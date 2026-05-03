<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_transfer_id',
        'source_product_id',
        'sku',
        'product_name',
        'unit',
        'requested_qty',
        'reserved_qty',
        'received_qty',
        'discrepancy_reason',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }

    public function sourceProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'source_product_id');
    }
}
