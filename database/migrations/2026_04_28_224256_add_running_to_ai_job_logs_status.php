<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_job_logs', function (Blueprint $table) {
            $table->enum('status', ['success', 'failed', 'running'])->change();
        });
    }

    public function down(): void
    {
        Schema::table('ai_job_logs', function (Blueprint $table) {
            $table->enum('status', ['success', 'failed'])->change();
        });
    }
};
