<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerFollowUp extends Model
{
    use HasFactory;

    protected $table = 'customer_followups';

    protected $fillable = [
        'branch_id',
        'customer_id',
        'created_by',
        'action_type',
        'status',
        'note',
        'reminder_at',
        'reminded_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'reminder_at' => 'datetime',
            'reminded_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
