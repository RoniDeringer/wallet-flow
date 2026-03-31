<?php

namespace Tests\Feature\Client;

use App\Jobs\ProcessDepositTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DepositRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_request_deposit_and_job_is_dispatched(): void
    {
        Queue::fake();

        $client = User::factory()->create([
            'email' => 'ana@gmail.com',
            'username' => 'ana',
            'role' => 'client',
        ]);

        Sanctum::actingAs($client);

        $response = $this->postJson('/api/me/deposits', [
            'amount' => '10.00',
        ]);

        $response->assertOk()->assertJsonPath('ok', true);

        $txId = $response->json('data.id');
        $this->assertNotEmpty($txId);

        $tx = DB::table('ledger_transactions')->where('id', '=', $txId)->first();
        $this->assertSame('deposit', $tx->type);
        $this->assertSame('pending', $tx->status);
        $this->assertSame(1000, (int) $tx->amount);

        Queue::assertPushed(ProcessDepositTransaction::class, fn ($job) => $job->ledgerTransactionId === (int) $txId);
    }

    public function test_deposit_amount_must_be_positive(): void
    {
        $client = User::factory()->create([
            'email' => 'ana@gmail.com',
            'username' => 'ana',
            'role' => 'client',
        ]);

        Sanctum::actingAs($client);

        $response = $this->postJson('/api/me/deposits', [
            'amount' => '-1',
        ]);

        $response->assertStatus(422);
    }
}

