<?php

declare(strict_types=1);

namespace App\Enums;

enum ClaimStatus: string
{
    case OPEN = 'open';
    case UNDER_REVIEW = 'under_review';
    case MORE_INFO_NEEDED = 'more_info_needed';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Open',
            self::UNDER_REVIEW => 'Under Review',
            self::MORE_INFO_NEEDED => 'More Info Needed',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::CLOSED => 'Closed',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::OPEN => 'bg-blue-50 text-blue-700 border-blue-200',
            self::UNDER_REVIEW => 'bg-amber-50 text-amber-700 border-amber-200',
            self::MORE_INFO_NEEDED => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            self::APPROVED => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::REJECTED => 'bg-rose-50 text-rose-700 border-rose-200',
            self::CLOSED => 'bg-slate-100 text-slate-600 border-slate-200',
        };
    }
}
