<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPurchaseAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_purchase_id',
        'supplier_purchase_payment_id',
        'uploaded_by',
        'kind',
        'path',
        'original_name',
        'mime_type',
        'size',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(SupplierPurchase::class, 'supplier_purchase_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(SupplierPurchasePayment::class, 'supplier_purchase_payment_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
