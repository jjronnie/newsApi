<?php

namespace App\Http\Controllers;

use App\Services\NewsService;
use Illuminate\Http\JsonResponse;

class NewsController extends Controller
{
    private NewsService $newsService;

    public function __construct(NewsService $newsService)
    {
        $this->newsService = $newsService;
    }

    /**
     * Get news from Technology and Sports categories
     */
    public function index(): JsonResponse
    {
        $news = $this->newsService->getNewsByCategories(['technology', 'sports']);
        
        return response()->json([
            'success' => true,
            'data' => $news,
            'count' => count($news),
        ]);
    }

    /**
     * Get news from a specific category
     */
    public function getByCategory(string $category): JsonResponse
    {
        $validCategories = ['technology', 'sports', 'business', 'entertainment', 'general', 'health', 'science'];
        
        if (!in_array(strtolower($category), $validCategories)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid category. Valid categories: ' . implode(', ', $validCategories),
            ], 400);
        }

        $news = $this->newsService->getNewsByCategories([strtolower($category)]);
        
        return response()->json([
            'success' => true,
            'category' => $category,
            'data' => $news,
            'count' => count($news),
        ]);
    }

    /**
     * Clear news cache
     */
    public function clearCache(): JsonResponse
    {
        $this->newsService->clearCache(['technology', 'sports']);
        
        return response()->json([
            'success' => true,
            'message' => 'News cache cleared successfully',
        ]);
    }
}
