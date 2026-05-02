<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required()->maxLength(150),
                TextInput::make('email')->label('Email')->email()->required()->unique(ignoreRecord: true),
                Select::make('role')
                    ->options(UserRole::options())
                    ->required()
                    ->default(UserRole::Cashier->value),
                TextInput::make('password')
                    ->password()
                    ->maxLength(255)
                    ->dehydrateStateUsing(fn (?string $state) => filled($state) ? bcrypt($state) : null)
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create'),
            ]);
    }
}
