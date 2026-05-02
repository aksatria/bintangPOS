<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    Select::make('category_id')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    TextInput::make('name')
                        ->required()
                        ->maxLength(150),
                    TextInput::make('sku')
                        ->required()
                        ->maxLength(100)
                        ->unique(ignoreRecord: true),
                    TextInput::make('barcode')
                        ->maxLength(100)
                        ->unique(ignoreRecord: true),
                    TextInput::make('purchase_price')
                        ->numeric()
                        ->required()
                        ->minValue(0),
                    TextInput::make('selling_price')
                        ->numeric()
                        ->required()
                        ->rule('gte:purchase_price')
                        ->helperText('Harga jual tidak boleh lebih kecil dari harga beli.'),
                    TextInput::make('stock')
                        ->numeric()
                        ->required()
                        ->minValue(0),
                    TextInput::make('low_stock_threshold')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->default(5),
                    TextInput::make('unit')
                        ->required()
                        ->maxLength(50)
                        ->default('pcs'),
                    FileUpload::make('image')
                        ->image()
                        ->disk('public')
                        ->directory('products'),
                    Toggle::make('is_active')
                        ->required()
                        ->default(true),
                ]),
            ]);
    }
}
