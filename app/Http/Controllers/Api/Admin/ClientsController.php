<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ClientsController extends Controller
{
    private function requireAdmin(Request $request): User|Response
    {
        $admin = $request->user();

        if (! $admin || $admin->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized.'], Response::HTTP_FORBIDDEN);
        }

        return $admin;
    }

    public function index(Request $request)
    {
        $admin = $this->requireAdmin($request);
        if ($admin instanceof Response) {
            return $admin;
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

    public function store(Request $request)
    {
        $admin = $this->requireAdmin($request);
        if ($admin instanceof Response) {
            return $admin;
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'username' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_]+$/', 'unique:users,username'],
        ], [
            'username.regex' => 'O username deve conter apenas letras, números e underscore (_).',
        ]);

        try {
            $user = DB::transaction(function () use ($validated) {
                $created = User::query()->create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'username' => $validated['username'],
                    'role' => 'client',
                    'password' => '12345',
                ]);

                DB::table('accounts')->insert([
                    'type' => 'user',
                    'user_id' => $created->id,
                    'key' => null,
                    'name' => $created->name,
                    'currency' => 'BRL',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return $created;
            });
        } catch (Throwable) {
            return response()->json(['message' => 'Não foi possível cadastrar o cliente.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'role' => $user->role,
            ],
        ], Response::HTTP_CREATED);
    }
}