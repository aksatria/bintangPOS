<?php

use App\Models\Permission;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

function seedRbacBaseline($testCase): void
{
    $testCase->artisan('db:seed --class=PermissionSeeder')->assertExitCode(0);
}

test('rbac health check fails when default permissions and mappings are missing', function () {
    seedRbacBaseline($this);

    Permission::query()->where('code', 'reports.view')->delete();
    DB::table('role_permissions')->where('role', 'admin')->delete();

    $this->artisan('rbac:health-check')
        ->assertExitCode(1);
});

test('rbac health check repair restores missing permission and default mappings', function () {
    seedRbacBaseline($this);

    Permission::query()->where('code', 'reports.view')->delete();
    DB::table('role_permissions')->where('role', 'admin')->delete();

    $this->artisan('rbac:health-check --repair')
        ->assertExitCode(0);

    $reportPermissionId = (int) Permission::query()->where('code', 'reports.view')->value('id');

    expect($reportPermissionId)->toBeGreaterThan(0);

    $adminHasReportView = DB::table('role_permissions')
        ->where('role', 'admin')
        ->where('permission_id', $reportPermissionId)
        ->exists();

    expect($adminHasReportView)->toBeTrue();
});

test('rbac health check json output contains machine readable keys', function () {
    seedRbacBaseline($this);

    $this->artisan('rbac:health-check --json')
        ->expectsOutputToContain('"ok"')
        ->assertExitCode(0);
});

test('rbac notify unhealthy does not send telegram when health is ok', function () {
    seedRbacBaseline($this);

    Http::fake();

    $this->artisan('rbac:notify-unhealthy')
        ->assertExitCode(0);

    Http::assertNothingSent();
});

test('rbac notify unhealthy sends telegram when health check fails', function () {
    seedRbacBaseline($this);

    putenv('TELEGRAM_BOT_TOKEN=test-token');
    putenv('TELEGRAM_CHAT_ID=123456');
    $_ENV['TELEGRAM_BOT_TOKEN'] = 'test-token';
    $_ENV['TELEGRAM_CHAT_ID'] = '123456';

    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    Permission::query()->where('code', 'reports.view')->delete();

    $this->artisan('rbac:notify-unhealthy')
        ->assertExitCode(1);

    Http::assertSentCount(1);
});
