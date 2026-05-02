<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class PosHold extends Model
{
    protected $fillable = [
        'user_id',
        'branch_id',
        'hold_code',
        'label',
        'payload',
        'total_qty',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function getRouteKeyName(): string
    {
        return 'hold_code';
    }
}
