<?php

declare(strict_types=1);

use FancyFlow\Mcp\Server\FlowBuilderServer;

/**
 * The version this server ADVERTISES must be the version it ships as.
 *
 * An MCP server's `#[Version]` is not decoration: it goes out in the
 * `serverInfo` of every `initialize` response, so it is the number every
 * connecting agent records, logs, and may gate behaviour on. This one was the
 * literal `'0.1.0'` while the package shipped as 0.4.0 — three minor releases
 * stale — and nothing anywhere compared the two.
 *
 * That is the same failure found in every other version surface in this estate:
 * a number living in two files with nothing comparing them. It is the failure
 * the envelope's `kit.json` rule exists to stop, and the one that let a footer
 * drift twelve minor versions behind before anyone noticed.
 *
 * A PHP attribute argument must be a constant expression, so this cannot read
 * the changelog at runtime the way the Node and Python packages read their
 * packaging metadata. One constant plus this assertion is the closest available
 * equivalent: the copy still exists, but it can no longer drift unnoticed.
 */
it('advertises the version named in the newest changelog entry', function () {
    $changelog = (string) file_get_contents(__DIR__.'/../../CHANGELOG.md');

    // The newest RELEASE heading — `## [Unreleased]` carries no version and is
    // always on top.
    preg_match_all('/^## \[?v?(\d+\.\d+\.\d+)\]?/m', $changelog, $m);

    expect($m[1])->not->toBeEmpty('no released version heading in CHANGELOG.md');
    expect(FlowBuilderServer::VERSION)->toBe(
        $m[1][0],
        'the advertised version and the newest changelog entry disagree. Fix the constant — '
        .'every agent that connects is told this number.'
    );
});

it('actually puts that constant in the #[Version] attribute', function () {
    // Guard the WIRING, not just the constant. Someone could bump `VERSION`
    // correctly and leave the attribute holding its own literal, which is
    // exactly the state this test was written to end — the constant would be
    // right and the handshake would still lie.
    $attributes = (new ReflectionClass(FlowBuilderServer::class))->getAttributes();

    $advertised = null;
    foreach ($attributes as $attribute) {
        if (str_contains($attribute->getName(), 'Version')) {
            $advertised = $attribute->getArguments()[0] ?? null;
        }
    }

    expect($advertised)->not->toBeNull('the server declares no #[Version] attribute at all');
    expect($advertised)->toBe(FlowBuilderServer::VERSION);
});
