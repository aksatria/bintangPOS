<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StockInventoryOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 4;
    protected ?string $pollingInterval = '45s';

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $inventoryAssetValue = (float) Product::query()
            ->active()
            ->selectRaw('COALESCE(SUM(stock * purchase_price), 0) as total')
            ->value('total');

        $outOfStockCount = (int) Product::query()
            ->active()
            ->where('stock', '<=', 0)
            ->count();
        $lowStockCount = (int) Product::query()
            ->active()
            ->where('stock', '>', 0)
            ->whereColumn('stock', '<=', 'low_stock_threshold')
            ->count();

        $activeProductsCount = (int) Product::query()
            ->active()
            ->count();

        return [
            Stat::make('Nilai Stok Saat Ini', 'Rp '.number_format($inventoryAssetValue, 0, ',', '.'))
                ->description('Aset persediaan produk aktif')
                ->color('primary'),
            Stat::make('Produk Stok Habis', number_format($outOfStockCount))
                ->description('Stok <= 0')
                ->color($outOfStockCount > 0 ? 'danger' : 'success'),
            Stat::make('Produk Menipis / Aktif', number_format($lowStockCount).' / '.number_format($activeProductsCount))
                ->description('Low stock terhadap total produk aktif')
                ->color($lowStockCount > 0 ? 'warning' : 'success'),
        ];
    }
}
