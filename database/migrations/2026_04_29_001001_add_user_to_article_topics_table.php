<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('article_topics', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id');
            $table->text('description')->nullable()->after('focus_keyword');
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('article_topics', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropColumn(['user_id', 'description']);
        });
    }
};
