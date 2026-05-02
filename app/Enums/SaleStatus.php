<?php

namespace App\Enums;

enum SaleStatus: string
{
    case Paid = 'paid';
    case Pending = 'pending';
    case Cancelled = 'cancelled';

    public static function options(): array
    {
        return [
            self::Paid->value => 'Paid',
            self::Pending->value => 'Pending',
            self::Cancelled->value => 'Cancelled',
        ];
    }
}
