<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'user_id',
        'seller_id',
        'order_id',
        'product_id',
        'subject',
        'category',
        'support_category_id',
        'support_question_id',
        'awb_number',
        'callback_phone',
        'priority',
        'status',
        'assigned_to',
        'reopened_at',
        'first_response_at',
        'is_escalated',
        'escalated_at',
        'escalation_reason',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'priority' => TicketPriority::class,
        'status' => TicketStatus::class,
        'reopened_at' => 'datetime',
        'first_response_at' => 'datetime',
        'is_escalated' => 'boolean',
        'escalated_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function categoryRelation(): BelongsTo
    {
        return $this->belongsTo(SupportCategory::class, 'support_category_id');
    }

    public function questionRelation(): BelongsTo
    {
        return $this->belongsTo(SupportQuestion::class, 'support_question_id');
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedToUser(): BelongsTo
    {
        return $this->assignedAgent();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }

    public function publicMessages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->where('is_internal', false);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [TicketStatus::OPEN, TicketStatus::IN_PROGRESS, TicketStatus::WAITING_CUSTOMER]);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [TicketStatus::OPEN, TicketStatus::IN_PROGRESS, TicketStatus::WAITING_CUSTOMER], true);
    }

    public function isResolved(): bool
    {
        return $this->status === TicketStatus::RESOLVED;
    }

    public function isClosed(): bool
    {
        return $this->status === TicketStatus::CLOSED;
    }

    public function canBeReopened(): bool
    {
        if ($this->isOpen()) {
            return false;
        }

        // Allow reopening if closed or resolved within 14 days
        $referenceDate = $this->resolved_at ?? $this->closed_at ?? $this->updated_at;
        return $referenceDate->greaterThan(now()->subDays(14));
    }

    public function reopen(): void
    {
        $this->status = TicketStatus::IN_PROGRESS;
        $this->reopened_at = now();
        $this->save();
    }

    public function escalate(string $reason, ?int $assignedTo = null): void
    {
        $this->is_escalated = true;
        $this->escalated_at = now();
        $this->escalation_reason = $reason;
        $this->priority = TicketPriority::URGENT;
        if ($assignedTo) {
            $this->assigned_to = $assignedTo;
        }
        if ($this->status === TicketStatus::OPEN || $this->status === TicketStatus::WAITING_CUSTOMER) {
            $this->status = TicketStatus::IN_PROGRESS;
        }
        $this->save();
    }

    public function getStatusLabelAttribute(): string
    {
        $val = is_object($this->status) ? $this->status->value : (string)$this->status;

        return match ($val) {
            'open' => 'Open',
            'in_progress' => 'In Progress',
            'waiting_customer' => 'Waiting for Seller',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
            default => ucfirst(str_replace('_', ' ', $val)),
        };
    }

    public function getStatusBadgeColorAttribute(): string
    {
        $val = is_object($this->status) ? $this->status->value : (string)$this->status;

        return match ($val) {
            'open' => 'rose',
            'in_progress' => 'amber',
            'waiting_customer' => 'indigo',
            'resolved' => 'emerald',
            'closed' => 'slate',
            default => 'slate',
        };
    }

    public function getCategoryNameAttribute(): string
    {
        if ($this->categoryRelation) {
            return $this->categoryRelation->name;
        }

        return match ($this->category) {
            'orders' => 'Orders and Shipping',
            'returns' => 'Returns and RTO',
            'cataloging', 'product' => 'Products and Catalog',
            'pricing' => 'Pricing',
            'inventory' => 'Inventory',
            'payments', 'payout' => 'Payments',
            'claims' => 'Claims',
            'promotions' => 'Promotions',
            'account' => 'Account and Profile',
            default => ucfirst($this->category ?: 'Other Issues'),
        };
    }
}
