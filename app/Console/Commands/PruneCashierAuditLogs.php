<?php

namespace App\Console\Commands;

use App\Models\CashierAuditLog;
use Illuminate\Console\Command;

class PruneCashierAuditLogs extends Command
{
    protected $signature = 'logs:prune-cashier-audit {--days=90 : Hapus log yang lebih lama dari jumlah hari ini}';

    protected $description = 'Hapus audit log kasir lama agar database tetap ringan';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        $deleted = CashierAuditLog::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Audit log terhapus: {$deleted} (lebih lama dari {$days} hari).");

        return self::SUCCESS;
    }
}

