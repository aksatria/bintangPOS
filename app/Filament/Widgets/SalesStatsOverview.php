<?php

namespace App\Filament\Widgets;

use App\Enums\SaleStatus;
use App\Models\CashierAuditLog;
use App\Models\Expense;
use App\Models\Sale;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class SalesStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;
    protected ?string $pollingInterval = '30s';

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $today = Carbon::today();

        $todaySales = Sale::query()
            ->whereDate('sold_at', $today)
            ->where('status', SaleStatus::Paid->value);

        $omzetToday = (float) $todaySales->sum('total_amount');
        $transactionsToday = (int) $todaySales->count();
        $todayExpense = (float) Expense::query()->whereDate('date', $today)->sum('amount');
        $pendingCount = (int) Sale::query()->whereDate('sold_at', $today)->where('status', SaleStatus::Pending->value)->count();
        $checkoutFailLastHour = (int) CashierAuditLog::query()
            ->where('action', 'checkout_failed_client')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        return [
            Stat::make('Omzet Paid Hari Ini', 'Rp '.number_format($omzetToday, 0, ',', '.'))
                ->description('Transaksi paid tanggal '.now()->format('d/m/Y'))
                ->color('success'),
            Stat::make('Transaksi Paid Hari Ini', number_format($transactionsToday))
                ->description('Pending saat ini: '.number_format($pendingCount))
                ->color($pendingCount > 0 ? 'warning' : 'success'),
            Stat::make('Pengeluaran Hari Ini', 'Rp '.number_format($todayExpense, 0, ',', '.'))
                ->description('Checkout gagal 1 jam: '.number_format($checkoutFailLastHour))
                ->color($checkoutFailLastHour > 0 ? 'danger' : 'gray'),
        ];
    }
}
