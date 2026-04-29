<script setup lang="ts">
import AiChatLauncher from '@/components/AiChatLauncher.vue';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';
import FlashMessage from '@/components/FlashMessage.vue';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <FlashMessage />
        <slot />
        <AiChatLauncher />
    </AppLayout>
</template>
