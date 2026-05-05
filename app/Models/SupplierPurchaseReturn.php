<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPurchaseReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_purchase_id',
        'supplier_purchase_item_id',
        'created_by',
        'quantity',
        'unit_cost',
        'amount',
        'reason',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_cost' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(SupplierPurchase::class, 'supplier_purchase_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(SupplierPurchaseItem::class, 'supplier_purchase_item_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
