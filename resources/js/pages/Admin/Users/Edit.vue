<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineProps<{
  user: {
    id: number;
    name: string;
    email: string;
  };
}>();

const form = useForm({
  name: props.user.name,
  email: props.user.email,
});

const submit = () => {
  form.put(`/admin/users/${props.user.id}`, {
    preserveScroll: true,
  });
};
</script>

<template>
  <AppLayout>
    <Head title="Edit User" />

    <div class="container mx-auto py-6 max-w-2xl">
      <div class="flex items-center gap-4 mb-6">
        <Link
          href="/admin/users"
          class="text-muted-foreground hover:text-foreground transition-colors"
        >
          ← Back
        </Link>
        <h1 class="text-2xl font-bold">Edit User</h1>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <div class="border border-sidebar-border/70 rounded-lg p-6 dark:border-sidebar-border">
          <div class="space-y-4">
            <div>
              <label for="name" class="block text-sm font-medium mb-1">Name</label>
              <input
                id="name"
                v-model="form.name"
                type="text"
                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                :class="{ 'border-destructive': form.errors.name }"
              />
              <p v-if="form.errors.name" class="text-destructive text-sm mt-1">{{ form.errors.name }}</p>
            </div>

            <div>
              <label for="email" class="block text-sm font-medium mb-1">Email</label>
              <input
                id="email"
                v-model="form.email"
                type="email"
                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                :class="{ 'border-destructive': form.errors.email }"
              />
              <p v-if="form.errors.email" class="text-destructive text-sm mt-1">{{ form.errors.email }}</p>
            </div>
          </div>
        </div>

        <div class="flex gap-4">
          <button
            type="submit"
            :disabled="form.processing"
            class="px-4 py-2 bg-primary text-primary-foreground rounded hover:opacity-90 transition-opacity disabled:opacity-50 text-sm font-medium"
          >
            {{ form.processing ? 'Saving...' : 'Save Changes' }}
          </button>
          <Link
            href="/admin/users"
            class="px-4 py-2 border border-sidebar-border/70 rounded hover:bg-accent/50 transition-colors text-sm font-medium dark:border-sidebar-border"
          >
            Cancel
          </Link>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
