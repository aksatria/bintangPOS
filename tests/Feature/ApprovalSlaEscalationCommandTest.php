<?php

use App\Enums\UserRole;
use App\Models\ApprovalRequest;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

function makeEscalationUser(string $role): User
{
    return User::factory()->create(['role' => $role]);
}

beforeEach(function () {
    putenv('TELEGRAM_BOT_TOKEN=test-bot-token');
    putenv('TELEGRAM_CHAT_ID=123456789');

    StoreSetting::query()->updateOrCreate(
        ['id' => 1],
        [
            'name' => 'BINTANG',
            'telegram_enabled' => true,
            'telegram_daily_summary_enabled' => false,
            'approval_rules' => [
                'export_min_rows' => 300,
                'export_min_total' => 100000000,
                'auto_expire_minutes' => 1440,
                'sla_minutes_sale' => 120,
                'sla_minutes_export' => 360,
                'reject_reason_presets' => ['Data tidak valid'],
            ],
            'telegram_approval_sla_minutes' => 120,
        ]
    );
});

test('escalation command level 2 prefers admin assignee', function () {
    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $owner = makeEscalationUser(UserRole::Owner->value);
    $admin = makeEscalationUser(UserRole::Admin->value);
    $requester = makeEscalationUser(UserRole::Admin->value);

    $approval = ApprovalRequest::query()->create([
        'type' => 'sale.quick_refund',
        'status' => 'pending',
        'requested_by' => $requester->id,
        'assigned_to' => $owner->id,
        'title' => 'L2 escalation prefer admin',
        'reason' => 'Uji',
        'payload' => [],
    ]);
    $approval->forceFill([
        'created_at' => now()->subMinutes(250),
        'updated_at' => now()->subMinutes(250),
    ])->saveQuietly();

    Artisan::call('approval:send-sla-escalation');

    $fresh = $approval->fresh();
    $assignedUser = User::query()->find((int) $fresh->assigned_to);
    expect((string) ($assignedUser?->role?->value ?? $assignedUser?->role ?? ''))->toBe('admin');
    expect((int) data_get((array) $fresh->payload, 'sla_escalation_last_level', 0))->toBe(2);
});

test('escalation command level 3 assigns owner', function () {
    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $owner = makeEscalationUser(UserRole::Owner->value);
    $admin = makeEscalationUser(UserRole::Admin->value);
    $requester = makeEscalationUser(UserRole::Admin->value);

    $approval = ApprovalRequest::query()->create([
        'type' => 'sale.quick_void',
        'status' => 'pending',
        'requested_by' => $requester->id,
        'assigned_to' => $admin->id,
        'title' => 'L3 escalation assign owner',
        'reason' => 'Uji',
        'payload' => [],
    ]);
    $approval->forceFill([
        'created_at' => now()->subMinutes(520),
        'updated_at' => now()->subMinutes(520),
    ])->saveQuietly();

    Artisan::call('approval:send-sla-escalation');

    $fresh = $approval->fresh();
    expect((int) $fresh->assigned_to)->toBe((int) $owner->id);
    expect((int) data_get((array) $fresh->payload, 'sla_escalation_last_level', 0))->toBe(3);
});

test('escalation command respects lock and cooldown', function () {
    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $admin = makeEscalationUser(UserRole::Admin->value);
    $requester = makeEscalationUser(UserRole::Admin->value);

    $approval = ApprovalRequest::query()->create([
        'type' => 'sale.quick_refund',
        'status' => 'pending',
        'requested_by' => $requester->id,
        'assigned_to' => $admin->id,
        'title' => 'Lock and cooldown check',
        'reason' => 'Uji',
        'payload' => [],
    ]);
    $approval->forceFill([
        'created_at' => now()->subMinutes(250),
        'updated_at' => now()->subMinutes(250),
    ])->saveQuietly();

    $lock = Cache::lock('approval:sla-escalation:lock', 540);
    $lock->get();
    Artisan::call('approval:send-sla-escalation');
    $lock->release();

    Http::assertNothingSent();

    $cooldownKey = "approval:sla-escalation:{$approval->id}:L2";
    Cache::put($cooldownKey, 1, now()->addMinutes(10));

    Artisan::call('approval:send-sla-escalation');
    Http::assertNothingSent();

    expect((array) ($approval->fresh()->payload ?? []))->not->toHaveKey('sla_escalation_last_level');
});
