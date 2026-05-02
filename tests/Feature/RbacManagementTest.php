<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function makeRoleUser(string $role): User
{
    return User::factory()->create(['role' => $role]);
}

function upsertPermissions(array $codes): void
{
    foreach ($codes as $code) {
        DB::table('permissions')->updateOrInsert(
            ['code' => $code],
            [
                'name' => $code,
                'group' => 'test',
                'description' => $code,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}

test('owner can access rbac management page', function () {
    $owner = makeRoleUser(UserRole::Owner->value);

    $this->actingAs($owner)
        ->get(route('admin.rbac.index'))
        ->assertOk()
        ->assertSee('RBAC Permission Matrix');
});

test('cashier cannot access rbac management page', function () {
    $cashier = makeRoleUser(UserRole::Cashier->value);

    $this->actingAs($cashier)
        ->get(route('admin.rbac.index'))
        ->assertRedirect(route('dashboard'));
});

test('owner can reset rbac mapping to default configuration', function () {
    $owner = makeRoleUser(UserRole::Owner->value);

    DB::table('permissions')->insertOrIgnore([
        ['code' => 'reports.view', 'name' => 'Lihat Laporan', 'group' => 'reports', 'description' => 'Lihat Laporan', 'created_at' => now(), 'updated_at' => now()],
        ['code' => 'settings.notification.manage', 'name' => 'Kelola Notifikasi', 'group' => 'settings', 'description' => 'Kelola Notifikasi', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $reportPermissionId = (int) DB::table('permissions')->where('code', 'reports.view')->value('id');
    DB::table('role_permissions')->where('role', 'admin')->delete();
    DB::table('role_permissions')->insert([
        'role' => 'admin',
        'permission_id' => $reportPermissionId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($owner)
        ->post(route('admin.rbac.reset-default'))
        ->assertRedirect();

    $adminHasNotification = DB::table('role_permissions')
        ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
        ->where('role_permissions.role', 'admin')
        ->where('permissions.code', 'settings.notification.manage')
        ->exists();

    expect($adminHasNotification)->toBeTrue();
});

test('rbac update writes before after and diff in audit context', function () {
    $owner = makeRoleUser(UserRole::Owner->value);

    upsertPermissions([
        'reports.view',
        'reports.export',
        'settings.notification.manage',
    ]);

    $reportViewId = (int) DB::table('permissions')->where('code', 'reports.view')->value('id');
    DB::table('role_permissions')->insert([
        'role' => 'admin',
        'permission_id' => $reportViewId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($owner)
        ->put(route('admin.rbac.update'), [
            'permissions' => [
                'admin' => ['reports.export'],
            ],
        ])
        ->assertRedirect();

    $log = DB::table('cashier_audit_logs')
        ->where('action', 'rbac_permissions_updated')
        ->latest('id')
        ->first();

    expect($log)->not()->toBeNull();

    $context = json_decode((string) $log->context, true);
    expect($context)->toBeArray()
        ->and(data_get($context, 'source'))->toBe('web')
        ->and(data_get($context, 'before.admin'))->toContain('reports.view')
        ->and(data_get($context, 'after.admin'))->toContain('reports.export')
        ->and(data_get($context, 'changes.admin.added'))->toContain('reports.export')
        ->and(data_get($context, 'changes.admin.removed'))->toContain('reports.view');
});
