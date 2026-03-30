<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class OverviewController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        if (! $user || $user->role !== 'client') {
            return response()->json(['message' => 'Forbidden.'], Response::HTTP_FORBIDDEN);
        }

        $accountId = DB::table('accounts')
            ->where('type', '=', 'user')
            ->where('user_id', '=', $user->id)
            ->where('currency', '=', 'BRL')
            ->value('id');

        if (! $accountId) {
            return response()->json([
                'ok' => true,
                'data' => [
                    'currency' => 'BRL',
                    'balance_cents' => 0,
                    'deposits_total_cents' => 0,
                    'transfers_received_total_cents' => 0,
                    'transfers_sent_total_cents' => 0,
                    'pending_transfers_count' => 0,
                ],
            ]);
        }

        $balanceCents = (int) DB::table('ledger_entries')
            ->where('account_id', '=', $accountId)
            ->where('currency', '=', 'BRL')
            ->sum('amount');

        $depositsTotalCents = (int) DB::table('ledger_transactions')
            ->where('type', '=', 'deposit')
            ->where('status', '=', 'posted')
            ->where('to_account_id', '=', $accountId)
            ->sum('amount');

        $receivedTotalCents = (int) DB::table('ledger_transactions')
            ->where('type', '=', 'transfer')
            ->where('status', '=', 'posted')
            ->where('to_account_id', '=', $accountId)
            ->sum('amount');

        $sentTotalCents = (int) DB::table('ledger_transactions')
            ->where('type', '=', 'transfer')
            ->where('status', '=', 'posted')
            ->where('from_account_id', '=', $accountId)
            ->sum('amount');

        $pendingTransfersCount = (int) DB::table('ledger_transactions')
            ->where('type', '=', 'transfer')
            ->where('status', '=', 'pending')
            ->where(function ($q) use ($accountId) {
                $q->where('from_account_id', '=', $accountId)
                    ->orWhere('to_account_id', '=', $accountId);
            })
            ->count();

        return response()->json([
            'ok' => true,
            'data' => [
                'currency' => 'BRL',
                'balance_cents' => $balanceCents,
                'deposits_total_cents' => $depositsTotalCents,
                'transfers_received_total_cents' => $receivedTotalCents,
                'transfers_sent_total_cents' => $sentTotalCents,
                'pending_transfers_count' => $pendingTransfersCount,
            ],
        ]);
    }
}