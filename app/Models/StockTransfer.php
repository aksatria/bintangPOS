<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    use HasFactory;

    public const STATUS_REQUESTED = 'requested';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'source_branch_id',
        'destination_branch_id',
        'requested_by',
        'approved_by',
        'received_by',
        'code',
        'status',
        'note',
        'delivery_ref',
        'courier_name',
        'receive_note',
        'dispatch_proof_path',
        'receive_proof_path',
        'reject_reason',
        'approved_at',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    public function sourceBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'source_branch_id');
    }

    public function destinationBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'destination_branch_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }
}
