# Using particle-academy/fancy-flow-mcp — guide for agents

How an agent installs and uses this package in a consumer app. What the package
is, is in [README.md](./README.md). Notes for working ON this repo's code are in
[CONTRIBUTING.md](./CONTRIBUTING.md).

## Install

```bash
composer require particle-academy/fancy-flow-mcp
```

- PHP 8.4+, Laravel 12 or 13. Pulls in `laravel/mcp` and
  `particle-academy/fancy-flow-php` (`>=0.66 <2.0`).
- The service provider auto-registers a **local (stdio)** MCP server named
  `fancy-flow`:

  ```bash
  php artisan mcp:inspector fancy-flow   # try it interactively
  php artisan mcp:start fancy-flow       # clients usually start this for you
  ```

- Config: `php artisan vendor:publish --tag=fancy-flow-mcp-config` writes
  `config/fancy-flow-mcp.php` (`local`, `web`, `store`).

## Find it on the Fancy MCP server

The catalog server is `https://ui.particle.academy/mcp` (streamable HTTP, no API
key; MCP id `fancy-ui`). It is listed as the registry entry `fancy-flow-mcp` (a
package entry, not vendorable source). Find it with `search-backend-packages`
(stack `php`). The Node twin is `fancy-flow-mcp-js`, with the same tools.

For node kinds beyond the builtins, the catalog's `search-nodes`, `get-node` and
`node-install-instructions` cover the fancy-flow marketplace.

## API essentials

- **HTTP instead of stdio:** set `web.path` (and middleware) in the config, or
  register it yourself in `routes/ai.php`:

  ```php
  use FancyFlow\Mcp\Server\FlowBuilderServer;
  use Laravel\Mcp\Facades\Mcp;

  Mcp::web('/mcp/fancy-flow', FlowBuilderServer::class)->middleware(['auth:sanctum']);
  ```

- **Draft store:** `store.driver` is `cache` (default; needed for a web server,
  where each call is a request) or `array` (in-process; fine for a long-lived
  stdio server). Rebind `FancyFlow\Mcp\Store\DraftStore` to persist drafts to a
  database.
- Tools: `list_node_kinds`, `describe_node_kind`, `create_workflow`,
  `import_workflow`, `get_workflow`, `list_workflows`, `delete_workflow`,
  `add_node`, `configure_node`, `connect_nodes`, `remove_node`, `remove_edge`,
  `validate_workflow`, `export_workflow`, `run_workflow`. Resource:
  `flow://node-kinds`, the whole kind catalogue as one JSON document.
- The authoring core lives in fancy-flow-php as `FancyFlow\Authoring\*`; the old
  `FancyFlow\Mcp\Support\*` names are aliases of the same classes.

## Traps

- **Protect an HTTP endpoint.** An MCP endpoint runs tools on your app; put auth
  middleware on it.
- **`array` store on a web server loses drafts** between calls. Use `cache` (or
  your own `DraftStore`) for HTTP.
- `run_workflow` runs fancy-flow-php's deterministic fake executors. It is a
  smoke test, not a production run.

## A typical build (for an agent connected to this server)

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

- `connect_nodes` handles default `out` → `in`. A `branch` needs
  `source_handle` `"true"` or `"false"`; a `merge` needs `target_handle` `"a"`
  or `"b"`; an `output` cannot be a source and a trigger cannot be a target. A
  wrong handle's error names the valid ports.
- `import_workflow` is lenient: it reports issues rather than rejecting.
- `export_workflow` produces the WorkflowSchema v1 document that `<FlowEditor>`
  imports and both the Node and PHP engines run unchanged.
