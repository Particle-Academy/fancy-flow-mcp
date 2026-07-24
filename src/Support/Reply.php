<?php

declare(strict_types=1);

namespace FancyFlow\Mcp\Support;

use Laravel\Mcp\Response;

/**
 * One place to shape a tool's success payload. Every tool returns pretty-printed
 * JSON as text so the response is both human-readable in the MCP Inspector and
 * trivially parseable by the connecting agent (and by `assertSee` in tests).
 */
final class Reply
{
    /** @param array<string,mixed> $data */
    public static function json(array $data): Response
    {
        return Response::text((string) json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));
    }
}
