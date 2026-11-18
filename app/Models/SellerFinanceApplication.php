<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SellerFinanceApplication extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'seller_finance_applications';

    protected $fillable = [
        'seller_id',
        'application_type',
        'requested_amount',
        'approved_limit',
        'outstanding_balance',
        'pan_number',
        'gstin',
        'annual_turnover',
        'status',
        'provider_name',
        'provider_reference_id',
        'terms_accepted_at',
        'seller_notes',
        'admin_notes',
        'rejection_reason',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'approved_limit' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'annual_turnover' => 'decimal:2',
        'terms_accepted_at' => 'datetime',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(SellerFinanceTransaction::class, 'finance_application_id');
    }

    /**
     * Scope for active applications.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['registered', 'under_review', 'approved']);
    }
}
