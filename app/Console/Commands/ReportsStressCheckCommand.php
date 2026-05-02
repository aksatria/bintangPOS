<?php

namespace App\Console\Commands;

use App\Models\Sale;
use Illuminate\Console\Command;

class ReportsStressCheckCommand extends Command
{
    protected $signature = 'reports:stress-check {--days=30 : Panjang rentang hari} {--loops=5 : Jumlah iterasi uji}';

    protected $description = 'Uji beban ringan query laporan untuk mengecek latency dasar';

    public function handle(): int
    {
        $days = max(1, min(366, (int) $this->option('days')));
        $loops = max(1, min(30, (int) $this->option('loops')));

        $start = now()->subDays($days - 1)->startOfDay();
        $end = now()->endOfDay();
        $durations = [];

        for ($i = 1; $i <= $loops; $i++) {
            $t0 = hrtime(true);

            $base = Sale::query()->whereBetween('sold_at', [$start, $end]);
            (clone $base)->count();
            (clone $base)->sum('total_amount');
            (clone $base)->where('status', 'paid')->sum('total_amount');
            (clone $base)->where('payment_method', 'qris')->count();
            (clone $base)->latest('sold_at')->limit(50)->get(['id', 'invoice_number', 'sold_at', 'total_amount', 'status']);

            $ms = (hrtime(true) - $t0) / 1_000_000;
            $durations[] = $ms;
            $this->line("Loop {$i}: ".number_format($ms, 2).' ms');
        }

        sort($durations);
        $avg = array_sum($durations) / count($durations);
        $p95Index = (int) ceil(count($durations) * 0.95) - 1;
        $p95 = $durations[max(0, min($p95Index, count($durations) - 1))];

        $this->newLine();
        $this->info('Ringkasan Stress Check');
        $this->line('Range hari : '.$days);
        $this->line('Loops      : '.$loops);
        $this->line('AVG        : '.number_format($avg, 2).' ms');
        $this->line('P95        : '.number_format($p95, 2).' ms');

        return self::SUCCESS;
    }
}
