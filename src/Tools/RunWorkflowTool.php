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

#[Name('run_workflow')]
#[Title('Run Workflow')]
#[Description('Execute the workflow with fancy-flow-php\'s engine and the batteries-included default executors (deterministic in-memory / echo fakes — no network, no API keys), and return the per-node outputs plus ok/error. This is a smoke test of the graph\'s wiring and routing, not a production run against real services. Refuses to run a graph with validation errors. Pass initial_inputs to seed entry nodes, keyed by node id then port.')]
final class RunWorkflowTool extends FlowTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'workflow_id' => $schema->string()->description('The workflow id.')->required(),
            'initial_inputs' => $schema->object()->description('Optional inputs seeded to entry nodes: { "<node_id>": { "<port>": <value> } }. Example: { "manual-1": { "payload": { "q": "hi" } } }.'),
        ];
    }

    protected function act(Request $request): Response
    {
        $draft = $this->draftOrFail((string) $request->get('workflow_id'));

        $inputs = $this->objectArg($request, 'initial_inputs') ?? [];

        return Reply::json(['workflow_id' => $draft->id] + $this->authoring->run($draft, $inputs));
    }
}
