<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index as articleChatIndex } from '@/routes/admin/articles/chat';
import { destroy as destroyArticle, push as pushArticle, show as showArticle } from '@/routes/admin/articles';

defineProps<{
  articles: {
    data: Array<{
      id: number;
      title: string;
      status: string;
      meta_title: string;
      generated_at: string | null;
      pushed_at: string | null;
      wp_post_id: number | null;
    }>;
    links: Array<{ url: string | null; label: string; active: boolean }>;
  };
}>();

const formatDate = (dateStr: string | null): string => {
  if (!dateStr) return '-';
  const date = new Date(dateStr);
  return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};

const formatRelativeTime = (dateStr: string | null): string => {
  if (!dateStr) return '-';
  const date = new Date(dateStr);
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMins / 60);
  const diffDays = Math.floor(diffHours / 24);

  if (diffMins < 1) return 'just now';
  if (diffMins < 60) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
  if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
  return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
};

const pushToWordPress = (id: number) => {
  if (confirm('Push this article to WordPress as draft?')) {
    router.post(pushArticle(id));
  }
};

const deleteArticle = (id: number) => {
  if (confirm('Are you sure you want to delete this article?')) {
    router.delete(destroyArticle(id));
  }
};
</script>

<template>
  <AppLayout>
    <Head title="Articles" />

    <div class="container mx-auto py-6">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Articles</h1>
        <Button
          @click="router.visit(articleChatIndex())"
          class="bg-blue-600 text-white hover:bg-blue-700"
        >
          <span class="flex items-center gap-2">
            <span>💬</span>
            Generate with AI
          </span>
        </Button>
      </div>

      <div class="border border-sidebar-border/70 rounded-lg overflow-hidden dark:border-sidebar-border">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-sidebar-border/70 bg-accent/50 dark:border-sidebar-border">
                <th class="px-4 py-3 text-left font-semibold text-foreground">Title</th>
                <th class="px-4 py-3 text-left font-semibold text-foreground">Status</th>
                <th class="px-4 py-3 text-left font-semibold text-foreground">Generated At</th>
                <th class="px-4 py-3 text-left font-semibold text-foreground">Pushed At</th>
                <th class="px-4 py-3 text-left font-semibold text-foreground">WP Post ID</th>
                <th class="px-4 py-3 text-left font-semibold text-foreground">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="article in articles.data"
                :key="article.id"
                class="border-b border-sidebar-border/70 hover:bg-accent/30 transition-colors dark:border-sidebar-border"
              >
                <td class="px-4 py-3 font-medium text-foreground">{{ article.title }}</td>
                <td class="px-4 py-3">
                  <Badge :variant="article.status === 'generated' ? 'default' : 'secondary'">
                    {{ article.status }}
                  </Badge>
                </td>
                <td class="px-4 py-3 text-muted-foreground">
                  {{ formatDate(article.generated_at) }} ({{ formatRelativeTime(article.generated_at) }})
                </td>
                <td class="px-4 py-3 text-muted-foreground">
                  {{ formatDate(article.pushed_at) }} ({{ formatRelativeTime(article.pushed_at) }})
                </td>
                <td class="px-4 py-3 text-muted-foreground">{{ article.wp_post_id || '-' }}</td>
                <td class="px-4 py-3">
                  <div class="flex gap-2">
                    <button
                      @click="router.visit(showArticle(article.id))"
                      class="inline-flex items-center rounded-md border border-primary/30 px-3 py-1.5 text-xs font-semibold text-primary hover:bg-primary/10 transition-colors"
                    >
                      View
                    </button>
                    <button
                      @click="router.visit(articleChatIndex({ query: { article: article.id } }))"
                      class="inline-flex items-center rounded-md border border-blue-300 px-3 py-1.5 text-xs font-semibold text-blue-600 hover:bg-blue-50 transition-colors"
                    >
                      Edit with AI
                    </button>
                    <button
                      @click="pushToWordPress(article.id)"
                      class="inline-flex items-center rounded-md border border-muted-foreground/30 px-3 py-1.5 text-xs font-semibold text-foreground hover:bg-accent/70 transition-colors"
                    >
                      Push to WP
                    </button>
                    <button
                      @click="deleteArticle(article.id)"
                      class="inline-flex items-center rounded-md border border-destructive/30 px-3 py-1.5 text-xs font-semibold text-destructive hover:bg-destructive/10 transition-colors"
                    >
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="mt-4 flex justify-center space-x-2">
        <button
          v-for="link in articles.links"
          :key="link.label"
          :disabled="!link.url"
          @click="link.url && router.visit(link.url)"
          class="px-3 py-2 rounded-md border text-sm font-medium transition-colors"
          :class="link.active
            ? 'bg-primary text-primary-foreground border-primary'
            : 'border-sidebar-border/70 text-foreground hover:bg-accent/50 dark:border-sidebar-border'"
          v-html="link.label"
        />
      </div>
    </div>

      <!-- Floating Chat Button - Links to full chat page -->
      <div class="fixed bottom-6 right-6 z-50">
        <Button
          @click="router.visit(articleChatIndex())"
          class="rounded-full w-14 h-14 shadow-lg hover:shadow-xl transition-shadow bg-blue-600 text-white hover:bg-blue-700"
          size="icon"
        >
          <span class="text-2xl">💬</span>
        </Button>
      </div>
  </AppLayout>
</template>
