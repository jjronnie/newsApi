<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\FetchWpCategoriesJob;
use App\Models\WpCategory;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class WpCategoryController extends Controller
{
    public function index()
    {
        $categories = WpCategory::latest()->paginate(20);

        return Inertia::render('Admin/WpCategories/Index', [
            'categories' => $categories,
        ]);
    }

    public function fetchFromWp()
    {
        FetchWpCategoriesJob::dispatch();

        return Redirect::back()->with('success', 'Fetching WP categories job dispatched.');
    }
}
