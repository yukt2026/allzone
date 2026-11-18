<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentWebhookLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'gateway',
        'event',
        'gateway_order_id',
        'gateway_payment_id',
        'payload',
        'signature',
        'is_valid_signature',
        'status',
        'error_message',
        'ip_address',
    ];

    protected $casts = [
        'payload' => 'array',
        'is_valid_signature' => 'boolean',
    ];

    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', 'processed');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }
}
