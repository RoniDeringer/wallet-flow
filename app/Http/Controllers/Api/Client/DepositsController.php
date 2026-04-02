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
            ->where('currency', '=', LedgerTransaction::CURRENCY_BRL)
            ->value('id');

        if (! $accountId) {
            return response()->json(['ok' => true, 'data' => []]);
        }

        $deposits = LedgerTransaction::query()
            ->where('type', '=', LedgerTransaction::TYPE_DEPOSIT)
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
                ['type' => 'user', 'user_id' => $user->id, 'currency' => LedgerTransaction::CURRENCY_BRL],
                ['name' => $user->name . ' (' . LedgerTransaction::CURRENCY_BRL . ')', 'key' => null]
            );

            $platformAccount = Account::query()->updateOrCreate(
                ['type' => 'system', 'key' => 'platform', 'currency' => LedgerTransaction::CURRENCY_BRL],
                ['name' => 'Plataforma (' . LedgerTransaction::CURRENCY_BRL . ')', 'user_id' => null]
            );

            return LedgerTransaction::query()->create([
                'uuid' => (string) Str::uuid(),
                'type' => LedgerTransaction::TYPE_DEPOSIT,
                'status' => LedgerTransaction::STATUS_PENDING,
                'amount' => $amountCents,
                'currency' => LedgerTransaction::CURRENCY_BRL,
                'requested_by_user_id' => $user->id,
                'from_account_id' => $platformAccount->id,
                'to_account_id' => $userAccount->id,
                'description' => LedgerTransaction::DESCRIPTION_DEPOSIT,
                'meta' => ['source' => LedgerTransaction::META_SOURCE_MANUAL],
            ]);
        });

        ProcessDepositTransaction::dispatch($tx->id)
            ->onQueue(config('queue.connections.rabbitmq.queue', 'default'))
            ->afterCommit();

        return response()->json(['ok' => true, 'data' => $tx]);
    }
}
