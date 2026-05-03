<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerDebt extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'customer_id',
        'sale_id',
        'created_by',
        'number',
        'debt_date',
        'due_date',
        'principal_amount',
        'paid_amount',
        'remaining_amount',
        'status',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'debt_date' => 'date',
            'due_date' => 'date',
            'principal_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CustomerDebtPayment::class);
    }
}
