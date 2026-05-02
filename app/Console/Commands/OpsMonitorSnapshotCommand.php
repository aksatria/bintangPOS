<?php

namespace App\Console\Commands;

use App\Enums\SaleStatus;
use App\Models\CashierAuditLog;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Console\Command;

class OpsMonitorSnapshotCommand extends Command
{
    protected $signature = 'ops:monitor-snapshot {--days=7 : Rentang hari untuk metrik penjualan}';

    protected $description = 'Ambil snapshot metrik operasional dan waktu query utama pasca deploy';

    public function handle(): int
    {
        $days = max(1, min(60, (int) $this->option('days')));
        $start = now()->subDays($days - 1)->startOfDay();
        $end = now()->endOfDay();

        $this->line('=== OPS MONITOR SNAPSHOT ===');
        $this->line('Range: '.$start->toDateString().' s/d '.$end->toDateString());

        $m1 = $this->timeQuery(fn () => Sale::query()->whereBetween('sold_at', [$start, $end])->where('status', SaleStatus::Paid->value)->sum('total_amount'));
        $m2 = $this->timeQuery(fn () => Sale::query()->whereBetween('sold_at', [$start, $end])->where('status', SaleStatus::Paid->value)->count());
        $m3 = $this->timeQuery(fn () => Expense::query()->whereBetween('date', [$start->toDateString(), $end->toDateString()])->sum('amount'));
        $m4 = $this->timeQuery(fn () => CashierAuditLog::query()->whereBetween('created_at', [$start, $end])->count());
        $m5 = $this->timeQuery(fn () => SaleItem::query()->join('sales', 'sales.id', '=', 'sale_items.sale_id')->whereBetween('sales.sold_at', [$start, $end])->where('sales.status', SaleStatus::Paid->value)->sum('sale_items.quantity'));

        $this->table(['Metric', 'Value', 'Query ms'], [
            ['Omzet paid', number_format((float) $m1['value'], 2, '.', ''), $m1['ms']],
            ['Transaksi paid', (int) $m2['value'], $m2['ms']],
            ['Total expense', number_format((float) $m3['value'], 2, '.', ''), $m3['ms']],
            ['Jumlah audit log', (int) $m4['value'], $m4['ms']],
            ['Qty terjual', (int) $m5['value'], $m5['ms']],
        ]);

        $this->line('Tip: simpan output ini harian selama 2-3 hari untuk bandingkan tren pasca perubahan index/validasi.');

        return self::SUCCESS;
    }

    /**
     * @return array{value:mixed,ms:string}
     */
    private function timeQuery(callable $query): array
    {
        $start = hrtime(true);
        $value = $query();
        $elapsedMs = (hrtime(true) - $start) / 1_000_000;

        return [
            'value' => $value,
            'ms' => number_format($elapsedMs, 2),
        ];
    }
}
