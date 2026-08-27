<?php

declare(strict_types=1);

use FancyFlow\Mcp\Server\FlowBuilderServer;
use FancyFlow\Mcp\Tools\AddNodeTool;
use FancyFlow\Mcp\Tools\ConnectNodesTool;
use FancyFlow\Mcp\Tools\CreateWorkflowTool;
use FancyFlow\Mcp\Tools\RemoveNodeTool;
use FancyFlow\Mcp\Tools\RunWorkflowTool;
use FancyFlow\Mcp\Tools\ValidateWorkflowTool;

/**
 * `fancy-flow-php` 0.48.0 refuses a graph containing a node that cannot take
 * part in it. This asserts the refusal REACHES AN AGENT through the tools.
 *
 * Not a duplicate of the engine's own suite. That one proves `Workflow::import()`
 * produces the error; this one proves the two tools an agent actually calls
 * surface it — which is a separate claim, and the one that has silently failed
 * before in this package (a check that fires where nobody is standing).
 *
 * ## The case that matters most is DELETION
 *
 * `remove_node` cascades a node's edges. Removing a node from the middle of a
 * chain therefore strands its neighbours — which is not a hypothetical, it is
 * the single most likely way an agent authoring iteratively produces a graph
 * that runs and does nothing. Before 0.48.0 the result validated clean.
 */
function connBuild(string $id): void
{
    FlowBuilderServer::tool(CreateWorkflowTool::class, ['workflow_id' => $id, 'name' => 'Conn'])->assertOk();
}

it('reports a floating node through validate_workflow', function (): void {
    $id = 'wf_float';
    connBuild($id);

    FlowBuilderServer::tool(AddNodeTool::class, ['workflow_id' => $id, 'kind' => 'manual_trigger', 'node_id' => 'start'])->assertOk();
    FlowBuilderServer::tool(AddNodeTool::class, ['workflow_id' => $id, 'kind' => 'output', 'node_id' => 'end'])->assertOk();
    FlowBuilderServer::tool(ConnectNodesTool::class, [
        'workflow_id' => $id, 'source' => 'start', 'target' => 'end',
    ])->assertOk();

    // Added and never wired — the shape an agent produces when it plans nodes
    // first and connects them after, then loses track of one.
    FlowBuilderServer::tool(AddNodeTool::class, ['workflow_id' => $id, 'kind' => 'log', 'node_id' => 'stray'])->assertOk();

    FlowBuilderServer::tool(ValidateWorkflowTool::class, ['workflow_id' => $id])
        ->assertOk()
        ->assertSee('connected to nothing')
        ->assertSee('stray');
});

it('refuses to RUN a graph with a floating node', function (): void {
    $id = 'wf_float_run';
    connBuild($id);

    FlowBuilderServer::tool(AddNodeTool::class, ['workflow_id' => $id, 'kind' => 'manual_trigger', 'node_id' => 'start'])->assertOk();
    FlowBuilderServer::tool(AddNodeTool::class, ['workflow_id' => $id, 'kind' => 'output', 'node_id' => 'end'])->assertOk();
    FlowBuilderServer::tool(ConnectNodesTool::class, ['workflow_id' => $id, 'source' => 'start', 'target' => 'end'])->assertOk();
    FlowBuilderServer::tool(AddNodeTool::class, ['workflow_id' => $id, 'kind' => 'log', 'node_id' => 'stray'])->assertOk();

    // A refusal, not a crash: the agent is told to fix it and pointed at the
    // tool that explains what.
    FlowBuilderServer::tool(RunWorkflowTool::class, ['workflow_id' => $id])
        ->assertOk()
        ->assertSee('validation errors');
});

it('catches the node a DELETION stranded', function (): void {
    $id = 'wf_deleted';
    connBuild($id);

    // start -> middle -> end
    FlowBuilderServer::tool(AddNodeTool::class, ['workflow_id' => $id, 'kind' => 'manual_trigger', 'node_id' => 'start'])->assertOk();
    FlowBuilderServer::tool(AddNodeTool::class, [
        'workflow_id' => $id, 'kind' => 'transform', 'node_id' => 'middle',
        'config' => ['expression' => '{{ $json }}'],
    ])->assertOk();
    FlowBuilderServer::tool(AddNodeTool::class, ['workflow_id' => $id, 'kind' => 'output', 'node_id' => 'end'])->assertOk();
    FlowBuilderServer::tool(ConnectNodesTool::class, ['workflow_id' => $id, 'source' => 'start', 'target' => 'middle'])->assertOk();
    FlowBuilderServer::tool(ConnectNodesTool::class, ['workflow_id' => $id, 'source' => 'middle', 'target' => 'end'])->assertOk();

    // Both edges go with it, so `start` and `end` are now islands of one.
    FlowBuilderServer::tool(RemoveNodeTool::class, ['workflow_id' => $id, 'node_id' => 'middle'])->assertOk();

    FlowBuilderServer::tool(ValidateWorkflowTool::class, ['workflow_id' => $id])
        ->assertOk()
        ->assertSee('connected to nothing');
});

it('reports an edge read from a terminal node', function (): void {
    $id = 'wf_term';
    connBuild($id);

    FlowBuilderServer::tool(AddNodeTool::class, ['workflow_id' => $id, 'kind' => 'manual_trigger', 'node_id' => 'start'])->assertOk();
    FlowBuilderServer::tool(AddNodeTool::class, ['workflow_id' => $id, 'kind' => 'output', 'node_id' => 'out'])->assertOk();
    FlowBuilderServer::tool(AddNodeTool::class, ['workflow_id' => $id, 'kind' => 'log', 'node_id' => 'after'])->assertOk();
    FlowBuilderServer::tool(ConnectNodesTool::class, ['workflow_id' => $id, 'source' => 'start', 'target' => 'out'])->assertOk();

    // `connect_nodes` validates PORTS. Whether it refuses this outright or lets
    // it through for `validate_workflow` to catch, the graph must not end up
    // both saved and reported clean — which is what this asserts.
    FlowBuilderServer::tool(ConnectNodesTool::class, ['workflow_id' => $id, 'source' => 'out', 'target' => 'after']);

    // Either the edge was never created (one edge, ok true) or it was, and
    // validation now refuses it. Both are correct; silently keeping it AND
    // reporting clean is not -- so the assertion is on the pair, not on which
    // of the two layers caught it.
    FlowBuilderServer::tool(ValidateWorkflowTool::class, ['workflow_id' => $id])
        ->assertOk()
        ->assertSee('"edge_count": 1');
});

it('still validates a correctly wired graph clean', function (): void {
    // The control. Without it, a check that refused EVERYTHING would pass every
    // test above.
    $id = 'wf_fine';
    connBuild($id);

    FlowBuilderServer::tool(AddNodeTool::class, ['workflow_id' => $id, 'kind' => 'manual_trigger', 'node_id' => 'start'])->assertOk();
    FlowBuilderServer::tool(AddNodeTool::class, [
        'workflow_id' => $id, 'kind' => 'transform', 'node_id' => 't',
        'config' => ['expression' => '{{ $json }}'],
    ])->assertOk();
    FlowBuilderServer::tool(AddNodeTool::class, ['workflow_id' => $id, 'kind' => 'output', 'node_id' => 'end'])->assertOk();
    FlowBuilderServer::tool(ConnectNodesTool::class, ['workflow_id' => $id, 'source' => 'start', 'target' => 't'])->assertOk();
    FlowBuilderServer::tool(ConnectNodesTool::class, ['workflow_id' => $id, 'source' => 't', 'target' => 'end'])->assertOk();

    FlowBuilderServer::tool(ValidateWorkflowTool::class, ['workflow_id' => $id])
        ->assertOk()
        ->assertSee('"ok": true');
});

it('lets a note float, so an agent can annotate what it built', function (): void {
    $id = 'wf_note';
    connBuild($id);

    FlowBuilderServer::tool(AddNodeTool::class, ['workflow_id' => $id, 'kind' => 'manual_trigger', 'node_id' => 'start'])->assertOk();
    FlowBuilderServer::tool(AddNodeTool::class, ['workflow_id' => $id, 'kind' => 'output', 'node_id' => 'end'])->assertOk();
    FlowBuilderServer::tool(ConnectNodesTool::class, ['workflow_id' => $id, 'source' => 'start', 'target' => 'end'])->assertOk();
    FlowBuilderServer::tool(AddNodeTool::class, [
        'workflow_id' => $id, 'kind' => 'note', 'node_id' => 'why',
        'config' => ['text' => 'explains the branch below'],
    ])->assertOk();

    FlowBuilderServer::tool(ValidateWorkflowTool::class, ['workflow_id' => $id])
        ->assertOk()
        ->assertSee('"ok": true');
});
