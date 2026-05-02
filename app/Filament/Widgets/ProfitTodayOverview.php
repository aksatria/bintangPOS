<?php

namespace App\Filament\Widgets;

use App\Enums\SaleStatus;
use App\Models\Expense;
use App\Models\SaleItem;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class ProfitTodayOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $today = Carbon::today();

        $grossSales = (float) SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereDate('sales.sold_at', $today)
            ->where('sales.status', SaleStatus::Paid->value)
            ->sum('sale_items.subtotal');

        $costOfGoods = (float) SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereDate('sales.sold_at', $today)
            ->where('sales.status', SaleStatus::Paid->value)
            ->selectRaw('COALESCE(SUM(sale_items.purchase_price * sale_items.quantity), 0) as total')
            ->value('total');

        $todayExpense = (float) Expense::query()
            ->whereDate('date', $today)
            ->sum('amount');

        $grossProfit = $grossSales - $costOfGoods;
        $netProfit = $grossProfit - $todayExpense;

        return [
            Stat::make('Laba Kotor Hari Ini', 'Rp '.number_format($grossProfit, 0, ',', '.')),
            Stat::make('HPP Hari Ini', 'Rp '.number_format($costOfGoods, 0, ',', '.')),
            Stat::make('Laba Bersih Hari Ini', 'Rp '.number_format($netProfit, 0, ',', '.')),
        ];
    }
}
