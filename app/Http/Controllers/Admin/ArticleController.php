<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateArticleJob;
use App\Jobs\PushPendingArticlesToWordPressJob;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class ArticleController extends Controller
{
    public function index()
    {
        $articles = Article::latest()->paginate(20);

        return Inertia::render('Admin/Articles/Index', [
            'articles' => $articles,
        ]);
    }

    public function show(Article $article)
    {
        $article->load('generationEvents');

        return Inertia::render('Admin/Articles/Show', [
            'article' => $article,
        ]);
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'topic' => ['required', 'string', 'max:255'],
        ]);

        GenerateArticleJob::dispatch($validated['topic']);

        return Redirect::back()->with('success', 'Article generation job dispatched successfully.');
    }

    public function push(Article $article)
    {
        PushPendingArticlesToWordPressJob::dispatch();

        return Redirect::back()->with('success', 'Push to WordPress job dispatched.');
    }

    public function progress(Article $article)
    {
        $events = $article->generationEvents()->latest()->limit(10)->get();

        return response()->json([
            'status' => $article->status->value,
            'events' => $events,
        ]);
    }

    public function destroy(Article $article)
    {
        $article->generationEvents()->delete();
        $article->delete();

        return Redirect::route('admin.articles.index')->with('success', 'Article deleted.');
    }
}
