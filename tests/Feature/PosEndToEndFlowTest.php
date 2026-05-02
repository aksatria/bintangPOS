<?php

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\PosHold;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;

function makeE2EUser(): User
{
    return User::factory()->create(['role' => UserRole::Cashier->value]);
}

function makeE2EProduct(int $price = 15000, int $stock = 10): Product
{
    $category = Category::query()->create([
        'name' => 'Kategori E2E '.uniqid(),
        'description' => 'Kategori untuk test e2e flow',
        'is_active' => true,
    ]);

    return Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Produk E2E '.uniqid(),
        'sku' => 'E2E-'.random_int(1000, 9999),
        'barcode' => (string) random_int(1000000000000, 9999999999999),
        'purchase_price' => 10000,
        'selling_price' => $price,
        'stock' => $stock,
        'low_stock_threshold' => 2,
        'unit' => 'pcs',
        'is_active' => true,
    ]);
}

test('end-to-end style flow: hold overwrite load checkout and settle pending', function () {
    $cashier = makeE2EUser();
    $product = makeE2EProduct(price: 15000, stock: 10);

    $baseHold = [
        'id' => 'HOLD-E2E-001',
        'label' => 'HOLD-E2E-001',
        'discount' => 0,
        'tax' => 0,
        'paid' => 0,
        'status' => 'pending',
        'payment_method' => 'cash',
        'split_payment_enabled' => false,
        'split_payments' => [
            ['method' => 'cash', 'amount' => 0],
            ['method' => 'qris', 'amount' => 0],
        ],
        'customer_name' => 'Pelanggan E2E',
    ];

    // Save hold (qty 1)
    $this->actingAs($cashier)
        ->postJson(route('pos.holds.store'), array_merge($baseHold, [
            'cart' => [
                ['id' => $product->id, 'name' => $product->name, 'quantity' => 1, 'price' => 15000],
            ],
            'note' => 'draft awal',
        ]))
        ->assertOk()
        ->assertJson(['ok' => true]);

    // Overwrite same hold (qty 2)
    $this->actingAs($cashier)
        ->postJson(route('pos.holds.store'), array_merge($baseHold, [
            'cart' => [
                ['id' => $product->id, 'name' => $product->name, 'quantity' => 2, 'price' => 15000],
            ],
            'note' => 'draft overwrite',
        ]))
        ->assertOk()
        ->assertJson(['ok' => true]);

    $this->actingAs($cashier)
        ->getJson(route('pos.holds.show', ['hold' => 'HOLD-E2E-001']))
        ->assertOk()
        ->assertJsonPath('data.totalQty', 2)
        ->assertJsonPath('data.note', 'draft overwrite');

    // Checkout from hold as pending (paid < total)
    $payload = [
        'customer_name' => 'Pelanggan E2E',
        'discount_amount' => 0,
        'tax_amount' => 0,
        'paid_amount' => 10000, // total 30000 -> pending
        'payment_method' => 'cash',
        'status' => 'paid',
        'source_hold_id' => 'HOLD-E2E-001',
        'checkout_token' => 'token-e2e-flow-1',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 2, 'discount_amount' => 0],
        ],
    ];

    $this->actingAs($cashier)
        ->post(route('pos.checkout'), $payload)
        ->assertRedirect();

    $sale = Sale::query()->latest('id')->first();
    expect($sale)->not->toBeNull();
    expect((string) $sale->status->value)->toBe('pending');
    expect((int) $product->fresh()->stock)->toBe(8);

    // Hold should still exist because only paid sales auto-clean hold
    expect(PosHold::query()->where('hold_code', 'HOLD-E2E-001')->exists())->toBeTrue();

    // Settle pending from sale detail route
    $this->actingAs($cashier)
        ->post(route('sales.quick-settle-pending', $sale), [
            'payment_method' => 'cash',
            'paid_amount' => 30000,
        ])
        ->assertRedirect();

    $sale = $sale->fresh();
    expect((string) $sale->status->value)->toBe('paid');
    expect((float) $sale->paid_amount)->toBeGreaterThanOrEqual((float) $sale->total_amount);
});

test('end-to-end style checkout blocks invalid split payment amount zero', function () {
    $cashier = makeE2EUser();
    $product = makeE2EProduct(price: 20000, stock: 10);

    $payload = [
        'customer_name' => 'Split Invalid E2E',
        'discount_amount' => 0,
        'tax_amount' => 0,
        'paid_amount' => 0,
        'payment_method' => 'mixed',
        'split_payments' => [
            ['method' => 'cash', 'amount' => 0],
            ['method' => 'qris', 'amount' => 20000],
        ],
        'status' => 'paid',
        'checkout_token' => 'token-e2e-split-invalid-1',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1, 'discount_amount' => 0],
        ],
    ];

    $this->actingAs($cashier)
        ->from(route('pos.index'))
        ->post(route('pos.checkout'), $payload)
        ->assertRedirect(route('pos.index'))
        ->assertSessionHasErrors('split_payments.0.amount');

    expect(Sale::query()->count())->toBe(0);
    expect((int) $product->fresh()->stock)->toBe(10);
});

test('end-to-end style checkout rejects overpay for non-cash method', function () {
    $cashier = makeE2EUser();
    $product = makeE2EProduct(price: 15000, stock: 10);

    $payload = [
        'customer_name' => 'Overpay Non Cash E2E',
        'discount_amount' => 0,
        'tax_amount' => 0,
        'paid_amount' => 20000, // > total 15000
        'payment_method' => 'qris',
        'status' => 'paid',
        'checkout_token' => 'token-e2e-overpay-noncash-1',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1, 'discount_amount' => 0],
        ],
    ];

    $this->actingAs($cashier)
        ->from(route('pos.index'))
        ->post(route('pos.checkout'), $payload)
        ->assertRedirect(route('pos.index'))
        ->assertSessionHasErrors('paid_amount');

    expect(Sale::query()->count())->toBe(0);
    expect((int) $product->fresh()->stock)->toBe(10);
});

test('end-to-end style hold endpoint blocks cross-user access', function () {
    $userA = makeE2EUser();
    $userB = makeE2EUser();
    $product = makeE2EProduct(price: 12000, stock: 8);

    $this->actingAs($userA)
        ->postJson(route('pos.holds.store'), [
            'id' => 'HOLD-E2E-SEC-001',
            'label' => 'HOLD-E2E-SEC-001',
            'cart' => [
                ['id' => $product->id, 'name' => $product->name, 'quantity' => 1, 'price' => 12000],
            ],
            'discount' => 0,
            'tax' => 0,
            'paid' => 0,
            'status' => 'pending',
            'payment_method' => 'cash',
            'split_payment_enabled' => false,
            'split_payments' => [
                ['method' => 'cash', 'amount' => 0],
                ['method' => 'qris', 'amount' => 0],
            ],
        ])
        ->assertOk()
        ->assertJson(['ok' => true]);

    $this->actingAs($userB)
        ->getJson(route('pos.holds.show', ['hold' => 'HOLD-E2E-SEC-001']))
        ->assertStatus(404);

    $this->actingAs($userB)
        ->patchJson(route('pos.holds.rename', ['hold' => 'HOLD-E2E-SEC-001']), [
            'label' => 'HACKED',
        ])
        ->assertStatus(404);

    $this->actingAs($userB)
        ->deleteJson(route('pos.holds.delete', ['hold' => 'HOLD-E2E-SEC-001']))
        ->assertStatus(404);

    expect(PosHold::query()->where('hold_code', 'HOLD-E2E-SEC-001')->exists())->toBeTrue();
});
