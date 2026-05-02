<?php

namespace App\Console\Commands;

use App\Models\Permission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RbacHealthCheckCommand extends Command
{
    protected $signature = 'rbac:health-check {--repair : Perbaiki permission default yang hilang dan mapping default role yang belum lengkap} {--json : Keluarkan hasil dalam format JSON}';

    protected $description = 'Audit konsistensi konfigurasi RBAC, tabel permissions, dan role_permissions.';

    public function handle(): int
    {
        $repair = (bool) $this->option('repair');
        $asJson = (bool) $this->option('json');
        $roles = array_keys((array) config('rbac.role_defaults', []));
        $configPermissions = array_values(array_unique((array) config('rbac.permissions', [])));

        $dbPermissions = Permission::query()->pluck('code')->all();
        $missingPermissions = array_values(array_diff($configPermissions, $dbPermissions));
        $extraPermissions = array_values(array_diff($dbPermissions, $configPermissions));

        if ($repair && $missingPermissions !== []) {
            foreach ($missingPermissions as $code) {
                Permission::query()->updateOrCreate(
                    ['code' => $code],
                    [
                        'name' => $code,
                        'group' => 'system',
                        'description' => $code,
                    ]
                );
            }
        }

        $unknownRoles = DB::table('role_permissions')
            ->select('role')
            ->distinct()
            ->pluck('role')
            ->filter(fn ($role) => ! in_array((string) $role, $roles, true))
            ->values()
            ->all();

        $orphanRolePermissionCount = DB::table('role_permissions')
            ->leftJoin('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->whereNull('permissions.id')
            ->count();

        $missingDefaultMappings = $this->collectMissingDefaultMappings($roles);
        if ($repair && $missingDefaultMappings !== []) {
            foreach ($missingDefaultMappings as $row) {
                $permissionId = (int) Permission::query()->where('code', $row['code'])->value('id');
                if ($permissionId <= 0) {
                    continue;
                }

                DB::table('role_permissions')->updateOrInsert(
                    [
                        'role' => $row['role'],
                        'permission_id' => $permissionId,
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
            $missingDefaultMappings = $this->collectMissingDefaultMappings($roles);
        }

        if ($repair) {
            $dbPermissions = Permission::query()->pluck('code')->all();
            $missingPermissions = array_values(array_diff($configPermissions, $dbPermissions));
            $extraPermissions = array_values(array_diff($dbPermissions, $configPermissions));
        }

        $hasBlockingIssue = $missingPermissions !== []
            || $unknownRoles !== []
            || $orphanRolePermissionCount > 0
            || $missingDefaultMappings !== [];

        $payload = [
            'ok' => ! $hasBlockingIssue,
            'mode' => [
                'repair' => $repair,
                'json' => $asJson,
            ],
            'summary' => [
                'role_config_count' => count($roles),
                'permission_config_count' => count($configPermissions),
                'permission_db_count' => count($dbPermissions),
                'missing_permission_count' => count($missingPermissions),
                'extra_permission_count' => count($extraPermissions),
                'unknown_role_count' => count($unknownRoles),
                'orphan_role_permission_count' => $orphanRolePermissionCount,
                'missing_default_mapping_count' => count($missingDefaultMappings),
            ],
            'details' => [
                'missing_permissions' => $missingPermissions,
                'extra_permissions' => $extraPermissions,
                'unknown_roles' => $unknownRoles,
                'missing_default_mappings' => $missingDefaultMappings,
            ],
        ];

        if ($asJson) {
            $this->line((string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        } else {
            $this->line('RBAC Health Check');
            $this->line('- Role config: '.count($roles));
            $this->line('- Permission config: '.count($configPermissions));
            $this->line('- Permission DB: '.count($dbPermissions));
            $this->line('- Missing permission in DB: '.count($missingPermissions));
            $this->line('- Extra permission in DB: '.count($extraPermissions));
            $this->line('- Unknown role in role_permissions: '.count($unknownRoles));
            $this->line('- Orphan role_permissions: '.$orphanRolePermissionCount);
            $this->line('- Missing default role mappings: '.count($missingDefaultMappings));

            if ($missingPermissions !== []) {
                $this->warn('Missing permissions: '.implode(', ', $missingPermissions));
            }
            if ($unknownRoles !== []) {
                $this->warn('Unknown roles in role_permissions: '.implode(', ', $unknownRoles));
            }
            if ($missingDefaultMappings !== []) {
                $this->warn('Missing default mappings: '.collect($missingDefaultMappings)->map(fn ($m) => $m['role'].':'.$m['code'])->implode(', '));
            }
            if ($extraPermissions !== []) {
                $this->info('Extra permissions in DB (informational): '.implode(', ', $extraPermissions));
            }
        }

        if ($hasBlockingIssue) {
            if (! $asJson) {
                $this->error($repair
                    ? 'RBAC masih memiliki isu yang perlu ditinjau manual.'
                    : 'RBAC tidak sehat. Jalankan dengan --repair untuk auto-perbaikan terbatas.');
            }
            return self::FAILURE;
        }

        if (! $asJson) {
            $this->info($repair ? 'RBAC sehat setelah repair.' : 'RBAC sehat.');
        }
        return self::SUCCESS;
    }

    /**
     * @return array<int, array{role:string, code:string}>
     */
    private function collectMissingDefaultMappings(array $roles): array
    {
        $allCodes = Permission::query()->pluck('code')->all();
        $roleDefaults = (array) config('rbac.role_defaults', []);
        $rows = [];

        foreach ($roles as $role) {
            $expected = (array) ($roleDefaults[$role] ?? []);
            if (in_array('*', $expected, true)) {
                $expected = $allCodes;
            }

            $current = DB::table('role_permissions')
                ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
                ->where('role_permissions.role', $role)
                ->pluck('permissions.code')
                ->all();

            $missing = array_values(array_diff($expected, $current));
            foreach ($missing as $code) {
                $rows[] = ['role' => $role, 'code' => $code];
            }
        }

        return $rows;
    }
}
