<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { fetch as fetchWpCategories } from '@/routes/admin/wp-categories';

defineProps<{
  categories: {
    data: Array<{
      id: number;
      name: string;
      slug: string;
      wp_id: number;
    }>;
    links: Array<{ url: string | null; label: string; active: boolean }>;
  };
}>();

const loading = ref(false);

const fetchFromWp = () => {
  loading.value = true;
  router.post(fetchWpCategories(), {}, {
    onSuccess: () => {
      loading.value = false;
    },
    onError: () => {
      loading.value = false;
    },
    preserveScroll: true,
  });
};

const formatDate = (dateString: string | null) => {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

const formatRelativeTime = (dateString: string | null) => {
  if (!dateString) return '-';
  const date = new Date(dateString);
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffMins = Math.floor(diffMs / 60000);

  if (diffMins < 1) return 'just now';
  if (diffMins < 60) return `${diffMins} min${diffMins > 1 ? 's' : ''} ago`;

  const diffHours = Math.floor(diffMins / 60);
  if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;

  const diffDays = Math.floor(diffHours / 24);
  return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
};
</script>

<template>
  <AppLayout>
    <Head title="WordPress Categories" />

    <div class="container mx-auto py-6">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">WordPress Categories</h1>
        <button
          @click="fetchFromWp"
          :disabled="loading"
          class="px-4 py-2 bg-blue-600 text-white rounded hover:opacity-90 transition-opacity text-sm font-medium disabled:opacity-50"
        >
          {{ loading ? 'Fetching...' : 'Fetch from WP' }}
        </button>
      </div>

      <div v-if="categories.data.length > 0" class="border border-sidebar-border/70 rounded-lg overflow-hidden dark:border-sidebar-border">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-sidebar-border/70 bg-accent/50 dark:border-sidebar-border">
                <th class="px-4 py-3 text-left font-semibold text-foreground">ID</th>
                <th class="px-4 py-3 text-left font-semibold text-foreground">Name</th>
                <th class="px-4 py-3 text-left font-semibold text-foreground">Slug</th>
                <th class="px-4 py-3 text-left font-semibold text-foreground">WP ID</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="cat in categories.data"
                :key="cat.id"
                class="border-b border-sidebar-border/70 hover:bg-accent/30 transition-colors dark:border-sidebar-border"
              >
                <td class="px-4 py-3 font-medium text-foreground">{{ cat.id }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ cat.name }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ cat.slug }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ cat.wp_id }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-else class="text-center py-12">
        <p class="text-muted-foreground mb-4">No categories found.</p>
        <button
          @click="fetchFromWp"
          :disabled="loading"
          class="text-primary hover:underline font-medium"
        >
          Fetch from WordPress
        </button>
      </div>

      <!-- Pagination -->
      <div v-if="categories.links" class="mt-4 flex justify-center space-x-2">
        <button
          v-for="link in categories.links"
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
