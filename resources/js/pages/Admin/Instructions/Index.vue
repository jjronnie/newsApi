<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { edit as editInstruction } from '@/routes/admin/instructions';

defineProps<{
  instructions: {
    data: Array<{
      id: number;
      name: string;
      type: string;
      is_active: boolean;
      created_at: string;
    }>;
    links: Array<{ url: string | null; label: string; active: boolean }>;
  };
}>();
</script>

<template>
  <AppLayout>
    <Head title="AI Instructions" />

    <div class="container mx-auto py-6">
      <h1 class="text-2xl font-bold mb-6">AI Instructions</h1>

      <div v-if="instructions.data.length > 0" class="border border-sidebar-border/70 rounded-lg overflow-hidden dark:border-sidebar-border">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-sidebar-border/70 bg-accent/50 dark:border-sidebar-border">
                <th class="px-4 py-3 text-left font-semibold text-foreground">ID</th>
                <th class="px-4 py-3 text-left font-semibold text-foreground">Name</th>
                <th class="px-4 py-3 text-left font-semibold text-foreground">Type</th>
                <th class="px-4 py-3 text-left font-semibold text-foreground">Status</th>
                <th class="px-4 py-3 text-left font-semibold text-foreground">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="instruction in instructions.data"
                :key="instruction.id"
                class="border-b border-sidebar-border/70 hover:bg-accent/30 transition-colors dark:border-sidebar-border"
              >
                <td class="px-4 py-3 font-medium text-foreground">{{ instruction.id }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ instruction.name }}</td>
                <td class="px-4 py-3 text-muted-foreground">
                  <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                    {{ instruction.type }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <span
                    class="px-2 py-1 rounded-full text-xs font-medium"
                    :class="instruction.is_active
                      ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                      : 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400'"
                  >
                    {{ instruction.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <Link
                    :href="editInstruction(instruction.id)"
                    class="text-primary hover:underline font-medium text-sm"
                  >
                    Edit
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-else class="text-center py-12">
        <p class="text-muted-foreground">No instructions found.</p>
      </div>

      <!-- Pagination -->
      <div v-if="instructions.links" class="mt-4 flex justify-center space-x-2">
        <button
          v-for="link in instructions.links"
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
