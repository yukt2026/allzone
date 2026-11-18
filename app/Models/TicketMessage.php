<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TicketMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'support_ticket_id',
        'user_id',
        'message',
        'attachments',
        'is_internal',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_internal' => 'boolean',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasAttachments(): bool
    {
        return !empty($this->attachments) && is_array($this->attachments) && count($this->attachments) > 0;
    }

    public function getAttachmentUrlsAttribute(): array
    {
        if (!$this->hasAttachments()) {
            return [];
        }

        return array_map(function ($item) {
            $path = is_array($item) ? ($item['path'] ?? '') : (string) $item;
            $name = is_array($item) ? ($item['name'] ?? basename($path)) : basename($path);

            return [
                'name' => $name,
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
                'is_image' => (bool) preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $path),
            ];
        }, $this->attachments);
    }
}
