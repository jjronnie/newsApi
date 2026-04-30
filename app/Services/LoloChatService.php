<?php

namespace App\Services;

use App\Ai\Agents\ContentWriterAgent;
use App\Models\Article;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class LoloChatService
{
    public function converse(User $user, string $message, array $history = [], ?Article $article = null): array
    {
        // Limit history to last 5 messages to avoid 413 errors
        $history = array_slice($history, -5);

        $prompt = $article
            ? $this->articleEditingPrompt($user, $message, $history, $article)
            : $this->contentPlanningPrompt($user, $message, $history);

        $response = (new ContentWriterAgent('', []))->prompt($prompt);
        $payload = $this->decodeJson($response->text);

        return [
            'response' => (string) ($payload['response'] ?? 'I am ready to help.'),
            'ready_to_generate' => (bool) ($payload['ready_to_generate'] ?? false),
            'needs_more_info' => (bool) ($payload['needs_more_info'] ?? false),
            'follow_up_questions' => array_values(array_filter(Arr::wrap($payload['follow_up_questions'] ?? []))),
            'conversation_title' => (string) ($payload['conversation_title'] ?? Str::limit($message, 80, '')),
            'generation_brief' => $payload['generation_brief'] ?? null,
            'should_update_article' => (bool) ($payload['should_update_article'] ?? false),
            'article_updates' => $this->normalizeArticleUpdates($payload['article_updates'] ?? []),
            'raw' => $payload,
        ];
    }

    public function generateArticle(User $user, string $brief, array $history = []): array
    {
        // Limit history to last 5 messages to avoid 413 errors
        $history = array_slice($history, -5);

        $prompt = $this->articleGenerationPrompt($user, $brief, $history);
        $response = (new ContentWriterAgent($brief, []))->prompt($prompt);

        return $this->decodeJson($response->text);
    }

    protected function contentPlanningPrompt(User $user, string $message, array $history): string
    {
        return str_replace(
            ['{user_name}', '{conversation}', '{message}'],
            [$user->name, $this->historyTranscript($history), $message],
            <<<'PROMPT'
You are Lolo, a professional AI content strategist and writing partner for {user_name}.

You are in planning mode, not draft-writing mode. Be natural, thoughtful, and concise.
Your goal is to help the user shape a strong article request through real conversation.

Rules:
- Do not say you can generate a draft on every message.
- Think about what the user asked and respond naturally.
- Ask follow-up questions only when they are genuinely needed.
- If the user already provided enough detail to draft a solid article, say so.
- If the user asks for research, structure, headlines, angle ideas, or refinement, help with that naturally.
- If the user explicitly wants the article written and enough detail is available, set ready_to_generate to true and provide a strong generation_brief.
- Prefer practical detail for Uganda and East Africa context when the topic suggests it.
- Never return markdown fences.

Return valid JSON only with this shape:
{
  "response": "natural assistant reply",
  "ready_to_generate": true,
  "needs_more_info": false,
  "follow_up_questions": ["", ""],
  "conversation_title": "short conversation title",
  "generation_brief": "complete writing brief to use for final article generation"
}

Conversation so far:
{conversation}

Latest user message:
{message}
PROMPT
        );
    }

    protected function articleEditingPrompt(User $user, string $message, array $history, Article $article): string
    {
        return str_replace(
            ['{user_name}', '{conversation}', '{message}', '{article_title}', '{article_excerpt}', '{article_content}'],
            [
                $user->name,
                $this->historyTranscript($history),
                $message,
                $article->title,
                $article->excerpt,
                Str::limit(strip_tags($article->content_html), 1200),
            ],
            <<<'PROMPT'
You are Lolo, a professional AI editor helping {user_name} improve an existing article.

Be conversational first. If the user is asking a question, advise naturally.
Only propose direct article updates when the request clearly asks for them.

Return valid JSON only with this shape:
{
  "response": "natural assistant reply",
  "should_update_article": false,
  "article_updates": {
    "title": "",
    "slug": "",
    "focus_keyword": "",
    "meta_title": "",
    "meta_description": "",
    "excerpt": "",
    "content_html": ""
  },
  "conversation_title": "short conversation title"
}

Current article title:
{article_title}

Current excerpt:
{article_excerpt}

Current article content:
{article_content}

Conversation so far:
{conversation}

Latest user message:
{message}
PROMPT
        );
    }

    protected function articleGenerationPrompt(User $user, string $brief, array $history): string
    {
        return str_replace(
            ['{user_name}', '{conversation}', '{brief}'],
            [$user->name, $this->historyTranscript($history), $brief],
            <<<'PROMPT'
You are Lolo, a professional content writer creating a polished article for {user_name}.

Write the final article now.
- Be accurate, structured, readable, and professional.
- Use concrete details where the brief suggests local context.
- Create a clean slug.
- Keep meta title between 50 and 60 characters.
- Keep meta description between 140 and 160 characters.
- Include at least 5 FAQ entries.
- Return valid JSON only.

Return JSON with this exact shape:
{
  "title": "",
  "slug": "",
  "focus_keyword": "",
  "meta_title": "",
  "meta_description": "",
  "excerpt": "",
  "category_suggestions_json": ["", ""],
  "tag_suggestions_json": ["", ""],
  "outline_json": [
    {"heading": "", "subheadings": ["", ""]}
  ],
  "faq_json": [
    {"question": "", "answer": ""}
  ],
  "content_html": ""
}

Conversation so far:
{conversation}

Writing brief:
{brief}
PROMPT
        );
    }

    protected function historyTranscript(array $history): string
    {
        return collect($history)
            ->filter(fn ($message) => isset($message['role'], $message['content']))
            ->map(fn ($message) => ($message['role'] === 'user' ? 'User' : 'Lolo').': '.Str::limit(strip_tags($message['content']), 1000))
            ->implode("\n");
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
            throw new \RuntimeException('Lolo returned invalid JSON: '.json_last_error_msg().'. Raw response: '.Str::limit($text, 300));
        }

        // Ensure content_html is not empty - if it is, the response was likely truncated
        if (isset($payload['content_html']) && empty(trim(strip_tags($payload['content_html'])))) {
            throw new \RuntimeException('Lolo returned empty content. The response may have been truncated. Please try again.');
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

    protected function normalizeArticleUpdates(array $updates): array
    {
        return collect($updates)
            ->only([
                'title',
                'slug',
                'focus_keyword',
                'meta_title',
                'meta_description',
                'excerpt',
                'content_html',
            ])
            ->filter(fn ($value) => filled($value))
            ->all();
    }
}
