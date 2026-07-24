<?php

declare(strict_types=1);

namespace FancyFlow\Mcp\Store;

use FancyFlow\Mcp\Support\DraftWorkflow;

/**
 * In-process draft store — one PHP process's memory. Perfect for a long-lived
 * local (stdio) MCP server, where the whole authoring session is one process,
 * and for tests. A web server should bind {@see CacheDraftStore} instead, since
 * each HTTP request starts cold.
 */
final class ArrayDraftStore implements DraftStore
{
    /** @var array<string,array<string,mixed>> */
    private array $drafts = [];

    public function get(string $id): ?DraftWorkflow
    {
        return isset($this->drafts[$id]) ? DraftWorkflow::fromArray($this->drafts[$id]) : null;
    }

    public function put(DraftWorkflow $draft): void
    {
        $this->drafts[$draft->id] = $draft->toArray();
    }

    public function has(string $id): bool
    {
        return isset($this->drafts[$id]);
    }

    public function forget(string $id): void
    {
        unset($this->drafts[$id]);
    }

    public function ids(): array
    {
        return array_keys($this->drafts);
    }
}
