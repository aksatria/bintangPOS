<?php

use App\Models\Branch;
use App\Models\CashierAuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('supports stock transfer request approve and receive across branches', function () {
    $category = Category::query()->create([
        'name' => 'Antibiotik',
        'is_active' => true,
    ]);

    $branchA = Branch::query()->create([
        'name' => 'Cabang A',
        'code' => 'CBA',
        'is_active' => true,
    ]);
    $branchB = Branch::query()->create([
        'name' => 'Cabang B',
        'code' => 'CBB',
        'is_active' => true,
    ]);

    $adminA = User::query()->create([
        'name' => 'Admin A',
        'email' => 'admin.a@local.test',
        'password' => 'password',
        'role' => 'admin',
        'branch_id' => $branchA->id,
    ]);
    $adminB = User::query()->create([
        'name' => 'Admin B',
        'email' => 'admin.b@local.test',
        'password' => 'password',
        'role' => 'admin',
        'branch_id' => $branchB->id,
    ]);

    $productA = Product::query()->create([
        'branch_id' => $branchA->id,
        'category_id' => $category->id,
        'name' => 'Amoxicillin 500',
        'sku' => 'AMX-500',
        'barcode' => '8990001112223',
        'purchase_price' => 5000,
        'selling_price' => 7000,
        'stock' => 10,
        'low_stock_threshold' => 2,
        'unit' => 'strip',
        'is_active' => true,
    ]);

    $this->actingAs($adminA)
        ->post(route('stock-transfers.store'), [
            'destination_branch_id' => $branchB->id,
            'note' => 'Restok cabang B',
            'delivery_ref' => 'SJ-TRF-001',
            'courier_name' => 'Kurir Internal',
            'items' => [
                ['product_id' => $productA->id, 'qty' => 5],
            ],
        ])
        ->assertSessionHas('status');

    $transfer = StockTransfer::query()->latest('id')->first();
    expect($transfer)->not->toBeNull();
    expect($transfer->status)->toBe(StockTransfer::STATUS_REQUESTED);
    expect((string) $transfer->delivery_ref)->toBe('SJ-TRF-001');
    expect((string) $transfer->courier_name)->toBe('Kurir Internal');
    expect($transfer->items()->count())->toBe(1);

    $this->actingAs($adminB)
        ->post(route('stock-transfers.approve', ['transferId' => $transfer->id]))
        ->assertSessionHas('status');

    expect($transfer->fresh()->status)->toBe(StockTransfer::STATUS_APPROVED);

    $this->actingAs($adminB)
        ->post(route('stock-transfers.receive', ['transferId' => $transfer->id]))
        ->assertSessionHas('status');

    $transfer = $transfer->fresh();
    expect($transfer->status)->toBe(StockTransfer::STATUS_RECEIVED);
    expect((int) $productA->fresh()->stock)->toBe(5);

    $destProduct = Product::query()->where('branch_id', $branchB->id)->where('name', 'Amoxicillin 500')->first();
    expect($destProduct)->not->toBeNull();
    expect((int) $destProduct->stock)->toBe(5);

    expect(CashierAuditLog::query()->where('action', 'stock_transfer_requested')->exists())->toBeTrue();
    expect(CashierAuditLog::query()->where('action', 'stock_transfer_approved')->exists())->toBeTrue();
    expect(CashierAuditLog::query()->where('action', 'stock_transfer_received')->exists())->toBeTrue();
});

it('supports partial receive and stores discrepancy reason', function () {
    $category = Category::query()->create([
        'name' => 'Antibiotik Parsial',
        'is_active' => true,
    ]);

    $branchA = Branch::query()->create(['name' => 'Cabang A2', 'code' => 'CBA2X', 'is_active' => true]);
    $branchB = Branch::query()->create(['name' => 'Cabang B2', 'code' => 'CBB2X', 'is_active' => true]);

    $adminA = User::query()->create([
        'name' => 'Admin A2',
        'email' => 'admin.a2@local.test',
        'password' => 'password',
        'role' => 'admin',
        'branch_id' => $branchA->id,
    ]);
    $adminB = User::query()->create([
        'name' => 'Admin B2',
        'email' => 'admin.b2@local.test',
        'password' => 'password',
        'role' => 'admin',
        'branch_id' => $branchB->id,
    ]);

    $productA = Product::query()->create([
        'branch_id' => $branchA->id,
        'category_id' => $category->id,
        'name' => 'Cefixime 200',
        'sku' => 'CFX-200',
        'barcode' => '8990001113334',
        'purchase_price' => 5000,
        'selling_price' => 7000,
        'stock' => 10,
        'low_stock_threshold' => 2,
        'unit' => 'strip',
        'is_active' => true,
    ]);

    $this->actingAs($adminA)
        ->post(route('stock-transfers.store'), [
            'destination_branch_id' => $branchB->id,
            'items' => [
                ['product_id' => $productA->id, 'qty' => 8],
            ],
        ])
        ->assertSessionHas('status');

    $transfer = StockTransfer::query()->latest('id')->first();
    $item = $transfer->items()->first();

    $this->actingAs($adminB)
        ->post(route('stock-transfers.approve', ['transferId' => $transfer->id]))
        ->assertSessionHas('status');

    $this->actingAs($adminB)
        ->post(route('stock-transfers.receive', ['transferId' => $transfer->id]), [
            'items' => [
                [
                    'id' => $item->id,
                    'received_qty' => 6,
                    'discrepancy_reason' => '2 strip rusak saat pengiriman',
                ],
            ],
            'receive_note' => 'Diterima parsial',
        ])
        ->assertSessionHas('status');

    $item = $item->fresh();
    expect((int) $item->received_qty)->toBe(6);
    expect((string) $item->discrepancy_reason)->toBe('2 strip rusak saat pengiriman');
    expect((int) $productA->fresh()->stock)->toBe(4);
});

it('releases reserved stock when approved transfer is cancelled', function () {
    $category = Category::query()->create(['name' => 'Cancel Reserve', 'is_active' => true]);
    $branchA = Branch::query()->create(['name' => 'Pusat CR', 'code' => 'PUSCR', 'is_active' => true]);
    $branchB = Branch::query()->create(['name' => 'Tujuan CR', 'code' => 'TUJCR', 'is_active' => true]);

    $adminA = User::query()->create([
        'name' => 'Admin CR A',
        'email' => 'admin.cra@local.test',
        'password' => 'password',
        'role' => 'admin',
        'branch_id' => $branchA->id,
    ]);
    $adminB = User::query()->create([
        'name' => 'Admin CR B',
        'email' => 'admin.crb@local.test',
        'password' => 'password',
        'role' => 'admin',
        'branch_id' => $branchB->id,
    ]);

    $productA = Product::query()->create([
        'branch_id' => $branchA->id,
        'category_id' => $category->id,
        'name' => 'Paracetamol CR',
        'sku' => 'PCR-100',
        'barcode' => '8990005551111',
        'purchase_price' => 2000,
        'selling_price' => 3000,
        'stock' => 20,
        'low_stock_threshold' => 2,
        'unit' => 'strip',
        'is_active' => true,
    ]);

    $this->actingAs($adminA)->post(route('stock-transfers.store'), [
        'destination_branch_id' => $branchB->id,
        'items' => [['product_id' => $productA->id, 'qty' => 7]],
    ])->assertSessionHas('status');

    $transfer = StockTransfer::query()->latest('id')->firstOrFail();
    $item = $transfer->items()->firstOrFail();

    $this->actingAs($adminB)
        ->post(route('stock-transfers.approve', ['transferId' => $transfer->id]))
        ->assertSessionHas('status');

    expect((int) $productA->fresh()->stock)->toBe(13);
    expect((int) $item->fresh()->reserved_qty)->toBe(7);

    $this->actingAs($adminA)
        ->post(route('stock-transfers.cancel', ['transferId' => $transfer->id]))
        ->assertSessionHas('status');

    expect($transfer->fresh()->status)->toBe(StockTransfer::STATUS_CANCELLED);
    expect((int) $productA->fresh()->stock)->toBe(20);
    expect((int) $item->fresh()->reserved_qty)->toBe(0);
});

it('stores dispatch and receive proofs when files are valid', function () {
    Storage::fake('public');

    $category = Category::query()->create(['name' => 'Proof Valid', 'is_active' => true]);
    $branchA = Branch::query()->create(['name' => 'Pusat PV', 'code' => 'PUSPV', 'is_active' => true]);
    $branchB = Branch::query()->create(['name' => 'Tujuan PV', 'code' => 'TUJPV', 'is_active' => true]);

    $adminA = User::query()->create([
        'name' => 'Admin PV A',
        'email' => 'admin.pva@local.test',
        'password' => 'password',
        'role' => 'admin',
        'branch_id' => $branchA->id,
    ]);
    $adminB = User::query()->create([
        'name' => 'Admin PV B',
        'email' => 'admin.pvb@local.test',
        'password' => 'password',
        'role' => 'admin',
        'branch_id' => $branchB->id,
    ]);

    $productA = Product::query()->create([
        'branch_id' => $branchA->id,
        'category_id' => $category->id,
        'name' => 'Ibuprofen PV',
        'sku' => 'IBU-PV',
        'barcode' => '8990007771111',
        'purchase_price' => 3000,
        'selling_price' => 4500,
        'stock' => 10,
        'low_stock_threshold' => 2,
        'unit' => 'strip',
        'is_active' => true,
    ]);

    $this->actingAs($adminA)->post(route('stock-transfers.store'), [
        'destination_branch_id' => $branchB->id,
        'dispatch_proof' => UploadedFile::fake()->image('dispatch.jpg'),
        'items' => [['product_id' => $productA->id, 'qty' => 5]],
    ])->assertSessionHas('status');

    $transfer = StockTransfer::query()->latest('id')->firstOrFail();
    expect($transfer->dispatch_proof_path)->not->toBeNull();
    Storage::disk('public')->assertExists($transfer->dispatch_proof_path);

    $this->actingAs($adminB)->post(route('stock-transfers.approve', ['transferId' => $transfer->id]))
        ->assertSessionHas('status');

    $item = $transfer->items()->firstOrFail();
    $this->actingAs($adminB)->post(route('stock-transfers.receive', ['transferId' => $transfer->id]), [
        'items' => [[
            'id' => $item->id,
            'received_qty' => 5,
        ]],
        'receive_proof' => UploadedFile::fake()->create('receive.pdf', 200, 'application/pdf'),
    ])->assertSessionHas('status');

    $transfer = $transfer->fresh();
    expect($transfer->receive_proof_path)->not->toBeNull();
    Storage::disk('public')->assertExists($transfer->receive_proof_path);
});

it('rejects invalid proof file types on request and receive', function () {
    Storage::fake('public');

    $category = Category::query()->create(['name' => 'Proof Invalid', 'is_active' => true]);
    $branchA = Branch::query()->create(['name' => 'Pusat PI', 'code' => 'PUSPI', 'is_active' => true]);
    $branchB = Branch::query()->create(['name' => 'Tujuan PI', 'code' => 'TUJPI', 'is_active' => true]);

    $adminA = User::query()->create([
        'name' => 'Admin PI A',
        'email' => 'admin.pia@local.test',
        'password' => 'password',
        'role' => 'admin',
        'branch_id' => $branchA->id,
    ]);
    $adminB = User::query()->create([
        'name' => 'Admin PI B',
        'email' => 'admin.pib@local.test',
        'password' => 'password',
        'role' => 'admin',
        'branch_id' => $branchB->id,
    ]);

    $productA = Product::query()->create([
        'branch_id' => $branchA->id,
        'category_id' => $category->id,
        'name' => 'Cetirizine PI',
        'sku' => 'CTZ-PI',
        'barcode' => '8990008881111',
        'purchase_price' => 2500,
        'selling_price' => 4000,
        'stock' => 9,
        'low_stock_threshold' => 2,
        'unit' => 'strip',
        'is_active' => true,
    ]);

    $this->actingAs($adminA)->post(route('stock-transfers.store'), [
        'destination_branch_id' => $branchB->id,
        'dispatch_proof' => UploadedFile::fake()->create('dispatch.txt', 10, 'text/plain'),
        'items' => [['product_id' => $productA->id, 'qty' => 2]],
    ])->assertSessionHasErrors('dispatch_proof');

    $this->actingAs($adminA)->post(route('stock-transfers.store'), [
        'destination_branch_id' => $branchB->id,
        'items' => [['product_id' => $productA->id, 'qty' => 2]],
    ])->assertSessionHas('status');

    $transfer = StockTransfer::query()->latest('id')->firstOrFail();
    $this->actingAs($adminB)->post(route('stock-transfers.approve', ['transferId' => $transfer->id]))
        ->assertSessionHas('status');

    $this->actingAs($adminB)->post(route('stock-transfers.receive', ['transferId' => $transfer->id]), [
        'receive_proof' => UploadedFile::fake()->create('receive.txt', 10, 'text/plain'),
    ])->assertSessionHasErrors('receive_proof');
});
