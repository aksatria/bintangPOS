# FAQ Operasional - Supplier & Akuntansi

## 1) Kenapa data supplier/pembelian tidak terlihat?

- Pastikan cabang aktif benar.
- Pastikan user punya permission `suppliers.manage`.

## 2) Kenapa tombol approve/reject tidak muncul?

- Fitur ini muncul untuk role/permission approval (`approvals.manage`).
- Cek halaman `Admin > Approvals`.

## 3) Kalau approval ditolak, apa yang terjadi?

- Status approval menjadi `rejected`.
- Draft pembelian tidak lanjut ke tahap receive sampai diajukan ulang/diubah sesuai kebutuhan.

## 4) Setelah klik Simpan Draft Pembelian, data masuk ke mana?

- Masuk ke data `supplier_purchases` dan detail item ke `supplier_purchase_items`.
- Bisa dilihat di daftar pembelian supplier.

## 5) Bagaimana jika item pembelian sangat banyak (mis. 1000 item)?

- Gunakan baris item dinamis (tombol tambah produk) dan input manual per baris.
- Disarankan pecah batch per dokumen bila terlalu besar untuk mengurangi risiko input.

## 6) Kenapa jurnal belum muncul di Akuntansi?

- Jurnal muncul setelah event transaksi yang memicu posting (pembelian/bayar/retur/POS) dieksekusi.
- Cek juga filter tanggal di halaman akuntansi.

## 7) Kode akun seperti 1101, 1102 itu apa?

- Itu kode COA (Chart of Accounts).
- Penjelasannya ada di halaman Laporan Akuntansi bagian arti kode akun.

## 8) Kenapa pagination terasa berbeda?

- Pagination sudah diseragamkan ke style global agar konsisten.
- Jika belum terlihat, lakukan hard refresh browser (`Ctrl+F5`).

## 9) Kenapa tampilan terasa tidak update?

- Kemungkinan cache browser.
- Hard refresh atau clear cache frontend.

## 10) Apa langkah pertama saat ada error tampilan Blade?

1. Cek syntax Blade/PHP pada file terkait.
2. Jalankan `php artisan view:cache`.
3. Buka ulang halaman dan cek log bila masih gagal.

## 11) Kenapa pembayaran supplier diblokir dengan pesan 3-way matching?

- Jika mode enforcement aktif, pembayaran supplier hanya boleh lanjut jika nilai PO/receive/invoice dalam toleransi.
- Cek `supplier_invoice_amount`, `received_quantity`, dan status rekonsiliasi.

## 12) Reorder suggestion dihitung dari apa?

- Sistem menghitung dari `low_stock_threshold` + rata-rata penjualan harian.
- Periode lookback, lead time, dan safety stock mengikuti rule di halaman Store Settings.

## 13) MoM di laporan artinya apa?

- MoM (Month-over-Month) membandingkan performa bulan berjalan vs bulan sebelumnya.
- Nilai positif berarti naik, negatif berarti turun.

## 14) Kenapa status readiness belum 100% padahal test sudah hijau?

- Test hijau artinya kode stabil, tetapi readiness 100% juga butuh operasi sehat (scheduler, backup, overdue approvals) dan UAT signoff real data.
- Jalankan `ops:health-check` dan `ops:production-sanity-check --strict` untuk melihat status operasional.

## 15) Checklist final fitur ada di mana?

- Gunakan dokumen `docs/UAT_CEPAT_FITUR_FINAL_2026-05-06.md`.
- Dokumen ini berisi langkah uji cepat untuk OTP login, supplier 3-way matching, reorder suggestion, report MoM, dan approval auto-routing.

## 16) Kenapa metode bayar di POS Friendly tidak bisa dipilih?

- Pastikan status transaksi dan validasi split payment tidak mengunci tombol bayar.
- Jika UI stale, lakukan hard refresh browser.

## 17) Kenapa keranjang kanan tidak muncul di iPad Air/Pro?

- Layout tablet aktif untuk lebar `>=768px`; jika menggunakan split-view sempit, sistem bisa fallback ke mode drawer.
- Coba keluar dari split-view atau perlebar area aplikasi.

## 18) Kenapa scan barcode terasa dobel / terlalu sensitif?

- Sistem memakai guard anti-duplikat singkat.
- Arahkan kamera stabil 1 barcode per item; untuk input manual gunakan fallback di modal scan.

## 19) Kenapa produk baru tidak langsung habis (infinite list)?

- POS Friendly memakai infinite scroll.
- Scroll ke bawah untuk memuat batch produk berikutnya.

## 20) Kalau internet putus saat checkout POS, apa aman?

- Aman, transaksi masuk antrean `Pending Sync`.
- Saat internet kembali, sistem retry sinkron otomatis.
