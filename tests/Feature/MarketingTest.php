<?php

it('shows the public sales page with one h1, price, schema and WhatsApp links', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect(substr_count($html, '<h1'))->toBe(1)
        ->and($html)->toContain('Artikel SEO automatik untuk website anda')
        ->toContain('<title>Artikel SEO Automatik untuk Website Anda | Dyno SEO</title>')
        ->toContain('rel="canonical"')
        ->toContain('og:image')
        ->toContain('"@type":"FAQPage"')
        ->toContain('"priceCurrency":"MYR"')
        ->toContain('RM49')
        ->toContain('https://wa.me/60182889932?text=')
        ->toContain('Google Ads vs Dyno SEO');
});

it('lets search engines crawl only the sales page', function () {
    $robots = $this->get('/robots.txt')->assertOk()->getContent();
    expect($robots)->toContain('Allow: /$')->toContain('Disallow: /')->toContain('Sitemap: '.url('/sitemap.xml'));

    $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml')->assertSee(url('/'), false);
});

it('keeps the app pages noindex', function () {
    $this->get('/masuk')->assertOk()->assertSee('noindex', false);
});

it('uses the configured price everywhere on the page', function () {
    config(['dynoseo.price_myr' => 59]);

    $this->get('/')->assertSee('RM59')->assertDontSee('RM49');
});
