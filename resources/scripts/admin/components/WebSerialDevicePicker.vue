<script setup>
/**
 * WebSerialDevicePicker — Settings page component for connecting fiscal printers via USB.
 *
 * Shows:
 * - Browser compatibility status
 * - "Connect USB Device" button → triggers port picker
 * - Connected device info (model, serial, firmware)
 * - Status check button
 * - Connection log
 */
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useFiscalPrinter } from '@/scripts/admin/composables/useFiscalPrinter'

const { t } = useI18n()

const {
  isSupported,
  isConnected,
  isProcessing,
  deviceInfo,
  lastStatus,
  error,
  statusSummary,
  connectionLog,
  connect,
  disconnect,
  getStatus,
} = useFiscalPrinter()

const showLog = ref(false)

async function handleConnect() {
  try {
    await connect()
  } catch (_e) {
    // Handled in composable
  }
}

async function handleTestStatus() {
  try {
    await getStatus()
  } catch (_e) {
    // Handled in composable
  }
}
</script>

<template>
  <div class="rounded-lg border p-4 space-y-3">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <h4 class="text-sm font-medium text-gray-900">
        USB {{ t('fiscal.connection') }}
      </h4>
      <span
        class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full"
        :class="
          isConnected
            ? 'bg-green-100 text-green-800'
            : isSupported
              ? 'bg-gray-100 text-gray-600'
              : 'bg-red-100 text-red-800'
        "
      >
        <span
          class="w-1.5 h-1.5 rounded-full"
          :class="isConnected ? 'bg-green-500' : isSupported ? 'bg-gray-400' : 'bg-red-500'"
        />
        {{
          isConnected
            ? t('fiscal.status_connected')
            : isSupported
              ? t('fiscal.status_ready')
              : t('fiscal.status_unsupported')
        }}
      </span>
    </div>

    <!-- Browser not supported -->
    <div
      v-if="!isSupported"
      class="p-3 rounded-md bg-amber-50 text-sm text-amber-800"
    >
      <p class="font-medium">{{ t('fiscal.browser_unsupported_title') }}</p>
      <p class="mt-1 text-xs">{{ t('fiscal.not_supported') }}</p>
    </div>

    <!-- Not connected -->
    <template v-else-if="!isConnected">
      <p class="text-sm text-gray-500">
        {{ t('fiscal.connect_description') }}
      </p>
      <button
        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-md border border-primary-300 bg-primary-50 text-primary-700 hover:bg-primary-100 transition-colors"
        @click="handleConnect"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
        </svg>
        {{ t('fiscal.connect_usb') }}
      </button>
    </template>

    <!-- Connected: device info -->
    <template v-else>
      <div class="grid grid-cols-2 gap-2 text-sm">
        <div class="text-gray-500">{{ t('fiscal.device_model') }}</div>
        <div class="font-medium text-gray-900">{{ deviceInfo?.model || '—' }}</div>

        <div class="text-gray-500">{{ t('fiscal.firmware') }}</div>
        <div class="text-gray-900">{{ deviceInfo?.firmwareVersion || '—' }}</div>

        <div class="text-gray-500">{{ t('fiscal.fm_serial') }}</div>
        <div class="font-mono text-xs text-gray-900">{{ deviceInfo?.fiscalMemorySerial || '—' }}</div>
      </div>

      <!-- Status indicators -->
      <div v-if="statusSummary" class="flex flex-wrap gap-2">
        <span
          class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full"
          :class="statusSummary.paperOut ? 'bg-red-100 text-red-800' : statusSummary.paperLow ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'"
        >
          {{ statusSummary.paperOut ? t('fiscal.paper_out') : statusSummary.paperLow ? t('fiscal.paper_low') : t('fiscal.paper_ok') }}
        </span>
        <span
          class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full"
          :class="statusSummary.fiscalMemoryFull ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'"
        >
          {{ statusSummary.fiscalMemoryFull ? t('fiscal.memory_full') : t('fiscal.memory_ok') }}
        </span>
        <span
          v-if="statusSummary.fiscalized"
          class="inline-flex items-center px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-800"
        >
          {{ t('fiscal.fiscalized') }}
        </span>
      </div>

      <!-- Action buttons -->
      <div class="flex gap-2">
        <button
          class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50"
          :disabled="isProcessing"
          @click="handleTestStatus"
        >
          {{ t('fiscal.check_status') }}
        </button>
        <button
          class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-md border border-red-200 bg-white text-red-600 hover:bg-red-50"
          @click="disconnect"
        >
          {{ t('fiscal.disconnect') }}
        </button>
      </div>
    </template>

    <!-- Error display -->
    <div v-if="error" class="p-2 rounded-md bg-red-50 text-xs text-red-700">
      {{ error }}
    </div>

    <!-- Connection log toggle -->
    <div v-if="connectionLog.length > 0" class="pt-2 border-t border-gray-100">
      <button
        class="text-xs text-gray-400 hover:text-gray-600"
        @click="showLog = !showLog"
      >
        {{ t('fiscal.connection_log') }} ({{ connectionLog.length }})
        <span v-if="showLog">&#9650;</span>
        <span v-else>&#9660;</span>
      </button>
      <div v-if="showLog" class="mt-2 max-h-32 overflow-y-auto space-y-1">
        <div
          v-for="(entry, idx) in connectionLog"
          :key="idx"
          class="text-xs font-mono"
          :class="{
            'text-red-600': entry.type === 'error',
            'text-green-600': entry.type === 'connected' || entry.type === 'receipt',
            'text-gray-500': entry.type === 'disconnected',
            'text-blue-600': entry.type === 'z-report',
          }"
        >
          <span class="text-gray-400">{{ entry.timestamp }}</span>
          [{{ entry.type }}] {{ entry.message }}
        </div>
      </div>
    </div>
  </div>
</template>
