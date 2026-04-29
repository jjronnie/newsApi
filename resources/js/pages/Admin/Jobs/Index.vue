<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { ref } from 'vue';

interface Job {
  id: number;
  queue: string;
  payload: string;
  attempts: number;
  reserved_at: number | null;
  available_at: number;
  created_at: number;
}

interface JobDetails {
  job: Job;
  batch: {
    id: string;
    name: string;
    total_jobs: number;
    pending_jobs: number;
    failed_jobs: number;
    failed_job_ids: string;
    options: string;
    cancelled_at: number | null;
    created_at: number;
    finished_at: number | null;
  } | null;
  failed_job: {
    id: number;
    uuid: string;
    connection: string;
    queue: string;
    payload: string;
    exception: string;
    failed_at: string;
  } | null;
}

interface JobsPagination {
  data: Job[];
  links: Array<{ url: string | null; label: string; active: boolean }>;
}

defineProps<{
  jobs: JobsPagination;
}>();

const selectedJob = ref<JobDetails | null>(null);
const showModal = ref(false);
const loading = ref(false);

const viewJob = async (jobId: number) => {
  loading.value = true;
  showModal.value = true;

  try {
    const response = await fetch(`/admin/jobs/${jobId}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    if (response.ok) {
      selectedJob.value = await response.json();
    } else {
      selectedJob.value = null;
    }
  } catch {
    selectedJob.value = null;
  } finally {
    loading.value = false;
  }
};

const closeModal = () => {
  showModal.value = false;
  selectedJob.value = null;
};

const parsePayload = (payload: string) => {
  try {
    return JSON.parse(payload);
  } catch {
    return null;
  }
};

const formatDate = (timestamp: number | null) => {
  if (!timestamp) return '-';
  const date = new Date(timestamp * 1000);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

const formatRelativeTime = (timestamp: number | null) => {
  if (!timestamp) return '-';
  const date = new Date(timestamp * 1000);
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

const formatRelativeTimeString = (dateString: string | null) => {
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

const getJobStatus = (job: Job) => {
  if (job.reserved_at) return 'running';
  return 'pending';
};
</script>

<template>
  <AppLayout>
    <Head title="Jobs" />

    <div class="container mx-auto py-6">
      <h1 class="text-2xl font-bold mb-6">Jobs ({{ jobs.total ?? jobs.data.length }})</h1>

      <div v-if="jobs.data.length > 0" class="border border-sidebar-border/70 rounded-lg overflow-hidden dark:border-sidebar-border">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-sidebar-border/70 bg-accent/50 dark:border-sidebar-border">
                <th class="px-4 py-3 text-left font-semibold text-foreground">ID</th>
                <th class="px-4 py-3 text-left font-semibold text-foreground">Queue</th>
                <th class="px-4 py-3 text-left font-semibold text-foreground">Job</th>
                <th class="px-4 py-3 text-left font-semibold text-foreground">Status</th>
                <th class="px-4 py-3 text-left font-semibold text-foreground">Attempts</th>
                <th class="px-4 py-3 text-left font-semibold text-foreground">Created At</th>
                <th class="px-4 py-3 text-left font-semibold text-foreground">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="job in jobs.data"
                :key="job.id"
                class="border-b border-sidebar-border/70 hover:bg-accent/30 transition-colors dark:border-sidebar-border"
              >
                <td class="px-4 py-3 font-medium text-foreground">{{ job.id }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ job.queue }}</td>
                <td class="px-4 py-3 text-muted-foreground text-xs max-w-[300px] truncate">
                  {{ parsePayload(job.payload)?.displayName ?? 'N/A' }}
                </td>
                <td class="px-4 py-3">
                  <span
                    :class="{
                      'px-2 py-1 rounded text-xs font-semibold': true,
                      'bg-green-100 text-green-800': getJobStatus(job) === 'pending',
                      'bg-blue-100 text-blue-800': getJobStatus(job) === 'running',
                    }"
                  >
                    {{ getJobStatus(job) }}
                  </span>
                </td>
                <td class="px-4 py-3 text-muted-foreground">{{ job.attempts }}</td>
                <td class="px-4 py-3 text-muted-foreground text-xs">
                  <div>{{ formatDate(job.created_at) }}</div>
                  <div class="text-xs text-muted-foreground/70">{{ formatRelativeTime(job.created_at) }}</div>
                </td>
                <td class="px-4 py-3">
                  <button
                    @click="viewJob(job.id)"
                    class="inline-flex items-center rounded-md border border-primary/30 px-3 py-1.5 text-xs font-semibold text-primary hover:bg-primary/10 transition-colors"
                  >
                    View
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-else class="text-center py-12">
        <p class="text-muted-foreground">No jobs found.</p>
      </div>

      <!-- Pagination -->
      <div v-if="jobs.links" class="mt-4 flex justify-center space-x-2">
        <button
          v-for="link in jobs.links"
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

    <!-- Job Details Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4"
      @click.self="closeModal"
    >
      <div class="bg-background border border-sidebar-border/70 rounded-lg w-full max-w-4xl dark:border-sidebar-border max-h-[80vh] overflow-y-auto">
        <div class="flex items-start justify-between border-b border-sidebar-border/70 p-4 dark:border-sidebar-border">
          <div>
            <h3 class="text-lg font-semibold text-foreground" v-if="selectedJob">
              Job Details #{{ selectedJob.job.id }}
            </h3>
            <p class="text-xs text-muted-foreground" v-if="selectedJob">
              {{ parsePayload(selectedJob.job.payload)?.displayName ?? 'N/A' }}
            </p>
          </div>
          <button
            @click="closeModal"
            class="text-muted-foreground hover:text-foreground transition-colors"
          >
            ✕
          </button>
        </div>

        <div v-if="loading" class="p-8 text-center">
          <p class="text-muted-foreground">Loading...</p>
        </div>

        <div v-else-if="selectedJob" class="p-4 space-y-6">
          <!-- Jobs Table Details -->
          <div>
            <h4 class="text-md font-semibold text-foreground mb-3">Jobs Table</h4>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-xs text-muted-foreground">ID</p>
                <p class="text-sm font-medium text-foreground">{{ selectedJob.job.id }}</p>
              </div>
              <div>
                <p class="text-xs text-muted-foreground">Queue</p>
                <p class="text-sm font-medium text-foreground">{{ selectedJob.job.queue }}</p>
              </div>
              <div>
                <p class="text-xs text-muted-foreground">Status</p>
                <p class="text-sm font-medium text-foreground">{{ getJobStatus(selectedJob.job) }}</p>
              </div>
              <div>
                <p class="text-xs text-muted-foreground">Attempts</p>
                <p class="text-sm font-medium text-foreground">{{ selectedJob.job.attempts }}</p>
              </div>
              <div>
                <p class="text-xs text-muted-foreground">Created At</p>
                <p class="text-sm font-medium text-foreground">{{ formatDate(selectedJob.job.created_at) }}</p>
              </div>
              <div>
                <p class="text-xs text-muted-foreground">Available At</p>
                <p class="text-sm font-medium text-foreground">{{ formatDate(selectedJob.job.available_at) }}</p>
                <p class="text-xs text-muted-foreground/70">{{ formatRelativeTime(selectedJob.job.available_at) }}</p>
              </div>
              <div>
                <p class="text-xs text-muted-foreground">Reserved At</p>
                <p class="text-sm font-medium text-foreground">{{ formatDate(selectedJob.job.reserved_at) }}</p>
                <p class="text-xs text-muted-foreground/70">{{ formatRelativeTime(selectedJob.job.reserved_at) }}</p>
              </div>
            </div>
            <div class="mt-4">
              <p class="text-xs text-muted-foreground mb-2">Payload</p>
              <pre class="text-xs bg-accent/50 p-3 rounded overflow-x-auto">{{ JSON.stringify(parsePayload(selectedJob.job.payload), null, 2) }}</pre>
            </div>
          </div>

          <!-- Job Batches Table Details -->
          <div v-if="selectedJob.batch">
            <h4 class="text-md font-semibold text-foreground mb-3">Job Batches Table</h4>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-xs text-muted-foreground">Batch ID</p>
                <p class="text-sm font-medium text-foreground">{{ selectedJob.batch.id }}</p>
              </div>
              <div>
                <p class="text-xs text-muted-foreground">Name</p>
                <p class="text-sm font-medium text-foreground">{{ selectedJob.batch.name }}</p>
              </div>
              <div>
                <p class="text-xs text-muted-foreground">Total Jobs</p>
                <p class="text-sm font-medium text-foreground">{{ selectedJob.batch.total_jobs }}</p>
              </div>
              <div>
                <p class="text-xs text-muted-foreground">Pending Jobs</p>
                <p class="text-sm font-medium text-foreground">{{ selectedJob.batch.pending_jobs }}</p>
              </div>
              <div>
                <p class="text-xs text-muted-foreground">Failed Jobs</p>
                <p class="text-sm font-medium text-foreground">{{ selectedJob.batch.failed_jobs }}</p>
              </div>
              <div>
                <p class="text-xs text-muted-foreground">Created At</p>
                <p class="text-sm font-medium text-foreground">{{ formatDate(selectedJob.batch.created_at) }}</p>
                <p class="text-xs text-muted-foreground/70">{{ formatRelativeTime(selectedJob.batch.created_at) }}</p>
              </div>
              <div>
                <p class="text-xs text-muted-foreground">Finished At</p>
                <p class="text-sm font-medium text-foreground">{{ formatDate(selectedJob.batch.finished_at) }}</p>
                <p class="text-xs text-muted-foreground/70">{{ formatRelativeTime(selectedJob.batch.finished_at) }}</p>
              </div>
            </div>
            <div class="mt-4">
              <p class="text-xs text-muted-foreground mb-2">Options</p>
              <pre class="text-xs bg-accent/50 p-3 rounded overflow-x-auto">{{ selectedJob.batch.options }}</pre>
            </div>
            <div class="mt-4">
              <p class="text-xs text-muted-foreground mb-2">Failed Job IDs</p>
              <pre class="text-xs bg-accent/50 p-3 rounded overflow-x-auto">{{ selectedJob.batch.failed_job_ids }}</pre>
            </div>
          </div>

          <!-- Failed Jobs Table Details -->
          <div v-if="selectedJob.failed_job">
            <h4 class="text-md font-semibold text-foreground mb-3">Failed Jobs Table</h4>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-xs text-muted-foreground">ID</p>
                <p class="text-sm font-medium text-foreground">{{ selectedJob.failed_job.id }}</p>
              </div>
              <div>
                <p class="text-xs text-muted-foreground">UUID</p>
                <p class="text-sm font-medium text-foreground">{{ selectedJob.failed_job.uuid }}</p>
              </div>
              <div>
                <p class="text-xs text-muted-foreground">Connection</p>
                <p class="text-sm font-medium text-foreground">{{ selectedJob.failed_job.connection }}</p>
              </div>
              <div>
                <p class="text-xs text-muted-foreground">Queue</p>
                <p class="text-sm font-medium text-foreground">{{ selectedJob.failed_job.queue }}</p>
              </div>
              <div>
                <p class="text-xs text-muted-foreground">Failed At</p>
                <p class="text-sm font-medium text-foreground">{{ formatDateString(selectedJob.failed_job.failed_at) }}</p>
                <p class="text-xs text-muted-foreground/70">{{ formatRelativeTimeString(selectedJob.failed_job.failed_at) }}</p>
              </div>
            </div>
            <div class="mt-4">
              <p class="text-xs text-muted-foreground mb-2">Payload</p>
              <pre class="text-xs bg-accent/50 p-3 rounded overflow-x-auto">{{ JSON.stringify(parsePayload(selectedJob.failed_job.payload), null, 2) }}</pre>
            </div>
            <div class="mt-4">
              <p class="text-xs text-muted-foreground mb-2">Exception</p>
              <pre class="text-xs bg-red-50 p-3 rounded overflow-x-auto text-red-800">{{ selectedJob.failed_job.exception }}</pre>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
