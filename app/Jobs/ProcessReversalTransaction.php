<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
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
            /** @var LedgerTransaction|null $reversal */
            $reversal = LedgerTransaction::query()
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
                LedgerTransaction::query()->where('id', '=', $reversal->id)->update([
                    'status' => 'failed',
                    'updated_at' => now(),
                ]);

                return;
            }

            /** @var LedgerTransaction|null $original */
            $original = LedgerTransaction::query()
                ->where('id', '=', $reversal->reversal_of_id)
                ->lockForUpdate()
                ->first();

            if (! $original || $original->status !== 'posted') {
                LedgerTransaction::query()->where('id', '=', $reversal->id)->update([
                    'status' => 'failed',
                    'updated_at' => now(),
                ]);

                return;
            }

            $alreadyHasEntries = LedgerEntry::query()
                ->where('ledger_transaction_id', '=', $reversal->id)
                ->exists();

            if ($alreadyHasEntries) {
                LedgerTransaction::query()->where('id', '=', $reversal->id)->update([
                    'status' => 'posted',
                    'updated_at' => now(),
                ]);

                return;
            }

            $alreadyReversed = LedgerTransaction::query()
                ->where('type', '=', 'reversal')
                ->where('reversal_of_id', '=', $original->id)
                ->where('id', '!=', $reversal->id)
                ->exists();

            if ($alreadyReversed) {
                LedgerTransaction::query()->where('id', '=', $reversal->id)->update([
                    'status' => 'failed',
                    'meta' => ['reason' => 'already_reversed'],
                    'updated_at' => now(),
                ]);

                return;
            }

            $userAccountId = null;
            if ($reversal->requested_by_user_id) {
                $userAccountId = Account::query()
                    ->where('type', '=', 'user')
                    ->where('user_id', '=', $reversal->requested_by_user_id)
                    ->where('currency', '=', $reversal->currency)
                    ->value('id');
            }

            if ($userAccountId) {
                $hasPendingTransfer = LedgerTransaction::query()
                    ->where('type', '=', 'transfer')
                    ->where('status', '=', 'pending')
                    ->where(function ($q) use ($userAccountId) {
                        $q->where('from_account_id', '=', $userAccountId)
                            ->orWhere('to_account_id', '=', $userAccountId);
                    })
                    ->exists();

                if ($hasPendingTransfer) {
                    LedgerTransaction::query()->where('id', '=', $reversal->id)->update([
                        'status' => 'failed',
                        'meta' => ['reason' => 'pending_transfer'],
                        'updated_at' => now(),
                    ]);

                    return;
                }

                $currentBalance = (int) LedgerEntry::query()
                    ->where('account_id', '=', $userAccountId)
                    ->where('currency', '=', $reversal->currency)
                    ->sum('amount');

                $originalDelta = (int) LedgerEntry::query()
                    ->where('ledger_transaction_id', '=', $original->id)
                    ->where('account_id', '=', $userAccountId)
                    ->where('currency', '=', $reversal->currency)
                    ->sum('amount');

                $balanceAfter = $currentBalance - $originalDelta;

                if ($balanceAfter < 0) {
                    LedgerTransaction::query()->where('id', '=', $reversal->id)->update([
                        'status' => 'failed',
                        'meta' => ['reason' => 'insufficient_balance_to_reverse', 'balance_cents' => $currentBalance],
                        'updated_at' => now(),
                    ]);

                    return;
                }
            }

            $entries = LedgerEntry::query()
                ->where('ledger_transaction_id', '=', $original->id)
                ->get(['account_id', 'amount', 'currency', 'description']);

            if ($entries->count() === 0) {
                LedgerTransaction::query()->where('id', '=', $reversal->id)->update([
                    'status' => 'failed',
                    'meta' => ['reason' => 'no_entries'],
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
                    'description' => ($e->description ? $e->description.' (reversão)' : 'reversão'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            LedgerEntry::query()->insert($insert);

            LedgerTransaction::query()->where('id', '=', $reversal->id)->update([
                'status' => 'posted',
                'updated_at' => now(),
            ]);

            LedgerTransaction::query()->where('id', '=', $original->id)->update([
                'status' => 'reversed',
                'updated_at' => now(),
            ]);
        }, 3);
    }
}