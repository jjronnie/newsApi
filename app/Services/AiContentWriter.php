<?php

namespace App\Services;

use App\Ai\Agents\ContentWriterAgent;
use App\Models\Article;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiContentWriter
{
    protected ContentWriterAgent $agent;

    public function __construct(string $topic, array $categories = [])
    {
        $this->agent = new ContentWriterAgent($topic, $categories);
    }

    public function generate(): array
    {
        $startTime = microtime(true);

        try {
            $response = $this->agent->prompt($this->agent->getTopicPrompt());
            $data = $this->decodeJson($response->text);
            $data = $this->normalizePayload($data);
            $this->validateResponse($data);

            return [
                'success' => true,
                'data' => $data,
                'duration_ms' => (int) ((microtime(true) - $startTime) * 1000),
            ];
        } catch (\Exception $e) {
            Log::error('AI Content Generation Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'duration_ms' => (int) ((microtime(true) - $startTime) * 1000),
            ];
        }
    }

    public function generateAndStore(): Article
    {
        $result = $this->generate();

        if (! $result['success']) {
            throw new \RuntimeException('AI generation failed: '.$result['error']);
        }

        $data = $result['data'];

        $article = Article::create([
            'title' => $data['title'],
            'slug' => $data['slug'],
            'focus_keyword' => $data['focus_keyword'],
            'meta_title' => $data['meta_title'],
            'meta_description' => $data['meta_description'],
            'excerpt' => $data['excerpt'],
            'content_html' => $data['content_html'],
            'faq_json' => $data['faq_json'],
            'outline_json' => $data['outline_json'],
            'tag_suggestions_json' => $data['tag_suggestions_json'],
            'category_suggestions_json' => $data['category_suggestions_json'],
            'status' => 'generated',
            'generated_at' => now(),
            'ai_provider' => 'groq',
            'ai_model' => config('ai.default_model', 'unknown'),
            'ai_prompt_version' => '1.0',
        ]);

        return $article;
    }

    protected function validateResponse(array $data): void
    {
        $requiredFields = [
            'title',
            'slug',
            'focus_keyword',
            'meta_title',
            'meta_description',
            'excerpt',
            'content_html',
            'faq_json',
        ];

        foreach ($requiredFields as $field) {
            if (! isset($data[$field])) {
                throw new \RuntimeException("Missing required field: {$field}");
            }
        }

        if (strlen($data['meta_title']) < 50 || strlen($data['meta_title']) > 60) {
            throw new \RuntimeException('Meta title must be between 50 and 60 characters');
        }

        if (strlen($data['meta_description']) < 140 || strlen($data['meta_description']) > 160) {
            throw new \RuntimeException('Meta description must be between 140 and 160 characters');
        }

        if (count($data['faq_json']) < 5) {
            throw new \RuntimeException('FAQ must contain at least 5 questions');
        }
    }

    protected function decodeJson(string $text): array
    {
        // Strip markdown code fences
        $text = preg_replace('/```[a-z]*\s*|```\s*$/m', '', $text);

        // Extract JSON object if embedded in other text
        if (preg_match('/\s*(\{.*\})\s*/s', $text, $matches)) {
            $text = $matches[1];
        }

        // Remove invalid control characters (except whitespace that's valid in JSON)
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);

        // Try to fix truncated JSON by closing unclosed structures
        $text = $this->repairJson($text);

        $payload = json_decode($text, true);

        if (! is_array($payload) || json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON response from AI: '.json_last_error_msg().'. Raw response: '.Str::limit($text, 300));
        }

        // Ensure content_html is not empty - if it is, the response was likely truncated
        if (isset($payload['content_html']) && empty(trim(strip_tags($payload['content_html'])))) {
            throw new \RuntimeException('AI returned empty content_html. The response may have been truncated. Please try again.');
        }

        return $payload;
    }

    protected function repairJson(string $text): string
    {
        // Count open vs closed brackets/braces to detect truncation
        $openBraces = substr_count($text, '{');
        $closedBraces = substr_count($text, '}');
        $openBrackets = substr_count($text, '[');
        $closedBrackets = substr_count($text, ']');

        // Remove trailing comma before closing brace/bracket
        $text = preg_replace('/,\s*([}\]])/', '$1', $text);

        // Add missing closing braces/brackets
        if ($openBrackets > $closedBrackets) {
            $text .= str_repeat(']', $openBrackets - $closedBrackets);
        }
        if ($openBraces > $closedBraces) {
            $text .= str_repeat('}', $openBraces - $closedBraces);
        }

        // Close any unclosed strings at the end
        $lastQuote = strrpos($text, '"');
        if ($lastQuote !== false && substr_count(substr($text, 0, $lastQuote + 1), '"') % 2 !== 0) {
            $text .= '"';
        }

        return $text;
    }

    protected function normalizePayload(array $data): array
    {
        $title = trim((string) ($data['title'] ?? 'Untitled'));

        return [
            'title' => $title,
            'slug' => $data['slug'] ?? Str::slug($title),
            'focus_keyword' => $data['focus_keyword'] ?? $data['key'] ?? $title,
            'meta_title' => trim((string) ($data['meta_title'] ?? '')),
            'meta_description' => trim((string) ($data['meta_description'] ?? '')),
            'excerpt' => trim((string) ($data['excerpt'] ?? '')),
            'content_html' => trim((string) ($data['content_html'] ?? '')),
            'faq_json' => $data['faq_json'] ?? $data['faq'] ?? [],
            'outline_json' => $data['outline_json'] ?? $data['outline'] ?? [],
            'tag_suggestions_json' => $data['tag_suggestions_json'] ?? $data['tag_suggestions'] ?? [],
            'category_suggestions_json' => $data['category_suggestions_json'] ?? $data['category_suggestions'] ?? [],
        ];
    }
}
