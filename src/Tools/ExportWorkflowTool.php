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
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('export_workflow')]
#[Title('Export Workflow')]
#[Description('Export the draft as a canonical fancy-flow WorkflowSchema v1 JSON document — the portable format that <FlowEditor> imports and fancy-flow (Node) and fancy-flow-php (PHP) both run unchanged. This is the deliverable: save it, load it in the editor, or run it on any backend. Consider validate_workflow first.')]
#[IsReadOnly]
final class ExportWorkflowTool extends FlowTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'workflow_id' => $schema->string()->description('The workflow id.')->required(),
            'pretty' => $schema->boolean()->description('Pretty-print the JSON (default true).')->default(true),
        ];
    }

    protected function act(Request $request): Response
    {
        $draft = $this->draftOrFail((string) $request->get('workflow_id'));

        $pretty = $request->get('pretty');
        $pretty = is_bool($pretty) ? $pretty : true;

        return Reply::json([
            'workflow_id' => $draft->id,
            'schema' => $this->authoring->schemaArray($draft),
            'json' => $this->authoring->export($draft, $pretty),
        ]);
    }
}
