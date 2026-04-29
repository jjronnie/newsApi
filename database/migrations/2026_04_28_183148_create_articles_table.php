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
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wp_post_id')->nullable();
            $table->unsignedBigInteger('wp_category_id')->nullable();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('focus_keyword');
            $table->string('meta_title');
            $table->string('meta_description', 160);
            $table->text('excerpt');
            $table->longText('content_html');
            $table->enum('status', ['pending', 'generating', 'generated', 'pushed', 'failed'])->default('pending');
            $table->dateTime('scheduled_for')->nullable();
            $table->dateTime('generated_at')->nullable();
            $table->dateTime('pushed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->string('ai_provider')->default('groq');
            $table->string('ai_model');
            $table->string('ai_prompt_version')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
