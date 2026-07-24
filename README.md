# fancy-flow-mcp

**A Laravel MCP server that lets an AI agent build [fancy-flow](https://github.com/Particle-Academy/fancy-flow) workflows headlessly.**

Built on [`laravel/mcp`](https://github.com/laravel/mcp) (Laravel's official MCP
package) and powered by
[`particle-academy/fancy-flow-php`](https://github.com/Particle-Academy/fancy-flow-php)
(the PHP runtime twin of fancy-flow). The connecting model is the only
intelligence involved — every tool here is a thin, **deterministic** wrapper over
fancy-flow-php's schema, registries, validation, and engine. No LLM calls, no
network, no guessing.

> An agent discovers the node kinds, creates a workflow, adds and wires nodes
> (connections are port-validated), fills in each node's config (schema-checked),
> validates the whole graph, and exports a portable **WorkflowSchema v1** JSON —
> the same document `<FlowEditor>` imports and both the Node and PHP engines run
> unchanged.

```bash
composer require particle-academy/fancy-flow-mcp
```

Requires PHP 8.3+ (fancy-flow-php's floor) and Laravel 12 or 13
(`laravel/mcp` needs `illuminate/json-schema`, which ships in framework 12+).

---

## The tools

| Tool | What it does |
|---|---|
| `list_node_kinds` | Every authorable kind — id, category, label, ports, aliases. Optional category filter. |
| `describe_node_kind` | One kind's full config schema, ports, defaults, and aliases. |
| `create_workflow` | Start an empty draft; returns a `workflow_id` used by every later call. |
| `import_workflow` | Load an existing WorkflowSchema document into an editable draft (lenient — reports issues, doesn't reject). |
| `get_workflow` | Inspect the current draft — metadata, nodes, edges. |
| `list_workflows` | Every draft the server holds. |
| `delete_workflow` | Discard a draft. |
| `add_node` | Add a node of a kind; config defaults applied, config schema-checked. |
| `configure_node` | Set/merge a node's config, re-validated against its kind. |
| `connect_nodes` | Draw a **port-validated** edge (handles default `out` → `in`). |
| `remove_node` | Delete a node and cascade its edges. |
| `remove_edge` | Delete a single edge. |
| `validate_workflow` | Authoritative `Workflow::import` check (unknown kinds, missing config, dangling edges) + host capability status. |
| `export_workflow` | The deliverable: canonical WorkflowSchema v1 JSON. |
| `run_workflow` | Smoke-test the graph with fancy-flow-php's deterministic fake executors. |

Plus a resource — `flow://node-kinds` — carrying the entire kind catalogue as
one JSON document, so an agent can pull it once as context.

### Why "port-validated" connections matter

`Workflow::import()` validates that an edge's endpoints *exist*; it does **not**
check that a handle is a real port. `connect_nodes` adds that: a `branch` needs
`source_handle` `"true"` or `"false"`, a `merge` needs `target_handle` `"a"` or
`"b"`, a terminal `output` can't be a source, a trigger can't be a target — and
when you get it wrong, the error names the valid ports. Dynamic-port kinds are
resolved from config the way the engine documents them (`llm_router` routes,
`switch_case` cases, `subflow` stream mode).

## Registering the server

The service provider registers a **local** (stdio) server named `fancy-flow` out
of the box, so it works with a desktop AI client immediately:

```bash
php artisan mcp:inspector fancy-flow      # try it interactively
php artisan mcp:start fancy-flow          # (clients usually start this for you)
```

Publish the config to customize:

```bash
php artisan vendor:publish --tag=fancy-flow-mcp-config
```

```php
// config/fancy-flow-mcp.php
'local' => ['enabled' => true, 'name' => 'fancy-flow'],
'web'   => ['path' => null, 'middleware' => []], // set a path to expose over HTTP
'store' => ['driver' => 'cache', /* 'array' for a long-lived local server */ ],
```

To expose it over HTTP instead (protect it — an MCP endpoint runs tools on your
app), set `web.path` (e.g. `/mcp/fancy-flow`) and appropriate middleware, or
register it yourself in `routes/ai.php`:

```php
use FancyFlow\Mcp\Server\FlowBuilderServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/fancy-flow', FlowBuilderServer::class)->middleware(['auth:sanctum']);
```

### The draft store

An agent authors a workflow across many tool calls, so drafts persist between
them via a `DraftStore`. `cache` (default) survives the request-per-call
lifecycle of a **web** server; `array` keeps drafts in-process, which is fine for
a long-lived **local/stdio** server. Rebind `FancyFlow\Mcp\Store\DraftStore` in
your app to persist to a database instead.

## A typical build

```
list_node_kinds
create_workflow           → { workflow_id: "wf_…" }
add_node    kind=manual_trigger  node_id=start
add_node    kind=llm_call        node_id=answer   config={ model, prompt, … }
add_node    kind=output          node_id=out
connect_nodes  start  → answer
connect_nodes  answer → out
validate_workflow         → { ok: true, … }
export_workflow           → WorkflowSchema v1 JSON  ← the deliverable
run_workflow              → deterministic smoke test
```

## Testing

```bash
composer install
vendor/bin/pest
```

The suite has framework-free unit tests (the authoring core + port resolver) and
feature tests that drive the real MCP tools through `FlowBuilderServer`.

## Local development note

`particle-academy/fancy-flow-php` is not yet published to Packagist. Until it is,
`composer.json` carries a local `path` repository pointing at `../fancy-flow-php`
(its sibling in the Fancy suite envelope). When fancy-flow-php ships, drop that
`repositories` block and the dependency resolves from Packagist as `^0.x`.

## License

MIT © Particle Academy
