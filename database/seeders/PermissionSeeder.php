<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissionDefinitions = [
            ['code' => 'dashboard.view', 'name' => 'Lihat Dashboard', 'group' => 'dashboard'],
            ['code' => 'pos.access', 'name' => 'Akses POS', 'group' => 'pos'],
            ['code' => 'pos.checkout', 'name' => 'Checkout POS', 'group' => 'pos'],
            ['code' => 'sales.view', 'name' => 'Lihat Penjualan', 'group' => 'sales'],
            ['code' => 'sales.pending.settle', 'name' => 'Settle Pending', 'group' => 'sales'],
            ['code' => 'sales.correction.manage', 'name' => 'Koreksi Penjualan (refund/void)', 'group' => 'sales'],
            ['code' => 'reports.view', 'name' => 'Lihat Laporan', 'group' => 'reports'],
            ['code' => 'reports.export', 'name' => 'Export Laporan', 'group' => 'reports'],
            ['code' => 'customers.manage', 'name' => 'Kelola Pelanggan', 'group' => 'customers'],
            ['code' => 'customers.followup.manage', 'name' => 'Kelola Follow-up Pelanggan', 'group' => 'customers'],
            ['code' => 'stock-opname.view', 'name' => 'Lihat Stock Opname', 'group' => 'stock-opname'],
            ['code' => 'stock-opname.manage', 'name' => 'Kelola Stock Opname', 'group' => 'stock-opname'],
            ['code' => 'audit-logs.view', 'name' => 'Lihat Audit Log', 'group' => 'audit'],
            ['code' => 'master-data.manage', 'name' => 'Kelola Master Data', 'group' => 'master-data'],
            ['code' => 'users.manage', 'name' => 'Kelola Pengguna', 'group' => 'users'],
            ['code' => 'permissions.manage', 'name' => 'Kelola RBAC Permission', 'group' => 'users'],
            ['code' => 'settings.store.manage', 'name' => 'Kelola Pengaturan Toko', 'group' => 'settings'],
            ['code' => 'settings.notification.manage', 'name' => 'Kelola Notifikasi', 'group' => 'settings'],
            ['code' => 'approvals.manage', 'name' => 'Kelola Approval Queue', 'group' => 'settings'],
        ];

        foreach ($permissionDefinitions as $permission) {
            Permission::query()->updateOrCreate(
                ['code' => $permission['code']],
                [
                    'name' => $permission['name'],
                    'group' => $permission['group'],
                    'description' => $permission['name'],
                ]
            );
        }

        $roleDefaults = (array) config('rbac.role_defaults', []);
        foreach ($roleDefaults as $role => $codes) {
            if (! is_array($codes)) {
                continue;
            }

            if (in_array('*', $codes, true)) {
                $codes = Permission::query()->pluck('code')->all();
            }

            $permissionIds = Permission::query()
                ->whereIn('code', $codes)
                ->pluck('id');

            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role' => $role, 'permission_id' => $permissionId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }
}
