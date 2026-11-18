<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerAdCampaignProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'ad_campaign_id',
        'product_id',
        'bid_amount',
        'impressions',
        'clicks',
        'attributed_orders',
        'attributed_revenue',
        'status',
    ];

    protected $casts = [
        'bid_amount' => 'decimal:2',
        'attributed_revenue' => 'decimal:2',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'attributed_orders' => 'integer',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(SellerAdCampaign::class, 'ad_campaign_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
