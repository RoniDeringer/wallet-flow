<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LedgerResetRequest;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class LedgerResetController extends Controller
{
    public function store(LedgerResetRequest $request)
    {
        $admin = $request->user();

        if (! $admin || $admin->role !== 'admin') {
            return response()->json(['message' => 'Não autorizado.'], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validated();

        if (! ($validated['confirm'] ?? false)) {
            throw ValidationException::withMessages([
                'confirm' => ['Confirmação obrigatória.'],
            ]);
        }

        $result = DB::transaction(function () {
            $entriesCount = (int) LedgerEntry::query()->count();
            $transactionsCount = (int) LedgerTransaction::query()->count();

            LedgerEntry::query()->delete();
            LedgerTransaction::query()->delete();

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