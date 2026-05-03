<?php

use App\Models\CashierAuditLog;
use App\Models\ApprovalRequest;
use App\Models\Permission;
use App\Models\RolePermissionGrant;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('blocks destructive command without two-step confirmation', function () {
    putenv('ALLOW_DESTRUCTIVE_COMMANDS=false');
    putenv('ENABLE_DESTRUCTIVE_GUARD_IN_TESTS=true');

    expect(fn () => Artisan::call('migrate:fresh'))
        ->toThrow(\RuntimeException::class);
});

it('creates ops health snapshot audit log', function () {
    Artisan::call('ops:health-check');

    expect(CashierAuditLog::query()->where('action', 'ops_health_snapshot')->exists())->toBeTrue();
});

it('system health page follows role permission policy', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $cashier = User::factory()->create(['role' => 'kasir']);

    actingAs($owner);
    get(route('admin.system-health.index'))->assertOk();

    actingAs($cashier);
    get(route('admin.system-health.index'))->assertRedirect(route('dashboard'));
});

it('expires temporary role grants by schedule command', function () {
    $grant = RolePermissionGrant::query()->create([
        'user_id' => null,
        'role' => 'admin',
        'permission_code' => 'reports.export',
        'starts_at' => now()->subDay(),
        'expires_at' => now()->subMinute(),
        'is_active' => true,
    ]);

    Artisan::call('rbac:expire-temp-grants');
    $grant->refresh();

    expect($grant->is_active)->toBeFalse();
});

it('owner can create and revoke temporary grant from rbac page', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    Permission::query()->firstOrCreate(
        ['code' => 'reports.export'],
        ['name' => 'Reports Export', 'group' => 'reports']
    );

    actingAs($owner);
    $res = \Pest\Laravel\post(route('admin.rbac.temp-grants.store'), [
        'permission_code' => 'reports.export',
        'scope_type' => 'role',
        'role' => 'admin',
        'duration_hours' => 24,
        'reason' => 'Emergency export support',
    ]);
    $res->assertRedirect();

    $grant = RolePermissionGrant::query()->latest('id')->first();
    expect($grant)->not->toBeNull();
    expect($grant->is_active)->toBeTrue();

    \Pest\Laravel\post(route('admin.rbac.temp-grants.revoke', $grant))->assertRedirect();
    $grant->refresh();
    expect($grant->is_active)->toBeFalse();
});

it('production sanity check returns success when core signals are healthy', function () {
    Cache::put('ops.scheduler.heartbeat_at', now()->toIso8601String(), now()->addHour());

    CashierAuditLog::query()->create([
        'user_id' => null,
        'action' => 'database_backup_created',
        'context' => ['path' => 'storage/app/backups/test.sql'],
        'ip_address' => 'cli',
        'user_agent' => 'artisan',
        'created_at' => now()->subHours(2),
        'updated_at' => now()->subHours(2),
    ]);
    CashierAuditLog::query()->create([
        'user_id' => null,
        'action' => 'disaster_recovery_drill_completed',
        'context' => ['ok' => true],
        'ip_address' => 'cli',
        'user_agent' => 'artisan',
        'created_at' => now()->subDays(3),
        'updated_at' => now()->subDays(3),
    ]);

    $exit = Artisan::call('ops:production-sanity-check');

    expect($exit)->toBe(0);
    expect(Artisan::output())->toContain('Sanity check produksi aman.');
});

it('production sanity check strict mode fails on stale scheduler heartbeat and overdue approvals', function () {
    Cache::put('ops.scheduler.heartbeat_at', now()->subMinutes(20)->toIso8601String(), now()->addHour());

    CashierAuditLog::query()->create([
        'user_id' => null,
        'action' => 'database_backup_created',
        'context' => ['path' => 'storage/app/backups/test.sql'],
        'ip_address' => 'cli',
        'user_agent' => 'artisan',
        'created_at' => now()->subDays(3),
        'updated_at' => now()->subDays(3),
    ]);

    ApprovalRequest::query()->create([
        'type' => 'report.export.pdf',
        'status' => 'pending',
        'requested_by' => User::factory()->create(['role' => 'admin'])->id,
        'title' => 'Pending Lama',
        'reason' => 'Uji overdue',
        'payload' => ['x' => 1],
        'created_at' => now()->subHours(3),
        'updated_at' => now()->subHours(3),
    ]);

    $exit = Artisan::call('ops:production-sanity-check --strict');

    expect($exit)->toBe(1);
    expect(Artisan::output())->toContain('warning');
});
