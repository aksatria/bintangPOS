# Release Checklist: Multi-Cabang

## 1) Pre-Deploy
- Pastikan backup database terbaru tersedia.
- Pastikan environment production sudah sinkron dengan branch release.
- Verifikasi `APP_ENV`, `APP_DEBUG`, dan kredensial DB benar.

## 2) Deploy Steps
- Pull kode terbaru ke server.
- Jalankan:
```bash
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
- Restart worker queue/supervisor bila dipakai.

## 3) Post-Migrate Data Checks
- Tabel `branches` terisi (minimal cabang default).
- Kolom `branch_id` terisi pada tabel utama:
  - `users`, `products`, `sales`, `expenses`, `pos_holds`, `stock_opnames`, `cash_reconciliations`
  - `customers`, `customer_followups`, `approval_requests`
  - `cashier_audit_logs`, `audit_alert_states`

## 4) Access & Security Checks
- User non-owner tidak bisa membuka data cabang lain via URL langsung.
- Admin user page: assign user ke cabang berfungsi.
- Branch management CRUD owner berfungsi.

## 5) Functional Smoke Test
- Checkout POS pada 2 cabang berbeda.
- Buat approval request refund/void di masing-masing cabang.
- Cek dashboard/report: data non-owner hanya menampilkan cabangnya.
- Cek audit log/sistem health: data terscope cabang.

## 6) Scheduler & Telegram Checks
- Jalankan manual:
```bash
php artisan telegram:send-daily-summary
php artisan telegram:send-weekly-sla-report
php artisan telegram:send-pending-overdue-reminder
php artisan telegram:send-followup-reminders
php artisan approval:send-sla-escalation
php artisan approval:send-sla-anomaly-alert
php artisan approval:auto-expire
```
- Pastikan pesan Telegram menyertakan konteks cabang.

## 7) Rollback Plan
- Jika issue kritis:
  - nonaktifkan scheduler command baru sementara,
  - restore database dari backup terakhir,
  - rollback release ke commit stabil sebelumnya.

