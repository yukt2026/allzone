<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ReconciliationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'gateway',
        'gateway_order_id',
        'gateway_payment_id',
        'gateway_signature',
        'method',
        'amount',
        'currency',
        'status',
        'reconciliation_status',
        'raw_response',
        'verified_at',
        'reconciled_at',
        'reconciled_by',
        'reconciliation_notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'method' => PaymentMethod::class,
        'status' => PaymentStatus::class,
        'reconciliation_status' => ReconciliationStatus::class,
        'raw_response' => 'array',
        'verified_at' => 'datetime',
        'reconciled_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function reconciler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::PAID);
    }

    public function scopeReconciled(Builder $query): Builder
    {
        return $query->where('reconciliation_status', ReconciliationStatus::RECONCILED);
    }

    public function scopeUnreconciled(Builder $query): Builder
    {
        return $query->whereIn('reconciliation_status', [
            ReconciliationStatus::UNRECONCILED,
            ReconciliationStatus::DISCREPANCY,
            ReconciliationStatus::PENDING,
        ]);
    }
}
