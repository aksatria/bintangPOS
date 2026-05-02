<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Owner->value, UserRole::Admin->value, UserRole::Cashier->value]);
    }

    public function view(User $user, Sale $sale): bool
    {
        if ($user->hasAnyRole([UserRole::Owner->value, UserRole::Admin->value])) {
            return true;
        }

        return $sale->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Owner->value, UserRole::Admin->value, UserRole::Cashier->value]);
    }

    public function update(User $user, Sale $sale): bool
    {
        return $user->hasAnyRole([UserRole::Owner->value, UserRole::Admin->value]);
    }

    public function delete(User $user, Sale $sale): bool
    {
        return $user->hasAnyRole([UserRole::Owner->value]);
    }
}
