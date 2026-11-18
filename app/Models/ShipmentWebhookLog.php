<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentWebhookLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'event',
        'awb',
        'order_id',
        'current_status',
        'status_code',
        'source_ip',
        'is_verified',
        'error',
        'payload',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'status_code' => 'integer',
        'payload' => 'array',
    ];
}
