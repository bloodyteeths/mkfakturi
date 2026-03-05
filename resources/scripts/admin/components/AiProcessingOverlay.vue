<template>
  <Transition
    enter-active-class="transition ease-out duration-300"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition ease-in duration-200"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div
      v-if="visible"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
    >
      <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full mx-4 p-8">
        <!-- Logo -->
        <div class="flex justify-center mb-6">
          <img
            :src="logoUrl"
            alt="Facturino"
            class="h-10"
          />
        </div>

        <!-- Animated Flow Diagram -->
        <div class="flex items-center justify-center gap-2 mb-8">
          <!-- Document Icon -->
          <div class="flex-shrink-0">
            <svg
              class="w-10 h-10 text-gray-400"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke-width="1.5"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"
              />
            </svg>
          </div>

          <!-- Flowing Dots (left) -->
          <div class="flex items-center gap-1">
            <span
              v-for="i in 3"
              :key="'left-' + i"
              class="flow-dot block w-1.5 h-1.5 rounded-full bg-primary-400"
              :style="{ animationDelay: (i - 1) * 0.2 + 's' }"
            />
          </div>

          <!-- AI Sparkle Icon (center) -->
          <div class="flex-shrink-0 flex flex-col items-center">
            <div class="ai-pulse p-3 rounded-xl bg-primary-50">
              <svg
                class="w-10 h-10 text-primary-600"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"
                />
              </svg>
            </div>
            <span class="mt-1.5 text-xs font-semibold text-primary-600">Facturino AI</span>
          </div>

          <!-- Flowing Dots (right) -->
          <div class="flex items-center gap-1">
            <span
              v-for="i in 3"
              :key="'right-' + i"
              class="flow-dot block w-1.5 h-1.5 rounded-full bg-primary-400"
              :style="{ animationDelay: (i - 1) * 0.2 + 0.6 + 's' }"
            />
          </div>

          <!-- Result Icon -->
          <div class="flex-shrink-0">
            <svg
              v-if="isComplete"
              class="w-10 h-10 text-green-500"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke-width="1.5"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
            <svg
              v-else
              class="w-10 h-10 text-gray-400"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke-width="1.5"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M10.125 2.25h-4.5c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125v-9M10.125 2.25h.375a9 9 0 019 9v.375M10.125 2.25A3.375 3.375 0 0113.5 5.625v1.5c0 .621.504 1.125 1.125 1.125h1.5a3.375 3.375 0 013.375 3.375M9 15l2.25 2.25L15 12"
              />
            </svg>
          </div>
        </div>

        <!-- Step List -->
        <div class="space-y-2">
          <div
            v-for="(step, index) in steps"
            :key="index"
            class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition-colors"
            :class="index === currentStep ? 'bg-primary-50' : ''"
          >
            <!-- Step Icon -->
            <div class="flex-shrink-0 w-5 h-5">
              <!-- Completed: green checkmark -->
              <svg
                v-if="index < currentStep"
                class="w-5 h-5 text-green-500"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="currentColor"
              >
                <path
                  fill-rule="evenodd"
                  d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z"
                  clip-rule="evenodd"
                />
              </svg>

              <!-- Current: spinner -->
              <svg
                v-else-if="index === currentStep"
                class="w-5 h-5 text-primary-600 animate-spin"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                />
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                />
              </svg>

              <!-- Pending: empty circle -->
              <svg
                v-else
                class="w-5 h-5 text-gray-300"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
              >
                <circle cx="12" cy="12" r="9" />
              </svg>
            </div>

            <!-- Step Text -->
            <span
              class="text-sm font-medium"
              :class="{
                'text-green-700': index < currentStep,
                'text-primary-700': index === currentStep,
                'text-gray-400': index > currentStep,
              }"
            >
              {{ step }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  visible: {
    type: Boolean,
    default: false,
  },
  currentStep: {
    type: Number,
    default: 0,
  },
  steps: {
    type: Array,
    default: () => [],
  },
})

const logoUrl = new URL('$images/facturino_logo_clear.png', import.meta.url).href

const isComplete = computed(() => {
  return props.steps.length > 0 && props.currentStep >= props.steps.length - 1
})
</script>

<style scoped>
.flow-dot {
  animation: flowDot 1.4s ease-in-out infinite;
}

@keyframes flowDot {
  0%, 100% { opacity: 0.3; transform: scale(0.8); }
  50% { opacity: 1; transform: scale(1.2); }
}

.ai-pulse {
  animation: aiPulse 2s ease-in-out infinite;
}

@keyframes aiPulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(var(--tw-color-primary-400), 0.4); }
  50% { box-shadow: 0 0 0 8px rgba(var(--tw-color-primary-400), 0); }
}
</style>

<!-- CLAUDE-CHECKPOINT -->
