<?php

declare(strict_types=1);

namespace FancyFlow\Mcp\Tests;

use FancyFlow\Mcp\FancyFlowMcpServiceProvider;
use Laravel\Mcp\Server\McpServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /** @return list<class-string> */
    protected function getPackageProviders($app): array
    {
        // McpServiceProvider registers the container hook that copies a tool
        // call's arguments onto the resolved Request; Testbench does not run
        // package auto-discovery, so it must be listed explicitly.
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
