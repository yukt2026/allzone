<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerBankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'account_holder',
        'account_no',
        'ifsc',
        'bank_name',
        'branch_name',
        'is_primary',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'account_no' => 'encrypted',
            'is_primary' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function getMaskedAccountNoAttribute(): string
    {
        $raw = (string) $this->account_no;
        $len = strlen($raw);
        if ($len <= 4) {
            return '•••• ' . $raw;
        }
        return str_repeat('•', 8) . ' ' . substr($raw, -4);
    }

    public function getIsVerifiedAttribute(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * Scope query to primary bank account.
     */
    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }

    /**
     * Seller profile relation.
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }
}
