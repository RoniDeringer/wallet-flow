<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class LedgerResetController extends Controller
{
    public function store(Request $request)
    {
        $admin = $request->user();

        if (! $admin || $admin->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized.'], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'confirm' => ['required', 'boolean', 'accepted'],
        ]);

        if (! $validated['confirm']) {
            throw ValidationException::withMessages([
                'confirm' => ['Confirmação obrigatória.'],
            ]);
        }

        $result = DB::transaction(function () {
            $entriesCount = (int) DB::table('ledger_entries')->count();
            $transactionsCount = (int) DB::table('ledger_transactions')->count();

            DB::table('ledger_entries')->delete();
            DB::table('ledger_transactions')->delete();

            return [
                'deleted_entries' => $entriesCount,
                'deleted_transactions' => $transactionsCount,
            ];
        }, 3);

        return response()->json([
            'ok' => true,
            'data' => $result,
        ]);
    }
}