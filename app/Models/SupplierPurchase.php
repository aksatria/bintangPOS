<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierPurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'supplier_id',
        'created_by',
        'number',
        'supplier_invoice_number',
        'delivery_note_number',
        'supplier_invoice_amount',
        'status',
        'ordered_at',
        'payment_term_days',
        'due_date',
        'received_at',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'shipping_amount',
        'shipping_accounting_treatment',
        'shipping_allocation_method',
        'inventory_shipping_amount',
        'expense_shipping_amount',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'payment_status',
        'reconciliation_status',
        'reconciliation_note',
        'paid_at',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'ordered_at' => 'date',
            'payment_term_days' => 'integer',
            'due_date' => 'date',
            'received_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'shipping_amount' => 'decimal:2',
            'inventory_shipping_amount' => 'decimal:2',
            'expense_shipping_amount' => 'decimal:2',
            'supplier_invoice_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierPurchaseItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPurchasePayment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SupplierPurchaseAttachment::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(SupplierPurchaseReturn::class);
    }
}
