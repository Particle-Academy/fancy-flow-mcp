<?php

declare(strict_types=1);

namespace FancyFlow\Mcp\Support;

use FancyFlow\Registry\KindId;
use FancyFlow\Registry\NodeKind;
use FancyFlow\Schema\PortDescriptor;

/**
 * Resolves the set of valid input / output port ids for a node, so
 * {@see FlowAuthoring::connect()} can reject an edge that references a port the
 * node does not have — a check {@see \FancyFlow\Workflow::import()} does NOT
 * perform (it validates edge endpoints exist, not that handles are real ports).
 *
 * The base set comes straight from the kind's declared ports:
 *   - `outputs`/`inputs` === null  → the engine default single `out` / `in` port
 *   - declared list                → those ids
 *   - declared empty `[]`          → an explicit terminal (no ports)
 *
 * A few kinds compute their ports from config at runtime (mirroring the TS
 * `outputs: (config) => …` closures). For those this resolver augments the base
 * set by reading config THE SAME WAY the executors document — never by calling
 * fancy-flow-php. It is deliberately generous: whole-graph correctness is still
 * owned by `validate_workflow` (which runs `Workflow::import`).
 */
final class PortResolver
{
    /**
     * @param array<string,mixed> $config
     * @return list<string>
     */
    public static function outputs(?NodeKind $kind, array $config): array
    {
        if ($kind === null) {
            return ['out'];
        }

        $base = self::declared($kind->outputs, 'out');

        return match (KindId::bare($kind->name)) {
            'llm_router' => self::llmRouterPorts($config, $base),
            'switch_case' => self::switchCasePorts($config, $base),
            'subflow' => self::subflowPorts($config, $base),
            default => $base,
        };
    }

    /**
     * @param array<string,mixed> $config
     * @return list<string>
     */
    public static function inputs(?NodeKind $kind, array $config): array
    {
        if ($kind === null) {
            return ['in'];
        }

        return self::declared($kind->inputs, 'in');
    }

    /**
     * @param list<PortDescriptor>|null $ports
     * @return list<string>
     */
    private static function declared(?array $ports, string $default): array
    {
        if ($ports === null) {
            return [$default];
        }

        return array_values(array_map(static fn (PortDescriptor $p): string => $p->id, $ports));
    }

    /**
     * @param array<string,mixed> $config
     * @param list<string>        $base
     * @return list<string>
     */
    private static function llmRouterPorts(array $config, array $base): array
    {
        $routes = $config['routes'] ?? null;
        if (! is_array($routes) || $routes === []) {
            return $base;
        }

        $ports = [];
        foreach ($routes as $route) {
            if (is_array($route) && isset($route['port']) && $route['port'] !== '') {
                $ports[] = (string) $route['port'];
            }
        }
        // The `fallback` port defaults ON (kind config switch default true).
        if (($config['fallback'] ?? true) !== false) {
            $ports[] = 'fallback';
        }

        return $ports === [] ? $base : array_values(array_unique($ports));
    }

    /**
     * @param array<string,mixed> $config
     * @param list<string>        $base
     * @return list<string>
     */
    private static function switchCasePorts(array $config, array $base): array
    {
        $cases = $config['cases'] ?? null;
        if (! is_array($cases) || $cases === []) {
            return $base;
        }

        $ports = [];
        foreach ($cases as $port) {
            if (is_string($port) && $port !== '') {
                $ports[] = $port;
            }
        }
        $ports[] = 'default';

        return array_values(array_unique($ports));
    }

    /**
     * @param array<string,mixed> $config
     * @param list<string>        $base
     * @return list<string>
     */
    private static function subflowPorts(array $config, array $base): array
    {
        $mode = (string) ($config['mode'] ?? 'output');
        if ($mode === 'stream' || $mode === 'both') {
            $base[] = 'stream';
        }

        return array_values(array_unique($base));
    }
}
