# Approval SLA Runbook

## Tujuan
- Menjaga request sensitif (refund, void, export besar) diproses tepat waktu.
- Mengurangi pending lama dan auto-expired.

## Level Escalation
- `L1`: request melewati SLA dasar.
- `L2`: request melewati 2x SLA dasar, sistem auto-assign reviewer.
- `L3`: request melewati 4x SLA dasar, sistem auto-assign ke owner.

## Aturan Operasional
- Untuk `L3`, aksi `Snooze` dinonaktifkan.
- Reviewer wajib memilih `Approve` atau `Reject`.
- Semua escalated request tercatat di payload approval (timeline L1/L2/L3).

## SLA Dasar
- `sale.quick_refund` / `sale.quick_void`: sesuai `approval_rules.sla_minutes_sale`.
- `report.export.*`: sesuai `approval_rules.sla_minutes_export`.
- Perhitungan SLA menggunakan jam kerja (`approval_rules.business_hours`) yang diatur dari menu `Pengaturan Toko`.

## Auto-Assign Policy
- `L2`: prioritaskan `admin` (fallback `owner`) berdasarkan beban pending paling ringan.
- `L3`: paksa ke `owner` berdasarkan beban pending paling ringan.

## Monitoring Harian
- Buka halaman `Approval Queue`.
- Fokus ke:
  - Card summary `Overdue SLA`.
  - Panel `Reviewer Workload` (Pending/Overdue/Avg Review).
  - Panel observability `Last Escalation Run` (`Waktu Run`, `Checked`, `Sent`, `Cooldown`).
  - Badge `Auto-assigned (Lx)` dan timeline `L1/L2/L3` pada card approval.

## Scheduler yang Wajib Aktif
- `approval:send-sla-escalation` tiap 10 menit.
- `approval:auto-expire` tiap 15 menit.
- `telegram:send-weekly-sla-report` tiap Senin 08:00.

## Failure Handling
- Jika Telegram gagal:
  - cek `TELEGRAM_BOT_TOKEN` dan `TELEGRAM_CHAT_ID`.
  - cek audit log `telegram_send_failed`.
  - gunakan tombol `Preview Weekly SLA` untuk validasi format isi pesan tanpa mengirim ke grup.
- Jika escalation dobel:
  - pastikan lock/cooldown command tidak dimatikan.

## Backup & Rollback Konfigurasi
- Dari menu `Pengaturan Toko`, gunakan:
  - `Export Runtime JSON` untuk backup `approval_rules` + RBAC mapping.
  - `Import Runtime JSON` untuk restore cepat saat rollback kebijakan.

## KPI Minimal
- Overdue pending turun tiap minggu.
- Reject rate stabil dan beralasan.
- Avg review time tidak naik tajam 2 minggu berturut-turut.
