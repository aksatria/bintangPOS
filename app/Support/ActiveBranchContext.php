<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Collection;

class ActiveBranchContext
{
    public const SESSION_KEY = 'active_branch_id';

    public static function resolveBranchId(?User $user): ?int
    {
        if (! $user) {
            return null;
        }

        if (! $user->hasAnyRole(['owner', 'admin'])) {
            return $user->branch_id ? (int) $user->branch_id : null;
        }

        $sessionBranchId = (int) (session(self::SESSION_KEY) ?? 0);
        if ($sessionBranchId > 0 && self::isActiveBranchId($sessionBranchId)) {
            return $sessionBranchId;
        }

        if ($user->branch_id && self::isActiveBranchId((int) $user->branch_id)) {
            return (int) $user->branch_id;
        }

        $firstActiveId = Branch::query()->where('is_active', true)->orderBy('name')->value('id');
        if ($firstActiveId) {
            return (int) $firstActiveId;
        }

        return null;
    }

    public static function setBranchId(?User $user, ?int $branchId): void
    {
        if (! $user || ! $user->hasAnyRole(['owner', 'admin'])) {
            return;
        }

        if (! $branchId || ! self::isActiveBranchId($branchId)) {
            session()->forget(self::SESSION_KEY);
            return;
        }

        session()->put(self::SESSION_KEY, $branchId);
    }

    public static function availableBranchesFor(?User $user): Collection
    {
        if (! $user || ! $user->hasAnyRole(['owner', 'admin'])) {
            return collect();
        }

        return Branch::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    private static function isActiveBranchId(int $branchId): bool
    {
        return Branch::query()
            ->whereKey($branchId)
            ->where('is_active', true)
            ->exists();
    }
}

