<?php

namespace App\Console\Commands;

use App\Jobs\PushPendingArticlesToWordPressJob;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('wp:push-pending')]
#[Description('Push pending articles to WordPress')]
class PushPendingCommand extends Command
{
    public function handle()
    {
        $this->info('Dispatching push pending articles job...');

        PushPendingArticlesToWordPressJob::dispatch();

        $this->info('Job dispatched successfully.');
    }
}
