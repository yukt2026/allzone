<?php

declare(strict_types=1);

namespace App\Enums;

enum SettlementStatus: string
{
    case PENDING = 'pending';
    case ON_HOLD = 'on_hold';
    case APPROVED = 'approved';
    case PROCESSING = 'processing';
    case PAID = 'paid';
    case SETTLED = 'settled';
    case FAILED = 'failed';
    case DISPUTED = 'disputed';
    case REVERSED = 'reversed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending Settlement',
            self::ON_HOLD => 'On Hold',
            self::APPROVED => 'Approved for Payout',
            self::PROCESSING => 'Processing Payout',
            self::PAID => 'Paid (Completed)',
            self::SETTLED => 'Settled',
            self::FAILED => 'Payout Failed',
            self::DISPUTED => 'Disputed',
            self::REVERSED => 'Reversed',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING => 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-400 border-amber-200 dark:border-amber-800',
            self::ON_HOLD => 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700',
            self::APPROVED => 'bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-400 border-sky-200 dark:border-sky-800',
            self::PROCESSING => 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-400 border-blue-200 dark:border-blue-800',
            self::PAID, self::SETTLED => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800',
            self::FAILED => 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-400 border-rose-200 dark:border-rose-800',
            self::DISPUTED => 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-400 border-purple-200 dark:border-purple-800',
            self::REVERSED => 'bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-400 border-red-200 dark:border-red-800',
        };
    }
}
