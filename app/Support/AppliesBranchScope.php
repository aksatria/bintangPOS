<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait AppliesBranchScope
{
    protected function applyBranchScope(Builder $query, ?User $user, ?string $table = null): Builder
    {
        if (! $user || $user->hasAnyRole(['owner'])) {
            return $query;
        }

        if (! $user->branch_id) {
            return $query;
        }

        $column = ($table ? $table.'.' : '').'branch_id';

        return $query->where($column, $user->branch_id);
    }
}

