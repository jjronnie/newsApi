<template>
  <Head title="News" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
        <span class="ml-4 text-muted-foreground">Loading news...</span>
      </div>

       <!-- Refresh Link -->
      <div v-if="!loading && news.length > 0" class="mt-4 text-center">
        <button
          @click="clearCache"
          class="text-sm text-primary hover:underline transition-colors"
        >
          Refresh News
        </button>
      </div>

      <!-- Error State -->
      <div
        v-if="error && !loading"
        class="mb-6 p-4 bg-destructive/10 border border-destructive/30 text-destructive rounded-lg"
      >
        <p>{{ error }}</p>
        <button
          @click="fetchNews"
          class="mt-2 px-4 py-2 bg-destructive text-destructive-foreground rounded hover:bg-destructive/90 transition-colors"
        >
          Retry
        </button>
      </div>

      <!-- News List -->
      <div v-if="!loading && news.length > 0" class="space-y-2">
        <article
          v-for="article in news"
          :key="article.title"
          class="flex gap-4 p-3 rounded-lg border border-sidebar-border/70 hover:bg-accent/50 transition-colors dark:border-sidebar-border"
        >
          <!-- Featured Image -->
          <a
            :href="article.link"
            target="_blank"
            rel="noopener noreferrer"
            class="shrink-0 w-24 h-24 overflow-hidden rounded bg-muted"
          >
            <img
              v-if="article.image"
              :src="article.image"
              :alt="article.title"
              class="w-full h-full object-cover hover:scale-105 transition-transform"
            />
            <div v-else class="w-full h-full flex items-center justify-center text-muted-foreground text-xs">
              No Image
            </div>
          </a>

          <!-- Content -->
          <div class="flex flex-col grow min-w-0">
            <!-- Source Badge -->
            <span class="text-xs font-semibold text-primary uppercase mb-1">
              {{ article.source }}
            </span>

            <!-- Title -->
            <a
              :href="article.link"
              target="_blank"
              rel="noopener noreferrer"
              class="font-semibold text-foreground hover:text-primary transition-colors line-clamp-2 leading-tight"
            >
              {{ article.title }}
            </a>

            <!-- Date -->
            <div class="text-xs text-muted-foreground mt-1">
              {{ formatDate(article.publishedAt) }}
            </div>
          </div>
        </article>
      </div>

      <!-- No Articles State -->
      <div v-if="!loading && news.length === 0 && !error" class="text-center py-12">
        <p class="text-muted-foreground text-lg">No news articles found. Please try again later.</p>
      </div>

     
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { news as newsRoute } from '@/routes'
import { type BreadcrumbItem } from '@/types'
import { Head } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'

interface Article {
  title: string
  excerpt: string
  image: string | null
  link: string
  source: string
  publishedAt: string | null
  author: string | null
}

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'News',
    href: newsRoute().url,
  },
]

const news = ref<Article[]>([])
const loading = ref(false)
const error = ref('')

const fetchNews = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await fetch('/api/news')
    
    if (!response.ok) {
      throw new Error('Failed to fetch news')
    }

    const data = await response.json()
    
    if (data.success) {
      news.value = data.data
    } else {
      error.value = 'Failed to load news. Please try again later.'
    }
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'An error occurred while fetching news'
    console.error('Error fetching news:', err)
  } finally {
    loading.value = false
  }
}

const clearCache = async () => {
  if (!confirm('Are you sure you want to refresh the news? This will clear the cache.')) {
    return
  }

  try {
    const response = await fetch('/api/news/cache/clear', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
      },
    })

    if (response.ok) {
      await fetchNews()
    }
  } catch (err) {
    console.error('Error clearing cache:', err)
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


onMounted(() => {
  fetchNews()
})
</script>

