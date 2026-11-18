<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaimMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_claim_id',
        'user_id',
        'is_internal',
        'message',
        'action_type',
        'attachments',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
        'attachments' => 'array',
    ];

    public function claim(): BelongsTo
    {
        return $this->belongsTo(SellerClaim::class, 'seller_claim_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
