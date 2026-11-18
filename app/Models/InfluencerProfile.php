<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InfluencerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'handle',
        'contact_email',
        'platform',
        'category_id',
        'bio',
        'profile_photo',
        'verified_followers',
        'collaboration_fee',
        'is_approved',
        'verification_notes',
        'status',
    ];

    protected $casts = [
        'verified_followers' => 'integer',
        'collaboration_fee' => 'decimal:2',
        'is_approved' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function collaborations(): HasMany
    {
        return $this->hasMany(InfluencerCollaboration::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')->where('is_approved', true);
    }
}
