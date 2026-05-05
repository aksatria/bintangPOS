# Modul Stock Opname & Transfer

## Fungsi

- Stock opname berkala.
- Koreksi selisih stok.
- Transfer stok antar cabang.
- Jejak audit perubahan stok.

## Alur Ringkas

1. Mulai sesi opname.
2. Input jumlah fisik.
3. Posting selisih.
4. Buat transfer stok jika diperlukan.
5. Terima transfer di cabang tujuan.

## Kontrol Penting

- Gunakan user berizin.
- Simpan jejak audit selisih.
- Validasi qty negatif dan satuan produk.

## Risiko Umum

- Opname dilakukan saat transaksi berjalan tanpa cut-off.
- Transfer belum diterima tapi stok tujuan dianggap masuk.
