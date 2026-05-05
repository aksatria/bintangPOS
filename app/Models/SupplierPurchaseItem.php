<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_purchase_id',
        'product_id',
        'product_name',
        'quantity',
        'received_quantity',
        'returned_quantity',
        'unit_cost',
        'line_total',
        'shipping_allocation_amount',
        'landed_unit_cost',
        'landed_line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'received_quantity' => 'integer',
            'returned_quantity' => 'integer',
            'unit_cost' => 'decimal:2',
            'line_total' => 'decimal:2',
            'shipping_allocation_amount' => 'decimal:2',
            'landed_unit_cost' => 'decimal:2',
            'landed_line_total' => 'decimal:2',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(SupplierPurchase::class, 'supplier_purchase_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
