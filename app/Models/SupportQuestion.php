<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'support_category_id',
        'question',
        'slug',
        'short_answer',
        'requires_order',
        'requires_product',
        'requires_shipment',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'requires_order' => 'boolean',
        'requires_product' => 'boolean',
        'requires_shipment' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(SupportCategory::class, 'support_category_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
