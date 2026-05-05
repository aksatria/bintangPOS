# Aplikasi Overview - POS, Supplier, Approval, Akuntansi

Dokumen ini merangkum fitur, alur kerja, cara baca layar, dan mekanisme akuntansi inti.

## 1. Tujuan Aplikasi

- Mengelola transaksi operasional harian (penjualan dan pembelian).
- Mengontrol proses persetujuan transaksi penting.
- Membentuk jurnal akuntansi otomatis untuk monitoring keuangan.

## 2. Modul Utama

1. Dashboard  
Ringkasan KPI, alert, dan insight operasional.

2. POS / Penjualan  
Transaksi kasir, metode bayar, diskon, split payment, hold/resume.

3. Master Data  
Kategori, produk, pelanggan, supplier, pengguna.

4. Supplier Management  
Draft pembelian, edit, approval, receive, retur, pembayaran, rekonsiliasi.

5. Approval Center  
Approve/reject request lintas modul dengan riwayat dan catatan.

6. Akuntansi  
Laporan Akuntansi, Jurnal Umum, Buku Besar.

7. Audit Log  
Jejak aktivitas kritikal.

8. Multi-cabang  
Scope data berdasarkan cabang aktif dan role.

## 3. Flow End-to-End Supplier

1. Buat/kelola supplier.
2. Buat draft pembelian supplier.
3. Edit draft jika perlu.
4. Ajukan persetujuan.
5. Owner approve/reject.
6. Terima barang (penuh/parsial).
7. Catat retur jika ada.
8. Catat pembayaran supplier.
9. Pantau status hutang (`unpaid/partial/paid/overdue`).

## 4. Flow Approval

1. Request dibuat user operasional.
2. Masuk ke `admin/approvals`.
3. Approver proses `approve/reject`.
4. Sistem simpan status + siapa memproses + catatan.

## 5. Cara Kerja Akuntansi Inti

Model:
- `accounts`
- `journal_entries`
- `journal_lines`

Prinsip:
- Setiap event penting menghasilkan jurnal.
- Total debit = total kredit.
- Laporan dibaca dari agregasi line jurnal.

Event utama yang diposting:
- pembelian supplier
- pembayaran supplier
- retur pembelian
- transaksi POS

## 6. Cara Baca Halaman Akuntansi

1. `Laporan Akuntansi`  
Filter tanggal, lihat ringkasan debit/kredit/laba, baca arti kode akun.

2. `Jurnal Umum`  
Audit per dokumen jurnal dan detail line debit-kredit.

3. `Buku Besar`  
Filter per akun dan telusuri mutasi.

## 7. Status Penting

- Approval: `pending`, `approved`, `rejected`
- Barang: `draft`, `received`, `partial`
- Bayar: `unpaid`, `partial`, `paid`, `overdue`

## 8. URL Operasional

- `/admin/suppliers`
- `/admin/approvals`
- `/admin/accounting/reports`
- `/admin/accounting/journals`
- `/admin/accounting/ledger`

