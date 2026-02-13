<template>
  <div
    ref="container"
    class="relative"
    @touchstart.passive="onTouchStart"
    @touchmove.passive="onTouchMove"
    @touchend.passive="onTouchEnd"
  >
    <Transition name="fade">
      <div
        v-if="pullDistance > 0"
        data-cy="pull-refresh-indicator"
        class="absolute top-0 left-0 right-0 flex items-center justify-center overflow-hidden md:hidden"
        :style="{ height: `${Math.min(pullDistance, maxPull)}px` }"
      >
        <div
          class="flex items-center space-x-2 text-sm text-gray-500"
          :class="{ 'text-primary-500': isTriggered }"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-5 w-5 transition-transform duration-200"
            :class="{ 'rotate-180': isTriggered, 'animate-spin': isRefreshing }"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
          >
            <path
              v-if="!isRefreshing"
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M19 14l-7 7m0 0l-7-7m7 7V3"
            />
            <path
              v-else
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
            />
          </svg>
          <span>{{ statusText }}</span>
        </div>
      </div>
    </Transition>

    <div
      :style="pullDistance > 0 ? { transform: `translateY(${Math.min(pullDistance, maxPull)}px)`, transition: isTouching ? 'none' : 'transform 0.3s ease' } : {}"
    >
      <slot />
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const emit = defineEmits(['refresh'])

const container = ref(null)
const pullDistance = ref(0)
const isTouching = ref(false)
const isRefreshing = ref(false)
const startY = ref(0)
const threshold = 60
const maxPull = 80

const isTriggered = computed(() => pullDistance.value >= threshold)

const statusText = computed(() => {
  if (isRefreshing.value) return 'Освежување...'
  if (isTriggered.value) return 'Пушти за освежување'
  return 'Повлечи надолу'
})

function onTouchStart(event) {
  // Only activate when scrolled to top
  const scrollContainer = container.value?.closest('.overflow-y-auto')
  if (scrollContainer && scrollContainer.scrollTop > 0) return

  startY.value = event.touches[0].clientY
  isTouching.value = true
}

function onTouchMove(event) {
  if (!isTouching.value || isRefreshing.value) return

  const currentY = event.touches[0].clientY
  const diff = currentY - startY.value

  if (diff > 0) {
    // Apply resistance — the further you pull, the harder it gets
    pullDistance.value = diff * 0.5
  }
}

async function onTouchEnd() {
  isTouching.value = false

  if (isTriggered.value && !isRefreshing.value) {
    isRefreshing.value = true
    pullDistance.value = threshold

    emit('refresh')

    // Auto-reset after a reasonable timeout
    setTimeout(() => {
      isRefreshing.value = false
      pullDistance.value = 0
    }, 2000)
  } else {
    pullDistance.value = 0
  }
}

function reset() {
  isRefreshing.value = false
  pullDistance.value = 0
}

defineExpose({ reset })
</script>

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
