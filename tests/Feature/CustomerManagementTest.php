<?php

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\CashierAuditLog;

test('checkout creates customer and links purchase history', function () {
    $cashier = User::factory()->create(['role' => UserRole::Cashier->value]);
    $category = Category::query()->create(['name' => 'Kategori C', 'description' => null, 'is_active' => true]);
    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Produk C',
        'sku' => 'SKU-C-001',
        'barcode' => '8990000000001',
        'purchase_price' => 10000,
        'selling_price' => 15000,
        'stock' => 20,
        'low_stock_threshold' => 2,
        'unit' => 'pcs',
        'is_active' => true,
    ]);

    $this->actingAs($cashier)->post(route('pos.checkout'), [
        'customer_name' => 'Budi Santoso',
        'customer_phone' => '08123456789',
        'customer_email' => 'budi@example.com',
        'customer_address' => 'Jl. Merdeka 10',
        'discount_amount' => 0,
        'tax_amount' => 0,
        'paid_amount' => 30000,
        'status' => 'paid',
        'checkout_token' => 'customer-link-1',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 2, 'discount_amount' => 0],
        ],
    ])->assertRedirect();

    $customer = Customer::query()->where('phone', '08123456789')->first();
    expect($customer)->not->toBeNull();
    expect($customer->name)->toBe('Budi Santoso');

    $sale = Sale::query()->latest('id')->first();
    expect($sale->customer_id)->toBe($customer->id);
});

test('owner can open customer list and detail', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner->value]);
    $customer = Customer::query()->create(['name' => 'Customer X', 'phone' => '0811111111', 'is_active' => true]);

    $this->actingAs($owner)->get(route('customers.index'))->assertOk();
    $this->actingAs($owner)->get(route('customers.show', $customer))->assertOk();
});

test('owner can merge duplicate customers and move sales history', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner->value]);
    $source = Customer::query()->create(['name' => 'Duplikat A', 'phone' => '081700001', 'is_active' => true]);
    $target = Customer::query()->create(['name' => 'Utama B', 'phone' => '081700002', 'is_active' => true]);

    $sale = Sale::query()->create([
        'user_id' => $owner->id,
        'customer_id' => $source->id,
        'invoice_number' => 'INV-TEST-0001',
        'customer_name' => $source->name,
        'subtotal' => 50000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => 50000,
        'paid_amount' => 50000,
        'change_amount' => 0,
        'status' => 'paid',
        'sold_at' => now(),
    ]);

    $this->actingAs($owner)
        ->post(route('customers.merge', $source), [
            'target_customer_id' => $target->id,
        ])
        ->assertRedirect(route('customers.show', $target));

    expect($sale->fresh()->customer_id)->toBe($target->id);
    expect($source->fresh()->is_active)->toBeFalse();
    expect($target->fresh()->is_active)->toBeTrue();
    expect(CashierAuditLog::query()->where('action', 'customer_merged')->exists())->toBeTrue();
});

test('merge is blocked when source customer still has pending sales', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner->value]);
    $source = Customer::query()->create(['name' => 'Pending Source', 'phone' => '081700010', 'is_active' => true]);
    $target = Customer::query()->create(['name' => 'Pending Target', 'phone' => '081700011', 'is_active' => true]);

    Sale::query()->create([
        'user_id' => $owner->id,
        'customer_id' => $source->id,
        'invoice_number' => 'INV-TEST-PENDING-1',
        'customer_name' => $source->name,
        'subtotal' => 30000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => 30000,
        'paid_amount' => 0,
        'change_amount' => 0,
        'status' => 'pending',
        'sold_at' => now(),
    ]);

    $this->actingAs($owner)
        ->post(route('customers.merge', $source), [
            'target_customer_id' => $target->id,
        ])
        ->assertSessionHasErrors('target_customer_id');

    expect(Sale::query()->where('customer_id', $source->id)->exists())->toBeTrue();
    expect(Sale::query()->where('customer_id', $target->id)->exists())->toBeFalse();
});
