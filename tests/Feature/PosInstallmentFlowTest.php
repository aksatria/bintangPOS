<?php

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerDebt;
use App\Models\CustomerDebtPayment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;

test('checkout installment creates customer debt and down payment record', function () {
    $branch = Branch::query()->create([
        'name' => 'Cabang POS',
        'code' => 'CBPOS',
        'is_active' => true,
    ]);

    $cashier = User::factory()->create([
        'role' => UserRole::Cashier->value,
        'branch_id' => $branch->id,
    ]);

    $category = Category::query()->create([
        'name' => 'Kategori I',
        'is_active' => true,
    ]);

    $product = Product::query()->create([
        'branch_id' => $branch->id,
        'category_id' => $category->id,
        'name' => 'Produk Cicilan',
        'sku' => 'CICIL-001',
        'barcode' => '8991112223334',
        'purchase_price' => 100000,
        'selling_price' => 150000,
        'stock' => 20,
        'low_stock_threshold' => 2,
        'unit' => 'pcs',
        'is_active' => true,
    ]);

    $customer = Customer::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Budi Cicilan',
        'phone' => '081999000111',
        'is_active' => true,
    ]);

    $this->actingAs($cashier)->post(route('pos.checkout'), [
        'customer_id' => $customer->id,
        'customer_name' => $customer->name,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'status' => 'pending',
        'payment_method' => 'installment',
        'installment_enabled' => 1,
        'installment_tenor_months' => 3,
        'installment_down_payment' => 50000,
        'installment_first_due_date' => now()->addMonth()->toDateString(),
        'checkout_token' => 'installment-flow-1',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1, 'discount_amount' => 0],
        ],
    ])->assertRedirect();

    $sale = Sale::query()->latest('id')->first();
    expect($sale)->not->toBeNull();
    expect((string) $sale->payment_method)->toBe('installment');

    $debt = CustomerDebt::query()->where('sale_id', $sale->id)->first();
    expect($debt)->not->toBeNull();
    expect((float) $debt->principal_amount)->toBe(150000.0);
    expect((float) $debt->paid_amount)->toBe(50000.0);
    expect((float) $debt->remaining_amount)->toBe(100000.0);

    expect(CustomerDebtPayment::query()->where('customer_debt_id', $debt->id)->count())->toBe(1);
});

test('cashier cannot checkout installment with customer from another branch', function () {
    $branchA = Branch::query()->create([
        'name' => 'Cabang A',
        'code' => 'CBA3',
        'is_active' => true,
    ]);
    $branchB = Branch::query()->create([
        'name' => 'Cabang B',
        'code' => 'CBB3',
        'is_active' => true,
    ]);

    $cashierA = User::factory()->create([
        'role' => UserRole::Cashier->value,
        'branch_id' => $branchA->id,
    ]);

    $category = Category::query()->create([
        'name' => 'Kategori X',
        'is_active' => true,
    ]);

    $productA = Product::query()->create([
        'branch_id' => $branchA->id,
        'category_id' => $category->id,
        'name' => 'Produk A',
        'sku' => 'PRD-A-001',
        'barcode' => '8999998887776',
        'purchase_price' => 10000,
        'selling_price' => 15000,
        'stock' => 10,
        'low_stock_threshold' => 2,
        'unit' => 'pcs',
        'is_active' => true,
    ]);

    $customerB = Customer::query()->create([
        'branch_id' => $branchB->id,
        'name' => 'Pelanggan Cabang B',
        'phone' => '081300000002',
        'is_active' => true,
    ]);

    $this->actingAs($cashierA)->post(route('pos.checkout'), [
        'customer_id' => $customerB->id,
        'customer_name' => $customerB->name,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'status' => 'pending',
        'payment_method' => 'installment',
        'installment_enabled' => 1,
        'installment_tenor_months' => 2,
        'installment_down_payment' => 5000,
        'installment_first_due_date' => now()->addMonth()->toDateString(),
        'checkout_token' => 'installment-cross-branch-1',
        'items' => [
            ['product_id' => $productA->id, 'quantity' => 1, 'discount_amount' => 0],
        ],
    ])->assertSessionHasErrors('customer_id');

    expect(Sale::query()->count())->toBe(0);
    expect(CustomerDebt::query()->count())->toBe(0);
});

test('cashier cannot checkout debt mode partial with customer from another branch', function () {
    $branchA = Branch::query()->create([
        'name' => 'Cabang A Debt',
        'code' => 'CAD',
        'is_active' => true,
    ]);
    $branchB = Branch::query()->create([
        'name' => 'Cabang B Debt',
        'code' => 'CBD',
        'is_active' => true,
    ]);

    $cashierA = User::factory()->create([
        'role' => UserRole::Cashier->value,
        'branch_id' => $branchA->id,
    ]);

    $category = Category::query()->create([
        'name' => 'Kategori Debt',
        'is_active' => true,
    ]);

    $productA = Product::query()->create([
        'branch_id' => $branchA->id,
        'category_id' => $category->id,
        'name' => 'Produk Debt A',
        'sku' => 'PRD-DBT-001',
        'barcode' => '8111222333445',
        'purchase_price' => 100000,
        'selling_price' => 150000,
        'stock' => 10,
        'low_stock_threshold' => 2,
        'unit' => 'pcs',
        'is_active' => true,
    ]);

    $customerB = Customer::query()->create([
        'branch_id' => $branchB->id,
        'name' => 'Pelanggan B Debt',
        'phone' => '081311100002',
        'is_active' => true,
    ]);

    $this->actingAs($cashierA)->post(route('pos.checkout'), [
        'customer_id' => $customerB->id,
        'customer_name' => $customerB->name,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'status' => 'paid',
        'payment_method' => 'cash',
        'paid_amount' => 50000,
        'debt_mode' => 'partial',
        'checkout_token' => 'debt-partial-cross-branch-1',
        'items' => [
            ['product_id' => $productA->id, 'quantity' => 1, 'discount_amount' => 0],
        ],
    ])->assertSessionHasErrors('customer_id');

    expect(Sale::query()->count())->toBe(0);
    expect(CustomerDebt::query()->count())->toBe(0);
});

test('cashier cannot checkout debt mode merge with customer from another branch', function () {
    $branchA = Branch::query()->create([
        'name' => 'Cabang A Merge',
        'code' => 'CAM',
        'is_active' => true,
    ]);
    $branchB = Branch::query()->create([
        'name' => 'Cabang B Merge',
        'code' => 'CBM',
        'is_active' => true,
    ]);

    $cashierA = User::factory()->create([
        'role' => UserRole::Cashier->value,
        'branch_id' => $branchA->id,
    ]);

    $category = Category::query()->create([
        'name' => 'Kategori Merge',
        'is_active' => true,
    ]);

    $productA = Product::query()->create([
        'branch_id' => $branchA->id,
        'category_id' => $category->id,
        'name' => 'Produk Merge A',
        'sku' => 'PRD-MRG-001',
        'barcode' => '8111222333555',
        'purchase_price' => 100000,
        'selling_price' => 150000,
        'stock' => 10,
        'low_stock_threshold' => 2,
        'unit' => 'pcs',
        'is_active' => true,
    ]);

    $customerB = Customer::query()->create([
        'branch_id' => $branchB->id,
        'name' => 'Pelanggan B Merge',
        'phone' => '081311100003',
        'is_active' => true,
    ]);

    $this->actingAs($cashierA)->post(route('pos.checkout'), [
        'customer_id' => $customerB->id,
        'customer_name' => $customerB->name,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'status' => 'paid',
        'payment_method' => 'cash',
        'paid_amount' => 0,
        'debt_mode' => 'merge',
        'checkout_token' => 'debt-merge-cross-branch-1',
        'items' => [
            ['product_id' => $productA->id, 'quantity' => 1, 'discount_amount' => 0],
        ],
    ])->assertSessionHasErrors('customer_id');

    expect(Sale::query()->count())->toBe(0);
    expect(CustomerDebt::query()->count())->toBe(0);
});
