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

class ProcessDepositTransaction implements ShouldQueue
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

            if ($tx->type !== LedgerTransaction::TYPE_DEPOSIT) {
                return;
            }

            if ($tx->status === LedgerTransaction::STATUS_POSTED) {
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

            if (! $tx->from_account_id || ! $tx->to_account_id) {
                LedgerTransaction::query()->where('id', '=', $tx->id)->update([
                    'status' => LedgerTransaction::STATUS_FAILED,
                    'updated_at' => now(),
                ]);

                return;
            }

            LedgerEntry::query()->insert([
                [
                    'ledger_transaction_id' => $tx->id,
                    'account_id' => $tx->to_account_id,
                    'amount' => $tx->amount,
                    'currency' => $tx->currency,
                    'balance_after' => null,
                    'description' => $tx->description,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'ledger_transaction_id' => $tx->id,
                    'account_id' => $tx->from_account_id,
                    'amount' => -$tx->amount,
                    'currency' => $tx->currency,
                    'balance_after' => null,
                    'description' => ($tx->description ? $tx->description.' (contrapartida)' : null),
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

