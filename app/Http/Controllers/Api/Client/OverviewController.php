<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OverviewController extends Controller
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

        $balanceCents = (int) LedgerEntry::query()
            ->where('account_id', '=', $accountId)
            ->where('currency', '=', 'BRL')
            ->sum('amount');

        $depositsTotalCents = (int) LedgerTransaction::query()
            ->where('type', '=', 'deposit')
            ->where('status', '=', 'posted')
            ->where('to_account_id', '=', $accountId)
            ->sum('amount');

        $receivedTotalCents = (int) LedgerTransaction::query()
            ->where('type', '=', 'transfer')
            ->where('status', '=', 'posted')
            ->where('to_account_id', '=', $accountId)
            ->sum('amount');

        $sentTotalCents = (int) LedgerTransaction::query()
            ->where('type', '=', 'transfer')
            ->where('status', '=', 'posted')
            ->where('from_account_id', '=', $accountId)
            ->sum('amount');

        $pendingTransfersCount = (int) LedgerTransaction::query()
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