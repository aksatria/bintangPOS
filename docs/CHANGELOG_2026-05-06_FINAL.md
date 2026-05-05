# Changelog Final - 6 Mei 2026

Commit referensi: `3ddb559`  
Scope: Finalisasi modul Supplier + Akuntansi ERP

## Ringkasan

Rilis ini menyelesaikan paket final end-to-end untuk pembelian supplier, approval owner, dan akuntansi otomatis, sekaligus merapikan konsistensi UI lintas halaman akuntansi.

## Perubahan Utama

### 1) Pembelian Supplier End-to-End

- Draft pembelian supplier (input manual multi-item).
- Edit draft pembelian.
- Duplicate draft pembelian.
- Cancel draft pembelian.
- Ajukan persetujuan (approval request) untuk kasus berisiko.
- Penerimaan barang penuh.
- Penerimaan barang parsial.
- Retur pembelian.
- Pembayaran hutang supplier (partial/full).
- Upload bukti bayar.
- Rekonsiliasi hutang dan status bayar.

### 2) Approval Flow

- Integrasi approval untuk pembelian supplier di pusat approval.
- Status approval tampil pada daftar dan detail pembelian.
- Riwayat approval mencakup requester/reviewer/catatan.
- Dukungan approve/reject/assign/snooze pada approval center.

### 3) Akuntansi Inti

- Halaman baru:
  - `GET /admin/accounting/reports` (Laporan Akuntansi)
  - `GET /admin/accounting/journals` (Jurnal Umum)
  - `GET /admin/accounting/ledger` (Buku Besar)
- Posting jurnal otomatis untuk event utama:
  - pembelian supplier
  - pembayaran supplier
  - retur pembelian
  - transaksi POS
- Entitas akuntansi:
  - `Account`
  - `JournalEntry`
  - `JournalLine`
- Service:
  - `AccountingService`

### 4) Database & Struktur Data

- Penambahan migration untuk:
  - field dokumen pembelian supplier
  - tabel lampiran pembelian supplier
  - reference number pada pembayaran supplier
  - relasi bukti pembayaran-lampiran
  - tracking receive/return per item pembelian
  - tabel retur pembelian supplier
  - field rekonsiliasi pembelian
  - field landed cost/ongkir treatment
  - core table akuntansi
  - ekstensi akun akuntansi untuk penjualan

### 5) UI/UX Konsistensi

- Topbar diperhalus (spacing dan alignment lebih presisi).
- Gaya panel/card/table/button dirapikan agar konsisten.
- Variasi warna navigasi akuntansi:
  - Laporan Akuntansi (biru)
  - Jurnal Umum (hijau)
  - Buku Besar (amber)
- Ketebalan teks diturunkan agar lebih nyaman dibaca.
- Pagination distandardisasi via custom pagination views.

### 6) Data Seeder & Dokumentasi

- Seeder supplier/pembelian demo dibuat lebih realistis dan konsisten.
- Branding seed diseragamkan (`Bintang Raya`).
- Dokumen final acceptance ditambahkan:
  - `docs/FINAL_ACCEPTANCE_SUPPLIER_ACCOUNTING.md`
- Matrix readiness diupdate:
  - `docs/FEATURE_READINESS_MATRIX.md`

## Validasi

- `php artisan view:cache` -> sukses.
- `php artisan test --filter=SupplierManagementTest` -> sukses (20 pass, 260 assertions).

## Catatan Operasional

- Hard refresh browser (`Ctrl+F5`) disarankan setelah deploy untuk memastikan CSS terbaru ter-load.
- Untuk verifikasi hasil, gunakan URL:
  - `/admin/suppliers`
  - `/admin/approvals`
  - `/admin/accounting/reports`
  - `/admin/accounting/journals`
  - `/admin/accounting/ledger`

