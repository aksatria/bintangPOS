<?php

use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Models\ApprovalRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Support\Carbon;

function makeQueueUser(string $role): User
{
    return User::factory()->create(['role' => $role]);
}

test('approval queue page requires approvals permission', function () {
    $cashier = makeQueueUser(UserRole::Cashier->value);

    $this->actingAs($cashier)
        ->get(route('admin.approvals.index'))
        ->assertRedirect(route('dashboard'));
});

test('approving refund request executes stock rollback and sale cancellation', function () {
    $owner = makeQueueUser(UserRole::Owner->value);
    $approver = makeQueueUser(UserRole::Admin->value);
    $category = Category::query()->create([
        'name' => 'Kategori Queue',
        'description' => 'Kategori uji queue',
        'is_active' => true,
    ]);
    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Produk Queue Refund',
        'sku' => 'SKU-QUEUE-REFUND',
        'barcode' => 'BR-QUEUE-REFUND',
        'purchase_price' => 10000,
        'selling_price' => 15000,
        'stock' => 5,
        'low_stock_threshold' => 2,
        'unit' => 'pcs',
        'is_active' => true,
    ]);

    $sale = Sale::query()->create([
        'invoice_number' => 'INV-QUEUE-REFUND-1',
        'user_id' => $owner->id,
        'customer_name' => 'Pelanggan Queue',
        'subtotal' => 30000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => 30000,
        'paid_amount' => 30000,
        'change_amount' => 0,
        'status' => SaleStatus::Paid->value,
        'payment_method' => 'cash',
        'sold_at' => Carbon::now(),
    ]);

    SaleItem::query()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'sku' => $product->sku,
        'quantity' => 2,
        'unit_price' => 15000,
        'purchase_price' => 10000,
        'discount_amount' => 0,
        'subtotal' => 30000,
    ]);

    $product->decrement('stock', 2);

    $approval = ApprovalRequest::query()->create([
        'type' => 'sale.quick_refund',
        'status' => 'pending',
        'requested_by' => $owner->id,
        'title' => 'Approval Refund INV-QUEUE-REFUND-1',
        'reason' => 'Salah input',
        'payload' => [
            'sale_id' => $sale->id,
            'invoice' => $sale->invoice_number,
            'reason' => 'Salah input',
            'total' => 30000,
        ],
    ]);

    $this->actingAs($approver)
        ->post(route('admin.approvals.approve', $approval), [
            'review_note' => 'Valid, lanjutkan.',
        ])
        ->assertRedirect();

    expect((string) $approval->fresh()->status)->toBe('approved');
    expect((string) $sale->fresh()->status->value)->toBe('cancelled');
    expect((int) $product->fresh()->stock)->toBe(5);
});

test('bulk approve and bulk reject process pending approvals', function () {
    $owner = makeQueueUser(UserRole::Owner->value);
    $requester = makeQueueUser(UserRole::Admin->value);

    $a1 = ApprovalRequest::query()->create([
        'type' => 'report.export.pdf',
        'status' => 'pending',
        'requested_by' => $requester->id,
        'title' => 'Bulk Approve 1',
        'reason' => 'Uji bulk approve',
        'payload' => ['fingerprint' => sha1('bulk-approve-1')],
    ]);
    $a2 = ApprovalRequest::query()->create([
        'type' => 'report.export.excel',
        'status' => 'pending',
        'requested_by' => $requester->id,
        'title' => 'Bulk Reject 1',
        'reason' => 'Uji bulk reject',
        'payload' => ['fingerprint' => sha1('bulk-reject-1')],
    ]);

    $this->actingAs($owner)
        ->post(route('admin.approvals.bulk-approve'), [
            'approval_ids' => [$a1->id],
            'review_note' => 'Setuju batch',
        ])
        ->assertRedirect();

    expect((string) $a1->fresh()->status)->toBe('approved');

    $this->actingAs($owner)
        ->post(route('admin.approvals.bulk-reject'), [
            'approval_ids' => [$a2->id],
            'review_note' => 'Tolak batch karena data kurang.',
        ])
        ->assertRedirect();

    expect((string) $a2->fresh()->status)->toBe('rejected');
});

test('approved export request can be executed directly from approval queue', function () {
    $owner = makeQueueUser(UserRole::Owner->value);
    $admin = makeQueueUser(UserRole::Admin->value);

    $sale = Sale::query()->create([
        'user_id' => $admin->id,
        'invoice_number' => 'INV-QUEUE-EXPORT-1',
        'customer_name' => 'Pelanggan Queue Export',
        'subtotal' => 120000000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => 120000000,
        'paid_amount' => 120000000,
        'change_amount' => 0,
        'payment_method' => 'qris',
        'status' => SaleStatus::Paid->value,
        'sold_at' => now(),
        'note' => '[QRIS] Ref: QUEUE-EXP-1 | Issuer: Test',
    ]);

    $fingerprint = sha1(json_encode([
        'format' => 'pdf',
        'start_date' => now()->toDateString(),
        'end_date' => now()->toDateString(),
        'customer_id' => 0,
        'payment_method' => 'all',
        'qris_reference' => '',
        'selected_ids' => [$sale->id],
        'selected_count' => 1,
        'selected_total' => round(120000000.0, 2),
    ]));

    $approval = ApprovalRequest::query()->create([
        'type' => 'report.export.pdf',
        'status' => 'approved',
        'requested_by' => $admin->id,
        'reviewed_by' => $owner->id,
        'title' => 'Approved Export Queue',
        'reason' => 'Audit bulanan',
        'payload' => [
            'format' => 'pdf',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'customer_id' => 0,
            'payment_method' => 'all',
            'qris_reference' => '',
            'selected_ids' => [$sale->id],
            'selected_count' => 1,
            'selected_total' => 120000000,
            'fingerprint' => $fingerprint,
        ],
        'reviewed_at' => now(),
    ]);

    $this->actingAs($owner)
        ->get(route('admin.approvals.execute-export', $approval))
        ->assertRedirectContains(route('reports.export.pdf'));

    $payload = (array) $approval->fresh()->payload;
    expect((string) data_get($payload, 'export_executed_at'))->not->toBe('');
    expect((string) data_get($payload, 'export_executed_by_name'))->toBe((string) $owner->name);

    $this->actingAs($owner)
        ->from(route('admin.approvals.index'))
        ->get(route('admin.approvals.execute-export', $approval))
        ->assertRedirect(route('admin.approvals.index'))
        ->assertSessionHas('error');
});

test('approval queue execution filter shows pending and done export execution correctly', function () {
    $owner = makeQueueUser(UserRole::Owner->value);
    $admin = makeQueueUser(UserRole::Admin->value);

    $pendingExec = ApprovalRequest::query()->create([
        'type' => 'report.export.excel',
        'status' => 'approved',
        'requested_by' => $admin->id,
        'reviewed_by' => $owner->id,
        'title' => 'Exec Pending',
        'reason' => 'Pending exec',
        'payload' => ['format' => 'excel', 'fingerprint' => sha1('exec-pending')],
        'reviewed_at' => now(),
    ]);

    $doneExec = ApprovalRequest::query()->create([
        'type' => 'report.export.pdf',
        'status' => 'approved',
        'requested_by' => $admin->id,
        'reviewed_by' => $owner->id,
        'title' => 'Exec Done',
        'reason' => 'Done exec',
        'payload' => [
            'format' => 'pdf',
            'fingerprint' => sha1('exec-done'),
            'export_executed_at' => now()->toIso8601String(),
            'export_executed_by_name' => $owner->name,
        ],
        'reviewed_at' => now(),
    ]);

    $this->actingAs($owner)
        ->get(route('admin.approvals.index', ['status' => 'all', 'execution' => 'pending']))
        ->assertOk()
        ->assertSee($pendingExec->title)
        ->assertDontSee($doneExec->title);

    $this->actingAs($owner)
        ->get(route('admin.approvals.index', ['status' => 'all', 'execution' => 'done']))
        ->assertOk()
        ->assertSee($doneExec->title)
        ->assertDontSee($pendingExec->title);
});

test('snooze is blocked for escalation level 3 approvals', function () {
    $owner = makeQueueUser(UserRole::Owner->value);
    $admin = makeQueueUser(UserRole::Admin->value);

    $approval = ApprovalRequest::query()->create([
        'type' => 'sale.quick_refund',
        'status' => 'pending',
        'requested_by' => $admin->id,
        'title' => 'Escalation L3 Refund',
        'reason' => 'Menunggu keputusan',
        'payload' => [
            'sla_escalation_last_level' => 3,
            'sla_escalation_levels' => [
                '1' => now()->subHours(10)->toIso8601String(),
                '2' => now()->subHours(6)->toIso8601String(),
                '3' => now()->subHours(2)->toIso8601String(),
            ],
        ],
    ]);

    $this->actingAs($owner)
        ->post(route('admin.approvals.snooze', $approval), [
            'minutes' => 15,
            'note' => 'Coba snooze',
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($approval->fresh()->snoozed_until)->toBeNull();
});

test('approval queue shows escalation timeline and auto-assigned badge', function () {
    $owner = makeQueueUser(UserRole::Owner->value);
    $admin = makeQueueUser(UserRole::Admin->value);

    $approval = ApprovalRequest::query()->create([
        'type' => 'sale.quick_refund',
        'status' => 'pending',
        'requested_by' => $admin->id,
        'assigned_to' => $owner->id,
        'title' => 'Timeline Escalation Approval',
        'reason' => 'Uji timeline',
        'payload' => [
            'sla_escalation_last_level' => 2,
            'sla_escalation_auto_assigned_level' => 2,
            'sla_escalation_auto_assigned_name' => $owner->name,
            'sla_escalation_levels' => [
                '1' => now()->subHours(4)->toIso8601String(),
                '2' => now()->subHours(1)->toIso8601String(),
            ],
        ],
    ]);

    $this->actingAs($owner)
        ->get(route('admin.approvals.index'))
        ->assertOk()
        ->assertSee($approval->title)
        ->assertSee('Auto-assigned (L2)')
        ->assertSee('L1')
        ->assertSee('L2');
});
