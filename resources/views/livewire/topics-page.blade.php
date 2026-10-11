<div class="space-y-4">
    <a href="{{ route('sites') }}" wire:navigate class="text-sm font-medium text-emerald-700">← Website</a>
    <x-page-header :title="'Topik: '.$site->name" subtitle="Artikel seterusnya ambil topik paling atas.">
        <button wire:click="plan" class="btn-soft" wire:loading.attr="disabled" wire:target="plan">
            <x-icon name="sparkles" class="h-4 w-4" />
            <span wire:loading.remove wire:target="plan">Jana topik</span>
            <span wire:loading wire:target="plan">Sekejap...</span>
        </button>
    </x-page-header>

    @if ($flash)
        <x-alert :type="$flashType">{{ $flash }}</x-alert>
    @endif

    <form wire:submit="add" class="card space-y-3 p-4">
        <x-field label="Tambah topik sendiri" name="title">
            <input type="text" wire:model="title" class="input" placeholder="Gabion wall vs concrete wall untuk cerun rumah">
        </x-field>
        <x-field label="Kata kunci (pilihan)" name="keyword">
            <input type="text" wire:model="keyword" class="input" placeholder="gabion wall vs concrete wall">
        </x-field>
        <button type="submit" class="btn-primary w-full"><x-icon name="plus" class="h-4 w-4" /> Tambah</button>
    </form>

    <div class="card divide-y divide-slate-100">
        @forelse ($planned as $topic)
            <div class="flex items-start gap-3 p-3" wire:key="t-{{ $topic->id }}">
                <span class="mt-0.5 w-5 text-right text-xs font-bold text-slate-400">{{ $loop->iteration }}</span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium">{{ $topic->title }}</p>
                    <p class="text-xs text-slate-500">{{ $topic->target_keyword }} @if($topic->intent) · {{ $topic->intent }} @endif @if($topic->source === 'manual') · manual @endif</p>
                </div>
                @unless ($loop->first)
                    <button wire:click="moveUp({{ $topic->id }})" class="text-xs font-semibold text-emerald-700">Atas</button>
                @endunless
                <button wire:click="skip({{ $topic->id }})" class="text-slate-400 hover:text-rose-600" title="Buang"><x-icon name="x" class="h-4 w-4" /></button>
            </div>
        @empty
            <div class="p-4"><x-empty icon="note" title="Tiada topik">Tekan Jana topik, atau tambah sendiri. App juga jana sendiri bila tinggal kurang 5.</x-empty></div>
        @endforelse
    </div>

    @if ($used->isNotEmpty())
        <p class="label">Sudah digunakan / dibuang</p>
        <div class="card divide-y divide-slate-100">
            @foreach ($used as $topic)
                <div class="flex items-center justify-between gap-3 p-3 text-sm">
                    <span class="min-w-0 truncate text-slate-600">{{ $topic->title }}</span>
                    <span class="chip">{{ $topic->status === 'used' ? 'Ditulis' : 'Dibuang' }}</span>
                </div>
            @endforeach
        </div>
    @endif
</div>
