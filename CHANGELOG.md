# Changelog

Notable changes to `particle-academy/fancy-flow-mcp`.

**BREAKING** marks anything that can stop working on upgrade. This package is
pre-1.0, so breaking changes land in MINOR releases — read those entries before
upgrading.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

---

## [Unreleased]

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
