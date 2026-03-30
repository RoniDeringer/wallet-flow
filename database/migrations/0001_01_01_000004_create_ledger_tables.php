<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ledger_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('type', 20)->index(); // deposit|transfer|reversal
            $table->string('status', 20)->default('pending')->index(); // pending|posted|failed|reversed

            $table->unsignedBigInteger('amount'); // cents
            $table->string('currency', 3)->default('BRL')->index();

            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('from_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('to_account_id')->nullable()->constrained('accounts')->nullOnDelete();

            $table->foreignId('reversal_of_id')->nullable()->constrained('ledger_transactions')->nullOnDelete();

            $table->string('idempotency_key', 120)->nullable()->unique();
            $table->string('description')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();
        });

        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ledger_transaction_id')
                ->constrained('ledger_transactions')
                ->cascadeOnDelete();

            $table->foreignId('account_id')
                ->constrained('accounts')
                ->restrictOnDelete();

            $table->bigInteger('amount'); // signed cents: credit (+) / debit (-)
            $table->string('currency', 3)->default('BRL')->index();
            $table->bigInteger('balance_after')->nullable(); // snapshot (optional)
            $table->string('description')->nullable();

            $table->timestamps();

            $table->index(['account_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('ledger_transactions');
    }
};

