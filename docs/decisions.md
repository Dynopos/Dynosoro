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


## 2026-10-11 — Halaman jualan ditangguh

- Bob mahu reka bentuk halaman jualan dengan ChatGPT dahulu. Draf pertama (RM49/bulan, butang
  WhatsApp, FAQ, schema) diberi sebagai satu fail HTML dan dikeluarkan dari app (commit revert).
  Laman utama kekal alih ke log masuk sehingga reka bentuk baru dipasang.

## 2026-10-11 — Halaman jualan dipasang (design Bob)

- Laman utama `dynoseo.my/` guna design ChatGPT Bob (biru/navy) dengan isi tambahan dan logo
  DYNO SEO baru. Harga: website RM200 sekali bayar (`WEBSITE_PRICE_MYR`) + DYNO SEO RM49/bulan
  (`DYNOSEO_PRICE_MYR`). Semua butang ke WhatsApp `COMPANY_WHATSAPP` (018-288 9932), bukan e-mel.
- Kedudukan Bob: DYNO SEO untuk website yang dibina oleh pihak kami; pelanggan dengan website
  sendiri diminta WhatsApp untuk semakan.

## 2026-10-12 — Video demo dan timeline hasil

- Video demo menegak (720×1280, ±3.5 MB) dalam `public/videos/`, `preload="none"` supaya halaman
  kekal laju; poster JPG dimuat dahulu. `robots.txt` benarkan `/videos/` supaya Google boleh baca video.
- Timeline hasil ikut Bob: Minggu 1 website aktif, Minggu 2–4 tersebar dalam carian, Minggu 5–8 mula
  muncul di Google/ChatGPT/AI, Minggu 9–12 SEO mula berkesan. "5–8" (bukan "4–8") supaya tidak
  bertindih. Nota "tempoh berbeza ikut bidang dan persaingan" dikekalkan; kedudukan tidak dijamin.
- Harga dalam video (RM200, RM49, RM249) ialah imej, bukan dari config. Jika harga berubah, video
  perlu dijana semula.

## 2026-10-12 — Harga pengenalan

- Arahan Bob: RM49 ialah harga pengenalan untuk 100 pelanggan pertama, harga akan naik. Ditulis
  sebagai teks sahaja (`DYNOSEO_INTRO_SLOTS=100`; 0 sembunyikan semua nota). Tiada kaunter "baki
  slot" kerana app belum ada data pelanggan berbayar; kaunter palsu mengelirukan.
- Halaman tidak sebut harga baru, dan tidak janji pelanggan awal kekal RM49. Bob perlu putuskan
  kedua-duanya sebelum ia ditulis.


## 2026-10-17 — Analisis bisnes wajib disahkan sebelum topik dirancang

- Kes skylift → oksigen: satu baris `niche` tidak cukup untuk AI memahami bidang. Pilihan paling
  selamat: AI baca website sebenar, admin sahkan, dan perancangan topik dikunci sehingga itu.
- Website sedia ada tanpa analisis disahkan **berhenti menerbit** selepas deploy (termasuk topik
  dan draf lama, yang mungkin sudah tersasar). Kos: Bob perlu tekan "Analisis bisnes" dan
  "Betul, sahkan" sekali bagi setiap website. Dianggap lebih murah daripada artikel memalukan.
- Analisis guna `GEMINI_MODEL_WRITE` (satu panggilan setiap website, jarang) kerana kefahaman
  bidang lebih penting daripada kos. `GitHubClient` hanya dibaca (`getFile`) oleh `SiteAnalyst`.
- Padanan kluster dalam kod longgar (normalisasi `Topic::keyFor`, padanan sebahagian) supaya
  variasi kecil nama kluster dari AI tidak membuang topik yang betul.
