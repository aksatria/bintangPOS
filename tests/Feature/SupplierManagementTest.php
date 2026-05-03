<?php

use App\Enums\UserRole;
use App\Models\ApprovalRequest;
use App\Models\Branch;
use App\Models\Category;
use App\Models\CashierAuditLog;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierPurchase;
use App\Models\User;

test('admin can create manual supplier purchase and receive it', function () {
    $branch = Branch::query()->create([
        'name' => 'Pusat',
        'code' => 'PST',
        'is_active' => true,
    ]);

    $admin = User::factory()->create([
        'role' => UserRole::Admin->value,
        'branch_id' => $branch->id,
    ]);

    $category = Category::query()->create([
        'name' => 'Obat',
        'is_active' => true,
    ]);

    $product = Product::query()->create([
        'branch_id' => $branch->id,
        'category_id' => $category->id,
        'name' => 'Vitamin A',
        'sku' => 'VIT-A-001',
        'barcode' => '1234567890123',
        'purchase_price' => 1000,
        'selling_price' => 1500,
        'stock' => 5,
        'low_stock_threshold' => 1,
        'unit' => 'pcs',
        'is_active' => true,
    ]);

    $supplier = Supplier::query()->create([
        'branch_id' => $branch->id,
        'name' => 'PT Supplier Sehat',
        'code' => 'SUP-SEHAT',
        'payment_term_days' => 7,
        'is_active' => true,
    ]);

    $this->actingAs($admin)->post(route('admin.supplier-purchases.store'), [
        'supplier_id' => $supplier->id,
        'ordered_at' => now()->toDateString(),
        'payment_term_days' => 21,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'items' => [
            ['product_name' => 'Vitamin A', 'quantity' => 10, 'unit_cost' => 1200],
        ],
    ])->assertRedirect();

    $purchase = SupplierPurchase::query()->where('supplier_id', $supplier->id)->latest('id')->first();
    expect($purchase)->not->toBeNull();
    expect($purchase->status)->toBe('draft');
    expect($purchase->due_date?->toDateString())->toBe(now()->addDays(21)->toDateString());

    $this->actingAs($admin)->post(route('admin.supplier-purchases.request-approval', $purchase))->assertRedirect();
    $approval = ApprovalRequest::query()->where('type', 'supplier.purchase_approval')->latest('id')->first();
    $this->actingAs($admin)->post(route('admin.approvals.approve', $approval), [
        'review_note' => 'Disetujui untuk penerimaan barang.',
    ])->assertRedirect();

    $this->actingAs($admin)->post(route('admin.supplier-purchases.receive', $purchase))->assertRedirect();

    expect($product->fresh()->stock)->toBe(5);
    expect($purchase->fresh()->status)->toBe('received');
    expect(CashierAuditLog::query()->where('action', 'supplier_purchase_received')->exists())->toBeTrue();
});

test('admin cannot receive supplier purchase from another branch', function () {
    $branchA = Branch::query()->create([
        'name' => 'Cabang 1',
        'code' => 'CB1',
        'is_active' => true,
    ]);
    $branchB = Branch::query()->create([
        'name' => 'Cabang 2',
        'code' => 'CB2',
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

    $category = Category::query()->create([
        'name' => 'Suplemen',
        'is_active' => true,
    ]);

    $productB = Product::query()->create([
        'branch_id' => $branchB->id,
        'category_id' => $category->id,
        'name' => 'Produk Cabang B',
        'sku' => 'SKU-B-01',
        'barcode' => '8800000000001',
        'purchase_price' => 5000,
        'selling_price' => 7000,
        'stock' => 3,
        'low_stock_threshold' => 1,
        'unit' => 'pcs',
        'is_active' => true,
    ]);

    $supplierB = Supplier::query()->create([
        'branch_id' => $branchB->id,
        'name' => 'Supplier B',
        'code' => 'SUP-B',
        'payment_term_days' => 7,
        'is_active' => true,
    ]);

    $purchaseB = SupplierPurchase::query()->create([
        'branch_id' => $branchB->id,
        'supplier_id' => $supplierB->id,
        'created_by' => $adminB->id,
        'number' => 'PO-20990101-0001',
        'status' => 'draft',
        'ordered_at' => now()->toDateString(),
        'due_date' => now()->addDays(7)->toDateString(),
        'subtotal' => 100000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => 100000,
    ]);

    $purchaseB->items()->create([
        'product_id' => $productB->id,
        'product_name' => $productB->name,
        'quantity' => 4,
        'unit_cost' => 6000,
        'line_total' => 24000,
    ]);

    $this->actingAs($adminA)->post(route('admin.supplier-purchases.receive', $purchaseB))
        ->assertNotFound();

    expect($purchaseB->fresh()->status)->toBe('draft');
    expect($productB->fresh()->stock)->toBe(3);
});

test('admin can create supplier with generated code', function () {
    $branch = Branch::query()->create([
        'name' => 'Cabang Auto Supplier',
        'code' => 'CAS',
        'is_active' => true,
    ]);

    $admin = User::factory()->create([
        'role' => UserRole::Admin->value,
        'branch_id' => $branch->id,
    ]);

    $this->actingAs($admin)->post(route('admin.suppliers.store'), [
        'name' => 'PT Auto Kode Supplier',
        'phone' => '021-7771001',
        'email' => 'auto.kode@example.test',
        'address' => 'Jl. Otomatis No. 1',
        'payment_term_days' => 14,
    ])->assertRedirect();

    $supplier = Supplier::query()->where('name', 'PT Auto Kode Supplier')->first();

    expect($supplier)->not->toBeNull();
    expect($supplier->code)->toBe('SUP-CAS-0001');
    expect($supplier->phone)->toBe('021-7771001');
    expect($supplier->email)->toBe('auto.kode@example.test');
    expect($supplier->address)->toBe('Jl. Otomatis No. 1');
});

test('admin can create supplier purchase from bulk typed items', function () {
    $branch = Branch::query()->create([
        'name' => 'Cabang Bulk Supplier',
        'code' => 'CBS',
        'is_active' => true,
    ]);

    $admin = User::factory()->create([
        'role' => UserRole::Admin->value,
        'branch_id' => $branch->id,
    ]);

    $supplier = Supplier::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Supplier Bulk',
        'code' => 'SUP-BULK',
        'payment_term_days' => 7,
        'is_active' => true,
    ]);

    $bulkItems = implode("\n", [
        'Paracetamol 500mg | 1000 | 850',
        'Vitamin C 1000mg | 250 | 1200',
        'Masker Medis | 50 | 18000',
        'Alkohol Swab | 75 | 650',
    ]);

    $this->actingAs($admin)->post(route('admin.supplier-purchases.store'), [
        'supplier_id' => $supplier->id,
        'ordered_at' => now()->toDateString(),
        'bulk_items' => $bulkItems,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'shipping_amount' => 25000,
        'down_payment_amount' => 500000,
        'down_payment_method' => 'transfer',
        'items' => [],
    ])->assertRedirect();

    $purchase = SupplierPurchase::query()->where('supplier_id', $supplier->id)->latest('id')->first();

    expect($purchase)->not->toBeNull();
    expect($purchase->items()->count())->toBe(4);
    expect((float) $purchase->shipping_amount)->toBe(25000.0);
    expect((float) $purchase->total_amount)->toBe((float) (1000 * 850 + 250 * 1200 + 50 * 18000 + 75 * 650 + 25000));
    expect((float) $purchase->paid_amount)->toBe(500000.0);
    expect((float) $purchase->remaining_amount)->toBe((float) ((1000 * 850 + 250 * 1200 + 50 * 18000 + 75 * 650 + 25000) - 500000));
    expect((string) $purchase->payment_status)->toBe('partial');
});

test('admin cannot update supplier from another branch', function () {
    $branchA = Branch::query()->create([
        'name' => 'Cabang Update A',
        'code' => 'CUA',
        'is_active' => true,
    ]);
    $branchB = Branch::query()->create([
        'name' => 'Cabang Update B',
        'code' => 'CUB',
        'is_active' => true,
    ]);

    $adminA = User::factory()->create([
        'role' => UserRole::Admin->value,
        'branch_id' => $branchA->id,
    ]);

    $supplierB = Supplier::query()->create([
        'branch_id' => $branchB->id,
        'name' => 'Supplier Asli B',
        'code' => 'SUP-UPD-B',
        'payment_term_days' => 10,
        'is_active' => true,
    ]);

    $this->actingAs($adminA)->put(route('admin.suppliers.update', $supplierB), [
        'name' => 'Diubah User A',
        'code' => 'SUP-UPD-B',
        'phone' => '081122334455',
        'email' => 'supplierb@example.com',
        'address' => 'Alamat baru',
        'payment_term_days' => 12,
        'note' => 'Catatan baru',
        'is_active' => 1,
    ])->assertNotFound();

    expect($supplierB->fresh()->name)->toBe('Supplier Asli B');
    expect((int) $supplierB->fresh()->payment_term_days)->toBe(10);
});

test('admin cannot delete supplier from another branch', function () {
    $branchA = Branch::query()->create([
        'name' => 'Cabang Delete A',
        'code' => 'CDA',
        'is_active' => true,
    ]);
    $branchB = Branch::query()->create([
        'name' => 'Cabang Delete B',
        'code' => 'CDB',
        'is_active' => true,
    ]);

    $adminA = User::factory()->create([
        'role' => UserRole::Admin->value,
        'branch_id' => $branchA->id,
    ]);

    $supplierB = Supplier::query()->create([
        'branch_id' => $branchB->id,
        'name' => 'Supplier Delete B',
        'code' => 'SUP-DEL-B',
        'payment_term_days' => 5,
        'is_active' => true,
    ]);

    $this->actingAs($adminA)->delete(route('admin.suppliers.destroy', $supplierB))
        ->assertNotFound();

    expect(Supplier::query()->whereKey($supplierB->id)->exists())->toBeTrue();
});

test('admin can pay supplier purchase debt partially and fully', function () {
    $branch = Branch::query()->create([
        'name' => 'Cabang Hutang Supplier',
        'code' => 'CHS',
        'is_active' => true,
    ]);

    $admin = User::factory()->create([
        'role' => UserRole::Admin->value,
        'branch_id' => $branch->id,
    ]);

    $category = Category::query()->create([
        'name' => 'Farmasi',
        'is_active' => true,
    ]);

    $product = Product::query()->create([
        'branch_id' => $branch->id,
        'category_id' => $category->id,
        'name' => 'Antibiotik',
        'sku' => 'ANT-001',
        'barcode' => '8991111111111',
        'purchase_price' => 10000,
        'selling_price' => 13000,
        'stock' => 2,
        'low_stock_threshold' => 1,
        'unit' => 'pcs',
        'is_active' => true,
    ]);

    $supplier = Supplier::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Supplier Hutang',
        'code' => 'SUP-HUTANG',
        'payment_term_days' => 7,
        'is_active' => true,
    ]);

    $this->actingAs($admin)->post(route('admin.supplier-purchases.store'), [
        'supplier_id' => $supplier->id,
        'ordered_at' => now()->toDateString(),
        'discount_amount' => 0,
        'tax_amount' => 0,
        'items' => [
            ['product_name' => 'Antibiotik', 'quantity' => 10, 'unit_cost' => 1200],
        ],
    ])->assertRedirect();

    $purchase = SupplierPurchase::query()->latest('id')->first();
    expect($purchase)->not->toBeNull();

    $this->actingAs($admin)->post(route('admin.supplier-purchases.request-approval', $purchase))->assertRedirect();
    $approval = ApprovalRequest::query()->where('type', 'supplier.purchase_approval')->latest('id')->first();
    $this->actingAs($admin)->post(route('admin.approvals.approve', $approval), [
        'review_note' => 'Disetujui untuk penerimaan barang.',
    ])->assertRedirect();

    $this->actingAs($admin)->post(route('admin.supplier-purchases.receive', $purchase))->assertRedirect();

    $this->actingAs($admin)->post(route('admin.supplier-purchases.pay', $purchase), [
        'amount' => 5000,
        'payment_method' => 'cash',
    ])->assertRedirect();

    expect((float) $purchase->fresh()->paid_amount)->toBe(5000.0);
    expect((float) $purchase->fresh()->remaining_amount)->toBe(7000.0);
    expect((string) $purchase->fresh()->payment_status)->toBe('partial');

    $this->actingAs($admin)->post(route('admin.supplier-purchases.pay', $purchase), [
        'amount' => 7000,
        'payment_method' => 'transfer',
    ])->assertRedirect();

    expect((float) $purchase->fresh()->paid_amount)->toBe(12000.0);
    expect((float) $purchase->fresh()->remaining_amount)->toBe(0.0);
    expect((string) $purchase->fresh()->payment_status)->toBe('paid');
});

test('owner can receive supplier purchase from another branch', function () {
    $branchA = Branch::query()->create([
        'name' => 'Cabang Owner A',
        'code' => 'COA2',
        'is_active' => true,
    ]);
    $branchB = Branch::query()->create([
        'name' => 'Cabang Owner B',
        'code' => 'COB2',
        'is_active' => true,
    ]);

    $owner = User::factory()->create([
        'role' => UserRole::Owner->value,
        'branch_id' => $branchA->id,
    ]);
    $adminB = User::factory()->create([
        'role' => UserRole::Admin->value,
        'branch_id' => $branchB->id,
    ]);

    $category = Category::query()->create([
        'name' => 'Owner Category',
        'is_active' => true,
    ]);

    $productB = Product::query()->create([
        'branch_id' => $branchB->id,
        'category_id' => $category->id,
        'name' => 'Produk Owner B',
        'sku' => 'OWN-B-001',
        'barcode' => '8800000000002',
        'purchase_price' => 5000,
        'selling_price' => 7000,
        'stock' => 3,
        'low_stock_threshold' => 1,
        'unit' => 'pcs',
        'is_active' => true,
    ]);

    $supplierB = Supplier::query()->create([
        'branch_id' => $branchB->id,
        'name' => 'Supplier Owner B',
        'code' => 'SUP-OWN-B',
        'payment_term_days' => 7,
        'is_active' => true,
    ]);

    $purchaseB = SupplierPurchase::query()->create([
        'branch_id' => $branchB->id,
        'supplier_id' => $supplierB->id,
        'created_by' => $adminB->id,
        'number' => 'PO-20990101-0002',
        'status' => 'draft',
        'ordered_at' => now()->toDateString(),
        'due_date' => now()->addDays(7)->toDateString(),
        'subtotal' => 100000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => 100000,
        'paid_amount' => 0,
        'remaining_amount' => 100000,
        'payment_status' => 'unpaid',
    ]);

    $purchaseB->items()->create([
        'product_id' => $productB->id,
        'product_name' => $productB->name,
        'quantity' => 4,
        'unit_cost' => 6000,
        'line_total' => 24000,
    ]);

    $this->actingAs($owner)->post(route('admin.supplier-purchases.request-approval', $purchaseB))->assertRedirect();
    $approval = ApprovalRequest::query()->where('type', 'supplier.purchase_approval')->latest('id')->first();
    $this->actingAs($owner)->post(route('admin.approvals.approve', $approval), [
        'review_note' => 'Disetujui owner.',
    ])->assertRedirect();

    $this->actingAs($owner)->post(route('admin.supplier-purchases.receive', $purchaseB))
        ->assertRedirect();

    expect($purchaseB->fresh()->status)->toBe('received');
    expect($productB->fresh()->stock)->toBe(7);
});

test('admin can edit draft supplier purchase before receive', function () {
    $branch = Branch::query()->create([
        'name' => 'Cabang Edit Draft',
        'code' => 'CED',
        'is_active' => true,
    ]);

    $admin = User::factory()->create([
        'role' => UserRole::Admin->value,
        'branch_id' => $branch->id,
    ]);

    $supplier = Supplier::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Supplier Edit Draft',
        'code' => 'SUP-EDIT',
        'payment_term_days' => 0,
        'is_active' => true,
    ]);

    $this->actingAs($admin)->post(route('admin.supplier-purchases.store'), [
        'supplier_id' => $supplier->id,
        'ordered_at' => now()->toDateString(),
        'payment_term_days' => 7,
        'bulk_items' => 'Produk Lama | 2 | 1000',
        'discount_amount' => 0,
        'tax_amount' => 0,
        'items' => [],
    ])->assertRedirect();

    $purchase = SupplierPurchase::query()->latest('id')->first();

    $this->actingAs($admin)->put(route('admin.supplier-purchases.update', $purchase), [
        'supplier_id' => $supplier->id,
        'ordered_at' => now()->toDateString(),
        'payment_term_days' => 14,
        'bulk_items' => implode("\n", [
            'Produk Baru A | 3 | 2000',
            'Produk Baru B | 1 | 5000',
        ]),
        'discount_amount' => 1000,
        'tax_amount' => 500,
        'shipping_amount' => 2500,
        'items' => [],
    ])->assertRedirect();

    $purchase->refresh();
    expect((int) $purchase->payment_term_days)->toBe(14);
    expect($purchase->due_date?->toDateString())->toBe(now()->addDays(14)->toDateString());
    expect((float) $purchase->subtotal)->toBe(11000.0);
    expect((float) $purchase->total_amount)->toBe(13000.0);
    expect($purchase->items()->count())->toBe(2);
    expect($purchase->items()->where('product_name', 'Produk Baru A')->exists())->toBeTrue();
});

test('supplier purchase waits for owner approval before receive', function () {
    $branch = Branch::query()->create([
        'name' => 'Cabang Approval Supplier',
        'code' => 'CASP',
        'is_active' => true,
    ]);

    $admin = User::factory()->create([
        'role' => UserRole::Admin->value,
        'branch_id' => $branch->id,
    ]);
    $owner = User::factory()->create([
        'role' => UserRole::Owner->value,
        'branch_id' => $branch->id,
    ]);

    $supplier = Supplier::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Supplier Approval Pembelian',
        'code' => 'SUP-APR',
        'payment_term_days' => 0,
        'is_active' => true,
    ]);

    $this->actingAs($admin)->post(route('admin.supplier-purchases.store'), [
        'supplier_id' => $supplier->id,
        'ordered_at' => now()->toDateString(),
        'bulk_items' => 'Produk Approval | 10 | 250000',
        'items' => [],
    ])->assertRedirect();

    $purchase = SupplierPurchase::query()->latest('id')->first();

    $this->actingAs($admin)->post(route('admin.supplier-purchases.receive', $purchase))
        ->assertSessionHasErrors('purchase');

    $this->actingAs($admin)->post(route('admin.supplier-purchases.request-approval', $purchase))
        ->assertRedirect();

    $approval = ApprovalRequest::query()->where('type', 'supplier.purchase_approval')->latest('id')->first();
    expect($approval)->not->toBeNull();
    expect($approval->status)->toBe('pending');

    $this->actingAs($owner)->post(route('admin.approvals.approve', $approval), [
        'review_note' => 'Disetujui owner.',
    ])->assertRedirect();

    $this->actingAs($admin)->post(route('admin.supplier-purchases.receive', $purchase))
        ->assertRedirect();

    expect($purchase->fresh()->status)->toBe('received');
    expect($approval->fresh()->status)->toBe('approved');
});
