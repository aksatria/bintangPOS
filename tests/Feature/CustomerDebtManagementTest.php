<?php

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerDebt;
use App\Models\CustomerDebtPayment;
use App\Models\User;

test('admin can create customer debt and record installment payment', function () {
    $branch = Branch::query()->create([
        'name' => 'Cabang A',
        'code' => 'CBA',
        'is_active' => true,
    ]);

    $admin = User::factory()->create([
        'role' => UserRole::Admin->value,
        'branch_id' => $branch->id,
    ]);

    $customer = Customer::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Pelanggan Termin',
        'phone' => '081222333444',
        'is_active' => true,
    ]);

    $this->actingAs($admin)->post(route('customers.debts.store'), [
        'customer_id' => $customer->id,
        'debt_date' => now()->toDateString(),
        'due_date' => now()->addDays(7)->toDateString(),
        'principal_amount' => 300000,
        'note' => 'Cicilan 2x',
    ])->assertRedirect();

    $debt = CustomerDebt::query()->latest('id')->first();
    expect($debt)->not->toBeNull();
    expect((float) $debt->remaining_amount)->toBe(300000.0);
    expect($debt->status)->toBe('active');

    $this->actingAs($admin)->post(route('customers.debts.pay', $debt), [
        'amount' => 100000,
        'payment_method' => 'cash',
    ])->assertRedirect();

    expect((float) $debt->fresh()->paid_amount)->toBe(100000.0);
    expect((float) $debt->fresh()->remaining_amount)->toBe(200000.0);
    expect(CustomerDebtPayment::query()->where('customer_debt_id', $debt->id)->count())->toBe(1);
});

test('admin cannot pay customer debt from another branch', function () {
    $branchA = Branch::query()->create([
        'name' => 'Cabang A',
        'code' => 'CBA2',
        'is_active' => true,
    ]);
    $branchB = Branch::query()->create([
        'name' => 'Cabang B',
        'code' => 'CBB2',
        'is_active' => true,
    ]);

    $adminA = User::factory()->create([
        'role' => UserRole::Admin->value,
        'branch_id' => $branchA->id,
    ]);

    $customerB = Customer::query()->create([
        'branch_id' => $branchB->id,
        'name' => 'Pelanggan B',
        'phone' => '081200000002',
        'is_active' => true,
    ]);

    $debtB = CustomerDebt::query()->create([
        'branch_id' => $branchB->id,
        'customer_id' => $customerB->id,
        'created_by' => $adminA->id,
        'number' => 'AR-9998',
        'debt_date' => now()->toDateString(),
        'due_date' => now()->addDays(5)->toDateString(),
        'principal_amount' => 200000,
        'paid_amount' => 0,
        'remaining_amount' => 200000,
        'status' => 'active',
    ]);

    $this->actingAs($adminA)->post(route('customers.debts.pay', $debtB), [
        'amount' => 50000,
        'payment_method' => 'cash',
    ])->assertNotFound();

    expect((float) $debtB->fresh()->paid_amount)->toBe(0.0);
    expect((float) $debtB->fresh()->remaining_amount)->toBe(200000.0);
    expect(CustomerDebtPayment::query()->where('customer_debt_id', $debtB->id)->count())->toBe(0);
});

test('owner can pay customer debt from another branch', function () {
    $branchA = Branch::query()->create([
        'name' => 'Cabang OA',
        'code' => 'COA',
        'is_active' => true,
    ]);
    $branchB = Branch::query()->create([
        'name' => 'Cabang OB',
        'code' => 'COB',
        'is_active' => true,
    ]);

    $owner = User::factory()->create([
        'role' => UserRole::Owner->value,
        'branch_id' => $branchA->id,
    ]);

    $customerB = Customer::query()->create([
        'branch_id' => $branchB->id,
        'name' => 'Pelanggan Owner B',
        'phone' => '081200000099',
        'is_active' => true,
    ]);

    $debtB = CustomerDebt::query()->create([
        'branch_id' => $branchB->id,
        'customer_id' => $customerB->id,
        'created_by' => $owner->id,
        'number' => 'AR-9997',
        'debt_date' => now()->toDateString(),
        'due_date' => now()->addDays(5)->toDateString(),
        'principal_amount' => 250000,
        'paid_amount' => 0,
        'remaining_amount' => 250000,
        'status' => 'active',
    ]);

    $this->actingAs($owner)->post(route('customers.debts.pay', $debtB), [
        'amount' => 50000,
        'payment_method' => 'cash',
    ])->assertRedirect();

    expect((float) $debtB->fresh()->paid_amount)->toBe(50000.0);
    expect((float) $debtB->fresh()->remaining_amount)->toBe(200000.0);
});
