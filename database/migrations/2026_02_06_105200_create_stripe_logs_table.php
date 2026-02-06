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
        Schema::create('stripe_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('type')->index(); // checkout, subscription, payment_intent, setup_intent
            $table->string('stripe_customer_id')->nullable()->index();
            $table->string('session_id')->nullable()->index();
            $table->string('payment_intent_id')->nullable()->index(); // Can be payment_intent or setup_intent
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('status')->nullable();
            $table->json('payload')->nullable(); // Store important response details
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stripe_logs');
    }
};
