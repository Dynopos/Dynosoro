# Changelog

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

## Spec v0.1 — 2026-10-11

- Spec pertama Dyno SEO (`docs/dynoseo-spec.md`), `CLAUDE.md`, `docs/decisions.md`.
- Keputusan Bob: `seo.dynolead.my`, langganan RM59/bulan, pelayan Forge baru, dynopos.my kekal Soro.
- Belum ada kod. Fasa 0 bermula selepas Bob beri arahan.
