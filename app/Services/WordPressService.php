<?php

namespace App\Services;

use App\Models\Article;
use Illuminate\Support\Facades\Http;

class WordPressService
{
    protected string $baseUrl;

    protected string $username;

    protected string $password;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('wordpress.url'), '/');
        $this->username = config('wordpress.username');
        $this->password = config('wordpress.app_password');
    }

    public function fetchCategories(): array
    {
        $response = Http::withBasicAuth($this->username, $this->password)
            ->get("{$this->baseUrl}/wp-json/wp/v2/categories", [
                'per_page' => 100,
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Failed to fetch WordPress categories: '.$response->body());
        }

        return $response->json();
    }

    public function createDraftPost(Article $article): int
    {
        $response = Http::withBasicAuth($this->username, $this->password)
            ->post("{$this->baseUrl}/wp-json/wp/v2/posts", [
                'title' => $article->title,
                'content' => $article->content_html,
                'excerpt' => $article->excerpt,
                'status' => 'draft',
                'categories' => $article->wp_category_id ? [$article->wp_category_id] : [],
                'meta' => [
                    'focus_keyword' => $article->focus_keyword,
                    'meta_title' => $article->meta_title,
                    'meta_description' => $article->meta_description,
                ],
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Failed to create WordPress draft: '.$response->body());
        }

        $data = $response->json();

        return $data['id'];
    }

    public function checkPostExistsBySlug(string $slug): ?int
    {
        $response = Http::withBasicAuth($this->username, $this->password)
            ->get("{$this->baseUrl}/wp-json/wp/v2/posts", [
                'slug' => $slug,
            ]);

        if ($response->failed() || empty($response->json())) {
            return null;
        }

        $posts = $response->json();

        return $posts[0]['id'] ?? null;
    }
}
