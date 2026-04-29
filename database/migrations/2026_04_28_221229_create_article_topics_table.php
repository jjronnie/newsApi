<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_topics', function (Blueprint $table) {
            $table->id();
            $table->string('topic_title');
            $table->string('focus_keyword');
            $table->enum('status', ['pending', 'used', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_topics');
    }
};
