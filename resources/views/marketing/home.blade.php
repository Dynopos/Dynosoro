@php
    $price = (int) config('dynoseo.price_myr');
    $websitePrice = (int) config('dynoseo.website_price_myr');
    $company = config('dynoseo.company.name');
    $waNumber = config('dynoseo.company.whatsapp');
    $waDisplay = preg_replace('/^(\d{3})(\d{3})(\d+)$/', '$1-$2 $3', $waNumber);
    $wa = fn (string $msg) => \App\Support\MalaysianPhone::waLink($waNumber, $msg) ?? '#';
    $waStart = $wa("Salam, saya berminat dengan website RM{$websitePrice} dan DYNO SEO RM{$price} sebulan.");
    $waAsk = $wa('Salam, saya nak tanya tentang DYNO SEO.');
    $waHave = $wa('Salam, saya dah ada website. Boleh guna DYNO SEO?');
    $title = 'DYNO SEO | Artikel SEO Automatik untuk Website Anda';
    $description = "Website RM{$websitePrice} sekali bayar dan DYNO SEO RM{$price} sebulan: artikel SEO automatik setiap 2 hari, gambar khas dan butang WhatsApp supaya bisnes anda lebih mudah ditemui di Google.";

    $faqs = [
        ['Apa itu DYNO SEO?', "DYNO SEO membantu mencari kata kunci, menulis dan menerbitkan artikel pada website DYNO secara automatik setiap 2 hari untuk membina peluang trafik organik."],
        ['Berapakah harga langganan DYNO SEO?', "Langganan DYNO SEO ialah RM{$price} sebulan untuk satu website. Tiada kontrak."],
        ['Berapakah kos untuk buat website?', "Kami menyediakan perkhidmatan membina website dengan harga RM{$websitePrice} sekali bayar."],
        ['Adakah RM'.$websitePrice.' termasuk langganan DYNO SEO?', "Tidak. RM{$websitePrice} ialah bayaran membina website, manakala DYNO SEO dikenakan RM{$price} sebulan. Bulan pertama: RM".($websitePrice + $price).'.'],
        ['Boleh guna DYNO SEO dengan website saya yang sedia ada?', 'DYNO SEO direka untuk website yang dibina oleh pihak kami. Jika anda sudah ada website, WhatsApp kami dahulu untuk semakan.'],
        ['Saya belum ada website. Boleh mula guna DYNO SEO?', "Boleh. Dapatkan website daripada kami pada harga RM{$websitePrice}, kemudian langgan DYNO SEO pada harga RM{$price} sebulan."],
        ['Artikel tentang apa?', 'Artikel umum yang berguna dalam bidang bisnes anda. Contoh untuk pemasang gabion: tanda cerun akan runtuh, gabion wall vs concrete wall, cara jaga cerun musim tengkujuh. Di bawah setiap artikel ada butang WhatsApp ke bisnes anda.'],
        ['Siapa yang menulis artikel?', 'Artikel ditulis oleh AI dalam Bahasa Melayu, kemudian disemak oleh sistem untuk tajuk berulang, fakta yang meragukan dan bahasa sebelum diterbitkan.'],
        ['Boleh saya ubah atau padam artikel?', 'Boleh. WhatsApp kami tajuk artikel yang ingin diubah atau dipadam, dan kami kemas kini di website anda.'],
        ['Adakah saya perlu tahu tentang SEO?', 'Tidak perlu. Beritahu kami apa bisnes anda dalam satu ayat. Selebihnya kami uruskan.'],
        ['Berapa lama baru nampak hasil?', 'SEO mengambil masa. Biasanya Google mula memberi kedudukan dalam 3 hingga 6 bulan, bergantung pada bidang dan persaingan.'],
        ['Adakah DYNO SEO menjamin website saya nombor satu di Google?', 'Tidak. SEO membantu meningkatkan peluang website ditemui, tetapi tiada jaminan kedudukan pertama, trafik atau jualan.'],
        ['Apa beza DYNO SEO dengan Google Ads?', 'Google Ads menggunakan bajet iklan untuk pendedahan berbayar. DYNO SEO membina kandungan untuk peluang trafik organik. SEO mengambil masa dan hasilnya tidak dijamin.'],
        ['Adakah trafik akan terus datang jika berhenti melanggan?', 'Artikel yang sudah diterbitkan kekal di website anda dan mungkin terus ditemui melalui Google, tetapi trafik tidak dijamin. Artikel baru hanya diterbitkan semasa langganan aktif.'],
        ['Bagaimana saya hendak bermula?', 'Tekan butang WhatsApp di halaman ini. Kami akan tanya tentang bisnes anda dan bantu dari bina website hingga artikel pertama diterbitkan.'],
    ];
@endphp
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $title }}</title>
<meta name="description" content="{{ $description }}">
<link rel="canonical" href="{{ url('/') }}">
<meta name="robots" content="index, follow, max-image-preview:large">
<meta name="theme-color" content="#10233f">
<link rel="icon" href="{{ asset('images/dynoseo-icon-64.png') }}" type="image/png">
<link rel="apple-touch-icon" href="{{ asset('images/dynoseo-icon-180.png') }}">
<meta property="og:type" content="website">
<meta property="og:site_name" content="DYNO SEO">
<meta property="og:locale" content="ms_MY">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ url('/') }}">
<meta property="og:image" content="{{ asset('images/og-dynoseo.jpg') }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ asset('images/og-dynoseo.jpg') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="preload" as="image" href="{{ asset('images/dynoseo-logo-128.webp') }}">
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'Organization',
            '@id' => url('/').'#org',
            'name' => 'DYNO SEO',
            'url' => url('/'),
            'logo' => asset('images/dynoseo-logo.webp'),
            'parentOrganization' => ['@type' => 'Organization', 'name' => $company],
            'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'Pasir Mas', 'addressRegion' => 'Kelantan', 'addressCountry' => 'MY'],
            'contactPoint' => ['@type' => 'ContactPoint', 'telephone' => '+6'.$waNumber, 'contactType' => 'sales', 'areaServed' => 'MY', 'availableLanguage' => ['ms']],
        ],
        [
            '@type' => 'Service',
            'name' => 'DYNO SEO: artikel SEO automatik',
            'serviceType' => 'Penulisan dan penerbitan artikel SEO automatik',
            'provider' => ['@id' => url('/').'#org'],
            'areaServed' => 'MY',
            'offers' => [
                ['@type' => 'Offer', 'name' => 'Langganan DYNO SEO', 'price' => $price, 'priceCurrency' => 'MYR', 'priceSpecification' => ['@type' => 'UnitPriceSpecification', 'price' => $price, 'priceCurrency' => 'MYR', 'unitText' => 'bulan']],
                ['@type' => 'Offer', 'name' => 'Bina website', 'price' => $websitePrice, 'priceCurrency' => 'MYR'],
            ],
        ],
        [
            '@type' => 'FAQPage',
            'mainEntity' => collect($faqs)->map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]])->values()->all(),
        ],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
</script>
<style>
:root{--navy:#10233f;--navy2:#193b66;--blue:#3976df;--muted:#69798e;--line:#e5ebf2;--bg:#f7f9fc;--white:#fff;--wa:#1faa53}*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:var(--bg);color:var(--navy);font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif;-webkit-font-smoothing:antialiased}a{text-decoration:none;color:inherit}img{max-width:100%;height:auto}.wrap{width:min(1180px,calc(100% - 40px));margin:auto}
.nav{position:sticky;top:0;z-index:20;background:#ffffffed;backdrop-filter:blur(18px);border-bottom:1px solid #eaf0f6}.navin{min-height:82px;display:flex;align-items:center;justify-content:space-between;gap:18px}.brand{display:flex;align-items:center;gap:11px;font-size:23px;font-weight:850;letter-spacing:-1px}.brandlogo{width:48px;height:48px;object-fit:contain;border-radius:12px}.links{display:flex;align-items:center;gap:27px;font-size:13px;font-weight:650}.links a:not(.btn):hover{color:var(--blue)}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;border-radius:100px;padding:15px 24px;min-height:48px;font-weight:750;font-size:14px;transition:.2s}.btn:hover{transform:translateY(-2px)}.primary{background:var(--navy);color:white;box-shadow:0 12px 28px #10233f25}.secondary{background:white;border:1px solid #dce5ef;color:var(--navy)}.btn-wa{background:var(--wa);color:#fff;box-shadow:0 12px 28px #1faa5333}.btn svg{width:18px;height:18px;flex:none}
.hero{padding:100px 0 65px;position:relative;overflow:hidden;background:radial-gradient(circle at 83% 28%,#e1ecfc 0,transparent 35%),linear-gradient(180deg,#fff,#f7f9fc)}.herogrid{display:grid;grid-template-columns:1.02fr .98fr;align-items:center;gap:55px}.eyebrow{display:inline-flex;align-items:center;gap:8px;border:1px solid #d8e5f7;background:#f0f6ff;padding:9px 14px;border-radius:100px;color:#245ca6;font-size:11px;font-weight:850;letter-spacing:1.4px;text-transform:uppercase}.dot{height:7px;width:7px;background:#4382e5;border-radius:50%}h1{font-size:clamp(44px,5.5vw,74px);letter-spacing:-.065em;line-height:1.06;margin:24px 0}h1 span{color:#3b78d8}.lead{font-size:17px;color:var(--muted);line-height:1.85;max-width:540px}.actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:28px}.micro{font-size:12px;color:#8794a5;margin-top:20px}
.pricebar{display:flex;gap:12px;flex-wrap:wrap;margin-top:23px}.pricechip{background:white;border:1px solid #dce5ef;border-radius:18px;padding:13px 19px;box-shadow:0 10px 30px #10233f0b}.pricechip b{display:block;font-size:23px;letter-spacing:-1px}.pricechip small{font-size:12px;color:#69798e}
.visual{position:relative;min-height:460px;display:flex;align-items:center}.dashboard{width:100%;background:white;border:1px solid #e2eaf4;border-radius:26px;padding:19px;box-shadow:0 35px 75px #10233f1b;transform:rotate(-2deg)}.dashhead{display:flex;justify-content:space-between;align-items:center;padding:6px 3px 17px;font-size:12px;font-weight:800}.pill{background:#e9f6ef;color:#24865a;padding:7px 11px;border-radius:30px;font-size:10px}.dashmain{background:#10233f;border-radius:19px;color:white;padding:27px;overflow:hidden}.dashmain small{color:#b9cce6}.dashmain strong{display:block;font-size:34px;letter-spacing:-1.5px;margin:9px 0}.bars{height:135px;display:flex;gap:11px;align-items:end;margin-top:20px;border-bottom:1px solid #ffffff30}.bars i{display:block;flex:1;background:linear-gradient(#75aeff,#3267b3);border-radius:8px 8px 0 0;opacity:.92}.dashbottom{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px}.mini{padding:17px;background:#f4f7fb;border-radius:15px}.mini small{color:#7b899a;font-size:10px}.mini b{display:block;font-size:19px;margin-top:6px}.float{position:absolute;right:-20px;bottom:24px;padding:17px 20px;background:#fff;border:1px solid #e4ebf5;border-radius:17px;box-shadow:0 15px 35px #10233f1c;transform:rotate(3deg);font-size:12px;font-weight:800}.float span{color:#3a82d7}
.strip{border-block:1px solid var(--line);background:#fff}.stripin{display:flex;flex-wrap:wrap;gap:10px 26px;justify-content:center;align-items:center;padding:22px 0;font-size:14px;font-weight:650;color:#536479}.stripin span:first-child{color:#8a97a8;font-weight:600}
.section{padding:90px 0}.sectionhead{max-width:690px;margin-bottom:34px}.center{text-align:center;margin-inline:auto}.kicker{font-size:11px;font-weight:850;letter-spacing:2px;text-transform:uppercase;color:#3e7dd6}h2{font-size:clamp(32px,4vw,50px);line-height:1.13;letter-spacing:-.055em;margin:15px 0}h3{letter-spacing:-.035em}p{line-height:1.8}.sub{color:var(--muted)}
.problems{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}.problem{background:#fff;border:1px solid var(--line);border-radius:22px;padding:26px}.problem .ico{background:#fff1f2;color:#e11d48}.problem h3{font-size:19px;margin:18px 0 6px}.problem p{margin:0;font-size:14px;color:var(--muted)}
.bento{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}.tile{background:white;border:1px solid var(--line);border-radius:25px;padding:30px;box-shadow:0 14px 40px #10233f08;min-height:225px}.tile.wide{grid-column:span 2;background:linear-gradient(135deg,#10233f,#204b7e);color:white;position:relative;overflow:hidden}.tile.wide p{color:#c9d9ef}.tile h3{font-size:23px;margin:22px 0 10px}.tile p{color:var(--muted);font-size:14px;margin:0}.ico{height:46px;width:46px;border-radius:15px;background:#edf4ff;color:#2f6dcb;display:grid;place-items:center;font-size:21px}.wide .ico{background:#ffffff1a;color:white}.orbit{position:absolute;width:290px;height:290px;border:1px solid #ffffff28;border-radius:50%;right:-90px;top:-100px;box-shadow:0 0 0 42px #ffffff09,0 0 0 88px #ffffff06}
.anatomy{display:grid;grid-template-columns:1fr 1fr;gap:40px;align-items:center}.checklist{list-style:none;padding:0;margin:0;display:grid;gap:14px}.checklist li{display:flex;gap:14px;align-items:flex-start;background:#fff;border:1px solid var(--line);border-radius:18px;padding:16px 18px}.checklist b{display:block;font-size:15px}.checklist small{color:var(--muted);font-size:13px;line-height:1.6}.tick{flex:none;height:28px;width:28px;border-radius:50%;background:#e9f6ef;color:#24865a;display:grid;place-items:center;font-weight:900;font-size:14px}
.article{background:#fff;border:1px solid #e2eaf4;border-radius:26px;box-shadow:0 35px 75px #10233f14;overflow:hidden}.article .img{height:190px;background:linear-gradient(135deg,#9fd3c7,#3d8f7a 55%,#205a4d);position:relative}.article .img:after{content:'Gambar khas ikut topik';position:absolute;left:16px;bottom:14px;background:#ffffffe0;color:#10233f;font-size:11px;font-weight:800;padding:6px 10px;border-radius:30px}.article .body{padding:24px}.article .meta{font-size:12px;color:#8794a5}.article h4{font-size:22px;letter-spacing:-.03em;margin:8px 0 10px;line-height:1.3}.article .line{height:9px;background:#eef2f7;border-radius:9px;margin:9px 0}.article .line.s{width:70%}.article .hook{margin:20px 24px 24px;background:linear-gradient(135deg,#10233f,#204b7e);color:#fff;border-radius:18px;padding:22px;text-align:center}.article .hook b{display:block;font-size:17px}.article .hook span{display:inline-block;margin-top:12px;background:var(--wa);border-radius:30px;padding:9px 18px;font-size:13px;font-weight:800}
.process{background:white;border-block:1px solid var(--line)}.steps{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}.step{padding:28px;border:1px solid var(--line);border-radius:22px;background:var(--bg)}.step b{font-size:12px;color:#3a77d4}.step h3{font-size:22px}.step p{color:var(--muted);font-size:14px}
.comparegrid{display:grid;grid-template-columns:1fr 1fr;gap:18px}.comparecard{padding:34px;background:#fff;border:1px solid #e5ebf2;border-radius:26px;box-shadow:0 14px 40px #10233f08}.comparecard h3{font-size:32px;margin:16px 0}.comparecard h3 small{font-size:16px}.comparecard.featured{background:linear-gradient(135deg,#10233f,#204b7e);color:#fff}.comparecard.featured p{color:#d9e5f6}.comparecard.featured .kicker{color:#9bc3ff}.rows{list-style:none;padding:0;margin:20px 0 0;display:grid;gap:12px}.rows li{display:flex;gap:12px;font-size:14px;line-height:1.6}.rows .x,.rows .ok{flex:none;height:24px;width:24px;border-radius:50%;display:grid;place-items:center;font-size:12px;font-weight:900}.rows .x{background:#fff1f2;color:#e11d48}.rows .ok{background:#ffffff1f;color:#7ee2a8}.comparecard:not(.featured) .rows b{color:var(--navy)}.featured .rows small{color:#c9d9ef}.rows small{display:block;color:var(--muted)}
.timeline{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;counter-reset:t}.when{background:#fff;border:1px solid var(--line);border-radius:22px;padding:24px;position:relative}.when .tag{display:inline-block;font-size:11px;font-weight:850;letter-spacing:1px;text-transform:uppercase;color:#245ca6;background:#f0f6ff;border:1px solid #d8e5f7;border-radius:30px;padding:6px 11px}.when h3{font-size:19px;margin:16px 0 6px}.when p{margin:0;font-size:14px;color:var(--muted)}
.pricing{display:grid;grid-template-columns:1fr 1fr;gap:18px;max-width:900px;margin:auto}.plan{background:#fff;border:1px solid var(--line);border-radius:28px;padding:34px;box-shadow:0 14px 40px #10233f08;display:flex;flex-direction:column}.plan.featured{border:2px solid var(--blue);box-shadow:0 30px 60px #3976df22;position:relative}.plan .badge{position:absolute;top:-13px;right:24px;background:var(--blue);color:#fff;font-size:11px;font-weight:850;border-radius:30px;padding:6px 12px;letter-spacing:.5px}.plan .amt{font-size:52px;font-weight:850;letter-spacing:-.06em;margin:10px 0 0}.plan .amt small{font-size:15px;font-weight:600;color:var(--muted);letter-spacing:0}.plan ul{list-style:none;padding:0;margin:22px 0 28px;display:grid;gap:11px;font-size:14px}.plan li{display:flex;gap:10px}.plan li:before{content:'✓';color:#24865a;font-weight:900}.plan .btn{margin-top:auto}.total{max-width:900px;margin:18px auto 0;text-align:center;color:var(--muted);font-size:14px}
.faqbox{border:1px solid #e2e7ee;border-radius:28px;overflow:hidden;background:white}.faqbox details{border-bottom:1px solid #e7ecf2;padding:0 32px}.faqbox details:last-child{border-bottom:0}.faqbox summary{list-style:none;cursor:pointer;padding:24px 0;display:flex;align-items:center;justify-content:space-between;gap:18px}.faqbox summary h3{font-size:17px;font-weight:720;line-height:1.5;margin:0;letter-spacing:-.01em}.faqbox summary::-webkit-details-marker{display:none}.faqbox summary:after{content:'+';font-size:29px;font-weight:350;color:#315e9b}.faqbox details[open] summary:after{content:'×'}.faqbox details p{margin:0 0 26px;color:#748295;max-width:850px;font-size:15px}
.cta{background:linear-gradient(135deg,#10233f,#204a7b);border-radius:32px;padding:70px 35px;text-align:center;color:white;position:relative;overflow:hidden}.cta h2{max-width:730px;margin:12px auto 20px}.cta p{color:#c8d8ec}.cta .actions{justify-content:center}.cta .secondary{background:transparent;border-color:#ffffff40;color:#fff}.ctalogo{width:96px;height:96px;border-radius:24px;margin:0 auto 6px;display:block;background:#fff;padding:6px}
.footer{padding:36px 0 40px;color:#8390a2;font-size:13px;border-top:1px solid var(--line);background:#fff}.footin{display:flex;justify-content:space-between;gap:20px;flex-wrap:wrap;align-items:center}.footer strong{color:#10233f}.footbrand{display:flex;align-items:center;gap:10px}.footbrand img{width:36px;height:36px;border-radius:9px}.footlinks{display:flex;gap:18px;flex-wrap:wrap}.footlinks a:hover{color:var(--blue)}
.wafloat{position:fixed;right:18px;bottom:18px;z-index:40;display:inline-flex;align-items:center;gap:9px;background:var(--wa);color:#fff;font-weight:800;font-size:14px;border-radius:100px;padding:14px 20px;box-shadow:0 14px 30px #1faa5355}.wafloat svg{width:20px;height:20px}
@media(max-width:980px){.problems,.timeline{grid-template-columns:repeat(2,1fr)}.anatomy{grid-template-columns:1fr}}
@media(max-width:850px){.links a:not(.btn){display:none}.herogrid{grid-template-columns:1fr;gap:30px}.hero{padding:64px 0 45px}.visual{min-height:400px;max-width:560px;width:100%;margin:auto}.bento{grid-template-columns:repeat(2,1fr)}.steps{grid-template-columns:1fr}}
@media(max-width:650px){.comparegrid,.pricing{grid-template-columns:1fr}.comparecard,.plan{padding:25px}}
@media(max-width:580px){.wrap{width:min(100% - 28px,1180px)}.navin{min-height:70px}.brand{font-size:19px}.brandlogo{width:39px;height:39px}.nav .btn{padding:11px 15px;font-size:12px;min-height:44px}h1{font-size:44px}.lead{font-size:15px}.visual{min-height:330px}.dashboard{padding:12px}.dashmain{padding:19px}.float{right:-4px;bottom:0}.section{padding:65px 0}.bento,.problems,.timeline{grid-template-columns:1fr}.tile.wide{grid-column:span 1}.tile{min-height:auto;padding:25px}.faqbox details{padding:0 20px}.faqbox summary{padding:20px 0}.cta{padding:55px 23px}.footin{flex-direction:column;align-items:flex-start}.pricechip{flex:1;min-width:130px}.footer{padding-bottom:96px}}
</style>
</head>
<body>
<header class="nav"><div class="wrap navin">
    <a class="brand" href="{{ url('/') }}"><img alt="Logo DYNO SEO" class="brandlogo" src="{{ asset('images/dynoseo-logo-128.webp') }}" width="48" height="48"> DYNO <span style="color:#3976df">SEO</span></a>
    <nav aria-label="Navigasi utama" class="links">
        <a href="#apa-itu-seo">Apa Itu SEO</a><a href="#kelebihan">Kelebihan</a><a href="#harga">Harga</a><a href="#faq">Soalan Lazim</a>
        <a class="btn primary" href="{{ $waStart }}">Mula Sekarang ↗</a>
    </nav>
</div></header>

<main id="utama">
<section class="hero"><div class="wrap herogrid">
    <div>
        <span class="eyebrow"><span class="dot"></span> Artikel SEO automatik setiap 2 hari</span>
        <h1>Website lebih mudah <span>ditemui</span> di Google.</h1>
        <p class="lead">Jangan biarkan website anda digantung tak bertali. DYNO SEO menulis dan menerbitkan artikel SEO automatik setiap 2 hari, lengkap dengan gambar khas dan butang WhatsApp, supaya bakal pelanggan lebih mudah menemui dan menghubungi bisnes anda.</p>
        <div class="pricebar">
            <div class="pricechip"><small>Bina website</small><b>RM{{ $websitePrice }}</b><small>Sekali bayar</small></div>
            <div class="pricechip"><small>Langgan DYNO SEO</small><b>RM{{ $price }}</b><small>Sebulan</small></div>
        </div>
        <div class="actions">
            <a class="btn btn-wa" href="{{ $waStart }}"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2a9.9 9.9 0 00-8.5 15l-1.5 5 5.2-1.4A9.9 9.9 0 1012.04 2zm5.8 14.1c-.25.7-1.45 1.33-2 1.38-.53.06-1.03.25-3.46-.72-2.92-1.15-4.79-4.15-4.93-4.34-.14-.2-1.18-1.57-1.18-3s.75-2.13 1.02-2.42c.26-.29.57-.36.76-.36l.55.01c.18 0 .42-.07.65.5.25.58.84 2.01.91 2.16.08.14.12.31.03.5-.1.2-.14.31-.29.48l-.43.5c-.14.14-.29.3-.12.59.17.28.75 1.24 1.6 2 1.1.98 2.03 1.29 2.31 1.43.29.15.46.12.62-.07.17-.2.72-.84.91-1.13.19-.28.38-.24.65-.14.26.1 1.68.79 1.97.94.29.14.48.21.55.33.07.12.07.7-.18 1.38z"/></svg> WhatsApp Kami</a>
            <a class="btn secondary" href="#kelebihan">Lihat Kelebihan →</a>
        </div>
        <p class="micro">Tiada kontrak · Boleh berhenti bila-bila masa · DYNO SEO untuk website yang dibina oleh pihak kami</p>
    </div>
    <div class="visual" aria-hidden="true">
        <div class="dashboard">
            <div class="dashhead"><span>✦ DYNO SEO · Gambaran Analitik</span><span class="pill">● Contoh Paparan</span></div>
            <div class="dashmain"><small>Artikel diterbitkan · ilustrasi</small><strong>Kandungan Bertambah ↗</strong><small>Setiap 2 hari, satu artikel baru di blog anda.</small>
                <div class="bars"><i style="height:28%"></i><i style="height:39%"></i><i style="height:35%"></i><i style="height:53%"></i><i style="height:49%"></i><i style="height:68%"></i><i style="height:63%"></i><i style="height:82%"></i><i style="height:75%"></i><i style="height:95%"></i></div>
            </div>
            <div class="dashbottom"><div class="mini"><small>Topik & kata kunci</small><b>Terancang ✓</b></div><div class="mini"><small>Butang WhatsApp</small><b>Setiap artikel ↗</b></div></div>
        </div>
        <div class="float">✧ Artikel baru <span>diterbitkan</span></div>
    </div>
</div></section>

<div class="strip"><div class="wrap stripin"><span>Sesuai untuk:</span><span>Kedai makan</span><span>Kontraktor</span><span>Klinik</span><span>Butik</span><span>Servis rumah</span><span>Kedai runcit</span><span>Bengkel</span></div></div>

<section class="section" id="masalah"><div class="wrap">
    <div class="sectionhead center"><span class="kicker">MASALAH BIASA</span><h2>Website digantung tak bertali?</h2><p class="sub">Kebanyakan website bisnes siap, kemudian dibiarkan. Hidup tanpa tujuan, mati sebelum usianya.</p></div>
    <div class="problems">
        <article class="problem"><span class="ico">◌</span><h3>Tiada pengunjung</h3><p>Google jarang datang semula ke website yang tidak pernah berubah.</p></article>
        <article class="problem"><span class="ico">✕</span><h3>Tiada jualan</h3><p>Tiada pengunjung bermakna tiada pertanyaan dan tiada pelanggan baru.</p></article>
        <article class="problem"><span class="ico">◷</span><h3>Jarang dikemas kini</h3><p>Pemilik bisnes sibuk. Tiada masa untuk menulis artikel setiap minggu.</p></article>
        <article class="problem"><span class="ico">↘</span><h3>Kedudukan tenggelam</h3><p>Pesaing yang aktif menerbitkan kandungan naik di atas anda.</p></article>
    </div>
</div></section>

<section class="section process" id="apa-itu-seo"><div class="wrap">
    <div class="sectionhead"><span class="kicker">KENALI SEO</span><h2>Apa itu SEO dan bagaimana ia berfungsi?</h2><p class="sub">SEO ialah usaha menjadikan website lebih mudah difahami oleh Google supaya berpeluang muncul apabila pelanggan mencari produk atau perkhidmatan anda.</p></div>
    <div class="bento">
        <article class="tile wide"><span class="ico">⌕</span><h3>Pelanggan mencari di Google. Adakah mereka menemui anda?</h3><p>Contohnya, pelanggan menaip “kedai kek di Kota Bharu”. Kandungan website yang berkaitan, jelas dan berkualiti membantu Google memahami perniagaan anda. Kedudukan carian tetap bergantung pada pelbagai faktor.</p></article>
        <article class="tile"><span class="ico">✍</span><h3>Artikel setiap 2 hari</h3><p>DYNO SEO menulis dan menerbitkan artikel secara automatik setiap 2 hari supaya website sentiasa mempunyai kandungan baharu.</p></article>
        <article class="tile"><span class="ico">◎</span><h3>Kata kunci automatik</h3><p>Sistem mencari dan menyusun topik serta kata kunci yang relevan dengan bidang perniagaan anda, tanpa mengulang topik yang sama.</p></article>
        <article class="tile"><span class="ico">↗</span><h3>Bina trafik organik</h3><p>Kandungan yang baik berpotensi menarik pengunjung daripada carian tanpa membayar untuk setiap klik.</p></article>
        <article class="tile"><span class="ico">◷</span><h3>Berjalan sepanjang masa</h3><p>Proses automasi beroperasi 24 jam, 7 hari seminggu. Hasil carian memerlukan masa dan tidak dijamin.</p></article>
    </div>
</div></section>

<section class="section" id="artikel"><div class="wrap anatomy">
    <div>
        <div class="sectionhead" style="margin-bottom:24px"><span class="kicker">SETIAP ARTIKEL</span><h2>Apa yang anda dapat dalam setiap artikel?</h2><p class="sub">Bukan sekadar teks. Setiap artikel disusun supaya mudah dibaca pelanggan dan mudah difahami Google.</p></div>
        <ul class="checklist">
            <li><span class="tick">✓</span><span><b>800 hingga 1,200 patah perkataan</b><small>Bahasa Melayu yang mudah dibaca di telefon, dengan subtajuk yang tersusun.</small></span></li>
            <li><span class="tick">✓</span><span><b>Gambar khas ikut topik</b><small>Gambar baru dijana untuk setiap artikel, tanpa tanda air.</small></span></li>
            <li><span class="tick">✓</span><span><b>Soalan lazim (FAQ)</b><small>Menjawab soalan yang biasa ditanya pelanggan dalam bidang anda.</small></span></li>
            <li><span class="tick">✓</span><span><b>Butang WhatsApp ke bisnes anda</b><small>Pembaca terus WhatsApp anda, dengan tajuk artikel yang mereka baca.</small></span></li>
            <li><span class="tick">✓</span><span><b>Tajuk SEO, schema, sitemap dan pautan dalaman</b><small>Elemen teknikal yang membantu Google memahami dan mengindeks artikel.</small></span></li>
            <li><span class="tick">✓</span><span><b>Disemak sebelum terbit</b><small>Sistem menolak topik berulang, fakta yang meragukan dan bahasa yang janggal.</small></span></li>
        </ul>
    </div>
    <div class="article" aria-hidden="true">
        <div class="img"></div>
        <div class="body"><span class="meta">Blog · Hari ini · 6 min baca</span><h4>Gabion wall vs concrete wall: mana sesuai untuk cerun rumah?</h4><div class="line"></div><div class="line"></div><div class="line s"></div></div>
        <div class="hook"><b>Cerun rumah anda mula runtuh?</b><span>💬 WhatsApp Kami</span></div>
    </div>
</div></section>

<section class="section process" id="kelebihan"><div class="wrap">
    <div class="sectionhead"><span class="kicker">KELEBIHAN DYNO SEO</span><h2>Website sendiri. SEO lebih tersusun.</h2><p class="sub">Mulakan dengan website daripada kami dan biar DYNO SEO urus kandungan untuk membantu pelanggan menemui perniagaan anda.</p></div>
    <div class="bento">
        <article class="tile wide"><div class="orbit"></div><span class="ico">✦</span><h3>Artikel diterbitkan secara automatik</h3><p>DYNO SEO menghasilkan dan menerbitkan artikel blog setiap 2 hari pada website DYNO anda. Anda tidak perlu menulis sepatah pun.</p></article>
        <article class="tile"><span class="ico">⌕</span><h3>Penyelidikan Kata Kunci</h3><p>Mencari dan menyusun kata kunci yang berkaitan dengan produk dan perkhidmatan anda secara automatik.</p></article>
        <article class="tile"><span class="ico">▤</span><h3>Kandungan Mesra SEO</h3><p>Tajuk, struktur artikel dan penerangan yang mudah difahami pengguna serta enjin carian.</p></article>
        <article class="tile"><span class="ico">↗</span><h3>Pengoptimuman Artikel</h3><p>Setiap artikel lengkap dengan tajuk halaman, penerangan meta, pautan dalaman dan sitemap.</p></article>
        <article class="tile"><span class="ico">◈</span><h3>Selari dengan Bisnes Anda</h3><p>Topik dipilih ikut bidang perniagaan anda, dan setiap artikel ditutup dengan butang WhatsApp ke bisnes anda.</p></article>
    </div>
</div></section>

<section class="section" id="perbandingan"><div class="wrap">
    <div class="sectionhead"><span class="kicker">FAHAMI PERBEZAANNYA</span><h2>Google Ads atau DYNO SEO?</h2><p class="sub">Iklan berbayar membantu mendapatkan pendedahan segera. SEO pula membina keterlihatan organik dari semasa ke semasa. Iklan menyewa trafik, SEO membina aset.</p></div>
    <div class="comparegrid">
        <article class="comparecard"><span class="kicker" style="color:#e11d48">GOOGLE ADS</span><h3>RM2,000 <small>/bulan (contoh bajet)</small></h3><p class="sub">Kos sebenar bergantung pada persaingan, kata kunci dan bajet. RM2,000 sebulan hanyalah contoh, bukan harga tetap.</p>
            <ul class="rows">
                <li><span class="x">✕</span><span><b>Bayar setiap klik</b><small>Setiap klik, bajet terus berkurang.</small></span></li>
                <li><span class="x">✕</span><span><b>Trafik berhenti</b><small>Sebaik anda berhenti bayar, iklan hilang.</small></span></li>
                <li><span class="x">✕</span><span><b>Kos klik boleh naik</b><small>Pesaing boleh bida lebih tinggi daripada anda.</small></span></li>
                <li><span class="x">✕</span><span><b>Menyewa trafik</b><small>Tiada aset kekal selepas kempen tamat.</small></span></li>
            </ul>
        </article>
        <article class="comparecard featured"><span class="kicker">DYNO SEO</span><h3>RM{{ $price }} <small>/bulan</small></h3><p>Artikel automatik setiap 2 hari, penyusunan kata kunci dan usaha membina trafik organik jangka panjang.</p>
            <ul class="rows">
                <li><span class="ok">✓</span><span><b>Harga tetap</b><small>RM{{ $price }} sebulan, tidak kira berapa ramai yang membaca.</small></span></li>
                <li><span class="ok">✓</span><span><b>Artikel kekal</b><small>Artikel yang diterbitkan kekal di website anda.</small></span></li>
                <li><span class="ok">✓</span><span><b>Tiada kontrak</b><small>Boleh berhenti bila-bila masa.</small></span></li>
                <li><span class="ok">✓</span><span><b>Bina aset jangka panjang</b><small>Website, kandungan dan pelanggan milik anda sendiri.</small></span></li>
            </ul>
        </article>
    </div>
    <p class="sub" style="font-size:12px;margin-top:18px">Nota: SEO tidak menjamin kedudukan nombor satu, bilangan pengunjung atau jumlah jualan. Trafik organik juga boleh berubah mengikut algoritma dan persaingan.</p>
</div></section>

<section class="section process" id="cara"><div class="wrap">
    <div class="sectionhead"><span class="kicker">CARA BERMULA</span><h2>Tiga langkah mudah untuk bermula.</h2></div>
    <div class="steps">
        <div class="step"><b>LANGKAH 01</b><h3>Bina website anda</h3><p>WhatsApp kami dan tempah website dengan bayaran RM{{ $websitePrice }} sekali sahaja.</p></div>
        <div class="step"><b>LANGKAH 02</b><h3>Beritahu bisnes anda</h3><p>Satu ayat cukup, contohnya "Pasang gabion dan tembok cerun di Kelantan". Kami aktifkan DYNO SEO RM{{ $price }} sebulan.</p></div>
        <div class="step"><b>LANGKAH 03</b><h3>Artikel terbit sendiri</h3><p>Setiap 2 hari, artikel baru masuk ke blog anda. Pembaca yang berminat terus WhatsApp anda.</p></div>
    </div>
</div></section>

<section class="section" id="perjalanan"><div class="wrap">
    <div class="sectionhead"><span class="kicker">SELEPAS ANDA MULA</span><h2>Apa yang berlaku selepas anda melanggan?</h2><p class="sub">SEO ialah usaha berterusan. Ini gambaran biasa, bukan janji keputusan.</p></div>
    <div class="timeline">
        <div class="when"><span class="tag">Hari pertama</span><h3>Blog diaktifkan</h3><p>Halaman blog dipasang di website anda dan artikel pertama ditulis.</p></div>
        <div class="when"><span class="tag">Minggu pertama</span><h3>3 hingga 4 artikel</h3><p>Website mula dikemas kini secara konsisten. Google nampak website anda aktif.</p></div>
        <div class="when"><span class="tag">Bulan pertama</span><h3>±15 artikel</h3><p>Kandungan dalam bidang anda bertambah, dengan pautan antara artikel.</p></div>
        <div class="when"><span class="tag">3 hingga 6 bulan</span><h3>Peluang ditemui</h3><p>Biasanya tempoh Google mula memberi kedudukan, bergantung pada bidang dan persaingan.</p></div>
    </div>
</div></section>

<section class="section process" id="harga"><div class="wrap">
    <div class="sectionhead center"><span class="kicker">HARGA</span><h2>Harga jelas. Tiada caj tersembunyi.</h2><p class="sub">Bina website sekali, kemudian biar DYNO SEO terus menghidupkannya setiap bulan.</p></div>
    <div class="pricing">
        <div class="plan">
            <span class="kicker">BINA WEBSITE</span>
            <p class="amt">RM{{ $websitePrice }} <small>sekali bayar</small></p>
            <ul><li>Website untuk bisnes anda</li><li>Mesra telefon</li><li>Butang WhatsApp ke bisnes anda</li><li>Sedia untuk DYNO SEO</li></ul>
            <a class="btn secondary" href="{{ $wa("Salam, saya nak tempah website RM{$websitePrice}.") }}">Tempah Website</a>
        </div>
        <div class="plan featured">
            <span class="badge">PALING BERNILAI</span>
            <span class="kicker">LANGGAN DYNO SEO</span>
            <p class="amt">RM{{ $price }} <small>/bulan</small></p>
            <ul><li>Artikel SEO baru setiap 2 hari (±15 sebulan)</li><li>Gambar khas untuk setiap artikel</li><li>Topik & kata kunci disusun automatik</li><li>Butang WhatsApp di bawah setiap artikel</li><li>Sitemap, schema dan pautan dalaman</li><li>Tiada kontrak, boleh berhenti bila-bila masa</li></ul>
            <a class="btn btn-wa" href="{{ $waStart }}">Langgan Melalui WhatsApp</a>
        </div>
    </div>
    <p class="total">Bulan pertama: RM{{ $websitePrice }} + RM{{ $price }} = <b>RM{{ $websitePrice + $price }}</b>. Bulan seterusnya: RM{{ $price }} sahaja.</p>
</div></section>

<section class="section" id="faq"><div class="wrap">
    <div class="sectionhead"><span class="kicker">SOALAN LAZIM</span><h2>Soalan mudah. Jawapan jelas.</h2><p class="sub">Semua yang anda perlu tahu sebelum bermula dengan DYNO SEO.</p></div>
    <div class="faqbox">
        @foreach ($faqs as $i => [$q, $a])
            <details @if ($i === 0) open @endif><summary><h3>{{ $q }}</h3></summary><p>{{ $a }}</p></details>
        @endforeach
    </div>
</div></section>

<section class="section" id="hubungi" style="padding-top:0"><div class="wrap"><div class="cta">
    <img src="{{ asset('images/dynoseo-logo.webp') }}" alt="Logo DYNO SEO" class="ctalogo" width="96" height="96" loading="lazy">
    <span class="kicker" style="color:#9ec4ff">MULAKAN LANGKAH SETERUSNYA</span>
    <h2>Jadikan website anda mesin jualan sekarang.</h2>
    <p>Bina website pada harga RM{{ $websitePrice }} sekali bayar dan langgan DYNO SEO pada harga RM{{ $price }} sebulan. WhatsApp kami di {{ $waDisplay }}.</p>
    <div class="actions">
        <a class="btn btn-wa" href="{{ $waStart }}">💬 WhatsApp {{ $waDisplay }}</a>
        <a class="btn secondary" href="{{ $waHave }}">Saya dah ada website</a>
    </div>
</div></div></section>
</main>

<footer class="footer"><div class="wrap footin">
    <div class="footbrand"><img src="{{ asset('images/dynoseo-logo-128.webp') }}" alt="" width="36" height="36" loading="lazy"><span><strong>DYNO SEO</strong> · © {{ date('Y') }} {{ $company }}, Pasir Mas, Kelantan</span></div>
    <nav class="footlinks" aria-label="Pautan kaki"><a href="#harga">Harga</a><a href="#faq">Soalan Lazim</a><a href="{{ $waAsk }}">WhatsApp {{ $waDisplay }}</a><a href="{{ route('login') }}">Log masuk</a></nav>
</div></footer>

<a class="wafloat" href="{{ $waAsk }}" aria-label="WhatsApp DYNO SEO"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2a9.9 9.9 0 00-8.5 15l-1.5 5 5.2-1.4A9.9 9.9 0 1012.04 2zm5.8 14.1c-.25.7-1.45 1.33-2 1.38-.53.06-1.03.25-3.46-.72-2.92-1.15-4.79-4.15-4.93-4.34-.14-.2-1.18-1.57-1.18-3s.75-2.13 1.02-2.42c.26-.29.57-.36.76-.36l.55.01c.18 0 .42-.07.65.5.25.58.84 2.01.91 2.16.08.14.12.31.03.5-.1.2-.14.31-.29.48l-.43.5c-.14.14-.29.3-.12.59.17.28.75 1.24 1.6 2 1.1.98 2.03 1.29 2.31 1.43.29.15.46.12.62-.07.17-.2.72-.84.91-1.13.19-.28.38-.24.65-.14.26.1 1.68.79 1.97.94.29.14.48.21.55.33.07.12.07.7-.18 1.38z"/></svg> WhatsApp</a>
</body>
</html>
