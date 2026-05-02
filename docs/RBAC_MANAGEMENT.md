# RBAC Management Guide

Panduan ini menjelaskan cara mengelola permission role lewat UI admin.

## Lokasi Menu

- URL: `/admin/rbac`
- Nama menu: `Kontrol & Sistem > RBAC Permission`
- Permission akses: `permissions.manage`

## Konsep Singkat

- Role yang dikelola: `owner`, `admin`, `kasir`
- Permission disimpan di tabel:
  - `permissions`
  - `role_permissions`
- Enforcement runtime:
  - middleware `permission:*`
  - helper `User::hasPermission()`

## Operasi Utama

1. Simpan mapping manual
- Centang permission per kolom role.
- Klik `Simpan Mapping RBAC`.

2. Copy cepat antar role
- Tombol:
  - `Copy OWNER -> ADMIN`
  - `Copy ADMIN -> KASIR`
  - `Copy OWNER -> KASIR`

3. Select/Clear cepat per role
- Tombol:
  - `OWNER/ADMIN/KASIR All`
  - `OWNER/ADMIN/KASIR Clear`

4. Reset ke default konfigurasi
- Klik `Reset ke Default`.
- Sistem akan mengambil default dari `config/rbac.php`.

## Audit Trail

Perubahan RBAC dicatat ke `cashier_audit_logs`:
- `rbac_permissions_updated`
- `rbac_permissions_reset_default`

## Command Terkait

- Seed permission:
  - `php artisan db:seed --class=PermissionSeeder`
- Expire temporary grants:
  - `php artisan rbac:expire-temp-grants`
- Reminder review bulanan:
  - `php artisan rbac:monthly-review-reminder`

## Governance Tambahan

- Deny-by-default tetap berlaku jika role sudah punya mapping DB (`role_permissions`) tetapi permission tertentu tidak ada di mapping.
- Permission sementara bisa diberikan melalui tabel `role_permission_grants` dengan kolom:
  - `user_id` (opsional, user spesifik)
  - `role` (opsional, grant berbasis role)
  - `permission_code`
  - `starts_at` / `expires_at`
  - `is_active`
- Grant yang melewati expiry akan nonaktif otomatis via scheduler.

## Catatan Penting

- Untuk route `/admin/*`, user tanpa akses akan diarahkan ke dashboard (`302`) sesuai policy global.
- Jika permission terlihat tidak sinkron setelah deploy, jalankan:
  1. `php artisan config:clear`
  2. `php artisan db:seed --class=PermissionSeeder`
