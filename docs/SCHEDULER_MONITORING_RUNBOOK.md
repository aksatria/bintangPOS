# Scheduler Monitoring Runbook

## Tujuan
Memastikan `schedule:run` aktif konsisten di server produksi.

## Konfigurasi Wajib Server
Gunakan cron (Linux) tiap menit:

```bash
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

## Heartbeat Scheduler
Sistem menyimpan heartbeat otomatis setiap menit via:
- command: `ops:scheduler-heartbeat`
- schedule: `everyMinute()`

Heartbeat dipantau di halaman:
- `Admin > System Health`
- indikator `Scheduler Age (menit)`

## Threshold Operasional
- `0-3 menit`: sehat
- `>3 menit`: warning
- `>10 menit`: incident, eskalasi ke tim infra

## Langkah Triage Cepat
1. Cek `System Health` dan pastikan heartbeat update.
2. Jalankan manual:
   - `php artisan schedule:run`
   - `php artisan ops:scheduler-heartbeat`
3. Cek cron service / supervisor di server.
4. Validasi job penting:
   - `ops:health-check --telegram`
   - `stock-transfer:send-aging-alert`

