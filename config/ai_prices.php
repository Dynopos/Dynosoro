<?php

/*
|--------------------------------------------------------------------------
| Jadual harga Vertex AI (Gemini + Imagen)
|--------------------------------------------------------------------------
| Harga senarai Google, diisi Oktober 2026. SEMAK SEMULA di
| https://cloud.google.com/vertex-ai/generative-ai/pricing sebelum hidupkan, dan bila
| tukar model dalam .env (GEMINI_MODEL_WRITE, GEMINI_MODEL_CHECK, IMAGEN_MODEL).
|
| - Teks: USD setiap 1 juta token. Token "thinking" dikira sebagai output.
| - Gambar: USD setiap gambar.
| - Kunci = ID model yang sama seperti dalam .env.
|
| Model yang tiada dalam jadual ini TIDAK akan dipanggil, sebab had kos tidak boleh dikira
| dengan betul. Ini sengaja (paling selamat). Jangan tulis harga di tempat lain dalam kod.
*/

return [

    'text' => [
        'gemini-2.5-flash' => ['input' => 0.30, 'output' => 2.50],
        'gemini-2.5-flash-lite' => ['input' => 0.10, 'output' => 0.40],
        'gemini-2.5-pro' => ['input' => 1.25, 'output' => 10.00],
    ],

    'image' => [
        'imagen-4.0-generate-001' => 0.04,
        'imagen-4.0-fast-generate-001' => 0.02,
    ],

    // Kadar tukaran USD ke RM (sama seperti Dyno Lead, ditetapkan Bob Oktober 2026).
    'usd_to_myr' => 4.70,
];
