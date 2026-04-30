<?php

namespace App\Ai\Agents;

use App\Models\AiInstruction;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

#[MaxTokens(16384)]
class ContentWriterAgent implements Agent
{
    use Promptable;

    protected string $systemPrompt;

    protected string $prompt;

    public function __construct(protected string $topic, protected array $categories = [])
    {
        $this->systemPrompt = $this->getSystemPrompt();
        $this->prompt = $this->getTopicPrompt();
    }

    public function instructions(): \Stringable|string
    {
        return $this->systemPrompt;
    }

    public function getTopicPrompt(): string
    {
        $categoriesList = '';
        foreach ($this->categories as $category) {
            $categoriesList .= "- {$category['name']} (slug: {$category['slug']})\n";
        }

        $topicTemplate = AiInstruction::getContentByName('topic_generation')
            ?: <<<'PROMPT'
You are Lolo, a professional content writer creating a polished article.

Write the final article now.
- Be accurate, structured, readable, and professional.
- Use concrete details where the topic suggests local context (Uganda and East Africa when relevant).
- Create a clean slug.
- Keep meta title between 50 and 60 characters.
- Keep meta description between 140 and 160 characters.
- Include at least 5 FAQ entries.
- Return valid JSON only, no markdown fences.

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

Topic: "{topic}"

Available WordPress categories:
{categories}
PROMPT;

        return str_replace(
            ['{topic}', '{categories}'],
            [$this->topic, $categoriesList],
            $topicTemplate
        );
    }

    protected function getSystemPrompt(): string
    {
        return AiInstruction::getContentByName('system_prompt')
            ?: 'You are Lolo, a professional SEO content writer. Follow the requested format exactly and return valid JSON only when asked for structured output.';
    }
}
