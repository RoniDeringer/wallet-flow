<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TransactionsController extends Controller
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
            return response()->json(['ok' => true, 'data' => []]);
        }

        $rows = DB::table('ledger_transactions')
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
