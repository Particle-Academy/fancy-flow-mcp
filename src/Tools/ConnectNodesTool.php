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

#[Name('connect_nodes')]
#[Title('Connect Nodes')]
#[Description('Draw a validated edge from one node\'s output port to another node\'s input port. Both endpoints must exist and the handles must be real ports on their kinds — source_handle defaults to "out", target_handle to "in". A node with multiple outputs (branch → "true"/"false", switch_case, llm_router routes) requires the right source_handle, and the error tells you the valid ports if you get it wrong. Terminal nodes (output, log) cannot be a source; trigger nodes cannot be a target.')]
final class ConnectNodesTool extends FlowTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'workflow_id' => $schema->string()->description('The workflow id.')->required(),
            'source' => $schema->string()->description('Source node id.')->required(),
            'target' => $schema->string()->description('Target node id.')->required(),
            'source_handle' => $schema->string()->description('Output port on the source (default "out"). e.g. "true" for a branch.'),
            'target_handle' => $schema->string()->description('Input port on the target (default "in"). e.g. "a" for a merge.'),
            'edge_id' => $schema->string()->description('Optional explicit edge id. Omit to auto-generate.'),
            'label' => $schema->string()->description('Optional edge label.'),
        ];
    }

    protected function act(Request $request): Response
    {
        $draft = $this->draftOrFail((string) $request->get('workflow_id'));

        $result = $this->authoring->connect(
            $draft,
            source: (string) $request->get('source'),
            target: (string) $request->get('target'),
            sourceHandle: $this->stringOrNull($request->get('source_handle')),
            targetHandle: $this->stringOrNull($request->get('target_handle')),
            edgeId: $this->stringOrNull($request->get('edge_id')),
            label: $this->stringOrNull($request->get('label')),
        );
        $this->store->put($draft);

        return Reply::json([
            'workflow_id' => $draft->id,
            'edge' => $result['edge'],
        ]);
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
