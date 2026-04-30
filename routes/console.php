<?php

use App\Jobs\GenerateArticleJob;
use App\Jobs\PushPendingArticlesToWordPressJob;
use App\Jobs\SyncWordPressCategoriesJob;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new SyncWordPressCategoriesJob)->dailyAt('00:00');

Schedule::job(new GenerateArticleJob)->timezone('Africa/Kampala')->at('9:00');
Schedule::job(new GenerateArticleJob)->timezone('Africa/Kampala')->at('11:55');
Schedule::job(new GenerateArticleJob)->timezone('Africa/Kampala')->at('15:00');
Schedule::job(new GenerateArticleJob)->timezone('Africa/Kampala')->at('20:00');
Schedule::job(new GenerateArticleJob)->timezone('Africa/Kampala')->at('22:00');

Schedule::job(new PushPendingArticlesToWordPressJob)->hourly();
