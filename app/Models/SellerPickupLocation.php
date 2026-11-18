<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SellerPickupLocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'seller_id',
        'nickname',
        'contact_person',
        'contact_phone',
        'contact_email',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'pincode',
        'country',
        'gstin',
        'is_default',
        'status',
        'is_verified',
        'verified_at',
        'verified_by',
        'rejection_reason',
        'shiprocket_pickup_name',
        'is_serviceable',
        'serviceability_details',
        'package_types',
        'operating_hours',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_verified' => 'boolean',
        'is_serviceable' => 'boolean',
        'verified_at' => 'datetime',
        'serviceability_details' => 'array',
        'package_types' => 'array',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_line1,
            $this->address_line2,
            $this->city,
            $this->state . ' - ' . $this->pincode,
            $this->country,
        ]);

        return implode(', ', $parts);
    }
}
