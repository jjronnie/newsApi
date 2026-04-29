<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->json('faq_json')->nullable()->after('content_html');
            $table->json('outline_json')->nullable()->after('faq_json');
            $table->json('tag_suggestions_json')->nullable()->after('outline_json');
            $table->json('category_suggestions_json')->nullable()->after('tag_suggestions_json');
            $table->enum('status', ['pending', 'generating', 'generated', 'pushing', 'pushed', 'failed'])->default('pending')->change();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['faq_json', 'outline_json', 'tag_suggestions_json', 'category_suggestions_json']);
            $table->enum('status', ['pending', 'generating', 'generated', 'pushed', 'failed'])->default('pending')->change();
            $table->dropIndex(['status']);
        });
    }
};
