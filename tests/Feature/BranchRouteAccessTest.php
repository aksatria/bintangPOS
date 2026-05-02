<?php

use App\Models\Branch;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('blocks non owner from accessing sale in another branch via route model binding', function () {
    $branchA = Branch::query()->create([
        'name' => 'Cabang A',
        'code' => 'CABA',
        'is_active' => true,
    ]);
    $branchB = Branch::query()->create([
        'name' => 'Cabang B',
        'code' => 'CABB',
        'is_active' => true,
    ]);

    $kasirA = User::query()->create([
        'name' => 'Kasir A',
        'email' => 'kasir.a@test.local',
        'password' => 'password',
        'role' => 'kasir',
        'branch_id' => $branchA->id,
    ]);
    $kasirB = User::query()->create([
        'name' => 'Kasir B',
        'email' => 'kasir.b@test.local',
        'password' => 'password',
        'role' => 'kasir',
        'branch_id' => $branchB->id,
    ]);

    $saleBranchB = Sale::query()->create([
        'user_id' => $kasirB->id,
        'branch_id' => $branchB->id,
        'invoice_number' => 'INV-BRANCH-B-001',
        'subtotal' => 10000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => 10000,
        'paid_amount' => 10000,
        'change_amount' => 0,
        'payment_method' => 'cash',
        'status' => 'paid',
        'sold_at' => now(),
    ]);

    $this->actingAs($kasirA)
        ->get(route('sales.show', $saleBranchB))
        ->assertNotFound();
});

it('allows owner to access sale across branches', function () {
    $branchA = Branch::query()->create([
        'name' => 'Cabang A',
        'code' => 'CABA2',
        'is_active' => true,
    ]);
    $branchB = Branch::query()->create([
        'name' => 'Cabang B',
        'code' => 'CABB2',
        'is_active' => true,
    ]);

    $owner = User::query()->create([
        'name' => 'Owner',
        'email' => 'owner.branch@test.local',
        'password' => 'password',
        'role' => 'owner',
        'branch_id' => $branchA->id,
    ]);
    $kasirB = User::query()->create([
        'name' => 'Kasir B',
        'email' => 'kasir.branch@test.local',
        'password' => 'password',
        'role' => 'kasir',
        'branch_id' => $branchB->id,
    ]);

    $saleBranchB = Sale::query()->create([
        'user_id' => $kasirB->id,
        'branch_id' => $branchB->id,
        'invoice_number' => 'INV-BRANCH-B-002',
        'subtotal' => 10000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => 10000,
        'paid_amount' => 10000,
        'change_amount' => 0,
        'payment_method' => 'cash',
        'status' => 'paid',
        'sold_at' => now(),
    ]);

    $this->actingAs($owner)
        ->get(route('sales.show', $saleBranchB))
        ->assertOk();
});

