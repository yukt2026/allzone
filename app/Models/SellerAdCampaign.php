<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SellerAdCampaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'seller_id',
        'name',
        'type',
        'status',
        'daily_budget',
        'total_budget',
        'spent_amount',
        'billing_status',
        'billing_notes',
        'starts_at',
        'ends_at',
        'targeting_type',
        'targeting_payload',
        'creative_image_url',
        'headline',
        'rejection_reason',
        'approved_at',
        'approved_by',
        'impressions_count',
        'clicks_count',
        'attributed_orders_count',
        'attributed_revenue',
    ];

    protected $casts = [
        'daily_budget' => 'decimal:2',
        'total_budget' => 'decimal:2',
        'spent_amount' => 'decimal:2',
        'attributed_revenue' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'approved_at' => 'datetime',
        'targeting_payload' => 'array',
        'impressions_count' => 'integer',
        'clicks_count' => 'integer',
        'attributed_orders_count' => 'integer',
    ];

    protected $appends = [
        'roi',
        'ctr',
    ];

    /**
     * ROI is computed strictly when reliable ad spend and attributed revenue exist.
     */
    public function getRoiAttribute(): ?float
    {
        $spent = (float) $this->spent_amount;
        $revenue = (float) $this->attributed_revenue;

        if ($spent > 0 && $revenue > 0) {
            return round($revenue / $spent, 2);
        }

        return null;
    }

    /**
     * Click-Through-Rate percentage.
     */
    public function getCtrAttribute(): float
    {
        if ($this->impressions_count > 0) {
            return round(($this->clicks_count / $this->impressions_count) * 100, 2);
        }

        return 0.0;
    }

    /**
     * Normalized creative image URL ensuring reliable rendering across localhost and 127.0.0.1.
     */
    public function getCreativeImageUrlAttribute(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if (str_contains($value, '/storage/')) {
            $parsed = parse_url($value);
            return $parsed['path'] ?? ('/storage/' . ltrim(explode('/storage/', $value)[1] ?? '', '/'));
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        return '/storage/' . ltrim($value, '/');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(SellerAdCampaignProduct::class, 'ad_campaign_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopePaused(Builder $query): Builder
    {
        return $query->where('status', 'paused');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending_approval');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }
}
