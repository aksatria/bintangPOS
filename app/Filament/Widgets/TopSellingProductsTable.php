<?php

namespace App\Filament\Widgets;

use App\Enums\SaleStatus;
use App\Models\SaleItem;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class TopSellingProductsTable extends TableWidget
{
    protected static ?int $sort = 6;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Top 5 Produk Terlaris (7 Hari Terakhir)';

    public function table(Table $table): Table
    {
        $start = Carbon::today()->subDays(6)->startOfDay();
        $end = Carbon::today()->endOfDay();

        return $table
            ->query(fn (): Builder => SaleItem::query()
                ->selectRaw('product_name, sku, SUM(quantity) as sold_qty, SUM(subtotal) as omzet')
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->where('sales.status', SaleStatus::Paid->value)
                ->whereBetween('sales.sold_at', [$start, $end])
                ->groupBy('product_name', 'sku')
                ->orderByDesc('sold_qty')
                ->limit(5))
            ->columns([
                TextColumn::make('product_name')->label('Produk'),
                TextColumn::make('sku'),
                TextColumn::make('sold_qty')->label('Terjual')->badge(),
                TextColumn::make('omzet')
                    ->money('IDR')
                    ->label('Omzet'),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
