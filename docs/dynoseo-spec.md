# Dyno SEO — Spec v0.1

Status: draf untuk Bob semak (11 Oktober 2026). Belum ada kod.

## 1. Masalah dan matlamat

Bob jual website RM200 kepada SME (HTML statik, dihos di GitHub Pages, repo dalam akaun
GitHub Bob). Selepas siap, kebanyakan website "mati": tiada kandungan baru, Google jarang
datang semula, dan website tidak muncul dalam carian.

Dyno SEO terbitkan **artikel umum ikut bidang** website itu secara automatik **setiap 2 hari**,
lengkap dengan gambar AI, dan selitkan **hook ke bisnes pelanggan** di bawah setiap artikel.
Website sentiasa hidup, Google nampak website itu pakar dalam bidangnya (topical authority),
dan pembaca dari Google ada jalan untuk jadi pelanggan.

Contoh: website pemasang gabion menerbitkan "Teknologi kawalan cerun terkini", "Gabion wall
vs concrete wall: mana lebih sesuai untuk cerun rumah?", dan di bawahnya kotak "Cerun rumah
anda mula runtuh? WhatsApp kami untuk lawatan tapak."

Ini menggantikan Soro AI yang Bob guna sekarang, dan dijual sebagai tambahan bulanan kepada
pakej website RM200.

### Kelebihan berbanding Soro (yang Bob perhatikan)

| | Soro | Dyno SEO |
|---|---|---|
| URL artikel | `?post=...` (query string) | `/blog/<slug>/` statik, mesra SEO |
| Gambar | AI generik, berulang (orang dan kaunter sama) | Satu gambar AI berbeza setiap artikel, ikut topik, suasana Malaysia |
| Topik | Bertindih | Pelan topik + semakan pertindihan dalam kod |
| Bahasa | BM umum | BM santai Malaysia |
| Fakta teknikal | Tidak disemak | Semakan kedua: tiada angka, piawaian atau statistik rekaan |

### Bukan matlamat (v0.1)

- WordPress atau platform lain (Fasa 3, jika perlu).
- Artikel tentang bisnes pelanggan itu sendiri (harga, produk, promosi). Artikel sentiasa
  umum ikut bidang; bisnes hanya muncul dalam kotak hook.
- Langkah lulus sebelum terbit. Artikel terus terbit (keputusan Bob); keselamatan dalam kod.
- Backlink, media sosial, iklan.

## 2. Fasa

| Fasa | Isi | Siapa guna |
|---|---|---|
| **0** | Enjin: daftar website, pelan topik, tulis artikel + gambar, semakan, terbit ke GitHub setiap 2 hari, hook bawah artikel, skrin admin, had kos | Bob sahaja |
| **1** | Portal pelanggan: lihat artikel, edit/padam, tulis artikel sendiri (+ "Kemaskan dengan AI"), suis berhenti, ubah hook | Pelanggan |
| **2** | Langganan RM59/bulan (CHIP), Search Console (topik dari carian sebenar + laporan bulanan) | Pelanggan |
| **3** | Pilihan: WordPress, jual kepada agensi lain | — |

Bina ikut fasa. Fasa seterusnya hanya bermula atas arahan Bob.

## 3. Skrin Fasa 0 (admin Bob)

### 3.1 Website (`/website`)

Senarai website: nama, domain, status (Aktif / Berhenti / Ralat), artikel terakhir, artikel
seterusnya, bilangan artikel. Butang **Tambah website**:

| Medan | Contoh | Wajib |
|---|---|---|
| Nama bisnes | Gabion Pantai Timur | Ya |
| Domain | gabionpantaitimur.my | Ya |
| Repo GitHub | `Dynopos/gabion-pantai-timur` | Ya |
| Branch | `main` | Ya (lalai `main`) |
| Bidang | gabion, kawalan cerun, tembok penahan tanah | Ya |
| Pembaca sasaran | pemilik rumah lereng bukit, kontraktor kecil | Pilihan |
| Kawasan | Kelantan, Terengganu | Pilihan |
| Topik dilarang | harga, nama pesaing | Pilihan |
| Kekerapan | setiap 2 hari | Ya (lalai 2) |
| Hook bawah artikel | lihat §3.2 | Ya |

Bila disimpan, app semak akses ke repo dan baca template (§7.2). Jika template tiada, app
tunjuk arahan pasang (§7.3).

### 3.2 Hook bawah artikel (wajib, setiap website)

Kotak di bawah setiap artikel, macam kotak "Nak kedai anda lebih sistematik?" di dynopos.my:

| Medan | Contoh |
|---|---|
| Tajuk | Cerun rumah anda mula runtuh? |
| Ayat | WhatsApp kami untuk lawatan tapak dan cadangan percuma. |
| Teks butang | 💬 WhatsApp Kami |
| Nombor WhatsApp | 0123456789 (ditukar ke `60123456789` untuk `wa.me`) |
| Mesej awal | Salam, saya baca artikel "{tajuk}" di website anda. Nak tanya... |
| Pautan alternatif | `/hubungi/` (jika tiada WhatsApp) |

- `{tajuk}` diganti dengan tajuk artikel, supaya Bob/pelanggan tahu artikel mana yang bawa lead.
- Hook ialah HTML dari template (bukan tulisan AI), jadi tiada risiko AI reka janji.
- Pilihan "Hook ikut topik" (lalai OFF): AI tulis satu ayat penyambung dari topik artikel ke
  hook, contoh "Jika cerun di belakang rumah anda menunjukkan tanda di atas, ...". Ayat ini
  disemak (§5.3): tiada harga, janji atau tawaran.

### 3.3 Artikel (`/artikel`)

Senarai semua artikel (semua website): tajuk, website, status, tarikh terbit, kos. Tindakan:
**Lihat** (pautan live), **Edit** (tajuk, isi, gambar), **Padam** (buang dari repo),
**Jana semula gambar**, **Terbit sekarang** (untuk artikel `ready`).

### 3.4 Topik (`/website/{id}/topik`)

Pelan topik setiap website: senarai topik akan datang (boleh susun, edit, padam, tambah
manual). App isi semula pelan bila tinggal kurang 5 topik.

### 3.5 Kos (`/kos`)

Bulan ini: panggilan AI, token, gambar, anggaran RM, baki had bulanan, kos setiap website,
kos purata setiap artikel. Amaran pada 80% had.

## 4. Aliran kerja (pipeline)

Scheduler setiap 15 minit cari website yang `next_publish_at <= now` dan status Aktif.
Setiap website diproses oleh satu job (`PublishNextArticle`) dalam giliran:

1. **Ambil topik** paling atas dari pelan. Jika pelan < 5 topik, jalankan `PlanTopics` dulu.
2. **Tulis** (`write.md`, `GEMINI_MODEL_WRITE`): JSON dengan tajuk, slug, meta description,
   ringkasan, isi HTML (H2/H3, senarai), 3–5 FAQ, prompt gambar, alt gambar.
3. **Semak dengan kod** (§5.4). Gagal → tulis semula (maksimum 2 kali).
4. **Semak dengan AI** (`check.md`, `GEMINI_MODEL_CHECK`, model murah): fakta rekaan,
   pertindihan, nada. Gagal → tulis semula dengan nota semakan (maksimum 2 kali, kongsi
   kiraan dengan langkah 3).
5. **Gambar** (Imagen, `IMAGEN_MODEL`): 16:9, ditukar ke WebP 1200×675, < 200 KB.
   Gagal → cuba sekali lagi; masih gagal → guna gambar sandaran website (jika ada) atau
   terbit tanpa gambar dan tanda untuk Jana semula gambar.
6. **Bina HTML** dari template website (§7.2): artikel + hook + schema + meta.
7. **Kemas kini** `blog/index.html`, blok "Artikel terkini" di laman utama, `blog/sitemap.xml`,
   `blog/feed.xml`.
8. **Commit** semua fail dalam satu commit ke repo (GitHub Git Data API). GitHub Pages terbit.
9. **IndexNow** (Bing/Yandex) dihantar. (Google tidak lagi terima ping sitemap; Google jumpa
   artikel melalui sitemap dan Search Console, Fasa 2.)
10. Tetapkan `next_publish_at` = sekarang + kekerapan, pada waktu rawak 8 pagi – 9 malam,
    supaya website-website tidak terbit serentak.

Mana-mana langkah gagal 3 kali berturut-turut → status website "Ralat", berhenti, Bob
dimaklumkan (e-mel / notis admin). Tiada loop tanpa had.

## 5. Prompt

Semua dalam `resources/prompts/*.md` dengan `prompt_version`. Bahasa Melayu santai Malaysia,
bukan Bahasa Indonesia.

### 5.1 `plan-topics.md` (model murah)

Input: bidang, pembaca sasaran, kawasan, topik dilarang, senarai tajuk lama (maksimum 200).
Output: 20 topik JSON `{title, target_keyword, intent, cluster}`. Campuran: panduan (cara),
perbandingan (X vs Y), senarai (tips, kesilapan), penerangan (apa itu), musim/tempatan.

### 5.2 `write.md` (model tulis)

Peraturan dalam prompt (dan disemak dalam kod):
- 800–1,200 patah perkataan, satu H1 (tajuk), H2/H3 tersusun, perenggan pendek (mudah baca di telefon).
- Artikel **umum ikut bidang**. Jangan sebut nama bisnes, harga, promosi atau janji.
- Jangan reka angka tepat, statistik, kajian, piawaian (contoh JKR, MS, ISO), nama produk,
  undang-undang atau petikan. Jika perlu, guna ayat umum ("ikut spesifikasi jurutera").
- Topik teknikal/keselamatan (struktur, kesihatan, kewangan): tambah nota "rujuk pakar bertauliah".
- Tiada pautan luar. Pautan dalaman hanya ke artikel lama website itu (senarai diberi).

### 5.3 `check.md` (model murah)

Output JSON `{pass, issues[]}`. Gagal jika: fakta/angka/piawaian yang meragukan dinyatakan
sebagai pasti; sebut harga, jenama atau nama bisnes; isi terlalu mirip artikel lama; bahasa
Indonesia; nada spam (keyword stuffing).

### 5.4 Semakan kod (bukan AI)

- Bilangan perkataan dalam julat; ada H2; meta description ≤ 155 aksara; slug unik.
- HTML dibersihkan dengan senarai tag dibenarkan (`h2 h3 p ul ol li strong em a blockquote
  table thead tbody tr th td`). Tiada `script`, `style`, `iframe`, atribut `on*`.
- Tiada `RM` + nombor, nombor telefon, URL luar.
- Tajuk/keyword sasaran tidak sama dengan artikel yang sudah terbit (dinormalkan).

### 5.5 `image.md`

Gambar foto realistik ikut topik. Tiada teks, logo, jenama atau tanda air. Suasana Malaysia
(cuaca tropika, rumah/jalan/kedai tempatan). Jika ada orang: pakaian dan rupa tempatan,
pelbagai; elak wajah jelas sebagai fokus. Setiap gambar mesti berbeza dari 5 gambar terakhir
website itu (prompt diberi ringkasan gambar lama).

## 6. Data

### Jadual

| Jadual | Lajur utama |
|---|---|
| `workspaces` | pelanggan (Fasa 1); Fasa 0 satu workspace `dalaman` |
| `sites` | workspace_id, name, domain, github_repo, branch, niche, audience, area, banned_topics, frequency_days, status, next_publish_at, paid_until, cta (json), template_sha |
| `topics` | site_id, title, target_keyword, intent, cluster, position, status (planned/used/skipped) |
| `articles` | site_id, topic_id, title, slug, meta_description, summary, body_html, faq (json), image_path, image_alt, status (writing/failed/ready/published/deleted), source (ai/customer), published_at, commit_sha, prompt_version |
| `ai_usage` | site_id, article_id, kind (plan/write/check/image), model, input_tokens, output_tokens, images, cost_usd |
| `publish_logs` | site_id, article_id, action (publish/update/delete), commit_sha, error |

Semua model data guna `BelongsToWorkspace` (sama seperti Dyno Lead) supaya Fasa 1 terasing
dari awal.

Kandungan artikel disimpan dalam DB **dan** repo. DB ialah sumber kebenaran; repo dibina
semula dari DB jika perlu (`dynoseo:rebuild {site}`).

## 7. Integrasi GitHub Pages

### 7.1 Akses

Semua repo website dalam akaun GitHub Bob. Fasa 0: satu **fine-grained personal access
token** (Contents: read & write, hanya repo website yang dipilih), dalam `.env`
(`GITHUB_TOKEN`). Token tamat maksimum 1 tahun; app beri amaran 14 hari sebelum tamat
(`GITHUB_TOKEN_EXPIRES_AT`). Kemudian boleh tukar ke GitHub App.

### 7.2 Template dalam repo website

Setiap repo website ada folder `_dynoseo/` (dipasang sekali, ikut reka bentuk website itu):

| Fail | Isi |
|---|---|
| `_dynoseo/article.html` | Layout artikel website (header, footer, CSS sama) dengan placeholder `{{title}} {{meta_description}} {{canonical}} {{og_image}} {{date}} {{image}} {{body}} {{faq}} {{cta}} {{related}} {{schema}}` |
| `_dynoseo/index.html` | Layout senarai blog dengan `{{cards}}` dan `{{pagination}}` |
| `_dynoseo/card.html` | Satu kad (gambar, tajuk, ringkasan, tarikh), macam kad Soro |
| `_dynoseo/cta.html` | Kotak hook dengan `{{cta_title}} {{cta_text}} {{cta_button}} {{cta_url}}` |
| `_dynoseo/config.json` | (pilihan) bilangan kad laman utama, warna |

Laman utama: blok antara `<!-- dynoseo:latest -->` dan `<!-- /dynoseo:latest -->` diganti
dengan 3 kad terbaru (HTML statik, dibaca Google tanpa JavaScript).

### 7.3 Fail yang ditulis

```
blog/index.html                  senarai (12 sehalaman, blog/page/2/ ...)
blog/<slug>/index.html           artikel
blog/img/<slug>.webp             gambar
blog/sitemap.xml                 semua artikel
blog/feed.xml                    RSS
sitemap.xml                      dijadikan sitemap index (sekali, semasa pasang) → sitemap-pages.xml + blog/sitemap.xml
robots.txt                       pastikan ada baris Sitemap
index.html                       blok dynoseo:latest sahaja
```

App hanya ubah fail di atas. Fail lain dalam repo tidak disentuh.

### 7.4 SEO setiap artikel

`<title>`, meta description, canonical, Open Graph + Twitter card, schema `BlogPosting`
(+ `BreadcrumbList`, `FAQPage`), `<img>` dengan `alt`, `width`, `height`, `loading="lazy"`
(kecuali gambar utama), 3 pautan "Artikel berkaitan" dalam website yang sama, tarikh terbit
dan dikemas kini. Penulis: "Pasukan <Nama bisnes>".

## 8. AI dan kos

### 8.1 Pembekal

Google **Vertex AI** (Gemini untuk teks, Imagen untuk gambar), dibayar dengan kredit Free
Trial GCP Bob (~RM1,224, tamat **5 Januari 2027**). Region `asia-southeast1` jika model
tersedia, jika tidak `us-central1`. Auth: service account dengan peranan `Vertex AI User`
sahaja; fail kunci JSON di pelayan, di luar repo (`GOOGLE_APPLICATION_CREDENTIALS`).
Semua panggilan melalui `VertexClient` (satu class, senang di-mock).

### 8.2 Pembolehubah `.env`

```
GCP_PROJECT_ID=
GCP_REGION=asia-southeast1
GOOGLE_APPLICATION_CREDENTIALS=
GEMINI_MODEL_WRITE=
GEMINI_MODEL_CHECK=
IMAGEN_MODEL=
AI_MONTHLY_BUDGET_MYR=300
MAX_DAILY_AI_CALLS=300
MAX_ARTICLES_PER_SITE_PER_DAY=1
GITHUB_TOKEN=
GITHUB_TOKEN_EXPIRES_AT=
INDEXNOW_KEY=
```

Nama model tidak ditulis dalam kod. Harga dalam `config/ai_prices.php` sahaja; selagi harga
kosong, app tidak panggil AI (sama seperti Dyno Lead).

### 8.3 Anggaran kos (kasar, semak harga semasa sebelum bina)

| Item | Anggaran |
|---|---|
| Tulis + semak + pelan topik | ± RM0.05–0.20 setiap artikel |
| Gambar Imagen | ± RM0.20 setiap gambar |
| **Satu artikel** | **± RM0.40** |
| Satu website (15 artikel/bulan) | ± RM6/bulan |
| RM1,000 kredit | ± 2,500 artikel ≈ 160 website-bulan |

GitHub Pages dan IndexNow percuma.

### 8.4 Had (dalam kod, setiap satu ada test)

- `AiBudget::assertCanSpend()` sebelum setiap panggilan Vertex. Had bulanan tercapai → semua
  kerja AI berhenti, website kekal "Aktif tertunda", admin dimaklumkan.
- `MAX_DAILY_AI_CALLS` global, `MAX_ARTICLES_PER_SITE_PER_DAY` setiap website.
- Tulis semula maksimum 2 kali setiap artikel; gambar maksimum 2 cubaan.
- Website `paid_until` sudah lepas (tolak tempoh tangguh 7 hari) → tidak dijana.

### 8.5 Selepas kredit tamat

Bila kredit habis atau 5 Januari 2027, akaun Free Trial berhenti dan Vertex AI gagal. App
mesti: kesan ralat billing/quota, hentikan semua website dengan status "Tertunda (AI)",
dan maklumkan admin sekali. Website pelanggan tidak terjejas (fail statik kekal).
Sebelum 1 Disember 2026, Bob putuskan: upgrade akaun GCP (kos sebenar ± RM6/website/bulan)
atau tukar pembekal.

## 9. Peraturan yang tidak boleh dilanggar

1. **Artikel umum sahaja.** AI tidak menulis tentang bisnes pelanggan, harga, promosi atau
   janji. Bisnes hanya muncul dalam hook dari template.
2. **Tiada fakta rekaan.** Angka, statistik, piawaian, kajian dan undang-undang tidak
   dinyatakan sebagai fakta pasti tanpa sumber. Semakan kod + semakan AI sebelum terbit.
3. **Setiap artikel ada hook** ke bisnes pelanggan (§3.2).
4. **Had kos dikuatkuasa sebelum setiap panggilan AI.** Tiada loop tanpa had.
5. **App hanya ubah fail Dyno SEO** dalam repo website (§7.3). HTML artikel dibersihkan.
6. **Data setiap pelanggan terasing** (`BelongsToWorkspace`), dari Fasa 0.
7. **Tiada rahsia dalam repo.** Token GitHub, kunci GCP dan lain-lain dalam `.env`.
8. **Tiada topik berulang** dalam website yang sama (semakan kod).

## 10. Kriteria siap Fasa 0

- [ ] Bob tambah satu website (contoh website gabion atau dynopos.my), app semak repo dan template.
- [ ] Pelan 20 topik dijana; Bob boleh edit.
- [ ] "Terbit sekarang" hasilkan artikel + gambar + hook, live di `/blog/<slug>/` dalam 5 minit.
- [ ] Blog index, blok laman utama, sitemap dan RSS dikemas kini dalam commit yang sama.
- [ ] Scheduler terbit setiap 2 hari tanpa campur tangan, selama 1 minggu.
- [ ] Edit dan padam artikel dari admin dikemas kini di repo.
- [ ] Skrin Kos tunjuk kos sebenar; had bulanan dan harian berfungsi.
- [ ] Ralat GitHub / Vertex tidak buat loop; website ditanda Ralat dan Bob dimaklumkan.
- [ ] `php artisan test` lulus; tiada test panggil API sebenar.

## 11. Keputusan Bob (11 Oktober 2026)

1. **Domain app: `dynoseo.my`** (domain sendiri, dibeli Bob 11 Oktober 2026; asalnya `seo.dynolead.my`). Artikel
   tetap tinggal dalam website pelanggan; subdomain ini hanya untuk panel admin Bob
   (Fasa 0) dan portal pelanggan (Fasa 1).
2. **Harga: langganan RM59 sebulan** setiap website (kos AI ± RM6/bulan). Fasa 0: Bob kutip
   manual dan isi `paid_until`. Fasa 2: langganan automatik melalui CHIP.
3. **Pelayan: pelayan Forge baru**, berasingan dari Dyno Lead.
4. **dynopos.my kekal guna Soro.** Dyno SEO tidak menyentuh repo atau artikel dynopos.my.

## 12. Soalan terbuka

1. **Website pertama untuk ujian**: repo GitHub yang mana? (bukan dynopos.my)
