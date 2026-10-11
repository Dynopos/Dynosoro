# Keputusan

Catat setiap keputusan reka bentuk di sini (tarikh, keputusan, sebab).

## 2026-10-11 — Spec v0.1

- **Artikel umum ikut bidang, bukan tentang bisnes** (arahan Bob). Contoh website gabion:
  cerun, teknologi cerun, gabion wall vs concrete wall. Bisnes hanya dalam hook bawah artikel.
- **Terus terbit tanpa langkah lulus** (arahan Bob, sama seperti Soro). Keselamatan dalam
  kod: semakan kod + semakan AI murah, tulis semula maksimum 2 kali, admin boleh edit/padam.
- **Kekerapan lalai setiap 2 hari** (arahan Bob). Boleh ubah setiap website.
- **Hook bawah artikel wajib** (arahan Bob, contoh kotak "Nak kedai anda lebih sistematik?"
  di dynopos.my). Hook datang dari template, bukan tulisan AI, supaya AI tidak reka janji.
- **Gambar AI umum ikut topik artikel** (arahan Bob). Satu gambar setiap artikel, suasana
  Malaysia, tiada teks/logo, berbeza dari gambar terdahulu.
- **Website pelanggan: HTML statik di GitHub Pages, semua repo dalam akaun GitHub Bob.**
  Satu fine-grained token untuk Fasa 0; GitHub App kemudian.
- **URL artikel statik `/blog/<slug>/`**, bukan `?post=` seperti Soro, supaya mesra SEO.
- **Laravel di Forge, bukan Cloud Run.** Sama stack dengan Dyno Lead (guna semula corak
  workspace, AiBudget, ai_usage). Kredit GCP tinggal ~12 minggu; pindah hosting tidak berbaloi.
- **Vertex AI (Gemini + Imagen)** supaya kredit GCP Free Trial yang bayar.
- **Risiko yang Bob terima:** polisi Google "scaled content abuse" boleh kurangkan ranking
  artikel AI umum. Dikurangkan dengan kekerapan sederhana (2 hari), semakan kualiti, topik
  tidak berulang, dan pautan dalaman.

## 2026-10-11 — Jawapan Bob

- **Panel app di `seo.dynolead.my`.** Artikel tinggal dalam website pelanggan, tetapi enjin,
  panel admin dan portal pelanggan perlukan alamat. Subdomain dynolead.my: tiada kos domain baru.
- **Langganan RM59 sebulan setiap website.** Kos AI anggaran ± RM6/bulan. Fasa 0 bayaran
  manual (`paid_until`), CHIP dalam Fasa 2.
- **Pelayan Forge baru** untuk Dyno SEO, berasingan dari Dyno Lead.
- **dynopos.my kekal dengan Soro**; Dyno SEO tidak sentuh repo itu.

## 2026-10-11 — Fasa 0 (enjin + admin)

- **Laravel 12 + Livewire 3**, asas disalin dari Dyno Lead (login, `BelongsToWorkspace`,
  `CurrentWorkspace`, susun atur telefon). Tiada daftar akaun: admin dicipta dengan
  `php artisan dynoseo:admin`.
- **Semua website Fasa 0 dalam workspace dalaman Bob.** Setiap jadual tetap ada
  `workspace_id` dan job berjalan dalam workspace website (`runAs`), supaya Fasa 1 boleh
  pindah website ke workspace pelanggan tanpa ubah model data.
- **Model lalai:** `gemini-2.5-flash` (tulis), `gemini-2.5-flash-lite` (pelan topik dan
  semakan), `imagen-4.0-fast-generate-001` (gambar). Murah dan cukup untuk artikel umum.
  Harga dalam `config/ai_prices.php` diisi dari harga senarai Google yang diketahui;
  **Bob mesti semak semula sebelum hidupkan** (harga dan model Vertex selalu berubah).
  Model tanpa harga tidak akan dipanggil.
- **Kunci service account dalam `.env` sebagai base64** (`GOOGLE_CREDENTIALS_BASE64`).
  App dihos di Forge, bukan GCP, jadi Secret Manager tidak terpakai; `.env` di Forge ialah
  tempat rahsia (peraturan 7). Fail kunci di luar repo juga disokong. Service account hanya
  ada peranan Vertex AI User.
- **Had:** `AI_MONTHLY_BUDGET_MYR` (RM300) dan `MAX_DAILY_AI_CALLS` (300) untuk semua
  website; satu artikel sehari setiap website; tulis semula maksimum 2 kali; gambar
  maksimum 2 cubaan. Anggaran kos dikira sebelum setiap panggilan (input ≈ aksara/3,
  output = had penuh).
- **Kegagalan:** job tidak diulang oleh queue (`tries = 1`). App cuba semula 3 jam kemudian
  dan tanda website "Ralat" selepas 3 kegagalan berturut-turut, dengan e-mel kepada Bob.
  Ralat billing Vertex (403 billing) tangguh **semua** website ("Tertunda (AI)") dan e-mel
  sekali; Bob tekan "Sambung semula semua" selepas betulkan.
- **Artikel yang sudah ditulis tetapi gagal diterbitkan** (contoh GitHub ralat) diterbitkan
  dahulu pada larian seterusnya, tanpa kos AI baru.
- **Website baru bermula "Berhenti".** Bob perlu Semak repo → Pasang blog → Aktifkan.
- **Template:** lalai dalam `resources/site-template/`, disalin ke `_dynoseo/` dalam repo
  website semasa pasang. Jika `_dynoseo/*.html` wujud, app guna versi repo itu (supaya blog
  ikut reka bentuk website). Jekyll GitHub Pages abaikan folder bermula `_`.
- **Laman utama** hanya diubah jika ada penanda `<!-- dynoseo:latest -->...<!-- /dynoseo:latest -->`.
  App tidak sesekali menambah penanda itu sendiri. `RepoGuard` semak setiap commit: fail
  hanya dalam `blog/`, `_dynoseo/`, `sitemap.xml`, `sitemap-pages.xml`, `robots.txt`,
  `index.html` (dalam penanda sahaja) dan fail kunci IndexNow.
- **Sitemap:** `blog/sitemap.xml` untuk artikel. Semasa pasang, `sitemap.xml` asal website
  dipindah ke `sitemap-pages.xml` dan `sitemap.xml` jadi sitemap index (jika sudah index,
  satu entri ditambah). Halaman senarai blog 2 dan seterusnya `noindex, follow`.
- **Google tidak lagi terima ping sitemap**; app hantar IndexNow (Bing/Yandex) sahaja.
  Google jumpa artikel melalui sitemap di Search Console.
- **Semakan kod (peraturan 2) sengaja ketat:** sebarang peratus, kod piawaian (JKR, MS,
  ISO, ASTM, SIRIM), akta/seksyen, dan "kajian/laporan menunjukkan" ditolak. Lebih baik
  tulis semula daripada terbit fakta rekaan.
- **Topik berulang (peraturan 8):** kata kunci dibandingkan selepas dinormalkan (huruf kecil,
  ASCII, tanpa kata hubung, perkataan disusun), jadi "gabion wall vs concrete wall" dan
  "Concrete Wall vs Gabion Wall" dikira sama.
- **Halaman Topik dicari dalam komponen** (`/website/{siteId}/topik`), bukan route model
  binding, supaya skop workspace sentiasa terpakai.
- **Penulis artikel** dipaparkan sebagai "Pasukan <Nama bisnes>" (schema `Organization`).

## 2026-10-11 — Website ujian pertama: dynolead.my

- **dynolead.my ialah app Laravel di Forge, bukan GitHub Pages.** Ditambah medan `docroot`
  pada website: kosong untuk GitHub Pages, `public` untuk app Laravel. Semua laluan web
  (blog, gambar, kunci IndexNow) dipetakan ke `public/`; template kekal di `_dynoseo/` pada
  akar repo. App tidak urus `sitemap.xml`/`robots.txt` untuk app Laravel (itu route).
- Dyno Lead dikemas kini supaya `robots.txt` benarkan `/blog/` dan sebut `/blog/sitemap.xml`,
  dan footer ada pautan Blog.

## 2026-10-11 — Domain sendiri

- **App di `dynoseo.my`** (Bob beli domain), menggantikan `seo.dynolead.my`. `www` dan
  `*.on-forge.com` dialih 301 ke `dynoseo.my` oleh `RedirectToCanonicalHost`.

## 2026-10-11 — Borang ringkas (maklum balas Bob)

- Bob: Soro hanya tanya apa bisnes kita; borang panjang buat pelanggan rasa lebih baik tulis
  sendiri. Borang kini 5 medan wajib (nama, apa bisnes ini, domain, repo, WhatsApp). Medan
  `niche` kini penerangan bisnes; AI kenal pasti bidang dan tulis artikel umum tentangnya.
- Hook kosong → hook standard dari template ("Berminat dengan <nama>?"), bukan AI (peraturan 1).
- Website baru: Simpan & mula = semak repo → pasang blog → aktifkan. Jika repo gagal,
  website kekal Berhenti dengan sebabnya. Butang langkah demi langkah kekal pada kad.
- Folder web `public` dikesan sendiri jika repo ada `artisan` dan `public/index.php`.

## 2026-10-11 — Halaman jualan dynoseo.my

- Laman utama `dynoseo.my/` ialah halaman jualan (Blade, `resources/views/marketing/home.blade.php`),
  gaya putih moden dengan aksen emas/biru gelap ikut poster Bob. Isi dari poster: masalah website
  "mati", 5 ciri, perbandingan Google Ads vs Dyno SEO, harga.
- **Harga dipaparkan RM49 sebulan** ikut poster Bob (sebelum ini RM59), dari
  `config('dynoseo.price_myr')` / `DYNOSEO_PRICE_MYR`, satu tempat sahaja.
- Fasa 0 tiada pendaftaran sendiri: semua butang "Mula" buka WhatsApp syarikat dengan mesej awal.
- Tidak menyalin dakwaan poster yang tidak boleh dibuktikan ("+RM3,450 jualan", "50% OFF hari ini");
  FAQ jujur: tiada jaminan nombor 1, SEO ambil 3–6 bulan. Google Ads RM2,000 dilabel "contoh bajet".
- `robots.txt` benarkan `/` sahaja; `sitemap.xml` untuk laman utama. Imej kongsi sosial
  `public/images/og-dynoseo.jpg` dipotong dari poster.

