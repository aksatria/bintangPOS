<?php

namespace App\Http\Controllers;

use App\Models\CashierAuditLog;
use App\Models\Permission;
use App\Models\RolePermissionGrant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RbacController extends Controller
{
    private const ROLES = ['owner', 'admin', 'kasir'];

    public function index()
    {
        $q = trim((string) request()->query('q', ''));
        $active = (string) request()->query('active', 'all');
        $scope = (string) request()->query('scope', 'all');
        $sort = (string) request()->query('sort', 'expires_asc');

        $permissions = Permission::query()
            ->orderBy('group')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'group']);

        $assigned = DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->select('role_permissions.role', 'permissions.code')
            ->get()
            ->groupBy('role')
            ->map(fn ($rows) => $rows->pluck('code')->values()->all())
            ->all();

        $grantsQuery = RolePermissionGrant::query()->orderByDesc('id');
        if ($q !== '') {
            $grantsQuery->where(function ($qr) use ($q) {
                $qr->where('permission_code', 'like', '%'.$q.'%')
                    ->orWhere('reason', 'like', '%'.$q.'%');
            });
        }
        if ($active === 'active') {
            $grantsQuery->where('is_active', true);
        } elseif ($active === 'inactive') {
            $grantsQuery->where('is_active', false);
        }
        if ($scope === 'role') {
            $grantsQuery->whereNull('user_id')->whereNotNull('role');
        } elseif ($scope === 'user') {
            $grantsQuery->whereNotNull('user_id');
        }
        if ($sort === 'expires_desc') {
            $grantsQuery->orderByDesc('expires_at')->orderByDesc('id');
        } else {
            $grantsQuery->orderByRaw('CASE WHEN expires_at IS NULL THEN 1 ELSE 0 END ASC')
                ->orderBy('expires_at')
                ->orderByDesc('id');
        }
        $now = now();
        $riskBase = RolePermissionGrant::query()->where('is_active', true);
        $riskCounts = [
            'expiring_lt_24h' => (int) (clone $riskBase)->whereNotNull('expires_at')->whereBetween('expires_at', [$now, $now->copy()->addDay()])->count(),
            'expired_but_active' => (int) (clone $riskBase)->whereNotNull('expires_at')->where('expires_at', '<', $now)->count(),
            'without_reason' => (int) (clone $riskBase)->where(function ($q) {
                $q->whereNull('reason')->orWhere('reason', '');
            })->count(),
        ];

        return view('admin.rbac', [
            'roles' => self::ROLES,
            'permissions' => $permissions,
            'assigned' => $assigned,
            'approvers' => User::query()->whereIn('role', ['owner', 'admin'])->orderBy('name')->get(['id', 'name', 'role']),
            'temporaryGrants' => $grantsQuery->limit(50)->get(),
            'grantFilters' => [
                'q' => $q,
                'active' => in_array($active, ['all', 'active', 'inactive'], true) ? $active : 'all',
                'scope' => in_array($scope, ['all', 'role', 'user'], true) ? $scope : 'all',
                'sort' => in_array($sort, ['expires_asc', 'expires_desc'], true) ? $sort : 'expires_asc',
            ],
            'grantRiskCounts' => $riskCounts,
        ]);
    }

    public function grantTemporary(Request $request)
    {
        $validated = $request->validate([
            'permission_code' => ['required', 'string', 'max:120', Rule::exists('permissions', 'code')],
            'scope_type' => ['required', Rule::in(['role', 'user'])],
            'role' => ['nullable', Rule::in(self::ROLES)],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'duration_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ]);

        $scopeType = (string) $validated['scope_type'];
        $role = $scopeType === 'role' ? (string) ($validated['role'] ?? '') : null;
        $userId = $scopeType === 'user' ? (int) ($validated['user_id'] ?? 0) : null;
        if ($scopeType === 'role' && ! in_array((string) $role, self::ROLES, true)) {
            return back()->withErrors(['role' => 'Role wajib dipilih untuk scope role.']);
        }
        if ($scopeType === 'user' && $userId <= 0) {
            return back()->withErrors(['user_id' => 'User wajib dipilih untuk scope user.']);
        }

        $hours = (int) $validated['duration_hours'];
        $grant = RolePermissionGrant::query()->create([
            'user_id' => $userId ?: null,
            'role' => $role ?: null,
            'permission_code' => (string) $validated['permission_code'],
            'starts_at' => now(),
            'expires_at' => now()->addHours($hours),
            'granted_by' => (int) ($request->user()?->id ?? 0),
            'reason' => trim((string) $validated['reason']),
            'is_active' => true,
        ]);

        CashierAuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'action' => 'rbac_temporary_grant_created',
            'context' => [
                'grant_id' => (int) $grant->id,
                'permission_code' => (string) $grant->permission_code,
                'role' => $grant->role,
                'user_id' => $grant->user_id,
                'expires_at' => optional($grant->expires_at)->toIso8601String(),
            ],
            'ip_address' => (string) $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return back()->with('status', 'Temporary grant berhasil dibuat.');
    }

    public function revokeTemporary(Request $request, RolePermissionGrant $grant)
    {
        $grant->is_active = false;
        $grant->save();

        CashierAuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'action' => 'rbac_temporary_grant_revoked',
            'context' => [
                'grant_id' => (int) $grant->id,
                'permission_code' => (string) $grant->permission_code,
            ],
            'ip_address' => (string) $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return back()->with('status', 'Temporary grant dinonaktifkan.');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['nullable', 'array'],
            'permissions.*.*' => ['string', 'max:120'],
            'permissions_payload' => ['nullable', 'string', 'max:200000'],
        ]);

        $allCodes = Permission::query()->pluck('code')->all();
        $payload = (array) ($validated['permissions'] ?? []);
        if (empty($payload) && ! empty($validated['permissions_payload'])) {
            $decoded = json_decode((string) $validated['permissions_payload'], true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        DB::transaction(function () use ($payload, $allCodes, $request): void {
            $before = $this->snapshotRolePermissions($allCodes);
            $this->applyRolePermissionMapping($payload, $allCodes);
            $after = $this->snapshotRolePermissions($allCodes);
            $this->writeAudit($request, 'rbac_permissions_updated', $before, $after);
        });

        return back()->with('status', 'Mapping permission role berhasil diperbarui.');
    }

    public function resetDefault(Request $request)
    {
        $allCodes = Permission::query()->pluck('code')->all();
        $roleDefaults = (array) config('rbac.role_defaults', []);
        $payload = [];

        foreach (self::ROLES as $role) {
            $codes = (array) ($roleDefaults[$role] ?? []);
            if (in_array('*', $codes, true)) {
                $codes = $allCodes;
            }
            $payload[$role] = $codes;
        }

        DB::transaction(function () use ($payload, $allCodes, $request): void {
            $before = $this->snapshotRolePermissions($allCodes);
            $this->applyRolePermissionMapping($payload, $allCodes);
            $after = $this->snapshotRolePermissions($allCodes);
            $this->writeAudit($request, 'rbac_permissions_reset_default', $before, $after);
        });

        return back()->with('status', 'Mapping RBAC berhasil direset ke default konfigurasi.');
    }

    private function applyRolePermissionMapping(array $payload, array $allCodes): void
    {
        foreach (self::ROLES as $role) {
            $codes = array_values(array_unique(array_intersect((array) ($payload[$role] ?? []), $allCodes)));
            $permissionIds = Permission::query()->whereIn('code', $codes)->pluck('id')->all();

            DB::table('role_permissions')->where('role', $role)->delete();

            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->insert([
                    'role' => $role,
                    'permission_id' => $permissionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function snapshotRolePermissions(array $allCodes): array
    {
        $assigned = DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->select('role_permissions.role', 'permissions.code')
            ->get()
            ->groupBy('role')
            ->map(fn ($rows) => $rows->pluck('code')->values()->all())
            ->all();

        $snapshot = [];
        foreach (self::ROLES as $role) {
            $snapshot[$role] = array_values(array_unique(array_intersect((array) ($assigned[$role] ?? []), $allCodes)));
            sort($snapshot[$role]);
        }

        return $snapshot;
    }

    private function computeDiff(array $before, array $after): array
    {
        $changes = [];
        foreach (self::ROLES as $role) {
            $beforeCodes = (array) ($before[$role] ?? []);
            $afterCodes = (array) ($after[$role] ?? []);
            $added = array_values(array_diff($afterCodes, $beforeCodes));
            $removed = array_values(array_diff($beforeCodes, $afterCodes));
            sort($added);
            sort($removed);

            if ($added === [] && $removed === []) {
                continue;
            }

            $changes[$role] = [
                'added' => $added,
                'removed' => $removed,
            ];
        }

        return $changes;
    }

    private function writeAudit(Request $request, string $action, array $before, array $after): void
    {
        $diff = $this->computeDiff($before, $after);

        CashierAuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'context' => [
                'roles' => self::ROLES,
                'updated_by' => $request->user()?->email,
                'source' => 'web',
                'before' => $before,
                'after' => $after,
                'changes' => $diff,
            ],
            'ip_address' => (string) $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);
    }
}
