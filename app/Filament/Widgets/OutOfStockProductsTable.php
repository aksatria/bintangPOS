<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class OutOfStockProductsTable extends TableWidget
{
    protected static ?int $sort = 8;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Notifikasi Stok Habis';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Product::query()
                ->active()
                ->where('stock', '<=', 0)
                ->orderBy('name'))
            ->columns([
                TextColumn::make('name')->label('Produk')->searchable(),
                TextColumn::make('sku'),
                TextColumn::make('stock')->label('Stok')->badge()->color('danger'),
                TextColumn::make('low_stock_threshold')->label('Batas Minimum'),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
