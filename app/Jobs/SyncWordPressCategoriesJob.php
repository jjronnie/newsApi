<?php

namespace App\Jobs;

use App\Models\AiJobLog;
use App\Models\WpCategory;
use App\Services\WordPressService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncWordPressCategoriesJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public function __construct()
    {
        //
    }

    public function handle(): void
    {
        $jobLog = AiJobLog::create([
            'job_name' => 'SyncWordPressCategoriesJob',
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $wordPressService = new WordPressService;
            $categories = $wordPressService->fetchCategories();

            foreach ($categories as $category) {
                WpCategory::updateOrCreate(
                    ['wp_id' => $category['id']],
                    [
                        'name' => $category['name'],
                        'slug' => $category['slug'],
                        'parent_wp_id' => $category['parent'] ?? null,
                    ]
                );
            }

            $jobLog->update([
                'status' => 'success',
                'finished_at' => now(),
                'duration_ms' => $jobLog->started_at->diffInMilliseconds(now()),
                'meta_json' => ['categories_count' => count($categories)],
            ]);
        } catch (\Exception $e) {
            Log::error('SyncWordPressCategoriesJob failed', ['error' => $e->getMessage()]);

            $jobLog->update([
                'status' => 'failed',
                'finished_at' => now(),
                'duration_ms' => $jobLog->started_at->diffInMilliseconds(now()),
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
