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

#[Name('list_node_kinds')]
#[Title('List Node Kinds')]
#[Description('List every node kind you can place in a fancy-flow workflow — its canonical id, category, label, ports, and aliases. Start here to discover what is available, then call describe_node_kind for a kind\'s full config schema. Optionally filter by category (trigger, human, logic, data, ai, io, output, custom).')]
#[IsReadOnly]
#[IsIdempotent]
final class ListNodeKindsTool extends FlowTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'category' => $schema->string()
                ->description('Optional filter: trigger, human, logic, data, ai, io, output, or custom.'),
        ];
    }

    protected function act(Request $request): Response
    {
        $category = $request->get('category');
        $category = is_string($category) && $category !== '' ? $category : null;

        $kinds = array_map(
            fn ($kind) => $this->authoring->summarizeKind($kind),
            $this->authoring->kinds()->all($category),
        );

        return Reply::json([
            'count' => count($kinds),
            'category' => $category,
            'kinds' => $kinds,
        ]);
    }
}
