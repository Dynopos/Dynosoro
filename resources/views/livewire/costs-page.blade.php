@php($rm = fn ($v) => 'RM'.number_format((float) $v, 2))
<div class="space-y-4">
    <x-page-header title="Kos" :subtitle="'Bulan '.now()->format('m/Y').' · anggaran dari jadual harga'" />

    @php($pct = $m['limit'] > 0 ? min(100, $m['platform_spent'] / $m['limit'] * 100) : 100)
    <div class="card p-4">
        <p class="label">Had AI bulan ini (semua website)</p>
        <p class="mt-1 text-2xl font-bold tabular-nums">{{ $rm($m['platform_spent']) }} <span class="text-base font-medium text-slate-500">/ {{ $rm($m['limit']) }}</span></p>
        <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
            <div @class(['h-full rounded-full', 'bg-emerald-500' => $pct < 80, 'bg-amber-500' => $pct >= 80 && $pct < 100, 'bg-rose-500' => $pct >= 100]) style="width: {{ $pct }}%"></div>
        </div>
        @if ($pct >= 80)
            <p class="mt-2 text-xs font-semibold text-amber-700">Hampir capai had. Artikel berhenti bila had dicapai.</p>
        @endif
        <p class="mt-2 text-xs text-slate-500">Panggilan AI hari ini: {{ $m['calls_today'] }} / {{ $m['daily_limit'] }}</p>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div class="card p-3"><p class="label">Artikel terbit</p><p class="mt-1 text-xl font-bold tabular-nums">{{ $m['articles'] }}</p></div>
        <div class="card p-3"><p class="label">Kos / artikel</p><p class="mt-1 text-xl font-bold tabular-nums">{{ $m['per_article'] !== null ? $rm($m['per_article']) : '-' }}</p></div>
        <div class="card p-3"><p class="label">Panggilan AI</p><p class="mt-1 text-xl font-bold tabular-nums">{{ number_format($m['calls']) }}</p></div>
        <div class="card p-3"><p class="label">Gambar</p><p class="mt-1 text-xl font-bold tabular-nums">{{ number_format($m['images']) }}</p></div>
    </div>

    <div class="card p-4">
        <p class="label mb-2">Ikut jenis</p>
        @php($labels = ['plan' => 'Pelan topik', 'write' => 'Tulis', 'check' => 'Semak', 'image' => 'Gambar'])
        @forelse ($m['by_purpose'] as $row)
            <div class="flex justify-between py-1 text-sm"><span>{{ $labels[$row->purpose] ?? $row->purpose }} ({{ $row->calls }})</span><span class="tabular-nums">{{ $rm($row->cost) }}</span></div>
        @empty
            <p class="text-sm text-slate-500">Belum ada panggilan AI bulan ini.</p>
        @endforelse
        <p class="mt-2 text-xs text-slate-400">Token: {{ number_format($m['input_tokens']) }} masuk, {{ number_format($m['output_tokens']) }} keluar.</p>
    </div>

    <div class="card p-4">
        <p class="label mb-2">Ikut website</p>
        @forelse ($m['by_site'] as $row)
            <div class="flex justify-between py-1 text-sm"><span class="truncate">{{ $row['name'] }} ({{ $row['articles'] }} artikel)</span><span class="tabular-nums">{{ $rm($row['cost']) }}</span></div>
        @empty
            <p class="text-sm text-slate-500">Belum ada website.</p>
        @endforelse
    </div>

    <x-alert type="info">Kos dibayar dengan kredit Google Cloud (tamat 5 Januari 2027). Semak baki sebenar di Console → Billing.</x-alert>
</div>
