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
        Schema::table('users', function (Blueprint $table) {
            $table->index('is_active');
            $table->index('is_blocked');
            $table->index('role');
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('media_file_versions', function (Blueprint $table) {
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['is_blocked']);
            $table->dropIndex(['role']);
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('media_file_versions', function (Blueprint $table) {
            $table->dropIndex(['type']);
        });
    }
};
