<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArticleTopic;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;

class ArticleTopicController extends Controller
{
    public function index()
    {
        $topics = ArticleTopic::latest()->paginate(20);

        return Inertia::render('Admin/Topics/Index', [
            'topics' => $topics,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Topics/Create');
    }

    public function store()
    {
        $validated = Request::validate([
            'topic_title' => ['required', 'string', 'max:255'],
            'focus_keyword' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:pending,used,rejected'],
        ]);

        ArticleTopic::create($validated);

        return Redirect::route('admin.topics.index')->with('success', 'Topic created successfully.');
    }

    public function edit(ArticleTopic $topic)
    {
        return Inertia::render('Admin/Topics/Edit', [
            'topic' => $topic,
        ]);
    }

    public function update(ArticleTopic $topic)
    {
        $validated = Request::validate([
            'topic_title' => ['required', 'string', 'max:255'],
            'focus_keyword' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:pending,used,rejected'],
        ]);

        $topic->update($validated);

        return Redirect::route('admin.topics.index')->with('success', 'Topic updated successfully.');
    }

    public function destroy(ArticleTopic $topic)
    {
        $topic->delete();

        return Redirect::back()->with('success', 'Topic deleted successfully.');
    }
}
