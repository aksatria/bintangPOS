# Troubleshooting Cepat

## 1. Halaman tidak update

- Hard refresh (`Ctrl+F5`).
- Cek cache view jika perlu.

## 2. Error Blade

1. Cek syntax file Blade/PHP.
2. Jalankan `php artisan view:cache`.
3. Cek log aplikasi.

## 3. Data tidak muncul

- Cek cabang aktif.
- Cek permission user.
- Cek filter tanggal/status.

## 4. Approval tidak tampil

- Pastikan request sudah diajukan.
- Pastikan role approver punya `approvals.manage`.

## 5. Jurnal belum muncul

- Pastikan event transaksi sudah final (receive/pay/checkout).
- Cek filter tanggal laporan/jurnal.

## 6. Langkah Eskalasi Minimal

1. Catat URL, waktu, dan user yang mengalami masalah.
2. Catat pesan error persis.
3. Lampirkan cuplikan log relevan.
4. Eskalasi ke PIC teknis dengan data lengkap.
