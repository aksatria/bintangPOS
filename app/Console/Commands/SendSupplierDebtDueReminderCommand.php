<?php

namespace App\Console\Commands;

use App\Models\CashierAuditLog;
use App\Models\SupplierPurchase;
use App\Support\TelegramNotifier;
use Illuminate\Console\Command;

class SendSupplierDebtDueReminderCommand extends Command
{
    protected $signature = 'suppliers:debt-due-reminder {--days=7 : Rentang hari jatuh tempo} {--telegram : Kirim ke Telegram jika aktif}';

    protected $description = 'Tampilkan/kirim reminder hutang supplier jatuh tempo dan overdue.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $rows = SupplierPurchase::query()
            ->with('supplier:id,name')
            ->where('remaining_amount', '>', 0)
            ->whereIn('payment_status', ['unpaid', 'partial', 'overdue'])
            ->where(function ($query) use ($days) {
                $query->whereDate('due_date', '<', now()->toDateString())
                    ->orWhereBetween('due_date', [now()->toDateString(), now()->addDays($days)->toDateString()]);
            })
            ->orderBy('due_date')
            ->limit(20)
            ->get();

        if ($rows->isEmpty()) {
            $this->info('Tidak ada hutang supplier jatuh tempo/overdue.');
            return self::SUCCESS;
        }

        $lines = [
            'Reminder Hutang Supplier',
            'Tanggal: '.now()->format('d/m/Y H:i'),
            'Total PO: '.$rows->count(),
            '',
        ];

        foreach ($rows as $idx => $purchase) {
            $due = $purchase->due_date?->format('d/m/Y') ?? '-';
            $isOverdue = $purchase->due_date && $purchase->due_date->lt(now()->startOfDay());
            $lines[] = ($idx + 1).'. '.$purchase->number.' - '.($purchase->supplier?->name ?? '-')
                .' - Due '.$due
                .' - Sisa Rp '.number_format((float) $purchase->remaining_amount, 0, ',', '.')
                .($isOverdue ? ' - OVERDUE' : '');
        }

        $message = implode("\n", $lines);
        $this->line($message);

        if ($this->option('telegram') && TelegramNotifier::enabled()) {
            TelegramNotifier::send($message, 'supplier_debt_due_reminder', TelegramNotifier::defaultChatId());
        }

        CashierAuditLog::query()->create([
            'action' => 'supplier_debt_due_reminder_generated',
            'context' => [
                'rows' => $rows->count(),
                'days' => $days,
                'telegram' => (bool) $this->option('telegram'),
            ],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'console',
        ]);

        return self::SUCCESS;
    }
}
