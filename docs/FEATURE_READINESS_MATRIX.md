# Feature Readiness Matrix

Dokumen ini merangkum status fitur POS saat ini dalam kategori:
- `Sudah`: sudah operasional dan dipakai
- `Partial`: sudah ada, tetapi belum lengkap enterprise-level
- `Belum`: modul inti belum tersedia

## Matriks Fitur

| Fitur | Status | Catatan |
|---|---|---|
| Manajemen Pelanggan | Sudah | Database customer + riwayat pembelian tersedia. |
| Sistem Retur/Refund | Sudah | Quick refund/void dan approval flow sudah berjalan. |
| Penyesuaian Stok (Opname) | Sudah | Flow opname, posting, import/export, audit tersedia. |
| Metode Pembayaran Ganda | Sudah | Cash, QRIS, debit, transfer, e-wallet, split payment tersedia. |
| Sistem Diskon/Promo | Sudah | Rule promo buy-X-get-Y tersedia di pengaturan toko. |
| Manajemen Supplier | Sudah | Master supplier + pembelian supplier + receive stok + audit log tersedia. |
| Manajemen Shift/Karyawan | Partial | Rekonsiliasi kasir ada, KPI SDM/shift mendalam belum penuh. |
| Laporan Lanjutan | Partial | Laporan operasional tersedia, analitik BI mendalam masih terbatas. |
| Multi-Lokasi/Cabang | Sudah | Cabang aktif, scope data, mutasi antar cabang, RBAC branch-aware tersedia. |
| Notifikasi Cerdas | Partial | Alert SLA/overdue tersedia, rule cerdas lanjutan masih bisa ditambah. |
| Backup Otomatis | Sudah | Backup command, restore dry-run, drill runbook, health monitoring tersedia. |
| Manajemen Hutang Pelanggan | Sudah | Modul hutang/cicilan pelanggan, pembayaran bertahap, status sisa & overdue tersedia. |
| Template Struk Custom | Partial | Branding dasar ada, payment link/QR dinamis lanjutan belum penuh. |
| Fitur POS Premium | Sudah | Split payment + hold/reservasi + checkout cicilan tenor sudah tersedia. |
| Akuntansi Inti (Jurnal, Laporan, Buku Besar) | Sudah | Posting otomatis dari pembelian supplier, pembayaran, retur, dan POS sudah berjalan + halaman audit akuntansi tersedia. |

## Prioritas Implementasi Berikutnya

1. Manajemen Supplier (pembelian, penerimaan barang, hutang supplier).
2. Manajemen Hutang Pelanggan (piutang/cicilan + jadwal bayar).
3. POS Premium Cicilan (tenor, bunga, histori angsuran).
4. Laporan Lanjutan BI (margin per produk, cohort pelanggan, forecasting sederhana).
5. Notifikasi Cerdas lanjutan (rule engine threshold multi-parameter).

## Definisi Done (per modul baru)

- Ada menu UI + validasi backend + RBAC.
- Ada audit log untuk aksi kritikal.
- Ada test feature utama (happy path + guardrail).
- Masuk changelog + runbook operasional jika menyentuh scheduler/ops.

## Final Baseline (Akuntansi + Supplier)

- UI konsisten: spacing, topbar, tombol, tabel, pagination pada modul akuntansi dan supplier sudah diseragamkan.
- Alur supplier end-to-end: draft pembelian -> edit -> ajukan approval -> approve/reject -> penerimaan -> pembayaran -> pelacakan sisa hutang.
- Approval tercatat: status pending/approved/rejected muncul di daftar dan detail, termasuk riwayat proses.
- Posting akuntansi otomatis: jurnal debit-kredit terbentuk dari event utama operasional.
- Data demo konsisten: seeder supplier/pembelian realistis dan seragam untuk uji pagination.
