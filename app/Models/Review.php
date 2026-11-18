<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'rating',
        'title',
        'body',
        'media',
        'is_verified_purchase',
        'status',
        'seller_response',
        'seller_responded_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'media' => 'array',
        'is_verified_purchase' => 'boolean',
        'status' => ReviewStatus::class,
        'seller_responded_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', ReviewStatus::APPROVED);
    }

    public function scopeForSeller(Builder $query, int $sellerId): Builder
    {
        return $query->whereHas('product', fn ($q) => $q->where('seller_id', $sellerId));
    }

    public function hasSellerResponse(): bool
    {
        return !empty($this->seller_response);
    }
}
