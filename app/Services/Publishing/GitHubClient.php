<?php

namespace App\Services\Publishing;

use App\Exceptions\GitHubException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * The only class that talks to the GitHub REST API. Writes go through the Git Data API so
 * that every publish is ONE commit with all its files (article, image, blog index, sitemap,
 * feed, home block). Token: fine-grained, Contents read & write on website repos only.
 */
class GitHubClient
{
    /** Repo exists, token can push, and the branch exists. Returns a problem in BM, or null. */
    public function checkAccess(string $repo, string $branch): ?string
    {
        $response = $this->http()->get("/repos/{$repo}");

        if ($response->status() === 404) {
            return "Repo {$repo} tidak dijumpai, atau token GitHub tiada akses kepadanya.";
        }

        if ($response->failed()) {
            return $this->describe($response);
        }

        if ($response->json('permissions.push') === false) {
            return "Token GitHub tiada kebenaran tulis (Contents: read and write) untuk {$repo}.";
        }

        $branchResponse = $this->http()->get("/repos/{$repo}/branches/".rawurlencode($branch));

        if ($branchResponse->status() === 404) {
            return "Branch {$branch} tiada dalam {$repo}.";
        }

        return $branchResponse->failed() ? $this->describe($branchResponse) : null;
    }

    /** @return array{content:string, sha:string}|null */
    public function getFile(string $repo, string $branch, string $path): ?array
    {
        $response = $this->http()->get("/repos/{$repo}/contents/".$this->encodePath($path), ['ref' => $branch]);

        if ($response->status() === 404) {
            return null;
        }

        if ($response->failed()) {
            throw new GitHubException($this->describe($response), $response->status());
        }

        if ($response->json('type') !== 'file') {
            return null;
        }

        $content = (string) $response->json('content');

        // Files over 1 MB come back without content; fetch them as blobs.
        if ($content === '' && $response->json('size') > 0) {
            $blob = $this->http()->get("/repos/{$repo}/git/blobs/".$response->json('sha'));
            $content = (string) $blob->json('content');
        }

        return [
            'content' => (string) base64_decode(str_replace("\n", '', $content)),
            'sha' => (string) $response->json('sha'),
        ];
    }

    /**
     * One commit on $branch. $files maps repo path => new content, or null to delete.
     * Deleting a path that does not exist is skipped. Returns the new commit sha, or the
     * current head when nothing changed. Retries once if the branch moved meanwhile.
     *
     * @param  array<string, string|null>  $files
     */
    public function commit(string $repo, string $branch, array $files, string $message): string
    {
        try {
            return $this->attemptCommit($repo, $branch, $files, $message);
        } catch (GitHubException $e) {
            if ($e->status !== 422 && $e->status !== 409) {
                throw $e;
            }

            return $this->attemptCommit($repo, $branch, $files, $message);
        }
    }

    private function attemptCommit(string $repo, string $branch, array $files, string $message): string
    {
        $ref = $this->json($this->http()->get("/repos/{$repo}/git/ref/heads/".rawurlencode($branch)));
        $headSha = (string) $ref['object']['sha'];
        $baseTree = (string) $this->json($this->http()->get("/repos/{$repo}/git/commits/{$headSha}"))['tree']['sha'];

        $deletes = array_keys(array_filter($files, fn ($content) => $content === null));
        $existing = $deletes === [] ? [] : $this->treePaths($repo, $baseTree);

        $tree = [];
        foreach ($files as $path => $content) {
            if ($content === null) {
                if (isset($existing[$path])) {
                    $tree[] = ['path' => $path, 'mode' => '100644', 'type' => 'blob', 'sha' => null];
                }

                continue;
            }

            $blob = $this->json($this->http()->post("/repos/{$repo}/git/blobs", [
                'content' => base64_encode($content),
                'encoding' => 'base64',
            ]));

            $tree[] = ['path' => $path, 'mode' => '100644', 'type' => 'blob', 'sha' => $blob['sha']];
        }

        if ($tree === []) {
            return $headSha;
        }

        $newTree = (string) $this->json($this->http()->post("/repos/{$repo}/git/trees", [
            'base_tree' => $baseTree,
            'tree' => $tree,
        ]))['sha'];

        if ($newTree === $baseTree) {
            return $headSha;
        }

        $commit = (string) $this->json($this->http()->post("/repos/{$repo}/git/commits", [
            'message' => $message,
            'tree' => $newTree,
            'parents' => [$headSha],
        ]))['sha'];

        $this->json($this->http()->patch("/repos/{$repo}/git/refs/heads/".rawurlencode($branch), [
            'sha' => $commit,
            'force' => false,
        ]));

        return $commit;
    }

    /** @return array<string, true> */
    private function treePaths(string $repo, string $treeSha): array
    {
        $tree = $this->json($this->http()->get("/repos/{$repo}/git/trees/{$treeSha}", ['recursive' => 1]));

        return collect($tree['tree'] ?? [])->where('type', 'blob')->pluck('path')->flip()->map(fn () => true)->all();
    }

    private function json(Response $response): array
    {
        if ($response->failed()) {
            throw new GitHubException($this->describe($response), $response->status());
        }

        return (array) $response->json();
    }

    private function describe(Response $response): string
    {
        return match ($response->status()) {
            401 => 'Token GitHub tidak sah atau sudah tamat. Buat token baru dan kemas kini GITHUB_TOKEN.',
            403 => $this->describeForbidden($response),
            default => 'GitHub gagal ('.$response->status().'): '.$this->githubMessage($response),
        };
    }

    /**
     * A 403 has several unrelated causes, each fixed somewhere different. Name the cause
     * and keep GitHub's own message, so the admin knows which setting to open.
     */
    private function describeForbidden(Response $response): string
    {
        $message = $this->githubMessage($response);
        $lower = mb_strtolower($message);

        if ($response->header('x-ratelimit-remaining') === '0' || str_contains($lower, 'rate limit')) {
            $reset = (int) $response->header('x-ratelimit-reset');
            $when = $reset > 0 ? ' Cuba semula selepas '.now()->setTimestamp($reset)->timezone(config('app.timezone'))->format('H:i').'.' : ' Cuba semula sejam lagi.';

            return 'GitHub tolak (403): had kadar GitHub dicapai.'.$when;
        }

        $cause = match (true) {
            str_contains($lower, 'forbids access via a fine-grained personal access') => 'Organisasi GitHub menyekat token fine-grained. Buka organisasi → Settings → Personal access tokens → benarkan token fine-grained.',
            str_contains($lower, 'approv') || str_contains($lower, 'pending') => 'Token menunggu kelulusan organisasi. Buka organisasi → Settings → Personal access tokens → Pending requests → luluskan token dynoseo.',
            str_contains($lower, 'not accessible by personal access token') => 'Token tiada akses tulis ke repo ini. Edit token dynoseo: tambah repo ini, dan Contents = Read and write.',
            default => 'Token tiada kebenaran untuk tindakan ini.',
        };

        $needed = (string) $response->header('x-accepted-github-permissions');

        return 'GitHub tolak (403): '.$cause
            .($needed !== '' ? ' Kebenaran diperlukan: '.$needed.'.' : '')
            .($message !== '' ? ' Mesej GitHub: "'.$message.'"' : '');
    }

    /** GitHub's own error message, short. It never contains the token. */
    private function githubMessage(Response $response): string
    {
        return trim(mb_substr((string) ($response->json('message') ?? $response->body()), 0, 200));
    }

    private function encodePath(string $path): string
    {
        return implode('/', array_map('rawurlencode', explode('/', $path)));
    }

    private function http(): PendingRequest
    {
        $token = (string) config('services.github.token');

        if ($token === '') {
            throw new GitHubException('GITHUB_TOKEN belum diset dalam .env.');
        }

        return Http::baseUrl((string) config('services.github.base_url'))
            ->withToken($token)
            ->withHeaders(['X-GitHub-Api-Version' => '2022-11-28'])
            ->accept('application/vnd.github+json')
            ->timeout((int) config('services.github.timeout', 60));
    }
}
