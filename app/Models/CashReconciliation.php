<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashReconciliation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'branch_id',
        'shift_date',
        'expected_cash',
        'actual_cash',
        'difference',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'shift_date' => 'date',
            'expected_cash' => 'decimal:2',
            'actual_cash' => 'decimal:2',
            'difference' => 'decimal:2',
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
}
