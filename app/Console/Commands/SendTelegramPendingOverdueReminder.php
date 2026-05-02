<?php

namespace App\Console\Commands;

use App\Enums\SaleStatus;
use App\Models\Branch;
use App\Models\Sale;
use App\Models\StoreSetting;
use App\Support\TelegramNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendTelegramPendingOverdueReminder extends Command
{
    protected $signature = 'telegram:send-pending-overdue-reminder';

    protected $description = 'Kirim pengingat Telegram untuk transaksi pending yang sudah lewat 3 hari.';

    public function handle(): int
    {
        $setting = StoreSetting::query()->first();
        if (! $setting || ! $setting->telegram_enabled) {
            return self::SUCCESS;
        }

        $branches = Branch::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $branches = $branches->concat([(object) ['id' => null, 'name' => 'Global']]);
        $sent = 0;

        foreach ($branches as $branch) {
            $cacheKey = 'telegram_pending_overdue_reminder_'.now()->format('Ymd').'_b'.($branch->id ?? 'global');
            if (Cache::has($cacheKey)) {
                continue;
            }

            $rows = Sale::query()
                ->with('customer:id,name')
                ->when($branch->id, fn ($q) => $q->where('branch_id', $branch->id), fn ($q) => $q->whereNull('branch_id'))
                ->where('status', SaleStatus::Pending->value)
                ->whereDate('sold_at', '<=', now()->subDays(3)->toDateString())
                ->orderBy('sold_at')
                ->limit(10)
                ->get(['id', 'customer_id', 'invoice_number', 'sold_at', 'total_amount']);

            if ($rows->isEmpty()) {
                Cache::put($cacheKey, true, now()->endOfDay());
                continue;
            }

            $lines = [
                'Pengingat Pending Overdue',
                'Cabang: '.$branch->name,
                'Tanggal: '.now()->format('d/m/Y H:i'),
                'Total overdue: '.$rows->count().' transaksi',
                '',
            ];

            foreach ($rows as $idx => $sale) {
                $days = $sale->sold_at ? $sale->sold_at->diffInDays(now()) : 0;
                $lines[] = ($idx + 1).'. '.$sale->invoice_number
                    .' | '.($sale->customer?->name ?: 'Tanpa Nama')
                    .' | Rp '.number_format((float) $sale->total_amount, 0, ',', '.')
                    .' | '.$days.' hari';
            }

            $ok = TelegramNotifier::send(implode("\n", $lines), 'pending_overdue_reminder');
            if ($ok) {
                Cache::put($cacheKey, true, now()->endOfDay());
                $sent++;
            }
        }

        $this->info("Pengingat pending overdue terkirim untuk {$sent} cabang.");
        return self::SUCCESS;
    }
}
