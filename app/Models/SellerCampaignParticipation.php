<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SellerCampaignParticipation extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'seller_id',
        'status',
        'enrolled_at',
        'opted_out_at',
        'approved_at',
        'approved_by',
        'rejection_reason',
        'agreed_to_terms',
        'notes',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'opted_out_at' => 'datetime',
        'approved_at' => 'datetime',
        'approved_by' => 'integer',
        'agreed_to_terms' => 'boolean',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isApproved(): bool
    {
        return $this->status === 'enrolled' || $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending_approval';
    }

    public function products(): HasMany
    {
        return $this->hasMany(SellerCampaignProduct::class, 'campaign_id', 'campaign_id')
            ->where('seller_id', $this->seller_id);
    }
}
