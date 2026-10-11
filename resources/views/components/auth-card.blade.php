@props(['title', 'subtitle' => null])
<div class="pb-10 pt-12">
    <a href="{{ route('login') }}" class="inline-flex items-center gap-3 text-white">
        <x-logo size="lg" class="ring-2 ring-white/40" />
        <span class="text-lg font-bold tracking-tight">Dyno SEO</span>
    </a>
    <h1 class="mt-6 text-3xl font-bold tracking-tight text-white">{{ $title }}</h1>
    @if ($subtitle)<p class="mt-1 text-emerald-50/90">{{ $subtitle }}</p>@endif

    <div class="card mt-7 p-5">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="mt-5 text-center text-sm text-slate-600">{{ $footer }}</div>
    @endisset
</div>
