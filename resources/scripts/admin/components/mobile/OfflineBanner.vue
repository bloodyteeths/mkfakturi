<template>
  <Transition name="slide-down">
    <div
      v-if="isOffline"
      data-cy="offline-message"
      class="fixed top-0 inset-x-0 z-[60] bg-amber-500 text-white text-center py-2 px-4 text-sm font-medium shadow-md"
    >
      <div class="flex items-center justify-center space-x-2">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="h-4 w-4 flex-shrink-0"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          stroke-width="2"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M18.364 5.636a9 9 0 010 12.728M5.636 18.364a9 9 0 010-12.728M12 9v4m0 4h.01"
          />
        </svg>
        <span>Офлајн - работите без интернет конекција</span>
      </div>
    </div>
  </Transition>

  <Transition name="slide-down">
    <div
      v-if="showSyncIndicator"
      data-cy="sync-indicator"
      class="fixed top-0 inset-x-0 z-[60] bg-green-500 text-white text-center py-2 px-4 text-sm font-medium shadow-md"
    >
      <div class="flex items-center justify-center space-x-2">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="h-4 w-4 flex-shrink-0 animate-spin"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          stroke-width="2"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
          />
        </svg>
        <span>Синхронизација...</span>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'

const isOffline = ref(!navigator.onLine)
const showSyncIndicator = ref(false)

function handleOnline() {
  isOffline.value = false
  // Briefly show sync indicator when coming back online
  showSyncIndicator.value = true
  setTimeout(() => {
    showSyncIndicator.value = false
  }, 3000)
}

function handleOffline() {
  isOffline.value = true
  showSyncIndicator.value = false
}

onMounted(() => {
  window.addEventListener('online', handleOnline)
  window.addEventListener('offline', handleOffline)
})

onBeforeUnmount(() => {
  window.removeEventListener('online', handleOnline)
  window.removeEventListener('offline', handleOffline)
})
</script>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
  transition: transform 0.3s ease;
}
.slide-down-enter-from,
.slide-down-leave-to {
  transform: translateY(-100%);
}
</style>
