<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LowStockProductsTable extends TableWidget
{
    protected static ?int $sort = 7;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Produk Stok Menipis';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Product::query()->active()->lowStock()->orderBy('stock'))
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('sku'),
                TextColumn::make('stock')->badge()->color('warning'),
                TextColumn::make('low_stock_threshold')->label('Batas'),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
