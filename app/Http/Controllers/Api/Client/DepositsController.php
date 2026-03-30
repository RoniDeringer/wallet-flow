<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class DepositsController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->header('X-User-Id');

        if (! $userId) {
            return response()->json(['message' => 'Missing X-User-Id header.'], Response::HTTP_UNAUTHORIZED);
        }

        $user = User::query()->find($userId);

        if (! $user) {
            return response()->json(['message' => 'Unauthorized.'], Response::HTTP_FORBIDDEN);
        }

        $accountId = DB::table('accounts')
            ->where('type', '=', 'user')
            ->where('user_id', '=', $user->id)
            ->where('currency', '=', 'BRL')
            ->value('id');

        if (! $accountId) {
            return response()->json([
                'ok' => true,
                'data' => [],
            ]);
        }

        $deposits = DB::table('ledger_transactions')
            ->where('type', '=', 'deposit')
            ->where('to_account_id', '=', $accountId)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['id', 'uuid', 'amount', 'currency', 'status', 'created_at']);

        return response()->json([
            'ok' => true,
            'data' => $deposits,
        ]);
    }

    public function store(Request $request)
    {
        $userId = $request->header('X-User-Id');

        if (! $userId) {
            return response()->json(['message' => 'Missing X-User-Id header.'], Response::HTTP_UNAUTHORIZED);
        }

        $user = User::query()->find($userId);

        if (! $user) {
            return response()->json(['message' => 'Unauthorized.'], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'amount' => ['required'],
        ]);

        $amountCents = $this->parseAmountToCents($validated['amount']);

        if ($amountCents <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['O valor deve ser maior que zero.'],
            ]);
        }

        $tx = DB::transaction(function () use ($user, $amountCents) {
            $userAccountId = DB::table('accounts')->updateOrInsert(
                ['type' => 'user', 'user_id' => $user->id, 'currency' => 'BRL'],
                ['name' => $user->name.' (BRL)', 'key' => null, 'updated_at' => now(), 'created_at' => now()]
            )
                ? DB::table('accounts')
                    ->where('type', '=', 'user')
                    ->where('user_id', '=', $user->id)
                    ->where('currency', '=', 'BRL')
                    ->value('id')
                : null;

            $platformAccountId = DB::table('accounts')->updateOrInsert(
                ['type' => 'system', 'key' => 'platform', 'currency' => 'BRL'],
                ['name' => 'Plataforma (BRL)', 'user_id' => null, 'updated_at' => now(), 'created_at' => now()]
            )
                ? DB::table('accounts')
                    ->where('type', '=', 'system')
                    ->where('key', '=', 'platform')
                    ->where('currency', '=', 'BRL')
                    ->value('id')
                : null;

            if (! $userAccountId || ! $platformAccountId) {
                throw ValidationException::withMessages([
                    'amount' => ['Não foi possível preparar as contas para o depósito.'],
                ]);
            }

            $uuid = (string) Str::uuid();

            $transactionId = DB::table('ledger_transactions')->insertGetId([
                'uuid' => $uuid,
                'type' => 'deposit',
                'status' => 'posted',
                'amount' => $amountCents,
                'currency' => 'BRL',
                'requested_by_user_id' => $user->id,
                'from_account_id' => $platformAccountId,
                'to_account_id' => $userAccountId,
                'description' => 'Depósito',
                'meta' => json_encode(['source' => 'manual']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('ledger_entries')->insert([
                [
                    'ledger_transaction_id' => $transactionId,
                    'account_id' => $userAccountId,
                    'amount' => $amountCents,
                    'currency' => 'BRL',
                    'balance_after' => null,
                    'description' => 'Depósito',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'ledger_transaction_id' => $transactionId,
                    'account_id' => $platformAccountId,
                    'amount' => -$amountCents,
                    'currency' => 'BRL',
                    'balance_after' => null,
                    'description' => 'Depósito (contrapartida)',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            return DB::table('ledger_transactions')->where('id', '=', $transactionId)->first();
        });

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

