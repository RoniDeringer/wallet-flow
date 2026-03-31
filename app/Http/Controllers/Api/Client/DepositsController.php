<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreDepositRequest;
use App\Jobs\ProcessDepositTransaction;
use App\Models\Account;
use App\Models\LedgerTransaction;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class DepositsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user || $user->role !== 'client') {
            return response()->json(['message' => 'Acesso negado.'], Response::HTTP_FORBIDDEN);
        }

        $accountId = Account::query()
            ->where('type', '=', 'user')
            ->where('user_id', '=', $user->id)
            ->where('currency', '=', 'BRL')
            ->value('id');

        if (! $accountId) {
            return response()->json(['ok' => true, 'data' => []]);
        }

        $deposits = LedgerTransaction::query()
            ->where('type', '=', 'deposit')
            ->where('to_account_id', '=', $accountId)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['id', 'uuid', 'amount', 'currency', 'status', 'created_at']);

        return response()->json(['ok' => true, 'data' => $deposits]);
    }

    public function store(StoreDepositRequest $request)
    {
        $user = $request->user();

        if (! $user || $user->role !== 'client') {
            return response()->json(['message' => 'Acesso negado.'], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validated();

        $amountCents = Money::parseAmountToCents($validated['amount']);

        if ($amountCents <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['O valor deve ser maior que zero.'],
            ]);
        }

        $tx = DB::transaction(function () use ($user, $amountCents) {
            $userAccount = Account::query()->updateOrCreate(
                ['type' => 'user', 'user_id' => $user->id, 'currency' => 'BRL'],
                ['name' => $user->name.' (BRL)', 'key' => null]
            );

            $platformAccount = Account::query()->updateOrCreate(
                ['type' => 'system', 'key' => 'platform', 'currency' => 'BRL'],
                ['name' => 'Plataforma (BRL)', 'user_id' => null]
            );

            return LedgerTransaction::query()->create([
                'uuid' => (string) Str::uuid(),
                'type' => 'deposit',
                'status' => 'pending',
                'amount' => $amountCents,
                'currency' => 'BRL',
                'requested_by_user_id' => $user->id,
                'from_account_id' => $platformAccount->id,
                'to_account_id' => $userAccount->id,
                'description' => 'Depósito',
                'meta' => ['source' => 'manual'],
            ]);
        });

        ProcessDepositTransaction::dispatch($tx->id)
            ->onQueue(config('queue.connections.rabbitmq.queue', 'default'))
            ->afterCommit();

        return response()->json(['ok' => true, 'data' => $tx]);
    }
}