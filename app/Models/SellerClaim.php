<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ClaimStatus;
use App\Enums\ClaimType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SellerClaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'claim_number',
        'seller_id',
        'order_id',
        'fulfilment_order_id',
        'order_item_id',
        'order_return_id',
        'claim_type',
        'reason',
        'description',
        'status',
        'claimed_amount',
        'approved_amount',
        'evidence_urls',
        'rejection_reason',
        'admin_notes',
        'compensation_reference',
        'compensated_at',
        'closed_at',
    ];

    protected $casts = [
        'status' => ClaimStatus::class,
        'claim_type' => ClaimType::class,
        'claimed_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'evidence_urls' => 'array',
        'compensated_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public static function generateClaimNumber(): string
    {
        return 'CLM-' . strtoupper(now()->format('ymd')) . '-' . strtoupper(substr(uniqid(), -5));
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function fulfilmentOrder(): BelongsTo
    {
        return $this->belongsTo(FulfilmentOrder::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function orderReturn(): BelongsTo
    {
        return $this->belongsTo(OrderReturn::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ClaimMessage::class, 'seller_claim_id');
    }

    public function publicMessages(): HasMany
    {
        return $this->hasMany(ClaimMessage::class, 'seller_claim_id')->where('is_internal', false);
    }
}
