<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as topicIndex, update as updateTopic } from '@/routes/admin/topics';

const props = defineProps<{
  topic: {
    id: number;
    topic_title: string;
    focus_keyword: string;
    description: string | null;
    status: string;
  };
}>();

const form = useForm({
  topic_title: props.topic.topic_title,
  focus_keyword: props.topic.focus_keyword,
  description: props.topic.description || '',
  status: props.topic.status,
});

const submit = () => {
  form.put(updateTopic(props.topic.id));
};
</script>

<template>
  <AppLayout>
    <Head title="Edit Topic" />

    <div class="container mx-auto py-6">
      <h1 class="text-2xl font-bold mb-6">Edit Topic</h1>

      <form @submit.prevent="submit" class="max-w-2xl space-y-4">
        <div>
          <label for="topic_title" class="block text-sm font-medium mb-1">Topic Title</label>
          <input
            id="topic_title"
            v-model="form.topic_title"
            type="text"
            class="w-full px-3 py-2 border border-sidebar-border/70 rounded-md bg-background text-foreground dark:border-sidebar-border"
            required
          />
          <p v-if="form.errors.topic_title" class="text-destructive text-sm mt-1">{{ form.errors.topic_title }}</p>
        </div>

        <div>
          <label for="focus_keyword" class="block text-sm font-medium mb-1">Focus Keyword</label>
          <input
            id="focus_keyword"
            v-model="form.focus_keyword"
            type="text"
            class="w-full px-3 py-2 border border-sidebar-border/70 rounded-md bg-background text-foreground dark:border-sidebar-border"
            required
          />
          <p v-if="form.errors.focus_keyword" class="text-destructive text-sm mt-1">{{ form.errors.focus_keyword }}</p>
        </div>

        <div>
          <label for="description" class="block text-sm font-medium mb-1">Description</label>
          <textarea
            id="description"
            v-model="form.description"
            rows="3"
            class="w-full px-3 py-2 border border-sidebar-border/70 rounded-md bg-background text-foreground dark:border-sidebar-border"
          />
          <p v-if="form.errors.description" class="text-destructive text-sm mt-1">{{ form.errors.description }}</p>
        </div>

        <div>
          <label for="status" class="block text-sm font-medium mb-1">Status</label>
          <select
            id="status"
            v-model="form.status"
            class="w-full px-3 py-2 border border-sidebar-border/70 rounded-md bg-background text-foreground dark:border-sidebar-border"
          >
            <option value="pending">Pending</option>
            <option value="used">Used</option>
            <option value="rejected">Rejected</option>
          </select>
          <p v-if="form.errors.status" class="text-destructive text-sm mt-1">{{ form.errors.status }}</p>
        </div>

        <div class="flex gap-2 pt-4">
          <button
            type="submit"
            :disabled="form.processing"
            class="px-4 py-2 bg-blue-600 text-white rounded hover:opacity-90 transition-opacity text-sm font-medium disabled:opacity-50"
          >
            {{ form.processing ? 'Updating...' : 'Update Topic' }}
          </button>
          <Link
            :href="topicIndex()"
            class="px-4 py-2 border border-sidebar-border/70 rounded hover:bg-accent/50 text-sm font-medium dark:border-sidebar-border"
          >
            Cancel
          </Link>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
