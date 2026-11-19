<?php

declare(strict_types=1);

namespace FancyFlow\Mcp\Tests;

use FancyFlow\Mcp\FancyFlowMcpServiceProvider;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\RegisterProviders;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use Laravel\Mcp\Server\McpServiceProvider;

/**
 * A hand-built Laravel application (laravel/framework only, no
 * orchestra/testbench) for the Feature suite.
 *
 * It lives in a throwaway base path: no `.env` content, no package discovery,
 * the framework's own default config plus the overrides below. In-memory
 * SQLite and blank provider keys are forced, so nothing can reach a real
 * database or a paid API.
 */
abstract class TestCase extends LaravelTestCase
{
    /** `artisan()` returns the exit code; output expectations need Mockery, which is not a dependency here. */
    public $mockConsoleOutput = false;

    public function createApplication(): Application
    {
        $base = sys_get_temp_dir().DIRECTORY_SEPARATOR.'fancy-flow-mcp-tests';
        foreach (['bootstrap/cache', 'config', 'database', 'storage/framework/cache', 'storage/framework/views', 'storage/framework/sessions', 'storage/logs'] as $dir) {
            if (! is_dir($base.'/'.$dir)) {
                mkdir($base.'/'.$dir, 0777, true);
            }
        }
        if (! is_file($base.'/.env')) {
            touch($base.'/.env');
        }

        $app = Application::configure(basePath: $base)->withExceptions()->create();

        $app->afterBootstrapping(LoadConfiguration::class, function (Application $app): void {
            $config = $app['config'];
            $config->set('app.env', 'testing');
            $app->detectEnvironment(fn (): string => 'testing');
            $config->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
            $config->set('app.providers', array_merge($config->get('app.providers', []), $this->getPackageProviders($app)));
            $config->set('database.default', 'sqlite');
            $config->set('database.connections.sqlite.database', ':memory:');
            $config->set('cache.default', 'array');
            $config->set('session.driver', 'array');
            $config->set('queue.default', 'sync');
            $config->set('mail.default', 'array');
            $config->set('logging.default', 'null');
            $config->set('services.anthropic.key', '');
            $config->set('services.openai.key', '');
        });

        // After every provider has registered and before any boots.
        $app->afterBootstrapping(RegisterProviders::class, function (Application $app): void {
            $this->defineEnvironment($app);
        });

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    /** @return list<class-string> */
    protected function getPackageProviders($app): array
    {
        // McpServiceProvider registers the container hook that copies a tool
        // call's arguments onto the resolved Request; package auto-discovery
        // does not run here, so it must be listed explicitly.
        return [McpServiceProvider::class, FancyFlowMcpServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        // In-process store so a multi-step build survives across tool() calls
        // within a single test (the DraftStore singleton is shared via the
        // container the MCP testing harness resolves from).
        $app['config']->set('fancy-flow-mcp.store.driver', 'array');
    }
}
