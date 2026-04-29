<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentConversation;
use App\Models\AgentConversationMessage;
use App\Models\Article;
use App\Models\User;
use App\Services\LoloChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ArticleChatController extends Controller
{
    public function __construct(protected LoloChatService $chatService) {}

    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $articleId = $request->integer('article') ?: null;
        $conversationId = $request->string('conversation')->toString() ?: null;

        $article = null;
        if ($articleId) {
            $article = Article::find($articleId);

            if ($article && $article->wp_post_id) {
                return Redirect::route('admin.articles.show', $articleId);
            }
        }

        $conversation = null;
        if ($conversationId) {
            $conversation = AgentConversation::query()
                ->where('id', $conversationId)
                ->where('user_id', $user->id)
                ->with(['messages' => fn ($query) => $query->orderBy('created_at')])
                ->first();
        }

        return Inertia::render('Admin/Articles/Chat', [
            'article' => $article,
            'conversationId' => $conversation?->id,
            'conversationTitle' => $conversation?->title,
            'messages' => $conversation
                ? $conversation->messages
                    ->whereIn('role', ['user', 'assistant'])
                    ->map(fn (AgentConversationMessage $message) => [
                        'id' => $message->id,
                        'role' => $message->role,
                        'content' => $message->content,
                        'created_at' => $message->created_at?->toIso8601String(),
                    ])
                    ->values()
                    ->all()
                : [],
        ]);
    }

    public function conversations(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $conversations = AgentConversation::query()
            ->where('user_id', $user->id)
            ->latest('updated_at')
            ->limit(50)
            ->get(['id', 'title', 'updated_at']);

        return response()->json([
            'conversations' => $conversations,
        ]);
    }

    public function destroyConversation(Request $request, AgentConversation $conversation): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($conversation->user_id === $user->id, 404);

        $conversation->messages()->delete();
        $conversation->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => ['required', 'string'],
            'article_id' => ['nullable', 'exists:articles,id'],
            'history' => ['nullable', 'array'],
            'conversation_id' => ['nullable', 'string'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $message = trim((string) $request->input('message'));
        $history = $request->input('history', []);
        $conversationRecord = $this->findOrCreateConversation(
            $request->input('conversation_id'),
            $message,
            $user
        );

        $article = null;
        if ($request->filled('article_id')) {
            $article = Article::find($request->integer('article_id'));
        }

        try {
            $result = $this->chatService->converse($user, $message, $history, $article);

            if (($result['should_update_article'] ?? false) && $article && ! $article->wp_post_id) {
                $article->update($result['article_updates']);
            }

            $this->storeConversationPair($conversationRecord, $message, $result['response'], [
                'mode' => $article ? 'edit' : 'chat',
                'article_id' => $article?->id,
                'ready_to_generate' => $result['ready_to_generate'],
                'needs_more_info' => $result['needs_more_info'],
                'follow_up_questions' => $result['follow_up_questions'],
                'generation_brief' => $result['generation_brief'],
                'article_updates' => $result['article_updates'],
            ]);

            $this->updateConversationTitle($conversationRecord, $result['conversation_title'], $message);

            return response()->json([
                'success' => true,
                'response' => $result['response'],
                'updates' => $result['article_updates'] ?: null,
                'conversation_id' => $conversationRecord->id,
                'conversation_title' => $conversationRecord->fresh()->title,
                'ready_to_generate' => $result['ready_to_generate'],
                'needs_more_info' => $result['needs_more_info'],
                'follow_up_questions' => $result['follow_up_questions'],
                'generation_brief' => $result['generation_brief'],
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'response' => $this->presentException($e),
                'conversation_id' => $conversationRecord->id,
            ], 422);
        }
    }

    public function generateWithChat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => ['nullable', 'string'],
            'history' => ['nullable', 'array'],
            'conversation_id' => ['nullable', 'string'],
            'generation_brief' => ['required_without:message', 'nullable', 'string'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $brief = trim((string) ($request->input('generation_brief') ?: $request->input('message')));
        $history = $request->input('history', []);
        $conversationRecord = $this->findOrCreateConversation(
            $request->input('conversation_id'),
            $brief,
            $user
        );

        try {
            $data = $this->chatService->generateArticle($user, $brief, $history);
            $normalized = $this->normalizeArticlePayload($data);

            $article = Article::create([
                'title' => $normalized['title'],
                'slug' => $normalized['slug'],
                'focus_keyword' => $normalized['focus_keyword'],
                'meta_title' => $normalized['meta_title'],
                'meta_description' => $normalized['meta_description'],
                'excerpt' => $normalized['excerpt'],
                'content_html' => $normalized['content_html'],
                'faq_json' => $normalized['faq_json'],
                'outline_json' => $normalized['outline_json'],
                'tag_suggestions_json' => $normalized['tag_suggestions_json'],
                'category_suggestions_json' => $normalized['category_suggestions_json'],
                'status' => 'generated',
                'generated_at' => now(),
                'ai_provider' => 'groq',
                'ai_model' => config('ai.default_model', 'unknown'),
                'ai_prompt_version' => '3.0',
            ]);

            $response = "Done, {$user->name}. I created the draft and it's ready for review.";

            $this->storeConversationPair($conversationRecord, $brief, $response, [
                'mode' => 'generate',
                'article_id' => $article->id,
            ]);

            return response()->json([
                'success' => true,
                'response' => $response,
                'article_id' => $article->id,
                'article' => $article,
                'conversation_id' => $conversationRecord->id,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'response' => $this->presentException($e),
                'conversation_id' => $conversationRecord->id,
            ], 422);
        }
    }

    protected function findOrCreateConversation(?string $conversationId, string $message, User $user): AgentConversation
    {
        if ($conversationId) {
            $conversation = AgentConversation::query()
                ->where('id', $conversationId)
                ->where('user_id', $user->id)
                ->first();

            if ($conversation) {
                return $conversation;
            }
        }

        return AgentConversation::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'title' => Str::limit($message, 80, ''),
        ]);
    }

    protected function storeConversationPair(AgentConversation $conversation, string $userMessage, string $assistantMessage, array $meta = []): void
    {
        $payload = [
            'attachments' => '[]',
            'tool_calls' => '[]',
            'tool_results' => '[]',
            'usage' => '{}',
            'meta' => json_encode($meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}',
        ];

        AgentConversationMessage::create([
            'id' => (string) Str::uuid(),
            'conversation_id' => $conversation->id,
            'user_id' => $conversation->user_id,
            'agent' => 'lolo',
            'role' => 'user',
            'content' => $userMessage,
            ...$payload,
        ]);

        AgentConversationMessage::create([
            'id' => (string) Str::uuid(),
            'conversation_id' => $conversation->id,
            'user_id' => $conversation->user_id,
            'agent' => 'lolo',
            'role' => 'assistant',
            'content' => $assistantMessage,
            ...$payload,
        ]);

        $conversation->touch();
    }

    protected function updateConversationTitle(AgentConversation $conversation, ?string $title, string $fallback): void
    {
        $conversation->update([
            'title' => Str::limit(trim($title ?: $fallback), 120, ''),
        ]);
    }

    protected function normalizeArticlePayload(array $data): array
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

    protected function presentException(\Throwable $e): string
    {
        return $e->getMessage() !== ''
            ? $e->getMessage()
            : 'An unexpected error happened while Lolo was processing your request.';
    }
}
