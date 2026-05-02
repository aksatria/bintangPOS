<?php

namespace App\Filament\Widgets;

use App\Enums\SaleStatus;
use App\Models\Sale;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class SalesLast7DaysChart extends ChartWidget
{
    protected static ?int $sort = 5;
    protected ?string $pollingInterval = '60s';

    protected int | string | array $columnSpan = 'full';

    protected ?string $heading = 'Trend Omzet 7 Hari Terakhir';

    protected function getData(): array
    {
        $start = Carbon::today()->subDays(6);

        $rows = Sale::query()
            ->selectRaw('DATE(sold_at) as date, SUM(total_amount) as omzet')
            ->where('status', SaleStatus::Paid->value)
            ->whereBetween('sold_at', [$start->copy()->startOfDay(), Carbon::today()->endOfDay()])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $labels = [];
        $data = [];

        for ($i = 0; $i < 7; $i++) {
            $date = $start->copy()->addDays($i);
            $labels[] = $date->format('d M');
            $data[] = (float) ($rows[$date->toDateString()]->omzet ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Omzet',
                    'data' => $data,
                    'borderColor' => '#0284c7',
                    'backgroundColor' => 'rgba(2, 132, 199, 0.15)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
