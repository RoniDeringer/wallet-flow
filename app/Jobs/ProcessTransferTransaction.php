<?php

namespace App\Jobs;

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
            $tx = DB::table('ledger_transactions')
                ->where('id', '=', $this->ledgerTransactionId)
                ->lockForUpdate()
                ->first();

            if (! $tx) {
                return;
            }

            if ($tx->type !== 'transfer') {
                return;
            }

            if ($tx->status === 'posted' || $tx->status === 'failed') {
                return;
            }

            if (! $tx->from_account_id || ! $tx->to_account_id) {
                DB::table('ledger_transactions')->where('id', '=', $tx->id)->update([
                    'status' => 'failed',
                    'updated_at' => now(),
                ]);
                return;
            }

            $alreadyHasEntries = DB::table('ledger_entries')
                ->where('ledger_transaction_id', '=', $tx->id)
                ->exists();

            if ($alreadyHasEntries) {
                DB::table('ledger_transactions')->where('id', '=', $tx->id)->update([
                    'status' => 'posted',
                    'updated_at' => now(),
                ]);
                return;
            }

            $amount = (int) $tx->amount;
            if ($amount <= 0) {
                DB::table('ledger_transactions')->where('id', '=', $tx->id)->update([
                    'status' => 'failed',
                    'updated_at' => now(),
                ]);
                return;
            }

            $senderBalance = (int) DB::table('ledger_entries')
                ->where('account_id', '=', $tx->from_account_id)
                ->where('currency', '=', $tx->currency)
                ->sum('amount');

            if ($senderBalance < $amount) {
                DB::table('ledger_transactions')->where('id', '=', $tx->id)->update([
                    'status' => 'failed',
                    'meta' => json_encode(['reason' => 'insufficient_funds', 'balance_cents' => $senderBalance]),
                    'updated_at' => now(),
                ]);
                return;
            }

            DB::table('ledger_entries')->insert([
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

            DB::table('ledger_transactions')->where('id', '=', $tx->id)->update([
                'status' => 'posted',
                'updated_at' => now(),
            ]);
        }, 3);
    }
}
