<?php

use App\Models\Branch;
use App\Models\StockTransfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows related branch and blocks unrelated branch for stock transfer export', function () {
    $branchA = Branch::query()->create(['name' => 'Cabang A', 'code' => 'EXPA', 'is_active' => true]);
    $branchB = Branch::query()->create(['name' => 'Cabang B', 'code' => 'EXPB', 'is_active' => true]);
    $branchC = Branch::query()->create(['name' => 'Cabang C', 'code' => 'EXPC', 'is_active' => true]);

    $adminA = User::query()->create([
        'name' => 'Admin A',
        'email' => 'admin.export.a@test.local',
        'password' => 'password',
        'role' => 'admin',
        'branch_id' => $branchA->id,
    ]);
    $adminC = User::query()->create([
        'name' => 'Admin C',
        'email' => 'admin.export.c@test.local',
        'password' => 'password',
        'role' => 'admin',
        'branch_id' => $branchC->id,
    ]);

    $transfer = StockTransfer::query()->create([
        'source_branch_id' => $branchA->id,
        'destination_branch_id' => $branchB->id,
        'requested_by' => $adminA->id,
        'code' => 'TRF-EXP-001',
        'status' => 'requested',
    ]);

    $this->actingAs($adminA)
        ->get(route('stock-transfers.export.csv', ['transferId' => $transfer->id]))
        ->assertOk();

    $this->actingAs($adminA)
        ->get(route('stock-transfers.show', ['transferId' => $transfer->id]))
        ->assertOk();

    $this->actingAs($adminC)
        ->get(route('stock-transfers.export.csv', ['transferId' => $transfer->id]))
        ->assertForbidden();

    $this->actingAs($adminC)
        ->get(route('stock-transfers.show', ['transferId' => $transfer->id]))
        ->assertForbidden();
});
