<?php

use App\Enums\UserRole;
use App\Models\ApprovalRequest;
use App\Models\CashierAuditLog;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

function makeRuntimeAdminUser(): User
{
    return User::factory()->create(['role' => UserRole::Admin->value]);
}

function setRuntimeAdminPermissions(array $codes): void
{
    foreach ($codes as $code) {
        DB::table('permissions')->updateOrInsert(
            ['code' => $code],
            [
                'name' => $code,
                'group' => 'test',
                'description' => $code,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    DB::table('role_permissions')->where('role', 'admin')->delete();

    $permissionIds = DB::table('permissions')
        ->whereIn('code', $codes)
        ->pluck('id')
        ->all();

    foreach ($permissionIds as $permissionId) {
        DB::table('role_permissions')->insert([
            'role' => 'admin',
            'permission_id' => $permissionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

test('weekly sla telegram preview endpoint returns preview payload', function () {
    $admin = makeRuntimeAdminUser();
    setRuntimeAdminPermissions(['settings.notification.manage']);

    StoreSetting::query()->firstOrCreate(['id' => 1], ['name' => 'BINTANG']);

    $this->actingAs($admin)
        ->postJson(route('notification-settings.preview-weekly-sla'))
        ->assertOk()
        ->assertJson([
            'ok' => true,
        ])
        ->assertJsonStructure([
            'ok',
            'preview',
        ])
        ->assertJsonPath('preview', fn ($value) => is_string($value) && str_contains($value, 'Weekly SLA Report'));
});

test('runtime config export endpoint returns approval rules and rbac data', function () {
    $admin = makeRuntimeAdminUser();
    setRuntimeAdminPermissions(['settings.store.manage']);

    StoreSetting::query()->updateOrCreate(
        ['id' => 1],
        [
            'name' => 'BINTANG',
            'approval_rules' => [
                'export_min_rows' => 321,
                'export_min_total' => 123456789,
                'auto_expire_minutes' => 600,
                'sla_minutes_sale' => 120,
                'sla_minutes_export' => 360,
                'business_hours' => [
                    'start' => '08:00',
                    'end' => '22:00',
                    'workdays' => [1, 2, 3, 4, 5, 6],
                ],
                'reject_reason_presets' => ['Data kurang valid'],
            ],
        ]
    );

    $response = $this->actingAs($admin)
        ->get(route('store-settings.export-runtime-config'))
        ->assertOk();

    $json = $response->json();
    expect($json)->toBeArray();
    expect($json)->toHaveKeys(['exported_at', 'approval_rules', 'rbac']);
    expect((int) data_get($json, 'approval_rules.export_min_rows'))->toBe(321);
    expect(CashierAuditLog::query()->where('action', 'runtime_config_exported')->exists())->toBeTrue();
});

test('runtime config import endpoint updates approval rules and role permissions', function () {
    $admin = makeRuntimeAdminUser();
    setRuntimeAdminPermissions(['settings.store.manage']);

    DB::table('permissions')->updateOrInsert(
        ['code' => 'reports.view'],
        [
            'name' => 'reports.view',
            'group' => 'reports',
            'description' => 'reports.view',
            'created_at' => now(),
            'updated_at' => now(),
        ]
    );
    DB::table('permissions')->updateOrInsert(
        ['code' => 'approvals.manage'],
        [
            'name' => 'approvals.manage',
            'group' => 'settings',
            'description' => 'approvals.manage',
            'created_at' => now(),
            'updated_at' => now(),
        ]
    );

    StoreSetting::query()->updateOrCreate(
        ['id' => 1],
        ['name' => 'BINTANG']
    );

    $payload = [
        'approval_rules' => [
            'export_min_rows' => 777,
            'export_min_total' => 99999999,
            'auto_expire_minutes' => 720,
            'sla_minutes_sale' => 90,
            'sla_minutes_export' => 300,
            'business_hours' => [
                'start' => '09:00',
                'end' => '20:00',
                'workdays' => [1, 2, 3, 4, 5],
            ],
            'reject_reason_presets' => ['Perlu validasi ulang'],
        ],
        'rbac' => [
            'owner' => ['reports.view', 'approvals.manage'],
            'admin' => ['reports.view'],
            'kasir' => [],
        ],
    ];

    $this->actingAs($admin)
        ->post(route('store-settings.import-runtime-config'), [
            'config_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    $setting = StoreSetting::query()->firstOrFail();
    expect((int) data_get((array) $setting->approval_rules, 'export_min_rows'))->toBe(777);
    expect((string) data_get((array) $setting->approval_rules, 'business_hours.start'))->toBe('09:00');

    $ownerReportPerm = DB::table('role_permissions')
        ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
        ->where('role_permissions.role', 'owner')
        ->where('permissions.code', 'reports.view')
        ->exists();
    expect($ownerReportPerm)->toBeTrue();
    expect(CashierAuditLog::query()->where('action', 'runtime_config_imported')->exists())->toBeTrue();
});

test('runtime config dry run returns diff and does not apply changes', function () {
    $admin = makeRuntimeAdminUser();
    setRuntimeAdminPermissions(['settings.store.manage']);

    StoreSetting::query()->updateOrCreate(['id' => 1], [
        'name' => 'BINTANG',
        'approval_rules' => [
            'export_min_rows' => 100,
            'export_min_total' => 1000000,
            'auto_expire_minutes' => 120,
            'sla_minutes_sale' => 120,
            'sla_minutes_export' => 360,
            'overdue_alert_threshold' => 5,
            'business_hours' => ['start' => '08:00', 'end' => '22:00', 'workdays' => [1, 2, 3, 4, 5, 6, 7]],
            'reject_reason_presets' => ['A'],
        ],
    ]);

    $payload = [
        'approval_rules' => [
            'export_min_rows' => 222,
            'export_min_total' => 2000000,
            'auto_expire_minutes' => 200,
            'sla_minutes_sale' => 90,
            'sla_minutes_export' => 300,
            'overdue_alert_threshold' => 3,
            'business_hours' => ['start' => '09:00', 'end' => '21:00', 'workdays' => [1, 2, 3, 4, 5]],
            'reject_reason_presets' => ['B'],
        ],
        'rbac' => [
            'owner' => ['reports.view'],
            'admin' => ['reports.view'],
            'kasir' => [],
        ],
    ];

    $this->actingAs($admin)
        ->post(route('store-settings.import-runtime-config'), [
            'config_json' => json_encode($payload),
            'dry_run' => 1,
        ])
        ->assertRedirect()
        ->assertSessionHas('runtime_config_diff');

    $setting = StoreSetting::query()->firstOrFail();
    expect((int) data_get((array) $setting->approval_rules, 'export_min_rows'))->toBe(100);
    expect(CashierAuditLog::query()->where('action', 'runtime_config_import_dry_run')->exists())->toBeTrue();
});

test('sla anomaly alert command sends alert when overdue crosses threshold', function () {
    $admin = makeRuntimeAdminUser();
    StoreSetting::query()->updateOrCreate(['id' => 1], [
        'name' => 'BINTANG',
        'telegram_enabled' => true,
        'approval_rules' => [
            'export_min_rows' => 300,
            'export_min_total' => 100000000,
            'auto_expire_minutes' => 1440,
            'sla_minutes_sale' => 5,
            'sla_minutes_export' => 10,
            'overdue_alert_threshold' => 1,
            'business_hours' => ['start' => '00:00', 'end' => '23:59', 'workdays' => [1, 2, 3, 4, 5, 6, 7]],
            'reject_reason_presets' => ['X'],
        ],
        'telegram_override_chat_id' => '',
    ]);

    $approval = ApprovalRequest::query()->create([
        'type' => 'sale.quick_refund',
        'status' => 'pending',
        'requested_by' => $admin->id,
        'title' => 'Overdue Test',
        'reason' => 'Test',
        'payload' => [
            'sale_id' => 999,
            'invoice' => 'UJI-INV-OVERDUE-001',
            'requested_total' => 150000,
        ],
    ]);
    $approval->timestamps = false;
    $approval->created_at = now()->subDay()->subHours(2);
    $approval->save();

    config()->set('app.env', 'testing');
    putenv('TELEGRAM_BOT_TOKEN=test-token');
    putenv('TELEGRAM_CHAT_ID=123456');
    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]], 200),
    ]);

    Artisan::call('approval:send-sla-anomaly-alert');
    $output = Artisan::output();
    expect($output)->toContain('Alert anomali SLA');
});
