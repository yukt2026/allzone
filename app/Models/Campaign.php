<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'banner',
        'type',
        'status',
        'discount_type',
        'discount_value',
        'min_discount_percentage',
        'max_discount_percentage',
        'max_discount',
        'min_product_rating',
        'starts_at',
        'ends_at',
        'opt_in_deadline',
        'allow_opt_out',
        'participation_requires_approval',
        'terms_and_conditions',
        'applicable_category_id',
        'eligible_seller_ids',
        'eligible_product_ids',
        'max_seller_products',
        'budget_limit',
        'usage_limit',
        'usage_count',
        'coupon_code',
        'is_active',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'opt_in_deadline' => 'datetime',
        'discount_value' => 'decimal:2',
        'min_discount_percentage' => 'decimal:2',
        'max_discount_percentage' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'min_product_rating' => 'decimal:2',
        'allow_opt_out' => 'boolean',
        'participation_requires_approval' => 'boolean',
        'is_active' => 'boolean',
        'max_seller_products' => 'integer',
        'budget_limit' => 'decimal:2',
        'usage_limit' => 'integer',
        'usage_count' => 'integer',
        'eligible_seller_ids' => 'array',
        'eligible_product_ids' => 'array',
    ];

    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('storefront.active_campaigns');
        });

        static::deleted(function () {
            Cache::forget('storefront.active_campaigns');
        });
    }

    protected $appends = [
        'type_label',
        'timeline_status',
    ];

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'sale' => 'Sale Campaign',
            'discount' => 'Discount Campaign',
            'coupon' => 'Coupon Campaign',
            'category' => 'Category Promotion',
            'seller' => 'Seller-Specific Promotion',
            'product' => 'Product Promotion',
            'flash_sale' => 'Flash Sale',
            'festive_season', 'festive_sale', 'festive' => 'Festive Season',
            'featured_stores' => 'Featured Stores',
            'clearance' => 'Clearance',
            'seasonal' => 'Seasonal Sale',
            default => ucwords(str_replace('_', ' ', (string) $this->type)),
        };
    }

    public function getTimelineStatusAttribute(): string
    {
        if ($this->status === 'paused') {
            return 'paused';
        }
        if ($this->status === 'ended') {
            return 'ended';
        }
        if ($this->status === 'draft') {
            return 'draft';
        }

        $now = now();
        if ($this->ends_at && $this->ends_at->isPast()) {
            return 'expired';
        }
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'upcoming';
        }
        return 'live';
    }

    public function isLive(): bool
    {
        if ($this->status === 'paused' || $this->status === 'ended' || $this->status === 'draft') {
            return false;
        }

        $now = now();
        return (bool) $this->is_active
            && (!$this->starts_at || $this->starts_at->lte($now))
            && (!$this->ends_at || $this->ends_at->gte($now));
    }

    public function isEligibleForSeller(Seller|int $seller): bool
    {
        $sellerId = $seller instanceof Seller ? $seller->id : $seller;

        if (!empty($this->eligible_seller_ids) && is_array($this->eligible_seller_ids)) {
            if (!in_array($sellerId, $this->eligible_seller_ids, false) && !in_array((string)$sellerId, $this->eligible_seller_ids, true)) {
                return false;
            }
        }

        if (!empty($this->eligible_product_ids) && is_array($this->eligible_product_ids)) {
            $hasProduct = Product::where('seller_id', $sellerId)
                ->whereIn('id', $this->eligible_product_ids)
                ->exists();
            if (!$hasProduct) {
                return false;
            }
        }

        return true;
    }

    public function scopeEligibleForSeller(Builder $query, Seller|int $seller): Builder
    {
        $sellerId = $seller instanceof Seller ? $seller->id : $seller;

        return $query->where(function (Builder $q) use ($sellerId) {
            $q->whereNull('eligible_seller_ids')
              ->orWhereJsonContains('eligible_seller_ids', $sellerId)
              ->orWhereJsonContains('eligible_seller_ids', (string) $sellerId);
        });
    }

    public function isUpcoming(): bool
    {
        return $this->starts_at && $this->starts_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->ends_at && $this->ends_at->isPast();
    }

    public function isOptInOpen(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        if ($this->isExpired()) {
            return false;
        }
        if ($this->opt_in_deadline && $this->opt_in_deadline->isPast()) {
            return false;
        }
        return true;
    }

    public function participations(): HasMany
    {
        return $this->hasMany(SellerCampaignParticipation::class);
    }

    public function participatingProducts(): HasMany
    {
        return $this->hasMany(SellerCampaignProduct::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'applicable_category_id');
    }

    public function participationForSeller(int $sellerId): ?SellerCampaignParticipation
    {
        return $this->participations()->where('seller_id', $sellerId)->first();
    }

    public function sellerStatus(int $sellerId): string
    {
        $participation = $this->participationForSeller($sellerId);
        if (!$participation) {
            return 'not_enrolled';
        }
        return $participation->status; // 'enrolled' or 'opted_out'
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>', now());
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('ends_at')->where('ends_at', '<', now());
    }
}
