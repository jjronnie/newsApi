<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Column user_id and index already added in 2026_04_29_001001_add_user_to_article_topics_table
        // This migration is kept for reference but does nothing
    }

    public function down(): void
    {
        // Nothing to revert
    }
};
