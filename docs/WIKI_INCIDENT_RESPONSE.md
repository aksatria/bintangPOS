# Incident Response (Operasional Cepat)

## Level Insiden

1. `P1` - Sistem tidak bisa transaksi.
2. `P2` - Fitur utama terganggu (supplier/approval/akuntansi).
3. `P3` - Gangguan minor/UI.

## Langkah Tanggap Awal

1. Catat waktu kejadian dan gejala.
2. Identifikasi modul terdampak.
3. Cek cabang/user scope (apakah semua atau sebagian).
4. Jalankan pemeriksaan dasar (cache, log, koneksi DB).

## Checklist Teknis Singkat

1. `php artisan view:cache`
2. Cek `storage/logs/laravel.log`
3. Cek status scheduler (jika terkait job otomatis)
4. Validasi permission role bila akses ditolak

## Eskalasi

- `P1`: eskalasi langsung ke owner + lead teknis.
- `P2`: eskalasi ke admin sistem + lead teknis.
- `P3`: masuk backlog perbaikan terjadwal.

## Penutupan Insiden

1. Verifikasi masalah terselesaikan.
2. Catat root cause.
3. Catat tindakan pencegahan berulang.
