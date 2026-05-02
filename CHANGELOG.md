# Changelog

Semua perubahan penting pada proyek ini akan dicatat di file ini.

Format tanggal: `YYYY-MM-DD`.

## [2026-05-02]

### Added
- Modul pengaturan notifikasi Telegram (`/admin/notification-settings`) termasuk test kirim notifikasi manual.
- Modul audit log kasir dengan halaman monitoring dan export (`/audit-logs`, Excel/PDF).
- Modul customer management lanjutan: merge customer, follow-up queue, export follow-up, export riwayat pembelian.
- Modul stock opname: create draft, import CSV, post adjustment, duplicate, export CSV/PDF.
- Kontrol pembayaran lanjutan di POS:
  - Split payment (`mixed`) dengan validasi nominal.
  - Aturan overpay per metode pembayaran.
  - Alur pending payment dan quick settle pending.
- Mekanisme hold transaksi POS (simpan, load, rename, delete).
- Snapshot dashboard ke PDF.
- Command operasional baru:
  - `audit:backfill-meta` untuk backfill metadata audit lama.
  - `ops:monitor-snapshot` untuk snapshot metrik + timing query.
  - `reports:stress-check` untuk uji beban ringan query laporan.
  - `rbac:health-check` untuk audit konsistensi RBAC (dengan opsi `--repair` untuk perbaikan terbatas baseline).
  - `rbac:notify-unhealthy` untuk kirim alert Telegram ketika hasil RBAC health check tidak sehat.
- Dokumen operasional:
  - `docs/AUDIT_ACTION_CATALOG.md`
  - `docs/POST_DEPLOY_MONITORING.md`
- Modul RBAC granular:
  - Tabel `permissions` dan `role_permissions`.
  - Middleware `permission:*` untuk endpoint sensitif.
  - Halaman manajemen RBAC `/admin/rbac`.
  - Fitur copy permission antar role, select/clear per role, dan reset ke default mapping.
  - Dokumen `docs/RBAC_MANAGEMENT.md`.

### Changed
- Dokumentasi utama proyek di `README.md` diselaraskan dengan fitur aktual aplikasi.
- Ditambahkan dokumen policy akses `docs/ACCESS_POLICY.md` untuk standard perilaku `302/403/401/422`.
- Urutan route penjualan diperbaiki agar route statis tidak tertangkap route dinamis:
  - `GET /sales/pending-attempts/history` diposisikan sebelum `GET /sales/{sale}`.
- Sinkronisasi ekspektasi test akses kasir pada area admin:
  - Endpoint `/admin/*` untuk user non-manager mengikuti behavior redirect ke dashboard.
- Hardening filter report:
  - Validasi ketat `period`, `payment_method`, `qris_reference`, `selected_ids`.
  - Periode `custom` wajib tanggal awal/akhir dengan batas maksimal 366 hari.
- Enforce middleware permission granular pada area yang sebelumnya hanya role-based:
  - Master data (`/admin/categories*`, `/admin/products*`, `/admin/expenses*`) kini wajib `master-data.manage`.
  - Customer management dan follow-up dipisah:
    - `customers.manage` untuk CRUD/list/export customer.
    - `customers.followup.manage` untuk queue/status follow-up dan aksi pending sales berbasis customer.
- Navigasi owner/admin kini menampilkan empty-state konsisten ketika tidak ada submenu yang bisa diakses (Master Data dan Kontrol & Sistem).
- Audit trail perubahan RBAC diperluas dengan snapshot `before/after`, `changes` (added/removed per role), dan metadata sumber aksi.
- Penambahan RBAC permission matrix test untuk endpoint mutasi sensitif (POST/PUT) agar middleware bukan hanya teruji pada endpoint GET.
- Penambahan test akses model API-style (`Accept: application/json`) untuk memastikan unauthorized permission tetap mengembalikan `403` dengan payload error yang konsisten.
- Penambahan matrix test untuk endpoint ber-parameter (`DELETE/PATCH`) yang memakai route model binding agar enforcement permission tetap konsisten pada aksi mutasi record.
- Penambahan smoke test skenario permission downgrade saat sesi admin masih aktif (akses langsung terblokir pada request berikutnya).
- Penambahan test command RBAC health check untuk mode audit gagal dan mode `--repair`.
- Penambahan output `--json` pada `rbac:health-check` untuk integrasi monitoring/CI.
- Penjadwalan harian `rbac:notify-unhealthy` pada scheduler (`03:00`) agar deteksi masalah RBAC lebih cepat.

### Fixed
- Perbaikan kegagalan test `PosFlowAndAccessTest` terkait perbedaan ekspektasi `403` vs `302` pada route admin.
- Mitigasi potensi konflik route sales history dengan route parameterized sale detail.
- Penambahan regression test untuk memastikan route sales history selalu resolve benar.
- Standardisasi metadata audit log (`context._meta`) agar format context lebih konsisten untuk analitik.
- Menutup celah inkonsistensi antara visibilitas menu RBAC dan enforcement backend pada beberapa route backoffice.

### Security
- Aksi sensitif (refund/void/partial refund dan export besar) menggunakan approval manager (`owner/admin`).
- Export laporan dilindungi rate limit untuk mengurangi penyalahgunaan endpoint.

### Performance
- Menambahkan index performa untuk query yang sering dipakai:
  - `sales(status, payment_method, sold_at)`
  - `sales(status, payment_due_at)`
  - `cashier_audit_logs(action, created_at)`
  - `cashier_audit_logs(user_id, created_at)`
  - `sale_items(product_id, created_at)`

## [Unreleased]

### Added
- Fondasi multi-cabang (multi-lokasi) tahap 1:
  - tabel `branches` + seed default cabang `PUSAT`,
  - kolom `branch_id` pada tabel inti (`users`, `products`, `sales`, `expenses`, `pos_holds`, `stock_opnames`, `cash_reconciliations`) beserta backfill data lama,
  - relasi model `Branch` pada entitas terkait.
- UI manajemen cabang pada halaman user admin:
  - tambah/edit/hapus cabang (owner),
  - filter user berdasarkan cabang,
  - assignment cabang saat tambah/edit user.
- Middleware `branch.access` untuk hard-enforce akses route-model berdasarkan `branch_id` (non-owner).
- Test baru route ownership lintas cabang:
  - `tests/Feature/BranchRouteAccessTest.php`.
- Hardening scope cabang pada laporan:
  - `ReportController` (index/export/approval metrics) kini scoped per cabang untuk non-owner.
  - `SalesReportExport` kini menerima konteks cabang agar isi file export tidak bocor lintas cabang.
- Test branch scope laporan:
  - `tests/Feature/ReportBranchScopeTest.php`.
- Hardening scope cabang pada operasional dashboard/audit:
  - `DashboardController` kini menerapkan scope cabang pada KPI utama (omzet/transaksi/HPP/pengeluaran/stok/best seller/top transaksi/sparkline),
  - `CashierAuditLogController` kini scoped cabang untuk list/export/summary/incident timeline.
- `NotificationSettingController` kini scoped cabang untuk recent Telegram logs, dan helper summary harian ditambah varian `buildDailySummaryMessageForBranch(...)`.
- Hardening scope cabang pada customer, stock opname, dan approval queue:
  - `CustomerController` scoped cabang untuk list/suggest/metrics/follow-up queue/pending aging/timeline, serta create customer/followup menyimpan `branch_id`.
  - `StockOpnameController` scoped cabang untuk list/open-session/create/post/duplicate/import dan query produk terkait.
  - `ApprovalRequestController` scoped cabang untuk queue summary/filter/workload/bulk action/execution path.
- Skema multi-cabang diperluas:
  - tambah `branch_id` pada `customers` dan `customer_followups` (dengan backfill),
  - tambah `branch_id` pada `approval_requests` (dengan backfill).
- Export customer kini aware cabang:
  - `CustomersExport`, `CustomersFollowUpExport`, `CustomerPurchaseHistoryExport`.
- Hardening lanjutan scope cabang:
  - `ReportController` kini juga scope `customers` filter list dan lookup approval export (`approved/pending`) per cabang.
  - `PosController` kini scope `customers` list POS dan quick refund query (`sale` + `product`) per cabang.
  - `SystemHealthController` kini scope metrik approval/audit berdasarkan cabang untuk non-owner.
- Approval SLA berbasis jam kerja (`business_hours`) untuk kalkulasi:
  - escalation L1/L2/L3,
  - aging bucket pending,
  - overdue summary,
  - avg review time.
- Panel `Reviewer Workload` di Approval Queue:
  - pending per reviewer,
  - overdue per reviewer,
  - avg review 7 hari per reviewer.
- Tren historis SLA di Approval Queue:
  - tren mingguan (8 minggu),
  - tren bulanan (6 bulan),
  - metrik reviewed/reject rate/avg review/auto expired.
- Command Telegram laporan SLA mingguan:
  - `telegram:send-weekly-sla-report`
  - dijadwalkan mingguan via scheduler.
- Dokumentasi operasional SLA:
  - `docs/approval-sla-runbook.md`
- Test command escalation SLA:
  - `tests/Feature/ApprovalSlaEscalationCommandTest.php`
  - mencakup policy assign L2/L3 + lock/cooldown.
- Endpoint preview Telegram weekly SLA (tanpa kirim pesan) di halaman Notifikasi Telegram.
- Endpoint backup/restore runtime config:
  - export JSON `approval_rules` + RBAC mapping,
  - import JSON untuk rollback cepat konfigurasi.
- Panel observability `Last Escalation Run` di Approval Queue.
- Test endpoint baru:
  - `tests/Feature/RuntimeConfigAndTelegramPreviewTest.php`.
- System safety & operations package:
  - Guard command destruktif untuk `migrate:fresh`, `db:wipe`, `migrate:refresh` (2-step confirmation di local/dev, blok total di non-dev).
  - Backup/restore command aman:
    - `db:backup-safe`
    - `db:restore-safe`
  - Dashboard health sistem:
    - route `/admin/system-health`
    - command `ops:health-check` + alert Telegram jika anomali.
  - RBAC governance lanjutan:
    - tabel `role_permission_grants` untuk permission sementara berbasis user/role + expiry.
    - command `rbac:expire-temp-grants`.
    - command `rbac:monthly-review-reminder`.
  - Audit tamper-evidence sederhana:
    - command `audit:seal` untuk hash chain metadata audit.
  - CI automation role-based UAT:
    - `.github/workflows/uat-role-automation.yml`.
- Test coverage baru:
  - `tests/Feature/SystemSafetyAndOpsTest.php`.
- RBAC temporary grants enhancement:
  - sort by expire date (nearest/farthest),
  - risk quick chips (`Expiring <24j`, `Expired tapi aktif`, `Tanpa reason`).
- Approval Queue: SLA target visual per tipe aksi (`refund/void/export`) di card detail request.
- Auto-revocation policy tambahan untuk temporary grant:
  - revoke otomatis saat role user berubah,
  - revoke otomatis saat user dihapus,
  - keduanya tercatat di audit log.
- Disaster recovery drill bulanan:
  - command `ops:drill-disaster-recovery --staging`,
  - dokumentasi `docs/disaster-recovery-drill.md`.
- Ops alert dedup + escalation:
  - dedup alert Telegram berbasis fingerprint,
  - escalation level berdasarkan consecutive unhealthy checks.
- Approval reviewer productivity:
  - template aksi cepat (approve/reject/snooze) di Approval Queue.
  - SLA capacity cards per hari dan jam (14 hari) untuk workload planning.
- Incident timeline:
  - panel kronologi insiden operasional di halaman Audit Log.
- Permission simulation mode (owner):
  - simulate user/role dalam mode read-only untuk verifikasi akses UI/RBAC.
- Recovery one-click pack:
  - command `ops:recovery-pack --staging --telegram`.

### Changed
- POS checkout kini menyimpan `branch_id` dari user, dan validasi produk checkout diikat ke cabang user.
- Query utama POS/pengeluaran/produk untuk non-owner sudah menerapkan scope cabang dasar.
- Group route `auth` kini juga memakai middleware `branch.access` agar akses URL langsung lintas cabang ditolak (`404`).
- `SaleController::pendingAttempts` kini ikut scope cabang untuk non-owner.
- `cashier_audit_logs` kini memiliki `branch_id` + auto-assign saat create (dari user login / `user_id`) sehingga seluruh panel audit, dashboard, dan system health benar-benar terfilter per cabang.
- Dashboard anomaly ack/notified state kini di-namespace per-cabang (non-owner) agar status alert cabang A tidak mempengaruhi cabang B.
- Ringkasan follow-up di dashboard manager kini menerapkan scope cabang untuk data `today/overdue`.
- `audit_alert_states` kini punya kolom `branch_id` + unique (`key`, `branch_id`) agar state anomaly tersimpan native per-cabang (tidak bergantung suffix key).
- Command `telegram:send-daily-summary` kini mengirim ringkasan per cabang aktif (dengan prefix nama cabang) + idempotency marker per tanggal-per cabang di `audit_alert_states`.
- Command `telegram:send-pending-overdue-reminder` kini mengecek/kirim per cabang aktif + cache key harian per cabang.
- Command `telegram:send-followup-reminders` kini hanya memproses follow-up due per cabang aktif dan menandai `reminded_at` tetap per item.
- Command `telegram:send-weekly-sla-report` kini menghitung metrik SLA dan mengirim laporan per cabang aktif.
- Command `approval:send-sla-escalation` kini menyertakan konteks cabang di alert dan auto-assign reviewer mempertimbangkan cabang request.
- Command `approval:send-sla-anomaly-alert` kini evaluasi threshold + cooldown per cabang aktif.
- Command `approval:auto-expire` kini merekap dan mengirim notifikasi auto-expire per cabang serta menyimpan `branch_id` pada audit log terkait.
- Endpoint preview Weekly SLA di halaman Notification Settings kini mengikuti scope cabang user non-owner.
- `approval:send-sla-anomaly-alert` menambahkan bucket `Global` (`branch_id` null) untuk kompatibilitas data legacy/test sambil tetap branch-aware.
- `telegram:send-weekly-sla-report` menambahkan bucket `Global` (`branch_id` null) agar data approval legacy tetap terlaporkan.
- `SaleController` hardening:
  - deduplikasi approval `quick_refund/quick_void` kini ikut `branch_id` transaksi,
  - manager approval untuk partial refund dibatasi per cabang (admin non-owner tidak bisa approve lintas cabang; owner tetap bisa).
- Command reminder Telegram (`pending-overdue` dan `followup-reminders`) kini juga memproses bucket `Global` (`branch_id` null) untuk kompatibilitas data legacy.
- Policy auto-assign escalation dipertegas:
  - L2: prioritaskan admin (fallback owner),
  - L3: owner-only.
- Snooze request pending level escalation 3 diblokir di backend dan UI.
- Notifikasi escalation dilindungi lock proses + cooldown per approval-level untuk hindari spam Telegram.
- Hardening Telegram sender dengan retry + timeout agar lebih tahan pada jaringan intermiten.
- Idempotency anti double-click/race di approval flow:
  - approve/reject single
  - bulk approve/reject
  - execute export single/bulk
  menggunakan lock berbasis cache.
- Pengaturan SLA business hours ditambah guard:
  - fallback saat jam tidak valid (`end <= start`),
  - fallback saat daftar hari kerja kosong.

### Planned
- Tambahkan changelog entry otomatis setiap rilis/deploy.
- Lengkapi dokumentasi arsitektur (sequence alur checkout, refund, stock opname).
- Tambahkan ringkasan migrasi database per versi untuk memudahkan audit perubahan skema.

### Docs
- Checklist rilis multi-cabang ditambahkan di:
  - `docs/release-multi-branch-checklist.md`
