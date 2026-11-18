<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'category',
        'priority',
        'summary',
        'content',
        'banner_image',
        'attachment_path',
        'attachment_name',
        'target_type',
        'target_seller_type',
        'target_seller_ids',
        'published_at',
        'expires_at',
        'is_published',
        'created_by',
    ];

    protected $casts = [
        'target_seller_ids' => 'array',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_published' => 'boolean',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            });
    }

    public function scopeForSeller(Builder $query, Seller $seller): Builder
    {
        $sellerType = is_object($seller->seller_type) ? $seller->seller_type->value : (string) $seller->seller_type;

        return $query->where(function ($q) use ($seller, $sellerType) {
            $q->where('target_type', 'all')
              ->orWhere(function ($sub) use ($sellerType) {
                  $sub->where('target_type', 'seller_group')
                      ->where('target_seller_type', $sellerType);
              })
              ->orWhere(function ($sub) use ($seller) {
                  $sub->where('target_type', 'specific_sellers')
                      ->whereJsonContains('target_seller_ids', $seller->id);
              });
        });
    }

    public function isReadBy(int|Seller $seller): bool
    {
        $sellerId = $seller instanceof Seller ? $seller->id : $seller;

        if ($this->relationLoaded('reads')) {
            return $this->reads->contains('seller_id', $sellerId);
        }

        return $this->reads()->where('seller_id', $sellerId)->exists();
    }

    public function markAsReadFor(int|Seller $seller): void
    {
        $sellerId = $seller instanceof Seller ? $seller->id : $seller;

        AnnouncementRead::firstOrCreate([
            'announcement_id' => $this->id,
            'seller_id' => $sellerId,
        ], [
            'read_at' => now(),
        ]);
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'important' => 'Important Notice',
            'policy' => 'Policy Update',
            'logistics' => 'Logistics & Cargo',
            'promotional' => 'Promotional Event',
            'feature' => 'Platform Feature',
            default => 'General Announcement',
        };
    }

    public function getPriorityBadgeColorAttribute(): string
    {
        return match ($this->priority) {
            'urgent' => 'rose',
            'high' => 'amber',
            'low' => 'slate',
            default => 'indigo',
        };
    }

    public function getCategoryBadgeColorAttribute(): string
    {
        return match ($this->category) {
            'important' => 'rose',
            'policy' => 'amber',
            'logistics' => 'sky',
            'promotional' => 'purple',
            'feature' => 'emerald',
            default => 'slate',
        };
    }
}
