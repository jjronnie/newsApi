<?php

namespace App\Console\Commands;

use App\Jobs\GenerateArticleJob;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('ai:generate-article {topic? : The topic to generate}')]
#[Description('Generate an article using AI')]
class GenerateArticleCommand extends Command
{
    public function handle()
    {
        $topic = $this->argument('topic');

        $this->info('Dispatching article generation job...');

        GenerateArticleJob::dispatch($topic);

        $this->info('Job dispatched successfully.');
    }
}
