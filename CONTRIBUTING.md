# Contributing to particle-academy/fancy-flow-mcp

Notes for anyone, human or agent, working ON this package's code. How to USE the
package is in [AGENTS.md](./AGENTS.md); what it is, is in [README.md](./README.md).
`CLAUDE.md` is a symlink to this file, so Claude Code reads these notes when it
works in this repo.

## Layout

- `src/Server/FlowBuilderServer.php` — the `laravel/mcp` server.
- `src/Tools/` — one class per MCP tool (`FlowTool` is the shared base).
- `src/Store/` — the `DraftStore` contract and its `cache` / `array` drivers.
- `src/Resources/` — the `flow://node-kinds` resource.
- `src/authoring-aliases.php` — the authoring core moved to fancy-flow-php as
  `FancyFlow\Authoring\*`; this file keeps the old `FancyFlow\Mcp\Support\*`
  names working with eager `class_alias` calls. Eager on purpose: PHP checks a
  parameter or return type by name without autoloading, so a lazy alias throws
  a `TypeError` until something else touches the old name.
  `tests/Feature/AuthoringMovedTest.php` pins this.
- `config/fancy-flow-mcp.php` — the published config.

## Testing

```bash
composer install
vendor/bin/pest
```

The suite has framework-free unit tests and feature tests that drive the real
MCP tools through `FlowBuilderServer`.

## Changelog

Update `CHANGELOG.md` in the same commit as the change.
