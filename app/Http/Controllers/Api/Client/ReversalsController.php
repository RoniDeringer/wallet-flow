<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreReversalRequest;
use App\Jobs\ProcessReversalTransaction;
use App\Models\Account;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ReversalsController extends Controller
{
    public function store(StoreReversalRequest $request, int $transactionId)
    {
        $user = $request->user();

        if (! $user || $user->role !== 'client') {
            return response()->json(['message' => 'Acesso negado.'], Response::HTTP_FORBIDDEN);
        }

        $accountId = Account::query()
            ->where('type', '=', 'user')
            ->where('user_id', '=', $user->id)
            ->where('currency', '=', LedgerTransaction::CURRENCY_BRL)
            ->value('id');

        if (! $accountId) {
            throw ValidationException::withMessages([
                'transactionId' => ['Conta do usuário não encontrada.'],
            ]);
        }

        /** @var LedgerTransaction|null $tx */
        $tx = LedgerTransaction::query()->where('id', '=', $transactionId)->first();

        if (! $tx) {
            throw ValidationException::withMessages([
                'transactionId' => ['Transação não encontrada.'],
            ]);
        }

        if ($tx->type === LedgerTransaction::TYPE_REVERSAL) {
            throw ValidationException::withMessages([
                'transactionId' => ['Esta transação já é uma reversão.'],
            ]);
        }

        if ($tx->status !== LedgerTransaction::STATUS_POSTED) {
            throw ValidationException::withMessages([
                'transactionId' => ['A transação precisa estar POSTED para ser revertida.'],
            ]);
        }

        $belongsToUser = ((int) $tx->from_account_id === (int) $accountId) || ((int) $tx->to_account_id === (int) $accountId);

        if (! $belongsToUser) {
            return response()->json(['message' => 'Acesso negado.'], Response::HTTP_FORBIDDEN);
        }

        $alreadyReversed = LedgerTransaction::query()
            ->where('type', '=', LedgerTransaction::TYPE_REVERSAL)
            ->where('reversal_of_id', '=', $tx->id)
            ->exists();

        if ($alreadyReversed) {
            throw ValidationException::withMessages([
                'transactionId' => ['Esta transação já foi revertida.'],
            ]);
        }

        $hasPendingTransfer = LedgerTransaction::query()
            ->where('type', '=', LedgerTransaction::TYPE_TRANSFER)
            ->where('status', '=', LedgerTransaction::STATUS_PENDING)
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

        $currentBalance = (int) LedgerEntry::query()
            ->where('account_id', '=', $accountId)
            ->where('currency', '=', $tx->currency)
            ->sum('amount');

        $originalDeltaForUser = (int) LedgerEntry::query()
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
            return LedgerTransaction::query()->create([
                'uuid' => (string) Str::uuid(),
                'type' => LedgerTransaction::TYPE_REVERSAL,
                'status' => LedgerTransaction::STATUS_PENDING,
                'amount' => $tx->amount,
                'currency' => $tx->currency,
                'requested_by_user_id' => $user->id,
                'from_account_id' => $tx->to_account_id,
                'to_account_id' => $tx->from_account_id,
                'reversal_of_id' => $tx->id,
                'description' => LedgerTransaction::DESCRIPTION_REVERSAL,
                'meta' => ['reversal_of_uuid' => $tx->uuid],
            ]);
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

