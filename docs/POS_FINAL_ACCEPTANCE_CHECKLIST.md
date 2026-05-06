# POS Final Acceptance Checklist

Tanggal: 2026-05-06

## 1) Scan dan Produk
- [ ] Search produk by nama/SKU/barcode stabil (hasil tidak hilang sendiri).
- [ ] Scan barcode menambah item yang benar.
- [ ] Anti double-add: scan kode sama beruntun tidak menambah duplikat dalam <2 detik.
- [ ] Gambar produk tampil normal.

## 2) Keranjang dan Checkout
- [ ] Ubah qty, hapus item, kosongkan keranjang berjalan.
- [ ] Undo setelah hapus/kosongkan berjalan.
- [ ] Split payment validasi jalan (harus pas total, metode tidak boleh sama).
- [ ] QRIS ref wajib saat metode QRIS dipakai.
- [ ] Guard duplicate submit checkout aktif (tidak double transaksi).

## 3) Stok
- [ ] Tombol Sinkron Stok berhasil.
- [ ] Jika stok berubah, qty keranjang auto disesuaikan.
- [ ] Warning stok muncul jika qty > stok.

## 4) Offline Flow
- [ ] Saat internet putus, checkout masuk pending queue local.
- [ ] Badge `Pending Sync` bertambah.
- [ ] Saat online lagi, queue auto resend.
- [ ] Toast sukses/sukses sebagian sinkronisasi muncul.

## 5) Midtrans QRIS
- [ ] Mode `MIDTRANS_MODE=sandbox/live` sesuai environment.
- [ ] Guard key aktif:
  - LIVE tidak boleh pakai key `SB-...`.
  - SANDBOX harus pakai key `SB-...`.
- [ ] Generate QRIS berhasil.
- [ ] Cek status QRIS berhasil.

## 6) Mobile UX
- [ ] Cart drawer bisa buka/tutup normal.
- [ ] Tidak ada horizontal scroll.
- [ ] CTA bayar terlihat dan aman di safe area.
- [ ] Interaksi touch tidak menutup cart secara tidak sengaja.

## 7) Go-Live Minimum
- [ ] ENV produksi sudah benar (APP_ENV, APP_DEBUG=false, MIDTRANS_MODE, key).
- [ ] Cache clear + config cache done.
- [ ] 10 transaksi uji (cash/debit/transfer/qris/split/pending) lolos.
- [ ] Backup DB terakhir tersedia.
