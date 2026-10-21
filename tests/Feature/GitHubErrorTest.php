<?php

use App\Services\Publishing\GitHubClient;
use Illuminate\Support\Facades\Http;

/**
 * A GitHub 403 has several causes, each fixed in a different place. The admin must see
 * which one, plus GitHub's own message, not a generic guess.
 */
beforeEach(function () {
    config()->set('services.github.token', 'github_pat_ujian');
});

function githubForbidden(string $message, array $headers = []): void
{
    Http::fake(['*' => Http::response(['message' => $message], 403, $headers)]);
}

it('names a token without write access to the repo, with GitHub\'s message', function () {
    githubForbidden('Resource not accessible by personal access token', ['X-Accepted-GitHub-Permissions' => 'contents=write']);

    $problem = app(GitHubClient::class)->checkAccess('Dynopos/Dynosoro', 'main');

    expect($problem)->toContain('tambah repo ini')
        ->toContain('contents=write')
        ->toContain('Resource not accessible by personal access token')
        ->not->toContain('github_pat_ujian');
});

it('names an organisation that blocks fine-grained tokens', function () {
    githubForbidden('`Dynopos` forbids access via a fine-grained personal access tokens if the token\'s lifetime is greater than 366 days.');

    expect(app(GitHubClient::class)->checkAccess('Dynopos/Dynosoro', 'main'))
        ->toContain('Organisasi GitHub menyekat token fine-grained');
});

it('names a rate limit separately from a permission problem', function () {
    githubForbidden('API rate limit exceeded for user ID 1.', ['x-ratelimit-remaining' => '0', 'x-ratelimit-reset' => (string) now()->addMinutes(20)->timestamp]);

    expect(app(GitHubClient::class)->checkAccess('Dynopos/Dynosoro', 'main'))
        ->toContain('had kadar GitHub dicapai')
        ->not->toContain('kebenaran');
});
