<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait AppliesBranchScope
{
    protected function applyBranchScope(Builder $query, ?User $user, ?string $table = null): Builder
    {
        if (! $user) {
            return $query;
        }

        $branchId = ActiveBranchContext::resolveBranchId($user);
        if (! $branchId) {
            return $query;
        }

        $column = ($table ? $table.'.' : '').'branch_id';

        return $query->where(function ($q) use ($column, $branchId) {
            $q->where($column, $branchId)
                ->orWhereNull($column);
        });
    }
}
