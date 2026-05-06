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
        Schema::create('payment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->onDelete('cascade');
            $table->string('fee_master_id')->nullable(); // Original Fee ID
            $table->string('fee_head')->nullable();
            $table->string('fee_type')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('fee_month')->nullable();
            $table->integer('fee_year')->nullable();
            $table->string('status')->default('pending'); // Can mirror parent status
            $table->date('date')->nullable();
            $table->time('time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_items');
    }
};
