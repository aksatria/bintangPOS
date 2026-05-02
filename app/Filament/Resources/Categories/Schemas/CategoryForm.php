<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(1)->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(150)
                        ->unique(ignoreRecord: true),
                    Textarea::make('description')
                        ->rows(3),
                    Toggle::make('is_active')
                        ->default(true)
                        ->required(),
                ]),
            ]);
    }
}
