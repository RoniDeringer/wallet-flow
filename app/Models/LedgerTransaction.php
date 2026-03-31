<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LedgerTransaction extends Model
{
    protected $table = 'ledger_transactions';

    protected $fillable = [
        'uuid',
        'type',
        'status',
        'amount',
        'currency',
        'requested_by_user_id',
        'from_account_id',
        'to_account_id',
        'reversal_of_id',
        'idempotency_key',
        'description',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'from_account_id');
    }

    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class, 'ledger_transaction_id');
    }
}

