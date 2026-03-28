<script setup>
/**
 * FiscalizeButton — Inline button for printing fiscal receipts
 *
 * Embed in invoice/bill view pages. Handles:
 * - WebSerial feature detection (shows fallback for unsupported browsers)
 * - Connect/disconnect to fiscal printer via browser
 * - Print fiscal receipt with status indicators
 * - Auto-selects first active fiscal device if deviceId not provided
 */
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useFiscalPrinter } from '@/scripts/admin/composables/useFiscalPrinter'
import { useFiscalDeviceStore } from '@/scripts/admin/stores/fiscal-device'
import LoadingIcon from '@/scripts/components/icons/LoadingIcon.vue'

const props = defineProps({
  invoice: { type: Object, required: true },
  deviceId: { type: [Number, String], default: null },
})

const emit = defineEmits(['fiscalized'])

const { t } = useI18n()
const fiscalDeviceStore = useFiscalDeviceStore()

const {
  isSupported,
  isConnected,
  isProcessing,
  deviceInfo,
  lastStatus,
  lastReceipt,
  error,
  canFiscalize,
  statusSummary,
  connect,
  disconnect,
  fiscalizeInvoice,
  getStatus,
} = useFiscalPrinter()

const selectedDeviceId = ref(props.deviceId)
const showSuccess = ref(false)
const alreadyFiscalized = ref(false)

// Check if this invoice already has a fiscal receipt
const hasFiscalReceipt = computed(() => {
  return (
    alreadyFiscalized.value ||
    (props.invoice.fiscal_receipts && props.invoice.fiscal_receipts.length > 0)
  )
})

// Load fiscal devices for the company on mount
onMounted(async () => {
  try {
    await fiscalDeviceStore.fetchFiscalDevices()
    // Auto-select first active device if none provided
    if (!selectedDeviceId.value && fiscalDeviceStore.fiscalDevices.length > 0) {
      const activeDevice = fiscalDeviceStore.fiscalDevices.find(
        (d) => d.is_active
      )
      if (activeDevice) {
        selectedDeviceId.value = activeDevice.id
      }
    }
  } catch (_e) {
    // Fiscal devices may not be enabled
  }
})

async function handleConnect() {
  try {
    await connect()
  } catch (_e) {
    // Error handled in composable
  }
}

async function handleFiscalize() {
  if (!selectedDeviceId.value) return
  try {
    const result = await fiscalizeInvoice(props.invoice, selectedDeviceId.value)
    showSuccess.value = true
    alreadyFiscalized.value = true
    emit('fiscalized', result)

    // Hide success after 5 seconds
    setTimeout(() => {
      showSuccess.value = false
    }, 5000)
  } catch (_e) {
    // Error handled in composable
  }
}

async function handleRefreshStatus() {
  try {
    await getStatus()
  } catch (_e) {
    // Ignore status check errors
  }
}
</script>

<template>
  <div class="inline-flex items-center gap-2">
    <!-- Not supported banner -->
    <span
      v-if="!isSupported"
      class="text-xs text-gray-400 italic"
      :title="t('fiscal.not_supported')"
    >
      {{ t('fiscal.not_supported_short') }}
    </span>

    <!-- Already fiscalized -->
    <span
      v-else-if="hasFiscalReceipt"
      class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-md bg-green-50 text-green-700"
    >
      <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
      </svg>
      {{ lastReceipt ? `#${lastReceipt.receiptNumber}` : t('fiscal.already_fiscalized') }}
    </span>

    <!-- Connect button -->
    <button
      v-else-if="!isConnected"
      class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors"
      @click="handleConnect"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
      </svg>
      {{ t('fiscal.connect_usb') }}
    </button>

    <!-- Connected: Fiscalize button + status -->
    <template v-else>
      <!-- Status indicators -->
      <div class="flex items-center gap-1" :title="deviceInfo?.model || 'Connected'">
        <!-- Connection LED -->
        <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse" />

        <!-- Paper status -->
        <span
          v-if="statusSummary?.paperOut"
          class="text-xs text-red-600 font-medium"
        >
          {{ t('fiscal.paper_out') }}
        </span>
        <span
          v-else-if="statusSummary?.paperLow"
          class="text-xs text-yellow-600"
        >
          {{ t('fiscal.paper_low') }}
        </span>
      </div>

      <!-- Fiscalize button -->
      <button
        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-md bg-primary-500 text-white hover:bg-primary-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        :disabled="!canFiscalize || statusSummary?.paperOut || statusSummary?.fiscalMemoryFull"
        @click="handleFiscalize"
      >
        <LoadingIcon v-if="isProcessing" class="w-4 h-4 animate-spin" />
        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
        </svg>
        {{ isProcessing ? t('fiscal.fiscalizing') : t('fiscal.fiscalize') }}
      </button>

      <!-- Disconnect -->
      <button
        class="text-xs text-gray-400 hover:text-gray-600"
        :title="t('fiscal.disconnect')"
        @click="disconnect"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </template>

    <!-- Error -->
    <span v-if="error" class="text-xs text-red-500 max-w-48 truncate" :title="error">
      {{ error }}
    </span>
  </div>
</template>
