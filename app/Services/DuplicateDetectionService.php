<?php

namespace App\Services;

use App\Models\Article;

class DuplicateDetectionService
{
    public function isDuplicate(string $title, string $slug, ?int $excludeArticleId = null): bool
    {
        $query = Article::query();

        if ($excludeArticleId) {
            $query->where('id', '!=', $excludeArticleId);
        }

        $existingArticles = $query->latest()->limit(50)->get();

        foreach ($existingArticles as $article) {
            if ($this->isSimilarTitle($title, $article->title)) {
                return true;
            }

            if ($article->slug === $slug) {
                return true;
            }
        }

        return false;
    }

    protected function isSimilarTitle(string $title1, string $title2): bool
    {
        $title1 = $this->normalizeString($title1);
        $title2 = $this->normalizeString($title2);

        similar_text($title1, $title2, $percent);

        return $percent > 75;
    }

    protected function normalizeString(string $string): string
    {
        $string = strtolower($string);
        $string = preg_replace('/[^\w\s]/', '', $string);
        $string = preg_replace('/\s+/', ' ', $string);

        return trim($string);
    }
}
