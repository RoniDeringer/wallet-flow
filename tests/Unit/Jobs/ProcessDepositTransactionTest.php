<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ProcessDepositTransaction;
use App\Models\LedgerTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProcessDepositTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_posts_double_entry_and_marks_posted(): void
    {
        $user = User::factory()->create([
            'username' => 'ana',
            'role' => 'client',
        ]);

        $userAccountId = $this->ensureUserAccount($user->id);
        $platformAccountId = $this->ensurePlatformAccount();

        $txId = DB::table('ledger_transactions')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'type' => LedgerTransaction::TYPE_DEPOSIT,
            'status' => LedgerTransaction::STATUS_PENDING,
            'amount' => 1500,
            'currency' => LedgerTransaction::CURRENCY_BRL,
            'requested_by_user_id' => $user->id,
            'from_account_id' => $platformAccountId,
            'to_account_id' => $userAccountId,
            'description' => LedgerTransaction::DESCRIPTION_DEPOSIT,
            'meta' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        (new ProcessDepositTransaction($txId))->handle();

        $tx = DB::table('ledger_transactions')->where('id', '=', $txId)->first();
        $this->assertSame('posted', $tx->status);

        $entries = DB::table('ledger_entries')->where('ledger_transaction_id', '=', $txId)->get();
        $this->assertCount(2, $entries);

        $sum = (int) $entries->sum('amount');
        $this->assertSame(0, $sum);

        $userDelta = (int) $entries->where('account_id', '=', $userAccountId)->sum('amount');
        $platformDelta = (int) $entries->where('account_id', '=', $platformAccountId)->sum('amount');
        $this->assertSame(1500, $userDelta);
        $this->assertSame(-1500, $platformDelta);
    }

    public function test_job_marks_failed_when_accounts_missing(): void
    {
        $user = User::factory()->create([
            'username' => 'ana',
            'role' => 'client',
        ]);

        $txId = DB::table('ledger_transactions')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'type' => LedgerTransaction::TYPE_DEPOSIT,
            'status' => LedgerTransaction::STATUS_PENDING,
            'amount' => 1500,
            'currency' => LedgerTransaction::CURRENCY_BRL,
            'requested_by_user_id' => $user->id,
            'from_account_id' => null,
            'to_account_id' => null,
            'description' => LedgerTransaction::DESCRIPTION_DEPOSIT,
            'meta' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        (new ProcessDepositTransaction($txId))->handle();

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



