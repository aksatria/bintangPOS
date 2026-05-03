<?php

namespace App\Services;

use App\Models\CustomerDebt;

class CustomerDebtNumberService
{
    public function nextNumber(): string
    {
        $max = 0;
        $numbers = CustomerDebt::query()
            ->where('number', 'like', 'AR-%')
            ->latest('id')
            ->limit(500)
            ->pluck('number');

        foreach ($numbers as $number) {
            if (preg_match('/^AR-(\d+)$/', (string) $number, $matches) === 1) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return 'AR-' . str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }
}
