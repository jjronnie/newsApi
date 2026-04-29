<?php

use App\Ai\Agents\ContentWriterAgent;
use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Services\AiContentWriter;

beforeEach(function () {
    $fakeJson = '{"title":"Test Article","slug":"test-article","focus_keyword":"test","meta_title":"Test Article | TheTechTower.com - Complete Guide 2026","meta_description":"This is a test article description that is between 140 and 160 characters long to satisfy Yoast SEO requirements for meta descriptions here.","excerpt":"Test excerpt","category_suggestions":["tutorials"],"tag_suggestions":["test","ai"],"reading_time_minutes":5,"outline":[{"heading":"Introduction","subheadings":["What is this","Why it matters"]}],"content_html":"<h2>Introduction</h2><p>Test content</p>","faq":[{"question":"What is this?","answer":"This is a test."},{"question":"Why it matters?","answer":"It matters for testing."},{"question":"How to start?","answer":"Start here."},{"question":"What next?","answer":"Continue learning."},{"question":"Final question?","answer":"Final answer."}]}';

    ContentWriterAgent::fake([$fakeJson]);
});

it('can generate content and store article', function () {
    $writer = new AiContentWriter('Test Topic', [
        ['name' => 'Tutorials', 'slug' => 'tutorials'],
    ]);

    $article = $writer->generateAndStore();

    expect($article)->toBeInstanceOf(Article::class);
    expect($article->title)->toBe('Test Article');
    expect($article->slug)->toBe('test-article');
    expect($article->status)->toBe(ArticleStatus::GENERATED);
    expect($article->focus_keyword)->toBe('test');
    expect(strlen($article->meta_description))->toBeBetween(140, 160);
});

it('returns success false when JSON is invalid', function () {
    ContentWriterAgent::fake(['invalid json']);

    $writer = new AiContentWriter('Test');
    $result = $writer->generate();

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toContain('Invalid JSON response from AI');
});

it('validates meta title length', function () {
    $fakeJson = '{"title":"Test","slug":"test","focus_keyword":"test","meta_title":"Short","meta_description":"This is a test article description that is between 140 and 160 characters long to satisfy Yoast SEO requirements for meta descriptions.","excerpt":"Test","category_suggestions":["tutorials"],"tag_suggestions":["test"],"reading_time_minutes":5,"outline":[],"content_html":"<p>Test</p>","faq":[{"question":"Q1?","answer":"A1"},{"question":"Q2?","answer":"A2"},{"question":"Q3?","answer":"A3"},{"question":"Q4?","answer":"A4"},{"question":"Q5?","answer":"A5"}]}';

    ContentWriterAgent::fake([$fakeJson]);

    $writer = new AiContentWriter('Test');
    $result = $writer->generate();

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toContain('Meta title must be between 50 and 60 characters');
});

it('validates required fields', function () {
    // Only provide title - normalizePayload will set defaults for missing fields
    // but meta_title will be empty/short and fail validation first
    ContentWriterAgent::fake(['{"title":"Test"}']);

    $writer = new AiContentWriter('Test');
    $result = $writer->generate();

    expect($result['success'])->toBeFalse();
    // The validation will fail on meta_title length before checking missing fields
    expect($result['error'])->toContain('Meta title must be between 50 and 60 characters');
});
