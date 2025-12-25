<template>
  <div
    class="flex items-center justify-between px-4 py-3 rounded-lg border transition-colors"
    :class="[
      isEnabled
        ? 'bg-green-50 border-green-200'
        : 'bg-gray-50 border-gray-200'
    ]"
  >
    <div class="flex items-center space-x-3">
      <!-- Scanner Icon -->
      <div
        class="flex items-center justify-center w-10 h-10 rounded-full transition-colors"
        :class="[
          isEnabled
            ? 'bg-green-100 text-green-600'
            : 'bg-gray-200 text-gray-500'
        ]"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="w-5 h-5"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          stroke-width="2"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h2M4 12h2m10 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"
          />
        </svg>
      </div>

      <div>
        <p class="text-sm font-medium" :class="isEnabled ? 'text-green-700' : 'text-gray-700'">
          {{ $t('stock.scanner_mode') }}
        </p>
        <p class="text-xs" :class="isEnabled ? 'text-green-600' : 'text-gray-500'">
          <template v-if="isEnabled">
            <span class="inline-flex items-center">
              <span class="relative flex h-2 w-2 mr-1">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
              </span>
              {{ $t('stock.scanner_listening') }}
              <span v-if="scanCount > 0" class="ml-2">
                ({{ scanCount }} {{ $t('stock.items_scanned') }})
              </span>
            </span>
          </template>
          <template v-else>
            {{ $t('stock.scanner_mode_hint') }}
          </template>
        </p>
      </div>
    </div>

    <div class="flex items-center space-x-3">
      <!-- Last scanned item indicator -->
      <div
        v-if="lastScannedItem && isEnabled"
        class="hidden sm:flex items-center px-3 py-1 bg-white rounded-md border border-green-200"
      >
        <span class="text-xs text-gray-500 mr-2">{{ $t('stock.last_scanned') }}:</span>
        <span class="text-sm font-medium text-green-700 truncate max-w-32">
          {{ lastScannedItem.name }}
        </span>
      </div>

      <!-- Error indicator -->
      <div
        v-if="error && isEnabled"
        class="hidden sm:flex items-center px-3 py-1 bg-red-50 rounded-md border border-red-200"
      >
        <span class="text-xs text-red-600 truncate max-w-48">
          {{ error }}
        </span>
      </div>

      <!-- Processing indicator -->
      <div v-if="isProcessing" class="flex items-center">
        <svg class="animate-spin h-5 w-5 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
      </div>

      <!-- Toggle Button -->
      <button
        type="button"
        @click="toggleScanner"
        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
        :class="isEnabled ? 'bg-green-600' : 'bg-gray-200'"
        role="switch"
        :aria-checked="isEnabled"
      >
        <span
          class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
          :class="isEnabled ? 'translate-x-5' : 'translate-x-0'"
        />
      </button>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits, toRefs } from 'vue'

const props = defineProps({
  isEnabled: {
    type: Boolean,
    default: false
  },
  isProcessing: {
    type: Boolean,
    default: false
  },
  lastScannedItem: {
    type: Object,
    default: null
  },
  error: {
    type: String,
    default: null
  },
  scanCount: {
    type: Number,
    default: 0
  }
})

const emit = defineEmits(['toggle'])

function toggleScanner() {
  emit('toggle')
}
</script>
