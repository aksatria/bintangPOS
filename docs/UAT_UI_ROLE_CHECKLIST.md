# UAT UI Checklist Per Role (RBAC)

Dokumen ini dipakai untuk verifikasi manual di browser bahwa tampilan menu dan akses route sudah konsisten dengan RBAC.

Base URL: `http://127.0.0.1:8000`

## Akun Uji

1. Owner: `owner@bintang.test / password`
2. Admin: `admin@bintang.test / password`
3. Kasir: `kasir@bintang.test / password`

## Checklist Owner

1. Login sebagai owner.
2. Sidebar menampilkan:
   - `Dashboard Penjualan`
   - Group `Operasional` (`Point of Sale`, `Laporan`, `Stock Opname`)
   - Group `Master Data` (sesuai permission aktif)
   - Group `Kontrol & Sistem` (termasuk `RBAC Permission`)
3. Buka `/admin/rbac`:
   - Halaman tampil normal (HTTP 200).
4. Buka `/admin/notification-settings`:
   - Halaman tampil normal (HTTP 200).
5. Buka `/reports`:
   - Halaman tampil normal (HTTP 200).

## Checklist Admin

1. Login sebagai admin.
2. Sidebar menampilkan group `Operasional`, `Master Data`, `Kontrol & Sistem` hanya untuk menu yang diizinkan permission.
3. Buka route sensitif tanpa permission (contoh jika dicabut): `/admin/notification-settings`.
   - Harus ditolak `403` (bukan redirect ke tempat lain).
4. Buka `/admin/rbac`:
   - Hanya boleh akses jika punya `permissions.manage`.
   - Jika tidak punya, harus `403`.
5. Uji perubahan permission saat masih login:
   - Cabut salah satu permission admin dari halaman RBAC (dengan owner).
   - Refresh halaman admin yang terkait.
   - Akses harus langsung terblokir (tanpa logout/login ulang).

## Checklist Kasir

1. Login sebagai kasir.
2. Sidebar menampilkan:
   - `Dashboard Penjualan`
   - `Point of Sale`
   - `Profile`
   - `Log Out`
3. Buka `/kasir/pos`:
   - Halaman tampil normal (HTTP 200).
4. Coba akses admin area, contoh:
   - `/admin/categories`
   - `/admin/notification-settings`
   - `/admin/rbac`
   - Semua harus tertolak (`403` atau akses ditolak sesuai middleware).
5. Coba aksi koreksi transaksi:
   - Endpoint quick refund/void harus ditolak (`403`).

## Smoke Route Cepat

1. `/dashboard` harus bisa diakses semua role login.
2. `/profile` harus bisa diakses semua role login.
3. `/sales/pending-attempts/history` harus resolve ke halaman history (bukan ketelan `/sales/{sale}`).

## Kriteria Lulus

1. Menu sidebar per role sesuai permission aktif.
2. Route sensitif menolak akses tanpa permission dengan status `403`.
3. Tidak ada fitur admin yang terlihat/terakses dari kasir.
4. Perubahan permission admin berlaku langsung pada sesi aktif.

## Checklist Final SLA Approval (Owner/Admin/Kasir)

### Owner

1. Buka `Kontrol & Sistem > Approval Queue`.
2. Pastikan panel berikut tampil:
   - Summary SLA,
   - `Reviewer Workload`,
   - `Tren SLA Mingguan`,
   - `Tren SLA Bulanan`.
3. Pastikan card request yang sudah escalation menampilkan:
   - badge `Auto-assigned (Lx)` bila terjadi auto assign,
   - timeline `L1/L2/L3`.
4. Untuk request dengan escalation `L3`:
   - aksi `Snooze` tidak tersedia,
   - muncul pesan wajib `Approve/Reject`.
5. Pastikan panel `Last Escalation Run` tampil:
   - `Waktu Run`, `Checked`, `Sent`, `Cooldown`.
6. Buka `Kontrol & Sistem > Notif Telegram`:
   - klik tombol `Preview Weekly SLA`,
   - pastikan preview teks report tampil tanpa kirim pesan Telegram.
7. Buka `Kontrol & Sistem > Pengaturan Toko`:
   - klik `Export Runtime JSON` dan pastikan file terunduh,
   - uji `Import Runtime JSON` memakai payload valid.

### Admin

1. Buka Approval Queue dan cek data pending/overdue reviewer.
2. Assign reviewer manual tetap berfungsi normal.
3. Uji request L2:
   - sistem auto-assign ke admin ketika command escalation jalan.
4. Verifikasi command:
   - `php artisan approval:send-sla-escalation`
   - tidak menimbulkan spam dobel pada run berulang cepat (cooldown aktif).
5. Verifikasi notifikasi Telegram tetap stabil:
   - request network lambat/intermiten tetap ditangani retry otomatis (tidak langsung gagal sekali kirim).

### Kasir

1. Kasir tidak melihat menu Approval Queue.
2. Akses `/admin/approvals` tetap ditolak.
3. Endpoint approve/reject/assign/snooze approval tidak bisa dipanggil oleh kasir.

## Status UAT Final (Isi Setelah Pengujian)

1. Owner: `PASS` (fitur Approval Queue, SLA trend, reviewer workload, dan guard L3 tervalidasi)
2. Admin: `PASS` (akses sesuai permission + auto-assign L2 ke role admin tervalidasi)
3. Kasir: `PASS` (akses area admin/approval tetap ditolak sesuai RBAC)
4. Catatan:
   - Validasi otomatis:
     - `tests/Feature/ApprovalQueueFlowTest.php` (PASS)
     - `tests/Feature/ApprovalSlaEscalationCommandTest.php` (PASS)
     - `tests/Feature/RuntimeConfigAndTelegramPreviewTest.php` (PASS)
   - Validasi manual yang wajib diulang saat deploy production:
     - login 3 role (`owner/admin/kasir`) di browser production,
     - cek menu/sidebar dan blokir route sensitif sesuai checklist di atas.
