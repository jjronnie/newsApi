<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('news', function () {
    return Inertia::render('News');
})->name('news');

Route::get('/api/news', [\App\Http\Controllers\NewsController::class, 'index'])->name('api.news.index');
Route::get('/api/news/{category}', [\App\Http\Controllers\NewsController::class, 'getByCategory'])->name('api.news.category');
Route::post('/api/news/cache/clear', [\App\Http\Controllers\NewsController::class, 'clearCache'])->name('api.news.clearCache');

Route::resource('websites', \App\Http\Controllers\WebsiteController::class)->middleware(['auth', 'verified']);
Route::get('/api/websites/{website}/posts', [\App\Http\Controllers\WebsiteController::class, 'getPosts'])->name('api.websites.posts');
Route::get('/api/websites/{website}/posts/{postId}', [\App\Http\Controllers\WebsiteController::class, 'getPost'])->name('api.websites.post');

require __DIR__.'/settings.php';
