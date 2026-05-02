<?php

use App\Enums\UserRole;
use App\Models\ApprovalRequest;
use App\Models\StoreSetting;
use App\Models\User;

test('approval auto expire rejects overdue pending approvals', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin->value]);

    StoreSetting::query()->updateOrCreate(
        ['id' => 1],
        [
            'name' => 'BINTANG',
            'telegram_enabled' => false,
            'telegram_approval_sla_minutes' => 120,
        ]
    );

    $oldPending = ApprovalRequest::query()->create([
        'type' => 'sale.quick_refund',
        'status' => 'pending',
        'requested_by' => $admin->id,
        'title' => 'Pending Lama',
        'reason' => 'Uji auto expire',
        'payload' => [],
    ]);
    $oldPending->created_at = now()->subMinutes(2000);
    $oldPending->updated_at = now()->subMinutes(2000);
    $oldPending->save();

    $freshPending = ApprovalRequest::query()->create([
        'type' => 'sale.quick_void',
        'status' => 'pending',
        'requested_by' => $admin->id,
        'title' => 'Pending Baru',
        'reason' => 'Belum expire',
        'payload' => [],
    ]);
    $freshPending->created_at = now()->subMinutes(30);
    $freshPending->updated_at = now()->subMinutes(30);
    $freshPending->save();

    $this->artisan('approval:auto-expire --minutes=60')
        ->assertSuccessful();

    $expired = $oldPending->fresh();
    expect($expired->status)->toBe('rejected');
    expect((string) $expired->review_note)->toContain('[AUTO-EXPIRED]');
    expect((string) data_get((array) $expired->payload, 'auto_expired_at'))->not->toBe('');
    expect((int) data_get((array) $expired->payload, 'auto_expire_threshold_minutes'))->toBe(60);

    expect($freshPending->fresh()->status)->toBe('pending');
});

test('approval auto expire uses store approval rule threshold when no override', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin->value]);

    StoreSetting::query()->updateOrCreate(
        ['id' => 1],
        [
            'name' => 'BINTANG',
            'telegram_enabled' => false,
            'telegram_approval_sla_minutes' => 120,
            'approval_rules' => [
                'export_min_rows' => 300,
                'export_min_total' => 100000000,
                'auto_expire_minutes' => 60,
            ],
        ]
    );

    $candidate = ApprovalRequest::query()->create([
        'type' => 'sale.quick_void',
        'status' => 'pending',
        'requested_by' => $admin->id,
        'title' => 'Pending Rule',
        'reason' => 'Uji auto expire rule',
        'payload' => [],
    ]);
    $candidate->created_at = now()->subMinutes(70);
    $candidate->updated_at = now()->subMinutes(70);
    $candidate->save();

    $this->artisan('approval:auto-expire')->assertSuccessful();

    expect($candidate->fresh()->status)->toBe('rejected');
    expect((int) data_get((array) $candidate->fresh()->payload, 'auto_expire_threshold_minutes'))->toBe(60);
});
