<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessTransferTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class TransfersController extends Controller
{
    public function store(Request $request)
    {
        $sender = $request->user();

        if (! $sender) {
            return response()->json(['message' => 'Unauthenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        if ($sender->role !== 'client') {
            return response()->json(['message' => 'Only clients can transfer.'], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'recipient_email' => ['required', 'email'],
            'amount' => ['required'],
        ]);

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

        $amountCents = $this->parseAmountToCents($validated['amount']);

        if ($amountCents <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['O valor deve ser maior que zero.'],
            ]);
        }

        $senderAccountId = DB::table('accounts')
            ->where('type', '=', 'user')
            ->where('user_id', '=', $sender->id)
            ->where('currency', '=', 'BRL')
            ->value('id');

        $senderBalance = 0;
        if ($senderAccountId) {
            $senderBalance = (int) DB::table('ledger_entries')
                ->where('account_id', '=', $senderAccountId)
                ->where('currency', '=', 'BRL')
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
            $existing = DB::table('ledger_transactions')->where('idempotency_key', '=', $idempotencyKey)->first();
            if ($existing) {
                return response()->json(['ok' => true, 'data' => $existing]);
            }
        }

        $tx = DB::transaction(function () use ($sender, $recipient, $amountCents, $idempotencyKey) {
            DB::table('accounts')->updateOrInsert(
                ['type' => 'user', 'user_id' => $sender->id, 'currency' => 'BRL'],
                ['name' => $sender->name.' (BRL)', 'key' => null, 'updated_at' => now(), 'created_at' => now()]
            );

            DB::table('accounts')->updateOrInsert(
                ['type' => 'user', 'user_id' => $recipient->id, 'currency' => 'BRL'],
                ['name' => $recipient->name.' (BRL)', 'key' => null, 'updated_at' => now(), 'created_at' => now()]
            );

            $fromAccountId = DB::table('accounts')
                ->where('type', '=', 'user')
                ->where('user_id', '=', $sender->id)
                ->where('currency', '=', 'BRL')
                ->value('id');

            $toAccountId = DB::table('accounts')
                ->where('type', '=', 'user')
                ->where('user_id', '=', $recipient->id)
                ->where('currency', '=', 'BRL')
                ->value('id');

            if (! $fromAccountId || ! $toAccountId) {
                throw ValidationException::withMessages([
                    'amount' => ['Não foi possível preparar as contas para a transferência.'],
                ]);
            }

            $transactionId = DB::table('ledger_transactions')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'type' => 'transfer',
                'status' => 'pending',
                'amount' => $amountCents,
                'currency' => 'BRL',
                'requested_by_user_id' => $sender->id,
                'from_account_id' => $fromAccountId,
                'to_account_id' => $toAccountId,
                'idempotency_key' => $idempotencyKey,
                'description' => 'Transferência',
                'meta' => json_encode(['recipient_user_id' => $recipient->id]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return DB::table('ledger_transactions')->where('id', '=', $transactionId)->first();
        });

        ProcessTransferTransaction::dispatch($tx->id)
            ->onQueue(config('queue.connections.rabbitmq.queue', 'default'))
            ->afterCommit();

        return response()->json([
            'ok' => true,
            'data' => $tx,
        ]);
    }

    private function parseAmountToCents(mixed $raw): int
    {
        $value = trim((string) $raw);
        $value = str_replace(['R$', ' '], '', $value);
        $value = str_replace(',', '.', $value);

        if (! preg_match('/^\d+(\.\d{1,2})?$/', $value)) {
            throw ValidationException::withMessages([
                'amount' => ['Informe um valor válido (ex: 10.00).'],
            ]);
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '0');
        $fraction = str_pad($fraction, 2, '0');

        return ((int) $whole * 100) + (int) substr($fraction, 0, 2);
    }
}