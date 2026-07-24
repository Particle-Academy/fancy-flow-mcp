<?php

declare(strict_types=1);

namespace FancyFlow\Mcp\Resources;

use FancyFlow\Mcp\Support\FlowAuthoring;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

/**
 * The full node-kind catalogue as a single readable resource — every kind's
 * complete manifest (config schema, ports, defaults, aliases). An agent can pull
 * this once as context instead of paging through describe_node_kind per kind.
 */
#[Title('Fancy Flow Node Kinds')]
#[Uri('flow://node-kinds')]
#[MimeType('application/json')]
#[Description('The complete fancy-flow node-kind catalogue: every authorable kind with its full config schema, ports, defaults, and aliases.')]
final class NodeKindsResource extends Resource
{
    public function __construct(private readonly FlowAuthoring $authoring) {}

    public function handle(Request $request): Response
    {
        $kinds = array_map(
            fn ($kind) => $this->authoring->describeKind($kind),
            $this->authoring->kinds()->all(),
        );

        return Response::text((string) json_encode(
            ['count' => count($kinds), 'kinds' => $kinds],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));
    }
}
