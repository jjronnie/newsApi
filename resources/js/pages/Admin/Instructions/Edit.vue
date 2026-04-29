<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as instructionIndex, update as updateInstruction } from '@/routes/admin/instructions';

const props = defineProps<{
  instruction: {
    id: number;
    name: string;
    content: string;
    type: string;
    is_active: boolean;
  };
}>();

const form = useForm({
  name: props.instruction.name,
  content: props.instruction.content,
  type: props.instruction.type,
  is_active: props.instruction.is_active,
});

const submit = () => {
  form.put(updateInstruction(props.instruction.id));
};
</script>

<template>
  <AppLayout>
    <Head title="Edit Instruction" />

    <div class="container mx-auto py-6">
      <h1 class="text-2xl font-bold mb-6">Edit Instruction: {{ instruction.name }}</h1>

      <form @submit.prevent="submit" class="max-w-4xl space-y-4">
        <div>
          <label for="name" class="block text-sm font-medium mb-1">Name</label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            class="w-full px-3 py-2 border border-sidebar-border/70 rounded-md bg-background text-foreground dark:border-sidebar-border"
            required
          />
          <p v-if="form.errors.name" class="text-destructive text-sm mt-1">{{ form.errors.name }}</p>
        </div>

        <div>
          <label for="type" class="block text-sm font-medium mb-1">Type</label>
          <input
            id="type"
            v-model="form.type"
            type="text"
            class="w-full px-3 py-2 border border-sidebar-border/70 rounded-md bg-background text-foreground dark:border-sidebar-border"
            required
          />
          <p v-if="form.errors.type" class="text-destructive text-sm mt-1">{{ form.errors.type }}</p>
        </div>

        <div>
          <label for="is_active" class="flex items-center gap-2">
            <input
              id="is_active"
              v-model="form.is_active"
              type="checkbox"
              class="rounded border-sidebar-border/70 dark:border-sidebar-border"
            />
            <span class="text-sm font-medium">Active</span>
          </label>
        </div>

        <div>
          <label for="content" class="block text-sm font-medium mb-1">Content</label>
          <textarea
            id="content"
            v-model="form.content"
            rows="20"
            class="w-full px-3 py-2 border border-sidebar-border/70 rounded-md bg-background text-foreground font-mono text-sm dark:border-sidebar-border"
            required
          />
          <p v-if="form.errors.content" class="text-destructive text-sm mt-1">{{ form.errors.content }}</p>
        </div>

        <div class="flex gap-2 pt-4">
          <button
            type="submit"
            :disabled="form.processing"
            class="px-4 py-2 bg-blue-600 text-white rounded hover:opacity-90 transition-opacity text-sm font-medium disabled:opacity-50"
          >
            {{ form.processing ? 'Updating...' : 'Update Instruction' }}
          </button>
          <Link
            :href="instructionIndex()"
            class="px-4 py-2 border border-sidebar-border/70 rounded hover:bg-accent/50 text-sm font-medium dark:border-sidebar-border"
          >
            Cancel
          </Link>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
