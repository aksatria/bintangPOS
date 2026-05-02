<?php

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Sale;
use App\Models\User;

test('non owner sees report data only from own branch', function () {
    $branchA = Branch::query()->create([
        'name' => 'Cabang A',
        'code' => 'RBSA',
        'is_active' => true,
    ]);
    $branchB = Branch::query()->create([
        'name' => 'Cabang B',
        'code' => 'RBSB',
        'is_active' => true,
    ]);

    $adminA = User::factory()->create([
        'role' => UserRole::Admin->value,
        'branch_id' => $branchA->id,
    ]);
    $adminB = User::factory()->create([
        'role' => UserRole::Admin->value,
        'branch_id' => $branchB->id,
    ]);

    Sale::query()->create([
        'user_id' => $adminA->id,
        'branch_id' => $branchA->id,
        'invoice_number' => 'INV-BR-A-001',
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
        'user_id' => $adminB->id,
        'branch_id' => $branchB->id,
        'invoice_number' => 'INV-BR-B-001',
        'customer_name' => 'B',
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

    $this->actingAs($adminA)
        ->get(route('reports.index', [
            'period' => 'custom',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->toDateString(),
        ]))
        ->assertOk()
        ->assertSee('INV-BR-A-001')
        ->assertDontSee('INV-BR-B-001');
});

