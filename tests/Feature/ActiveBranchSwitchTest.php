<?php

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\CashierAuditLog;
use App\Models\Sale;
use App\Models\User;

test('owner can switch active branch from session context', function () {
    $branchA = Branch::query()->create([
        'name' => 'Cabang A',
        'code' => 'SWA',
        'is_active' => true,
    ]);
    $branchB = Branch::query()->create([
        'name' => 'Cabang B',
        'code' => 'SWB',
        'is_active' => true,
    ]);

    $owner = User::factory()->create([
        'role' => UserRole::Owner->value,
        'branch_id' => $branchA->id,
    ]);

    $userA = User::factory()->create([
        'role' => UserRole::Admin->value,
        'branch_id' => $branchA->id,
    ]);
    $userB = User::factory()->create([
        'role' => UserRole::Admin->value,
        'branch_id' => $branchB->id,
    ]);

    Sale::query()->create([
        'user_id' => $userA->id,
        'branch_id' => $branchA->id,
        'invoice_number' => 'INV-SWA-001',
        'customer_name' => 'A',
        'subtotal' => 10000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'rounding_amount' => 0,
        'admin_fee_amount' => 0,
        'total_amount' => 10000,
        'paid_amount' => 10000,
        'change_amount' => 0,
        'payment_method' => 'cash',
        'status' => 'paid',
        'sold_at' => now(),
    ]);
    Sale::query()->create([
        'user_id' => $userB->id,
        'branch_id' => $branchB->id,
        'invoice_number' => 'INV-SWB-001',
        'customer_name' => 'B',
        'subtotal' => 12000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'rounding_amount' => 0,
        'admin_fee_amount' => 0,
        'total_amount' => 12000,
        'paid_amount' => 12000,
        'change_amount' => 0,
        'payment_method' => 'cash',
        'status' => 'paid',
        'sold_at' => now(),
    ]);

    $this->actingAs($owner)
        ->get(route('reports.index', [
            'period' => 'custom',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->toDateString(),
        ]))
        ->assertOk()
        ->assertSee('INV-SWA-001')
        ->assertDontSee('INV-SWB-001');

    $this->actingAs($owner)
        ->post(route('context.active-branch.update'), [
            'branch_id' => $branchB->id,
        ])
        ->assertRedirect();

    expect(CashierAuditLog::query()->where('action', 'active_branch_switched')->exists())->toBeTrue();

    $this->actingAs($owner)
        ->get(route('reports.index', [
            'period' => 'custom',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->toDateString(),
        ]))
        ->assertOk()
        ->assertSee('INV-SWB-001')
        ->assertDontSee('INV-SWA-001');
});
