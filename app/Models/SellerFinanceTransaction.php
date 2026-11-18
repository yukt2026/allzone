<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerFinanceTransaction extends Model
{
    use HasFactory;

    protected $table = 'seller_finance_transactions';

    protected $fillable = [
        'finance_application_id',
        'seller_id',
        'transaction_type',
        'amount',
        'reference_id',
        'status',
        'notes',
        'transacted_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transacted_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(SellerFinanceApplication::class, 'finance_application_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }
}
