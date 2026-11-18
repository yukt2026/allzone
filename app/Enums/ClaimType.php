<?php

declare(strict_types=1);

namespace App\Enums;

enum ClaimType: string
{
    case RTO_DAMAGE = 'rto_damage';
    case RTO_MISSING = 'rto_missing';
    case RETURN_WRONG_ITEM = 'return_wrong_item';
    case RETURN_USED_DAMAGED = 'return_used_damaged';
    case COURIER_LOSS = 'courier_loss';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::RTO_DAMAGE => 'RTO Consignment Damaged in Transit',
            self::RTO_MISSING => 'RTO Consignment Missing Items',
            self::RETURN_WRONG_ITEM => 'Customer Returned Wrong / Fake Item',
            self::RETURN_USED_DAMAGED => 'Customer Returned Used / Damaged Item',
            self::COURIER_LOSS => 'Package Lost by Logistics Carrier',
            self::OTHER => 'Other Operational Issue',
        };
    }
}
