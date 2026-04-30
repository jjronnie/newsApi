<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { edit as editUser, destroy as destroyUser } from '@/routes/admin/users';

defineProps<{
  users: {
    data: Array<{
      id: number;
      name: string;
      email: string;
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

const deleteUser = (id: number) => {
  if (confirm('Are you sure you want to delete this user?')) {
    router.delete(destroyUser(id), {
      preserveScroll: true,
    });
  }
};
</script>

<template>
  <AppLayout>
    <Head title="Users" />

    <div class="container mx-auto py-6">
      <h1 class="text-2xl font-bold mb-6">Users</h1>

      <div class="border border-sidebar-border/70 rounded-lg overflow-hidden dark:border-sidebar-border">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-sidebar-border/70 bg-accent/50 dark:border-sidebar-border">
                <th class="px-4 py-3 text-left font-semibold text-foreground">Name</th>
                <th class="px-4 py-3 text-left font-semibold text-foreground">Email</th>
                <th class="px-4 py-3 text-left font-semibold text-foreground">Created</th>
                <th class="px-4 py-3 text-left font-semibold text-foreground">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="user in users.data"
                :key="user.id"
                class="border-b border-sidebar-border/70 hover:bg-accent/30 transition-colors dark:border-sidebar-border"
              >
                <td class="px-4 py-3 font-medium text-foreground">{{ user.name }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ user.email }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ formatDate(user.created_at) }}</td>
                <td class="px-4 py-3">
                  <div class="flex gap-2">
                    <Button
                      @click="router.visit(editUser(user.id))"
                      variant="outline"
                      size="sm"
                      class="border-primary/30 text-primary hover:bg-primary/10"
                    >
                      Edit
                    </Button>
                    <Button
                      @click="deleteUser(user.id)"
                      variant="outline"
                      size="sm"
                      class="border-destructive/30 text-destructive hover:bg-destructive/10"
                    >
                      Delete
                    </Button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-if="users.data.length === 0" class="text-center py-12">
        <p class="text-muted-foreground">No users found.</p>
      </div>

      <!-- Pagination -->
      <div v-if="users.links" class="mt-4 flex justify-center space-x-2">
        <button
          v-for="link in users.links"
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
