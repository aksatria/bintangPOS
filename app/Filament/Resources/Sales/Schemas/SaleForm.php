<?php

namespace App\Filament\Resources\Sales\Schemas;

use App\Enums\SaleStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class SaleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    TextInput::make('invoice_number')->disabled(),
                    TextInput::make('user.name')->label('Kasir')->disabled(),
                    TextInput::make('subtotal')->numeric()->disabled(),
                    TextInput::make('discount_amount')->numeric()->disabled(),
                    TextInput::make('tax_amount')->numeric()->disabled(),
                    TextInput::make('total_amount')->numeric()->disabled(),
                    TextInput::make('paid_amount')->numeric()->disabled(),
                    TextInput::make('change_amount')->numeric()->disabled(),
                    Select::make('status')->options(SaleStatus::options())->required(),
                    Textarea::make('note')->rows(2),
                ]),
            ]);
    }
}
