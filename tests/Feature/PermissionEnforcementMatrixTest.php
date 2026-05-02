<?php

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerFollowUp;
use App\Models\Expense;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function makeAdminRbacMatrixUser(): User
{
    return User::factory()->create(['role' => UserRole::Admin->value]);
}

function setAdminPermissions(array $codes): void
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

    DB::table('role_permissions')->where('role', 'admin')->delete();

    $permissionIds = DB::table('permissions')
        ->whereIn('code', $codes)
        ->pluck('id')
        ->all();

    foreach ($permissionIds as $permissionId) {
        DB::table('role_permissions')->insert([
            'role' => 'admin',
            'permission_id' => $permissionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

function makeRouteFixtures(User $admin): array
{
    $category = Category::query()->create([
        'name' => 'Kategori Test',
        'description' => 'Kategori untuk test permission',
        'is_active' => true,
    ]);

    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Produk Test',
        'sku' => 'SKU-RBAC-TEST',
        'barcode' => 'BR-RBAC-TEST',
        'purchase_price' => 1000,
        'selling_price' => 2000,
        'stock' => 10,
        'low_stock_threshold' => 2,
        'unit' => 'pcs',
        'is_active' => true,
    ]);

    $expense = Expense::query()->create([
        'user_id' => $admin->id,
        'category' => 'Operasional',
        'title' => 'Expense Test',
        'amount' => 10000,
        'date' => now()->toDateString(),
        'note' => 'Untuk test permission',
    ]);

    $customer = Customer::query()->create([
        'name' => 'Pelanggan Test',
        'phone' => '081234567890',
        'email' => 'pelanggan-test@example.com',
        'address' => 'Alamat test',
        'is_active' => true,
    ]);

    $followup = CustomerFollowUp::query()->create([
        'customer_id' => $customer->id,
        'created_by' => $admin->id,
        'action_type' => 'reminder',
        'status' => 'pending',
        'note' => 'Follow-up test',
        'reminder_at' => now()->addDay(),
    ]);

    return compact('category', 'product', 'expense', 'customer', 'followup');
}

test('admin without master data permission gets 403 on master data routes', function () {
    $admin = makeAdminRbacMatrixUser();
    setAdminPermissions(['reports.view']);

    $this->actingAs($admin)
        ->get(route('admin.categories.index'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('admin.products.index'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('admin.expenses.index'))
        ->assertForbidden();
});

test('admin with master data permission can access master data routes', function () {
    $admin = makeAdminRbacMatrixUser();
    setAdminPermissions(['master-data.manage']);

    $this->actingAs($admin)
        ->get(route('admin.categories.index'))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('admin.products.index'))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('admin.expenses.index'))
        ->assertOk();
});

test('admin without customer followup permission gets 403 on followup queue', function () {
    $admin = makeAdminRbacMatrixUser();
    setAdminPermissions(['customers.manage']);

    $this->actingAs($admin)
        ->get(route('customers.followups'))
        ->assertForbidden();
});

test('admin with customer followup permission can access followup queue', function () {
    $admin = makeAdminRbacMatrixUser();
    setAdminPermissions(['customers.followup.manage']);

    $this->actingAs($admin)
        ->get(route('customers.followups'))
        ->assertOk();
});

test('admin with no allowed submenu sees navigation empty states', function () {
    $admin = makeAdminRbacMatrixUser();
    setAdminPermissions(['reports.view']);

    $this->actingAs($admin)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Belum ada akses modul master data')
        ->assertSee('Belum ada akses modul kontrol sistem');
});

test('admin without proper permission gets 403 on sensitive mutation routes', function () {
    $admin = makeAdminRbacMatrixUser();
    setAdminPermissions(['reports.view']);

    $this->actingAs($admin)
        ->post(route('admin.categories.store'), [])
        ->assertForbidden();

    $this->actingAs($admin)
        ->post(route('admin.expenses.store'), [])
        ->assertForbidden();

    $this->actingAs($admin)
        ->post(route('customers.store'), [])
        ->assertForbidden();

    $this->actingAs($admin)
        ->put(route('notification-settings.update'), [])
        ->assertForbidden();

    $this->actingAs($admin)
        ->put(route('admin.rbac.update'), [])
        ->assertForbidden();
});

test('admin with required permission can pass middleware on sensitive mutation routes', function () {
    $admin = makeAdminRbacMatrixUser();
    setAdminPermissions([
        'master-data.manage',
        'customers.manage',
        'settings.notification.manage',
        'permissions.manage',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.categories.store'), [])
        ->assertStatus(302);

    $this->actingAs($admin)
        ->post(route('customers.store'), [])
        ->assertStatus(302);

    $this->actingAs($admin)
        ->put(route('notification-settings.update'), [])
        ->assertStatus(302);

    $this->actingAs($admin)
        ->put(route('admin.rbac.update'), [])
        ->assertStatus(302);
});

test('forbidden permission response stays 403 in api style json request', function () {
    $admin = makeAdminRbacMatrixUser();
    setAdminPermissions(['reports.view']);

    $this->actingAs($admin)
        ->withHeaders(['Accept' => 'application/json'])
        ->post(route('admin.categories.store'), [])
        ->assertStatus(403)
        ->assertJsonFragment(['message' => 'Anda tidak memiliki izin untuk mengakses fitur ini.']);
});

test('admin without permission gets 403 on delete and patch routes with model binding', function () {
    $admin = makeAdminRbacMatrixUser();
    setAdminPermissions(['reports.view']);
    $fixtures = makeRouteFixtures($admin);

    $this->actingAs($admin)
        ->delete(route('admin.categories.destroy', $fixtures['category']))
        ->assertForbidden();

    $this->actingAs($admin)
        ->delete(route('admin.products.destroy', $fixtures['product']))
        ->assertForbidden();

    $this->actingAs($admin)
        ->delete(route('admin.expenses.destroy', $fixtures['expense']))
        ->assertForbidden();

    $this->actingAs($admin)
        ->patch(route('customers.toggle-active', $fixtures['customer']))
        ->assertForbidden();

    $this->actingAs($admin)
        ->patch(route('customers.followups.status', $fixtures['followup']))
        ->assertForbidden();
});

test('admin with permission passes middleware on delete and patch routes with model binding', function () {
    $admin = makeAdminRbacMatrixUser();
    setAdminPermissions(['master-data.manage', 'customers.manage', 'customers.followup.manage']);
    $fixtures = makeRouteFixtures($admin);

    $this->actingAs($admin)
        ->delete(route('admin.categories.destroy', $fixtures['category']))
        ->assertStatus(302);

    $this->actingAs($admin)
        ->delete(route('admin.products.destroy', $fixtures['product']))
        ->assertStatus(302);

    $this->actingAs($admin)
        ->delete(route('admin.expenses.destroy', $fixtures['expense']))
        ->assertStatus(302);

    $this->actingAs($admin)
        ->patch(route('customers.toggle-active', $fixtures['customer']))
        ->assertStatus(302);

    $this->actingAs($admin)
        ->patch(route('customers.followups.status', $fixtures['followup']))
        ->assertStatus(302);
});

test('active session is blocked immediately after admin permission downgrade', function () {
    $admin = makeAdminRbacMatrixUser();
    setAdminPermissions(['settings.notification.manage']);

    $this->actingAs($admin)
        ->get(route('notification-settings.edit'))
        ->assertOk();

    setAdminPermissions(['reports.view']);

    $this->actingAs($admin)
        ->get(route('notification-settings.edit'))
        ->assertForbidden();
});
