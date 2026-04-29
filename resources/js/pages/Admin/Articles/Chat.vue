<script setup lang="ts">
import { computed, nextTick, onMounted, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  conversations as articleChatConversations,
  generate as generateArticleFromChat,
  index as articleChatIndex,
  send as sendArticleChat,
} from '@/routes/admin/articles/chat';
import { destroy as destroyConversationRoute } from '@/routes/admin/articles/chat/conversations';
import { index as articleIndex, show as showArticle } from '@/routes/admin/articles';

interface Article {
  id: number;
  title: string;
  wp_post_id?: number | null;
}

interface ChatMessage {
  id?: string;
  role: 'user' | 'assistant';
  content: string;
  error?: boolean;
  created_at?: string;
  readyToGenerate?: boolean;
  generationBrief?: string | null;
}

interface Conversation {
  id: string;
  title: string;
  updated_at: string;
}

interface Props {
  article?: Article;
  conversationId?: string | null;
  conversationTitle?: string | null;
  messages?: ChatMessage[];
}

const props = defineProps<Props>();
const page = usePage();

const messages = ref<ChatMessage[]>([]);
const userInput = ref('');
const loading = ref(false);
const deletingConversationId = ref<string | null>(null);
const showDeleteModal = ref(false);
const conversationToDelete = ref<{ id: string; title: string } | null>(null);
const showSuccessMessage = ref(false);
const successMessage = ref('');
const chatContainer = ref<HTMLElement | null>(null);
const conversations = ref<Conversation[]>([]);
const showConversations = ref(true);
const selectedArticleId = ref<number | null>(props.article?.id ?? null);
const activeConversationId = ref<string | null>(props.conversationId ?? null);
const activeConversationTitle = ref<string | null>(props.conversationTitle ?? null);
const activeDropdown = ref<string | null>(null);
const userName = computed(() => String(page.props.auth?.user?.name ?? 'there'));

onMounted(async () => {
  if (props.messages && props.messages.length > 0) {
    messages.value = props.messages;
  } else if (props.article) {
    messages.value = [{
      role: 'assistant',
      content: `Hello ${userName.value}, I'm Lolo. I'm ready to help you improve "${props.article.title}". Tell me what you want to change and I'll work through it with you.`,
    }];
  } else {
    messages.value = [{
      role: 'assistant',
      content: `Hello ${userName.value}, I'm Lolo. I can help you research, shape, write, and refine articles naturally. Start with a topic, a draft idea, or a question.`,
    }];
  }

  await loadConversations();
  await scrollToBottom();
});

const canGenerateFromLatestAssistantMessage = computed(() => {
  const latestAssistantMessage = [...messages.value].reverse().find((message) => message.role === 'assistant');

  return Boolean(latestAssistantMessage?.readyToGenerate && latestAssistantMessage.generationBrief);
});

const latestGenerationBrief = computed(() => {
  const latestAssistantMessage = [...messages.value].reverse().find((message) => message.role === 'assistant' && message.readyToGenerate);

  return latestAssistantMessage?.generationBrief ?? null;
});

const loadConversations = async () => {
  try {
    const response = await fetch(articleChatConversations.url(), {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    });

    if (!response.ok) {
      return;
    }

    const data = await response.json();
    conversations.value = data.conversations ?? [];
  } catch {
    // Keep the chat usable even if the sidebar list fails to load.
  }
};

const sendMessage = async () => {
  if (!userInput.value.trim() || loading.value) {
    return;
  }

  const userMessage = userInput.value.trim();

  messages.value.push({
    role: 'user',
    content: userMessage,
  });

  userInput.value = '';
  loading.value = true;
  await scrollToBottom();

  try {
    const payload: Record<string, unknown> = {
      message: userMessage,
      // Only send last 5 messages to avoid 413 errors
      history: messages.value
        .filter((message) => !message.error)
        .slice(-5, -1)
        .map((msg) => ({
          ...msg,
          content: msg.content.replace(/<[^>]*>/g, ''), // Strip HTML from history
        })),
      conversation_id: activeConversationId.value,
    };

    if (selectedArticleId.value) {
      payload.article_id = selectedArticleId.value;
    }

    const response = await fetch(
      selectedArticleId.value ? sendArticleChat.url() : sendArticleChat.url(),
      {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify(payload),
      },
    );

    const data = await response.json();

    if (!response.ok || !data.success) {
      messages.value.push({
        role: 'assistant',
        content: data.response ?? 'Lolo could not process that message.',
        error: true,
      });

      return;
    }

    activeConversationId.value = data.conversation_id ?? activeConversationId.value;
    activeConversationTitle.value = data.conversation_title ?? activeConversationTitle.value;

    messages.value.push({
      role: 'assistant',
      content: data.response.replace(/<[^>]*>/g, ''),
      readyToGenerate: Boolean(data.ready_to_generate && data.generation_brief),
      generationBrief: data.generation_brief ?? null,
    });

    if (data.updates && selectedArticleId.value) {
      messages.value.push({
        role: 'assistant',
        content: 'I have applied the requested draft updates.',
      });
    }

    await loadConversations();
  } catch (error) {
    messages.value.push({
      role: 'assistant',
      content: formatNetworkError(error),
      error: true,
    });
  } finally {
    loading.value = false;
    await scrollToBottom();
  }
};

const generateDraft = async () => {
  if (!latestGenerationBrief.value || loading.value) {
    return;
  }

  loading.value = true;

  messages.value.push({
    role: 'assistant',
    content: 'Working on the draft now...',
  });

  await scrollToBottom();

  try {
    const response = await fetch(generateArticleFromChat.url(), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: JSON.stringify({
        generation_brief: latestGenerationBrief.value,
        history: messages.value.filter((message) => !message.error),
        conversation_id: activeConversationId.value,
      }),
    });

    const data = await response.json();

    if (!response.ok || !data.success) {
      messages.value.push({
        role: 'assistant',
        content: data.response ?? 'Lolo could not generate the draft.',
        error: true,
      });

      return;
    }

    activeConversationId.value = data.conversation_id ?? activeConversationId.value;
    messages.value.push({
      role: 'assistant',
      content: data.response,
    });

    await loadConversations();

    if (data.article_id) {
      const articleUrl = showArticle.url(data.article_id);
      messages.value.push({
        role: 'assistant',
        content: `Article generated successfully! You can view it here: ${window.location.origin}${articleUrl}`,
      });
    }
  } catch (error) {
    messages.value.push({
      role: 'assistant',
      content: formatNetworkError(error),
      error: true,
    });
  } finally {
    loading.value = false;
    await scrollToBottom();
  }
};

const startNewChat = () => {
  router.visit(articleChatIndex(selectedArticleId.value ? { query: { article: selectedArticleId.value } } : undefined));
};

const openConversation = (conversationId: string) => {
  router.visit(articleChatIndex({ query: { conversation: conversationId } }));
};

const shortTitle = (title: string): string => {
  const words = (title || 'Untitled conversation').split(/\s+/);
  return words.slice(0, 5).join(' ') + (words.length > 5 ? '...' : '');
};

const openDeleteModal = (conversation: { id: string; title: string }) => {
  conversationToDelete.value = conversation;
  showDeleteModal.value = true;
};

const confirmDelete = async () => {
  if (!conversationToDelete.value || deletingConversationId.value) {
    return;
  }

  const conversationId = conversationToDelete.value.id;
  showDeleteModal.value = false;
  deletingConversationId.value = conversationId;

  try {
    const response = await fetch(destroyConversationRoute(conversationId).url, {
      method: 'DELETE',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    });

    if (response.ok) {
      if (activeConversationId.value === conversationId) {
        startNewChat();
        return;
      }

      conversations.value = conversations.value.filter((conversation) => conversation.id !== conversationId);
      showSuccessMessage.value = true;
      successMessage.value = 'Conversation deleted successfully.';
      setTimeout(() => { showSuccessMessage.value = false; }, 3000);
    }
  } finally {
    deletingConversationId.value = null;
    conversationToDelete.value = null;
  }
};

const cancelDelete = () => {
  showDeleteModal.value = false;
  conversationToDelete.value = null;
};

const scrollToBottom = async () => {
  await nextTick();

  if (chatContainer.value) {
    chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
  }
};

const getCsrfToken = (): string => {
  const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;

  return meta?.content ?? '';
};

const formatDate = (value: string): string => {
  return new Date(value).toLocaleString('en-UG', {
    dateStyle: 'medium',
    timeStyle: 'short',
  });
};

const formatNetworkError = (error: unknown): string => {
  if (error instanceof Error) {
    if (error.message.includes('fetch')) {
      return 'Network error: I could not reach the server. Check the app connection and try again.';
    }

    return error.message;
  }

  return 'An unexpected error happened while sending the message.';
};
</script>

<template>
  <AppLayout>
    <Head title="Lolo Chat" />

    <div class="flex h-screen flex-col bg-background">
      <div class="border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
        <div class="container mx-auto flex items-center justify-between px-4 py-4">
          <div class="flex items-center gap-4">
            <Button
              variant="ghost"
              size="sm"
              class="md:hidden"
              @click="showConversations = !showConversations"
            >
              Menu
            </Button>

            <div>
              <h1 class="text-2xl font-bold">Lolo</h1>
              <p class="mt-1 text-sm text-muted-foreground">
                {{ activeConversationTitle || 'Natural AI writing workspace' }}
              </p>
            </div>
          </div>

          <div class="flex gap-2">
            <Button variant="outline" size="sm" @click="startNewChat">
              New Chat
            </Button>
            <Button variant="outline" @click="router.visit(articleIndex())">
              Back to Articles
            </Button>
          </div>
        </div>
      </div>

      <div class="flex flex-1 overflow-hidden">
        <aside
          v-show="showConversations"
          class="hidden w-80 border-r bg-accent/10 md:block"
        >
          <div class="flex h-full flex-col">
            <div class="border-b p-4">
              <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                Conversations
              </h2>
            </div>

            <div class="flex-1 overflow-y-auto p-4">
              <div class="space-y-2">
                <div
                  v-for="conversation in conversations"
                  :key="conversation.id"
                  class="group relative flex items-center rounded-lg border bg-background p-3"
                  :class="conversation.id === activeConversationId ? 'border-primary/40' : 'border-border'"
                >
                  <button
                    class="min-w-0 flex-1 text-left"
                    @click="openConversation(conversation.id)"
                  >
                    <p class="truncate text-sm font-medium">
                      {{ shortTitle(conversation.title) }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                      {{ formatDate(conversation.updated_at) }}
                    </p>
                  </button>

                  <div class="relative ml-2">
                    <Button
                      variant="ghost"
                      size="sm"
                      class="h-7 w-7 p-0 opacity-0 transition-opacity group-hover:opacity-100"
                      :class="{ 'opacity-100': activeDropdown === conversation.id }"
                      @click="activeDropdown = activeDropdown === conversation.id ? null : conversation.id"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <circle cx="4" cy="10" r="1.5" />
                        <circle cx="10" cy="10" r="1.5" />
                        <circle cx="16" cy="10" r="1.5" />
                      </svg>
                    </Button>

                    <div
                      v-if="activeDropdown === conversation.id"
                      class="absolute right-0 top-full z-10 mt-1 w-36 rounded-lg border bg-background shadow-lg"
                    >
                      <button
                        class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-destructive hover:bg-destructive/10"
                        :disabled="deletingConversationId === conversation.id"
                        @click="openDeleteModal({ id: conversation.id, title: conversation.title })"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        Delete
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <p
                v-if="conversations.length === 0"
                class="text-sm text-muted-foreground"
              >
                No conversations yet.
              </p>
            </div>
          </div>
        </aside>

        <section class="flex flex-1 flex-col overflow-hidden">
          <div
            ref="chatContainer"
            class="flex-1 space-y-4 overflow-y-auto p-6"
          >
        <div
          v-for="message in messages"
          :key="message.id ?? `${message.role}-${message.content}`"
          class="flex"
          :class="message.role === 'user' ? 'justify-end' : 'justify-start'"
        >
          <div
            class="max-w-[85%] rounded-2xl border p-4 shadow-sm"
            :class="message.role === 'user'
              ? 'border-primary bg-primary text-primary-foreground'
              : message.error
                ? 'border-destructive/30 bg-destructive/10 text-destructive'
                : 'border-border bg-background'"
          >
            <p
              v-if="message.role === 'user'"
              class="whitespace-pre-wrap text-sm leading-relaxed"
            >{{ message.content }}</p>
            <div
              v-else
              class="text-sm leading-relaxed"
              v-html="message.content"
            ></div>

                <div
                  v-if="message.readyToGenerate && message.generationBrief"
                  class="mt-3 rounded-xl border border-primary/20 bg-primary/5 p-3"
                >
                  <p class="text-xs text-muted-foreground">
                    Lolo has enough information to draft the article.
                  </p>
                  <div class="mt-2">
                    <Button size="sm" :disabled="loading" @click="generateDraft">
                      Generate Draft
                    </Button>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="loading" class="flex justify-start">
              <div class="rounded-2xl border bg-background p-4 shadow-sm">
                <div class="flex items-center gap-2">
                  <div class="h-2 w-2 animate-bounce rounded-full bg-primary"></div>
                  <div class="h-2 w-2 animate-bounce rounded-full bg-primary" style="animation-delay: 0.15s"></div>
                  <div class="h-2 w-2 animate-bounce rounded-full bg-primary" style="animation-delay: 0.3s"></div>
                </div>
              </div>
            </div>
          </div>

          <div class="shrink-0 border-t bg-background p-4">
            <div
              v-if="selectedArticleId"
              class="mb-3 rounded-lg bg-accent/20 p-3 text-sm"
            >
              Editing article ID: {{ selectedArticleId }}
            </div>

            <div class="flex gap-3">
              <Input
                v-model="userInput"
                class="flex-1 text-base"
                :disabled="loading"
                :placeholder="selectedArticleId
                  ? 'Tell Lolo what to improve in this draft...'
                  : 'Ask, brainstorm, refine, or tell Lolo what article you want to write...'"
                @keyup.enter="sendMessage()"
              />

              <Button
                :disabled="loading || !userInput.trim()"
                @click="sendMessage()"
              >
                {{ loading ? 'Working...' : 'Send' }}
              </Button>
            </div>
          </div>
        </section>
      </div>
    </div>

    <Transition name="fade">
      <div
        v-if="showSuccessMessage"
        class="fixed top-6 left-1/2 z-50 -translate-x-1/2 rounded-lg bg-green-600 px-4 py-2 text-sm text-white shadow-lg"
      >
        {{ successMessage }}
      </div>
    </Transition>

    <Transition name="fade">
      <div
        v-if="showDeleteModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        @click.self="cancelDelete"
      >
        <div class="w-full max-w-sm rounded-xl bg-background p-6 shadow-xl">
          <h3 class="text-lg font-semibold">Delete Conversation</h3>
          <p class="mt-2 text-sm text-muted-foreground">
            Are you sure you want to delete "<strong>{{ shortTitle(conversationToDelete?.title ?? '') }}</strong>"? This action cannot be undone.
          </p>
          <div class="mt-6 flex justify-end gap-3">
            <Button variant="outline" @click="cancelDelete">
              Cancel
            </Button>
            <Button variant="destructive" :disabled="deletingConversationId !== null" @click="confirmDelete">
              {{ deletingConversationId ? 'Deleting...' : 'Delete' }}
            </Button>
          </div>
        </div>
      </div>
    </Transition>
  </AppLayout>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
