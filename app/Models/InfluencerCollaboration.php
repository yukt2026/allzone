<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfluencerCollaboration extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'influencer_profile_id',
        'product_id',
        'campaign_title',
        'proposal_notes',
        'proposed_budget',
        'payment_status',
        'paid_at',
        'payment_reference',
        'status',
        'deliverable_url',
        'admin_notes',
        'impressions',
        'clicks',
        'attributed_orders',
        'attributed_revenue',
    ];

    protected $casts = [
        'proposed_budget' => 'decimal:2',
        'attributed_revenue' => 'decimal:2',
        'paid_at' => 'datetime',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'attributed_orders' => 'integer',
    ];

    protected $appends = [
        'roi',
    ];

    public function getRoiAttribute(): ?float
    {
        $budget = (float) $this->proposed_budget;
        $revenue = (float) $this->attributed_revenue;

        if ($budget > 0 && $revenue > 0) {
            return round($revenue / $budget, 2);
        }

        return null;
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function influencer(): BelongsTo
    {
        return $this->belongsTo(InfluencerProfile::class, 'influencer_profile_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
