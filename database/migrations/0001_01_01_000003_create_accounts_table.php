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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->index(); // user|system
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('key')->nullable()->index(); // used for system accounts (e.g. platform)
            $table->string('name');
            $table->string('currency', 3)->default('BRL')->index();
            $table->timestamps();

            $table->unique(['user_id', 'currency']);
            $table->unique(['type', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};

