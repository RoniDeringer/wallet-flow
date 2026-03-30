<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessReversalTransaction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $ledgerTransactionId,
    ) {
    }

    public function handle(): void
    {
        DB::transaction(function () {
            $reversal = DB::table('ledger_transactions')
                ->where('id', '=', $this->ledgerTransactionId)
                ->lockForUpdate()
                ->first();

            if (! $reversal) {
                return;
            }

            if ($reversal->type !== 'reversal') {
                return;
            }

            if ($reversal->status === 'posted' || $reversal->status === 'failed') {
                return;
            }

            if (! $reversal->reversal_of_id) {
                DB::table('ledger_transactions')->where('id', '=', $reversal->id)->update([
                    'status' => 'failed',
                    'updated_at' => now(),
                ]);
                return;
            }

            $original = DB::table('ledger_transactions')
                ->where('id', '=', $reversal->reversal_of_id)
                ->lockForUpdate()
                ->first();

            if (! $original || $original->status !== 'posted') {
                DB::table('ledger_transactions')->where('id', '=', $reversal->id)->update([
                    'status' => 'failed',
                    'updated_at' => now(),
                ]);
                return;
            }

            $alreadyHasEntries = DB::table('ledger_entries')
                ->where('ledger_transaction_id', '=', $reversal->id)
                ->exists();

            if ($alreadyHasEntries) {
                DB::table('ledger_transactions')->where('id', '=', $reversal->id)->update([
                    'status' => 'posted',
                    'updated_at' => now(),
                ]);
                return;
            }

            $alreadyReversed = DB::table('ledger_transactions')
                ->where('type', '=', 'reversal')
                ->where('reversal_of_id', '=', $original->id)
                ->where('id', '!=', $reversal->id)
                ->exists();

            if ($alreadyReversed) {
                DB::table('ledger_transactions')->where('id', '=', $reversal->id)->update([
                    'status' => 'failed',
                    'meta' => json_encode(['reason' => 'already_reversed']),
                    'updated_at' => now(),
                ]);
                return;
            }

            $userAccountId = null;
            if ($reversal->requested_by_user_id) {
                $userAccountId = DB::table('accounts')
                    ->where('type', '=', 'user')
                    ->where('user_id', '=', $reversal->requested_by_user_id)
                    ->where('currency', '=', $reversal->currency)
                    ->value('id');
            }

            if ($userAccountId) {
                $hasPendingTransfer = DB::table('ledger_transactions')
                    ->where('type', '=', 'transfer')
                    ->where('status', '=', 'pending')
                    ->where(function ($q) use ($userAccountId) {
                        $q->where('from_account_id', '=', $userAccountId)
                            ->orWhere('to_account_id', '=', $userAccountId);
                    })
                    ->exists();

                if ($hasPendingTransfer) {
                    DB::table('ledger_transactions')->where('id', '=', $reversal->id)->update([
                        'status' => 'failed',
                        'meta' => json_encode(['reason' => 'pending_transfer']),
                        'updated_at' => now(),
                    ]);
                    return;
                }

                $currentBalance = (int) DB::table('ledger_entries')
                    ->where('account_id', '=', $userAccountId)
                    ->where('currency', '=', $reversal->currency)
                    ->sum('amount');

                $originalDelta = (int) DB::table('ledger_entries')
                    ->where('ledger_transaction_id', '=', $original->id)
                    ->where('account_id', '=', $userAccountId)
                    ->where('currency', '=', $reversal->currency)
                    ->sum('amount');

                $balanceAfter = $currentBalance - $originalDelta;

                if ($balanceAfter < 0) {
                    DB::table('ledger_transactions')->where('id', '=', $reversal->id)->update([
                        'status' => 'failed',
                        'meta' => json_encode(['reason' => 'insufficient_balance_to_reverse', 'balance_cents' => $currentBalance]),
                        'updated_at' => now(),
                    ]);
                    return;
                }
            }

            $entries = DB::table('ledger_entries')
                ->where('ledger_transaction_id', '=', $original->id)
                ->get(['account_id', 'amount', 'currency', 'description']);

            if ($entries->count() === 0) {
                DB::table('ledger_transactions')->where('id', '=', $reversal->id)->update([
                    'status' => 'failed',
                    'meta' => json_encode(['reason' => 'no_entries']),
                    'updated_at' => now(),
                ]);
                return;
            }

            $insert = [];
            foreach ($entries as $e) {
                $insert[] = [
                    'ledger_transaction_id' => $reversal->id,
                    'account_id' => $e->account_id,
                    'amount' => -((int) $e->amount),
                    'currency' => $e->currency,
                    'balance_after' => null,
                    'description' => ($e->description ? $e->description.' (reversao)' : 'reversao'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('ledger_entries')->insert($insert);

            DB::table('ledger_transactions')->where('id', '=', $reversal->id)->update([
                'status' => 'posted',
                'updated_at' => now(),
            ]);

            DB::table('ledger_transactions')->where('id', '=', $original->id)->update([
                'status' => 'reversed',
                'updated_at' => now(),
            ]);
        }, 3);
    }
}
