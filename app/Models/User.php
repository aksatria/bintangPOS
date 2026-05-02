<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'branch_id',
        'role',
        'password',
        'photo',
        'ui_preferences',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'ui_preferences' => 'array',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role?->value, [UserRole::Owner->value, UserRole::Admin->value], true);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function posHolds(): HasMany
    {
        return $this->hasMany(PosHold::class);
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role?->value, $roles, true);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function hasPermission(string $permission): bool
    {
        $role = $this->role?->value;
        if (! $role) {
            return false;
        }

        $defaults = (array) config('rbac.role_defaults', []);
        $roleDefaults = (array) ($defaults[$role] ?? []);
        if (in_array('*', $roleDefaults, true)) {
            return true;
        }

        try {
            $now = Carbon::now();
            $hasTemporaryGrant = RolePermissionGrant::query()
                ->where('permission_code', $permission)
                ->where('is_active', true)
                ->where(function ($q) use ($role) {
                    $q->where('user_id', $this->id)
                        ->orWhere(function ($qr) use ($role) {
                            $qr->whereNull('user_id')->where('role', $role);
                        });
                })
                ->where(function ($q) use ($now) {
                    $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
                })
                ->where(function ($q) use ($now) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', $now);
                })
                ->exists();
            if ($hasTemporaryGrant) {
                return true;
            }

            $exists = DB::table('role_permissions')
                ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
                ->where('role_permissions.role', $role)
                ->where('permissions.code', $permission)
                ->exists();

            if ($exists) {
                return true;
            }

            $roleHasAnyDbPermission = DB::table('role_permissions')
                ->where('role', $role)
                ->exists();

            if ($roleHasAnyDbPermission) {
                return false;
            }
        } catch (\Throwable $e) {
            // fallback ke default map di bawah jika tabel belum ada atau query gagal
        }

        return in_array($permission, $roleDefaults, true);
    }
}
