<?php

namespace Tests\Support;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * In-memory GitHub for tests: one or more repos with files, and the Git Data API flow
 * (ref → commit → blobs → tree → commit → update ref) applied to them.
 */
class FakeGitHub
{
    /** @var array<string, array<string, string>> repo => path => content */
    public array $repos = [];

    /** @var list<array{repo:string, message:string, paths:list<string>}> */
    public array $commits = [];

    public int $failCommits = 0;

    private array $blobs = [];

    private array $trees = [];

    private array $pendingCommits = [];

    private int $seq = 0;

    public function __construct(array $repos = [])
    {
        $this->repos = $repos;
    }

    public function handler(): \Closure
    {
        return function (Request $request) {
            $path = parse_url($request->url(), PHP_URL_PATH);
            $method = $request->method();

            if (! preg_match('#^/repos/([^/]+/[^/]+)(/.*)?$#', $path, $m)) {
                return Http::response(['message' => 'Not Found'], 404);
            }

            $repo = $m[1];
            $rest = $m[2] ?? '';

            if (! array_key_exists($repo, $this->repos)) {
                return Http::response(['message' => 'Not Found'], 404);
            }

            if ($rest === '' && $method === 'GET') {
                return Http::response(['full_name' => $repo, 'permissions' => ['push' => true]]);
            }

            if (str_starts_with($rest, '/branches/')) {
                return Http::response(['name' => 'main']);
            }

            if (str_starts_with($rest, '/contents/') && $method === 'GET') {
                $file = rawurldecode(substr($rest, strlen('/contents/')));
                if (! isset($this->repos[$repo][$file])) {
                    return Http::response(['message' => 'Not Found'], 404);
                }

                return Http::response([
                    'type' => 'file',
                    'sha' => sha1($this->repos[$repo][$file]),
                    'size' => strlen($this->repos[$repo][$file]),
                    'content' => chunk_split(base64_encode($this->repos[$repo][$file]), 60, "\n"),
                ]);
            }

            if (str_starts_with($rest, '/git/ref/heads/')) {
                return Http::response(['object' => ['sha' => 'head-'.$repo]]);
            }

            if (preg_match('#^/git/commits/(.+)$#', $rest)) {
                return Http::response(['tree' => ['sha' => 'base-tree']]);
            }

            if (str_starts_with($rest, '/git/trees/') && $method === 'GET') {
                return Http::response(['tree' => collect(array_keys($this->repos[$repo]))->map(fn ($p) => ['path' => $p, 'type' => 'blob'])->all()]);
            }

            if ($rest === '/git/blobs' && $method === 'POST') {
                $sha = 'blob-'.(++$this->seq);
                $this->blobs[$sha] = base64_decode($request['content']);

                return Http::response(['sha' => $sha], 201);
            }

            if ($rest === '/git/trees' && $method === 'POST') {
                $sha = 'tree-'.(++$this->seq);
                $this->trees[$sha] = $request['tree'];

                return Http::response(['sha' => $sha], 201);
            }

            if ($rest === '/git/commits' && $method === 'POST') {
                $sha = 'commit-'.(++$this->seq);
                $this->pendingCommits[$sha] = ['tree' => $request['tree'], 'message' => $request['message']];

                return Http::response(['sha' => $sha], 201);
            }

            if (str_starts_with($rest, '/git/refs/heads/') && $method === 'PATCH') {
                if ($this->failCommits > 0) {
                    $this->failCommits--;

                    return Http::response(['message' => 'Server Error'], 500);
                }

                $commit = $this->pendingCommits[$request['sha']];
                $paths = [];
                foreach ($this->trees[$commit['tree']] as $entry) {
                    $paths[] = $entry['path'];
                    if ($entry['sha'] === null) {
                        unset($this->repos[$repo][$entry['path']]);
                    } else {
                        $this->repos[$repo][$entry['path']] = $this->blobs[$entry['sha']];
                    }
                }
                $this->commits[] = ['repo' => $repo, 'message' => $commit['message'], 'paths' => $paths];

                return Http::response(['object' => ['sha' => $request['sha']]]);
            }

            return Http::response(['message' => 'Unhandled '.$method.' '.$rest], 500);
        };
    }

    public function file(string $repo, string $path): ?string
    {
        return $this->repos[$repo][$path] ?? null;
    }
}
