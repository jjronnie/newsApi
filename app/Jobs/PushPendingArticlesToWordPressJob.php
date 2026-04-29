<?php

namespace App\Jobs;

use App\Models\AiJobLog;
use App\Models\Article;
use App\Services\WordPressService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class PushPendingArticlesToWordPressJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public function __construct(protected int $maxPostsPerRun = 3)
    {
        //
    }

    public function handle(): void
    {
        $jobLog = AiJobLog::create([
            'job_name' => 'PushPendingArticlesToWordPressJob',
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $articles = Article::where('status', 'generated')
                ->whereNull('wp_post_id')
                ->limit($this->maxPostsPerRun)
                ->get();

            $pushed = 0;
            $wordPressService = new WordPressService;

            foreach ($articles as $article) {
                try {
                    $article->update(['status' => 'pushing']);
                    $wpPostId = $wordPressService->createDraftPost($article);

                    $article->update([
                        'wp_post_id' => $wpPostId,
                        'status' => 'pushed',
                        'pushed_at' => now(),
                    ]);

                    $pushed++;
                } catch (\Exception $e) {
                    $article->update([
                        'status' => 'failed',
                        'error_message' => $e->getMessage(),
                    ]);
                }
            }

            $jobLog->update([
                'status' => 'success',
                'finished_at' => now(),
                'duration_ms' => $jobLog->started_at->diffInMilliseconds(now()),
                'meta_json' => ['pushed_count' => $pushed],
            ]);
        } catch (\Exception $e) {
            Log::error('PushPendingArticlesToWordPressJob failed', ['error' => $e->getMessage()]);

            $jobLog->update([
                'status' => 'failed',
                'finished_at' => now(),
                'duration_ms' => $jobLog->started_at->diffInMilliseconds(now()),
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
