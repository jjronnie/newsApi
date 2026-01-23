<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'

interface Website {
  id: number
  name: string
  url: string
  last_updated: string | null
  created_at: string
}

interface Props {
  websites: Website[]
}

const props = defineProps<Props>()

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Websites',
    href: '/websites',
  },
]

const showForm = ref(false)
const loading = ref(false)
const form = ref({
  name: '',
  url: '',
})
const formError = ref('')
const showDeleteModal = ref(false)
const showEditModal = ref(false)
const selectedWebsite = ref<Website | null>(null)
const editLoading = ref(false)
const editError = ref('')
const deleteLoading = ref(false)
const deleteError = ref('')
const editForm = ref({
  name: '',
  url: '',
})

const getCookie = (name: string) => {
  const value = `; ${document.cookie}`
  const parts = value.split(`; ${name}=`)
  if (parts.length === 2) {
    return parts.pop()?.split(';').shift() || ''
  }
  return ''
}

const getCsrfToken = () => {
  const metaToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
  if (metaToken) return metaToken
  const cookieToken = getCookie('XSRF-TOKEN')
  return cookieToken ? decodeURIComponent(cookieToken) : ''
}

const parseResponse = async (response: Response) => {
  const contentType = response.headers.get('content-type') || ''
  if (contentType.includes('application/json')) {
    return await response.json()
  }
  const text = await response.text()
  return { message: text }
}

const submitForm = async () => {
  loading.value = true
  formError.value = ''

  try {
    const response = await fetch('/websites', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: JSON.stringify(form.value),
    })

    const data = await parseResponse(response)

    if (response.ok) {
      form.value = { name: '', url: '' }
      showForm.value = false
      // Reload page to show new website
      router.reload()
    } else {
      formError.value =
        data.message ||
        data.errors?.url?.[0] ||
        data.errors?.name?.[0] ||
        'Failed to add website'
    }
  } catch (err) {
    formError.value = 'An error occurred. Please try again.'
    console.error('Error:', err)
  } finally {
    loading.value = false
  }
}

const openDeleteModal = (website: Website) => {
  selectedWebsite.value = website
  showDeleteModal.value = true
}

const closeDeleteModal = () => {
  showDeleteModal.value = false
  selectedWebsite.value = null
}

const deleteWebsite = async () => {
  if (!selectedWebsite.value) return

  deleteLoading.value = true
  deleteError.value = ''

  try {
    const response = await fetch(`/websites/${selectedWebsite.value.id}`, {
      method: 'DELETE',
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    })

    const data = await parseResponse(response)

    if (response.ok) {
      closeDeleteModal()
      router.reload()
    } else {
      deleteError.value = data.message || 'Failed to delete website'
    }
  } catch (err) {
    deleteError.value = 'An error occurred. Please try again.'
    console.error('Error deleting website:', err)
  } finally {
    deleteLoading.value = false
  }
}

const openEditModal = (website: Website) => {
  selectedWebsite.value = website
  editForm.value = {
    name: website.name,
    url: website.url,
  }
  editError.value = ''
  showEditModal.value = true
}

const closeEditModal = () => {
  showEditModal.value = false
  selectedWebsite.value = null
}

const updateWebsite = async () => {
  if (!selectedWebsite.value) return
  editLoading.value = true
  editError.value = ''

  try {
    const response = await fetch(`/websites/${selectedWebsite.value.id}`, {
      method: 'PUT',
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: JSON.stringify(editForm.value),
    })

    const data = await parseResponse(response)

    if (response.ok) {
      closeEditModal()
      router.reload()
    } else {
      editError.value =
        data.message ||
        data.errors?.url?.[0] ||
        data.errors?.name?.[0] ||
        'Failed to update website'
    }
  } catch (err) {
    editError.value = 'An error occurred. Please try again.'
    console.error('Error updating website:', err)
  } finally {
    editLoading.value = false
  }
}

const formatDate = (dateString: string | null) => {
  if (!dateString) return 'Never'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>

<template>
  <Head title="Websites" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <!-- Header with Add Button -->
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-foreground">Your Websites</h2>
        <button
          @click="showForm = !showForm"
          class="px-4 py-2 bg-primary text-primary-foreground rounded hover:opacity-90 transition-opacity text-sm font-medium"
        >
          {{ showForm ? 'Cancel' : '+ Add Website' }}
        </button>
      </div>

      <!-- Add Website Form -->
      <div v-if="showForm" class="border border-sidebar-border/70 rounded-lg p-4 dark:border-sidebar-border bg-accent/20">
        <h3 class="text-lg font-semibold text-foreground mb-4">Add New Website</h3>
        
        <div v-if="formError" class="mb-4 p-3 bg-destructive/10 border border-destructive/30 text-destructive rounded">
          {{ formError }}
        </div>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-foreground mb-2">Website Name</label>
            <input
              v-model="form.name"
              type="text"
              placeholder="e.g., The Tech Tower"
              class="w-full px-3 py-2 border border-sidebar-border/70 rounded bg-background text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary dark:border-sidebar-border"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-foreground mb-2">Website URL</label>
            <input
              v-model="form.url"
              type="url"
              placeholder="e.g., thetechtower.com or https://thetechtower.com"
              class="w-full px-3 py-2 border border-sidebar-border/70 rounded bg-background text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary dark:border-sidebar-border"
            />
          </div>

          <button
            @click="submitForm"
            :disabled="loading"
            class="w-full px-4 py-2 bg-primary text-primary-foreground rounded hover:opacity-90 transition-opacity disabled:opacity-50 font-medium"
          >
            {{ loading ? 'Adding...' : 'Add Website' }}
          </button>
        </div>
      </div>

      <!-- Websites Table -->
      <div v-if="props.websites.length > 0" class="border border-sidebar-border/70 rounded-lg overflow-hidden dark:border-sidebar-border">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-sidebar-border/70 bg-accent/50 dark:border-sidebar-border">
                <th class="px-4 py-3 text-left font-semibold text-foreground">Website Name</th>
                <th class="px-4 py-3 text-left font-semibold text-foreground">URL</th>
                <th class="px-4 py-3 text-left font-semibold text-foreground">Last Updated</th>
                <th class="px-4 py-3 text-left font-semibold text-foreground">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="website in props.websites"
                :key="website.id"
                class="border-b border-sidebar-border/70 hover:bg-accent/30 transition-colors dark:border-sidebar-border"
              >
                <td class="px-4 py-3 font-medium text-foreground">{{ website.name }}</td>
                <td class="px-4 py-3 text-muted-foreground text-xs truncate">{{ website.url }}</td>
                <td class="px-4 py-3 text-muted-foreground text-xs">
                  {{ formatDate(website.last_updated) }}
                </td>
                <td class="px-4 py-3">
                  <div class="flex gap-2">
                    <a
                      :href="`/websites/${website.id}`"
                      class="inline-flex items-center rounded-md border border-primary/30 px-3 py-1.5 text-xs font-semibold text-primary hover:bg-primary/10 transition-colors"
                    >
                      Show
                    </a>
                    <button
                      @click="openEditModal(website)"
                      class="inline-flex items-center rounded-md border border-muted-foreground/30 px-3 py-1.5 text-xs font-semibold text-foreground hover:bg-accent/70 transition-colors"
                    >
                      Edit
                    </button>
                    <button
                      @click="openDeleteModal(website)"
                      class="inline-flex items-center rounded-md border border-destructive/30 px-3 py-1.5 text-xs font-semibold text-destructive hover:bg-destructive/10 transition-colors"
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

      <!-- Empty State -->
      <div v-else class="text-center py-12">
        <p class="text-muted-foreground mb-4">No websites added yet.</p>
        <button
          @click="showForm = true"
          class="text-primary hover:underline font-medium"
        >
          Add your first website
        </button>
      </div>

      <!-- Edit Modal -->
      <div
        v-if="showEditModal && selectedWebsite"
        class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4"
        @click.self="closeEditModal"
      >
        <div class="bg-background border border-sidebar-border/70 rounded-lg w-full max-w-lg dark:border-sidebar-border">
          <div class="flex items-start justify-between border-b border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <div>
              <h3 class="text-lg font-semibold text-foreground">Edit Website</h3>
              <p class="text-xs text-muted-foreground">Update name or URL for {{ selectedWebsite.name }}.</p>
            </div>
            <button
              @click="closeEditModal"
              class="text-muted-foreground hover:text-foreground transition-colors"
            >
              ✕
            </button>
          </div>

          <div class="p-4 space-y-4">
            <div v-if="editError" class="p-3 bg-destructive/10 border border-destructive/30 text-destructive rounded">
              {{ editError }}
            </div>

            <div>
              <label class="block text-sm font-medium text-foreground mb-2">Website Name</label>
              <input
                v-model="editForm.name"
                type="text"
                class="w-full px-3 py-2 border border-sidebar-border/70 rounded bg-background text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary dark:border-sidebar-border"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-foreground mb-2">Website URL</label>
              <input
                v-model="editForm.url"
                type="text"
                class="w-full px-3 py-2 border border-sidebar-border/70 rounded bg-background text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary dark:border-sidebar-border"
              />
            </div>
          </div>

          <div class="flex justify-end gap-2 border-t border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <button
              @click="closeEditModal"
              class="px-3 py-2 rounded-md border border-sidebar-border/70 text-foreground text-sm font-medium hover:bg-accent/50 transition-colors dark:border-sidebar-border"
            >
              Cancel
            </button>
            <button
              @click="updateWebsite"
              :disabled="editLoading"
              class="px-4 py-2 rounded-md bg-primary text-primary-foreground text-sm font-semibold hover:opacity-90 transition-opacity disabled:opacity-50"
            >
              {{ editLoading ? 'Saving...' : 'Save Changes' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Delete Modal -->
      <div
        v-if="showDeleteModal && selectedWebsite"
        class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4"
        @click.self="closeDeleteModal"
      >
        <div class="bg-background border border-sidebar-border/70 rounded-lg w-full max-w-md dark:border-sidebar-border">
          <div class="flex items-start justify-between border-b border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <div>
              <h3 class="text-lg font-semibold text-foreground">Delete Website</h3>
              <p class="text-xs text-muted-foreground">This action cannot be undone.</p>
            </div>
            <button
              @click="closeDeleteModal"
              :disabled="deleteLoading"
              class="text-muted-foreground hover:text-foreground transition-colors disabled:opacity-50"
            >
              ✕
            </button>
          </div>

          <div class="p-4 space-y-4">
            <div v-if="deleteError" class="p-3 bg-destructive/10 border border-destructive/30 text-destructive rounded">
              {{ deleteError }}
            </div>

            <div class="text-sm text-foreground">
              Are you sure you want to delete <span class="font-semibold">{{ selectedWebsite.name }}</span>?
            </div>
          </div>

          <div class="flex justify-end gap-2 border-t border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <button
              @click="closeDeleteModal"
              :disabled="deleteLoading"
              class="px-3 py-2 rounded-md border border-sidebar-border/70 text-foreground text-sm font-medium hover:bg-accent/50 transition-colors disabled:opacity-50 dark:border-sidebar-border"
            >
              Cancel
            </button>
            <button
              @click="deleteWebsite"
              :disabled="deleteLoading"
              class="px-4 py-2 rounded-md bg-destructive text-destructive-foreground text-sm font-semibold hover:opacity-90 transition-opacity disabled:opacity-50"
            >
              {{ deleteLoading ? 'Deleting...' : 'Delete Website' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
