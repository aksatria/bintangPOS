<?php

namespace App\Console\Commands;

use App\Models\CashierAuditLog;
use App\Models\RolePermissionGrant;
use Illuminate\Console\Command;

class ExpireTemporaryRolePermissionsCommand extends Command
{
    protected $signature = 'rbac:expire-temp-grants';

    protected $description = 'Nonaktifkan grant permission sementara yang sudah lewat expiry.';

    public function handle(): int
    {
        $expired = RolePermissionGrant::query()
            ->where('is_active', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['is_active' => false]);

        CashierAuditLog::query()->create([
            'user_id' => null,
            'action' => 'rbac_temp_grants_expired',
            'context' => ['expired_count' => (int) $expired],
            'ip_address' => 'cli',
            'user_agent' => 'artisan',
        ]);

        $this->info("Temporary grants expired: {$expired}");
        return self::SUCCESS;
    }
}

