# Production Go-Live Multi-Cabang

## 1) Setup Scheduler Server (wajib)
Cron per menit:

```bash
* * * * * cd /path/to/pos && php artisan schedule:run >> /dev/null 2>&1
```

## 2) Warm-up awal setelah deploy
Jalankan sekali:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
php artisan migrate --force
php artisan db:backup-safe
php artisan ops:scheduler-heartbeat
php artisan ops:health-check --telegram
```

## 3) Sanity check go-live (single command)
Jalankan:

```bash
php artisan ops:production-sanity-check --strict
```

Interpretasi:
- Exit code `0`: aman.
- Exit code `1`: ada warning/critical, jangan close deployment sebelum dibereskan.

## 4) Verifikasi aplikasi (manual cepat)
1. Login owner, pindah `Cabang Aktif`, pastikan data ikut berubah.
2. Buat mutasi test kecil antar cabang (request -> approve -> receive).
3. Cek `System Health`:
   - scheduler heartbeat age <= 3 menit
   - last backup terisi
4. Cek `Dashboard` manager:
   - tabel KPI mutasi per cabang tampil.

## 5) Recovery drill staging (bulanan)
```bash
php artisan ops:drill-disaster-recovery --staging
php artisan ops:recovery-pack --staging --telegram
```

