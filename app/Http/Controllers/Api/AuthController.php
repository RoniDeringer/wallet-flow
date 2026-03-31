<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Account;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['E-mail ou senha inválidos.'],
            ]);
        }

        $token = $user->createToken('web')->plainTextToken;

        return response()->json([
            'ok' => true,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'role' => $user->role,
            ],
        ]);
    }

    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        try {
            $user = DB::transaction(function () use ($validated) {
                $created = User::query()->create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'username' => $validated['username'],
                    'role' => 'client',
                    'password' => $validated['password'],
                ]);

                Account::query()->create([
                    'type' => 'user',
                    'user_id' => $created->id,
                    'key' => null,
                    'name' => $created->name,
                    'currency' => 'BRL',
                ]);

                return $created;
            });
        } catch (Throwable) {
            return response()->json(['message' => 'Não foi possível criar o cadastro.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $token = $user->createToken('web')->plainTextToken;

        return response()->json([
            'ok' => true,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'role' => $user->role,
            ],
        ], Response::HTTP_CREATED);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Não autenticado.'], Response::HTTP_UNAUTHORIZED);
        }

        $user->currentAccessToken()?->delete();

        return response()->json(['ok' => true]);
    }
}