<?php

declare(strict_types=1);

namespace FancyFlow\Mcp\Tools;

use FancyFlow\Mcp\Store\DraftStore;
use FancyFlow\Mcp\Support\DraftWorkflow;
use FancyFlow\Mcp\Support\FlowAuthoring;
use FancyFlow\Mcp\Support\FlowAuthoringException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

/**
 * Shared base for the flow-authoring tools: constructor DI of the two
 * collaborators every tool needs, a draft loader with a helpful not-found
 * message, and one place that turns a {@see FlowAuthoringException} into a clean
 * MCP error response — so each tool's {@see act()} can stay a few honest lines.
 */
abstract class FlowTool extends Tool
{
    public function __construct(
        protected readonly FlowAuthoring $authoring,
        protected readonly DraftStore $store,
    ) {}

    /** The one method a concrete tool implements. */
    abstract protected function act(Request $request): Response;

    public function handle(Request $request): Response
    {
        try {
            return $this->act($request);
        } catch (FlowAuthoringException $e) {
            return Response::error($e->getMessage());
        }
    }

    protected function draftOrFail(string $id): DraftWorkflow
    {
        $draft = $this->store->get($id);
        if ($draft === null) {
            $known = $this->store->ids();
            $hint = $known === []
                ? 'No workflows exist yet — start one with create_workflow.'
                : 'Known workflow ids: '.implode(', ', $known).'.';
            throw new FlowAuthoringException("No workflow \"{$id}\". {$hint}");
        }

        return $draft;
    }

    /** @return array<string,mixed>|null */
    protected function objectArg(Request $request, string $key): ?array
    {
        $value = $request->get($key);

        return is_array($value) ? $value : null;
    }
}
