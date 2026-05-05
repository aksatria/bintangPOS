# Modul POS & Penjualan

## Fungsi

- Checkout transaksi kasir.
- Split payment.
- Hold/resume transaksi.
- Rekam histori transaksi penjualan.
- Dukungan kontrol diskon/promo sesuai kebijakan.

## Alur Ringkas

1. Pilih produk.
2. Atur qty/diskon.
3. Pilih metode bayar.
4. Selesaikan checkout.
5. Verifikasi transaksi tercatat.

## Kontrol Penting

- Cabang aktif harus benar.
- Nominal bayar harus sesuai.
- Void/refund mengikuti policy approval (jika aktif).

## Data yang Terdampak

- Penjualan header/detail.
- Stok produk.
- Metode pembayaran dan jejak kasir.
- Jurnal akuntansi otomatis (saat transaksi final).

## KPI yang Dipantau

- Omzet harian.
- Jumlah transaksi.
- Rata-rata nilai transaksi.
- Proporsi metode pembayaran.

## Risiko Umum

- Salah cabang aktif.
- Salah nominal saat split payment.
- Transaksi hold tidak ditutup ulang.
