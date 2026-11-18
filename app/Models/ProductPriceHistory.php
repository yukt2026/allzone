<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPriceHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variant_id',
        'seller_id',
        'user_id',
        'old_price',
        'new_price',
        'old_mrp',
        'new_mrp',
        'change_type',
        'reason',
    ];

    protected $casts = [
        'old_price' => 'decimal:2',
        'new_price' => 'decimal:2',
        'old_mrp' => 'decimal:2',
        'new_mrp' => 'decimal:2',
    ];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate absolute change in selling price.
     */
    public function getPriceDifferenceAttribute(): float
    {
        return round((float) $this->new_price - (float) $this->old_price, 2);
    }

    /**
     * Calculate percentage change in selling price.
     */
    public function getPercentageChangeAttribute(): float
    {
        if ((float) $this->old_price <= 0) {
            return 0.0;
        }

        return round((((float) $this->new_price - (float) $this->old_price) / (float) $this->old_price) * 100, 1);
    }
}
