<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { create as createTopic, destroy as destroyTopic, edit as editTopic } from '@/routes/admin/topics';

defineProps<{
  topics: {
    data: Array<{
      id: number;
      topic_title: string;
      focus_keyword: string;
      description: string | null;
      status: string;
      created_at: string;
    }>;
    links: Array<{ url: string | null; label: string; active: boolean }>;
  };
}>();

const formatDate = (dateString: string | null) => {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

const deleteTopic = (id: number) => {
  if (confirm('Are you sure you want to delete this topic?')) {
    router.delete(destroyTopic(id), {
      preserveScroll: true,
    });
  }
};

const getStatusClass = (status: string) => {
  switch (status) {
    case 'pending':
      return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400';
    case 'used':
      return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
    case 'rejected':
      return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
    default:
      return 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400';
  }
};
</script>

<template>
  <AppLayout>
    <Head title="Topics" />

    <div class="container mx-auto py-6">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Topics</h1>
        <Link
          :href="createTopic()"
          class="px-4 py-2 bg-blue-600 text-white rounded hover:opacity-90 transition-opacity text-sm font-medium"
        >
          Add Topic
        </Link>
      </div>

      <div v-if="topics.data.length > 0" class="border border-sidebar-border/70 rounded-lg overflow-hidden dark:border-sidebar-border">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-sidebar-border/70 bg-accent/50 dark:border-sidebar-border">
                <th class="px-4 py-3 text-left font-semibold text-foreground">ID</th>
                <th class="px-4 py-3 text-left font-semibold text-foreground">Title</th>
                <th class="px-4 py-3 text-left font-semibold text-foreground">Focus Keyword</th>
                <th class="px-4 py-3 text-left font-semibold text-foreground">Status</th>
                <th class="px-4 py-3 text-left font-semibold text-foreground">Created</th>
                <th class="px-4 py-3 text-left font-semibold text-foreground">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="topic in topics.data"
                :key="topic.id"
                class="border-b border-sidebar-border/70 hover:bg-accent/30 transition-colors dark:border-sidebar-border"
              >
                <td class="px-4 py-3 font-medium text-foreground">{{ topic.id }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ topic.topic_title }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ topic.focus_keyword }}</td>
                <td class="px-4 py-3">
                  <span
                    class="px-2 py-1 rounded-full text-xs font-medium"
                    :class="getStatusClass(topic.status)"
                  >
                    {{ topic.status }}
                  </span>
                </td>
                <td class="px-4 py-3 text-muted-foreground">{{ formatDate(topic.created_at) }}</td>
                <td class="px-4 py-3">
                  <div class="flex gap-2">
                    <Link
                      :href="editTopic(topic.id)"
                      class="text-primary hover:underline font-medium text-sm"
                    >
                      Edit
                    </Link>
                    <button
                      @click="deleteTopic(topic.id)"
                      class="text-destructive hover:underline font-medium text-sm"
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

      <div v-else class="text-center py-12">
        <p class="text-muted-foreground mb-4">No topics found.</p>
        <Link
          :href="createTopic()"
          class="text-primary hover:underline font-medium"
        >
          Add your first topic
        </Link>
      </div>

      <!-- Pagination -->
      <div v-if="topics.links" class="mt-4 flex justify-center space-x-2">
        <button
          v-for="link in topics.links"
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
  </AppLayout>
</template>
