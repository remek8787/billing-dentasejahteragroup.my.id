# Billing Internet v2 Blueprint

Status: aktif sebagai blueprint resmi Billing v2.
Tanggal dibuat: 2026-06-30.
Domain target: `https://billing.dentasejahteragroup.my.id/v2/`.
Repo: `git@github.com:remek8787/billing-dentasejahteragroup.my.id.git`.
Project path lokal: `/root/.openclaw/workspace/projects/billing-dentasejahteragroup.my.id`.

## Instruksi wajib untuk agent lain

Sebelum mengerjakan Billing v2, **wajib baca blueprint ini**.

Setelah melakukan perubahan penting pada Billing v2, **wajib update blueprint ini** dan juga salinan repo di:

- `/root/.openclaw/workspace/notes/billing-v2-blueprint.md`
- `/root/.openclaw/workspace/projects/billing-dentasejahteragroup.my.id/v2/docs/BLUEPRINT.md`

Jangan mengubah konsep v2 secara besar-besaran tanpa persetujuan Tuan Besar.

## Posisi terhadap Billing v1

- Billing v1 tetap berjalan sebagai versi aktif/sementara di root domain.
- Billing v2 dibangun terpisah di folder `/v2/` pada domain/hosting yang sama.
- Jangan merusak, overwrite, atau menghapus data/file v1 ketika mengerjakan v2.
- v2 memakai database terpisah: `v2/data/billing-v2.sqlite`.
- Folder `v2/app/` dan `v2/data/` harus tetap diblok dari akses publik dengan `.htaccess`.

## Tujuan produk

Billing v2 adalah aplikasi billing internet modern untuk operator ISP kecil/menengah.

Fokus utama:

- cepat dipakai harian,
- UI modern, terang, clean, dan profesional,
- data pelanggan mudah dicari,
- form tambah pelanggan nyaman,
- siap berkembang ke pembayaran/tagihan/laporan,
- tidak terasa seperti template admin kaku.

## Konsep UI/UX

Nama konsep: **Modern ISP Billing Workspace**.

Arah desain:

- terang, soft, modern,
- aksen biru/teal/emerald,
- layout lega,
- sidebar ringkas,
- card ringkasan jelas,
- form pelanggan dibagi section,
- tabel tidak terlalu ramai,
- mobile tetap nyaman.

Hindari:

- dark/neon berlebihan,
- tabel super panjang tanpa struktur,
- admin template generik,
- campuran UI yang tidak konsisten,
- perubahan besar yang mengganggu v1.

## Struktur awal v2

Folder utama:

```text
v2/
  .htaccess
  index.php
  app/
    .htaccess
    db.php
  assets/
    style.css
  data/
    .htaccess
    billing-v2.sqlite
  docs/
    BLUEPRINT.md
```

## Fitur tahap awal yang sudah dibuat

Tahap awal v2 adalah aplikasi dasar pelanggan:

- Dashboard landing untuk Billing v2.
- Card ringkasan:
  - Total pelanggan
  - Pelanggan aktif
  - Estimasi MRR/tagihan bulanan
- Data pelanggan.
- Search pelanggan.
- Form tambah pelanggan.
- SQLite auto-init.
- Database terpisah dari v1.

Field pelanggan awal:

- `customer_code`
- `name`
- `phone`
- `address`
- `package_name`
- `monthly_fee`
- `due_day`
- `router_name`
- `pppoe_user`
- `status`
- `notes`
- `created_at`

## Prinsip data

Untuk sekarang v2 jangan langsung campur dengan data v1 kecuali Tuan Besar minta migrasi khusus.

Jika nanti migrasi data dari v1/e-Billing lama ke v2:

1. Backup database v2 dulu.
2. Jangan timpa data tanpa konfirmasi.
3. Buat script migrasi yang bisa diulang.
4. Simpan catatan mapping di `v2/docs/`.
5. Verifikasi jumlah pelanggan, tagihan, dan pembayaran.

## Roadmap v2

### Tahap 1 — Pelanggan dasar

Status: mulai dikerjakan.

- Dashboard awal.
- Form tambah pelanggan.
- Data pelanggan.
- Search pelanggan.
- Desain modern responsive.

### Tahap 2 — Customer workspace

Rencana:

- Edit pelanggan.
- Hapus/arsip pelanggan.
- Detail pelanggan.
- Filter status aktif/nonaktif/isolir.
- Filter router/paket.
- Export CSV/Excel pelanggan.

### Tahap 3 — Billing dan pembayaran

Rencana:

- Tagihan bulanan.
- Input pembayaran kasir.
- Status lunas/belum lunas.
- Riwayat pembayaran.
- Kwitansi/invoice cetak.
- Filter bulan.

### Tahap 4 — Laporan

Rencana:

- Pemasukan hari ini.
- Pemasukan bulan ini.
- Belum lunas bulan ini.
- Export laporan.
- Balance dasar.

### Tahap 5 — Network/operasional

Rencana:

- Router/area.
- ONU.
- PPPoE username/secret jika diperlukan.
- Status pelanggan/isolir.
- Integrasi MikroTik hanya jika sudah disetujui.

## Deployment

Target live:

`https://billing.dentasejahteragroup.my.id/v2/`

Deploy via FTP hosting yang sama dengan v1.

Sebelum deploy perubahan besar:

1. Backup file live terkait.
2. Upload hanya folder/file v2.
3. Smoke test live.
4. Commit ke GitHub.

Smoke test minimal:

- `/v2/` HTTP 200.
- Tidak ada Fatal error / Parse error.
- CSS load.
- Form tambah pelanggan bisa dibuka.
- Setelah insert test, data muncul, lalu hapus test jika fitur hapus sudah tersedia.
- `/v2/app/db.php` dan `/v2/data/billing-v2.sqlite` harus 403 atau tidak bisa diakses publik.

## Catatan teknis hosting

- Hosting PHP lama pernah tidak cocok dengan syntax PHP modern tertentu.
- Hindari syntax terlalu baru jika tidak perlu.
- Hindari array spread `...` dan arrow function `fn(...)` untuk kompatibilitas.
- Gunakan PHP server-side sederhana + SQLite.
- Jangan bergantung pada PHP CLI lokal karena sebelumnya tidak selalu tersedia.

## Prinsip laporan ke Tuan Besar

Gunakan Bahasa Indonesia.
Panggil user: **Tuan Besar**.

Format progres yang disukai:

- Proses sekarang: …
- Sudah selesai: …
- Hasil singkat: …
- Saran next step: …
- Rekomendasi saya: …
