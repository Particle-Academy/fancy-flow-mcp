<?php

declare(strict_types=1);

namespace FancyFlow\Mcp\Store;

use FancyFlow\Mcp\Support\DraftWorkflow;
use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * Draft store backed by a Laravel cache repository — the store a WEB MCP server
 * needs, because every HTTP tool call is a fresh process and in-memory state
 * would vanish between them. Drafts persist under a shared prefix; a companion
 * index key tracks the live ids so {@see ids()} can enumerate them without a
 * cache that supports tag/scan.
 */
final class CacheDraftStore implements DraftStore
{
    public function __construct(
        private readonly Cache $cache,
        private readonly string $prefix = 'fancy-flow-mcp:draft:',
        private readonly ?int $ttlSeconds = null,
    ) {}

    public function get(string $id): ?DraftWorkflow
    {
        $raw = $this->cache->get($this->key($id));

        return is_array($raw) ? DraftWorkflow::fromArray($raw) : null;
    }

    public function put(DraftWorkflow $draft): void
    {
        $this->store($this->key($draft->id), $draft->toArray());
        $this->indexAdd($draft->id);
    }

    public function has(string $id): bool
    {
        return $this->cache->has($this->key($id));
    }

    public function forget(string $id): void
    {
        $this->cache->forget($this->key($id));
        $this->indexRemove($id);
    }

    public function ids(): array
    {
        $ids = $this->cache->get($this->indexKey(), []);

        return array_values(array_filter(is_array($ids) ? $ids : [], fn (string $id) => $this->has($id)));
    }

    private function key(string $id): string
    {
        return $this->prefix.$id;
    }

    private function indexKey(): string
    {
        return $this->prefix.'__index__';
    }

    /** @param array<string,mixed> $value */
    private function store(string $key, array $value): void
    {
        if ($this->ttlSeconds === null) {
            $this->cache->forever($key, $value);
        } else {
            $this->cache->put($key, $value, $this->ttlSeconds);
        }
    }

    private function indexAdd(string $id): void
    {
        $index = $this->cache->get($this->indexKey(), []);
        $index = is_array($index) ? $index : [];
        if (! in_array($id, $index, true)) {
            $index[] = $id;
            $this->store($this->indexKey(), $index);
        }
    }

    private function indexRemove(string $id): void
    {
        $index = $this->cache->get($this->indexKey(), []);
        $index = is_array($index) ? array_values(array_filter($index, static fn ($v) => $v !== $id)) : [];
        $this->store($this->indexKey(), $index);
    }
}
