<?php

namespace App\Console\Commands;

use App\Models\StockTransfer;
use App\Support\TelegramNotifier;
use Illuminate\Console\Command;

class SendStockTransferAgingAlertCommand extends Command
{
    protected $signature = 'stock-transfer:send-aging-alert';

    protected $description = 'Kirim alert Telegram untuk transfer requested/approved yang overdue >24 jam';

    public function handle(): int
    {
        $overdue = StockTransfer::query()
            ->whereIn('status', [StockTransfer::STATUS_REQUESTED, StockTransfer::STATUS_APPROVED])
            ->where('created_at', '<=', now()->subHours(24))
            ->with(['sourceBranch:id,name', 'destinationBranch:id,name'])
            ->orderBy('created_at')
            ->limit(20)
            ->get();

        if ($overdue->isEmpty()) {
            $this->info('Tidak ada transfer overdue.');
            return self::SUCCESS;
        }

        $lines = ["Alert Mutasi Overdue (>24 jam)", "Total: {$overdue->count()}"];
        foreach ($overdue as $t) {
            $hours = (int) floor($t->created_at->diffInHours(now()));
            $lines[] = "- {$t->code} | {$t->status} | {$t->sourceBranch?->name} -> {$t->destinationBranch?->name} | {$hours} jam";
        }

        if (TelegramNotifier::enabled()) {
            TelegramNotifier::send(implode("\n", $lines), 'stock_transfer_aging_alert', TelegramNotifier::defaultChatId());
            $this->info('Alert Telegram terkirim.');
        } else {
            $this->warn('Telegram disabled, alert tidak dikirim.');
        }

        return self::SUCCESS;
    }
}

