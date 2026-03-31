<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ProcessReversalTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProcessReversalTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_posts_reversal_entries_and_marks_original_reversed(): void
    {
        $user = User::factory()->create(['username' => 'ana', 'role' => 'client']);

        $userAccountId = $this->ensureUserAccount($user->id);
        $platformAccountId = $this->ensurePlatformAccount();

        $originalId = DB::table('ledger_transactions')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'type' => 'deposit',
            'status' => 'posted',
            'amount' => 2000,
            'currency' => 'BRL',
            'requested_by_user_id' => $user->id,
            'from_account_id' => $platformAccountId,
            'to_account_id' => $userAccountId,
            'description' => 'Depósito',
            'meta' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('ledger_entries')->insert([
            [
                'ledger_transaction_id' => $originalId,
                'account_id' => $userAccountId,
                'amount' => 2000,
                'currency' => 'BRL',
                'balance_after' => null,
                'description' => 'Depósito',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ledger_transaction_id' => $originalId,
                'account_id' => $platformAccountId,
                'amount' => -2000,
                'currency' => 'BRL',
                'balance_after' => null,
                'description' => 'Depósito',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $reversalId = DB::table('ledger_transactions')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'type' => 'reversal',
            'status' => 'pending',
            'amount' => 2000,
            'currency' => 'BRL',
            'requested_by_user_id' => $user->id,
            'from_account_id' => $userAccountId,
            'to_account_id' => $platformAccountId,
            'reversal_of_id' => $originalId,
            'description' => 'Reversão',
            'meta' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        (new ProcessReversalTransaction($reversalId))->handle();

        $reversal = DB::table('ledger_transactions')->where('id', '=', $reversalId)->first();
        $this->assertSame('posted', $reversal->status);

        $original = DB::table('ledger_transactions')->where('id', '=', $originalId)->first();
        $this->assertSame('reversed', $original->status);

        $reversalEntries = DB::table('ledger_entries')->where('ledger_transaction_id', '=', $reversalId)->get();
        $this->assertCount(2, $reversalEntries);
        $this->assertSame(0, (int) $reversalEntries->sum('amount'));
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

