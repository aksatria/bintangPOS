# SOP POS Friendly (Printable A4)

Tanggal: 2026-05-06
Tim: Kasir / Operasional

## A. Alur Cepat Transaksi
1. Cari produk (nama/barcode) atau klik `Scan Kamera`.
2. Tambah item ke keranjang.
3. Pilih metode bayar (`Cash/QRIS/Debit/Transfer/E-Wallet`).
4. Klik `Bayar & Simpan`.

## B. Scan Produk
1. Klik `Scan Kamera`.
2. Arahkan ke barcode sampai produk masuk otomatis.
3. Jika realtime scan tidak didukung browser, pakai input kode manual.

## C. Customer dan Hutang
1. Buka `Transaksi Member` bila diperlukan.
2. Pilih customer dari suggestion.
3. Jika ada hutang, ikon `!` muncul.
4. Klik `!` untuk lihat ringkasan dan rincian invoice hutang.

## D. Split Payment
1. Aktifkan `Split payment` jika bayar 2 metode.
2. Metode A dan B harus berbeda.
3. Total nominal split harus sama dengan total transaksi.
4. Jika pakai QRIS, isi referensi QRIS.

## E. Pending / Hutang
1. Ubah status ke `Menunggu / Hutang` jika belum lunas.
2. Pastikan customer terisi.
3. Simpan transaksi.

## F. Jika Internet Putus
1. Transaksi masuk antrean `Pending Sync`.
2. Saat online, sistem sinkron otomatis.
3. Cek toast notifikasi untuk hasil sinkron.

## G. Troubleshooting Cepat
1. Produk tidak muncul: cek filter kategori, lanjut scroll ke bawah (infinite load).
2. Stok berubah: klik `Sinkron Stok`.
3. Tombol bayar gagal: cek split/QRIS/customer lalu ulang.
4. Tampilan tidak update: hard refresh browser.

## H. Catatan Penting
1. Jangan tutup tab saat transaksi sedang diproses.
2. Gunakan `Kosongkan` hanya jika yakin membatalkan keranjang.
3. HP memakai keranjang FAB, tablet/iPad memakai panel kanan.
