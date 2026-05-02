# Post-Deploy Monitoring Checklist

Checklist monitoring 2-3 hari setelah perubahan performa/validasi.

## Hari 1-3

1. Jalankan snapshot metrik harian
- `php artisan ops:monitor-snapshot --days=7`
- Simpan hasil untuk pembanding antar hari.

2. Jalankan stress check report
- `php artisan reports:stress-check --days=30 --loops=5`
- Catat AVG dan P95; bandingkan trend.

3. Pantau error validasi baru
- Fokus pada filter report: `period`, `payment_method`, `qris_reference`, `custom range`.
- Pastikan error bersifat user-facing (bukan 500).

4. Verifikasi konsistensi audit context
- Jalankan `php artisan audit:backfill-meta --dry-run`.
- Target: `Missing _meta` mendekati 0 setelah backfill produksi.

5. Keputusan index lanjutan
- Jika AVG/P95 masih tinggi, review query terberat dulu sebelum menambah index baru.

6. Monitoring escalation scheduler
- Cek panel `Last Escalation Run` di halaman Approval Queue.
- Pastikan nilai `Checked` dan `Sent` bergerak sesuai beban nyata, bukan selalu 0.
- Jika `Sent` tiba-tiba naik tajam, verifikasi cooldown escalation aktif.

7. Monitoring backup/restore runtime config
- Uji `Export Runtime JSON` dari Pengaturan Toko.
- Simpan 1 file baseline harian untuk rollback cepat.
- Uji `Import Runtime JSON` di staging sebelum dipakai di production.

8. Monitoring notifikasi Telegram weekly SLA
- Uji tombol `Preview Weekly SLA` dari halaman Notif Telegram.
- Pastikan format report sesuai ekspektasi owner.
- Jika kirim Telegram gagal, cek log `telegram_send_failed` lalu validasi retry/timeout.

9. Monitoring health sistem otomatis
- Jalankan `php artisan ops:health-check --telegram`.
- Pastikan metrik berikut tidak melewati threshold:
  - `failed_jobs`
  - `queue_backlog`
  - `overdue_approvals`
  - `critical_audit_errors_1h`
- Cek dashboard `/admin/system-health` untuk ringkasan cepat.

10. Monitoring backup/restore
- Pastikan scheduler harian `db:backup-safe` berjalan.
- Simulasi `php artisan db:restore-safe <path-file> --dry-run`.
- Uji restore real hanya di staging dengan `--force`.

## Catatan

- Hindari menambah index secara membabi-buta tanpa bukti query lambat.
- Prioritaskan query read-heavy yang dipakai dashboard/report harian.
