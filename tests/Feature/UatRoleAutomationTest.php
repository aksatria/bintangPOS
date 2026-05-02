<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function makeUatUser(string $role): User
{
    return User::factory()->create(['role' => $role]);
}

function setUatAdminPermissions(array $codes): void
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
    $permissionIds = DB::table('permissions')->whereIn('code', $codes)->pluck('id')->all();
    foreach ($permissionIds as $permissionId) {
        DB::table('role_permissions')->insert([
            'role' => UserRole::Admin->value,
            'permission_id' => $permissionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

test('uat owner can access control system modules including approvals and settings', function () {
    $owner = makeUatUser(UserRole::Owner->value);

    $this->actingAs($owner)->get(route('admin.approvals.index'))->assertOk();
    $this->actingAs($owner)->get(route('store-settings.edit'))->assertOk();
    $this->actingAs($owner)->get(route('notification-settings.edit'))->assertOk();
    $this->actingAs($owner)->get(route('admin.rbac.index'))->assertOk();
});

test('uat admin access follows permission matrix in active session', function () {
    $admin = makeUatUser(UserRole::Admin->value);
    setUatAdminPermissions(['reports.view', 'approvals.manage']);

    $this->actingAs($admin)->get(route('admin.approvals.index'))->assertOk();
    $this->actingAs($admin)->get(route('store-settings.edit'))->assertForbidden();

    setUatAdminPermissions(['reports.view']);
    $this->actingAs($admin)->get(route('admin.approvals.index'))->assertForbidden();
});

test('uat cashier cannot access control system routes and endpoints', function () {
    $cashier = makeUatUser(UserRole::Cashier->value);

    $this->actingAs($cashier)->get(route('admin.approvals.index'))->assertRedirect(route('dashboard'));
    $this->actingAs($cashier)->get(route('store-settings.edit'))->assertRedirect(route('dashboard'));
    $this->actingAs($cashier)->post(route('notification-settings.preview-weekly-sla'))->assertRedirect(route('dashboard'));
});

