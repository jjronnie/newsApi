<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { index as websitesIndexRoute, show as websitesShowRoute } from '@/routes/websites'
import { type BreadcrumbItem } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'

interface Post {
  id: number
  title: string
  excerpt: string
  content: string
  image: string | null
  link: string
  publishedAt: string
  modifiedAt: string
  author: string
}

interface Pagination {
  current_page: number
  per_page: number
  total: number
  total_pages: number
  has_more: boolean
}

interface Props {
  website: {
    id: number
    name: string
    url: string
  }
  posts: Post[]
  pagination: Pagination
  error: string | null
}

const props = defineProps<Props>()

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Websites',
    href: websitesIndexRoute().url,
  },
  {
    title: props.website.name,
    href: websitesShowRoute(props.website.id).url,
  },
]

const posts = ref<Post[]>(props.posts)
const pagination = ref<Pagination>(props.pagination)
const loading = ref(false)
const selectedPost = ref<Post | null>(null)
const showPostModal = ref(false)
const error = ref(props.error)
const showDeleteModal = ref(false)
const showEditModal = ref(false)
const editLoading = ref(false)
const editError = ref('')
const editForm = ref({
  name: props.website.name,
  url: props.website.url,
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

const loadMore = async () => {
  if (!pagination.value.has_more) return

  loading.value = true
  try {
    const response = await fetch(
      `/api/websites/${props.website.id}/posts?page=${pagination.value.current_page + 1}`
    )
    const data = await response.json()

    if (data.posts) {
      posts.value.push(...data.posts)
      pagination.value = data.pagination
    }
  } catch (err) {
    console.error('Error loading more posts:', err)
  } finally {
    loading.value = false
  }
}

const viewPost = (post: Post) => {
  selectedPost.value = post
  showPostModal.value = true
}

const closeModal = () => {
  showPostModal.value = false
  selectedPost.value = null
}

const openDeleteModal = () => {
  showDeleteModal.value = true
}

const closeDeleteModal = () => {
  showDeleteModal.value = false
}

const deleteWebsite = async () => {
  try {
    const response = await fetch(`/websites/${props.website.id}`, {
      method: 'DELETE',
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    })

    if (response.ok) {
      router.visit('/websites')
    }
  } catch (err) {
    console.error('Error deleting website:', err)
  } finally {
    closeDeleteModal()
  }
}

const openEditModal = () => {
  editForm.value = {
    name: props.website.name,
    url: props.website.url,
  }
  editError.value = ''
  showEditModal.value = true
}

const closeEditModal = () => {
  showEditModal.value = false
}

const updateWebsite = async () => {
  editLoading.value = true
  editError.value = ''

  try {
    const response = await fetch(`/websites/${props.website.id}`, {
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

const formatDate = (dateString: string | null): string => {
  if (!dateString) return 'Unknown'

  const date = new Date(dateString)
  if (isNaN(date.getTime())) return 'Unknown'

  const now = new Date()
  const diffInSeconds = Math.floor((now.getTime() - date.getTime()) / 1000)

  const rtf = new Intl.RelativeTimeFormat('en', { numeric: 'auto' })

  const units: { unit: Intl.RelativeTimeFormatUnit; seconds: number }[] = [
    { unit: 'year', seconds: 31536000 },
    { unit: 'month', seconds: 2592000 },
    { unit: 'week', seconds: 604800 },
    { unit: 'day', seconds: 86400 },
    { unit: 'hour', seconds: 3600 },
    { unit: 'minute', seconds: 60 },
    { unit: 'second', seconds: 1 },
  ]

  for (const { unit, seconds } of units) {
    const value = Math.floor(diffInSeconds / seconds)
    if (value >= 1) {
      return rtf.format(-value, unit)
    }
  }

  return 'just now'
}

</script>

<template>
  <Head :title="`Posts - ${website.name}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <!-- Header -->
      <div class="flex flex-wrap items-start justify-between gap-3 mb-2">
        <div>
          <h2 class="text-xl font-semibold text-foreground">{{ website.name }}</h2>
          <p class="text-sm text-muted-foreground">{{ website.url }}</p>
        </div>
        <div class="flex gap-2">
          <button
            @click="openEditModal"
            class="inline-flex items-center rounded-md border border-muted-foreground/30 px-3 py-1.5 text-xs font-semibold text-foreground hover:bg-accent/70 transition-colors"
          >
            Edit Website
          </button>
          <button
            @click="openDeleteModal"
            class="inline-flex items-center rounded-md border border-destructive/30 px-3 py-1.5 text-xs font-semibold text-destructive hover:bg-destructive/10 transition-colors"
          >
            Delete Website
          </button>
        </div>
      </div>

      <!-- Error State -->
      <div
        v-if="error"
        class="p-4 bg-destructive/10 border border-destructive/30 text-destructive rounded-lg"
      >
        {{ error }}
      </div>

      <!-- Posts List -->
      <div v-if="posts.length > 0" class="space-y-2">
        <article
          v-for="post in posts"
          :key="post.id"
          class="flex gap-4 p-3 rounded-lg border border-sidebar-border/70 hover:bg-accent/50 transition-colors dark:border-sidebar-border cursor-pointer"
          @click="viewPost(post)"
        >
          <!-- Featured Image -->
          <div class="shrink-0 w-24 h-24 overflow-hidden rounded bg-muted">
            <img
              v-if="post.image"
              :src="post.image"
              :alt="post.title"
              class="w-full h-full object-cover"
            />
            <div v-else class="w-full h-full flex items-center justify-center text-muted-foreground text-xs">
              No Image
            </div>
          </div>

          <!-- Content -->
          <div class="flex flex-col grow min-w-0">
            <!-- Title -->
            <h3 class="font-semibold text-foreground hover:text-primary transition-colors line-clamp-2 leading-tight">
              {{ post.title }}
            </h3>

            <!-- Excerpt -->
            <p class="text-sm text-muted-foreground line-clamp-2 mt-1">
              {{ post.excerpt || 'No excerpt available' }}
            </p>

            <!-- Author and Date -->
            <div class="flex items-center gap-2 text-xs text-muted-foreground mt-2">
              <span v-if="post.author" class="font-medium">{{ post.author }}</span>
              <span v-if="post.author" class="text-xs">•</span>
              <span>{{ formatDate(post.publishedAt) }}</span>
            </div>
          </div>
        </article>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-12">
        <p class="text-muted-foreground">No posts found.</p>
      </div>

      <!-- Load More Button -->
      <div v-if="pagination.has_more && posts.length > 0" class="flex justify-center mt-4">
        <button
          @click="loadMore"
          :disabled="loading"
          class="text-primary hover:underline disabled:opacity-50 font-medium text-sm"
        >
          {{ loading ? 'Loading...' : 'Load More' }}
        </button>
      </div>

      <!-- Post Detail Modal -->
      <div
        v-if="showPostModal && selectedPost"
        class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4"
        @click.self="closeModal"
      >
        <div
          class="bg-background border border-sidebar-border/70 rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto dark:border-sidebar-border"
        >
          <!-- Modal Header -->
          <div class="sticky top-0 bg-background border-b border-sidebar-border/70 p-4 flex justify-between items-start dark:border-sidebar-border">
            <h2 class="text-lg font-semibold text-foreground pr-4 grow">{{ selectedPost.title }}</h2>
            <button
              @click="closeModal"
              class="text-muted-foreground hover:text-foreground transition-colors shrink-0"
            >
              ✕
            </button>
          </div>

          <!-- Modal Content -->
          <div class="p-6">
            <!-- Featured Image -->
            <img
              v-if="selectedPost.image"
              :src="selectedPost.image"
              :alt="selectedPost.title"
              class="w-full h-64 object-cover rounded-lg mb-4"
            />

            <!-- Meta Info -->
            <div class="flex flex-col gap-2 text-sm text-muted-foreground mb-4">
              <div v-if="selectedPost.author">
                <span class="font-medium text-foreground">By:</span> {{ selectedPost.author }}
              </div>
              <div>
                <span class="font-medium text-foreground">Published:</span> {{ formatDate(selectedPost.publishedAt) }}
              </div>
            </div>

            <!-- Content -->
            <div class="prose prose-sm dark:prose-invert max-w-none mb-6">
              <div v-html="selectedPost.content" class="text-foreground leading-relaxed"></div>
            </div>

            <!-- Read on Original Site Link -->
            <a
              :href="selectedPost.link"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-block text-primary hover:underline font-medium"
            >
              Read on {{ website.name }} →
            </a>
          </div>
        </div>
      </div>

      <!-- Edit Website Modal -->
      <div
        v-if="showEditModal"
        class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4"
        @click.self="closeEditModal"
      >
        <div class="bg-background border border-sidebar-border/70 rounded-lg w-full max-w-lg dark:border-sidebar-border">
          <div class="flex items-start justify-between border-b border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <div>
              <h3 class="text-lg font-semibold text-foreground">Edit Website</h3>
              <p class="text-xs text-muted-foreground">Update name or URL for this website.</p>
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

      <!-- Delete Website Modal -->
      <div
        v-if="showDeleteModal"
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
              class="text-muted-foreground hover:text-foreground transition-colors"
            >
              ✕
            </button>
          </div>

          <div class="p-4 text-sm text-foreground">
            Are you sure you want to delete <span class="font-semibold">{{ website.name }}</span>?
          </div>

          <div class="flex justify-end gap-2 border-t border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <button
              @click="closeDeleteModal"
              class="px-3 py-2 rounded-md border border-sidebar-border/70 text-foreground text-sm font-medium hover:bg-accent/50 transition-colors dark:border-sidebar-border"
            >
              Cancel
            </button>
            <button
              @click="deleteWebsite"
              class="px-4 py-2 rounded-md bg-destructive text-destructive-foreground text-sm font-semibold hover:opacity-90 transition-opacity"
            >
              Delete Website
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
