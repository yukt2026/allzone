<?php

declare(strict_types=1);

namespace App\Enums;

enum QCStatus: string
{
    case PENDING = 'pending';
    case PASSED = 'passed';
    case FAILED = 'failed';
    case WAIVED = 'waived';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'QC Pending',
            self::PASSED => 'QC Passed',
            self::FAILED => 'QC Failed',
            self::WAIVED => 'QC Waived',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/60 dark:text-amber-400 dark:border-amber-800',
            self::PASSED => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-400 dark:border-emerald-800',
            self::FAILED => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/60 dark:text-rose-400 dark:border-rose-800',
            self::WAIVED => 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700',
        };
    }
}
