<?php

declare(strict_types=1);

namespace FancyFlow\Mcp\Tools;

use FancyFlow\Mcp\Support\Reply;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('describe_node_kind')]
#[Title('Describe Node Kind')]
#[Description('Get the full schema for one node kind: its config fields (type, required, defaults, options), input/output ports, aliases, and the effective default config a fresh node would carry. Call this before add_node / configure_node so you know exactly which config keys and ports the kind accepts. Accepts a bare name ("branch") or a namespaced id.')]
#[IsReadOnly]
#[IsIdempotent]
final class DescribeNodeKindTool extends FlowTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'kind' => $schema->string()
                ->description('The node kind to describe, e.g. "llm_router" or "@particle-academy/llm_router".')
                ->required(),
        ];
    }

    protected function act(Request $request): Response
    {
        $name = (string) $request->get('kind');
        $kind = $this->authoring->kinds()->get($name);

        if ($kind === null) {
            return Response::error("Unknown node kind \"{$name}\". Call list_node_kinds to see what is available.");
        }

        return Reply::json($this->authoring->describeKind($kind));
    }
}
