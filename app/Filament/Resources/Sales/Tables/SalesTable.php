<?php

namespace App\Filament\Resources\Sales\Tables;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sold_at')->label('Tanggal')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('invoice_number')->searchable(),
                TextColumn::make('user.name')->label('Kasir')->searchable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('total_amount')->money('IDR')->label('Total')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'paid' => 'Paid',
                        'pending' => 'Pending',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->recordActions([
                Action::make('receipt')
                    ->label('Struk')
                    ->icon('heroicon-o-printer')
                    ->url(fn ($record) => route('sales.receipt', $record), shouldOpenInNewTab: true),
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
