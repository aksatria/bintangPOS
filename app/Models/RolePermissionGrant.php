<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePermissionGrant extends Model
{
    protected $fillable = [
        'user_id',
        'role',
        'permission_code',
        'starts_at',
        'expires_at',
        'granted_by',
        'reason',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}

