<?php

use App\Enums\UserRole;
use App\Models\ApprovalRequest;
use App\Models\CashierAuditLog;
use App\Models\StoreSetting;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Carbon;

function makeAdminUser(): User
{
    return User::factory()->create(['role' => UserRole::Admin->value]);
}

function seedReportSale(User $admin, string $invoice, string $status = 'paid', string $note = '', ?Carbon $soldAt = null): Sale
{
    return Sale::query()->create([
        'user_id' => $admin->id,
        'invoice_number' => $invoice,
        'customer_name' => 'Pelanggan Test',
        'subtotal' => 10000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'rounding_amount' => 0,
        'admin_fee_amount' => 0,
        'total_amount' => 10000,
        'paid_amount' => $status === 'paid' ? 10000 : 0,
        'change_amount' => 0,
        'payment_method' => 'qris',
        'status' => $status,
        'note' => $note,
        'sold_at' => $soldAt ?: now(),
    ]);
}

test('reports index filters by qris reference and paginates transaction list', function () {
    $admin = makeAdminUser();

    foreach (range(1, 23) as $i) {
        seedReportSale(
            $admin,
            sprintf('INV-QRIS-%04d', $i),
            'paid',
            '[QRIS] Ref: AUTO-REF-123 | Issuer: Test Issuer',
            now()->subMinutes($i)
        );
    }
    seedReportSale($admin, 'INV-NON-QRIS-0001', 'paid', 'Tanpa ref qris', now()->subDay());

    $response = $this->actingAs($admin)
        ->get(route('reports.index', [
            'period' => 'custom',
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->toDateString(),
            'qris_reference' => 'AUTO-REF-123',
        ]));

    $response->assertOk()
        ->assertSee('INV-QRIS-0001')
        ->assertDontSee('INV-NON-QRIS-0001')
        ->assertSee('page=2', false);
});

test('reports export excel supports qris reference filter', function () {
    $admin = makeAdminUser();

    seedReportSale($admin, 'INV-EXCEL-0001', 'paid', '[QRIS] Ref: AUTO-EXCEL-1 | Issuer: Test');
    seedReportSale($admin, 'INV-EXCEL-0002', 'paid', '[QRIS] Ref: AUTO-EXCEL-2 | Issuer: Test');

    $this->actingAs($admin)
        ->get(route('reports.export.excel', [
            'period' => 'custom',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->toDateString(),
            'qris_reference' => 'AUTO-EXCEL-1',
        ]))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('reports export pdf supports qris reference filter', function () {
    $admin = makeAdminUser();

    seedReportSale($admin, 'INV-PDF-0001', 'paid', '[QRIS] Ref: AUTO-PDF-1 | Issuer: Test');

    $this->actingAs($admin)
        ->get(route('reports.export.pdf', [
            'period' => 'custom',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->toDateString(),
            'qris_reference' => 'AUTO-PDF-1',
        ]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('large export requires reason before entering approval queue', function () {
    $admin = makeAdminUser();
    $bigSale = seedReportSale($admin, 'INV-LARGE-0001', 'paid', '[QRIS] Ref: AUTO-LARGE-1 | Issuer: Test');
    $bigSale->update(['total_amount' => 120000000, 'paid_amount' => 120000000]);

    $this->actingAs($admin)
        ->from(route('reports.index'))
        ->get(route('reports.export.pdf', [
            'period' => 'custom',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->toDateString(),
            'selected_ids' => [$bigSale->id],
            'export_reason' => '',
        ]))
        ->assertRedirect(route('reports.index'))
        ->assertSessionHasErrors(['export_reason']);
});

test('large export creates pending approval queue request', function () {
    $admin = makeAdminUser();

    $bigSale = seedReportSale($admin, 'INV-LARGE-0002', 'paid', '[QRIS] Ref: AUTO-LARGE-2 | Issuer: Test');
    $bigSale->update(['total_amount' => 120000000, 'paid_amount' => 120000000]);

    $response = $this->actingAs($admin)
        ->from(route('reports.index'))
        ->get(route('reports.export.excel', [
            'period' => 'custom',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->toDateString(),
            'selected_ids' => [$bigSale->id],
            'export_reason' => 'Audit bulanan owner',
        ]));
    $response->assertStatus(302);
    expect((string) $response->headers->get('Location'))->toContain(route('reports.index'));

    $approval = ApprovalRequest::query()
        ->where('type', 'report.export.excel')
        ->latest('id')
        ->first();

    expect($approval)->not->toBeNull();
    expect((string) $approval->status)->toBe('pending');
    expect((int) data_get($approval->payload, 'selected_count'))->toBe(1);
});

test('export threshold follows store approval rules', function () {
    $admin = makeAdminUser();

    StoreSetting::query()->updateOrCreate(
        ['id' => 1],
        [
            'name' => 'BINTANG',
            'approval_rules' => [
                'export_min_rows' => 1,
                'export_min_total' => 999999999999,
                'auto_expire_minutes' => 1440,
            ],
        ]
    );

    $sale = seedReportSale($admin, 'INV-RULE-0001', 'paid', '[QRIS] Ref: AUTO-RULE-1 | Issuer: Test');
    $sale->update(['total_amount' => 20000, 'paid_amount' => 20000]);

    $response = $this->actingAs($admin)
        ->from(route('reports.index'))
        ->get(route('reports.export.pdf', [
            'period' => 'custom',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->toDateString(),
            'selected_ids' => [$sale->id],
            'export_reason' => 'Uji threshold rule',
        ]));
    $response->assertStatus(302);
    expect((string) $response->headers->get('Location'))->toContain(route('reports.index'));

    $approval = ApprovalRequest::query()
        ->where('type', 'report.export.pdf')
        ->latest('id')
        ->first();

    expect($approval)->not->toBeNull();
    expect((string) $approval->status)->toBe('pending');
    expect((int) data_get($approval->payload, 'selected_count'))->toBe(1);
});

test('approved large export can proceed without re-queue', function () {
    $admin = makeAdminUser();
    $owner = User::factory()->create(['role' => UserRole::Owner->value]);

    $bigSale = seedReportSale($admin, 'INV-LARGE-0003', 'paid', '[QRIS] Ref: AUTO-LARGE-3 | Issuer: Test');
    $bigSale->update(['total_amount' => 120000000, 'paid_amount' => 120000000]);

    $response = $this->actingAs($admin)
        ->get(route('reports.export.pdf', [
            'period' => 'custom',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->toDateString(),
            'selected_ids' => [$bigSale->id],
            'export_reason' => 'Audit bulanan owner',
        ]));
    $response->assertStatus(302);
    expect((string) $response->headers->get('Location'))->toContain(route('reports.index'));

    $approval = ApprovalRequest::query()->where('type', 'report.export.pdf')->latest('id')->first();
    expect($approval)->not->toBeNull();

    $this->actingAs($owner)
        ->post(route('admin.approvals.approve', $approval), ['review_note' => 'OK'])
        ->assertRedirect();

    $this->actingAs($admin)
        ->get(route('reports.export.pdf', [
            'period' => 'custom',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->toDateString(),
            'selected_ids' => [$bigSale->id],
            'export_reason' => 'Audit bulanan owner',
        ]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $log = CashierAuditLog::query()
        ->where('action', 'report_export_pdf')
        ->latest('id')
        ->first();

    expect($log)->not->toBeNull();
    expect((string) data_get($log->context, 'approved_by'))->toBe('');
});

test('reports index rejects invalid period and payment method filters', function () {
    $admin = makeAdminUser();

    $this->actingAs($admin)
        ->from(route('reports.index'))
        ->get(route('reports.index', [
            'period' => 'yearly',
            'payment_method' => 'crypto',
        ]))
        ->assertRedirect(route('reports.index'))
        ->assertSessionHasErrors(['period', 'payment_method']);
});

test('reports export rejects custom period when dates are missing', function () {
    $admin = makeAdminUser();

    $this->actingAs($admin)
        ->from(route('reports.index'))
        ->get(route('reports.export.pdf', [
            'period' => 'custom',
            'start_date' => '',
            'end_date' => '',
        ]))
        ->assertRedirect(route('reports.index'))
        ->assertSessionHasErrors(['start_date', 'end_date']);
});

test('reports export rejects custom period that is too long', function () {
    $admin = makeAdminUser();

    $this->actingAs($admin)
        ->from(route('reports.index'))
        ->get(route('reports.export.excel', [
            'period' => 'custom',
            'start_date' => now()->subDays(500)->toDateString(),
            'end_date' => now()->toDateString(),
        ]))
        ->assertRedirect(route('reports.index'))
        ->assertSessionHasErrors(['end_date']);
});

test('reports index rejects invalid qris reference format', function () {
    $admin = makeAdminUser();

    $this->actingAs($admin)
        ->from(route('reports.index'))
        ->get(route('reports.index', [
            'period' => 'daily',
            'qris_reference' => '??',
        ]))
        ->assertRedirect(route('reports.index'))
        ->assertSessionHasErrors(['qris_reference']);
});
