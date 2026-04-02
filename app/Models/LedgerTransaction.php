<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LedgerTransaction extends Model
{
    public const TYPE_DEPOSIT = 'deposit';
    public const TYPE_TRANSFER = 'transfer';
    public const TYPE_REVERSAL = 'reversal';

    public const STATUS_PENDING = 'pending';
    public const STATUS_POSTED = 'posted';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REVERSED = 'reversed';

    public const CURRENCY_BRL = 'BRL';

    public const DESCRIPTION_DEPOSIT = 'Depósito';
    public const DESCRIPTION_TRANSFER = 'Transferência';
    public const DESCRIPTION_REVERSAL = 'Reversão';

    public const META_SOURCE_MANUAL = 'manual';

    public const TYPES = [
        self::TYPE_DEPOSIT,
        self::TYPE_TRANSFER,
        self::TYPE_REVERSAL,
    ];

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_POSTED,
        self::STATUS_FAILED,
        self::STATUS_REVERSED,
    ];

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
