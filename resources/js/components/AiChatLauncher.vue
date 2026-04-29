<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { index as articleChatIndex } from '@/routes/admin/articles/chat';
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const userName = computed(() => page.props.auth?.user?.name ?? null);
const isChatPage = computed(() => page.url.startsWith('/admin/articles/chat'));

const openChat = () => {
    if (! userName.value || isChatPage.value) {
        return;
    }

    router.visit(articleChatIndex());
};
</script>

<template>
    <div v-if="userName && !isChatPage" class="fixed right-5 bottom-5 z-50">
        <Button
            type="button"
            class="h-auto rounded-full px-4 py-3 shadow-lg"
            @click="openChat"
        >
            Lolo
        </Button>
    </div>
</template>
