<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="show" class="fixed inset-0 z-50 flex flex-col bg-gray-900/80">
        <!-- Toolbar -->
        <div class="flex items-center justify-between px-4 py-2 bg-white border-b border-gray-200 shadow-sm">
          <div class="flex items-center space-x-3">
            <h3 class="text-sm font-medium text-gray-900 truncate max-w-md">
              {{ title || $t('general.pdf_preview', 'PDF Preview') }}
            </h3>
          </div>
          <div class="flex items-center space-x-2">
            <button
              class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500"
              @click="$emit('download')"
            >
              <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              {{ $t('general.download', 'Download') }}
            </button>
            <button
              class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-400"
              @click="$emit('close')"
            >
              <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
              {{ $t('general.close', 'Close') }}
            </button>
          </div>
        </div>

        <!-- PDF iframe -->
        <div class="flex-1 p-4">
          <iframe
            v-if="pdfUrl"
            :src="pdfUrl"
            class="w-full h-full bg-white rounded-lg shadow-lg"
          />
          <div v-else class="flex items-center justify-center w-full h-full">
            <div class="text-center">
              <svg class="mx-auto h-12 w-12 text-gray-400 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
              </svg>
              <p class="mt-2 text-sm text-gray-300">{{ $t('general.loading', 'Loading...') }}</p>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  pdfUrl: {
    type: String,
    default: null,
  },
  title: {
    type: String,
    default: '',
  },
})

defineEmits(['close', 'download'])
</script>
