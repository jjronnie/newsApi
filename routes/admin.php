<?php

use App\Http\Controllers\Admin\AiInstructionController;
use App\Http\Controllers\Admin\AiJobLogController;
use App\Http\Controllers\Admin\ArticleChatController;
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\ArticleTopicController;
use App\Http\Controllers\Admin\JobController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WpCategoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    // Article Chat
    Route::get('/articles/chat', [ArticleChatController::class, 'index'])->name('articles.chat.index');
    Route::get('/articles/chat/conversations', [ArticleChatController::class, 'conversations'])->name('articles.chat.conversations');
    Route::delete('/articles/chat/conversations/{conversation}', [ArticleChatController::class, 'destroyConversation'])->name('articles.chat.conversations.destroy');
    Route::post('/articles/chat', [ArticleChatController::class, 'chat'])->name('articles.chat.send');
    Route::post('/articles/generate-chat', [ArticleChatController::class, 'generateWithChat'])->name('articles.chat.generate');

    // Articles
    Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
    Route::post('/articles/generate', [ArticleController::class, 'generate'])->name('articles.generate');
    Route::get('/articles/{article}', [ArticleController::class, 'show'])->name('articles.show');
    Route::post('/articles/{article}/push', [ArticleController::class, 'push'])->name('articles.push');
    Route::delete('/articles/{article}', [ArticleController::class, 'destroy'])->name('articles.destroy');
    Route::get('/articles/{article}/progress', [ArticleController::class, 'progress'])->name('articles.progress');

    // Topics
    Route::get('/topics', [ArticleTopicController::class, 'index'])->name('topics.index');
    Route::get('/topics/create', [ArticleTopicController::class, 'create'])->name('topics.create');
    Route::post('/topics', [ArticleTopicController::class, 'store'])->name('topics.store');
    Route::get('/topics/{topic}/edit', [ArticleTopicController::class, 'edit'])->name('topics.edit');
    Route::put('/topics/{topic}', [ArticleTopicController::class, 'update'])->name('topics.update');
    Route::delete('/topics/{topic}', [ArticleTopicController::class, 'destroy'])->name('topics.destroy');

    // Instructions
    Route::get('/instructions', [AiInstructionController::class, 'index'])->name('instructions.index');
    Route::get('/instructions/{instruction}/edit', [AiInstructionController::class, 'edit'])->name('instructions.edit');
    Route::put('/instructions/{instruction}', [AiInstructionController::class, 'update'])->name('instructions.update');

    // WordPress Categories
    Route::get('/wp-categories', [WpCategoryController::class, 'index'])->name('wp-categories.index');
    Route::post('/wp-categories/fetch', [WpCategoryController::class, 'fetchFromWp'])->name('wp-categories.fetch');

    // AI Job Logs
    Route::get('/logs', [AiJobLogController::class, 'index'])->name('logs.index');
    Route::delete('/logs/{log}', [AiJobLogController::class, 'destroy'])->name('logs.destroy');

    // Users
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    // Queued Jobs
    Route::get('/jobs', [JobController::class, 'index'])->name('jobs.index');
    Route::get('/jobs/{id}', [JobController::class, 'show'])->name('jobs.show');
});
