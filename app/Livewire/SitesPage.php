<?php

namespace App\Livewire;

use App\Enums\SiteStatus;
use App\Exceptions\GoogleBusinessException;
use App\Models\GoogleConnection;
use App\Models\Site;
use App\Services\Content\SiteAnalyst;
use App\Services\Content\TopicPlanner;
use App\Services\Google\GoogleBusinessClient;
use App\Services\Sites\SiteService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Layout('components.layouts.app')]
#[Title('Website')]
class SitesPage extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public array $form = [];

    public ?string $flash = null;

    public ?string $flashType = null;

    /** Owner's correction per website, sent with "Analisis semula". */
    public array $corrections = [];

    /** Business Profiles the connected Google account manages, for the website form. */
    public array $gbpLocations = [];

    public ?string $gbpError = null;

    public function create(): void
    {
        $this->editingId = null;
        $this->form = SiteService::form();
        $this->showForm = true;
        $this->resetErrorBag();
        $this->loadGbpLocations();
    }

    public function edit(int $id): void
    {
        $site = Site::query()->findOrFail($id);
        $this->editingId = $site->id;
        $this->form = SiteService::form($site);
        $this->showForm = true;
        $this->resetErrorBag();
        $this->loadGbpLocations();
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->editingId = null;
    }

    public function save(SiteService $sites, SiteAnalyst $analyst, TopicPlanner $planner): void
    {
        $this->form['gbp_location'] = (string) ($this->form['gbp_location'] ?? '');
        $chosen = collect($this->gbpLocations)->firstWhere('name', $this->form['gbp_location']);
        $current = $this->editingId ? (string) Site::query()->whereKey($this->editingId)->value('gbp_location') : '';

        // Only a profile the connected account manages (or the one already saved) can be chosen.
        if ($this->form['gbp_location'] !== '' && $chosen === null && $this->form['gbp_location'] !== $current) {
            $this->addError('form.gbp_location', 'Pilih profil dari senarai.');

            return;
        }

        if ($chosen !== null) {
            $this->form['gbp_title'] = $chosen['title'];
        }

        $rules = collect(SiteService::rules())->mapWithKeys(fn ($rule, $key) => ['form.'.$key => $rule])->all();
        $messages = collect(SiteService::messages())->mapWithKeys(fn ($m, $key) => [str_contains($key, '.') ? 'form.'.$key : $key => $m])->all();
        $data = $this->validate($rules, $messages)['form'];

        $site = $sites->save($data, $this->editingId ? Site::query()->findOrFail($this->editingId) : null);

        $this->showForm = false;
        $this->editingId = null;

        if (! $site->wasRecentlyCreated) {
            $this->say("Website {$site->name} dikemas kini. Tekan Bina semula jika hook berubah.".$this->planTargets($planner, $site));

            return;
        }

        // New website: understand the business first (read from the website itself), then
        // check the repo and install the blog. It goes live once the analysis is confirmed.
        try {
            $analyst->analyse($site);
        } catch (Throwable $e) {
            report($e);
        }

        $problem = $sites->setUp($site);

        $problem === null
            ? $this->say("{$site->name} siap dan aktif. Artikel pertama akan ditulis dalam 15 minit (atau tekan Terbit sekarang).")
            : $this->say("{$site->name} disimpan tetapi belum aktif: {$problem}", 'warning');
    }

    public function check(SiteService $sites, int $id): void
    {
        $site = Site::query()->findOrFail($id);
        $problem = $sites->check($site);

        $problem === null
            ? $this->say("Repo {$site->github_repo} OK: boleh tulis ke branch {$site->branch}.")
            : $this->say($problem, 'danger');
    }

    public function install(SiteService $sites, int $id): void
    {
        $this->attempt(fn () => $sites->install(Site::query()->findOrFail($id)), 'Blog dipasang: template, sitemap dan robots.txt dah di-commit.');
    }

    public function activate(SiteService $sites, int $id): void
    {
        $site = Site::query()->findOrFail($id);

        if (! $site->installed_at) {
            $this->say('Pasang blog dahulu sebelum aktifkan.', 'warning');

            return;
        }

        if (! $site->hasConfirmedAnalysis()) {
            $this->say('Sahkan analisis bisnes dahulu, supaya artikel kena dengan bidang bisnes ini.', 'warning');

            return;
        }

        $sites->activate($site);
        $this->say("{$site->name} aktif. Artikel pertama akan ditulis dalam 15 minit.");
    }

    public function analyse(SiteAnalyst $analyst, int $id): void
    {
        $site = Site::query()->findOrFail($id);

        $this->attempt(function () use ($analyst, $site) {
            $analyst->analyse($site, $this->corrections[$site->id] ?? null);
            unset($this->corrections[$site->id]);
        }, 'Analisis bisnes siap. Semak, dan tekan "Betul, sahkan" jika tepat.');
    }

    public function confirmAnalysis(SiteAnalyst $analyst, TopicPlanner $planner, int $id): void
    {
        $site = Site::query()->findOrFail($id);

        if ($site->analysis === null) {
            $this->say('Tiada analisis untuk disahkan. Tekan Analisis bisnes dahulu.', 'warning');

            return;
        }

        $analyst->confirm($site);
        $dropped = $planner->dropOffField($site->refresh());
        $this->say("Analisis {$site->name} disahkan. Topik baru akan dirancang ikut bidang ini.".($dropped ? " {$dropped} topik lama yang tersasar bidang dibuang." : '').($site->status === SiteStatus::Active ? '' : ' Tekan Aktifkan untuk mula.'));
    }

    public function pause(SiteService $sites, int $id): void
    {
        $sites->pause(Site::query()->findOrFail($id));
        $this->say('Website diberhentikan. Tiada artikel baru sehingga diaktifkan semula.');
    }

    public function publishNow(SiteService $sites, int $id): void
    {
        $site = Site::query()->findOrFail($id);

        if (! $site->installed_at) {
            $this->say('Pasang blog dahulu.', 'warning');

            return;
        }

        $sites->publishNow($site);
        $this->say('Artikel sedang ditulis. Semak skrin Artikel dalam beberapa minit.');
    }

    public function rebuild(SiteService $sites, int $id): void
    {
        $this->attempt(fn () => $sites->rebuild(Site::query()->findOrFail($id)), 'Semua halaman blog dibina semula.');
    }

    public function resumeAi(SiteService $sites): void
    {
        $count = $sites->resumeAiPaused();
        $this->say("{$count} website disambung semula.");
    }

    /** New target keywords become topics straight away, so the admin sees them in Topik. */
    private function planTargets(TopicPlanner $planner, Site $site): string
    {
        if ($site->pendingTargetKeywords() === [] || ! $site->hasConfirmedAnalysis()) {
            return '';
        }

        try {
            $planner->plan($site);
        } catch (Throwable $e) {
            report($e);

            return ' Topik untuk keyword sasaran akan dirancang pada giliran seterusnya.';
        }

        return ' Topik untuk keyword sasaran dah dirancang dan akan ditulis dahulu (lihat Topik).';
    }

    /** Profiles for the form. Cached 10 minutes; a Google error is shown, never fatal. */
    private function loadGbpLocations(): void
    {
        $this->gbpLocations = [];
        $this->gbpError = null;
        $google = app(GoogleBusinessClient::class);
        $connection = GoogleConnection::query()->first();

        if (! $google->configured() || $connection === null) {
            return;
        }

        try {
            $this->gbpLocations = Cache::remember('gbp-locations:'.$connection->id, now()->addMinutes(10), fn () => $google->locations($connection));
        } catch (GoogleBusinessException $e) {
            $this->gbpError = $e->getMessage();
        }
    }

    private function attempt(callable $action, string $success): void
    {
        try {
            $action();
            $this->say($success);
        } catch (Throwable $e) {
            report($e);
            $this->say($e->getMessage(), 'danger');
        }
    }

    private function say(string $message, string $type = 'success'): void
    {
        $this->flash = $message;
        $this->flashType = $type;
    }

    public function render()
    {
        $expires = config('dynoseo.github.token_expires_at');

        return view('livewire.sites-page', [
            'sites' => Site::query()->withCount([
                'articles as published_count' => fn ($q) => $q->where('status', 'published'),
                'topics as planned_count' => fn ($q) => $q->where('status', 'planned'),
            ])->orderBy('name')->get(),
            'aiPaused' => Site::query()->where('status', SiteStatus::AiPaused)->count(),
            'tokenExpires' => $expires ? Carbon::parse($expires) : null,
            'googleConfigured' => app(GoogleBusinessClient::class)->configured(),
            'googleConnection' => GoogleConnection::query()->first(),
            'googleFlash' => session('google_flash'),
        ]);
    }
}
