<div class="space-y-4">
    <x-page-header title="Website" subtitle="Artikel baru setiap beberapa hari, terus ke GitHub Pages.">
        @unless ($showForm)
            <button wire:click="create" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Tambah</button>
        @endunless
    </x-page-header>

    @if ($flash)
        <x-alert :type="$flashType">{{ $flash }}</x-alert>
    @endif

    @if ($aiPaused > 0)
        <x-alert type="warning">
            <p class="font-semibold">{{ $aiPaused }} website tertunda: Vertex AI berhenti.</p>
            <p class="mt-0.5">Biasanya kredit GCP habis atau billing dimatikan. Selesaikan di Google Cloud Console, kemudian:</p>
            <button wire:click="resumeAi" class="btn-dark mt-2 py-2">Sambung semula semua</button>
        </x-alert>
    @endif

    @if ($tokenExpires && $tokenExpires->lte(now()->addDays(config('dynoseo.github.warn_days'))))
        <x-alert type="warning">Token GitHub tamat {{ $tokenExpires->format('d/m/Y') }}. Buat token baru dan kemas kini .env.</x-alert>
    @endif

    @if ($showForm)
        <form wire:submit="save" class="card space-y-4 p-4">
            <h2 class="text-lg font-bold">{{ $editingId ? 'Edit website' : 'Website baru' }}</h2>

            <x-field label="Nama bisnes" name="form.name">
                <input type="text" wire:model="form.name" class="input" placeholder="Gabion Pantai Timur">
            </x-field>
            <x-field label="Domain" name="form.domain">
                <input type="text" wire:model="form.domain" class="input" placeholder="gabionpantaitimur.my" autocapitalize="none">
            </x-field>
            <div class="grid grid-cols-3 gap-3">
                <div class="col-span-2">
                    <x-field label="Repo GitHub" name="form.github_repo">
                        <input type="text" wire:model="form.github_repo" class="input" placeholder="Dynopos/gabion" autocapitalize="none">
                    </x-field>
                </div>
                <x-field label="Branch" name="form.branch">
                    <input type="text" wire:model="form.branch" class="input" autocapitalize="none">
                </x-field>
            </div>

            <x-field label="Folder web (pilihan)" name="form.docroot" hint="Kosong untuk GitHub Pages. Isi public untuk app Laravel (contoh dynolead.my).">
                <input type="text" wire:model="form.docroot" class="input" placeholder="public" autocapitalize="none">
            </x-field>

            <p class="label pt-2">Artikel</p>
            <x-field label="Bidang" name="form.niche" hint="Topik umum artikel. Contoh: gabion, kawalan cerun, tembok penahan tanah">
                <textarea wire:model="form.niche" rows="2" class="input"></textarea>
            </x-field>
            <x-field label="Pembaca sasaran (pilihan)" name="form.audience">
                <input type="text" wire:model="form.audience" class="input" placeholder="pemilik rumah lereng bukit, kontraktor kecil">
            </x-field>
            <x-field label="Kawasan (pilihan)" name="form.area">
                <input type="text" wire:model="form.area" class="input" placeholder="Kelantan, Terengganu">
            </x-field>
            <x-field label="Topik dilarang (pilihan)" name="form.banned_topics" hint="Pisahkan dengan koma.">
                <input type="text" wire:model="form.banned_topics" class="input" placeholder="harga, nama pesaing">
            </x-field>
            <div class="grid grid-cols-2 gap-3">
                <x-field label="Setiap (hari)" name="form.frequency_days">
                    <input type="number" min="1" max="30" wire:model="form.frequency_days" class="input">
                </x-field>
                <x-field label="Bayar sehingga" name="form.paid_until" hint="Kosong = tiada had">
                    <input type="date" wire:model="form.paid_until" class="input">
                </x-field>
            </div>

            <p class="label pt-2">Hook bawah artikel</p>
            <x-field label="Tajuk" name="form.cta_title">
                <input type="text" wire:model="form.cta_title" class="input" placeholder="Cerun rumah anda mula runtuh?">
            </x-field>
            <x-field label="Ayat" name="form.cta_text">
                <textarea wire:model="form.cta_text" rows="2" class="input" placeholder="WhatsApp kami untuk lawatan tapak dan cadangan."></textarea>
            </x-field>
            <x-field label="Teks butang" name="form.cta_button">
                <input type="text" wire:model="form.cta_button" class="input">
            </x-field>
            <x-field label="Nombor WhatsApp" name="form.cta_whatsapp" hint="Telefon bimbit 01x. Kosongkan jika guna pautan.">
                <input type="tel" wire:model="form.cta_whatsapp" class="input" placeholder="0123456789">
            </x-field>
            <x-field label="Mesej awal WhatsApp" name="form.cta_message" hint="{tajuk} diganti dengan tajuk artikel.">
                <textarea wire:model="form.cta_message" rows="2" class="input"></textarea>
            </x-field>
            <x-field label="Pautan (jika tiada WhatsApp)" name="form.cta_url">
                <input type="text" wire:model="form.cta_url" class="input" placeholder="/hubungi/" autocapitalize="none">
            </x-field>
            <x-toggle wire:model="form.cta_bridge" label="Ayat penyambung AI" hint="AI tulis satu ayat dari topik artikel ke hook." />

            <div class="flex gap-2 pt-2">
                <button type="submit" class="btn-primary flex-1" wire:loading.attr="disabled">Simpan</button>
                <button type="button" wire:click="cancel" class="btn-soft">Batal</button>
            </div>
        </form>
    @endif

    @forelse ($sites as $site)
        <div class="card p-4" wire:key="site-{{ $site->id }}">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="truncate font-semibold">{{ $site->name }}</p>
                    <a href="{{ $site->baseUrl() }}/blog/" target="_blank" rel="noopener" class="block truncate text-sm text-emerald-700">{{ $site->domain }}/blog</a>
                </div>
                <span class="chip shrink-0 ring-1 ring-inset {{ $site->status->color() }}">{{ $site->status->label() }}</span>
            </div>

            <dl class="mt-3 grid grid-cols-3 gap-2 text-center text-xs">
                <div class="rounded-xl bg-slate-50 p-2"><dt class="text-slate-500">Terbit</dt><dd class="text-base font-bold">{{ $site->published_count }}</dd></div>
                <div class="rounded-xl bg-slate-50 p-2"><dt class="text-slate-500">Topik</dt><dd class="text-base font-bold">{{ $site->planned_count }}</dd></div>
                <div class="rounded-xl bg-slate-50 p-2"><dt class="text-slate-500">Seterusnya</dt><dd class="text-sm font-semibold">{{ $site->status === \App\Enums\SiteStatus::Active && $site->next_publish_at ? $site->next_publish_at->format('d/m H:i') : '-' }}</dd></div>
            </dl>

            @if ($site->status_note)
                <p class="mt-3 rounded-xl bg-amber-50 p-2.5 text-xs text-amber-900">{{ $site->status_note }}</p>
            @endif
            @if ($site->paid_until)
                <p class="mt-2 text-xs {{ $site->isPaid() ? 'text-slate-500' : 'font-semibold text-rose-600' }}">Bayar sehingga {{ $site->paid_until->format('d/m/Y') }}</p>
            @endif

            <div class="mt-3 flex flex-wrap gap-2">
                <button wire:click="edit({{ $site->id }})" class="btn-soft px-3 py-2"><x-icon name="pencil" class="h-4 w-4" /> Edit</button>
                <a href="{{ route('topics', $site->id) }}" wire:navigate class="btn-soft px-3 py-2"><x-icon name="note" class="h-4 w-4" /> Topik</a>
                <button wire:click="check({{ $site->id }})" class="btn-soft px-3 py-2" wire:loading.attr="disabled"><x-icon name="check-circle" class="h-4 w-4" /> Semak repo</button>
                @if (! $site->installed_at)
                    <button wire:click="install({{ $site->id }})" class="btn-dark px-3 py-2" wire:loading.attr="disabled">Pasang blog</button>
                @else
                    <button wire:click="publishNow({{ $site->id }})" wire:confirm="Tulis dan terbit satu artikel sekarang?" class="btn-soft px-3 py-2"><x-icon name="sparkles" class="h-4 w-4" /> Terbit sekarang</button>
                    <button wire:click="rebuild({{ $site->id }})" wire:confirm="Bina semula semua halaman blog (contoh selepas tukar hook)?" class="btn-soft px-3 py-2" wire:loading.attr="disabled"><x-icon name="refresh" class="h-4 w-4" /> Bina semula</button>
                @endif
                @if ($site->status === \App\Enums\SiteStatus::Active)
                    <button wire:click="pause({{ $site->id }})" class="btn-soft px-3 py-2">Henti</button>
                @else
                    <button wire:click="activate({{ $site->id }})" class="btn-primary px-3 py-2">Aktifkan</button>
                @endif
            </div>
        </div>
    @empty
        @unless ($showForm)
            <x-empty icon="store" title="Belum ada website">Tekan Tambah untuk daftar website pertama.</x-empty>
        @endunless
    @endforelse
</div>
