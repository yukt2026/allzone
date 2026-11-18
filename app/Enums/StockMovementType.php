<?php

declare(strict_types=1);

namespace App\Enums;

enum StockMovementType: string
{
    case PURCHASE = 'purchase';
    case SALE = 'sale';
    case RESERVE = 'reserve';
    case RELEASE = 'release';
    case ADJUSTMENT = 'adjustment';
    case RETURN = 'return';
    case SYNC = 'sync';
    case RESTOCK = 'restock';
    case TRANSFER = 'transfer';

    public function label(): string
    {
        return match ($this) {
            self::PURCHASE => 'Purchase / Inward Stock',
            self::SALE => 'Delivered Sale / Outward',
            self::RESERVE => 'Cart/Order Reservation Lock',
            self::RELEASE => 'Unfulfilled Reservation Release',
            self::ADJUSTMENT => 'Manual Inventory Reconciliation',
            self::RETURN => 'Customer Return / Restock',
            self::SYNC => 'POS Bi-directional Shelf Sync',
            self::RESTOCK => 'Inward Stock Restock',
            self::TRANSFER => 'Inter-location Stock Transfer',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PURCHASE, self::RESTOCK => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300',
            self::SALE => 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-950/40 dark:text-blue-300',
            self::RESERVE => 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-950/40 dark:text-amber-300',
            self::RELEASE => 'bg-purple-50 text-purple-700 ring-purple-600/20 dark:bg-purple-950/40 dark:text-purple-300',
            self::ADJUSTMENT => 'bg-orange-50 text-orange-700 ring-orange-600/20 dark:bg-orange-950/40 dark:text-orange-300',
            self::RETURN => 'bg-teal-50 text-teal-700 ring-teal-600/20 dark:bg-teal-950/40 dark:text-teal-300',
            self::SYNC => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20 dark:bg-indigo-950/40 dark:text-indigo-300',
            self::TRANSFER => 'bg-slate-50 text-slate-700 ring-slate-600/20 dark:bg-slate-800 dark:text-slate-300',
        };
    }
}
