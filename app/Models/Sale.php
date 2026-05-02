<?php

namespace App\Models;

use App\Enums\SaleStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'branch_id',
        'customer_id',
        'invoice_number',
        'customer_name',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'rounding_amount',
        'admin_fee_amount',
        'total_amount',
        'paid_amount',
        'change_amount',
        'payment_method',
        'payment_breakdown',
        'payment_due_at',
        'payment_attempt_count',
        'payment_attempt_logs',
        'status',
        'note',
        'sold_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'rounding_amount' => 'decimal:2',
            'admin_fee_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'payment_breakdown' => 'array',
            'payment_due_at' => 'datetime',
            'payment_attempt_logs' => 'array',
            'sold_at' => 'datetime',
            'status' => SaleStatus::class,
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', SaleStatus::Paid->value);
    }
}
