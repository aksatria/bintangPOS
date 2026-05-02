<?php

use App\Enums\UserRole;
use App\Models\CashierAuditLog;
use App\Models\User;

function makeAuditUser(string $role): User
{
    return User::factory()->create(['role' => $role]);
}

test('owner can open audit logs page and see expense quick filters', function () {
    $owner = makeAuditUser(UserRole::Owner->value);

    $this->actingAs($owner)
        ->get(route('audit-logs.index'))
        ->assertOk()
        ->assertSee('Audit Log Kasir')
        ->assertSee('Pengeluaran Ditambah')
        ->assertSee('Pengeluaran Diubah')
        ->assertSee('Pengeluaran Dihapus')
        ->assertSee('Pengeluaran Duplikasi');
});

test('cashier cannot access audit logs page', function () {
    $cashier = makeAuditUser(UserRole::Cashier->value);

    $this->actingAs($cashier)
        ->get(route('audit-logs.index'))
        ->assertForbidden();
});

test('audit log filter by expense action works', function () {
    $admin = makeAuditUser(UserRole::Admin->value);

    CashierAuditLog::query()->create([
        'user_id' => $admin->id,
        'action' => 'expense_created',
        'context' => ['expense_id' => 11, 'title' => 'Biaya Test A'],
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
    ]);

    CashierAuditLog::query()->create([
        'user_id' => $admin->id,
        'action' => 'report_export_excel',
        'context' => ['selected_count' => 1, 'selected_total' => 10000],
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
    ]);

    $this->actingAs($admin)
        ->get(route('audit-logs.index', [
            'from' => now()->subDay()->toDateString(),
            'to' => now()->addDay()->toDateString(),
            'action' => 'expense_created',
        ]))
        ->assertOk()
        ->assertSee('Pengeluaran Ditambah')
        ->assertSee('Aksi Pengeluaran')
        ->assertSee('1');
});

test('audit log exports excel and pdf respond successfully', function () {
    $owner = makeAuditUser(UserRole::Owner->value);

    CashierAuditLog::query()->create([
        'user_id' => $owner->id,
        'action' => 'expense_updated',
        'context' => ['expense_id' => 25, 'title' => 'Biaya Test B'],
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
    ]);

    $this->actingAs($owner)
        ->get(route('audit-logs.export.excel', [
            'from' => now()->toDateString(),
            'to' => now()->toDateString(),
        ]))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $this->actingAs($owner)
        ->get(route('audit-logs.export.pdf', [
            'from' => now()->toDateString(),
            'to' => now()->toDateString(),
        ]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('all audit log quick filter chips respond successfully', function () {
    $owner = makeAuditUser(UserRole::Owner->value);

    $base = [
        'from' => now()->subMonths(6)->toDateString(),
        'to' => now()->toDateString(),
        'user_id' => 0,
        'action' => '',
        'q' => '',
    ];

    $queries = [
        ['export_type' => 'excel'],
        ['export_type' => 'pdf'],
        ['export_type' => 'mass'],
        ['export_type' => 'mass', 'sort' => 'export_priority', 'action' => ''],
        ['sort' => 'export_priority'],
        ['action' => 'customer_merged'],
        ['action' => 'checkout_failed_client'],
        ['action' => 'stock_sync_adjusted'],
        ['action' => 'expense_created'],
        ['action' => 'expense_updated'],
        ['action' => 'expense_deleted'],
        ['action' => 'expense_duplicated'],
        ['action' => 'report_export_excel'],
        ['action' => 'report_export_pdf'],
    ];

    foreach ($queries as $query) {
        $this->actingAs($owner)
            ->get(route('audit-logs.index', array_merge($base, $query)))
            ->assertOk()
            ->assertSee('Audit Log Kasir')
            ->assertSee('Aktivitas Kasir');
    }
});
