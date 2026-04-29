<?php

namespace App\Jobs;

use App\Models\AiJobLog;
use App\Services\AiContentWriter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateArticleJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public function __construct(protected ?string $topic = null, protected ?int $topicId = null)
    {
        //
    }

    public function handle(): void
    {
        $jobLog = AiJobLog::create([
            'job_name' => 'GenerateArticleJob',
            'status' => 'running',
            'started_at' => now(),
            'meta_json' => [
                'topic' => $this->topic,
                'topic_id' => $this->topicId,
            ],
        ]);

        try {
            $writer = new AiContentWriter($this->topic ?? 'How to get started with Laravel in Uganda');
            $article = $writer->generateAndStore();

            $jobLog->update([
                'status' => 'success',
                'finished_at' => now(),
                'duration_ms' => $jobLog->started_at->diffInMilliseconds(now()),
                'meta_json' => [
                    'topic' => $this->topic,
                    'topic_id' => $this->topicId,
                    'article_id' => $article->id,
                    'title' => $article->title,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('GenerateArticleJob failed', ['error' => $e->getMessage()]);

            $jobLog->update([
                'status' => 'failed',
                'finished_at' => now(),
                'duration_ms' => $jobLog->started_at->diffInMilliseconds(now()),
                'error_message' => $e->getMessage(),
                'meta_json' => [
                    'topic' => $this->topic,
                    'topic_id' => $this->topicId,
                ],
            ]);
        }
    }
}
