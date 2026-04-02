<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\LedgerTransaction;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TransactionsController extends Controller
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

        $rows = LedgerTransaction::query()
            ->where(function ($q) use ($accountId) {
                $q->where('from_account_id', '=', $accountId)
                    ->orWhere('to_account_id', '=', $accountId);
            })
            ->orderByDesc('created_at')
            ->limit(50)
            ->select(['id', 'uuid', 'type', 'status', 'currency', 'created_at'])
            ->selectRaw('CASE WHEN to_account_id = ? THEN amount ELSE -amount END as amount_signed', [$accountId])
            ->get();

        return response()->json([
            'ok' => true,
            'data' => $rows,
        ]);
    }
}
