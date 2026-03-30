<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

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

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'username' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_]+$/', 'unique:users,username'],
            'password' => ['required', 'string', 'min:5'],
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
                    'password' => $validated['password'],
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
            return response()->json(['message' => 'Unauthenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        $user->currentAccessToken()?->delete();

        return response()->json(['ok' => true]);
    }
}