# Modul Pelanggan & Piutang

## Fungsi

- Kelola data pelanggan.
- Pantau hutang/piutang pelanggan.
- Catat pembayaran cicilan/piutang.
- Lihat histori pembayaran dan status aging.

## Alur Ringkas

1. Buat/pilih pelanggan.
2. Buat transaksi dengan skema hutang jika diperlukan.
3. Pantau aging dan status piutang.
4. Catat pembayaran bertahap sampai lunas.

## Status Umum

- Active / Overdue / Settled.

## Kontrol Penting

- Nominal pembayaran tidak boleh melebihi sisa.
- Catat tanggal bayar aktual untuk akurasi aging.
- Gunakan catatan pembayaran jika ada kasus khusus.

## Risiko Umum

- Salah mapping customer pada transaksi.
- Pembayaran masuk tapi tidak dicatat ke debt record.
