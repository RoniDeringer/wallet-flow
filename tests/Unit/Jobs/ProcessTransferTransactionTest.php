<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ProcessTransferTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProcessTransferTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_posts_transfer_entries_and_marks_posted(): void
    {
        $sender = User::factory()->create(['username' => 'ana', 'role' => 'client']);
        $recipient = User::factory()->create(['username' => 'daniel', 'role' => 'client']);

        $fromAccountId = $this->ensureUserAccount($sender->id);
        $toAccountId = $this->ensureUserAccount($recipient->id);
        $platformAccountId = $this->ensurePlatformAccount();

        // Seed sender balance with +R$ 30.00
        $seedTxId = DB::table('ledger_transactions')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'type' => 'deposit',
            'status' => 'posted',
            'amount' => 3000,
            'currency' => 'BRL',
            'requested_by_user_id' => $sender->id,
            'from_account_id' => $platformAccountId,
            'to_account_id' => $fromAccountId,
            'description' => 'Seed',
            'meta' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('ledger_entries')->insert([
            [
                'ledger_transaction_id' => $seedTxId,
                'account_id' => $fromAccountId,
                'amount' => 3000,
                'currency' => 'BRL',
                'balance_after' => null,
                'description' => 'Seed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ledger_transaction_id' => $seedTxId,
                'account_id' => $platformAccountId,
                'amount' => -3000,
                'currency' => 'BRL',
                'balance_after' => null,
                'description' => 'Seed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $txId = DB::table('ledger_transactions')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'type' => 'transfer',
            'status' => 'pending',
            'amount' => 1200,
            'currency' => 'BRL',
            'requested_by_user_id' => $sender->id,
            'from_account_id' => $fromAccountId,
            'to_account_id' => $toAccountId,
            'description' => 'Transferência',
            'meta' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        (new ProcessTransferTransaction($txId))->handle();

        $tx = DB::table('ledger_transactions')->where('id', '=', $txId)->first();
        $this->assertSame('posted', $tx->status);

        $entries = DB::table('ledger_entries')->where('ledger_transaction_id', '=', $txId)->get();
        $this->assertCount(2, $entries);
        $this->assertSame(0, (int) $entries->sum('amount'));
    }

    public function test_job_fails_when_insufficient_funds(): void
    {
        $sender = User::factory()->create(['username' => 'ana', 'role' => 'client']);
        $recipient = User::factory()->create(['username' => 'daniel', 'role' => 'client']);

        $fromAccountId = $this->ensureUserAccount($sender->id);
        $toAccountId = $this->ensureUserAccount($recipient->id);

        $txId = DB::table('ledger_transactions')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'type' => 'transfer',
            'status' => 'pending',
            'amount' => 1200,
            'currency' => 'BRL',
            'requested_by_user_id' => $sender->id,
            'from_account_id' => $fromAccountId,
            'to_account_id' => $toAccountId,
            'description' => 'Transferência',
            'meta' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        (new ProcessTransferTransaction($txId))->handle();

        $tx = DB::table('ledger_transactions')->where('id', '=', $txId)->first();
        $this->assertSame('failed', $tx->status);
        $this->assertSame(0, (int) DB::table('ledger_entries')->where('ledger_transaction_id', '=', $txId)->count());
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

