<?php

declare(strict_types=1);

use FancyFlow\Authoring\DraftWorkflow;
use FancyFlow\Authoring\FlowAuthoring;
use FancyFlow\Authoring\FlowAuthoringException;
use FancyFlow\Authoring\PortResolver;

/**
 * The core moved to fancy-flow-php. There is one copy of validation, and every
 * name this package used to own still means that copy.
 */
it('keeps each old class name as the same class, not a second copy', function (): void {
    expect(class_exists('FancyFlow\Mcp\Support\FlowAuthoring'))->toBeTrue()
        ->and(FlowAuthoring::default())->toBeInstanceOf('FancyFlow\Mcp\Support\FlowAuthoring');

    expect((new ReflectionClass('FancyFlow\Mcp\Support\DraftWorkflow'))->getName())->toBe(DraftWorkflow::class)
        ->and((new ReflectionClass('FancyFlow\Mcp\Support\PortResolver'))->getName())->toBe(PortResolver::class)
        ->and((new ReflectionClass('FancyFlow\Mcp\Support\FlowAuthoringException'))->getName())->toBe(FlowAuthoringException::class);
});

it('answers the old container key with the same singleton', function (): void {
    // A container binding is keyed by the STRING. Without an explicit alias a host
    // resolving the old name would get a new instance, with no host kinds.
    expect($this->app->make('FancyFlow\Mcp\Support\FlowAuthoring'))
        ->toBe($this->app->make(FlowAuthoring::class));
});

it('leaves no authoring source in this package', function (): void {
    foreach (['FlowAuthoring', 'DraftWorkflow', 'PortResolver', 'FlowAuthoringException'] as $name) {
        expect(is_file(dirname(__DIR__, 2).'/src/Support/'.$name.'.php'))->toBeFalse($name);
    }
});
