# SOP Ringkas POS Friendly (Final)

Tanggal update: 2026-05-06

## 1) Alur Kasir Harian
1. Buka `POS Friendly`.
2. Cari produk (nama/barcode) atau gunakan `Scan Kamera`.
3. Tambah item ke keranjang.
4. Cek jumlah item dan total di panel keranjang.
5. Pilih metode bayar (`Cash/QRIS/Debit/Transfer/E-Wallet`).
6. Klik `Bayar & Simpan`.

## 2) Scan Produk
1. Klik `Scan Kamera`.
2. Arahkan kamera ke barcode sampai produk otomatis masuk.
3. Jika browser tidak support scan realtime, isi kode manual di kolom fallback.
4. Jika scan gagal, pastikan pencahayaan cukup dan barcode tidak blur.

## 3) Customer & Hutang
1. Buka `Transaksi Member` hanya jika diperlukan.
2. Ketik nama/HP/email lalu pilih dari suggestion.
3. Jika ada hutang aktif, ikon `!` muncul.
4. Klik ikon `!` untuk lihat ringkasan hutang + rincian invoice.

## 4) Split Payment
1. Aktifkan `Split payment` jika bayar 2 metode.
2. Pastikan metode A dan B berbeda.
3. Total nominal split harus sama dengan total transaksi.
4. Jika menggunakan QRIS, isi referensi QRIS.

## 5) Pending / Hutang
1. Ubah status ke `Menunggu / Hutang` jika pembayaran belum lunas.
2. Lengkapi data customer agar transaksi pending valid.
3. Simpan transaksi sesuai status (label tombol otomatis menyesuaikan).

## 6) Saat Internet Putus
1. Jika checkout saat offline, transaksi masuk antrean `Pending Sync`.
2. Setelah online kembali, sistem akan sinkron otomatis.
3. Pantau notifikasi toast untuk status sinkronisasi.

## 7) Troubleshooting Cepat
1. Produk tidak muncul: cek filter kategori, lalu scroll kebawah (infinite load).
2. Stok tidak sesuai: klik `Sinkron Stok`.
3. Tombol bayar tidak jalan: cek validasi split/QRIS/customer lalu coba lagi.
4. UI tidak update: hard refresh browser.

## 8) Catatan Operasional
1. Jangan tutup tab saat transaksi masih diproses.
2. Gunakan `Kosongkan` hanya jika yakin membatalkan keranjang.
3. Untuk tablet/iPad, keranjang tampil di kanan; untuk HP gunakan tombol keranjang mengambang.
