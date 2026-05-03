<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CashierAuditLog;
use App\Support\ActiveBranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ActiveBranchController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user || ! $user->hasAnyRole(['owner', 'admin'])) {
            abort(403);
        }

        $validated = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
        ]);

        $branchId = (int) $validated['branch_id'];
        $beforeBranchId = (int) (ActiveBranchContext::resolveBranchId($user) ?? 0);
        $branchActive = Branch::query()->whereKey($branchId)->where('is_active', true)->exists();
        if (! $branchActive) {
            return back()->with('error', 'Cabang tidak aktif.');
        }

        ActiveBranchContext::setBranchId($user, $branchId);

        if ($beforeBranchId !== $branchId) {
            CashierAuditLog::query()->create([
                'user_id' => $user->id,
                'branch_id' => $branchId > 0 ? $branchId : null,
                'action' => 'active_branch_switched',
                'context' => [
                    'from_branch_id' => $beforeBranchId > 0 ? $beforeBranchId : null,
                    'to_branch_id' => $branchId,
                ],
                'ip_address' => (string) $request->ip(),
                'user_agent' => (string) ($request->userAgent() ?? ''),
            ]);
        }

        return back()->with('status', 'Cabang aktif berhasil diubah.');
    }
}
