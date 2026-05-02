<?php

use App\Enums\UserRole;
use App\Models\CashierAuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\UploadedFile;

function makeOwner(): User
{
    return User::factory()->create(['role' => UserRole::Owner->value]);
}

function makeActiveProduct(string $name, int $stock): Product
{
    $category = Category::query()->create([
        'name' => 'Kategori '.$name,
        'description' => 'Kategori test',
        'is_active' => true,
    ]);

    return Product::query()->create([
        'category_id' => $category->id,
        'name' => $name,
        'sku' => 'SKU-'.strtoupper(substr(md5($name), 0, 6)),
        'barcode' => (string) random_int(1000000000000, 9999999999999),
        'purchase_price' => 1000,
        'selling_price' => 2000,
        'stock' => $stock,
        'low_stock_threshold' => 1,
        'unit' => 'pcs',
        'is_active' => true,
    ]);
}

test('owner can start stock opname session and snapshot active products', function () {
    $owner = makeOwner();
    $productA = makeActiveProduct('Produk A', 5);
    $productB = makeActiveProduct('Produk B', 8);

    $this->actingAs($owner)
        ->post(route('stock-opnames.store'), ['note' => 'Opname awal minggu'])
        ->assertRedirect();

    $session = StockOpname::query()->latest('id')->first();
    expect($session)->not->toBeNull();
    expect($session->status)->toBe('open');
    expect((int) $session->total_items)->toBe(2);
    expect($session->items()->count())->toBe(2);

    $itemA = $session->items()->where('product_id', $productA->id)->first();
    $itemB = $session->items()->where('product_id', $productB->id)->first();
    expect((int) $itemA->system_stock)->toBe(5);
    expect((int) $itemB->system_stock)->toBe(8);
});

test('posting stock opname updates product stock and writes audit log', function () {
    $owner = makeOwner();
    $productA = makeActiveProduct('Produk C', 10);
    $productB = makeActiveProduct('Produk D', 2);

    $this->actingAs($owner)->post(route('stock-opnames.store'), ['note' => 'Opname posting'])->assertRedirect();
    $session = StockOpname::query()->latest('id')->first();
    $items = $session->items()->orderBy('id')->get();

    $payload = [
        'posted_note' => 'Selisih rak belakang',
        'items' => [
            ['id' => $items[0]->id, 'counted_stock' => 7], // diff -3
            ['id' => $items[1]->id, 'counted_stock' => 4], // diff +2
        ],
    ];

    $this->actingAs($owner)
        ->post(route('stock-opnames.post', $session), $payload)
        ->assertRedirect();

    $session = $session->fresh();
    expect($session->status)->toBe('posted');
    expect((int) $session->adjusted_items)->toBe(2);
    expect((int) $session->total_difference)->toBe(-1);
    expect((int) $productA->fresh()->stock)->toBe(7);
    expect((int) $productB->fresh()->stock)->toBe(4);

    $log = CashierAuditLog::query()->where('action', 'stock_opname_posted')->latest('id')->first();
    expect($log)->not->toBeNull();
    expect((int) data_get($log->context, 'stock_opname_id'))->toBe((int) $session->id);
});

test('posted stock opname can be duplicated into new open session', function () {
    $owner = makeOwner();
    makeActiveProduct('Produk E', 9);
    makeActiveProduct('Produk F', 3);

    $this->actingAs($owner)->post(route('stock-opnames.store'), ['note' => 'Sumber duplikasi'])->assertRedirect();
    $source = StockOpname::query()->latest('id')->first();
    $items = $source->items()->orderBy('id')->get();

    $this->actingAs($owner)->post(route('stock-opnames.post', $source), [
        'posted_note' => 'Posting sumber',
        'items' => $items->map(fn ($item) => [
            'id' => $item->id,
            'counted_stock' => (int) $item->system_stock,
        ])->values()->all(),
    ])->assertRedirect();

    $this->actingAs($owner)->post(route('stock-opnames.duplicate', $source->fresh()))->assertRedirect();

    $new = StockOpname::query()->latest('id')->first();
    expect($new->id)->not->toBe((int) $source->id);
    expect($new->status)->toBe('open');
    expect((int) $new->total_items)->toBe((int) $source->total_items);
    expect($new->items()->count())->toBe((int) $source->total_items);
    expect($new->items()->whereNotNull('counted_stock')->count())->toBe(0);

    $log = CashierAuditLog::query()->where('action', 'stock_opname_duplicated')->latest('id')->first();
    expect($log)->not->toBeNull();
    expect((int) data_get($log->context, 'source_stock_opname_id'))->toBe((int) $source->id);
    expect((int) data_get($log->context, 'new_stock_opname_id'))->toBe((int) $new->id);
});

test('posting supports partial submitted rows as long as all items already filled', function () {
    $owner = makeOwner();
    $productA = makeActiveProduct('Produk G', 10);
    $productB = makeActiveProduct('Produk H', 5);

    $this->actingAs($owner)->post(route('stock-opnames.store'), ['note' => 'Partial submit guard'])->assertRedirect();
    $session = StockOpname::query()->latest('id')->first();
    $items = $session->items()->orderBy('id')->get();

    StockOpnameItem::query()->whereKey($items[1]->id)->update([
        'counted_stock' => 7,
        'difference' => 2,
    ]);

    $this->actingAs($owner)
        ->post(route('stock-opnames.post', $session), [
            'items' => [
                ['id' => $items[0]->id, 'counted_stock' => 8],
            ],
            'posted_note' => 'submit dari tampilan terfilter',
        ])->assertRedirect();

    $session = $session->fresh();
    expect($session->status)->toBe('posted');
    expect((int) $productA->fresh()->stock)->toBe(8);
    expect((int) $productB->fresh()->stock)->toBe(7);
});

test('posting is blocked when manager approval setting enabled and credentials invalid', function () {
    $owner = makeOwner();
    $manager = User::factory()->create([
        'role' => UserRole::Admin->value,
        'email' => 'manager-opname@example.com',
        'password' => Hash::make('manager-pass-123'),
    ]);
    makeActiveProduct('Produk I', 4);

    StoreSetting::query()->updateOrCreate(['id' => 1], [
        'name' => 'BINTANG',
        'stock_opname_require_manager_approval' => true,
    ]);

    $this->actingAs($owner)->post(route('stock-opnames.store'), ['note' => 'Butuh approval'])->assertRedirect();
    $session = StockOpname::query()->latest('id')->first();
    $items = $session->items()->orderBy('id')->get();

    $this->actingAs($owner)
        ->post(route('stock-opnames.post', $session), [
            'items' => [
                ['id' => $items[0]->id, 'counted_stock' => 4],
            ],
            'manager_approval_email' => $manager->email,
            'manager_approval_password' => 'wrong',
        ])
        ->assertSessionHasErrors('manager_approval_email');
});

test('stock opname detail renders only-diff toggle and draft restore modal hooks', function () {
    $owner = makeOwner();
    makeActiveProduct('Produk J', 12);
    $this->actingAs($owner)->post(route('stock-opnames.store'), ['note' => 'UI hooks'])->assertRedirect();
    $session = StockOpname::query()->latest('id')->first();

    $response = $this->actingAs($owner)->get(route('stock-opnames.show', $session));
    $response->assertOk();
    $response->assertSee('Hanya tampilkan item selisih');
    $response->assertSee('Abaikan Draft');
    $response->assertSee('Restore Draft');
    $response->assertSee('stock_opname_draft_');
});

test('stock opname detail renders posting guardrail script and confirmation modal', function () {
    $owner = makeOwner();
    makeActiveProduct('Produk K', 15);
    $this->actingAs($owner)->post(route('stock-opnames.store'), ['note' => 'Guardrail UI'])->assertRedirect();
    $session = StockOpname::query()->latest('id')->first();

    $response = $this->actingAs($owner)->get(route('stock-opnames.show', $session));
    $response->assertOk();
    $response->assertSee('Konfirmasi Posting Opname');
    $response->assertSee('guardrailWarning');
    $response->assertSee('diffItems >= 50', false);
    $response->assertSee('totalAbsDiff >= 500', false);
});

test('end to end stock opname flow list detail import post and audit works', function () {
    $owner = makeOwner();
    $productA = makeActiveProduct('Produk L', 10);
    $productB = makeActiveProduct('Produk M', 20);

    $this->actingAs($owner)
        ->post(route('stock-opnames.store'), ['note' => 'E2E flow'])
        ->assertRedirect();

    $session = StockOpname::query()->latest('id')->firstOrFail();

    $this->actingAs($owner)
        ->get(route('stock-opnames.index', ['quick' => 'open', 'sort' => 'progress_desc']))
        ->assertOk()
        ->assertSee('OPEN saja')
        ->assertSee($session->code);

    $csv = implode("\n", [
        'item_id,sku,product_name,system_stock,counted_stock',
        $session->items()->where('product_id', $productA->id)->value('id').','.$productA->sku.','.$productA->name.',10,12',
        $session->items()->where('product_id', $productB->id)->value('id').','.$productB->sku.','.$productB->name.',20,18',
    ]);
    $file = UploadedFile::fake()->createWithContent('opname.csv', $csv);

    $this->actingAs($owner)
        ->post(route('stock-opnames.import.csv', $session), [
            'csv_file' => $file,
            'preview_only' => 1,
        ])
        ->assertRedirect(route('stock-opnames.show', $session));

    $this->actingAs($owner)
        ->get(route('stock-opnames.show', $session))
        ->assertOk()
        ->assertSee('Preview Import CSV')
        ->assertSee('CSV terlihat valid');

    $fileApply = UploadedFile::fake()->createWithContent('opname-apply.csv', $csv);
    $this->actingAs($owner)
        ->post(route('stock-opnames.import.csv', $session), [
            'csv_file' => $fileApply,
            'preview_only' => 0,
        ])
        ->assertRedirect(route('stock-opnames.show', $session));

    $this->actingAs($owner)
        ->post(route('stock-opnames.post', $session), [
            'posted_note' => 'Posting dari import CSV',
            'items' => [],
        ])
        ->assertRedirect(route('stock-opnames.show', $session));

    expect($session->fresh()->status)->toBe('posted');
    expect((int) $productA->fresh()->stock)->toBe(12);
    expect((int) $productB->fresh()->stock)->toBe(18);

    $importLog = CashierAuditLog::query()->where('action', 'stock_opname_csv_imported')->latest('id')->first();
    $postLog = CashierAuditLog::query()->where('action', 'stock_opname_posted')->latest('id')->first();
    expect($importLog)->not->toBeNull();
    expect($postLog)->not->toBeNull();
    expect((int) data_get($postLog->context, 'stock_opname_id'))->toBe((int) $session->id);
});
