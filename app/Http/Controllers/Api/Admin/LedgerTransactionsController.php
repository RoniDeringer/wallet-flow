<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class LedgerTransactionsController extends Controller
{
    public function index(Request $request)
    {
        $admin = $request->user();

        if (! $admin || $admin->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized.'], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'type' => ['nullable', 'string', 'in:deposit,transfer,reversal'],
            'status' => ['nullable', 'string', 'in:pending,posted,failed,reversed'],
            'client_id' => ['nullable', 'integer', 'min:1'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $page = (int) ($validated['page'] ?? 1);
        $perPage = (int) ($validated['per_page'] ?? 25);
        $offset = ($page - 1) * $perPage;

        $query = DB::table('ledger_transactions as lt')
            ->leftJoin('accounts as fa', 'fa.id', '=', 'lt.from_account_id')
            ->leftJoin('users as fu', 'fu.id', '=', 'fa.user_id')
            ->leftJoin('accounts as ta', 'ta.id', '=', 'lt.to_account_id')
            ->leftJoin('users as tu', 'tu.id', '=', 'ta.user_id')
            ->select([
                'lt.id',
                'lt.uuid',
                'lt.type',
                'lt.status',
                'lt.amount',
                'lt.currency',
                'lt.created_at',
                'lt.reversal_of_id',
                'fu.id as from_user_id',
                'fu.name as from_user_name',
                'fu.email as from_user_email',
                'tu.id as to_user_id',
                'tu.name as to_user_name',
                'tu.email as to_user_email',
            ]);

        if (! empty($validated['type'])) {
            $query->where('lt.type', '=', $validated['type']);
        }

        if (! empty($validated['status'])) {
            $query->where('lt.status', '=', $validated['status']);
        }

        if (! empty($validated['client_id'])) {
            $clientId = (int) $validated['client_id'];
            $query->where(function ($q) use ($clientId) {
                $q->where('fa.user_id', '=', $clientId)
                    ->orWhere('ta.user_id', '=', $clientId);
            });
        }

        if (! empty($validated['date_from'])) {
            $query->where('lt.created_at', '>=', $validated['date_from']);
        }

        if (! empty($validated['date_to'])) {
            $query->where('lt.created_at', '<=', $validated['date_to']);
        }

        $total = (clone $query)->count();

        $data = $query
            ->orderByDesc('lt.created_at')
            ->offset($offset)
            ->limit($perPage)
            ->get();

        return response()->json([
            'ok' => true,
            'data' => $data,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
            ],
        ]);
    }
}