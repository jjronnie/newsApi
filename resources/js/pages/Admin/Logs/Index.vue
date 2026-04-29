<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';

function formatDate(dateStr: string | null): string {
  if (!dateStr) return '-';
  const date = new Date(dateStr);
  return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatRelativeTime(dateStr: string | null): string {
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
}

interface Log {
  id: number;
  job_name: string;
  status: string;
  started_at: string;
  finished_at: string | null;
  duration_ms: number | null;
  error_message: string | null;
}

const props = defineProps<{
  logs: {
    data: Log[];
  };
}>();

const showDeleteModal = ref(false);
const logToDelete = ref<Log | null>(null);
const showMessage = ref(false);
const messageText = ref('');
const messageType = ref<'success' | 'error'>('success');

const openDeleteModal = (log: Log) => {
  logToDelete.value = log;
  showDeleteModal.value = true;
};

const confirmDelete = async () => {
  if (!logToDelete.value) return;

  showDeleteModal.value = false;
  try {
    const response = await fetch(`/admin/logs/${logToDelete.value.id}`, {
      method: 'DELETE',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    });

    const data = await response.json();

    if (data.success) {
      showMessage.value = true;
      messageType.value = 'success';
      messageText.value = data.message || 'Log deleted successfully.';
      router.reload({ only: ['logs'] });
    } else {
      showMessage.value = true;
      messageType.value = 'error';
      messageText.value = data.message || 'Failed to delete log.';
    }
  } catch {
    showMessage.value = true;
    messageType.value = 'error';
    messageText.value = 'Network error. Please try again.';
  } finally {
    logToDelete.value = null;
    setTimeout(() => { showMessage.value = false; }, 3000);
  }
};

const cancelDelete = () => {
  showDeleteModal.value = false;
  logToDelete.value = null;
};

const getCsrfToken = (): string => {
  const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;
  return meta?.content ?? '';
};
</script>

<template>
  <AppLayout>
    <Head title="AI Job Logs" />

    <div class="container mx-auto py-6">
      <h1 class="text-2xl font-bold mb-6">AI Job Logs</h1>

      <Transition name="fade">
        <div
          v-if="showMessage"
          class="mb-4 rounded-lg px-4 py-3 text-sm"
          :class="messageType === 'success' ? 'bg-green-600 text-white' : 'bg-destructive text-destructive-foreground'"
        >
          {{ messageText }}
        </div>
      </Transition>

      <div class="border border-sidebar-border/70 rounded-lg overflow-hidden dark:border-sidebar-border">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-sidebar-border/70 bg-accent/50 dark:border-sidebar-border">
                <th class="px-4 py-3 text-left font-semibold text-foreground">ID</th>
                <th class="px-4 py-3 text-left font-semibold text-foreground">Job Name</th>
                <th class="px-4 py-3 text-left font-semibold text-foreground">Status</th>
                <th class="px-4 py-3 text-left font-semibold text-foreground">Started At</th>
                <th class="px-4 py-3 text-left font-semibold text-foreground">Finished At</th>
                <th class="px-4 py-3 text-left font-semibold text-foreground">Duration (ms)</th>
                <th class="px-4 py-3 text-left font-semibold text-foreground">Error</th>
                <th class="px-4 py-3 text-left font-semibold text-foreground">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="log in logs.data"
                :key="log.id"
                class="border-b border-sidebar-border/70 hover:bg-accent/30 transition-colors dark:border-sidebar-border"
              >
                <td class="px-4 py-3 font-medium text-foreground">{{ log.id }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ log.job_name }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ log.status }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ formatDate(log.started_at) }} ({{ formatRelativeTime(log.started_at) }})</td>
                <td class="px-4 py-3 text-muted-foreground">{{ log.finished_at ? formatDate(log.finished_at) + ' (' + formatRelativeTime(log.finished_at) + ')' : '-' }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ log.duration_ms || '-' }}</td>
                <td class="px-4 py-3 text-red-500">{{ log.error_message || '-' }}</td>
                <td class="px-4 py-3">
                  <Button
                    variant="destructive"
                    size="sm"
                    @click="openDeleteModal(log)"
                  >
                    Delete
                  </Button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <Transition name="fade">
      <div
        v-if="showDeleteModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        @click.self="cancelDelete"
      >
        <div class="w-full max-w-sm rounded-xl bg-background p-6 shadow-xl">
          <h3 class="text-lg font-semibold">Delete Log</h3>
          <p class="mt-2 text-sm text-muted-foreground">
            Are you sure you want to delete log <strong>#{{ logToDelete?.id }}</strong>? This action cannot be undone.
          </p>
          <div class="mt-6 flex justify-end gap-3">
            <Button variant="outline" @click="cancelDelete">
              Cancel
            </Button>
            <Button variant="destructive" @click="confirmDelete">
              Delete
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
