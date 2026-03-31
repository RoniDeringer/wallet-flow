<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreateClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_client_with_default_password(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@admin.com',
            'username' => 'admin',
            'role' => 'admin',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/clients', [
            'name' => 'Daniel',
            'email' => 'daniel@daniel.com',
            'username' => 'daniel',
        ]);

        $response->assertStatus(201)->assertJsonPath('ok', true);

        $created = User::query()->where('email', '=', 'daniel@daniel.com')->firstOrFail();
        $this->assertSame('client', $created->role);
        $this->assertTrue(Hash::check('12345', $created->password));

        $accountId = DB::table('accounts')
            ->where('type', '=', 'user')
            ->where('user_id', '=', $created->id)
            ->where('currency', '=', 'BRL')
            ->value('id');

        $this->assertNotNull($accountId);
    }

    public function test_client_cannot_create_client(): void
    {
        $client = User::factory()->create([
            'email' => 'ana@gmail.com',
            'username' => 'ana',
            'role' => 'client',
        ]);

        Sanctum::actingAs($client);

        $response = $this->postJson('/api/admin/clients', [
            'name' => 'X',
            'email' => 'x@gmail.com',
            'username' => 'x',
        ]);

        $response->assertStatus(403);
    }
}

