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

#[Name('validate_workflow')]
#[Title('Validate Workflow')]
#[Description('Run the whole workflow through fancy-flow\'s importer and report what it found: unknown kinds, missing required config, and dangling edges (as errors/warnings), plus whether the graph is ok. Also reports host capability status — whether an llm_router / subflow has what it needs before a run would park. This is the authoritative check; run it before export_workflow or run_workflow.')]
#[IsReadOnly]
final class ValidateWorkflowTool extends FlowTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'workflow_id' => $schema->string()->description('The workflow id.')->required(),
        ];
    }

    protected function act(Request $request): Response
    {
        $draft = $this->draftOrFail((string) $request->get('workflow_id'));

        return Reply::json(['workflow_id' => $draft->id] + $this->authoring->validate($draft));
    }
}
