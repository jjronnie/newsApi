<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class NewsService
{
    private string $apiKey = '8d68b50d2ed9484ab47af3ee674f5a38';
    private string $baseUrl = 'https://newsapi.org/v2';
    private int $cacheMinutes = 30; // Cache for 30 minutes

    /**
     * Get news from specified categories
     */
    public function getNewsByCategories(array $categories = ['technology', 'sports']): array
    {
        $articles = [];

        foreach ($categories as $category) {
            $cacheKey = "news_category_{$category}";
            
            // Check cache first
            if (Cache::has($cacheKey)) {
                $articles = array_merge($articles, Cache::get($cacheKey));
                continue;
            }

            try {
                $response = Http::get("{$this->baseUrl}/top-headlines", [
                    'category' => $category,
                    'apiKey' => $this->apiKey,
                    'pageSize' => 20,
                    'language' => 'en',
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $processedArticles = $this->processArticles($data['articles'] ?? []);
                    
                    // Cache the results
                    Cache::put($cacheKey, $processedArticles, now()->addMinutes($this->cacheMinutes));
                    
                    $articles = array_merge($articles, $processedArticles);
                }
            } catch (\Exception $e) {
                \Log::error("Error fetching news for category {$category}: " . $e->getMessage());
            }
        }

        return $this->formatResponse($articles);
    }

    /**
     * Process articles from API response
     */
    private function processArticles(array $articles): array
    {
        return array_map(function ($article) {
            return [
                'title' => $article['title'] ?? 'No title',
                'excerpt' => $article['description'] ?? 'No description available',
                'image' => $article['urlToImage'] ?? null,
                'link' => $article['url'] ?? '#',
                'source' => $article['source']['name'] ?? 'Unknown',
                'publishedAt' => $article['publishedAt'] ?? null,
                'author' => $article['author'] ?? null,
            ];
        }, $articles);
    }

    /**
     * Format the final response
     */
    private function formatResponse(array $articles): array
    {
        // Remove duplicates based on title
        $unique = [];
        $titles = [];

        foreach ($articles as $article) {
            if (!in_array($article['title'], $titles)) {
                $unique[] = $article;
                $titles[] = $article['title'];
            }
        }

        // Sort by published date (newest first)
        usort($unique, function ($a, $b) {
            $dateA = strtotime($a['publishedAt'] ?? 0);
            $dateB = strtotime($b['publishedAt'] ?? 0);
            return $dateB - $dateA;
        });

        return $unique;
    }

    /**
     * Clear cache for all categories
     */
    public function clearCache(array $categories = ['technology', 'sports']): void
    {
        foreach ($categories as $category) {
            Cache::forget("news_category_{$category}");
        }
    }
}
