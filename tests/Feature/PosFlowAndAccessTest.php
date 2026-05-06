<?php

use App\Enums\UserRole;
use App\Models\CashierAuditLog;
use App\Models\ApprovalRequest;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerDebt;
use App\Models\JournalEntry;
use App\Models\PosHold;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

function makeUserWithRole(string $role): User
{
    return User::factory()->create(['role' => $role]);
}

function makeProductWithStock(int $stock = 10): Product
{
    $category = Category::query()->create([
        'name' => 'Kategori Test '.uniqid(),
        'description' => 'Kategori test',
        'is_active' => true,
    ]);

    return Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Produk Test '.uniqid(),
        'sku' => 'SKU-'.random_int(1000, 9999),
        'barcode' => (string) random_int(1000000000000, 9999999999999),
        'purchase_price' => 10000,
        'selling_price' => 15000,
        'stock' => $stock,
        'low_stock_threshold' => 2,
        'unit' => 'pcs',
        'is_active' => true,
    ]);
}

test('kasir can access pos page', function () {
    $cashier = makeUserWithRole(UserRole::Cashier->value);

    $this->actingAs($cashier)
        ->get(route('pos.index'))
        ->assertOk();
});

test('kasir can access pos friendly page', function () {
    $cashier = makeUserWithRole(UserRole::Cashier->value);

    $this->actingAs($cashier)
        ->get(route('pos.responsive'))
        ->assertOk()
        ->assertSee('Kasir Modern');
});

test('kasir can create dynamic qris with midtrans sandbox endpoint', function () {
    config()->set('services.midtrans.enabled', true);
    config()->set('services.midtrans.is_sandbox', true);
    config()->set('services.midtrans.server_key', 'SB-Mid-server-test');

    Http::fake([
        'https://api.sandbox.midtrans.com/v2/charge' => Http::response([
            'order_id' => 'POS-QRIS-TEST-001',
            'transaction_id' => 'trx-test-001',
            'transaction_status' => 'pending',
            'expiry_time' => '2026-05-06 12:30:00',
            'actions' => [
                ['name' => 'generate-qr-code', 'url' => 'https://api.sandbox.midtrans.com/v2/qris/test-qr.png'],
            ],
        ], 201),
    ]);

    $cashier = makeUserWithRole(UserRole::Cashier->value);

    $this->actingAs($cashier)
        ->postJson(route('pos.qris.midtrans.create'), [
            'amount' => 15000,
            'invoice_hint' => 'TOKEN123',
        ])
        ->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('issuer', 'midtrans-sandbox')
        ->assertJsonPath('transaction_status', 'pending');
});

test('kasir can check dynamic qris midtrans sandbox status', function () {
    config()->set('services.midtrans.enabled', true);
    config()->set('services.midtrans.is_sandbox', true);
    config()->set('services.midtrans.server_key', 'SB-Mid-server-test');

    Http::fake([
        'https://api.sandbox.midtrans.com/v2/POS-QRIS-TEST-001/status' => Http::response([
            'order_id' => 'POS-QRIS-TEST-001',
            'transaction_id' => 'trx-test-001',
            'transaction_status' => 'settlement',
            'payment_type' => 'qris',
            'expiry_time' => '2026-05-06 12:30:00',
        ], 200),
    ]);

    $cashier = makeUserWithRole(UserRole::Cashier->value);

    $this->actingAs($cashier)
        ->getJson(route('pos.qris.midtrans.status', ['order_id' => 'POS-QRIS-TEST-001']))
        ->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('transaction_status', 'settlement')
        ->assertJsonPath('issuer', 'midtrans-sandbox');
});

test('midtrans live mode rejects sandbox server key', function () {
    config()->set('services.midtrans.enabled', true);
    config()->set('services.midtrans.mode', 'live');
    config()->set('services.midtrans.server_key', 'SB-Mid-server-test');

    $cashier = makeUserWithRole(UserRole::Cashier->value);

    $this->actingAs($cashier)
        ->postJson(route('pos.qris.midtrans.create'), [
            'amount' => 10000,
            'invoice_hint' => 'LIVEKEYTEST',
        ])
        ->assertStatus(422)
        ->assertJsonPath('ok', false)
        ->assertJsonPath('message', 'Mode LIVE terdeteksi, tapi server key masih sandbox (SB-).');
});

test('search customers includes debt summary and debt items', function () {
    $cashier = makeUserWithRole(UserRole::Cashier->value);
    $customer = Customer::query()->create([
        'branch_id' => $cashier->branch_id,
        'name' => 'Customer Hutang Test',
        'phone' => '081299998877',
        'email' => 'hutang.test@example.com',
        'is_active' => true,
    ]);

    CustomerDebt::query()->create([
        'branch_id' => $cashier->branch_id,
        'customer_id' => $customer->id,
        'created_by' => $cashier->id,
        'number' => 'HUT-001',
        'debt_date' => now()->toDateString(),
        'due_date' => now()->addDays(1)->toDateString(),
        'principal_amount' => 100000,
        'paid_amount' => 0,
        'remaining_amount' => 100000,
        'status' => 'active',
    ]);
    CustomerDebt::query()->create([
        'branch_id' => $cashier->branch_id,
        'customer_id' => $customer->id,
        'created_by' => $cashier->id,
        'number' => 'HUT-002',
        'debt_date' => now()->toDateString(),
        'due_date' => now()->addDays(2)->toDateString(),
        'principal_amount' => 200000,
        'paid_amount' => 0,
        'remaining_amount' => 200000,
        'status' => 'overdue',
    ]);

    $res = $this->actingAs($cashier)
        ->getJson(route('pos.search-customers', ['q' => 'Customer Hutang']))
        ->assertOk()
        ->json('data');

    $row = collect($res)->firstWhere('id', $customer->id);
    expect($row)->not->toBeNull();
    expect((int) ($row['debt_count'] ?? 0))->toBe(2);
    expect((float) ($row['debt_total'] ?? 0))->toBe(300000.0);
    expect(is_array($row['debt_items'] ?? null))->toBeTrue();
    expect(count($row['debt_items'] ?? []))->toBeGreaterThan(0);
});

test('checkout success sends telegram notification when enabled', function () {
    putenv('TELEGRAM_BOT_TOKEN=test-token-123');
    putenv('TELEGRAM_CHAT_ID=5711502419');
    $_ENV['TELEGRAM_BOT_TOKEN'] = 'test-token-123';
    $_ENV['TELEGRAM_CHAT_ID'] = '5711502419';
    $_SERVER['TELEGRAM_BOT_TOKEN'] = 'test-token-123';
    $_SERVER['TELEGRAM_CHAT_ID'] = '5711502419';

    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $cashier = makeUserWithRole(UserRole::Cashier->value);
    $product = makeProductWithStock(10);

    StoreSetting::query()->updateOrCreate(
        ['id' => 1],
        ['name' => 'Store Test Telegram', 'telegram_enabled' => true, 'telegram_override_chat_id' => null]
    );

    $this->actingAs($cashier)
        ->post(route('pos.checkout'), [
            'customer_name' => 'Notif Telegram Test',
            'discount_amount' => 0,
            'tax_amount' => 0,
            'paid_amount' => 15000,
            'payment_method' => 'cash',
            'status' => 'paid',
            'checkout_token' => 'token-telegram-checkout-1',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'discount_amount' => 0],
            ],
        ])
        ->assertRedirect();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'api.telegram.org')
            && str_contains($request->url(), '/sendMessage');
    });
});

test('kasir cannot access notification settings and telegram test route', function () {
    $cashier = makeUserWithRole(UserRole::Cashier->value);

    $this->actingAs($cashier)
        ->get(route('notification-settings.edit'))
        ->assertRedirect(route('dashboard'));

    $this->actingAs($cashier)
        ->get(route('dashboard.telegram-test'))
        ->assertForbidden();
});

test('owner can access notification settings and telegram test route', function () {
    $owner = makeUserWithRole(UserRole::Owner->value);

    $this->actingAs($owner)
        ->get(route('notification-settings.edit'))
        ->assertOk();

    $this->actingAs($owner)
        ->get(route('dashboard.telegram-test'))
        ->assertOk();
});

test('admin is denied notification settings when permission mapping in db does not grant it', function () {
    $admin = makeUserWithRole(UserRole::Admin->value);

    DB::table('permissions')->insertOrIgnore([
        ['code' => 'reports.view', 'name' => 'Lihat Laporan', 'group' => 'reports', 'description' => 'Lihat Laporan', 'created_at' => now(), 'updated_at' => now()],
        ['code' => 'settings.notification.manage', 'name' => 'Kelola Notifikasi', 'group' => 'settings', 'description' => 'Kelola Notifikasi', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $reportsPermissionId = DB::table('permissions')->where('code', 'reports.view')->value('id');
    expect($reportsPermissionId)->not->toBeNull();

    DB::table('role_permissions')->where('role', UserRole::Admin->value)->delete();
    DB::table('role_permissions')->insert([
        'role' => UserRole::Admin->value,
        'permission_id' => (int) $reportsPermissionId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('notification-settings.edit'))
        ->assertForbidden();
});

test('sales pending attempts history route is resolved correctly', function () {
    $cashier = makeUserWithRole(UserRole::Cashier->value);

    $this->actingAs($cashier)
        ->get(route('sales.pending-attempts'))
        ->assertOk()
        ->assertViewIs('sales.pending-attempts');
});

test('checkout succeeds and decrements stock', function () {
    $cashier = makeUserWithRole(UserRole::Cashier->value);
    $product = makeProductWithStock(10);

    $payload = [
        'customer_name' => 'Pelanggan Test',
        'discount_amount' => 0,
        'tax_amount' => 0,
        'paid_amount' => 30000,
        'status' => 'paid',
        'checkout_token' => 'token-checkout-1',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 2,
                'discount_amount' => 0,
            ],
        ],
    ];

    $this->actingAs($cashier)
        ->post(route('pos.checkout'), $payload)
        ->assertRedirect();

    expect(Sale::query()->count())->toBe(1);
    expect((int) $product->fresh()->stock)->toBe(8);
    expect(JournalEntry::query()->where('event', 'sale_posted')->count())->toBe(1);
});

test('owner can open accounting reports after pos posting', function () {
    $owner = makeUserWithRole(UserRole::Owner->value);
    $product = makeProductWithStock(10);

    $this->actingAs($owner)
        ->post(route('pos.checkout'), [
            'customer_name' => 'Laporan Akuntansi POS',
            'discount_amount' => 0,
            'tax_amount' => 0,
            'paid_amount' => 15000,
            'payment_method' => 'cash',
            'status' => 'paid',
            'checkout_token' => 'token-accounting-report-1',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'discount_amount' => 0],
            ],
        ])
        ->assertRedirect();

    $this->actingAs($owner)
        ->get(route('admin.accounting.reports'))
        ->assertOk()
        ->assertSee('Trial Balance');
});

test('checkout fails on duplicate checkout token', function () {
    $cashier = makeUserWithRole(UserRole::Cashier->value);
    $product = makeProductWithStock(10);

    $payload = [
        'customer_name' => 'Pelanggan Test',
        'discount_amount' => 0,
        'tax_amount' => 0,
        'paid_amount' => 15000,
        'status' => 'paid',
        'checkout_token' => 'dup-token-1',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'discount_amount' => 0,
            ],
        ],
    ];

    $this->actingAs($cashier)
        ->post(route('pos.checkout'), $payload)
        ->assertRedirect();

    $this->actingAs($cashier)
        ->from(route('pos.index'))
        ->post(route('pos.checkout'), $payload)
        ->assertRedirect(route('pos.index'))
        ->assertSessionHasErrors('items');
});

test('checkout fails when requested quantity exceeds latest stock', function () {
    $cashier = makeUserWithRole(UserRole::Cashier->value);
    $product = makeProductWithStock(1);

    $payload = [
        'customer_name' => 'Pelanggan Test',
        'discount_amount' => 0,
        'tax_amount' => 0,
        'paid_amount' => 30000,
        'status' => 'paid',
        'checkout_token' => 'token-stock-fail-1',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 2,
                'discount_amount' => 0,
            ],
        ],
    ];

    $this->actingAs($cashier)
        ->from(route('pos.index'))
        ->post(route('pos.checkout'), $payload)
        ->assertRedirect(route('pos.index'))
        ->assertSessionHasErrors('items');

    expect(Sale::query()->count())->toBe(0);
    expect((int) $product->fresh()->stock)->toBe(1);
});

test('checkout paid supports split payment and stores breakdown', function () {
    $cashier = makeUserWithRole(UserRole::Cashier->value);
    $product = makeProductWithStock(10);

    $payload = [
        'customer_name' => 'Split Pay Customer',
        'discount_amount' => 0,
        'tax_amount' => 0,
        'paid_amount' => 0,
        'payment_method' => 'mixed',
        'split_payments' => [
            ['method' => 'cash', 'amount' => 10000],
            ['method' => 'qris', 'amount' => 5000],
        ],
        'status' => 'paid',
        'checkout_token' => 'token-split-pay-1',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'discount_amount' => 0,
            ],
        ],
    ];

    $this->actingAs($cashier)
        ->post(route('pos.checkout'), $payload)
        ->assertRedirect();

    $sale = Sale::query()->latest('id')->first();
    expect((string) $sale->payment_method)->toBe('mixed');
    expect((float) $sale->paid_amount)->toBe(15000.0);
    expect(is_array($sale->payment_breakdown))->toBeTrue();
    expect(count($sale->payment_breakdown))->toBe(2);
    expect(JournalEntry::query()->where('event', 'sale_posted')->count())->toBe(1);
});

test('checkout rejects split payment rows with zero amount', function () {
    $cashier = makeUserWithRole(UserRole::Cashier->value);
    $product = makeProductWithStock(10);

    $payload = [
        'customer_name' => 'Split Zero Test',
        'discount_amount' => 0,
        'tax_amount' => 0,
        'paid_amount' => 0,
        'payment_method' => 'mixed',
        'split_payments' => [
            ['method' => 'cash', 'amount' => 0],
            ['method' => 'qris', 'amount' => 15000],
        ],
        'status' => 'paid',
        'checkout_token' => 'token-split-zero-1',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1, 'discount_amount' => 0],
        ],
    ];

    $this->actingAs($cashier)
        ->from(route('pos.index'))
        ->post(route('pos.checkout'), $payload)
        ->assertRedirect(route('pos.index'))
        ->assertSessionHasErrors('split_payments.0.amount');
});

test('checkout enforces overpay rule per method', function () {
    $cashier = makeUserWithRole(UserRole::Cashier->value);
    $product = makeProductWithStock(10);

    StoreSetting::query()->create([
        'name' => 'Store Test Overpay',
        'payment_overpay_rules' => [
            'cash' => ['max_overpay' => 1000],
            'qris' => ['max_overpay' => 0],
            'debit' => ['max_overpay' => 0],
            'transfer' => ['max_overpay' => 0],
            'e_wallet' => ['max_overpay' => 0],
        ],
    ]);

    $payload = [
        'customer_name' => 'Overpay Cash Test',
        'discount_amount' => 0,
        'tax_amount' => 0,
        'paid_amount' => 20000, // total item 15000, overpay 5000 > 1000
        'payment_method' => 'cash',
        'status' => 'paid',
        'checkout_token' => 'token-overpay-cash-1',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1, 'discount_amount' => 0],
        ],
    ];

    $this->actingAs($cashier)
        ->from(route('pos.index'))
        ->post(route('pos.checkout'), $payload)
        ->assertRedirect(route('pos.index'))
        ->assertSessionHasErrors('paid_amount');
});

test('checkout paid becomes pending when paid amount is below total', function () {
    $cashier = makeUserWithRole(UserRole::Cashier->value);
    $product = makeProductWithStock(10);

    $payload = [
        'customer_name' => 'Pending Transition Test',
        'discount_amount' => 0,
        'tax_amount' => 0,
        'paid_amount' => 5000, // total item 15000
        'payment_method' => 'cash',
        'status' => 'paid',
        'checkout_token' => 'token-pending-transition-1',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1, 'discount_amount' => 0],
        ],
    ];

    $this->actingAs($cashier)
        ->post(route('pos.checkout'), $payload)
        ->assertRedirect();

    $sale = Sale::query()->latest('id')->first();
    expect($sale)->not->toBeNull();
    expect((string) $sale->status->value)->toBe('pending');
    expect((float) $sale->paid_amount)->toBe(0.0);
    expect((float) $sale->change_amount)->toBe(0.0);
});

test('hold can be overwritten loaded and deleted via pos hold endpoints', function () {
    $cashier = makeUserWithRole(UserRole::Cashier->value);

    $base = [
        'id' => 'HOLD-EDGE-001',
        'label' => 'HOLD-EDGE-001',
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
        'customer_name' => 'Hold Tester',
    ];

    $this->actingAs($cashier)
        ->postJson(route('pos.holds.store'), array_merge($base, [
            'cart' => [
                ['id' => 1, 'name' => 'Item 1', 'quantity' => 1, 'price' => 10000],
            ],
        ]))
        ->assertOk()
        ->assertJson(['ok' => true]);

    $this->actingAs($cashier)
        ->postJson(route('pos.holds.store'), array_merge($base, [
            'cart' => [
                ['id' => 1, 'name' => 'Item 1', 'quantity' => 3, 'price' => 10000],
            ],
            'note' => 'Overwritten',
        ]))
        ->assertOk()
        ->assertJson(['ok' => true]);

    $this->actingAs($cashier)
        ->getJson(route('pos.holds.show', ['hold' => 'HOLD-EDGE-001']))
        ->assertOk()
        ->assertJsonPath('data.totalQty', 3)
        ->assertJsonPath('data.note', 'Overwritten');

    $this->actingAs($cashier)
        ->deleteJson(route('pos.holds.delete', ['hold' => 'HOLD-EDGE-001']))
        ->assertOk()
        ->assertJson(['ok' => true]);

    expect(PosHold::query()->where('hold_code', 'HOLD-EDGE-001')->exists())->toBeFalse();
});

test('audit event endpoint stores allowed pos events', function () {
    $cashier = makeUserWithRole(UserRole::Cashier->value);

    $payload = [
        'action' => 'hold_saved',
        'context' => [
            'hold_id' => 'HOLD-TEST-001',
            'total_qty' => 4,
        ],
    ];

    $this->actingAs($cashier)
        ->postJson(route('pos.audit-event'), $payload)
        ->assertOk()
        ->assertJson(['ok' => true]);

    $log = CashierAuditLog::query()->latest('id')->first();
    expect($log)->not->toBeNull();
    expect($log->action)->toBe('hold_saved');
    expect(data_get($log->context, 'hold_id'))->toBe('HOLD-TEST-001');
    expect((int) data_get($log->context, 'total_qty'))->toBe(4);
    expect((string) data_get($log->context, '_meta.schema'))->toBe('cashier_audit_log.v1');
    expect((string) data_get($log->context, '_meta.recorded_at'))->not->toBe('');
});

test('audit event endpoint rejects unknown action', function () {
    $cashier = makeUserWithRole(UserRole::Cashier->value);

    $this->actingAs($cashier)
        ->postJson(route('pos.audit-event'), [
            'action' => 'unknown_action',
            'context' => ['x' => 1],
        ])
        ->assertStatus(422)
        ->assertJson(['ok' => false]);

    expect(CashierAuditLog::query()->count())->toBe(0);
});

test('owner can access audit endpoint', function () {
    $owner = makeUserWithRole(UserRole::Owner->value);

    $this->actingAs($owner)
        ->postJson(route('pos.audit-event'), [
            'action' => 'cart_cleared',
            'context' => ['source' => 'test'],
        ])
        ->assertOk();
});

test('guest cannot access audit endpoint', function () {
    $this
        ->withHeader('Accept', 'application/json')
        ->postJson(route('pos.audit-event'), [
            'action' => 'cart_cleared',
            'context' => ['source' => 'test'],
        ])
        ->assertStatus(401);
});

test('kasir gets 403 when accessing quick refund and quick void', function () {
    $cashier = makeUserWithRole(UserRole::Cashier->value);
    $owner = makeUserWithRole(UserRole::Owner->value);
    $product = makeProductWithStock(10);

    $this->actingAs($owner)->post(route('pos.checkout'), [
        'customer_name' => 'Refund Block Test',
        'discount_amount' => 0,
        'tax_amount' => 0,
        'paid_amount' => 15000,
        'status' => 'paid',
        'checkout_token' => 'token-refund-block-1',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1, 'discount_amount' => 0],
        ],
    ])->assertRedirect();
    $salePaid = Sale::query()->latest('id')->first();

    $this->actingAs($owner)->post(route('pos.checkout'), [
        'customer_name' => 'Void Block Test',
        'discount_amount' => 0,
        'tax_amount' => 0,
        'paid_amount' => 0,
        'status' => 'pending',
        'checkout_token' => 'token-void-block-1',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1, 'discount_amount' => 0],
        ],
    ])->assertRedirect();
    $salePending = Sale::query()->latest('id')->first();

    $this->actingAs($cashier)
        ->post(route('sales.quick-refund', $salePaid), ['reason' => 'Refund tidak sesuai'])
        ->assertForbidden();

    $this->actingAs($cashier)
        ->post(route('sales.quick-void', $salePending), ['reason' => 'Void tidak sesuai'])
        ->assertForbidden();
});

test('owner can create quick refund approval request', function () {
    $owner = makeUserWithRole(UserRole::Owner->value);
    $product = makeProductWithStock(5);

    $this->actingAs($owner)->post(route('pos.checkout'), [
        'customer_name' => 'Refund Owner Test',
        'discount_amount' => 0,
        'tax_amount' => 0,
        'paid_amount' => 30000,
        'status' => 'paid',
        'checkout_token' => 'token-refund-owner-1',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 2, 'discount_amount' => 0],
        ],
    ])->assertRedirect();

    $sale = Sale::query()->latest('id')->first();
    expect((string) $sale->status->value)->toBe('paid');
    expect((int) $product->fresh()->stock)->toBe(3);

    $this->actingAs($owner)
        ->post(route('sales.quick-refund', $sale), [
            'reason' => 'Salah input transaksi',
        ])
        ->assertRedirect();

    $sale = $sale->fresh();
    expect((string) $sale->status->value)->toBe('paid');
    expect((int) $product->fresh()->stock)->toBe(3);

    $approval = ApprovalRequest::query()->where('type', 'sale.quick_refund')->latest('id')->first();
    expect($approval)->not->toBeNull();
    expect((string) $approval->status)->toBe('pending');
    expect((int) data_get($approval->payload, 'sale_id'))->toBe((int) $sale->id);

    $log = CashierAuditLog::query()->where('action', 'approval_request_created')->latest('id')->first();
    expect($log)->not->toBeNull();
    expect((string) data_get($log->context, 'type'))->toBe('sale.quick_refund');
});

test('quick refund now requires only business reason and creates queue', function () {
    $owner = makeUserWithRole(UserRole::Owner->value);
    $product = makeProductWithStock(5);

    $this->actingAs($owner)->post(route('pos.checkout'), [
        'customer_name' => 'Refund Approval Test',
        'discount_amount' => 0,
        'tax_amount' => 0,
        'paid_amount' => 15000,
        'status' => 'paid',
        'checkout_token' => 'token-refund-approval-1',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1, 'discount_amount' => 0],
        ],
    ])->assertRedirect();

    $sale = Sale::query()->latest('id')->first();

    $this->actingAs($owner)
        ->from(route('sales.show', $sale))
        ->post(route('sales.quick-refund', $sale), [
            'reason' => 'Refund perlu approval',
        ])
        ->assertRedirect(route('sales.show', $sale));

    $approval = ApprovalRequest::query()->where('type', 'sale.quick_refund')->latest('id')->first();
    expect($approval)->not->toBeNull();
    expect((string) $approval->status)->toBe('pending');
});

test('admin can create quick void approval request', function () {
    $admin = makeUserWithRole(UserRole::Admin->value);
    $product = makeProductWithStock(7);

    $this->actingAs($admin)->post(route('pos.checkout'), [
        'customer_name' => 'Void Admin Test',
        'discount_amount' => 0,
        'tax_amount' => 0,
        'paid_amount' => 0,
        'status' => 'pending',
        'checkout_token' => 'token-void-admin-1',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1, 'discount_amount' => 0],
        ],
    ])->assertRedirect();

    $sale = Sale::query()->latest('id')->first();
    expect((string) $sale->status->value)->toBe('pending');

    $this->actingAs($admin)
        ->post(route('sales.quick-void', $sale), [
            'reason' => 'Pending tidak dilanjutkan',
        ])
        ->assertRedirect();

    $sale = $sale->fresh();
    expect((string) $sale->status->value)->toBe('pending');
    expect((float) $sale->paid_amount)->toBe(0.0);

    $approval = ApprovalRequest::query()->where('type', 'sale.quick_void')->latest('id')->first();
    expect($approval)->not->toBeNull();
    expect((string) $approval->status)->toBe('pending');
});

test('kasir can settle pending sale from pos detail', function () {
    $kasir = makeUserWithRole(UserRole::Cashier->value);
    $product = makeProductWithStock(10);

    $this->actingAs($kasir)->post(route('pos.checkout'), [
        'customer_name' => 'Settle Pending Test',
        'discount_amount' => 0,
        'tax_amount' => 0,
        'paid_amount' => 0,
        'status' => 'pending',
        'checkout_token' => 'token-settle-pending-1',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1, 'discount_amount' => 0],
        ],
    ])->assertRedirect();

    $sale = Sale::query()->latest('id')->first();
    expect((string) $sale->status->value)->toBe('pending');

    $this->actingAs($kasir)
        ->post(route('sales.quick-settle-pending', $sale), [
            'payment_method' => 'cash',
            'paid_amount' => 20000,
        ])
        ->assertRedirect();

    $sale = $sale->fresh();
    expect((string) $sale->status->value)->toBe('paid');
    expect((string) $sale->payment_method)->toBe('cash');

    $log = CashierAuditLog::query()->where('action', 'pending_settled_pos')->latest('id')->first();
    expect($log)->not->toBeNull();
});

test('checkout blocks payment above method limit', function () {
    $kasir = makeUserWithRole(UserRole::Cashier->value);
    $product = makeProductWithStock(10);

    $this->actingAs($kasir)
        ->from(route('pos.index'))
        ->post(route('pos.checkout'), [
            'customer_name' => 'Limit Test',
            'discount_amount' => 0,
            'tax_amount' => 0,
            'paid_amount' => 25000000,
            'payment_method' => 'e_wallet',
            'status' => 'paid',
            'checkout_token' => 'token-limit-ewallet-1',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'discount_amount' => 0],
            ],
        ])
        ->assertRedirect(route('pos.index'))
        ->assertSessionHasErrors('paid_amount');
});

test('pos page includes draft schema v2 and legacy migration hooks', function () {
    $cashier = makeUserWithRole(UserRole::Cashier->value);

    $this->actingAs($cashier)
        ->get(route('pos.index'))
        ->assertOk()
        ->assertSee('pos_checkout_draft_v2_', false)
        ->assertSee('pos_checkout_draft_v1_', false)
        ->assertSee('normalizeCheckoutDraft(', false)
        ->assertSee('checkoutDraftSchemaVersion: 2', false);
});
