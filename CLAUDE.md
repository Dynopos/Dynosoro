# CLAUDE.md — Dyno SEO

Arahan untuk Claude Code yang bekerja dalam repo ini. Baca fail ini dan
`docs/dynoseo-spec.md` sebelum mula apa-apa kerja.

## Apa app ini

Dyno SEO (repo `dynosoro`) ialah enjin blog automatik untuk website pelanggan Bob
(pakej website RM200, HTML statik di GitHub Pages, repo dalam akaun GitHub Bob). Sebahagian
ekosistem DYNOPRO (DynoPOS Technologies, Pasir Mas). Ganti Soro AI. App ini:

1. rancang topik **umum ikut bidang** setiap website (contoh website gabion: cerun,
   tembok penahan, gabion vs konkrit),
2. tulis artikel BM + jana gambar AI (Vertex AI: Gemini + Imagen),
3. semak artikel (kod dahulu, kemudian AI murah),
4. terbit terus ke repo GitHub website itu **setiap 2 hari**, dengan **hook ke bisnes
   pelanggan** di bawah setiap artikel,
5. (Fasa 1) pelanggan boleh edit/padam artikel dan tulis artikel sendiri.

Pemilik produk: Bob (Borhan Sidqy). Fasa 0 untuk Bob (admin) sahaja.

## Stack

- Laravel 11 (atau 12), Livewire 3, Blade, Tailwind, MySQL, Pest
- Queue: database driver. Scheduler: `php artisan schedule:run` melalui cron Forge
- Hosting app: Laravel Forge. Website pelanggan: GitHub Pages (bukan app ini)
- AI: Google Vertex AI (Gemini teks, Imagen gambar), dibayar kredit GCP Free Trial
  (tamat 5 Januari 2027). Nama model dari `.env`, jangan tulis dalam kod.
- GitHub: REST API (Git Data API untuk satu commit berbilang fail)

## Peraturan yang tidak boleh dilanggar

Ditulis dalam kod, bukan diharap AI ingat. Setiap satu perlukan test.

1. **Artikel umum sahaja.** AI tidak tulis tentang bisnes pelanggan, harga, promosi atau
   janji. Bisnes hanya muncul dalam hook dari template.
2. **Tiada fakta rekaan.** Angka, statistik, piawaian, kajian, undang-undang tidak
   dinyatakan sebagai fakta pasti. Semakan kod + semakan AI sebelum terbit.
3. **Setiap artikel ada hook** ke bisnes pelanggan.
4. **Had kos AI dikuatkuasa sebelum setiap panggilan.** `AiBudget::assertCanSpend()` sebelum
   setiap panggilan Vertex. Had bulanan/harian; tulis semula maksimum 2 kali. Tiada loop tanpa had.
5. **App hanya ubah fail Dyno SEO** dalam repo website (spec §7.3). HTML artikel dibersihkan
   (senarai tag dibenarkan).
6. **Data setiap pelanggan terasing.** Model data guna `BelongsToWorkspace` dari Fasa 0.
7. **Tiada rahsia dalam repo.** Token GitHub, kunci service account GCP dalam `.env` /
   di luar repo. `.env.example` tunjuk nama pembolehubah sahaja.
8. **Tiada topik berulang** dalam website yang sama.

## Kawalan kos

- Model murah (`GEMINI_MODEL_CHECK`) untuk pelan topik dan semakan; model tulis
  (`GEMINI_MODEL_WRITE`) untuk artikel sahaja.
- Satu gambar setiap artikel. Imagen hanya selepas teks lulus semakan.
- Setiap panggilan direkod dalam `ai_usage` (token, gambar, anggaran kos).
- Harga dalam `config/ai_prices.php` sahaja; selagi harga kosong, app tidak panggil AI.
- Google Search grounding: OFF secara default.
- Bila kredit GCP habis / akaun Free Trial berhenti: kesan ralat, hentikan semua website
  ("Tertunda (AI)"), maklumkan admin sekali. Jangan cuba semula berulang kali.

## Konvensyen

- Bahasa UI dan artikel: Bahasa Melayu santai Malaysia (bukan Bahasa Indonesia).
  Kod, nama jadual dan komen: English.
- Logik dalam service classes (`app/Services/...`), bukan dalam komponen Livewire.
- Semua panggilan luar melalui satu client class setiap satu (`VertexClient`,
  `GitHubClient`, `IndexNowClient`) supaya senang di-mock.
- Prompt dalam `resources/prompts/*.md` dengan `prompt_version`.
- Nombor WhatsApp hook: `01x...` ditukar ke `601x...` untuk `wa.me`.

## Test

- Pest. `php artisan test` mesti lulus sebelum commit.
- Test tidak boleh panggil API sebenar. Guna `Http::fake()`.
- Setiap peraturan di atas ada sekurang-kurangnya satu test.

## Cara kerja

- Bina ikut fasa dalam spec. Jangan mula fasa seterusnya tanpa arahan Bob.
- Commit kecil dengan mesej jelas. Kemas kini `docs/CHANGELOG.md` setiap fasa.
- Bila spec tidak jelas, pilih pilihan paling selamat dan paling murah, kemudian catat
  dalam `docs/decisions.md`.
- Peringatkan Bob jika kerja dibuat selepas 1 Disember 2026 (kredit GCP tamat 5 Jan 2027).
