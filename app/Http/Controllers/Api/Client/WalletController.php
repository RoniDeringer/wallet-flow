<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\LedgerEntry;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WalletController extends Controller
{
    public function show(Request $request)
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

        $balanceCents = 0;
        if ($accountId) {
            $balanceCents = (int) LedgerEntry::query()
                ->where('account_id', '=', $accountId)
                ->where('currency', '=', 'BRL')
                ->sum('amount');
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'currency' => 'BRL',
                'balance_cents' => $balanceCents,
            ],
        ]);
    }
}