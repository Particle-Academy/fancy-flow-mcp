<?php

declare(strict_types=1);

/*
 * The authoring core moved to fancy-flow-php as `FancyFlow\Authoring\*`. These
 * names keep working for anything that imported them from this package.
 *
 * EAGER, deliberately. A lazy autoload alias looks cheaper and is wrong: PHP checks
 * a parameter or return type by name WITHOUT autoloading, so
 * `function f(): FancyFlow\Mcp\Support\DraftWorkflow` throws a TypeError until
 * something else happens to touch the old name. This package's own suite caught it.
 */
foreach (['FlowAuthoring', 'DraftWorkflow', 'PortResolver', 'FlowAuthoringException'] as $name) {
    class_alias('FancyFlow\\Authoring\\'.$name, 'FancyFlow\\Mcp\\Support\\'.$name);
}
