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
