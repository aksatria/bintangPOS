<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function makeRoleUserForUi(string $role): User
{
    return User::factory()->create(['role' => $role]);
}

function setAdminUiPermissions(array $codes): void
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

    DB::table('role_permissions')->where('role', UserRole::Admin->value)->delete();

    $permissionIds = DB::table('permissions')
        ->whereIn('code', $codes)
        ->pluck('id')
        ->all();

    foreach ($permissionIds as $permissionId) {
        DB::table('role_permissions')->insert([
            'role' => UserRole::Admin->value,
            'permission_id' => $permissionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

test('owner sees full operational and control menus including rbac', function () {
    $owner = makeRoleUserForUi(UserRole::Owner->value);

    $this->actingAs($owner)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSeeText('Operasional')
        ->assertSeeText('Master Data')
        ->assertSeeText('RBAC Permission');
});

test('admin menu follows granted permissions', function () {
    $admin = makeRoleUserForUi(UserRole::Admin->value);
    setAdminUiPermissions(['reports.view']);

    $this->actingAs($admin)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSeeText('Operasional')
        ->assertSeeText('Laporan')
        ->assertDontSeeText('Kategori')
        ->assertDontSeeText('Produk')
        ->assertDontSeeText('Pengguna')
        ->assertDontSeeText('RBAC Permission')
        ->assertSeeText('Belum ada akses modul master data')
        ->assertSeeText('Belum ada akses modul kontrol sistem');
});

test('cashier only sees pos path and cannot access admin routes', function () {
    $cashier = makeRoleUserForUi(UserRole::Cashier->value);

    $this->actingAs($cashier)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSeeText('Point of Sale')
        ->assertDontSeeText('Master Data')
        ->assertDontSeeText('RBAC Permission');

    $this->actingAs($cashier)
        ->get(route('admin.categories.index'))
        ->assertRedirect(route('dashboard'));

    $this->actingAs($cashier)
        ->get(route('notification-settings.edit'))
        ->assertRedirect(route('dashboard'));

    $this->actingAs($cashier)
        ->get(route('admin.rbac.index'))
        ->assertRedirect(route('dashboard'));
});
