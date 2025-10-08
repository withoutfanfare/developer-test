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
        Schema::table('tasks', function (Blueprint $table) {
            // Single-column indexes
            $table->index('status');
            $table->index('priority');
            $table->index('category');
            $table->index('created_at');

            // Composite indexes for common query patterns
            $table->index(['user_id', 'status']);
            $table->index(['category', 'status']);
            $table->index(['created_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Drop single-column indexes
            $table->dropIndex(['status']);
            $table->dropIndex(['priority']);
            $table->dropIndex(['category']);
            $table->dropIndex(['created_at']);

            // Drop composite indexes
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['category', 'status']);
            $table->dropIndex(['created_at', 'status']);
        });
    }
};
