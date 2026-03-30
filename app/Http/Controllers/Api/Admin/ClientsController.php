<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ClientsController extends Controller
{
    public function index(Request $request)
    {
        $adminUserId = $request->header('X-User-Id');

        if (! $adminUserId) {
            return response()->json(['message' => 'Missing X-User-Id header.'], Response::HTTP_UNAUTHORIZED);
        }

        $admin = User::query()->find($adminUserId);

        if (! $admin || $admin->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized.'], Response::HTTP_FORBIDDEN);
        }

        $clients = DB::table('users')
            ->where('users.role', '=', 'client')
            ->leftJoin('accounts', function ($join) {
                $join->on('accounts.user_id', '=', 'users.id')
                    ->where('accounts.type', '=', 'user')
                    ->where('accounts.currency', '=', 'BRL');
            })
            ->leftJoin('ledger_entries', function ($join) {
                $join->on('ledger_entries.account_id', '=', 'accounts.id')
                    ->where('ledger_entries.currency', '=', 'BRL');
            })
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderBy('users.name')
            ->select([
                'users.id',
                'users.name',
                'users.email',
                DB::raw('COALESCE(SUM(ledger_entries.amount), 0) as balance_cents'),
            ])
            ->get();

        return response()->json([
            'ok' => true,
            'data' => $clients,
        ]);
    }
}

