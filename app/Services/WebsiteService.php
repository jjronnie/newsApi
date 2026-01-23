<?php

namespace App\Services;

use App\Models\Website;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WebsiteService
{
    private int $cacheMinutes = 60; // Cache for 1 hour

    /**
     * Get posts from a WordPress website
     */
    public function getPostsByWebsite(Website $website, int $page = 1, int $perPage = 10): array
    {
        $cacheKey = "website_posts_{$website->id}_page_{$page}";
        
        // Check cache first
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $baseUrl = $this->normalizeUrl($website->url);
            $response = Http::get("{$baseUrl}/wp-json/wp/v2/posts", [
                'per_page' => $perPage,
                'page' => $page,
                '_embed' => true, // Include embedded data like featured images
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $totalPages = (int)$response->header('X-WP-TotalPages') ?? 1;
                $total = (int)$response->header('X-WP-Total') ?? 0;

                $processed = $this->processPostData($data, $website->url);
                
                // Update last_updated timestamp
                $website->update(['last_updated' => now()]);
                
                $result = [
                    'posts' => $processed,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total' => $total,
                        'total_pages' => $totalPages,
                        'has_more' => $page < $totalPages,
                    ],
                ];

                // Cache the results
                Cache::put($cacheKey, $result, now()->addMinutes($this->cacheMinutes));
                
                return $result;
            }
        } catch (\Exception $e) {
            \Log::error("Error fetching posts from website {$website->id}: " . $e->getMessage());
        }

        return [
            'posts' => [],
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => 0,
                'total_pages' => 1,
                'has_more' => false,
            ],
            'error' => 'Failed to fetch posts',
        ];
    }

    /**
     * Get a single post by ID
     */
    public function getPost(Website $website, int $postId): array
    {
        $cacheKey = "website_post_{$website->id}_{$postId}";
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $baseUrl = $this->normalizeUrl($website->url);
            $response = Http::get("{$baseUrl}/wp-json/wp/v2/posts/{$postId}", [
                '_embed' => true,
            ]);

            if ($response->successful()) {
                $post = $response->json();
                $processed = $this->processSinglePost($post, $website->url);
                
                Cache::put($cacheKey, $processed, now()->addMinutes($this->cacheMinutes));
                
                return $processed;
            }
        } catch (\Exception $e) {
            \Log::error("Error fetching post {$postId} from website {$website->id}: " . $e->getMessage());
        }

        return ['error' => 'Failed to fetch post'];
    }

    /**
     * Test WordPress connection
     */
    public function testConnection(string $websiteUrl): bool
    {
        return $this->resolveBaseUrl($websiteUrl) !== null;
    }

    /**
     * Resolve a working WordPress base URL.
     */
    public function resolveBaseUrl(string $websiteUrl): ?string
    {
        try {
            foreach ($this->buildCandidateUrls($websiteUrl) as $baseUrl) {
                $response = Http::get("{$baseUrl}/wp-json/wp/v2/posts", [
                    'per_page' => 1,
                ]);

                if ($response->successful()) {
                    return $baseUrl;
                }
            }
        } catch (\Exception $e) {
            \Log::error("Connection test failed for {$websiteUrl}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Normalize URL to ensure it ends without trailing slash
     */
    private function normalizeUrl(string $url): string
    {
        $url = trim($url);
        
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            $url = 'https://' . $url;
        }
        
        return rtrim($url, '/');
    }

    /**
     * Build candidate URLs to try.
     */
    private function buildCandidateUrls(string $url): array
    {
        $trimmed = trim($url);
        $candidates = [];

        if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://')) {
            $candidates[] = $this->normalizeUrl($trimmed);

            if (str_starts_with($trimmed, 'https://')) {
                $candidates[] = $this->normalizeUrl('http://' . substr($trimmed, 8));
            }
        } else {
            $candidates[] = $this->normalizeUrl('https://' . $trimmed);
            $candidates[] = $this->normalizeUrl('http://' . $trimmed);
        }

        return array_values(array_unique($candidates));
    }

    /**
     * Process post data from WordPress API
     */
    private function processPostData(array $posts, string $websiteUrl): array
    {
        return array_map(fn($post) => $this->formatPost($post, $websiteUrl), $posts);
    }

    /**
     * Process single post
     */
    private function processSinglePost(array $post, string $websiteUrl): array
    {
        return $this->formatPost($post, $websiteUrl);
    }

    /**
     * Format a single post
     */
    private function formatPost(array $post, string $websiteUrl): array
    {
        $baseUrl = $this->normalizeUrl($websiteUrl);
        
        $featuredImage = null;
        if (isset($post['_embedded']['wp:featuredmedia'][0]['source_url'])) {
            $featuredImage = $post['_embedded']['wp:featuredmedia'][0]['source_url'];
        }

        return [
            'id' => $post['id'],
            'title' => $post['title']['rendered'] ?? 'Untitled',
            'excerpt' => strip_tags($post['excerpt']['rendered'] ?? ''),
            'content' => $post['content']['rendered'] ?? '',
            'image' => $featuredImage,
            'link' => $post['link'] ?? '#',
            'publishedAt' => $post['date'] ?? null,
            'modifiedAt' => $post['modified'] ?? null,
            'author' => $post['_embedded']['author'][0]['name'] ?? 'Unknown' ?? 'Unknown',
        ];
    }

    /**
     * Clear cache for website
     */
    public function clearCache(Website $website): void
    {
        // Clear all cached posts for this website
        $keys = Cache::tags(["website_{$website->id}"])->flush();
    }
}
