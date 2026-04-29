<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { destroy as destroyArticle, index as articleIndex, push as pushArticle } from '@/routes/admin/articles';

defineProps<{
  article: {
    id: number;
    title: string;
    slug: string;
    focus_keyword: string;
    meta_title: string;
    meta_description: string;
    excerpt: string;
    content_html: string;
    status: string;
    generated_at: string | null;
    pushed_at: string | null;
    wp_post_id: number | null;
    faq_json: Array<{ question: string; answer: string }>;
    tag_suggestions_json: string[];
    category_suggestions_json: string[];
  };
}>();

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
    <Head :title="article.title" />

    <div class="container mx-auto py-6 space-y-6">
      <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold">{{ article.title }}</h1>
        <div class="space-x-2">
          <Button variant="outline" @click="router.visit(articleIndex())">
            Back to List
          </Button>
          <Button @click="pushToWordPress(article.id)">
            Push to WordPress
          </Button>
          <Button variant="destructive" @click="deleteArticle(article.id)">
            Delete
          </Button>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <Card>
          <CardHeader>
            <CardTitle>SEO Metadata</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div>
              <strong>Slug:</strong> {{ article.slug }}
            </div>
            <div>
              <strong>Focus Keyword:</strong> {{ article.focus_keyword }}
            </div>
            <div>
              <strong>Meta Title:</strong> {{ article.meta_title }}
            </div>
            <div>
              <strong>Meta Description:</strong> {{ article.meta_description }}
            </div>
            <div>
              <strong>Excerpt:</strong> {{ article.excerpt }}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Status & Timestamps</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div>
              <strong>Status:</strong> {{ article.status }}
            </div>
            <div>
              <strong>Generated At:</strong> {{ article.generated_at || '-' }}
            </div>
            <div>
              <strong>Pushed At:</strong> {{ article.pushed_at || '-' }}
            </div>
            <div>
              <strong>WP Post ID:</strong> {{ article.wp_post_id || '-' }}
            </div>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Content Preview</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="prose max-w-none" v-html="article.content_html"></div>
        </CardContent>
      </Card>

      <Card v-if="article.faq_json && article.faq_json.length > 0">
        <CardHeader>
          <CardTitle>FAQ</CardTitle>
        </CardHeader>
        <CardContent>
          <div v-for="(faq, index) in article.faq_json" :key="index" class="mb-4">
            <strong>Q: {{ faq.question }}</strong>
            <p>A: {{ faq.answer }}</p>
          </div>
        </CardContent>
      </Card>

      <Card v-if="article.tag_suggestions_json">
        <CardHeader>
          <CardTitle>Tag Suggestions</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="flex flex-wrap gap-2">
            <span
              v-for="tag in article.tag_suggestions_json"
              :key="tag"
              class="px-2 py-1 bg-secondary text-secondary-foreground rounded"
            >
              {{ tag }}
            </span>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
