<?php

namespace App\Filament\Widgets;

use App\Enums\SaleStatus;
use App\Models\Sale;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class TransactionStatusTodayOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 3;
    protected ?string $pollingInterval = '30s';

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $today = Carbon::today();

        $baseQuery = Sale::query()->whereDate('sold_at', $today);

        $paid = (int) (clone $baseQuery)->where('status', SaleStatus::Paid->value)->count();
        $pending = (int) (clone $baseQuery)->where('status', SaleStatus::Pending->value)->count();
        $cancelled = (int) (clone $baseQuery)->where('status', SaleStatus::Cancelled->value)->count();
        $pendingValue = (float) (clone $baseQuery)->where('status', SaleStatus::Pending->value)->sum('total_amount');
        $overduePending = (int) (clone $baseQuery)
            ->where('status', SaleStatus::Pending->value)
            ->whereNotNull('payment_due_at')
            ->where('payment_due_at', '<', now())
            ->count();

        return [
            Stat::make('Paid Hari Ini', number_format($paid))
                ->description('Transaksi lunas')
                ->color('success'),
            Stat::make('Pending Hari Ini', number_format($pending))
                ->description('Nominal pending: Rp '.number_format($pendingValue, 0, ',', '.'))
                ->color($pending > 0 ? 'warning' : 'gray'),
            Stat::make('Cancelled Hari Ini', number_format($cancelled))
                ->description('Pending overdue: '.number_format($overduePending))
                ->color(($cancelled > 0 || $overduePending > 0) ? 'danger' : 'gray'),
        ];
    }
}
