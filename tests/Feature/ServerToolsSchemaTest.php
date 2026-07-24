<?php

declare(strict_types=1);

use FancyFlow\Mcp\Server\FlowBuilderServer;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Server\Tool;

/**
 * Guards that EVERY registered tool and resource resolves from the container
 * (constructor DI is satisfied) and that each tool's input schema builds and
 * serializes without error — a JsonSchema misuse in a tool the build-flow test
 * never calls would otherwise only surface when a client lists the tools.
 */
it('resolves and serializes every registered tool', function (): void {
    $server = new ReflectionClass(FlowBuilderServer::class);
    $tools = $server->getDefaultProperties()['tools'];

    expect($tools)->toHaveCount(15);

    foreach ($tools as $class) {
        $tool = app($class);
        expect($tool)->toBeInstanceOf(Tool::class);

        $array = $tool->toArray();
        expect($array)->toHaveKeys(['name', 'inputSchema'])
            ->and($array['name'])->toBeString()->not->toBe('');
    }
});

it('resolves every registered resource', function (): void {
    $server = new ReflectionClass(FlowBuilderServer::class);
    $resources = $server->getDefaultProperties()['resources'];

    expect($resources)->not->toBeEmpty();

    foreach ($resources as $class) {
        expect(app($class))->toBeInstanceOf(Resource::class);
    }
});
