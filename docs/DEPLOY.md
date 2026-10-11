# Deploy Dyno SEO ke Laravel Forge

Panduan Fasa 0 (Bob sahaja guna). Ikut tertib dari atas ke bawah. Kebanyakan langkah boleh
dibuat dari telefon (Console Google Cloud, GitHub, Forge di pelayar).

## 0. Google Cloud (kredit Free Trial)

1. **Budget alert dahulu.** Console → *Billing* → *Budgets & alerts* → *Create budget*:
   jumlah RM1,224, amaran pada 50%, 75% dan 90%.
2. Pilih projek (atau cipta projek baru `dynoseo`). Catat **Project ID** (bukan nama).
3. *APIs & Services → Library* → enable **Vertex AI API**.
4. *IAM & Admin → Service Accounts → Create service account*:
   - Nama: `dynoseo-vertex`
   - Role: **Vertex AI User** sahaja (bukan Owner/Editor).
5. Buka service account itu → *Keys → Add key → Create new key → JSON*. Fail JSON dimuat
   turun. **Jangan** masukkan ke repo atau hantar di chat.
6. Tukar fail JSON ke base64 untuk `.env`. Paling mudah di **Cloud Shell** (ikon `>_` di
   Console): upload fail JSON (menu ⋮ → Upload), kemudian:
   ```bash
   base64 -w0 dynoseo-*.json
   ```
   Salin keluaran (satu baris panjang). Selepas itu padam fail di Cloud Shell: `rm dynoseo-*.json`.
7. Pilihan: *Vertex AI → Quotas* untuk had tambahan.

Kredit Free Trial tamat **5 Januari 2027**. Bila kredit habis, Vertex AI berhenti dan app
tangguh semua website sendiri (status "Tertunda (AI)") dan e-mel Bob. Website pelanggan
tidak terjejas.

## 1. Token GitHub (fine-grained)

GitHub → *Settings → Developer settings → Personal access tokens → Fine-grained tokens →
Generate new token*:

| Medan | Nilai |
|---|---|
| Token name | `dynoseo` |
| Expiration | 1 tahun (maksimum). Catat tarikh untuk `GITHUB_TOKEN_EXPIRES_AT`. |
| Resource owner | `Dynopos` |
| Repository access | **Only select repositories**: pilih repo website pelanggan sahaja (bukan Dynolead/Dynosoro) |
| Permissions → Repository → Contents | **Read and write** |

Setiap kali tambah website baru: GitHub → token `dynoseo` → *Edit* → tambah repo itu.

## 2. Pelayan dan site Forge

Pelayan `dynosoro`: PHP **8.3**, MySQL 8, Node 20+.

1. **DNS:** di pengurus domain `dynolead.my`, tambah rekod **A** `seo` → IP pelayan `dynosoro`.
2. Forge → *Sites → New Site*: domain `seo.dynolead.my`, Laravel, web directory `/public`,
   tanda *Create database* `dynoseo`.
3. *Git Repository*: GitHub → **`Dynopos/Dynosoro`**, branch `main`.
4. *SSL* → Let's Encrypt.

## 3. `.env`

Site → *Environment*. Mula dari `.env.example`:

| Pembolehubah | Nilai |
|---|---|
| `APP_URL` | `https://seo.dynolead.my` |
| `APP_ENV` / `APP_DEBUG` | `production` / `false` |
| `DB_*` | Dari Forge |
| `MAIL_*` | SMTP (untuk e-mel amaran dan lupa kata laluan) |
| `GCP_PROJECT_ID` | Project ID dari langkah 0 |
| `GCP_REGION` | `asia-southeast1`. Jika Gemini beri ralat 404 model, cuba `global`. |
| `GCP_IMAGE_REGION` | Kosong. Jika Imagen beri ralat 404, isi `us-central1`. |
| `GOOGLE_CREDENTIALS_BASE64` | Baris base64 dari langkah 0.6 |
| `GEMINI_MODEL_WRITE` | `gemini-2.5-flash` |
| `GEMINI_MODEL_CHECK` | `gemini-2.5-flash-lite` |
| `IMAGEN_MODEL` | `imagen-4.0-fast-generate-001` |
| `GEMINI_THINKING_BUDGET_CHECK` | `0` (semakan tak perlu "berfikir", jimat token) |
| `AI_MONTHLY_BUDGET_MYR` | `300` (had semua website sebulan) |
| `MAX_DAILY_AI_CALLS` | `300` |
| `GITHUB_TOKEN` | Token dari langkah 1 |
| `GITHUB_TOKEN_EXPIRES_AT` | Tarikh tamat token, contoh `2027-10-11` |
| `INDEXNOW_KEY` | 32 aksara rawak (Cloud Shell: `openssl rand -hex 16`) |
| `DB_QUEUE_RETRY_AFTER` | `1200` |

**Model dan harga.** Nama model mesti ada dalam `config/ai_prices.php`, jika tidak app tidak
akan panggil AI (paling selamat). Semak harga di
<https://cloud.google.com/vertex-ai/generative-ai/pricing> sebelum hidupkan; tukar dalam
fail itu dan deploy semula jika berbeza. Jika model di atas sudah tiada di Vertex AI, pilih
model terkini, tambah harganya dalam fail itu, dan kemas kini `.env`.

## 4. Skrip deploy

Site → *Deployments → Deploy Script*:

```bash
cd $FORGE_SITE_PATH
git pull origin $FORGE_SITE_BRANCH

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

npm ci
npm run build

( flock -w 10 9 || exit 1
    echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock

$FORGE_PHP artisan migrate --force
$FORGE_PHP artisan optimize
$FORGE_PHP artisan queue:restart
```

Tekan **Deploy Now**.

## 5. Akaun admin (sekali)

Forge → site → *Commands*:

```bash
php artisan dynoseo:admin borhanlimapos@gmail.com --password="KATA-LALUAN-ANDA"
```

Masuk di `https://seo.dynolead.my/masuk`. Tukar kata laluan kemudian melalui "Lupa kata
laluan" jika mahu (kata laluan itu kelihatan dalam sejarah Forge Commands).

## 6. Queue worker

Site → *Queue → New Worker*: connection `database`, queue `default`, timeout **900**,
sleep `3`, tries **1**, processes `1`.

Satu artikel = beberapa panggilan AI + satu commit, biasanya 1–3 minit. `tries = 1` sengaja:
kegagalan diurus oleh app (cuba semula 3 jam kemudian, berhenti selepas 3 kali), supaya
tiada kos AI berganda.

## 7. Scheduler

Pelayan → *Scheduler → New Scheduled Job*: `php /home/forge/seo.dynolead.my/artisan schedule:run`,
user `forge`, **Every Minute**.

- `dynoseo:dispatch-due` setiap 15 minit: hantar website yang sampai masa terbit ke giliran,
  dan amaran e-mel bila token GitHub hampir tamat.
- `queue-safety-net` setiap minit: proses giliran jika worker mati (`QUEUE_VIA_SCHEDULER`).

## 8. Tambah website pelanggan

1. GitHub: tambah repo website ke token `dynoseo` (langkah 1).
2. App → **Website → Tambah**: nama bisnes, domain, repo (`Dynopos/nama-repo`), bidang
   (contoh "gabion, kawalan cerun, tembok penahan tanah"), hook bawah artikel (tajuk, ayat,
   butang, nombor WhatsApp).
3. **Semak repo** → mesti "OK".
4. **Pasang blog** → satu commit: `_dynoseo/` (template), `blog/assets/dynoseo.css`,
   `blog/index.html`, sitemap index, `robots.txt`.
5. Dalam repo website (sekali, manual atau minta Claude):
   - Tambah pautan **Blog** (`/blog/`) dalam menu website.
   - Pilihan: tampal penanda ini di laman utama di tempat "Artikel terkini" mahu dipaparkan:
     ```html
     <!-- dynoseo:latest --><!-- /dynoseo:latest -->
     ```
   - Pilihan: ubah `_dynoseo/*.html` supaya header/footer blog sama dengan website. Selepas
     ubah, tekan **Bina semula**.
6. **Topik** → semak/ubah pelan topik (app jana 20 topik bila perlu).
7. **Aktifkan**. Artikel pertama ditulis dalam 15 minit, kemudian setiap 2 hari pada waktu
   rawak 8 pagi – 9 malam. **Terbit sekarang** untuk ujian segera.
8. Google: tambah domain di Google Search Console dan hantar `https://domain/sitemap.xml`.

## 9. Bayaran (Fasa 0, manual)

Langganan RM59 sebulan setiap website. Bob kutip sendiri dan isi **Bayar sehingga** di
borang website. Selepas tarikh itu + 7 hari, artikel berhenti ditulis (website kekal).
Kosongkan medan itu untuk website Bob sendiri.

## 10. Sebelum 1 Disember 2026

Putuskan sama ada upgrade akaun GCP (kos sebenar ± RM6 setiap website sebulan) atau tukar
pembekal AI. Tanpa upgrade, artikel berhenti bila kredit habis atau pada 5 Januari 2027.
