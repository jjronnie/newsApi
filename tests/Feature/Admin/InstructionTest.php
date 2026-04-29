<?php

use App\Models\AiInstruction;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('guests cannot access instructions', function () {
    get(route('admin.instructions.index'))->assertRedirect('/login');
    get(route('admin.instructions.edit', 1))->assertRedirect('/login');
});

test('authenticated users can view instructions index', function () {
    AiInstruction::factory()->create(['name' => 'test_instruction']);

    actingAs($this->user)
        ->get(route('admin.instructions.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/Instructions/Index'));
});

test('authenticated users can edit instructions', function () {
    $instruction = AiInstruction::factory()->create(['name' => 'test_instruction']);

    actingAs($this->user)
        ->get(route('admin.instructions.edit', $instruction))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/Instructions/Edit'));
});

test('authenticated users can update instructions', function () {
    $instruction = AiInstruction::factory()->create(['name' => 'old_name']);

    actingAs($this->user)
        ->put(route('admin.instructions.update', $instruction), [
            'name' => 'updated_name',
            'content' => 'Updated content',
            'type' => 'system',
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.instructions.index'));

    assertDatabaseHas('ai_instructions', [
        'id' => $instruction->id,
        'name' => 'updated_name',
        'content' => 'Updated content',
    ]);
});

test('getContentByName returns correct instruction', function () {
    AiInstruction::factory()->create([
        'name' => 'system_prompt',
        'content' => 'Test system prompt',
        'is_active' => true,
    ]);

    $content = AiInstruction::getContentByName('system_prompt');
    expect($content)->toBe('Test system prompt');
});

test('getContentByName returns empty for inactive instruction', function () {
    AiInstruction::factory()->create([
        'name' => 'system_prompt',
        'content' => 'Test system prompt',
        'is_active' => false,
    ]);

    $content = AiInstruction::getContentByName('system_prompt');
    expect($content)->toBe('');
});
