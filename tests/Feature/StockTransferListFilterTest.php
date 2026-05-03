<?php

use App\Models\Branch;
use App\Models\StockTransfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('filters stock transfer list by date status and branches', function () {
    $branchA = Branch::query()->create(['name' => 'Pusat', 'code' => 'PUSATX', 'is_active' => true]);
    $branchB = Branch::query()->create(['name' => 'Surabaya', 'code' => 'SBYX', 'is_active' => true]);
    $branchC = Branch::query()->create(['name' => 'Bandung', 'code' => 'BDGX', 'is_active' => true]);

    $owner = User::query()->create([
        'name' => 'Owner Filter',
        'email' => 'owner.filter@test.local',
        'password' => 'password',
        'role' => 'owner',
        'branch_id' => $branchA->id,
    ]);

    StockTransfer::query()->create([
        'source_branch_id' => $branchA->id,
        'destination_branch_id' => $branchB->id,
        'requested_by' => $owner->id,
        'code' => 'TRF-FILTER-001',
        'status' => 'requested',
        'created_at' => now()->subDays(5),
        'updated_at' => now()->subDays(5),
    ]);

    StockTransfer::query()->create([
        'source_branch_id' => $branchA->id,
        'destination_branch_id' => $branchB->id,
        'requested_by' => $owner->id,
        'code' => 'TRF-FILTER-002',
        'status' => 'approved',
        'created_at' => now()->subDays(1),
        'updated_at' => now()->subDays(1),
    ]);

    StockTransfer::query()->create([
        'source_branch_id' => $branchC->id,
        'destination_branch_id' => $branchB->id,
        'requested_by' => $owner->id,
        'code' => 'TRF-FILTER-003',
        'status' => 'approved',
        'created_at' => now()->subDays(1),
        'updated_at' => now()->subDays(1),
    ]);

    $this->actingAs($owner)
        ->get(route('stock-transfers.index', [
            'status' => 'approved',
            'date_from' => now()->subDays(2)->toDateString(),
            'date_to' => now()->toDateString(),
            'source_branch_id' => $branchA->id,
            'destination_branch_id' => $branchB->id,
        ]))
        ->assertOk()
        ->assertSee('TRF-FILTER-002')
        ->assertDontSee('TRF-FILTER-001')
        ->assertDontSee('TRF-FILTER-003');
});

it('supports quick preset pending filter on stock transfer list', function () {
    $branchA = Branch::query()->create(['name' => 'Pusat Q', 'code' => 'PUSATQ', 'is_active' => true]);
    $branchB = Branch::query()->create(['name' => 'Surabaya Q', 'code' => 'SBYQ', 'is_active' => true]);

    $owner = User::query()->create([
        'name' => 'Owner Quick',
        'email' => 'owner.quick@test.local',
        'password' => 'password',
        'role' => 'owner',
        'branch_id' => $branchA->id,
    ]);

    StockTransfer::query()->create([
        'source_branch_id' => $branchA->id,
        'destination_branch_id' => $branchB->id,
        'requested_by' => $owner->id,
        'code' => 'TRF-QUICK-REQ',
        'status' => 'requested',
    ]);
    StockTransfer::query()->create([
        'source_branch_id' => $branchA->id,
        'destination_branch_id' => $branchB->id,
        'requested_by' => $owner->id,
        'code' => 'TRF-QUICK-APP',
        'status' => 'approved',
    ]);

    $this->actingAs($owner)
        ->get(route('stock-transfers.index', ['quick' => 'pending']))
        ->assertOk()
        ->assertSee('TRF-QUICK-REQ')
        ->assertDontSee('TRF-QUICK-APP');
});
