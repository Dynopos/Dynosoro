# Changelog

## Carian AI (ChatGPT, Gemini) — 2026-10-27

- **robots.txt dynoseo.my** kini benarkan `/blog/` dan `/llms.txt`. Sebelum ini blog Dyno SEO
  sendiri disekat (`Disallow: /`) daripada semua enjin carian. Sitemap blog disebut juga.
- **Bot AI dinamakan** (GPTBot, OAI-SearchBot, ChatGPT-User, Google-Extended, PerplexityBot,
  ClaudeBot, Claude-SearchBot) dalam robots.txt yang app cipta. robots.txt yang dicipta oleh
  versi lama app dinaik taraf semasa "Pasang blog"; robots.txt milik pemilik tidak diubah.
- **llms.txt** (llmstxt.org) setiap website: ringkasan bisnes (dari analisis disahkan), kawasan,
  WhatsApp, dan senarai artikel. Di root untuk website statik; di `/blog/llms.txt` untuk app
  Laravel atau bila pemilik sudah ada llms.txt sendiri. dynoseo.my hidangkan `/llms.txt`.
- **Schema Organization** dalam setiap artikel: nama, bidang (ringkasan analisis), kawasan,
  telefon WhatsApp, kluster topik (`knowsAbout`). Hanya fakta yang admin isi atau sahkan.

## Analisis bisnes — 2026-10-17

Punca: dalam demo kepada pelanggan skylift, app merancang posting tentang oksigen. AI hanya
diberi satu baris penerangan ("skylift") dan meneka bidangnya sendiri tanpa disemak.

- **Analisis bisnes setiap website** (`SiteAnalyst`, prompt `analyse-v1`, model tulis): AI baca
  halaman utama website dari repo GitHub, kemudian tentukan bidang, apa bisnes buat,
  perkhidmatan, pelanggan, apa yang pelanggan cari di Google, 4–6 kluster topik, dan topik yang
  TIDAK boleh ditulis. Jika kurang pasti, AI tanya satu soalan.
- **Admin sahkan dahulu.** Kad website tunjuk analisis; admin tekan "Betul, sahkan", atau tulis
  pembetulan dan "Analisis semula". Website baru tidak aktif sehingga analisis disahkan.
- **Tiada terbit tanpa analisis disahkan**: website lama juga ditahan (nota pada kad, bukan
  dikira gagal) sehingga analisisnya disahkan. Selepas disahkan, topik AI lama yang tersasar
  bidang dibuang.
- **Topik dirancang ikut analisis** (`plan-v3`): setiap topik mesti dalam kluster yang disahkan;
  kod buang topik luar kluster atau yang menyentuh senarai "jangan tulis".
- **Penulis dan penyemak** (`write-v3`, `check-v3`) diberi bidang, kluster dan senarai "jangan
  tulis"; penyemak gagalkan artikel yang tersasar bidang.

## Fasa 0 — 2026-10-11

Enjin Dyno SEO untuk Bob (admin sahaja). Belum diuji dengan Vertex AI dan GitHub sebenar.

- **Website:** tambah/edit website (repo GitHub, bidang, pembaca, kawasan, topik dilarang,
  kekerapan, bayar sehingga, hook bawah artikel), Semak repo, Pasang blog, Aktifkan/Henti,
  Terbit sekarang, Bina semula, Sambung semula selepas Vertex berhenti.
- **Topik:** pelan 20 topik umum ikut bidang (model murah), isi semula bila tinggal < 5,
  tambah topik sendiri (naik ke atas), susun, buang. Topik berulang ditolak.
- **Artikel:** tulis (Gemini) → semakan kod → semakan AI → tulis semula maksimum 2 kali →
  gambar Imagen (WebP 1200×675) → satu commit ke GitHub: halaman artikel, gambar, senarai
  blog bernombor, `blog/sitemap.xml`, `blog/feed.xml`, blok "Artikel terkini" di laman utama.
  Schema BlogPosting + Breadcrumb + FAQ, canonical, Open Graph, pautan dalaman, artikel berkaitan.
- **Hook** di bawah setiap artikel dari template (WhatsApp dengan tajuk artikel, atau pautan).
- **Skrin Artikel:** lihat, edit (terus dikemas kini di website), padam, gambar baru, terbit.
- **Skrin Kos:** kos bulan ini, had bulanan dan harian, ikut jenis dan ikut website.
- **Jadual:** setiap 15 minit hantar website yang sampai masa; terbit setiap N hari pada
  waktu rawak 8 pagi – 9 malam; had satu artikel sehari setiap website.
- **Keselamatan kos:** `AiBudget` sebelum setiap panggilan, had harian, tiada panggilan
  tanpa harga model, `tries = 1`, Ralat selepas 3 kegagalan, tangguh semua bila billing
  GCP berhenti, e-mel amaran kepada admin (termasuk token GitHub hampir tamat).
- Arahan: `dynoseo:admin`, `dynoseo:publish {site}`, `dynoseo:rebuild {site}`,
  `dynoseo:dispatch-due`.
- 77 test Pest (semua API dipalsukan), termasuk sekurang-kurangnya satu test untuk setiap
  peraturan dalam CLAUDE.md.
- Panduan pasang: `docs/DEPLOY.md`.
- Sokongan website Laravel (Folder web `public`), untuk ujian pertama di dynolead.my.
- Domain app: `dynoseo.my`.
- Halaman jualan `dynoseo.my/` (design Bob, logo baru, RM200 + RM49/bulan, WhatsApp, 15 FAQ, schema, sitemap).
- Video demo "Cara DYNO SEO berfungsi" di halaman jualan (`public/videos/`, schema VideoObject). Timeline
  hasil ikut video: Minggu 1, 2–4, 5–8, 9–12 (SEO mula berkesan).
- RM49 ditanda **harga pengenalan untuk 100 pelanggan pertama, harga akan naik** (halaman jualan,
  FAQ, schema, video). `DYNOSEO_INTRO_SLOTS` (lalai 100, 0 = sembunyi).
- Borang website ringkas (macam Soro): nama bisnes, apa bisnes ini, domain, repo, WhatsApp.
  Lain-lain dalam "Tetapan lanjutan". Hook standard jika kosong. **Simpan & mula** terus
  semak repo, pasang blog dan aktifkan. App Laravel (`artisan` + `public/index.php`) dikesan
  sendiri (folder web `public`). Prompt `plan-v2`, `write-v2`, `check-v2` terima penerangan bisnes.

## Spec v0.1 — 2026-10-11

- Spec pertama Dyno SEO (`docs/dynoseo-spec.md`), `CLAUDE.md`, `docs/decisions.md`.
- Keputusan Bob: `seo.dynolead.my`, langganan RM59/bulan, pelayan Forge baru, dynopos.my kekal Soro.
- Belum ada kod. Fasa 0 bermula selepas Bob beri arahan.
