<?php

namespace App\Services\Sites;

use App\Enums\SiteStatus;
use App\Jobs\PublishSiteArticle;
use App\Models\Site;
use App\Services\Publishing\GitHubClient;
use App\Services\Publishing\SitePublisher;
use App\Support\MalaysianPhone;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/** Admin actions on websites (spec §3.1–3.2). Livewire screens call these. */
class SiteService
{
    public function __construct(
        private GitHubClient $github,
        private SitePublisher $publisher,
    ) {}

    public static function rules(): array
    {
        return [
            'name' => 'required|string|max:120',
            'domain' => ['required', 'string', 'max:190', 'regex:/^(https?:\/\/)?[a-z0-9.-]+\.[a-z]{2,}\/?$/i'],
            'github_repo' => ['required', 'string', 'max:190', 'regex:/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/'],
            'branch' => 'required|string|max:100',
            'docroot' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]*$/'],
            'niche' => 'required|string|max:500',
            'audience' => 'nullable|string|max:500',
            'area' => 'nullable|string|max:190',
            'banned_topics' => 'nullable|string|max:1000',
            'frequency_days' => 'required|integer|min:1|max:30',
            'paid_until' => 'nullable|date',
            // Hook fields are optional: empty ones get a standard hook (defaultCta()).
            'cta_title' => 'nullable|string|max:120',
            'cta_text' => 'nullable|string|max:300',
            'cta_button' => 'nullable|string|max:40',
            'cta_whatsapp' => 'nullable|string|max:30',
            'cta_message' => 'nullable|string|max:300',
            'cta_url' => ['nullable', 'string', 'max:300', 'regex:/^(https?:\/\/|\/)/i'],
            'cta_bridge' => 'boolean',
        ];
    }

    public static function messages(): array
    {
        return [
            'required' => 'Wajib diisi.',
            'domain.regex' => 'Contoh: gabionpantaitimur.my',
            'github_repo.regex' => 'Format: pemilik/nama-repo, contoh Dynopos/gabion-pantai-timur',
            'docroot.regex' => 'Nama folder sahaja, contoh public.',
            'cta_url.regex' => 'Mula dengan / (contoh /hubungi/) atau https://',
        ];
    }

    /** Form fields for an existing site, or defaults for a new one. */
    public static function form(?Site $site = null): array
    {
        $cta = (array) ($site?->cta ?? []);

        return [
            'name' => $site?->name ?? '',
            'domain' => $site?->domain ?? '',
            'github_repo' => $site?->github_repo ?? '',
            'branch' => $site?->branch ?? 'main',
            'docroot' => $site?->docroot ?? '',
            'niche' => $site?->niche ?? '',
            'audience' => $site?->audience ?? '',
            'area' => $site?->area ?? '',
            'banned_topics' => $site?->banned_topics ?? '',
            'frequency_days' => $site?->frequency_days ?? (int) config('dynoseo.articles.default_frequency_days'),
            'paid_until' => $site?->paid_until?->toDateString() ?? '',
            'cta_title' => $cta['title'] ?? '',
            'cta_text' => $cta['text'] ?? '',
            'cta_button' => $cta['button'] ?? '',
            'cta_whatsapp' => $cta['whatsapp'] ?? '',
            'cta_message' => $cta['message'] ?? 'Salam, saya baca artikel "{tajuk}" di website anda. Nak tanya...',
            'cta_url' => $cta['url'] ?? '',
            'cta_bridge' => (bool) ($site?->cta_bridge ?? false),
        ];
    }

    /** Create or update. The hook must lead somewhere: a mobile WhatsApp number or a link. */
    public function save(array $data, ?Site $site = null): Site
    {
        if (! MalaysianPhone::isMobile($data['cta_whatsapp'] ?? null) && trim((string) ($data['cta_url'] ?? '')) === '') {
            throw ValidationException::withMessages(['form.cta_whatsapp' => 'Isi nombor WhatsApp telefon bimbit (01x...) atau pautan hook.']);
        }

        $attributes = [
            'name' => Str::squish($data['name']),
            'domain' => Str::lower(rtrim(preg_replace('#^https?://#i', '', trim($data['domain'])), '/')),
            'github_repo' => trim($data['github_repo']),
            'branch' => trim($data['branch']),
            'docroot' => trim((string) ($data['docroot'] ?? ''), '/ '),
            'niche' => trim($data['niche']),
            'audience' => trim((string) ($data['audience'] ?? '')) ?: null,
            'area' => trim((string) ($data['area'] ?? '')) ?: null,
            'banned_topics' => trim((string) ($data['banned_topics'] ?? '')) ?: null,
            'frequency_days' => (int) $data['frequency_days'],
            'paid_until' => ($data['paid_until'] ?? '') ?: null,
            'cta' => [
                'title' => trim((string) ($data['cta_title'] ?? '')) ?: self::defaultCta($data['name'])['title'],
                'text' => trim((string) ($data['cta_text'] ?? '')) ?: self::defaultCta($data['name'])['text'],
                'button' => trim((string) ($data['cta_button'] ?? '')) ?: self::defaultCta($data['name'])['button'],
                'whatsapp' => trim((string) ($data['cta_whatsapp'] ?? '')) ?: null,
                'message' => trim((string) ($data['cta_message'] ?? '')) ?: null,
                'url' => trim((string) ($data['cta_url'] ?? '')) ?: null,
            ],
            'cta_bridge' => (bool) ($data['cta_bridge'] ?? false),
        ];

        if ($site === null) {
            // New websites start paused: Bob checks the repo and installs before activating.
            return Site::query()->create($attributes + ['status' => SiteStatus::Paused]);
        }

        $site->update($attributes);

        return $site;
    }

    /** Standard hook when Bob leaves the hook fields empty (a template, not AI: rule 1). */
    public static function defaultCta(string $name): array
    {
        return [
            'title' => 'Berminat dengan '.Str::squish($name).'?',
            'text' => 'WhatsApp kami sekarang. Kami sedia bantu dan jawab soalan anda.',
            'button' => '💬 WhatsApp Kami',
        ];
    }

    /**
     * Returns a problem in BM, or null when the repo is reachable and writable. A Laravel
     * app (artisan + public/index.php) is detected and its web folder set to public.
     */
    public function check(Site $site): ?string
    {
        try {
            $problem = $this->github->checkAccess($site->github_repo, $site->branch);

            if ($problem === null && trim((string) $site->docroot) === ''
                && $this->github->getFile($site->github_repo, $site->branch, 'artisan') !== null
                && $this->github->getFile($site->github_repo, $site->branch, 'public/index.php') !== null) {
                $site->forceFill(['docroot' => 'public'])->save();
            }

            return $problem;
        } catch (Throwable $e) {
            return $e->getMessage();
        }
    }

    /**
     * New website in one step: check the repo, install the blog, activate. Returns a problem
     * in BM (the website stays paused), or null when the first article is on its way.
     */
    public function setUp(Site $site): ?string
    {
        $problem = $this->check($site);

        if ($problem !== null) {
            return $problem;
        }

        try {
            if (! $site->installed_at) {
                $this->publisher->install($site);
            }
        } catch (Throwable $e) {
            report($e);

            return 'Pasang blog gagal: '.$e->getMessage();
        }

        // A new website starts only after its business analysis is confirmed.
        if (! $site->refresh()->hasConfirmedAnalysis()) {
            return 'Semak analisis bisnes di bawah dan tekan "Betul, sahkan", kemudian Aktifkan.';
        }

        $this->activate($site);

        return null;
    }

    public function install(Site $site): void
    {
        $this->publisher->install($site);
    }

    public function activate(Site $site): void
    {
        $site->forceFill([
            'status' => SiteStatus::Active,
            'failure_count' => 0,
            'status_note' => null,
            'next_publish_at' => $site->next_publish_at?->isFuture() ? $site->next_publish_at : now(),
        ])->save();
    }

    public function pause(Site $site): void
    {
        $site->forceFill(['status' => SiteStatus::Paused, 'status_note' => null])->save();
    }

    /** After GCP billing is fixed: every website paused for AI becomes active again. */
    public function resumeAiPaused(): int
    {
        return Site::query()
            ->where('status', SiteStatus::AiPaused)
            ->update(['status' => SiteStatus::Active, 'status_note' => null, 'failure_count' => 0, 'next_publish_at' => now()]);
    }

    /** Write and publish one article now, in the background. */
    public function publishNow(Site $site): void
    {
        PublishSiteArticle::dispatch($site->id, true);
    }

    public function rebuild(Site $site): void
    {
        $this->publisher->rebuild($site);
    }
}
