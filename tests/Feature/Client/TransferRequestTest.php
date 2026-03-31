<?php

namespace Tests\Feature\Client;

use App\Jobs\ProcessTransferTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TransferRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_request_transfer_and_job_is_dispatched(): void
    {
        Queue::fake();

        $sender = User::factory()->create([
            'email' => 'ana@gmail.com',
            'username' => 'ana',
            'role' => 'client',
        ]);

        $recipient = User::factory()->create([
            'email' => 'daniel@daniel.com',
            'username' => 'daniel',
            'role' => 'client',
        ]);

        $senderAccountId = $this->ensureUserAccount($sender->id);
        $recipientAccountId = $this->ensureUserAccount($recipient->id);
        $platformAccountId = $this->ensurePlatformAccount();

        // Seed balance for sender: +R$ 50.00
        $seedTxId = DB::table('ledger_transactions')->insertGetId([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'deposit',
            'status' => 'posted',
            'amount' => 5000,
            'currency' => 'BRL',
            'requested_by_user_id' => $sender->id,
            'from_account_id' => $platformAccountId,
            'to_account_id' => $senderAccountId,
            'description' => 'Seed',
            'meta' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('ledger_entries')->insert([
            [
                'ledger_transaction_id' => $seedTxId,
                'account_id' => $senderAccountId,
                'amount' => 5000,
                'currency' => 'BRL',
                'balance_after' => null,
                'description' => 'Seed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ledger_transaction_id' => $seedTxId,
                'account_id' => $platformAccountId,
                'amount' => -5000,
                'currency' => 'BRL',
                'balance_after' => null,
                'description' => 'Seed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->assertNotNull($recipientAccountId);

        Sanctum::actingAs($sender);

        $response = $this->postJson('/api/me/transfers', [
            'recipient_email' => $recipient->email,
            'amount' => '10.00',
        ], [
            'Idempotency-Key' => 't1',
        ]);

        $response->assertOk()->assertJsonPath('ok', true);

        $txId = $response->json('data.id');
        $this->assertNotEmpty($txId);

        $tx = DB::table('ledger_transactions')->where('id', '=', $txId)->first();
        $this->assertSame('transfer', $tx->type);
        $this->assertSame('pending', $tx->status);
        $this->assertSame(1000, (int) $tx->amount);

        Queue::assertPushed(ProcessTransferTransaction::class, fn ($job) => $job->ledgerTransactionId === (int) $txId);
    }

    public function test_transfer_requires_sufficient_balance(): void
    {
        Queue::fake();

        $sender = User::factory()->create([
            'email' => 'ana@gmail.com',
            'username' => 'ana',
            'role' => 'client',
        ]);

        $recipient = User::factory()->create([
            'email' => 'daniel@daniel.com',
            'username' => 'daniel',
            'role' => 'client',
        ]);

        $this->ensureUserAccount($sender->id);
        $this->ensureUserAccount($recipient->id);

        Sanctum::actingAs($sender);

        $response = $this->postJson('/api/me/transfers', [
            'recipient_email' => $recipient->email,
            'amount' => '10.00',
        ]);

        $response->assertStatus(422);
        Queue::assertNothingPushed();
    }

    private function ensureUserAccount(int $userId): int
    {
        DB::table('accounts')->updateOrInsert(
            ['type' => 'user', 'user_id' => $userId, 'currency' => 'BRL'],
            ['name' => 'User (BRL)', 'key' => null, 'updated_at' => now(), 'created_at' => now()]
        );

        return (int) DB::table('accounts')
            ->where('type', '=', 'user')
            ->where('user_id', '=', $userId)
            ->where('currency', '=', 'BRL')
            ->value('id');
    }

    private function ensurePlatformAccount(): int
    {
        DB::table('accounts')->updateOrInsert(
            ['type' => 'system', 'key' => 'platform', 'currency' => 'BRL'],
            ['name' => 'Plataforma (BRL)', 'user_id' => null, 'updated_at' => now(), 'created_at' => now()]
        );

        return (int) DB::table('accounts')
            ->where('type', '=', 'system')
            ->where('key', '=', 'platform')
            ->where('currency', '=', 'BRL')
            ->value('id');
    }
}

