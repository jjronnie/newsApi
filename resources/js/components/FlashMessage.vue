<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const page = usePage();

const flashMessage = computed(() => {
  return page.props.flash?.success || page.props.flash?.error || null;
});

const messageType = computed(() => {
  return page.props.flash?.error ? 'error' : 'success';
});

const showMessage = ref(false);
const messageText = ref('');

watch(flashMessage, (newVal) => {
  if (newVal) {
    messageText.value = newVal;
    showMessage.value = true;
    setTimeout(() => {
      showMessage.value = false;
    }, 4000);
  }
}, { immediate: true });
</script>

<template>
  <div
    v-if="showMessage"
    class="fixed top-4 right-4 z-50 max-w-md p-4 rounded-lg shadow-lg transition-all duration-300"
    :class="messageType === 'error' ? 'bg-red-100 text-red-800 border border-red-300' : 'bg-green-100 text-green-800 border border-green-300'"
  >
    <div class="flex items-center justify-between">
      <span class="text-sm font-medium">{{ messageText }}</span>
      <button
        @click="showMessage = false"
        class="ml-2 text-lg leading-none hover:opacity-70"
      >
        ×
      </button>
    </div>
  </div>
</template>
