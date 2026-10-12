<?php

it('shows the public sales page with one h1, price, schema and WhatsApp links', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect(substr_count($html, '<h1'))->toBe(1)
        ->and($html)->toContain('Website lebih mudah <span>ditemui</span> di Google.')
        ->toContain('<title>DYNO SEO | Artikel SEO Automatik untuk Website Anda</title>')
        ->toContain('RM200')
        ->toContain('RM249')
        ->toContain('dynoseo-logo')
        ->toContain('rel="canonical"')
        ->toContain('og:image')
        ->toContain('"@type":"FAQPage"')
        ->toContain('"priceCurrency":"MYR"')
        ->toContain('RM49')
        ->toContain('https://wa.me/60182889932?text=')
        ->toContain('Google Ads atau DYNO SEO?')
        ->not->toContain('mailto:');
});

it('shows the demo video and the same timeline as the video', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('id="video"')
        ->toContain('<video controls playsinline preload="none"')
        ->toContain(asset('videos/dynoseo-demo.mp4'))
        ->toContain('"@type":"VideoObject"')
        ->toContain('Minggu 1')
        ->toContain('Minggu 9 – 12')
        ->toContain('SEO mula berkesan')
        ->not->toContain('3 hingga 6 bulan');

    expect(public_path('videos/dynoseo-demo.mp4'))->toBeFile()
        ->and(public_path('videos/dynoseo-demo-poster.jpg'))->toBeFile();
});

it('lets search engines crawl only the sales page', function () {
    $robots = $this->get('/robots.txt')->assertOk()->getContent();
    expect($robots)->toContain('Allow: /$')->toContain('Allow: /videos/')->toContain('Disallow: /')->toContain('Sitemap: '.url('/sitemap.xml'));

    $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml')->assertSee(url('/'), false);
});

it('keeps the app pages noindex', function () {
    $this->get('/masuk')->assertOk()->assertSee('noindex', false);
});

it('uses the configured price everywhere on the page', function () {
    config(['dynoseo.price_myr' => 59]);

    $this->get('/')->assertSee('RM59')->assertDontSee('RM49');
});

it('marks the monthly price as an introductory price for the first customers', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('100 pelanggan pertama')
        ->toContain('Harga akan naik')
        ->toContain('HARGA PENGENALAN');
});

it('hides the introductory price note when it is switched off', function () {
    config(['dynoseo.intro_slots' => 0]);

    $this->get('/')->assertOk()
        ->assertDontSee('pelanggan pertama')
        ->assertDontSee('Harga akan naik')
        ->assertSee('PALING BERNILAI');
});
