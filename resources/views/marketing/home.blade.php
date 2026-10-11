@php
    $price = (int) config('dynoseo.price_myr');
    $company = config('dynoseo.company.name');
    $waNumber = config('dynoseo.company.whatsapp');
    $wa = fn (string $msg) => \App\Support\MalaysianPhone::waLink($waNumber, $msg) ?? '#';
    $waStart = $wa("Salam, saya nak langgan Dyno SEO RM{$price} sebulan untuk website saya.");
    $waAsk = $wa('Salam, saya nak tanya tentang Dyno SEO.');
    $title = 'Artikel SEO Automatik untuk Website Anda | Dyno SEO';
    $description = "Dyno SEO tulis dan terbitkan artikel SEO automatik setiap 2 hari untuk website anda, lengkap dengan gambar dan butang WhatsApp. Hanya RM{$price} sebulan.";

    $features = [
        ['icon' => 'M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z', 'title' => 'Tulis & terbit setiap 2 hari', 'text' => 'Artikel baru masuk ke blog website anda secara automatik. Anda tak perlu tulis sepatah pun.'],
        ['icon' => 'M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z', 'title' => 'Kata kunci disusun automatik', 'text' => 'AI rancang topik ikut apa yang orang Malaysia cari di Google, tanpa ulang topik yang sama.'],
        ['icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z', 'title' => 'Bantu naikkan kedudukan Google', 'text' => 'Setiap artikel lengkap dengan tajuk SEO, schema, sitemap dan pautan dalaman yang Google suka.'],
        ['icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z', 'title' => 'Trafik organik jangka panjang', 'text' => 'Artikel kekal di website anda. Pengunjung terus datang dari Google walaupun anda tak bayar iklan.'],
        ['icon' => 'M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z', 'title' => 'Gambar untuk setiap artikel', 'text' => 'Setiap artikel ada gambar baru yang dijana khas ikut topik, dalam suasana Malaysia.'],
        ['icon' => 'M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z', 'title' => 'Butang WhatsApp bawah artikel', 'text' => 'Pembaca yang berminat terus WhatsApp anda, dengan tajuk artikel yang mereka baca.'],
    ];

    $ads = [
        ['Bayar setiap klik', 'Setiap pelanggan klik, duit terus berkurang.'],
        ['Trafik berhenti', 'Sebaik anda berhenti bayar, trafik terus hilang.'],
        ['Kos setiap klik naik', 'Semakin mahal bila pesaing bida lebih tinggi.'],
        ['Anda menyewa trafik', 'Bukan aset milik anda sendiri.'],
    ];
    $seo = [
        ['Kedudukan secara organik', 'Bantu website anda naik di Google tanpa bayar setiap klik.'],
        ['Trafik terus datang', 'Artikel lama terus bawa pengunjung walaupun anda berhenti.'],
        ['Tiada kontrak', 'Bayar bulanan, boleh berhenti bila-bila masa.'],
        ['Aset jangka panjang', 'Artikel, trafik dan pelanggan milik anda sendiri.'],
    ];

    $faqs = [
        ['Perlu ke saya tulis artikel sendiri?', 'Tidak. Beritahu kami apa bisnes anda dalam satu ayat. Dyno SEO pilih topik, tulis artikel, jana gambar dan terbitkan ke blog website anda setiap 2 hari.'],
        ['Artikel tentang apa?', 'Artikel umum yang berguna dalam bidang bisnes anda. Contoh website pemasang gabion: tanda cerun akan runtuh, gabion wall vs concrete wall, cara jaga cerun musim tengkujuh. Di bawah setiap artikel ada butang WhatsApp ke bisnes anda.'],
        ['Berapa lama baru nampak hasil?', 'SEO mengambil masa. Biasanya Google mula beri kedudukan dalam 3 hingga 6 bulan, bergantung pada bidang dan persaingan. Lagi banyak artikel berkualiti, lagi kuat website anda.'],
        ['Adakah website saya dijamin nombor 1 di Google?', 'Tiada siapa boleh jamin kedudukan nombor 1, termasuk kami. Yang kami jamin: website anda sentiasa dikemas kini dengan artikel baru yang disusun ikut amalan SEO yang betul.'],
        ['Website jenis apa yang disokong?', 'Website yang kami bina, website HTML di GitHub Pages dan website Laravel. Untuk platform lain, WhatsApp kami dahulu.'],
        ['Boleh berhenti bila-bila masa?', "Boleh. Tiada kontrak. Artikel yang sudah diterbitkan kekal di website anda."],
    ];
@endphp
<!DOCTYPE html>
<html lang="ms" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0b1530">
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ url('/') }}">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:image" content="{{ asset('images/og-dynoseo.jpg') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="ms_MY">
    <meta property="og:site_name" content="Dyno SEO">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ asset('images/og-dynoseo.jpg') }}">
    <link rel="icon" href="/favicon.png" type="image/png">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    @vite(['resources/css/app.css'])
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                '@id' => url('/').'#org',
                'name' => 'Dyno SEO',
                'url' => url('/'),
                'logo' => asset('images/logo-mark.webp'),
                'parentOrganization' => ['@type' => 'Organization', 'name' => $company],
                'contactPoint' => ['@type' => 'ContactPoint', 'telephone' => '+6'.$waNumber, 'contactType' => 'sales', 'areaServed' => 'MY', 'availableLanguage' => ['ms', 'en']],
            ],
            [
                '@type' => 'Service',
                'name' => 'Dyno SEO: artikel SEO automatik',
                'serviceType' => 'Penulisan dan penerbitan artikel SEO automatik',
                'provider' => ['@id' => url('/').'#org'],
                'areaServed' => 'MY',
                'offers' => [
                    '@type' => 'Offer',
                    'price' => $price,
                    'priceCurrency' => 'MYR',
                    'availability' => 'https://schema.org/InStock',
                    'priceSpecification' => ['@type' => 'UnitPriceSpecification', 'price' => $price, 'priceCurrency' => 'MYR', 'unitText' => 'bulan'],
                ],
            ],
            [
                '@type' => 'FAQPage',
                'mainEntity' => collect($faqs)->map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]])->all(),
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
    </script>
</head>
<body class="bg-white font-sans text-slate-900 antialiased">

{{-- Navigation --}}
<header class="sticky top-0 z-40 border-b border-slate-200/70 bg-white/80 backdrop-blur-lg">
    <nav class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 sm:px-6" aria-label="Utama">
        <a href="/" class="flex items-center gap-2.5">
            <img src="{{ asset('images/logo-mark.webp') }}" alt="Logo Dyno SEO" width="36" height="36" class="h-9 w-9 rounded-full">
            <span class="text-lg font-bold tracking-tight">Dyno <span class="text-amber-500">SEO</span></span>
        </a>
        <div class="hidden items-center gap-8 text-sm font-medium text-slate-600 md:flex">
            <a href="#ciri" class="hover:text-slate-900">Ciri-ciri</a>
            <a href="#cara" class="hover:text-slate-900">Cara ia berfungsi</a>
            <a href="#harga" class="hover:text-slate-900">Harga</a>
            <a href="#soalan" class="hover:text-slate-900">Soalan</a>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('login') }}" class="hidden px-3 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 sm:block">Log masuk</a>
            <a href="{{ $waStart }}" class="inline-flex min-h-[44px] items-center rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">Mula sekarang</a>
        </div>
    </nav>
</header>

<main>
    {{-- Hero --}}
    <section class="relative overflow-hidden">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(60rem_30rem_at_80%_-10%,rgba(251,191,36,0.18),transparent),radial-gradient(40rem_30rem_at_0%_10%,rgba(15,23,42,0.06),transparent)]"></div>
        <div class="relative mx-auto grid max-w-6xl items-center gap-12 px-4 pb-16 pt-12 sm:px-6 lg:grid-cols-2 lg:pb-24 lg:pt-20">
            <div>
                <p class="inline-flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> Jana jualan melalui website anda
                </p>
                <h1 class="mt-5 text-4xl font-bold leading-[1.1] tracking-tight sm:text-5xl lg:text-6xl">
                    Artikel SEO automatik untuk website anda
                </h1>
                <p class="mt-5 max-w-xl text-lg leading-relaxed text-slate-600">
                    Jangan biarkan website anda digantung tak bertali. Dyno SEO menulis dan menerbitkan artikel SEO automatik setiap 2 hari, lengkap dengan gambar dan butang WhatsApp, supaya website anda terus hidup dan dijumpai di Google.
                </p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ $waStart }}" class="inline-flex min-h-[48px] items-center justify-center gap-2 rounded-xl bg-slate-900 px-6 text-base font-semibold text-white shadow-lg shadow-slate-900/20 hover:bg-slate-800">
                        Mula dengan RM{{ $price }}/bulan
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                    </a>
                    <a href="#cara" class="inline-flex min-h-[48px] items-center justify-center rounded-xl border border-slate-200 bg-white px-6 text-base font-semibold text-slate-700 hover:bg-slate-50">Lihat cara ia berfungsi</a>
                </div>
                <p class="mt-4 text-sm text-slate-500">Tiada kontrak · Boleh berhenti bila-bila masa · Sokongan WhatsApp</p>
            </div>

            {{-- Product mock (HTML, no image) --}}
            <div class="relative mx-auto w-full max-w-lg" aria-hidden="true">
                <div class="rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-900/10">
                    <div class="flex items-center gap-1.5 border-b border-slate-100 px-4 py-3">
                        <span class="h-2.5 w-2.5 rounded-full bg-rose-300"></span><span class="h-2.5 w-2.5 rounded-full bg-amber-300"></span><span class="h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                        <span class="ml-3 flex-1 truncate rounded-md bg-slate-100 px-3 py-1 text-[11px] text-slate-500">websiteanda.my/blog</span>
                    </div>
                    <div class="space-y-3 p-4">
                        <p class="text-sm font-bold">Blog Gabion Pantai Timur</p>
                        @foreach ([['Gabion wall vs concrete wall: mana sesuai untuk cerun rumah?', 'from-emerald-200 to-teal-500', 'Hari ini'], ['7 tanda awal cerun belakang rumah akan runtuh', 'from-amber-200 to-orange-500', '2 hari lalu'], ['Cara jaga cerun semasa musim tengkujuh', 'from-sky-200 to-indigo-500', '4 hari lalu']] as [$t, $g, $d])
                            <div class="flex gap-3 rounded-xl border border-slate-100 p-2.5">
                                <div class="h-14 w-20 shrink-0 rounded-lg bg-gradient-to-br {{ $g }}"></div>
                                <div class="min-w-0">
                                    <p class="line-clamp-2 text-[13px] font-semibold leading-snug">{{ $t }}</p>
                                    <p class="mt-1 text-[11px] text-slate-400">{{ $d }}</p>
                                </div>
                            </div>
                        @endforeach
                        <div class="rounded-xl bg-[#0b1530] p-4 text-center">
                            <p class="text-sm font-bold text-white">Cerun rumah anda mula runtuh?</p>
                            <span class="mt-2 inline-block rounded-full bg-emerald-500 px-4 py-1.5 text-xs font-semibold text-white">💬 WhatsApp Kami</span>
                        </div>
                    </div>
                </div>
                <div class="absolute -left-8 top-[46%] hidden rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-xl sm:block">
                    <p class="flex items-center gap-1.5 text-xs font-semibold text-emerald-700"><span class="h-2 w-2 rounded-full bg-emerald-500"></span> Artikel baru diterbitkan</p>
                    <p class="text-[11px] text-slate-500">Automatik · setiap 2 hari</p>
                </div>
                <div class="absolute -right-3 bottom-24 hidden rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-xl sm:block">
                    <p class="text-[11px] text-slate-500">Kata kunci</p>
                    <p class="text-xs font-semibold">tembok penahan cerun</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Who it is for --}}
    <section class="border-y border-slate-100 bg-slate-50/60">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-center gap-x-8 gap-y-3 px-4 py-6 text-sm font-medium text-slate-500 sm:px-6">
            <span class="text-slate-400">Sesuai untuk:</span>
            <span>Kedai makan</span><span>Kontraktor</span><span>Klinik</span><span>Butik</span><span>Servis rumah</span><span>Kedai runcit</span>
        </div>
    </section>

    {{-- Problem --}}
    <section class="mx-auto max-w-6xl px-4 py-20 sm:px-6">
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-sm font-semibold uppercase tracking-widest text-amber-600">Masalahnya</p>
            <h2 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Website yang tak dikemas kini, mati sebelum usianya</h2>
            <p class="mt-4 text-lg text-slate-600">Kebanyakan website SME siap, kemudian dibiarkan. Hidup tanpa tujuan.</p>
        </div>
        <div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach (['Tiada pengunjung' => 'Google jarang datang ke website yang tak berubah.', 'Tiada jualan' => 'Tiada pengunjung bermakna tiada pertanyaan.', 'Jarang dikemas kini' => 'Pemilik bisnes sibuk, tiada masa menulis.', 'Kedudukan tenggelam' => 'Pesaing yang aktif menulis naik di atas anda.'] as $h => $t)
                <div class="rounded-2xl border border-slate-200 p-6">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-rose-50 text-rose-500">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6L9 12.75l4.286-4.286a11.948 11.948 0 014.306 6.43l.776 2.898m0 0l3.182-5.511m-3.182 5.51l-5.511-3.181"/></svg>
                    </span>
                    <h3 class="mt-4 font-semibold">{{ $h }}</h3>
                    <p class="mt-1 text-sm text-slate-600">{{ $t }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Features --}}
    <section id="ciri" class="scroll-mt-20 bg-slate-50 py-20">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-semibold uppercase tracking-widest text-amber-600">Penyelesaian</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Jadikan website anda mesin jualan, 24/7</h2>
                <p class="mt-4 text-lg text-slate-600">Dyno SEO bekerja tanpa henti di belakang tabir. Anda fokus urus bisnes.</p>
            </div>
            <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($features as $f)
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        <span class="grid h-11 w-11 place-items-center rounded-xl bg-gradient-to-br from-amber-300 to-amber-500 text-slate-900 shadow-sm">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $f['icon'] }}"/></svg>
                        </span>
                        <h3 class="mt-4 text-lg font-semibold">{{ $f['title'] }}</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-slate-600">{{ $f['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section id="cara" class="mx-auto max-w-6xl scroll-mt-20 px-4 py-20 sm:px-6">
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-sm font-semibold uppercase tracking-widest text-amber-600">Cara ia berfungsi</p>
            <h2 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Tiga langkah. Selepas itu, automatik.</h2>
        </div>
        <ol class="mt-12 grid gap-6 md:grid-cols-3">
            @foreach ([['Beritahu bisnes anda', 'Satu ayat cukup. Contoh: "Pasang gabion dan tembok cerun di Kelantan."'], ['AI tulis & terbitkan', 'Setiap 2 hari, artikel baru dengan gambar diterbitkan terus ke blog website anda.'], ['Pelanggan WhatsApp anda', 'Pembaca dari Google tekan butang WhatsApp di bawah artikel dan terus bertanya.']] as $i => [$h, $t])
                <li class="relative rounded-2xl border border-slate-200 p-6">
                    <span class="grid h-10 w-10 place-items-center rounded-full bg-slate-900 text-sm font-bold text-amber-400">{{ $i + 1 }}</span>
                    <h3 class="mt-4 text-lg font-semibold">{{ $h }}</h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-600">{{ $t }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Comparison --}}
    <section class="bg-[#0b1530] py-20 text-white">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-semibold uppercase tracking-widest text-amber-400">Bandingkan</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Google Ads vs Dyno SEO</h2>
                <p class="mt-4 text-lg text-slate-300">Iklan menyewa trafik. SEO membina aset.</p>
            </div>
            <div class="mt-12 grid gap-5 lg:grid-cols-2">
                <div class="rounded-2xl border border-white/10 bg-white/5 p-6 sm:p-8">
                    <h3 class="text-lg font-semibold text-slate-300">Google Ads</h3>
                    <p class="mt-2 text-4xl font-bold">RM2,000<span class="text-base font-medium text-slate-400">/bulan</span></p>
                    <p class="text-sm text-slate-400">Contoh bajet iklan (± RM24,000 setahun). Bergantung pada bajet anda.</p>
                    <ul class="mt-6 space-y-4">
                        @foreach ($ads as [$h, $t])
                            <li class="flex gap-3">
                                <span class="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full bg-rose-500/15 text-rose-400"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></span>
                                <span><span class="font-semibold">{{ $h }}</span><span class="block text-sm text-slate-400">{{ $t }}</span></span>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="relative rounded-2xl border border-amber-400/40 bg-gradient-to-b from-amber-400/15 to-white/5 p-6 sm:p-8">
                    <span class="absolute -top-3 right-6 rounded-full bg-amber-400 px-3 py-1 text-xs font-bold text-slate-900">Pilihan bijak</span>
                    <h3 class="text-lg font-semibold text-amber-300">Dyno SEO</h3>
                    <p class="mt-2 text-4xl font-bold">RM{{ $price }}<span class="text-base font-medium text-slate-400">/bulan</span></p>
                    <p class="text-sm text-slate-400">Satu harga tetap untuk satu website.</p>
                    <ul class="mt-6 space-y-4">
                        @foreach ($seo as [$h, $t])
                            <li class="flex gap-3">
                                <span class="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full bg-emerald-500/20 text-emerald-400"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg></span>
                                <span><span class="font-semibold">{{ $h }}</span><span class="block text-sm text-slate-400">{{ $t }}</span></span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- Pricing --}}
    <section id="harga" class="mx-auto max-w-6xl scroll-mt-20 px-4 py-20 sm:px-6">
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-sm font-semibold uppercase tracking-widest text-amber-600">Harga</p>
            <h2 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Satu harga. Semua termasuk.</h2>
        </div>
        <div class="mx-auto mt-12 max-w-md rounded-3xl border border-slate-200 bg-white p-8 shadow-xl shadow-slate-900/5">
            <p class="text-sm font-semibold text-slate-500">Dyno SEO</p>
            <p class="mt-2 flex items-end gap-1"><span class="text-5xl font-bold tracking-tight">RM{{ $price }}</span><span class="mb-1.5 text-slate-500">/bulan setiap website</span></p>
            <ul class="mt-6 space-y-3 text-sm">
                @foreach (['Artikel SEO baru setiap 2 hari (±15 sebulan)', 'Gambar khas untuk setiap artikel', 'Topik & kata kunci disusun automatik', 'Butang WhatsApp di bawah setiap artikel', 'Sitemap, schema dan pautan dalaman', 'Tiada kontrak, boleh berhenti bila-bila masa'] as $item)
                    <li class="flex gap-2.5"><svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>{{ $item }}</li>
                @endforeach
            </ul>
            <a href="{{ $waStart }}" class="mt-8 flex min-h-[48px] items-center justify-center rounded-xl bg-gradient-to-r from-amber-400 to-amber-500 px-6 font-bold text-slate-900 shadow-lg shadow-amber-500/30 hover:from-amber-500 hover:to-amber-600">Langgan melalui WhatsApp</a>
            <p class="mt-3 text-center text-xs text-slate-500">Belum ada website? Kami boleh bina untuk anda.</p>
        </div>
    </section>

    {{-- FAQ --}}
    <section id="soalan" class="scroll-mt-20 bg-slate-50 py-20">
        <div class="mx-auto max-w-3xl px-4 sm:px-6">
            <h2 class="text-center text-3xl font-bold tracking-tight sm:text-4xl">Soalan lazim</h2>
            <div class="mt-10 space-y-3">
                @foreach ($faqs as [$q, $a])
                    <details class="group rounded-2xl border border-slate-200 bg-white p-5 [&_summary::-webkit-details-marker]:hidden">
                        <summary class="flex cursor-pointer items-center justify-between gap-4 font-semibold">
                            <h3>{{ $q }}</h3>
                            <svg class="h-5 w-5 shrink-0 text-slate-400 transition group-open:rotate-45" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        </summary>
                        <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ $a }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Final CTA --}}
    <section class="px-4 py-20 sm:px-6">
        <div class="relative mx-auto max-w-5xl overflow-hidden rounded-3xl bg-[#0b1530] px-6 py-14 text-center sm:px-12">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(40rem_20rem_at_50%_-20%,rgba(251,191,36,0.25),transparent)]"></div>
            <h2 class="relative text-3xl font-bold tracking-tight text-white sm:text-4xl">Jadikan website anda mesin jualan sekarang</h2>
            <p class="relative mx-auto mt-4 max-w-xl text-lg text-slate-300">Artikel pertama boleh diterbitkan hari ini. Hanya RM{{ $price }} sebulan.</p>
            <div class="relative mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ $waStart }}" class="inline-flex min-h-[48px] items-center justify-center rounded-xl bg-gradient-to-r from-amber-400 to-amber-500 px-7 font-bold text-slate-900 hover:from-amber-500 hover:to-amber-600">Mula sekarang</a>
                <a href="{{ $waAsk }}" class="inline-flex min-h-[48px] items-center justify-center rounded-xl border border-white/20 px-7 font-semibold text-white hover:bg-white/10">Tanya dahulu</a>
            </div>
        </div>
    </section>
</main>

<footer class="border-t border-slate-200">
    <div class="mx-auto flex max-w-6xl flex-col gap-4 px-4 py-8 pb-24 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 sm:pb-8">
        <div class="flex items-center gap-2">
            <img src="{{ asset('images/logo-mark.webp') }}" alt="" width="28" height="28" class="h-7 w-7 rounded-full" loading="lazy">
            <span>© {{ date('Y') }} Dyno SEO · {{ $company }}, Pasir Mas, Kelantan</span>
        </div>
        <nav class="flex gap-5" aria-label="Pautan kaki">
            <a href="#harga" class="hover:text-slate-900">Harga</a>
            <a href="#soalan" class="hover:text-slate-900">Soalan</a>
            <a href="{{ $waAsk }}" class="hover:text-slate-900">WhatsApp {{ $waNumber }}</a>
            <a href="{{ route('login') }}" class="hover:text-slate-900">Log masuk</a>
        </nav>
    </div>
</footer>

{{-- Floating WhatsApp (mobile) --}}
<a href="{{ $waAsk }}" class="fixed bottom-4 right-4 z-50 inline-flex min-h-[52px] items-center gap-2 rounded-full bg-[#1faa53] px-5 font-semibold text-white shadow-xl shadow-green-900/30 sm:hidden" aria-label="WhatsApp Dyno SEO">
    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2a9.9 9.9 0 00-8.5 15l-1.5 5 5.2-1.4A9.9 9.9 0 1012.04 2zm5.8 14.1c-.25.7-1.45 1.33-2 1.38-.53.06-1.03.25-3.46-.72-2.92-1.15-4.79-4.15-4.93-4.34-.14-.2-1.18-1.57-1.18-3s.75-2.13 1.02-2.42c.26-.29.57-.36.76-.36l.55.01c.18 0 .42-.07.65.5.25.58.84 2.01.91 2.16.08.14.12.31.03.5-.1.2-.14.31-.29.48l-.43.5c-.14.14-.29.3-.12.59.17.28.75 1.24 1.6 2 1.1.98 2.03 1.29 2.31 1.43.29.15.46.12.62-.07.17-.2.72-.84.91-1.13.19-.28.38-.24.65-.14.26.1 1.68.79 1.97.94.29.14.48.21.55.33.07.12.07.7-.18 1.38z"/></svg>
    WhatsApp
</a>
</body>
</html>
