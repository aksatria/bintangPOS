<?php

use App\Enums\UserRole;
use App\Models\ApprovalRequest;
use App\Models\Branch;
use App\Models\Category;
use App\Models\CashierAuditLog;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierPurchase;
use App\Models\SupplierPurchaseItem;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('admin can create manual supplier purchase and receive it', function () {
    Storage::fake('public');

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
        'supplier_invoice_number' => 'INV-SUP-001',
        'delivery_note_number' => 'SJ-SUP-001',
        'payment_term_days' => 21,
        'due_date' => now()->addDays(10)->toDateString(),
        'discount_amount' => 0,
        'tax_amount' => 0,
        'items' => [
            ['product_name' => 'Vitamin A', 'quantity' => 10, 'unit_cost' => 1200],
        ],
    ])->assertRedirect();

    $purchase = SupplierPurchase::query()->where('supplier_id', $supplier->id)->latest('id')->first();
    expect($purchase)->not->toBeNull();
    expect($purchase->status)->toBe('draft');
    expect($purchase->number)->toMatch('/^PO-PST-'.now()->format('Ym').'-\d{4}$/');
    expect($purchase->supplier_invoice_number)->toBe('INV-SUP-001');
    expect($purchase->delivery_note_number)->toBe('SJ-SUP-001');
    expect($purchase->due_date?->toDateString())->toBe(now()->addDays(10)->toDateString());

    $this->actingAs($admin)->post(route('admin.supplier-purchases.attachments.store', $purchase), [
        'kind' => 'supplier_invoice',
        'attachment' => UploadedFile::fake()->create('invoice-approval.pdf', 80, 'application/pdf'),
        'note' => 'Invoice untuk approval.',
    ])->assertRedirect();

    $this->actingAs($admin)->post(route('admin.supplier-purchases.request-approval', $purchase))->assertRedirect();
    $approval = ApprovalRequest::query()->where('type', 'supplier.purchase_approval')->latest('id')->first();

    $this->actingAs($admin)->get(route('admin.approvals.index', ['status' => 'pending', 'type' => 'supplier.purchase_approval']))
        ->assertOk()
        ->assertSee('Ringkasan pembelian supplier')
        ->assertSee($purchase->number)
        ->assertSee('Vitamin A')
        ->assertSee('invoice-approval.pdf')
        ->assertSee('Invoice');

    $this->actingAs($admin)->post(route('admin.approvals.approve', $approval), [
        'review_note' => 'Disetujui untuk penerimaan barang.',
    ])->assertRedirect();

    $this->actingAs($admin)->post(route('admin.supplier-purchases.receive', $purchase))->assertRedirect();

    expect($product->fresh()->stock)->toBe(5);
    expect($purchase->fresh()->status)->toBe('received');
    expect(CashierAuditLog::query()->where('action', 'supplier_purchase_received')->exists())->toBeTrue();
    $this->actingAs($admin)->get(route('admin.supplier-purchases.show', $purchase))
        ->assertOk()
        ->assertSee('Detail Barang Dipesan')
        ->assertSee('Timeline PO')
        ->assertSee('Barang diterima penuh')
        ->assertSee('INV-SUP-001')
        ->assertSee('SJ-SUP-001');
    $this->actingAs($admin)->get(route('admin.supplier-purchases.pdf', $purchase))->assertOk();

    $this->actingAs($admin)->get(route('admin.suppliers.show', $supplier))
        ->assertOk()
        ->assertSee('Detail Supplier')
        ->assertSee('Histori Pembelian')
        ->assertSee($purchase->number)
        ->assertSee('Total PO');
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
        'supplier_invoice_number' => 'INV-BULK-77',
        'delivery_note_number' => 'SJ-BULK-77',
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
    expect($purchase->supplier_invoice_number)->toBe('INV-BULK-77');
    expect($purchase->delivery_note_number)->toBe('SJ-BULK-77');
    expect($purchase->items()->count())->toBe(4);
    expect((float) $purchase->shipping_amount)->toBe(25000.0);
    expect((string) $purchase->shipping_accounting_treatment)->toBe('inventory');
    expect((float) $purchase->inventory_shipping_amount)->toBe(25000.0);
    expect((float) $purchase->expense_shipping_amount)->toBe(0.0);
    expect((float) $purchase->items()->sum('shipping_allocation_amount'))->toBe(25000.0);
    expect((float) $purchase->items()->sum('landed_line_total'))->toBe((float) (1000 * 850 + 250 * 1200 + 50 * 18000 + 75 * 650 + 25000));
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
    $purchase->update(['supplier_invoice_number' => 'INV-HUTANG-TEST']);

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
    $purchaseB->update(['supplier_invoice_number' => 'INV-OWNER-B']);

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
        'supplier_invoice_number' => 'INV-EDIT-NEW',
        'delivery_note_number' => 'SJ-EDIT-NEW',
        'due_date' => now()->addDays(20)->toDateString(),
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
    expect($purchase->supplier_invoice_number)->toBe('INV-EDIT-NEW');
    expect($purchase->delivery_note_number)->toBe('SJ-EDIT-NEW');
    expect((int) $purchase->payment_term_days)->toBe(14);
    expect($purchase->due_date?->toDateString())->toBe(now()->addDays(20)->toDateString());
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
    $purchase->update(['supplier_invoice_number' => 'INV-APR-TEST']);

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

test('editing approved supplier draft requires approval again', function () {
    $branch = Branch::query()->create([
        'name' => 'Cabang Reapproval Supplier',
        'code' => 'CRS',
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
        'name' => 'Supplier Reapproval',
        'code' => 'SUP-REAPR',
        'payment_term_days' => 0,
        'is_active' => true,
    ]);

    $this->actingAs($admin)->post(route('admin.supplier-purchases.store'), [
        'supplier_id' => $supplier->id,
        'ordered_at' => now()->toDateString(),
        'bulk_items' => 'Produk Awal | 2 | 10000',
        'items' => [],
    ])->assertRedirect();

    $purchase = SupplierPurchase::query()->where('supplier_id', $supplier->id)->latest('id')->first();
    $purchase->update(['supplier_invoice_number' => 'INV-REAPR-1']);
    $this->actingAs($admin)->post(route('admin.supplier-purchases.request-approval', $purchase))->assertRedirect();
    $firstApproval = ApprovalRequest::query()->where('type', 'supplier.purchase_approval')->latest('id')->first();
    $this->actingAs($owner)->post(route('admin.approvals.approve', $firstApproval), [
        'review_note' => 'Versi pertama disetujui.',
    ])->assertRedirect();

    $this->actingAs($admin)->put(route('admin.supplier-purchases.update', $purchase), [
        'supplier_id' => $supplier->id,
        'ordered_at' => now()->toDateString(),
        'payment_term_days' => 0,
        'bulk_items' => 'Produk Diubah | 3 | 12000',
        'discount_amount' => 0,
        'tax_amount' => 0,
        'shipping_amount' => 0,
        'items' => [],
    ])->assertRedirect();
    $purchase->refresh();
    $purchase->update(['supplier_invoice_number' => 'INV-REAPR-2']);

    $this->actingAs($admin)->post(route('admin.supplier-purchases.receive', $purchase))
        ->assertSessionHasErrors('purchase');

    $this->actingAs($admin)->post(route('admin.supplier-purchases.request-approval', $purchase))->assertRedirect();
    $secondApproval = ApprovalRequest::query()->where('type', 'supplier.purchase_approval')->latest('id')->first();
    expect($secondApproval->id)->not->toBe($firstApproval->id);

    $this->actingAs($owner)->post(route('admin.approvals.approve', $secondApproval), [
        'review_note' => 'Versi baru disetujui.',
    ])->assertRedirect();

    $this->actingAs($admin)->post(route('admin.supplier-purchases.receive', $purchase))->assertRedirect();
    expect($purchase->fresh()->status)->toBe('received');
});

test('admin can cancel draft supplier purchase and pending approval is closed', function () {
    $branch = Branch::query()->create([
        'name' => 'Cabang Cancel Supplier',
        'code' => 'CCS',
        'is_active' => true,
    ]);

    $admin = User::factory()->create([
        'role' => UserRole::Admin->value,
        'branch_id' => $branch->id,
    ]);

    $supplier = Supplier::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Supplier Cancel',
        'code' => 'SUP-CANCEL',
        'payment_term_days' => 0,
        'is_active' => true,
    ]);

    $this->actingAs($admin)->post(route('admin.supplier-purchases.store'), [
        'supplier_id' => $supplier->id,
        'ordered_at' => now()->toDateString(),
        'bulk_items' => 'Produk Cancel | 1 | 15000',
        'items' => [],
    ])->assertRedirect();

    $purchase = SupplierPurchase::query()->where('supplier_id', $supplier->id)->latest('id')->first();
    $purchase->update(['supplier_invoice_number' => 'INV-CANCEL-TEST']);
    $this->actingAs($admin)->post(route('admin.supplier-purchases.request-approval', $purchase))->assertRedirect();
    $approval = ApprovalRequest::query()->where('type', 'supplier.purchase_approval')->latest('id')->first();

    $this->actingAs($admin)->post(route('admin.supplier-purchases.cancel', $purchase))->assertRedirect();

    expect($purchase->fresh()->status)->toBe('cancelled');
    expect($approval->fresh()->status)->toBe('rejected');
    expect($approval->fresh()->review_note)->toBe('[CANCELLED] Draft pembelian dibatalkan.');
});

test('supplier purchase approval filter paginates the matched status before rendering', function () {
    $branch = Branch::query()->create([
        'name' => 'Cabang Filter Approval Supplier',
        'code' => 'CFAS',
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

    $makePurchase = function (string $number, string $supplierName, float $amount) use ($branch, $admin): SupplierPurchase {
        $supplier = Supplier::query()->create([
            'branch_id' => $branch->id,
            'name' => $supplierName,
            'code' => 'SUP-' . substr(md5($number), 0, 8),
            'is_active' => true,
        ]);

        $purchase = SupplierPurchase::query()->create([
            'branch_id' => $branch->id,
            'supplier_id' => $supplier->id,
            'created_by' => $admin->id,
            'number' => $number,
            'supplier_invoice_number' => 'INV-' . $number,
            'status' => 'draft',
            'ordered_at' => now()->toDateString(),
            'payment_term_days' => 0,
            'due_date' => now()->toDateString(),
            'subtotal' => $amount,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'shipping_amount' => 0,
            'total_amount' => $amount,
            'paid_amount' => 0,
            'remaining_amount' => $amount,
            'payment_status' => 'unpaid',
        ]);

        SupplierPurchaseItem::query()->create([
            'supplier_purchase_id' => $purchase->id,
            'product_name' => 'Produk ' . $number,
            'quantity' => 1,
            'unit_cost' => $amount,
            'line_total' => $amount,
        ]);

        return $purchase;
    };

    $none = $makePurchase('PO-FILTER-NONE', 'Supplier Filter None', 10000);
    $pending = $makePurchase('PO-FILTER-PENDING', 'Supplier Filter Pending', 20000);
    $approved = $makePurchase('PO-FILTER-APPROVED', 'Supplier Filter Approved', 30000);
    $stale = $makePurchase('PO-FILTER-STALE', 'Supplier Filter Stale', 40000);

    $this->actingAs($admin)->post(route('admin.supplier-purchases.request-approval', $pending))->assertRedirect();

    $this->actingAs($admin)->post(route('admin.supplier-purchases.request-approval', $approved))->assertRedirect();
    $approvedApproval = ApprovalRequest::query()
        ->where('type', 'supplier.purchase_approval')
        ->where('payload->supplier_purchase_id', $approved->id)
        ->latest('id')
        ->first();
    $this->actingAs($owner)->post(route('admin.approvals.approve', $approvedApproval), [
        'review_note' => 'Disetujui.',
    ])->assertRedirect();

    $this->actingAs($admin)->post(route('admin.supplier-purchases.request-approval', $stale))->assertRedirect();
    $staleApproval = ApprovalRequest::query()
        ->where('type', 'supplier.purchase_approval')
        ->where('payload->supplier_purchase_id', $stale->id)
        ->latest('id')
        ->first();
    $this->actingAs($owner)->post(route('admin.approvals.approve', $staleApproval), [
        'review_note' => 'Disetujui sebelum edit.',
    ])->assertRedirect();
    $stale->items()->first()->update(['product_name' => 'Produk Stale Setelah Edit']);

    $assertFilteredList = function (string $filter, string $expectedNumber) use ($admin) {
        $response = $this->actingAs($admin)->get(route('admin.suppliers.index', ['approval_status' => $filter]));
        $response->assertOk()->assertSee($expectedNumber);
        expect(substr_count($response->getContent(), 'class="ux-purchase-row"'))->toBe(1);
    };

    $assertFilteredList('none', $none->number);
    $assertFilteredList('pending', $pending->number);
    $assertFilteredList('approved', $approved->number);
    $assertFilteredList('stale', $stale->number);
});

test('admin can upload and delete supplier purchase attachment', function () {
    Storage::fake('public');

    $branch = Branch::query()->create([
        'name' => 'Cabang Lampiran Supplier',
        'code' => 'CLS',
        'is_active' => true,
    ]);

    $admin = User::factory()->create([
        'role' => UserRole::Admin->value,
        'branch_id' => $branch->id,
    ]);

    $supplier = Supplier::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Supplier Lampiran',
        'code' => 'SUP-LAMP',
        'is_active' => true,
    ]);

    $purchase = SupplierPurchase::query()->create([
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'created_by' => $admin->id,
        'number' => 'PO-LAMPIRAN-01',
        'status' => 'draft',
        'ordered_at' => now()->toDateString(),
        'payment_term_days' => 0,
        'subtotal' => 100000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'shipping_amount' => 0,
        'total_amount' => 100000,
        'paid_amount' => 0,
        'remaining_amount' => 100000,
        'payment_status' => 'unpaid',
    ]);

    $this->actingAs($admin)->post(route('admin.supplier-purchases.attachments.store', $purchase), [
        'kind' => 'supplier_invoice',
        'attachment' => UploadedFile::fake()->create('invoice-supplier.pdf', 120, 'application/pdf'),
        'note' => 'Invoice asli supplier.',
    ])->assertRedirect();

    $attachment = $purchase->attachments()->first();
    expect($attachment)->not->toBeNull();
    expect($attachment->kind)->toBe('supplier_invoice');
    expect($attachment->note)->toBe('Invoice asli supplier.');
    Storage::disk('public')->assertExists($attachment->path);

    $this->actingAs($admin)->get(route('admin.supplier-purchases.show', $purchase))
        ->assertOk()
        ->assertSee('Lampiran Dokumen')
        ->assertSee('invoice-supplier.pdf');

    $this->actingAs($admin)->delete(route('admin.supplier-purchase-attachments.destroy', $attachment))
        ->assertRedirect();

    Storage::disk('public')->assertMissing($attachment->path);
    expect($purchase->attachments()->count())->toBe(0);
});

test('supplier purchase requires document before approval and can be duplicated/exported', function () {
    $branch = Branch::query()->create([
        'name' => 'Cabang Closing Supplier',
        'code' => 'CLOS',
        'is_active' => true,
    ]);

    $admin = User::factory()->create([
        'role' => UserRole::Admin->value,
        'branch_id' => $branch->id,
    ]);

    $supplier = Supplier::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Supplier Closing',
        'code' => 'SUP-CLOS',
        'is_active' => true,
    ]);

    $this->actingAs($admin)->post(route('admin.supplier-purchases.store'), [
        'supplier_id' => $supplier->id,
        'ordered_at' => now()->toDateString(),
        'payment_term_days' => 7,
        'bulk_items' => 'Produk Closing | 2 | 50000',
        'items' => [],
    ])->assertRedirect();

    $purchase = SupplierPurchase::query()->where('supplier_id', $supplier->id)->latest('id')->first();

    $this->actingAs($admin)->post(route('admin.supplier-purchases.request-approval', $purchase))
        ->assertSessionHasErrors('purchase');

    $purchase->update(['supplier_invoice_number' => 'INV-CLOSING-01']);

    $this->actingAs($admin)->post(route('admin.supplier-purchases.request-approval', $purchase))
        ->assertRedirect();

    $this->actingAs($admin)->post(route('admin.supplier-purchases.duplicate', $purchase))
        ->assertRedirect();

    $duplicated = SupplierPurchase::query()
        ->where('id', '!=', $purchase->id)
        ->where('supplier_id', $supplier->id)
        ->latest('id')
        ->first();

    expect($duplicated)->not->toBeNull();
    expect($duplicated->status)->toBe('draft');
    expect((float) $duplicated->remaining_amount)->toBe((float) $duplicated->total_amount);
    expect($duplicated->items()->count())->toBe(1);

    $this->actingAs($admin)->get(route('admin.supplier-purchases.export.excel'))
        ->assertOk()
        ->assertHeader('content-type');
});

test('supplier purchase rejects unsafe dates down payments item prices and overpayments', function () {
    $branch = Branch::query()->create([
        'name' => 'Cabang Validasi Supplier',
        'code' => 'CVS',
        'is_active' => true,
    ]);

    $admin = User::factory()->create([
        'role' => UserRole::Admin->value,
        'branch_id' => $branch->id,
    ]);

    $supplier = Supplier::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Supplier Validasi',
        'code' => 'SUP-VALID',
        'is_active' => true,
    ]);

    $basePayload = [
        'supplier_id' => $supplier->id,
        'ordered_at' => '2026-05-10',
        'payment_term_days' => 0,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'shipping_amount' => 0,
        'items' => [
            ['product_name' => 'Produk Validasi', 'quantity' => 2, 'unit_cost' => 50000],
        ],
    ];

    $this->actingAs($admin)->post(route('admin.supplier-purchases.store'), $basePayload + [
        'due_date' => '2026-05-09',
    ])->assertSessionHasErrors('due_date');

    $this->actingAs($admin)->post(route('admin.supplier-purchases.store'), $basePayload + [
        'down_payment_amount' => 150000,
    ])->assertSessionHasErrors('down_payment_amount');

    $this->actingAs($admin)->post(route('admin.supplier-purchases.store'), [
        ...$basePayload,
        'items' => [
            ['product_name' => 'Produk Nol', 'quantity' => 1, 'unit_cost' => 0],
        ],
    ])->assertSessionHasErrors('items.0.unit_cost');

    $this->actingAs($admin)->post(route('admin.supplier-purchases.store'), $basePayload + [
        'supplier_invoice_number' => 'INV-VALID-01',
    ])->assertRedirect();

    $purchase = SupplierPurchase::query()->where('supplier_id', $supplier->id)->latest('id')->first();
    expect($purchase)->not->toBeNull();

    $this->actingAs($admin)->post(route('admin.supplier-purchases.request-approval', $purchase))->assertRedirect();
    $approval = ApprovalRequest::query()->where('type', 'supplier.purchase_approval')->latest('id')->first();
    $this->actingAs($admin)->post(route('admin.approvals.approve', $approval), [
        'review_note' => 'Validasi disetujui.',
    ])->assertRedirect();
    $this->actingAs($admin)->post(route('admin.supplier-purchases.receive', $purchase))->assertRedirect();

    $this->actingAs($admin)->post(route('admin.supplier-purchases.pay', $purchase), [
        'amount' => 100001,
        'payment_method' => 'cash',
    ])->assertSessionHasErrors('amount');

    expect((float) $purchase->fresh()->paid_amount)->toBe(0.0);

    $this->actingAs($admin)->post(route('admin.supplier-purchases.store'), [
        ...$basePayload,
        'supplier_invoice_number' => 'INV-EXPENSE-SHIP',
        'shipping_amount' => 20000,
        'shipping_accounting_treatment' => 'expense',
    ])->assertRedirect();

    $expenseShippingPurchase = SupplierPurchase::query()->where('supplier_invoice_number', 'INV-EXPENSE-SHIP')->latest('id')->first();
    expect($expenseShippingPurchase)->not->toBeNull();
    expect((string) $expenseShippingPurchase->shipping_accounting_treatment)->toBe('expense');
    expect((float) $expenseShippingPurchase->inventory_shipping_amount)->toBe(0.0);
    expect((float) $expenseShippingPurchase->expense_shipping_amount)->toBe(20000.0);
    expect((float) $expenseShippingPurchase->items()->sum('shipping_allocation_amount'))->toBe(0.0);
    expect((float) $expenseShippingPurchase->items()->sum('landed_line_total'))->toBe(100000.0);
});

test('supplier purchase attachment download follows branch access', function () {
    Storage::fake('public');

    $branchA = Branch::query()->create([
        'name' => 'Cabang Lampiran A',
        'code' => 'CLA',
        'is_active' => true,
    ]);
    $branchB = Branch::query()->create([
        'name' => 'Cabang Lampiran B',
        'code' => 'CLB',
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

    $supplierB = Supplier::query()->create([
        'branch_id' => $branchB->id,
        'name' => 'Supplier Dokumen B',
        'code' => 'SUP-DOK-B',
        'is_active' => true,
    ]);

    $purchaseB = SupplierPurchase::query()->create([
        'branch_id' => $branchB->id,
        'supplier_id' => $supplierB->id,
        'created_by' => $adminB->id,
        'number' => 'PO-DOK-B-01',
        'status' => 'draft',
        'ordered_at' => now()->toDateString(),
        'payment_term_days' => 0,
        'subtotal' => 100000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'shipping_amount' => 0,
        'total_amount' => 100000,
        'paid_amount' => 0,
        'remaining_amount' => 100000,
        'payment_status' => 'unpaid',
    ]);

    $this->actingAs($adminB)->post(route('admin.supplier-purchases.attachments.store', $purchaseB), [
        'kind' => 'supplier_invoice',
        'attachment' => UploadedFile::fake()->create('invoice-cabang-b.pdf', 80, 'application/pdf'),
    ])->assertRedirect();

    $attachment = $purchaseB->attachments()->first();
    expect($attachment)->not->toBeNull();

    $this->actingAs($adminB)->get(route('admin.supplier-purchase-attachments.download', $attachment))
        ->assertOk();

    $this->actingAs($adminA)->get(route('admin.supplier-purchase-attachments.download', $attachment))
        ->assertNotFound();
});

test('supplier purchase uat end to end flow is complete', function () {
    Storage::fake('public');

    $branch = Branch::query()->create([
        'name' => 'Cabang UAT Supplier',
        'code' => 'UAT',
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
        'name' => 'PT Sumber Medika Nusantara',
        'code' => 'SUP-UAT-001',
        'phone' => '021-5500101',
        'email' => 'sumber.medika@example.test',
        'address' => 'Jl. Pembelian Raya No. 10',
        'is_active' => true,
    ]);

    $this->actingAs($admin)->post(route('admin.supplier-purchases.store'), [
        'supplier_id' => $supplier->id,
        'ordered_at' => '2026-05-05',
        'payment_term_days' => 14,
        'supplier_invoice_number' => 'INV-UAT-001',
        'delivery_note_number' => 'SJ-UAT-001',
        'bulk_items' => implode("\n", [
            'Paracetamol 500mg | 100 | 1500',
            'Vitamin C 500mg | 50 | 2500',
            'Masker Medis | 20 | 10000',
        ]),
        'discount_amount' => 25000,
        'tax_amount' => 0,
        'shipping_amount' => 30000,
        'items' => [],
    ])->assertRedirect();

    $purchase = SupplierPurchase::query()->where('supplier_id', $supplier->id)->latest('id')->first();
    expect($purchase)->not->toBeNull();
    expect((float) $purchase->total_amount)->toBe(480000.0);
    expect($purchase->due_date?->toDateString())->toBe('2026-05-19');

    $this->actingAs($admin)->post(route('admin.supplier-purchases.attachments.store', $purchase), [
        'kind' => 'supplier_invoice',
        'attachment' => UploadedFile::fake()->create('invoice-uat.pdf', 100, 'application/pdf'),
    ])->assertRedirect();

    $this->actingAs($admin)->post(route('admin.supplier-purchases.request-approval', $purchase))->assertRedirect();
    $approval = ApprovalRequest::query()->where('type', 'supplier.purchase_approval')->latest('id')->first();
    expect($approval->status)->toBe('pending');

    $this->actingAs($owner)->post(route('admin.approvals.approve', $approval), [
        'review_note' => 'UAT disetujui.',
    ])->assertRedirect();

    $this->actingAs($admin)->post(route('admin.supplier-purchases.receive', $purchase))->assertRedirect();

    $this->actingAs($admin)->post(route('admin.supplier-purchases.pay', $purchase), [
        'amount' => 200000,
        'payment_method' => 'transfer',
        'payment_reference' => 'TRF-UAT-001',
        'note' => 'Pembayaran pertama.',
    ])->assertRedirect();

    expect((float) $purchase->fresh()->remaining_amount)->toBe(280000.0);
    expect((string) $purchase->fresh()->payment_status)->toBe('partial');

    $this->actingAs($admin)->post(route('admin.supplier-purchases.pay', $purchase), [
        'amount' => 280000,
        'payment_method' => 'cash',
        'note' => 'Pelunasan.',
    ])->assertRedirect();

    $purchase->refresh();
    expect($purchase->status)->toBe('received');
    expect((float) $purchase->remaining_amount)->toBe(0.0);
    expect((string) $purchase->payment_status)->toBe('paid');
    expect($purchase->payments()->count())->toBe(2);
    expect($purchase->attachments()->count())->toBe(1);
    expect(JournalEntry::query()->where('event', 'supplier_receive')->where('source_id', $purchase->id)->exists())->toBeTrue();
    expect(JournalEntry::query()->where('event', 'supplier_payment')->count())->toBe(2);

    $this->actingAs($admin)->get(route('admin.supplier-purchases.show', $purchase))
        ->assertOk()
        ->assertSee('Barang Diterima')
        ->assertSee('Lunas')
        ->assertSee('TRF-UAT-001')
        ->assertSee('Audit Trail Pembelian')
        ->assertSee('invoice-uat.pdf');

    $this->actingAs($admin)->get(route('admin.accounting.journals'))
        ->assertOk()
        ->assertSee('Jurnal Umum')
        ->assertSee('Posting pembayaran hutang supplier');

    $this->actingAs($admin)->get(route('admin.suppliers.index', [
        'purchase_supplier_id' => $supplier->id,
        'debt_filter' => 'closed',
        'attachment_filter' => 'with',
        'due_from' => '2026-05-01',
        'due_to' => '2026-05-31',
    ]))
        ->assertOk()
        ->assertSee('Laporan Hutang Supplier')
        ->assertSee($purchase->number)
        ->assertSee('Lunas');

    $this->actingAs($admin)->get(route('admin.supplier-debts.index', [
        'supplier_id' => $supplier->id,
        'payment_status' => 'all',
    ]))
        ->assertOk()
        ->assertSee('Hutang Supplier');

    $this->actingAs($admin)->get(route('admin.supplier-purchases.export.excel'))
        ->assertOk()
        ->assertHeader('content-type');
});

test('supplier debt report exports and payment proof upload are available', function () {
    Storage::fake('public');

    $branch = Branch::query()->create([
        'name' => 'Cabang Aging Supplier',
        'code' => 'AGS',
        'is_active' => true,
    ]);

    $admin = User::factory()->create([
        'role' => UserRole::Admin->value,
        'branch_id' => $branch->id,
    ]);

    $supplier = Supplier::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Supplier Aging',
        'code' => 'SUP-AGING',
        'is_active' => true,
    ]);

    $purchase = SupplierPurchase::query()->create([
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'created_by' => $admin->id,
        'number' => 'PO-AGING-01',
        'supplier_invoice_number' => 'INV-AGING-01',
        'status' => 'received',
        'ordered_at' => now()->subDays(20)->toDateString(),
        'payment_term_days' => 7,
        'due_date' => now()->subDays(13)->toDateString(),
        'subtotal' => 500000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'shipping_amount' => 0,
        'total_amount' => 500000,
        'paid_amount' => 100000,
        'remaining_amount' => 400000,
        'payment_status' => 'partial',
    ]);

    $payment = $purchase->payments()->create([
        'received_by' => $admin->id,
        'paid_at' => now(),
        'amount' => 100000,
        'payment_method' => 'transfer',
        'reference_number' => 'TRF-AGING-01',
        'note' => 'DP aging.',
    ]);

    $this->actingAs($admin)->get(route('admin.supplier-debts.index', [
        'supplier_id' => $supplier->id,
        'aging_bucket' => '8_14',
    ]))
        ->assertOk()
        ->assertSee('Hutang Supplier')
        ->assertSee('PO-AGING-01')
        ->assertSee('Overdue');

    $this->actingAs($admin)->get(route('admin.supplier-debts.export.excel', [
        'supplier_id' => $supplier->id,
    ]))
        ->assertOk()
        ->assertHeader('content-type');

    $this->actingAs($admin)->get(route('admin.supplier-debts.export.pdf', [
        'supplier_id' => $supplier->id,
    ]))
        ->assertOk()
        ->assertHeader('content-type');

    $this->actingAs($admin)->post(route('admin.supplier-purchase-payments.proof.store', $payment), [
        'attachment' => UploadedFile::fake()->create('bukti-transfer-aging.pdf', 90, 'application/pdf'),
        'note' => 'Bukti transfer.',
    ])->assertRedirect();

    $proof = $payment->proofAttachments()->first();
    expect($proof)->not->toBeNull();
    expect($proof->kind)->toBe('payment_proof');
    Storage::disk('public')->assertExists($proof->path);

    $this->actingAs($admin)->get(route('admin.supplier-purchases.show', $purchase))
        ->assertOk()
        ->assertSee('TRF-AGING-01')
        ->assertSee('bukti-transfer-aging.pdf');
});

test('supplier purchase supports partial receive returns and reconciliation', function () {
    $branch = Branch::query()->create([
        'name' => 'Cabang Partial Supplier',
        'code' => 'CPS',
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

    $category = Category::query()->create([
        'name' => 'Partial Category',
        'is_active' => true,
    ]);

    $product = Product::query()->create([
        'branch_id' => $branch->id,
        'category_id' => $category->id,
        'name' => 'Produk Partial',
        'sku' => 'PART-001',
        'barcode' => '8899001100223',
        'purchase_price' => 1000,
        'selling_price' => 2000,
        'stock' => 10,
        'low_stock_threshold' => 1,
        'unit' => 'pcs',
        'is_active' => true,
    ]);

    $supplier = Supplier::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Supplier Partial',
        'code' => 'SUP-PARTIAL',
        'is_active' => true,
    ]);

    $purchase = SupplierPurchase::query()->create([
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'created_by' => $admin->id,
        'number' => 'PO-PARTIAL-01',
        'supplier_invoice_number' => 'INV-PARTIAL-01',
        'status' => 'draft',
        'ordered_at' => now()->toDateString(),
        'payment_term_days' => 7,
        'due_date' => now()->addDays(7)->toDateString(),
        'subtotal' => 100000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'shipping_amount' => 0,
        'total_amount' => 100000,
        'paid_amount' => 0,
        'remaining_amount' => 100000,
        'payment_status' => 'unpaid',
    ]);

    $item = $purchase->items()->create([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 10,
        'received_quantity' => 0,
        'returned_quantity' => 0,
        'unit_cost' => 10000,
        'line_total' => 100000,
    ]);

    $this->actingAs($admin)->post(route('admin.supplier-purchases.request-approval', $purchase))->assertRedirect();
    $approval = ApprovalRequest::query()->where('type', 'supplier.purchase_approval')->latest('id')->first();
    $this->actingAs($owner)->post(route('admin.approvals.approve', $approval), [
        'review_note' => 'Partial receive disetujui.',
    ])->assertRedirect();

    $this->actingAs($admin)->post(route('admin.supplier-purchases.receive-partial', $purchase), [
        'received' => [$item->id => 4],
        'note' => 'Barang datang sebagian.',
    ])->assertRedirect();

    $purchase->refresh();
    $item->refresh();
    expect($purchase->status)->toBe('partial_received');
    expect((int) $item->received_quantity)->toBe(4);
    expect($product->fresh()->stock)->toBe(14);

    $this->actingAs($admin)->post(route('admin.supplier-purchases.returns.store', $purchase), [
        'supplier_purchase_item_id' => $item->id,
        'quantity' => 1,
        'reason' => 'Rusak',
        'note' => 'Kemasan pecah.',
    ])->assertRedirect();

    $purchase->refresh();
    $item->refresh();
    expect((int) $item->returned_quantity)->toBe(1);
    expect((float) $purchase->subtotal)->toBe(90000.0);
    expect((float) $purchase->total_amount)->toBe(90000.0);
    expect((float) $purchase->remaining_amount)->toBe(90000.0);
    expect($product->fresh()->stock)->toBe(13);

    $this->actingAs($admin)->post(route('admin.supplier-purchases.reconcile', $purchase), [
        'supplier_invoice_amount' => 95000,
        'reconciliation_status' => 'mismatch',
        'reconciliation_note' => 'Invoice supplier lebih tinggi dari sistem.',
    ])->assertRedirect();

    $purchase->refresh();
    expect((float) $purchase->supplier_invoice_amount)->toBe(95000.0);
    expect((string) $purchase->reconciliation_status)->toBe('mismatch');

    $this->actingAs($admin)->post(route('admin.supplier-purchases.receive', $purchase))->assertRedirect();

    expect($purchase->fresh()->status)->toBe('received');
    expect($item->fresh()->received_quantity)->toBe(10);
    expect($product->fresh()->stock)->toBe(19);
    expect(JournalEntry::query()->where('event', 'supplier_receive')->where('source_id', $purchase->id)->exists())->toBeTrue();

    $this->actingAs($admin)->get(route('admin.supplier-purchases.show', $purchase))
        ->assertOk()
        ->assertSee('Rekonsiliasi Hutang')
        ->assertSee('Riwayat Retur Pembelian')
        ->assertSee('Selisih')
        ->assertSee('Rusak');

    $this->actingAs($admin)->get(route('admin.supplier-purchases.returns.pdf', $purchase))
        ->assertOk()
        ->assertHeader('content-type');

    $this->artisan('suppliers:debt-due-reminder', ['--days' => 7])
        ->assertExitCode(0);
});
