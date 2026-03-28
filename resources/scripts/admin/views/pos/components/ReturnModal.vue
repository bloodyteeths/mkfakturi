<template>
  <div class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="$emit('close')">
    <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden ring-1 ring-gray-200 dark:ring-gray-800">
      <!-- Header -->
      <div class="px-6 py-5 bg-gradient-to-b from-gray-50 to-white dark:from-gray-800 dark:to-gray-900 border-b border-gray-100 dark:border-gray-800">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-bold text-gray-900 dark:text-white">
            {{ t('pos.return_sale') || 'Return' }}
          </h3>
          <button class="w-9 h-9 flex items-center justify-center rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-400 transition-colors" @click="$emit('close')">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <div class="p-6 space-y-5 max-h-[70vh] overflow-y-auto">
        <!-- Step 1: Find invoice -->
        <div v-if="!selectedInvoice">
          <label class="text-xs font-bold text-gray-500 uppercase tracking-wider block mb-2">
            {{ t('pos.receipt_number') || 'Receipt' }} #
          </label>
          <div class="flex gap-2">
            <input
              v-model="invoiceSearch"
              type="text"
              class="flex-1 px-4 py-3 text-lg bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none"
              :placeholder="t('pos.search_or_scan') || 'Invoice number...'"
              @keyup.enter="searchInvoice"
            />
            <button
              class="px-5 py-3 bg-primary-600 text-white font-bold rounded-xl hover:bg-primary-700 active:scale-95 transition-all"
              @click="searchInvoice"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </button>
          </div>
          <p v-if="searchError" class="text-sm text-red-500 mt-2">{{ searchError }}</p>
        </div>

        <!-- Step 2: Select items to return -->
        <div v-if="selectedInvoice">
          <div class="flex items-center justify-between mb-4">
            <div>
              <div class="text-sm font-bold text-gray-900 dark:text-white">
                {{ selectedInvoice.invoice_number }}
              </div>
              <div class="text-xs text-gray-500">{{ formatPrice(selectedInvoice.total) }}</div>
            </div>
            <button class="text-xs text-primary-600 font-medium" @click="selectedInvoice = null; returnItems = []">
              {{ t('pos.change_invoice') || 'Change' }}
            </button>
          </div>

          <!-- Items list -->
          <div class="space-y-2">
            <div
              v-for="(item, index) in returnItems"
              :key="index"
              class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-xl"
            >
              <input
                v-model="item.selected"
                type="checkbox"
                class="w-5 h-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
              />
              <div class="flex-1 min-w-0">
                <div class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ item.name }}</div>
                <div class="text-xs text-gray-500">{{ formatPrice(item.price) }} x {{ item.max_qty }}</div>
              </div>
              <div v-if="item.selected" class="flex items-center gap-1 bg-white dark:bg-gray-700 rounded-lg p-0.5">
                <button
                  class="w-9 h-9 flex items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-600 active:scale-90 font-bold"
                  @click="item.return_qty = Math.max(1, item.return_qty - 1)"
                >-</button>
                <span class="w-8 text-center text-sm font-bold">{{ item.return_qty }}</span>
                <button
                  class="w-9 h-9 flex items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-600 active:scale-90 font-bold"
                  @click="item.return_qty = Math.min(item.max_qty, item.return_qty + 1)"
                >+</button>
              </div>
            </div>
          </div>

          <!-- Reason -->
          <div class="mt-4">
            <label class="text-xs font-bold text-gray-500 uppercase tracking-wider block mb-1.5">
              {{ t('pos.return_reason') || 'Reason' }}
            </label>
            <textarea
              v-model="returnReason"
              class="w-full px-3 py-2.5 text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none resize-none"
              rows="2"
              required
            />
          </div>

          <!-- Return total -->
          <div v-if="returnTotal > 0" class="flex items-center justify-between p-4 bg-red-50 dark:bg-red-900/20 rounded-xl ring-1 ring-red-200 dark:ring-red-800">
            <span class="text-sm font-bold text-red-700 dark:text-red-300">{{ t('pos.refund') || 'Refund' }}</span>
            <span class="text-xl font-black text-red-600 dark:text-red-400 tabular-nums">
              {{ formatPrice(returnTotal) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Confirm button -->
      <div v-if="selectedInvoice" class="px-6 pb-6">
        <button
          :disabled="isProcessing || returnTotal === 0 || !returnReason.trim()"
          class="w-full py-4 rounded-2xl font-bold text-lg transition-all duration-200 flex items-center justify-center gap-2"
          :class="!isProcessing && returnTotal > 0 && returnReason.trim()
            ? 'bg-gradient-to-r from-red-500 to-rose-500 hover:from-red-600 hover:to-rose-600 text-white shadow-lg shadow-red-500/25'
            : 'bg-gray-200 dark:bg-gray-700 cursor-not-allowed text-gray-400'"
          @click="processReturn"
        >
          <template v-if="isProcessing">
            <div class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
            {{ t('pos.processing') || 'Processing...' }}
          </template>
          <template v-else>
            {{ t('pos.return_sale') || 'Process Return' }}
          </template>
        </button>
      </div>

      <!-- Success result -->
      <div v-if="returnResult" class="px-6 pb-6">
        <div class="p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl ring-1 ring-emerald-200 dark:ring-emerald-800 text-center">
          <div class="text-emerald-700 dark:text-emerald-300 font-bold">{{ t('pos.credit_note_created') || 'Credit note created' }}</div>
          <div class="text-sm text-emerald-600 dark:text-emerald-400 mt-1">{{ returnResult.credit_note_number }}</div>
          <div class="text-lg font-black text-emerald-600 dark:text-emerald-400 mt-1">
            {{ formatPrice(returnResult.refund_total) }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'

const { t } = useI18n()

defineEmits(['close'])

const invoiceSearch = ref('')
const searchError = ref('')
const selectedInvoice = ref(null)
const returnItems = ref([])
const returnReason = ref('')
const isProcessing = ref(false)
const returnResult = ref(null)

const returnTotal = computed(() => {
  return returnItems.value
    .filter(i => i.selected)
    .reduce((sum, i) => sum + i.price * i.return_qty, 0)
})

async function searchInvoice() {
  if (!invoiceSearch.value.trim()) return
  searchError.value = ''

  try {
    const { data } = await axios.get(`/pos/invoice-lookup`, {
      params: { number: invoiceSearch.value.trim() },
    })

    if (!data.invoice) {
      searchError.value = t('pos.invoice_not_found') || 'Invoice not found'
      return
    }

    selectedInvoice.value = data.invoice
    returnItems.value = (data.invoice.items || []).map(item => ({
      item_id: item.item_id,
      name: item.name,
      price: item.price,
      max_qty: item.quantity,
      return_qty: item.quantity,
      selected: true,
    }))
  } catch (e) {
    searchError.value = e.response?.data?.error || t('pos.invoice_not_found') || 'Invoice not found'
  }
}

async function processReturn() {
  if (isProcessing.value || returnTotal.value === 0) return
  isProcessing.value = true

  try {
    const items = returnItems.value
      .filter(i => i.selected)
      .map(i => ({
        item_id: i.item_id,
        quantity: i.return_qty,
      }))

    const { data } = await axios.post('/pos/return', {
      invoice_id: selectedInvoice.value.id,
      items,
      reason: returnReason.value,
    })

    returnResult.value = data
    selectedInvoice.value = null
  } catch (e) {
    alert(t('pos.sale_failed') + ': ' + (e.response?.data?.error || e.message || 'Return failed'))
  } finally {
    isProcessing.value = false
  }
}

function formatPrice(cents) {
  if (!cents) return '0 MKD'
  return (cents / 100).toLocaleString('mk-MK', { minimumFractionDigits: 0 }) + ' MKD'
}
</script>

<!-- CLAUDE-CHECKPOINT -->
