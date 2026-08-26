<?php

declare(strict_types=1);

namespace FancyFlow\Mcp;

use FancyFlow\Mcp\Server\FlowBuilderServer;
use FancyFlow\Mcp\Store\ArrayDraftStore;
use FancyFlow\Mcp\Store\CacheDraftStore;
use FancyFlow\Mcp\Store\DraftStore;
use FancyFlow\Mcp\Support\FlowAuthoring;
use FancyFlow\NodeKindRegistry;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Laravel\Mcp\Facades\Mcp;

/**
 * Wires the Fancy Flow Builder into a Laravel app:
 *   - a shared {@see FlowAuthoring} (the full node-kind catalogue, once);
 *   - a {@see DraftStore} chosen by config ('cache' for web, 'array' for a
 *     long-lived local server) — rebind it in the app to persist elsewhere;
 *   - the {@see FlowBuilderServer} registered as a local (and optionally web)
 *     MCP server, so `php artisan mcp:start` finds it out of the box.
 */
final class FancyFlowMcpServiceProvider extends ServiceProvider
{
    private const CONFIG = __DIR__.'/../config/fancy-flow-mcp.php';

    public function register(): void
    {
        $this->mergeConfigFrom(self::CONFIG, 'fancy-flow-mcp');

        // Prefer the HOST's registry when the app has bound one. `fancy-flow-php`'s
        // own service provider binds `NodeKindRegistry`, and a host registering
        // custom kinds registers them there -- the same registry `FlowRunner`
        // resolves. Reading it here is what lets an agent author the host's real
        // workflows rather than only ours.
        //
        // Resolved lazily inside the closure, not at register() time: a host
        // registers its kinds in its own provider's boot(), which may run after
        // this one.
        $this->app->singleton(FlowAuthoring::class, static function ($app): FlowAuthoring {
            $host = $app->bound(NodeKindRegistry::class)
                ? $app->make(NodeKindRegistry::class)
                : null;

            return FlowAuthoring::withHostKinds($host);
        });

        $this->app->singleton(DraftStore::class, function (Application $app): DraftStore {
            $config = (array) $app['config']->get('fancy-flow-mcp.store', []);

            if (($config['driver'] ?? 'cache') === 'array') {
                return new ArrayDraftStore();
            }

            $cache = $app['cache']->store($config['cache_store'] ?? null);
            $ttl = $config['ttl'] ?? null;

            return new CacheDraftStore(
                $cache,
                (string) ($config['prefix'] ?? 'fancy-flow-mcp:draft:'),
                $ttl === null ? null : (int) $ttl,
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([self::CONFIG => $this->app->configPath('fancy-flow-mcp.php')], 'fancy-flow-mcp-config');
        }

        $this->registerServers();
    }

    /**
     * Register the MCP server so clients can reach it. Guarded so the package is
     * still loadable if the MCP facade is somehow unavailable, and so both
     * registrations are opt-out via config.
     */
    private function registerServers(): void
    {
        if (! class_exists(Mcp::class)) {
            return;
        }

        $config = $this->app['config'];

        if ((bool) $config->get('fancy-flow-mcp.local.enabled', true)) {
            Mcp::local((string) $config->get('fancy-flow-mcp.local.name', 'fancy-flow'), FlowBuilderServer::class);
        }

        $path = $config->get('fancy-flow-mcp.web.path');
        if (is_string($path) && $path !== '') {
            Mcp::web($path, FlowBuilderServer::class)
                ->middleware((array) $config->get('fancy-flow-mcp.web.middleware', []));
        }
    }
}
