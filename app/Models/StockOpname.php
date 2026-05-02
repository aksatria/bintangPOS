<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockOpname extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'branch_id',
        'code',
        'status',
        'total_items',
        'adjusted_items',
        'total_difference',
        'note',
        'posted_note',
        'started_at',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'posted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class);
    }
}
