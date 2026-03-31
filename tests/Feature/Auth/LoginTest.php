<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_token_and_user(): void
    {
        $user = User::factory()->create([
            'email' => 'ana@gmail.com',
            'username' => 'ana',
            'role' => 'client',
            'password' => Hash::make('12345'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'ana@gmail.com',
            'password' => '12345',
        ]);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonStructure([
                'ok',
                'token',
                'user' => ['id', 'name', 'email', 'username', 'role'],
            ]);

        $this->assertNotEmpty($response->json('token'));
        $this->assertSame($user->email, $response->json('user.email'));
    }

    public function test_login_with_wrong_password_returns_422(): void
    {
        User::factory()->create([
            'email' => 'admin@admin.com',
            'username' => 'admin',
            'role' => 'admin',
            'password' => Hash::make('12345'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@admin.com',
            'password' => 'wrong',
        ]);

        $response->assertStatus(422);
    }
}

