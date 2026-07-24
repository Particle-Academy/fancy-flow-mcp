<?php

declare(strict_types=1);

namespace FancyFlow\Mcp\Store;

use FancyFlow\Mcp\Support\DraftWorkflow;

/**
 * Where in-progress workflows live between MCP tool calls. An agent authors a
 * flow across many calls (create → add_node → connect → …), and each call is a
 * separate request, so drafts must survive between them.
 *
 * Two implementations ship: an in-process {@see ArrayDraftStore} (a long-lived
 * local/stdio server, and tests) and a {@see CacheDraftStore} (a web server,
 * where every request is cold). Bind whichever fits in the service provider.
 */
interface DraftStore
{
    public function get(string $id): ?DraftWorkflow;

    public function put(DraftWorkflow $draft): void;

    public function has(string $id): bool;

    public function forget(string $id): void;

    /** @return list<string> stored workflow ids */
    public function ids(): array;
}
