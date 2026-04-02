<?php

namespace App\Jobs;

use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessTransferTransaction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $ledgerTransactionId,
    ) {
    }

    public function handle(): void
    {
        DB::transaction(function () {
            /** @var LedgerTransaction|null $tx */
            $tx = LedgerTransaction::query()
                ->where('id', '=', $this->ledgerTransactionId)
                ->lockForUpdate()
                ->first();

            if (! $tx) {
                return;
            }

            if ($tx->type !== LedgerTransaction::TYPE_TRANSFER) {
                return;
            }

            if ($tx->status === LedgerTransaction::STATUS_POSTED || $tx->status === LedgerTransaction::STATUS_FAILED) {
                return;
            }

            if (! $tx->from_account_id || ! $tx->to_account_id) {
                LedgerTransaction::query()->where('id', '=', $tx->id)->update([
                    'status' => LedgerTransaction::STATUS_FAILED,
                    'updated_at' => now(),
                ]);

                return;
            }

            $alreadyHasEntries = LedgerEntry::query()
                ->where('ledger_transaction_id', '=', $tx->id)
                ->exists();

            if ($alreadyHasEntries) {
                LedgerTransaction::query()->where('id', '=', $tx->id)->update([
                    'status' => LedgerTransaction::STATUS_POSTED,
                    'updated_at' => now(),
                ]);

                return;
            }

            $amount = (int) $tx->amount;
            if ($amount <= 0) {
                LedgerTransaction::query()->where('id', '=', $tx->id)->update([
                    'status' => LedgerTransaction::STATUS_FAILED,
                    'updated_at' => now(),
                ]);

                return;
            }

            $senderBalance = (int) LedgerEntry::query()
                ->where('account_id', '=', $tx->from_account_id)
                ->where('currency', '=', $tx->currency)
                ->sum('amount');

            if ($senderBalance < $amount) {
                LedgerTransaction::query()->where('id', '=', $tx->id)->update([
                    'status' => LedgerTransaction::STATUS_FAILED,
                    'meta' => ['reason' => 'insufficient_funds', 'balance_cents' => $senderBalance],
                    'updated_at' => now(),
                ]);

                return;
            }

            LedgerEntry::query()->insert([
                [
                    'ledger_transaction_id' => $tx->id,
                    'account_id' => $tx->from_account_id,
                    'amount' => -$amount,
                    'currency' => $tx->currency,
                    'balance_after' => null,
                    'description' => $tx->description,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'ledger_transaction_id' => $tx->id,
                    'account_id' => $tx->to_account_id,
                    'amount' => $amount,
                    'currency' => $tx->currency,
                    'balance_after' => null,
                    'description' => $tx->description,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            LedgerTransaction::query()->where('id', '=', $tx->id)->update([
                'status' => LedgerTransaction::STATUS_POSTED,
                'updated_at' => now(),
            ]);
        }, 3);
    }
}

