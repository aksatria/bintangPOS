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

