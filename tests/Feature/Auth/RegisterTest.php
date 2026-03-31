<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_client_user_and_account_and_returns_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Ana',
            'email' => 'ana@gmail.com',
            'username' => 'ana',
            'password' => '12345',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('ok', true)
            ->assertJsonStructure(['token', 'user' => ['id', 'email', 'username', 'role']]);

        $user = User::query()->where('email', '=', 'ana@gmail.com')->firstOrFail();
        $this->assertSame('client', $user->role);
        $this->assertTrue(Hash::check('12345', $user->password));

        $accountId = DB::table('accounts')
            ->where('type', '=', 'user')
            ->where('user_id', '=', $user->id)
            ->where('currency', '=', 'BRL')
            ->value('id');

        $this->assertNotNull($accountId);
    }
}

