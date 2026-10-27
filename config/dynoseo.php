<?php

return [

    // Redirect other hosts (Forge test domain, www) to APP_URL's host. See RedirectToCanonicalHost.
    'canonical_redirect' => (bool) env('CANONICAL_REDIRECT', true),

    // Safety net: the scheduler (cron, every minute) also works the queue until it is empty,
    // so articles are still written if the Forge queue worker is down. See routes/console.php.
    'queue_via_scheduler' => (bool) env('QUEUE_VIA_SCHEDULER', true),

    // Public prices on the sales page (RM). Dyno SEO per website per month; website build once.
    'price_myr' => (int) env('DYNOSEO_PRICE_MYR', 49),
    'website_price_myr' => (int) env('WEBSITE_PRICE_MYR', 200),
    // Introductory price: shown as "harga pengenalan untuk N pelanggan pertama". 0 hides the note.
    'intro_slots' => (int) env('DYNOSEO_INTRO_SLOTS', 100),

    'company' => [
        'name' => env('COMPANY_NAME', 'DynoPOS Technologies'),
        'whatsapp' => env('COMPANY_WHATSAPP', '0182889932'),
    ],

    'ai' => [
        'model_write' => env('GEMINI_MODEL_WRITE'),
        'model_check' => env('GEMINI_MODEL_CHECK'),
        'model_image' => env('IMAGEN_MODEL'),
        // Optional Gemini thinking budget per purpose. Leave empty to use the model default.
        'thinking_write' => env('GEMINI_THINKING_BUDGET_WRITE'),
        'thinking_check' => env('GEMINI_THINKING_BUDGET_CHECK'),
        // Google Search grounding stays off (CLAUDE.md, kawalan kos).
        'grounding' => false,
        // Platform-wide AI cost cap per month (MYR), all websites together.
        'monthly_budget_myr' => (float) env('AI_MONTHLY_BUDGET_MYR', 300),
        // Hard cap on Vertex calls per day, all websites together.
        'max_daily_calls' => (int) env('MAX_DAILY_AI_CALLS', 300),
        'max_tokens_plan' => 4000,
        'max_tokens_write' => 8192,
        'max_tokens_check' => 1500,
        'max_tokens_analyse' => 3000,
        // Rewrites after the first draft (spec §4: maximum 2).
        'max_rewrites' => 2,
        'max_image_attempts' => 2,
    ],

    'articles' => [
        'min_words' => 700,
        'max_words' => 1500,
        'topics_per_plan' => 20,
        'refill_below' => 5,
        // Keywords the admin wants a website to rank for; each gets a topic before the rest.
        'max_target_keywords' => 5,
        'max_per_site_per_day' => (int) env('MAX_ARTICLES_PER_SITE_PER_DAY', 1),
        'default_frequency_days' => 2,
        // Random publish time between these hours (Asia/Kuala_Lumpur), spec §4.10.
        'publish_window' => [8, 21],
        // A failing website is retried after this many hours, and stops after max_failures.
        'retry_after_hours' => 3,
        'max_failures' => 3,
        // Websites whose paid_until passed more than this many days ago are not written for.
        'grace_days' => 7,
        'cards_per_page' => 12,
        'home_cards' => 3,
        'related' => 3,
        'image_width' => 1200,
        'image_height' => 675,
        'image_quality' => 80,
    ],

    'github' => [
        'token_expires_at' => env('GITHUB_TOKEN_EXPIRES_AT'),
        'warn_days' => 14,
    ],

    'indexnow_key' => env('INDEXNOW_KEY'),

    'price_table_path' => env('PRICE_TABLE_PATH', 'config/ai_prices.php'),
];
