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
        Schema::table('stripe_logs', function (Blueprint $table) {
            $table->string('subscription_id')->nullable()->index()->after('session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stripe_logs', function (Blueprint $table) {
            $table->dropColumn(['subscription_id']);
        });
    }
};
