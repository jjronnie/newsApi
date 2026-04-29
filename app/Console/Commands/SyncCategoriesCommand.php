<?php

namespace App\Console\Commands;

use App\Jobs\SyncWordPressCategoriesJob;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('wp:sync-categories')]
#[Description('Sync WordPress categories')]
class SyncCategoriesCommand extends Command
{
    public function handle()
    {
        $this->info('Dispatching WordPress categories sync job...');

        SyncWordPressCategoriesJob::dispatch();

        $this->info('Job dispatched successfully.');
    }
}
