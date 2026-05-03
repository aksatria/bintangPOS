# Backup-Restore Drill Multi-Cabang (Singkat)

## Tujuan
Validasi data multi-cabang dapat dipulihkan dengan aman dari backup terbaru.

## Frekuensi
- Minimal bulanan (staging)

## Langkah Drill
1. Buat backup terbaru:
   - `php artisan db:backup-safe`
2. Ambil path backup terakhir dari output command.
3. Jalankan dry-run restore:
   - `php artisan db:restore-safe <path-backup> --dry-run`
4. Jalankan health check:
   - `php artisan ops:health-check`
5. Jalankan drill automation:
   - `php artisan ops:drill-disaster-recovery --staging`

## Validasi Multi-Cabang
- Data `branches` tetap konsisten.
- Sampel mutasi (`stock_transfers`, `stock_transfer_items`) terbaca normal.
- User cabang + branch scope tetap sesuai.

## Kriteria Lulus
- Backup sukses.
- Dry-run restore sukses tanpa error.
- Health check tidak critical.
- Audit log memiliki `disaster_recovery_drill_completed`.

