<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')->circular(),
                TextColumn::make('category.name')->label('Kategori')->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('sku')->searchable(),
                TextColumn::make('barcode')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('purchase_price')->money('IDR')->label('Harga Beli'),
                TextColumn::make('selling_price')->money('IDR')->label('Harga Jual'),
                TextColumn::make('stock')
                    ->badge()
                    ->color(fn ($record) => $record->isLowStock() ? 'warning' : 'success')
                    ->sortable(),
                IconColumn::make('is_active')->boolean()->label('Aktif'),
            ])
            ->filters([
                SelectFilter::make('category')->relationship('category', 'name'),
                TernaryFilter::make('is_active')->label('Aktif'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
