<?php

use App\Http\Controllers\NewsController;
use App\Http\Controllers\WebsiteController;
use App\Models\Article;
use App\Models\User;
use App\Models\Website;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('/register', function () {
    return redirect()->route('login');
})->name('register');

Route::get('dashboard', function () {
    $stats = [
        'articles' => [
            'total' => Article::count(),
            'pushed' => Article::where('status', 'pushed')->count(),
            'generated' => Article::where('status', 'generated')->count(),
            'pending' => Article::where('status', 'pending')->count(),
            'failed' => Article::where('status', 'failed')->count(),
            'today' => Article::whereDate('generated_at', today())->count(),
        ],
        'users' => User::count(),
        'websites' => Website::count(),
    ];

    return Inertia::render('Dashboard', [
        'stats' => $stats,
    ]);
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
require __DIR__.'/admin.php';
