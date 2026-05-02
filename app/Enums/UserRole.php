<?php

namespace App\Enums;

enum UserRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Cashier = 'kasir';

    public static function options(): array
    {
        return [
            self::Owner->value => 'Owner',
            self::Admin->value => 'Admin',
            self::Cashier->value => 'Kasir',
        ];
    }
}
