<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

defineProps<{
  stats: {
    articles: {
      total: number;
      pushed: number;
      generated: number;
      pending: number;
      failed: number;
      today: number;
    };
    users: number;
    websites: number;
  };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <h1 class="text-2xl font-bold">Dashboard</h1>

            <!-- Stats Grid -->
            <div>
                <h2 class="mb-3 text-lg font-semibold text-foreground">Overview</h2>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">Total Articles</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats.articles.total }}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">Published</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold text-emerald-600">{{ stats.articles.pushed }}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">Generated</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold text-green-600">{{ stats.articles.generated }}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">Pending</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold text-gray-600">{{ stats.articles.pending }}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">Failed</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold text-red-600">{{ stats.articles.failed }}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">Generated Today</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold text-blue-600">{{ stats.articles.today }}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">Users</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats.users }}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">Websites</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats.websites }}</div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Quick Actions -->
            <div>
                <h2 class="mb-3 text-lg font-semibold text-foreground">Quick Actions</h2>
                <div class="flex gap-3">
                    <a
                        href="/admin/articles"
                        class="inline-flex items-center rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90 transition-colors"
                    >
                        Manage Articles
                    </a>
                    <a
                        href="/admin/topics"
                        class="inline-flex items-center rounded-md border border-input bg-background px-4 py-2 text-sm font-semibold hover:bg-accent hover:text-accent-foreground transition-colors"
                    >
                        Manage Topics
                    </a>
                    <a
                        href="/admin/articles/chat"
                        class="inline-flex items-center rounded-md border border-blue-300 px-4 py-2 text-sm font-semibold text-blue-600 hover:bg-blue-50 transition-colors"
                    >
                        Generate with AI
                    </a>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
