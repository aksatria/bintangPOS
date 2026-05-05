# Runbook Rilis & Monitoring

## Tujuan

Menjaga proses rilis aman dan memastikan kondisi sistem terpantau setelah deploy.

## Checklist Rilis Singkat

1. Validasi migrasi dan backup sebelum rilis.
2. Deploy aplikasi sesuai prosedur.
3. Verifikasi endpoint kritikal.
4. Verifikasi scheduler/job penting.

## Monitoring Pasca Rilis

1. Cek error log.
2. Cek performa endpoint utama.
3. Cek approval, supplier, dan akuntansi berjalan normal.
4. Cek notifikasi terjadwal.

## Command Gate (Wajib Untuk Final 100%)

Jalankan berurutan:

```bash
php artisan test --stop-on-failure
php artisan ops:health-check
php artisan ops:production-sanity-check --strict
```

Kriteria lulus:
- Semua test pass.
- Tidak ada `CRITICAL` di strict sanity check.
- Metric `overdue_approvals` tidak melewati threshold.

Jika gagal:
1. Perbaiki scheduler heartbeat.
2. Jalankan backup dan verifikasi umur backup.
3. Selesaikan approval overdue (approve/reject/auto-expire).
4. Ulangi command gate sampai hijau.

## Referensi Detail

- [POST_DEPLOY_MONITORING.md](./POST_DEPLOY_MONITORING.md)
- [release-multi-branch-checklist.md](./release-multi-branch-checklist.md)
