<?php

use App\Ai\Agents\ContentWriterAgent;
use App\Models\AgentConversation;
use App\Models\Article;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    $this->user = User::factory()->create([
        'email_verified_at' => now(),
    ]);
});

test('authenticated users can open the chat page and see stored messages', function () {
    $conversation = AgentConversation::create([
        'user_id' => $this->user->id,
        'title' => 'Existing chat',
    ]);

    $conversation->messages()->createMany([
        [
            'id' => (string) str()->uuid(),
            'user_id' => $this->user->id,
            'agent' => ContentWriterAgent::class,
            'role' => 'user',
            'content' => 'Hello',
            'attachments' => '[]',
            'tool_calls' => '[]',
            'tool_results' => '[]',
            'usage' => '{}',
            'meta' => '{}',
        ],
        [
            'id' => (string) str()->uuid(),
            'user_id' => $this->user->id,
            'agent' => ContentWriterAgent::class,
            'role' => 'assistant',
            'content' => 'Hello back',
            'attachments' => '[]',
            'tool_calls' => '[]',
            'tool_results' => '[]',
            'usage' => '{}',
            'meta' => '{}',
        ],
    ]);

    actingAs($this->user)
        ->get(route('admin.articles.chat.index', ['conversation' => $conversation->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Articles/Chat')
            ->where('conversationId', $conversation->id)
            ->has('messages', 2)
        );
});

test('planning chat responds naturally and can mark itself ready to generate', function () {
    ContentWriterAgent::fake([
        json_encode([
            'response' => 'That topic is strong. I already have enough context to draft it for Uganda unless you want a specific audience or tone.',
            'ready_to_generate' => true,
            'needs_more_info' => false,
            'follow_up_questions' => [],
            'conversation_title' => 'MTN virtual card article',
            'generation_brief' => 'Write a practical guide for Uganda explaining how to get and use an MTN virtual card for online payments.',
        ], JSON_THROW_ON_ERROR),
    ]);

    actingAs($this->user)
        ->postJson(route('admin.articles.chat.send'), [
            'message' => 'write about how to get an MTN virtual card used for online payments in Uganda',
            'history' => [],
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('ready_to_generate', true)
        ->assertJsonPath('conversation_title', 'MTN virtual card article');

    expect(AgentConversation::query()->count())->toBe(1);

    assertDatabaseHas('agent_conversation_messages', [
        'role' => 'assistant',
        'content' => 'That topic is strong. I already have enough context to draft it for Uganda unless you want a specific audience or tone.',
    ]);
});

test('article edit chat can update the draft article', function () {
    $article = Article::create([
        'title' => 'Original title',
        'slug' => 'original-title',
        'focus_keyword' => 'laravel ai',
        'meta_title' => 'Original meta title that is exactly fifty-five letters wide',
        'meta_description' => str_repeat('A', 150),
        'excerpt' => 'Original excerpt',
        'content_html' => '<p>Old content</p>',
        'status' => 'generated',
        'generated_at' => now(),
        'ai_provider' => 'groq',
        'ai_model' => 'llama-3.1-70b-versatile',
    ]);

    ContentWriterAgent::fake([
        json_encode([
            'response' => 'I updated the draft for you.',
            'should_update_article' => true,
            'article_updates' => [
                'title' => 'Updated title',
                'excerpt' => 'Updated excerpt',
                'content_html' => '<p>Updated content</p>',
            ],
        ], JSON_THROW_ON_ERROR),
    ]);

    actingAs($this->user)
        ->postJson(route('admin.articles.chat.send'), [
            'message' => 'Please rewrite this draft.',
            'article_id' => $article->id,
            'history' => [],
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('updates.title', 'Updated title');

    assertDatabaseHas('articles', [
        'id' => $article->id,
        'title' => 'Updated title',
        'excerpt' => 'Updated excerpt',
    ]);
});

test('users can delete their stored conversations', function () {
    $conversation = AgentConversation::create([
        'user_id' => $this->user->id,
        'title' => 'Delete me',
    ]);

    $conversation->messages()->create([
        'id' => (string) str()->uuid(),
        'user_id' => $this->user->id,
        'agent' => 'lolo',
        'role' => 'assistant',
        'content' => 'Temporary message',
        'attachments' => '[]',
        'tool_calls' => '[]',
        'tool_results' => '[]',
        'usage' => '{}',
        'meta' => '{}',
    ]);

    actingAs($this->user)
        ->deleteJson(route('admin.articles.chat.conversations.destroy', $conversation))
        ->assertOk()
        ->assertJsonPath('success', true);

    assertDatabaseMissing('agent_conversations', ['id' => $conversation->id]);
    assertDatabaseMissing('agent_conversation_messages', ['conversation_id' => $conversation->id]);
});
