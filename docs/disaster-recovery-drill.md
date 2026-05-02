# Disaster Recovery Drill (Bulanan)

Tujuan: memastikan backup benar-benar bisa dipakai saat insiden.

## Jadwal

- Scheduler: `ops:drill-disaster-recovery --staging`
- Default: setiap tanggal 1, jam 04:30.

## Langkah Drill

1. Pastikan dijalankan di environment staging.
2. Ambil backup terbaru dari `storage/app/backups`.
3. Jalankan dry-run restore:
   - `php artisan db:restore-safe <path-backup> --dry-run`
4. Jalankan health check:
   - `php artisan ops:health-check`
5. Validasi audit log muncul:
   - action: `disaster_recovery_drill_completed`

## Kriteria Lulus

- File backup terbaru terdeteksi.
- Dry-run restore sukses tanpa error.
- Health check command selesai.
- Audit trail drill tercatat.

## Catatan

- Command drill ini TIDAK melakukan restore real (hanya dry-run).
- Restore real tetap wajib prosedur terpisah dengan `--force` dan change approval.
