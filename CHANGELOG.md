# Changelog

Notable changes to `particle-academy/fancy-flow-mcp`.

**BREAKING** marks anything that can stop working on upgrade. This package is
pre-1.0, so breaking changes land in MINOR releases — read those entries before
upgrading.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

---

## 0.4.0 — 2026-08-26

### Changed

- **Floor raised to `fancy-flow-php` >=0.48**, which refuses a graph containing
  a node that cannot take part in it. Two shapes an agent authoring iteratively
  produces, both of which used to validate clean and then run doing nothing:

  - a **floating node** — no inbound and no outbound edge. It is not skipped; a
    node with no incoming edge is a root, so the engine runs it, disconnected.
  - an **edge whose source is a terminal node** (`output`, `log`). The
    downstream node still runs, with its input resolving to `""`.

  `note` may float, so an agent can annotate what it built.

  **The likeliest way to hit this is `remove_node`**, which cascades a node's
  edges — deleting one from the middle of a chain strands both its neighbours.
  That case now fails `validate_workflow` instead of passing it.

### Added

- Tests asserting the refusal REACHES AN AGENT, through `validate_workflow` and
  `run_workflow` rather than only through the engine's own importer. A separate
  claim from "the engine produces the error", and the one worth pinning here.

  These also record where the terminator edge is actually caught: **`connect_nodes`
  already refuses to create it** (a terminal kind publishes no source port), so
  validation is the second line, for a graph that arrived as JSON rather than
  through the tools.

### What a consumer must do

Nothing, unless a graph you author already contains a stray node — in which case
`validate_workflow` now tells you which one, by id, and `run_workflow` declines
until it is wired or deleted. Mid-build drafts are unaffected: `validate` runs
only when you call it, never after each `add_node`.

---

## 0.3.1 — 2026-08-26

### Fixed

- **`describe_node_kind` and the ENGINE disagreed about a config-derived node's
  ports**, and the engine was the one that got it wrong — so an agent that
  followed this server's advice had its graph flagged.

  This package derived a `switch_case`'s ports from its `cases` map (correctly
  offering a third case once three were configured), while the engine's
  undelivered-edge warning read only the kind's static declaration and reported
  that port as impossible. **The authoring API invited the edge and the runtime
  called it a mistake.**

  The derivation now lives once, in `fancy-flow-php`'s
  `Registry\PortResolution`, and this package calls it. Requires
  `fancy-flow-php >= 0.46`.

  What deliberately did NOT move: a TERMINAL kind declares an empty port list
  and nothing may connect from it — an authoring rule, kept here, because the
  engine legitimately answers differently (`activatedPorts` publishes `out` for
  such a node, a historical fallback so a chain through one is not silently
  cut). Two different questions; only the duplicated one was unified.

  Found by **flabs**, where an agent configured a third case through this server
  and the engine then failed the graph.

## 0.3.0 — 2026-08-26

### Added

- **The authoring catalogue now includes the HOST application's own node
  kinds.** `list_node_kinds`, `describe_node_kind` and — the one that matters —
  `connect_nodes` all see them.

  This is the difference between an MCP that can author your real workflows and
  one that can only author ours. A host whose graphs are built from `deal_list`,
  `document`, `response` or any org-scoped kind previously got a server that
  could not name them, let alone connect them.

  **Why `connect_nodes` is the point.** An edge naming a source port nothing
  publishes does not fail and does not warn — `collectInputs` binds a payload
  only when `"<sourceId>:<handle>"` exists, so the edge silently delivers
  NOTHING and the downstream template renders empty while being completely
  correct. Authoring through an API that knows the ports removes the string
  there is to get wrong — but only for kinds the API can see. The consumer who
  asked for this had misdiagnosed two filed issues off the back of that exact
  failure.

  **How to use it:** in a Laravel app, nothing. The service provider resolves
  the container's `NodeKindRegistry` — the same one `FlowRunner` uses — so kinds
  you register are picked up automatically. It is resolved lazily inside the
  binding, because a host registers its kinds in its own provider's `boot()`,
  which may run after ours. Outside Laravel, call
  `FlowAuthoring::withHostKinds($yourRegistry)`.

  Host kinds are **copied in**, not read by reference, so concurrent servers
  cannot clobber each other's catalogue. They are registered LAST and therefore
  win over a builtin of the same name: a host overriding a builtin means it, and
  showing the builtin instead would describe ports the run does not have.

  Requested by MOIC.

### Changed

- **`describe_node_kind` now RESOLVES what a kind emits instead of returning the
  serialisation marker.** `toArray()` writes `"dynamic"` for a config-dependent
  shape, because a Closure cannot cross a JSON manifest — and handing that to an
  authoring agent is useless in the place it matters most, since `llm_call` is
  the most-referenced kind there is and "dynamic" says nothing about whether
  `{{ in.text }}` will resolve.

  Here there is a live registry and a config, so the question can be answered.
  The reply carries `emits.fields`, `emits.relation`, `emits.expressionConfigKey`
  and `emits.configDependent` — the last so an agent knows the answer MOVES when
  it configures the node (an `llm_call` gains `data` the moment a
  `response_schema` is set, and an author who cached the first answer would be
  wrong about the second).

  `emits.note` spells out the difference between `fields: null` and `fields: []`,
  because a reply full of nulls invites exactly the reassuring misreading:
  **not-declared is UNKNOWN, not "emits nothing"**, and an agent must not refuse
  a reference on that basis.

  `describeKind()` takes an optional `$config`, so an existing node is described
  with its OWN configuration rather than the kind's defaults.

- Requires `particle-academy/fancy-flow-php >= 0.43`, and is tested against it.
  That release fixes the engine half of the same bug: host-registered kinds'
  output ports were invisible to `FlowRunner` too, so authoring a correct edge
  was necessary but not sufficient. **Take both or neither** — this package
  alone will happily author an edge the engine then refuses to route.

## [Unreleased]

## [0.2.0] — 2026-08-07

### Changed

- **BREAKING — PHP 8.3 is no longer supported.** `require.php` moves from `^8.3` to `^8.4`.

  **What you must do:** on PHP 8.4 or newer, nothing. On 8.3, either upgrade PHP first or stay on the previous release — it keeps working and is unaffected by this.

- CI now tests PHP 8.4 only, instead of a matrix spanning versions this package no longer claims to support. A matrix that tests what the manifest forbids is worse than none — it reports green for a combination nobody can install.

### Why

These are the kit 0.5 platform floors. The suite was split across PHP 8.2 and 8.3 with the framework spanning 11–13, so no package could rely on anything newer than its weakest sibling. Every PHP package in the kit takes the same floors at once, so a consumer never has to resolve a mix.

Pre-1.0, so this lands in a MINOR. **No API changed, nothing was removed, nothing was renamed** — only what the package requires.


## [0.1.1] — 2026-07-28

### Changed

- Widened the `particle-academy/fancy-flow-php` requirement from `^0.8` to `>=0.8 <2.0`, so a sibling
  minor release is an upgrade and not a resolver conflict. **No action needed** —
  widening a range only adds candidates; the version you have today still resolves.

  A caret on a `0.x` range locks the MINOR, so every one of these pinned a
  sibling at whatever it happened to be on the day it was written, and each
  sibling release then read as a conflict to Composer/npm rather than an
  upgrade. Nothing in this package was using an API the newer minors removed
  — the range was the whole problem.

  This one was **already blocking**: the sibling had shipped past the cap,
  so installing the two together resolved to an old copy or refused
  outright. Nothing reported it, because a resolver quietly picking an older
  version looks exactly like success.

### Added

- Initial cut: a [`laravel/mcp`](https://github.com/laravel/mcp) server that lets
  an AI agent build [fancy-flow](https://github.com/Particle-Academy/fancy-flow)
  workflows headlessly, as a thin, deterministic wrapper over
  `particle-academy/fancy-flow-php` (no LLM calls — the connecting agent is the
  only intelligence).
- `FlowBuilderServer` — the MCP server, registered as a local (stdio) server out
  of the box and optionally as a web server, exposing:
  - **Discover** — `list_node_kinds`, `describe_node_kind`, and a
    `flow://node-kinds` resource carrying the full kind catalogue.
  - **Lifecycle** — `create_workflow`, `import_workflow`, `get_workflow`,
    `list_workflows`, `delete_workflow`.
  - **Author** — `add_node`, `configure_node` (schema-checked),
    `connect_nodes` (port-validated), `remove_node` (cascades edges),
    `remove_edge`.
  - **Finish** — `validate_workflow` (the authoritative `Workflow::import`
    check + host capability status), `export_workflow` (canonical WorkflowSchema
    v1 JSON), `run_workflow` (deterministic run with fancy-flow-php's default
    fake executors).
- `FlowAuthoring` — the framework-free authoring core. Reuses fancy-flow-php's
  `NodeKindRegistry` (kinds, config schema, defaults, config validation),
  `Workflow::import()`/`::export()` (whole-graph validation + canonical export),
  and `FlowRunner` (execution). Kinds are stored under their canonical
  namespaced ids, so an exported document matches what `<FlowEditor>` saves.
- `PortResolver` — connect-time port validation, the one piece of logic not in
  fancy-flow-php (import validates that edge endpoints exist, not that handles
  are real ports). Resolves dynamic ports from config for `llm_router` (routes +
  fallback), `switch_case` (cases + default), and `subflow` (stream mode).
- `DraftStore` with two implementations: `ArrayDraftStore` (in-process — a
  long-lived local server, and tests) and `CacheDraftStore` (a web server, where
  each request is cold). Selectable via `config/fancy-flow-mcp.php`.
- Pest suite (unit + feature) covering authoring, port resolution, and the full
  build-validate-export-run cycle through the real MCP tools.

### Notes

- Depends on `particle-academy/fancy-flow-php`, which is **not yet on Packagist**.
  Until it is, `composer.json` carries a local `path` repository pointing at
  `../fancy-flow-php`; on publish, drop that block and pin `^0.x` from Packagist.
