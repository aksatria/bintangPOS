<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class CashierAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'branch_id',
        'action',
        'context',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
        ];
    }

    public function setContextAttribute($value): void
    {
        $context = is_array($value) ? $value : [];
        $meta = is_array(data_get($context, '_meta')) ? $context['_meta'] : [];
        $context['_meta'] = array_merge([
            'schema' => 'cashier_audit_log.v1',
            'recorded_at' => now()->toIso8601String(),
        ], $meta);

        $this->attributes['context'] = json_encode($context, JSON_UNESCAPED_UNICODE);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    protected static function booted(): void
    {
        static::creating(function (self $log): void {
            if ($log->branch_id) {
                return;
            }

            $user = Auth::user();
            if ($user && $user->branch_id) {
                $log->branch_id = $user->branch_id;
                return;
            }

            if ($log->user_id) {
                $branchId = User::query()->whereKey($log->user_id)->value('branch_id');
                if ($branchId) {
                    $log->branch_id = $branchId;
                }
            }
        });
    }
}
