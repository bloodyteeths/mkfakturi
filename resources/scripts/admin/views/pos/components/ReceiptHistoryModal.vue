<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40 backdrop-blur-sm"
      @click.self="$emit('close')"
    >
      <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-lg mx-4 max-h-[80vh] flex flex-col" @click.stop>
        <!-- Header -->
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between shrink-0">
          <h2 class="font-bold text-gray-900 dark:text-white">{{ t('pos.receipt_history') || 'Receipt History' }}</h2>
          <button
            class="w-9 h-9 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
            @click="$emit('close')"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Search -->
        <div class="px-5 py-3 border-b border-gray-50 dark:border-gray-800 shrink-0">
          <input
            ref="searchInput"
            v-model="searchQuery"
            type="text"
            :placeholder="t('pos.search_receipt') || 'Search by receipt or invoice number...'"
            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            @input="debouncedSearch"
          />
        </div>

        <!-- Receipt List -->
        <div class="flex-1 overflow-y-auto">
          <div v-if="loading" class="flex items-center justify-center py-12">
            <div class="w-8 h-8 rounded-full border-2 border-primary-500 border-t-transparent animate-spin"></div>
          </div>

          <div v-else-if="receipts.length === 0" class="flex flex-col items-center justify-center py-12 text-gray-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span class="text-sm font-medium">{{ t('pos.no_receipts') || 'No receipts found' }}</span>
          </div>

          <div v-else>
            <button
              v-for="receipt in receipts"
              :key="receipt.id"
              class="w-full px-5 py-3 flex items-center gap-3 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors border-b border-gray-50 dark:border-gray-800/50 text-left"
              @click="selectReceipt(receipt)"
            >
              <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <div class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                  #{{ receipt.receipt_number }}
                  <span v-if="receipt.invoice" class="text-gray-400 font-normal ml-1">
                    ({{ receipt.invoice.invoice_number }})
                  </span>
                </div>
                <div class="text-xs text-gray-400 mt-0.5">
                  {{ formatDate(receipt.created_at) }}
                  <span v-if="receipt.fiscal_device" class="ml-2">{{ receipt.fiscal_device.name }}</span>
                </div>
              </div>
              <div class="text-sm font-bold text-gray-900 dark:text-white tabular-nums shrink-0">
                {{ formatPrice(receipt.amount) }}
              </div>
            </button>
          </div>
        </div>

        <!-- Reprint action for selected receipt -->
        <div v-if="selectedReceipt" class="px-5 py-4 border-t border-gray-100 dark:border-gray-800 shrink-0 bg-gray-50 dark:bg-gray-800/50">
          <div class="flex items-center justify-between mb-3">
            <div>
              <div class="text-sm font-bold text-gray-900 dark:text-white">#{{ selectedReceipt.receipt_number }}</div>
              <div class="text-xs text-gray-400">{{ formatPrice(selectedReceipt.amount) }}</div>
            </div>
            <button
              class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
              @click="selectedReceipt = null"
            >
              {{ t('general.cancel') || 'Cancel' }}
            </button>
          </div>
          <button
            :disabled="reprinting"
            class="w-full py-3 rounded-xl font-bold text-sm text-white bg-primary-500 hover:bg-primary-600 active:bg-primary-700 disabled:bg-gray-300 dark:disabled:bg-gray-700 transition-colors flex items-center justify-center gap-2"
            @click="reprintReceipt"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            {{ reprinting ? (t('pos.reprinting') || 'Reprinting...') : (t('pos.reprint_receipt') || 'Reprint Receipt') }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'

const { t } = useI18n()

const props = defineProps({
  show: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'reprint'])

const searchQuery = ref('')
const receipts = ref([])
const loading = ref(false)
const selectedReceipt = ref(null)
const reprinting = ref(false)
const searchInput = ref(null)
let debounceTimer = null

watch(() => props.show, async (val) => {
  if (val) {
    searchQuery.value = ''
    selectedReceipt.value = null
    await fetchReceipts()
    nextTick(() => searchInput.value?.focus())
  }
})

async function fetchReceipts(query = '') {
  loading.value = true
  try {
    const params = { limit: 20, orderByField: 'created_at', orderBy: 'desc' }
    if (query) params.search = query
    const { data } = await axios.get('/api/v1/admin/fiscal-receipts', { params })
    receipts.value = data.data || []
  } catch (e) {
    receipts.value = []
  } finally {
    loading.value = false
  }
}

function debouncedSearch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => fetchReceipts(searchQuery.value), 300)
}

function selectReceipt(receipt) {
  selectedReceipt.value = receipt
}

function reprintReceipt() {
  if (!selectedReceipt.value) return
  emit('reprint', selectedReceipt.value)
}

function formatPrice(cents) {
  if (!cents) return '0 МКД'
  return (cents / 100).toLocaleString('mk-MK', { minimumFractionDigits: 0 }) + ' МКД'
}

function formatDate(iso) {
  if (!iso) return ''
  const d = new Date(iso)
  return d.toLocaleDateString('mk-MK', { day: '2-digit', month: '2-digit', year: 'numeric' }) +
    ' ' + d.toLocaleTimeString('mk-MK', { hour: '2-digit', minute: '2-digit' })
}
</script>

<!-- CLAUDE-CHECKPOINT -->
