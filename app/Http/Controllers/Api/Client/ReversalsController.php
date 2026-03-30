<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessReversalTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ReversalsController extends Controller
{
    public function store(Request $request, int $transactionId)
    {
        $user = $request->user();

        if (! $user || $user->role !== 'client') {
            return response()->json(['message' => 'Forbidden.'], Response::HTTP_FORBIDDEN);
        }

        $accountId = DB::table('accounts')
            ->where('type', '=', 'user')
            ->where('user_id', '=', $user->id)
            ->where('currency', '=', 'BRL')
            ->value('id');

        if (! $accountId) {
            throw ValidationException::withMessages([
                'transactionId' => ['Conta do usuário não encontrada.'],
            ]);
        }

        $tx = DB::table('ledger_transactions')->where('id', '=', $transactionId)->first();

        if (! $tx) {
            throw ValidationException::withMessages([
                'transactionId' => ['Transação não encontrada.'],
            ]);
        }

        if ($tx->type === 'reversal') {
            throw ValidationException::withMessages([
                'transactionId' => ['Esta transação já é uma reversão.'],
            ]);
        }

        if ($tx->status !== 'posted') {
            throw ValidationException::withMessages([
                'transactionId' => ['A transação precisa estar POSTED para ser revertida.'],
            ]);
        }

        $belongsToUser = ((int) $tx->from_account_id === (int) $accountId) || ((int) $tx->to_account_id === (int) $accountId);

        if (! $belongsToUser) {
            return response()->json(['message' => 'Forbidden.'], Response::HTTP_FORBIDDEN);
        }

        $alreadyReversed = DB::table('ledger_transactions')
            ->where('type', '=', 'reversal')
            ->where('reversal_of_id', '=', $tx->id)
            ->exists();

        if ($alreadyReversed) {
            throw ValidationException::withMessages([
                'transactionId' => ['Esta transação já foi revertida.'],
            ]);
        }

        $hasPendingTransfer = DB::table('ledger_transactions')
            ->where('type', '=', 'transfer')
            ->where('status', '=', 'pending')
            ->where(function ($q) use ($accountId) {
                $q->where('from_account_id', '=', $accountId)
                    ->orWhere('to_account_id', '=', $accountId);
            })
            ->exists();

        if ($hasPendingTransfer) {
            throw ValidationException::withMessages([
                'transactionId' => ['Não é possível fazer rollback com transferência pendente.'],
            ]);
        }

        $currentBalance = (int) DB::table('ledger_entries')
            ->where('account_id', '=', $accountId)
            ->where('currency', '=', $tx->currency)
            ->sum('amount');

        $originalDeltaForUser = (int) DB::table('ledger_entries')
            ->where('ledger_transaction_id', '=', $tx->id)
            ->where('account_id', '=', $accountId)
            ->where('currency', '=', $tx->currency)
            ->sum('amount');

        $balanceAfterReversal = $currentBalance - $originalDeltaForUser;

        if ($balanceAfterReversal < 0) {
            throw ValidationException::withMessages([
                'transactionId' => ['Não é possível fazer rollback: saldo insuficiente.'],
            ]);
        }

        $reversalTx = DB::transaction(function () use ($user, $tx) {
            $reversalId = DB::table('ledger_transactions')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'type' => 'reversal',
                'status' => 'pending',
                'amount' => $tx->amount,
                'currency' => $tx->currency,
                'requested_by_user_id' => $user->id,
                'from_account_id' => $tx->to_account_id,
                'to_account_id' => $tx->from_account_id,
                'reversal_of_id' => $tx->id,
                'description' => 'Reversão',
                'meta' => json_encode(['reversal_of_uuid' => $tx->uuid]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return DB::table('ledger_transactions')->where('id', '=', $reversalId)->first();
        });

        ProcessReversalTransaction::dispatch($reversalTx->id)
            ->onQueue(config('queue.connections.rabbitmq.queue', 'default'))
            ->afterCommit();

        return response()->json([
            'ok' => true,
            'data' => $reversalTx,
        ]);
    }
}