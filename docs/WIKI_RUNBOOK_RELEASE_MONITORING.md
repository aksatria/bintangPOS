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

## Referensi Detail

- [POST_DEPLOY_MONITORING.md](./POST_DEPLOY_MONITORING.md)
- [release-multi-branch-checklist.md](./release-multi-branch-checklist.md)
