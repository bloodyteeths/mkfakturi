<template>
  <BaseCard v-if="hasFiscalDevices">
    <template #header>
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900">
          {{ $t('fiscal.title') }}
        </h3>
        <span
          class="inline-flex h-3 w-3 rounded-full"
          :class="isConnected ? 'bg-green-400' : 'bg-gray-300'"
        ></span>
      </div>
    </template>

    <!-- Connected State -->
    <div v-if="isConnected" class="space-y-3">
      <div class="flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 p-3">
        <svg class="h-8 w-8 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18.75 9H5.25" />
        </svg>
        <div class="min-w-0 flex-1">
          <p class="text-sm font-medium text-green-800">
            {{ deviceInfo?.model || $t('fiscal.status_connected') }}
          </p>
          <p v-if="deviceInfo?.fiscalMemorySerial" class="text-xs text-green-600 truncate">
            FM: {{ deviceInfo.fiscalMemorySerial }}
          </p>
        </div>
      </div>

      <!-- Status indicators -->
      <div v-if="statusSummary" class="flex items-center gap-2 text-xs">
        <span
          class="inline-flex items-center gap-1 rounded-full px-2 py-0.5"
          :class="statusSummary.paperOut ? 'bg-red-100 text-red-700' : statusSummary.paperLow ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700'"
        >
          <span class="inline-block h-1.5 w-1.5 rounded-full" :class="statusSummary.paperOut ? 'bg-red-500' : statusSummary.paperLow ? 'bg-yellow-500' : 'bg-green-500'"></span>
          {{ statusSummary.paperOut ? $t('fiscal.paper_out') : statusSummary.paperLow ? $t('fiscal.paper_low') : $t('fiscal.paper_ok') }}
        </span>
        <span
          class="inline-flex items-center gap-1 rounded-full px-2 py-0.5"
          :class="statusSummary.fiscalMemoryFull ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'"
        >
          <span class="inline-block h-1.5 w-1.5 rounded-full" :class="statusSummary.fiscalMemoryFull ? 'bg-red-500' : 'bg-green-500'"></span>
          {{ statusSummary.fiscalMemoryFull ? $t('fiscal.memory_full') : $t('fiscal.memory_ok') }}
        </span>
      </div>

      <!-- Quick actions -->
      <div class="flex gap-2 pt-1">
        <button
          class="text-xs font-medium text-primary-600 hover:text-primary-700"
          @click="refreshStatus"
          :disabled="isProcessing"
        >
          {{ $t('fiscal.check_status') }}
        </button>
        <span class="text-gray-300">|</span>
        <router-link
          to="/admin/settings"
          class="text-xs font-medium text-gray-500 hover:text-gray-700"
        >
          {{ $t('general.settings') || 'Settings' }}
        </router-link>
      </div>
    </div>

    <!-- Not connected — show connect prompt -->
    <div v-else class="text-center py-4">
      <svg class="h-12 w-12 mx-auto text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18.75 9H5.25" />
      </svg>
      <p class="text-sm text-gray-500 mb-3">
        {{ $t('fiscal.not_connected_dashboard') }}
      </p>
      <button
        v-if="isSupported"
        class="inline-flex items-center gap-1.5 rounded-md bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700"
        @click="connectPrinter"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-2.44a4.5 4.5 0 00-1.242-7.244l4.5-4.5a4.5 4.5 0 016.364 6.364l-1.757 1.757" />
        </svg>
        {{ $t('fiscal.connect_usb') }}
      </button>
      <p v-else class="text-xs text-gray-400">
        {{ $t('fiscal.not_supported_short') }}
      </p>
    </div>
  </BaseCard>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useFiscalPrinter } from '@/scripts/admin/composables/useFiscalPrinter'
import axios from 'axios'

const {
  isSupported,
  isConnected,
  isProcessing,
  deviceInfo,
  statusSummary,
  connect,
  getStatus,
} = useFiscalPrinter()

const hasFiscalDevices = ref(false)

onMounted(async () => {
  try {
    const { data } = await axios.get('/fiscal-devices')
    hasFiscalDevices.value = (data.data || []).length > 0
  } catch (_e) {
    hasFiscalDevices.value = false
  }
})

async function connectPrinter() {
  try {
    await connect()
  } catch (_e) {
    // Notification already shown by composable
  }
}

async function refreshStatus() {
  try {
    await getStatus()
  } catch (_e) {
    // Silent
  }
}
</script>

<!-- CLAUDE-CHECKPOINT -->
