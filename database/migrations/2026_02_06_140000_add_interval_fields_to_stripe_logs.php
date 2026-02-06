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
            $table->string('interval')->nullable()->after('product_name'); // day, week, month, year
            $table->integer('interval_count')->nullable()->after('interval'); // 1, 6, 15, etc.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stripe_logs', function (Blueprint $table) {
            $table->dropColumn(['interval', 'interval_count']);
        });
    }
};
