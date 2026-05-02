<?php

namespace App\Filament\Resources\Expenses\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')->required()->maxLength(150),
                TextInput::make('amount')->numeric()->required()->minValue(0),
                DatePicker::make('date')->required(),
                Textarea::make('note')->rows(3),
            ]);
    }
}
