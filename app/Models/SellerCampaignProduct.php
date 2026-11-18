<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerCampaignProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'seller_id',
        'product_id',
        'original_price',
        'discount_percentage',
        'campaign_price',
        'status',
    ];

    protected $casts = [
        'original_price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'campaign_price' => 'decimal:2',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
