<?php

namespace Tests\Feature\Client;

use App\Jobs\ProcessReversalTransaction;
use App\Models\LedgerTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReversalRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_request_reversal_and_job_is_dispatched(): void
    {
        Queue::fake();

        $client = User::factory()->create([
            'email' => 'ana@gmail.com',
            'username' => 'ana',
            'role' => 'client',
        ]);

        Sanctum::actingAs($client);

        $userAccountId = $this->ensureUserAccount($client->id);
        $platformAccountId = $this->ensurePlatformAccount();

        $originalId = DB::table('ledger_transactions')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'type' => LedgerTransaction::TYPE_DEPOSIT,
            'status' => LedgerTransaction::STATUS_POSTED,
            'amount' => 1000,
            'currency' => LedgerTransaction::CURRENCY_BRL,
            'requested_by_user_id' => $client->id,
            'from_account_id' => $platformAccountId,
            'to_account_id' => $userAccountId,
            'description' => LedgerTransaction::DESCRIPTION_DEPOSIT,
            'meta' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('ledger_entries')->insert([
            [
                'ledger_transaction_id' => $originalId,
                'account_id' => $userAccountId,
                'amount' => 1000,
                'currency' => LedgerTransaction::CURRENCY_BRL,
                'balance_after' => null,
                'description' => LedgerTransaction::DESCRIPTION_DEPOSIT,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ledger_transaction_id' => $originalId,
                'account_id' => $platformAccountId,
                'amount' => -1000,
                'currency' => LedgerTransaction::CURRENCY_BRL,
                'balance_after' => null,
                'description' => LedgerTransaction::DESCRIPTION_DEPOSIT,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->postJson("/api/me/transactions/{$originalId}/reversal");

        $response->assertOk()->assertJsonPath('ok', true);
        $reversalId = (int) $response->json('data.id');

        $reversal = DB::table('ledger_transactions')->where('id', '=', $reversalId)->first();
        $this->assertSame('reversal', $reversal->type);
        $this->assertSame('pending', $reversal->status);
        $this->assertSame($originalId, (int) $reversal->reversal_of_id);

        Queue::assertPushed(ProcessReversalTransaction::class, fn($job) => $job->ledgerTransactionId === $reversalId);
    }

    public function test_reversal_is_blocked_when_user_has_pending_transfer(): void
    {
        Queue::fake();

        $client = User::factory()->create([
            'email' => 'ana@gmail.com',
            'username' => 'ana',
            'role' => 'client',
        ]);

        Sanctum::actingAs($client);

        $userAccountId = $this->ensureUserAccount($client->id);
        $platformAccountId = $this->ensurePlatformAccount();

        $originalId = DB::table('ledger_transactions')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'type' => LedgerTransaction::TYPE_DEPOSIT,
            'status' => LedgerTransaction::STATUS_POSTED,
            'amount' => 1000,
            'currency' => LedgerTransaction::CURRENCY_BRL,
            'requested_by_user_id' => $client->id,
            'from_account_id' => $platformAccountId,
            'to_account_id' => $userAccountId,
            'description' => LedgerTransaction::DESCRIPTION_DEPOSIT,
            'meta' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('ledger_entries')->insert([
            [
                'ledger_transaction_id' => $originalId,
                'account_id' => $userAccountId,
                'amount' => 1000,
                'currency' => LedgerTransaction::CURRENCY_BRL,
                'balance_after' => null,
                'description' => LedgerTransaction::DESCRIPTION_DEPOSIT,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ledger_transaction_id' => $originalId,
                'account_id' => $platformAccountId,
                'amount' => -1000,
                'currency' => LedgerTransaction::CURRENCY_BRL,
                'balance_after' => null,
                'description' => LedgerTransaction::DESCRIPTION_DEPOSIT,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Pending transfer involving user blocks reversal
        DB::table('ledger_transactions')->insert([
            'uuid' => (string) Str::uuid(),
            'type' => LedgerTransaction::TYPE_TRANSFER,
            'status' => LedgerTransaction::STATUS_PENDING,
            'amount' => 100,
            'currency' => LedgerTransaction::CURRENCY_BRL,
            'requested_by_user_id' => $client->id,
            'from_account_id' => $userAccountId,
            'to_account_id' => $platformAccountId,
            'description' => 'Pendente',
            'meta' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson("/api/me/transactions/{$originalId}/reversal");

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
