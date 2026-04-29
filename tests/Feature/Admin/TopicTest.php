<?php

use App\Models\ArticleTopic;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('guests cannot access topics', function () {
    get(route('admin.topics.index'))->assertRedirect('/login');
    get(route('admin.topics.create'))->assertRedirect('/login');
    get(route('admin.topics.edit', 1))->assertRedirect('/login');
});

test('authenticated users can view topics index', function () {
    ArticleTopic::factory()->create(['topic_title' => 'Test Topic']);

    actingAs($this->user)
        ->get(route('admin.topics.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/Topics/Index'));
});

test('authenticated users can create topics', function () {
    actingAs($this->user)
        ->post(route('admin.topics.store'), [
            'topic_title' => 'New Topic',
            'focus_keyword' => 'new-keyword',
            'description' => 'A test topic',
            'status' => 'pending',
        ])
        ->assertRedirect(route('admin.topics.index'));

    assertDatabaseHas('article_topics', [
        'topic_title' => 'New Topic',
        'focus_keyword' => 'new-keyword',
    ]);
});

test('authenticated users can edit topics', function () {
    $topic = ArticleTopic::factory()->create(['topic_title' => 'Test Topic']);

    actingAs($this->user)
        ->get(route('admin.topics.edit', $topic))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/Topics/Edit'));
});

test('authenticated users can update topics', function () {
    $topic = ArticleTopic::factory()->create(['topic_title' => 'Old Title']);

    actingAs($this->user)
        ->put(route('admin.topics.update', $topic), [
            'topic_title' => 'Updated Title',
            'focus_keyword' => 'updated-keyword',
            'description' => 'Updated description',
            'status' => 'used',
        ])
        ->assertRedirect(route('admin.topics.index'));

    assertDatabaseHas('article_topics', [
        'id' => $topic->id,
        'topic_title' => 'Updated Title',
        'status' => 'used',
    ]);
});

test('authenticated users can delete topics', function () {
    $topic = ArticleTopic::factory()->create(['topic_title' => 'Test Topic']);

    actingAs($this->user)
        ->delete(route('admin.topics.destroy', $topic))
        ->assertRedirect();

    assertDatabaseMissing('article_topics', ['id' => $topic->id]);
});
