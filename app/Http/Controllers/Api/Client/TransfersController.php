<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreTransferRequest;
use App\Jobs\ProcessTransferTransaction;
use App\Models\Account;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class TransfersController extends Controller
{
    public function store(StoreTransferRequest $request)
    {
        $sender = $request->user();

        if (! $sender) {
            return response()->json(['message' => 'Não autenticado.'], Response::HTTP_UNAUTHORIZED);
        }

        if ($sender->role !== 'client') {
            return response()->json(['message' => 'Apenas clientes podem transferir.'], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validated();

        $recipient = User::query()->where('email', '=', $validated['recipient_email'])->first();

        if (! $recipient || $recipient->role !== 'client') {
            throw ValidationException::withMessages([
                'recipient_email' => ['Destinatário não encontrado.'],
            ]);
        }

        if ($recipient->id === $sender->id) {
            throw ValidationException::withMessages([
                'recipient_email' => ['Você não pode transferir para si mesmo.'],
            ]);
        }

        $amountCents = Money::parseAmountToCents($validated['amount']);

        if ($amountCents <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['O valor deve ser maior que zero.'],
            ]);
        }

        $senderAccountId = Account::query()
            ->where('type', '=', 'user')
            ->where('user_id', '=', $sender->id)
            ->where('currency', '=', LedgerTransaction::CURRENCY_BRL)
            ->value('id');

        $senderBalance = 0;
        if ($senderAccountId) {
            $senderBalance = (int) LedgerEntry::query()
                ->where('account_id', '=', $senderAccountId)
                ->where('currency', '=', LedgerTransaction::CURRENCY_BRL)
                ->sum('amount');
        }

        if ($senderBalance < $amountCents) {
            throw ValidationException::withMessages([
                'amount' => ['Saldo insuficiente.'],
            ]);
        }

        $idempotencyKey = $request->header('Idempotency-Key');
        if ($idempotencyKey && strlen($idempotencyKey) > 120) {
            throw ValidationException::withMessages([
                'recipient_email' => ['Idempotency-Key muito grande.'],
            ]);
        }

        if ($idempotencyKey) {
            $existing = LedgerTransaction::query()->where('idempotency_key', '=', $idempotencyKey)->first();
            if ($existing) {
                return response()->json(['ok' => true, 'data' => $existing]);
            }
        }

        $tx = DB::transaction(function () use ($sender, $recipient, $amountCents, $idempotencyKey) {
            $senderAccount = Account::query()->updateOrCreate(
                ['type' => 'user', 'user_id' => $sender->id, 'currency' => LedgerTransaction::CURRENCY_BRL],
                ['name' => $sender->name.' ('.LedgerTransaction::CURRENCY_BRL.')', 'key' => null]
            );

            $recipientAccount = Account::query()->updateOrCreate(
                ['type' => 'user', 'user_id' => $recipient->id, 'currency' => LedgerTransaction::CURRENCY_BRL],
                ['name' => $recipient->name.' ('.LedgerTransaction::CURRENCY_BRL.')', 'key' => null]
            );

            return LedgerTransaction::query()->create([
                'uuid' => (string) Str::uuid(),
                'type' => LedgerTransaction::TYPE_TRANSFER,
                'status' => LedgerTransaction::STATUS_PENDING,
                'amount' => $amountCents,
                'currency' => LedgerTransaction::CURRENCY_BRL,
                'requested_by_user_id' => $sender->id,
                'from_account_id' => $senderAccount->id,
                'to_account_id' => $recipientAccount->id,
                'idempotency_key' => $idempotencyKey,
                'description' => LedgerTransaction::DESCRIPTION_TRANSFER,
                'meta' => ['recipient_user_id' => $recipient->id],
            ]);
        });

        ProcessTransferTransaction::dispatch($tx->id)
            ->onQueue(config('queue.connections.rabbitmq.queue', 'default'))
            ->afterCommit();

        return response()->json([
            'ok' => true,
            'data' => $tx,
        ]);
    }
}

