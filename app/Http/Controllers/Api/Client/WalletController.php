<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class WalletController extends Controller
{
    public function show(Request $request)
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

        $balanceCents = 0;

        if ($accountId) {
            $balanceCents = (int) DB::table('ledger_entries')
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
