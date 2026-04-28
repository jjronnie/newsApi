<?php

use App\Http\Controllers\NewsController;
use App\Http\Controllers\WebsiteController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('/test-wp-post', function () {
    $response = Http::withBasicAuth(
        config('wordpress.username'),
        config('wordpress.app_password')
    )->post(config('wordpress.url').'/wp-json/wp/v2/posts', [
        'title' => 'Hello from Laravel',
        'content' => '<p>This post was created by Laravel via REST API.</p>',
        'status' => 'draft',
    ]);

    return $response->json();
});

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('news', function () {
    return Inertia::render('News');
})->name('news');

Route::get('/api/news', [NewsController::class, 'index'])->name('api.news.index');
Route::get('/api/news/{category}', [NewsController::class, 'getByCategory'])->name('api.news.category');
Route::post('/api/news/cache/clear', [NewsController::class, 'clearCache'])->name('api.news.clearCache');

Route::resource('websites', WebsiteController::class)->middleware(['auth', 'verified']);
Route::get('/api/websites/{website}/posts', [WebsiteController::class, 'getPosts'])->name('api.websites.posts');
Route::get('/api/websites/{website}/posts/{postId}', [WebsiteController::class, 'getPost'])->name('api.websites.post');

require __DIR__.'/settings.php';
