<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Index already exists from model's $table->index(['type', 'is_active'])
        // This migration is kept for reference but does nothing
    }

    public function down(): void
    {
        // Nothing to revert
    }
};
