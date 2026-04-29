<?php

use App\Jobs\GenerateArticleJob;
use App\Jobs\PushPendingArticlesToWordPressJob;
use App\Jobs\SyncWordPressCategoriesJob;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new SyncWordPressCategoriesJob)->dailyAt('00:00');

Schedule::job(new GenerateArticleJob)->timezone('Africa/Kampala')->at('8:00');
Schedule::job(new GenerateArticleJob)->timezone('Africa/Kampala')->at('11:00');
Schedule::job(new GenerateArticleJob)->timezone('Africa/Kampala')->at('15:00');
Schedule::job(new GenerateArticleJob)->timezone('Africa/Kampala')->at('19:00');
Schedule::job(new GenerateArticleJob)->timezone('Africa/Kampala')->at('3:56');

Schedule::job(new PushPendingArticlesToWordPressJob)->hourly();
