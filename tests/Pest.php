<?php

use App\Models\Site;
use App\Models\User;
use App\Models\Workspace;
use App\Support\Tenancy\CurrentWorkspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Tests\Support\FakeGitHub;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
| Feature tests run against an in-memory SQLite database, inside Bob's internal workspace
| ($this->workspace, owned by admin $this->user). No test may call a real external API:
| every HTTP call must be faked with Http::fake() (TestCase prevents stray requests).
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        $this->workspace = Workspace::factory()->create(['plan' => 'dalaman']);
        $this->user = User::factory()->create(['workspace_id' => $this->workspace->id, 'role' => 'owner', 'is_admin' => true]);
        app(CurrentWorkspace::class)->set($this->workspace);
    })
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');

function actingAsAdmin(): TestCase
{
    return test()->actingAs(test()->user);
}

/** A second, unrelated customer with its own (non-admin) user. */
function otherWorkspace(): array
{
    $workspace = Workspace::factory()->create();
    $user = User::factory()->create(['workspace_id' => $workspace->id]);

    return [$workspace, $user];
}

/** A website repo with a home page that has the latest-articles markers. */
function siteRepo(array $extra = []): array
{
    return array_replace([
        'index.html' => "<html><body><h1>Gabion Pantai Timur</h1>\n<!-- dynoseo:latest --><!-- /dynoseo:latest -->\n<footer>Hubungi kami</footer></body></html>",
        'sitemap.xml' => '<?xml version="1.0"?><urlset><url><loc>https://example.my/</loc></url></urlset>',
    ], $extra);
}

/** Fake GitHub with the given repos (repo => files). */
function fakeGitHub(array $repos): FakeGitHub
{
    $github = new FakeGitHub($repos);
    test()->github = $github;

    return $github;
}

/** About $words words of plain, rule-abiding Bahasa Melayu article body with three H2s. */
function articleBody(int $words = 850, string $extra = ''): string
{
    $sentences = [
        'Gabion ialah bakul dawai yang diisi dengan batu dan disusun untuk menahan tanah di cerun.',
        'Struktur ini membenarkan air mengalir keluar, jadi tekanan air di belakang tembok menjadi lebih rendah.',
        'Pemilik rumah di kawasan berbukit selalu risau tentang tanah runtuh selepas hujan lebat.',
        'Pemeriksaan tapak oleh jurutera membantu memilih jenis tembok yang sesuai dengan keadaan tanah.',
        'Batu sungai dan batu kuari boleh digunakan, bergantung pada saiz bakul dan reka bentuk.',
        'Tumbuhan boleh tumbuh di celah batu, menjadikan cerun lebih hijau dan stabil dari semasa ke semasa.',
    ];
    $paragraphs = [];
    $count = 0;
    $i = 0;
    while ($count < $words) {
        $p = implode(' ', array_slice(array_merge($sentences, $sentences), $i % 6, 3));
        $paragraphs[] = '<p>'.$p.'</p>';
        $count += str_word_count($p) + substr_count($p, '-');
        $i++;
    }
    $chunks = array_chunk($paragraphs, (int) ceil(count($paragraphs) / 3));

    return '<h2>Apa itu gabion</h2>'.implode('', $chunks[0])
        .'<h2>Kelebihan untuk cerun rumah</h2>'.implode('', $chunks[1] ?? [])
        .'<h2>Rumusan</h2>'.implode('', $chunks[2] ?? []).$extra;
}

function draftReply(array $overrides = []): array
{
    return array_replace([
        'title' => 'Gabion Wall vs Concrete Wall untuk Cerun Rumah',
        'slug' => 'gabion-wall-vs-concrete-wall',
        'meta_description' => 'Bandingkan gabion wall dan concrete wall untuk cerun rumah: cara ia berfungsi, saliran air dan bila setiap satu lebih sesuai.',
        'summary' => 'Perbandingan ringkas gabion wall dan concrete wall untuk cerun rumah.',
        'body_html' => articleBody(),
        'faq' => [['q' => 'Adakah gabion tahan lama?', 'a' => 'Ya, jika dawai bersalut dan dipasang dengan betul.']],
        'image_prompt' => 'a gabion retaining wall on a tropical hillside beside a Malaysian kampung house',
        'image_alt' => 'Tembok gabion di cerun bukit',
        'cta_bridge' => '',
    ], $overrides);
}

function planReply(array $topics = []): array
{
    return ['topics' => $topics ?: [
        ['title' => 'Gabion wall vs concrete wall untuk cerun rumah', 'target_keyword' => 'gabion wall vs concrete wall', 'intent' => 'perbandingan', 'cluster' => 'tembok'],
        ['title' => 'Tanda awal cerun belakang rumah akan runtuh', 'target_keyword' => 'tanda cerun runtuh', 'intent' => 'senarai', 'cluster' => 'cerun'],
        ['title' => 'Apa itu soil nailing dan bila ia digunakan', 'target_keyword' => 'soil nailing', 'intent' => 'penerangan', 'cluster' => 'cerun'],
        ['title' => 'Cara jaga cerun semasa musim tengkujuh', 'target_keyword' => 'jaga cerun musim tengkujuh', 'intent' => 'musim', 'cluster' => 'cerun'],
        ['title' => 'Jenis batu yang sesuai untuk gabion', 'target_keyword' => 'batu gabion', 'intent' => 'panduan', 'cluster' => 'tembok'],
    ]];
}

/** Which fake queue a Vertex text request belongs to, from its response schema. */
function vertexPurpose(array $schemaProperties): string
{
    return match (true) {
        isset($schemaProperties['pillars']) => 'analyse',
        isset($schemaProperties['topics']) => 'plan',
        isset($schemaProperties['pass']) => 'check',
        default => 'write',
    };
}

/** A business analysis reply (SiteAnalyst). */
function analysisReply(array $overrides = []): array
{
    return array_replace([
        'field' => 'sewa skylift dan kerja di tempat tinggi',
        'summary' => 'Sewa lori skylift dengan operator untuk kerja pasang papan tanda, lampu jalan dan cat bangunan tinggi.',
        'services' => ['sewa skylift', 'pasang papan tanda', 'kerja lampu jalan'],
        'customers' => ['kontraktor', 'pemilik kedai', 'pengurusan bangunan'],
        'searches' => ['sewa skylift kelantan', 'harga sewa skylift sehari'],
        'pillars' => [
            ['name' => 'keselamatan kerja tinggi', 'about' => 'Amalan selamat bekerja di tempat tinggi.'],
            ['name' => 'pilih skylift', 'about' => 'Jenis dan saiz skylift ikut kerja.'],
            ['name' => 'papan tanda', 'about' => 'Pasang dan selenggara papan tanda.'],
        ],
        'avoid' => ['oksigen', 'tangki oksigen', 'penerbangan'],
        'confidence' => 'tinggi',
        'question' => '',
    ], $overrides);
}

/** A Gemini generateContent response whose text is $data as JSON. */
function geminiReply(array|string $data, int $input = 1200, int $output = 1500): array
{
    return [
        'candidates' => [['content' => ['role' => 'model', 'parts' => [['text' => is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE)]]], 'finishReason' => 'STOP']],
        'usageMetadata' => ['promptTokenCount' => $input, 'candidatesTokenCount' => $output, 'totalTokenCount' => $input + $output],
        'modelVersion' => 'test',
    ];
}

function tinyPng(): string
{
    $img = imagecreatetruecolor(64, 36);
    imagefill($img, 0, 0, imagecolorallocate($img, 40, 120, 90));
    ob_start();
    imagepng($img);

    return (string) ob_get_clean();
}

/**
 * Fake Vertex AI. Queues per purpose (plan, write, check); the last reply repeats when a
 * queue runs out. Images return a small PNG unless $image is false.
 */
function fakeVertex(array $write = [], array $check = [], array $plan = [], bool|array $image = true, ?FakeGitHub $github = null, array $extra = [], array $analyse = []): void
{
    $queues = [
        'analyse' => $analyse ?: [geminiReply(analysisReply())],
        'plan' => $plan ?: [geminiReply(planReply())],
        'write' => $write ?: [geminiReply(draftReply())],
        'check' => $check ?: [geminiReply(['pass' => true, 'issues' => []], 2000, 50)],
    ];

    Http::fake(array_merge($extra, [
        'oauth2.googleapis.com/*' => Http::response(['access_token' => 'ya29.test', 'expires_in' => 3599]),
        '*aiplatform.googleapis.com/*' => function (Request $request) use (&$queues, $image) {
            if (str_ends_with($request->url(), ':predict')) {
                if (is_array($image)) {
                    return Http::response($image['body'] ?? [], $image['status'] ?? 200);
                }

                return Http::response(['predictions' => $image ? [['bytesBase64Encoded' => base64_encode(tinyPng()), 'mimeType' => 'image/png']] : []]);
            }

            $schema = $request['generationConfig']['responseSchema']['properties'] ?? [];
            $key = vertexPurpose($schema);
            $reply = count($queues[$key]) > 1 ? array_shift($queues[$key]) : $queues[$key][0];

            return isset($reply['__status']) ? Http::response($reply['body'], $reply['__status']) : Http::response($reply);
        },
        'api.github.com/*' => $github ? $github->handler() : Http::response(['message' => 'Not Found'], 404),
        'api.indexnow.org/*' => Http::response('', 200),
    ]));
}

/** Requests sent to Vertex for one purpose (plan, write, check, image). */
function vertexRequests(string $purpose): Collection
{
    return Http::recorded(function ($request) use ($purpose) {
        if (! str_contains($request->url(), 'aiplatform.googleapis.com')) {
            return false;
        }
        if ($purpose === 'image') {
            return str_ends_with($request->url(), ':predict');
        }
        if (str_ends_with($request->url(), ':predict')) {
            return false;
        }
        $schema = $request['generationConfig']['responseSchema']['properties'] ?? [];

        return vertexPurpose($schema) === $purpose;
    })->map(fn ($pair) => $pair[0]);
}

function vertexCalls(): int
{
    return Http::recorded(fn ($r) => str_contains($r->url(), 'aiplatform.googleapis.com'))->count();
}

/** An active, installed website in the test workspace backed by a fake repo. */
function makeSite(array $attributes = [], array $files = []): Site
{
    $site = Site::factory()->create(array_merge(['workspace_id' => test()->workspace->id], $attributes));
    $github = test()->github ?? fakeGitHub([]);
    $github->repos[$site->github_repo] = siteRepo($files);

    return $site;
}
