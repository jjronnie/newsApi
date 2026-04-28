<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Services\WebsiteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WebsiteController extends Controller
{
    private WebsiteService $websiteService;

    public function __construct(WebsiteService $websiteService)
    {
        $this->websiteService = $websiteService;
    }

    /**
     * Display a listing of websites
     */
    public function index()
    {
        $websites = Website::latest()->get();

        return Inertia::render('Websites/Index', [
            'websites' => $websites,
        ]);
    }

    /**
     * Store a newly created website
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|string|max:255',
        ]);

        $resolvedUrl = $this->websiteService->resolveBaseUrl($validated['url']);

        if (! $resolvedUrl) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to connect to this website. Make sure it has WordPress REST API enabled.',
            ], 422);
        }

        if (Website::where('url', $resolvedUrl)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This website URL already exists.',
            ], 422);
        }

        $website = Website::create([
            'name' => $validated['name'],
            'url' => $resolvedUrl,
        ]);

        return response()->json([
            'success' => true,
            'website' => $website,
            'message' => 'Website added successfully',
        ]);
    }

    /**
     * Display posts from a website
     */
    public function show(Website $website, Request $request)
    {
        $page = $request->query('page', 1);
        $perPage = 10;

        $postsData = $this->websiteService->getPostsByWebsite($website, $page, $perPage);

        return Inertia::render('Websites/Show', [
            'website' => $website,
            'posts' => $postsData['posts'],
            'pagination' => $postsData['pagination'] ?? [],
            'error' => $postsData['error'] ?? null,
        ]);
    }

    /**
     * Get posts for a website (API endpoint)
     */
    public function getPosts(Website $website, Request $request): JsonResponse
    {
        $page = $request->query('page', 1);
        $perPage = 10;

        $postsData = $this->websiteService->getPostsByWebsite($website, $page, $perPage);

        return response()->json($postsData);
    }

    /**
     * Get a single post from a website
     */
    public function getPost(Website $website, int $postId): JsonResponse
    {
        $post = $this->websiteService->getPost($website, $postId);

        if (isset($post['error'])) {
            return response()->json($post, 404);
        }

        return response()->json([
            'success' => true,
            'post' => $post,
        ]);
    }

    /**
     * Delete a website
     */
    public function destroy(Website $website): JsonResponse
    {
        $website->delete();

        return response()->json([
            'success' => true,
            'message' => 'Website deleted successfully',
        ]);
    }

    /**
     * Update a website
     */
    public function update(Request $request, Website $website): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|string|max:255',
        ]);

        $resolvedUrl = $this->websiteService->resolveBaseUrl($validated['url']);

        if (! $resolvedUrl) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to connect to this website. Make sure it has WordPress REST API enabled.',
            ], 422);
        }

        if ($resolvedUrl !== $website->url && Website::where('url', $resolvedUrl)->whereKeyNot($website->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This website URL already exists.',
            ], 422);
        }

        $website->update([
            'name' => $validated['name'],
            'url' => $resolvedUrl,
        ]);

        return response()->json([
            'success' => true,
            'website' => $website->fresh(),
            'message' => 'Website updated successfully',
        ]);
    }
}
