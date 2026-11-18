<?php

declare(strict_types=1);

namespace App\Enums;

enum ReconciliationStatus: string
{
    case RECONCILED = 'reconciled';
    case UNRECONCILED = 'unreconciled';
    case DISCREPANCY = 'discrepancy';
    case PENDING = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::RECONCILED => 'Reconciled',
            self::UNRECONCILED => 'Unreconciled',
            self::DISCREPANCY => 'Discrepancy Detected',
            self::PENDING => 'Pending Verification',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::RECONCILED => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800',
            self::UNRECONCILED => 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700',
            self::DISCREPANCY => 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-400 border-rose-200 dark:border-rose-800',
            self::PENDING => 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-400 border-amber-200 dark:border-amber-800',
        };
    }
}
