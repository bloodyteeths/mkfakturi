<template>
  <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="$emit('close')">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ t('pos.pay') || 'Payment' }}</h3>
        <button class="text-gray-400 hover:text-gray-600" @click="$emit('close')">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Body -->
      <div class="p-6 space-y-5">
        <!-- Total -->
        <div class="text-center">
          <div class="text-sm text-gray-500">{{ t('pos.total') || 'Total' }}</div>
          <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ formatPrice(total) }}</div>
        </div>

        <!-- Payment Method -->
        <div>
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 block">
            {{ t('pos.payment_method') || 'Payment Method' }}
          </label>
          <div class="grid grid-cols-2 gap-3">
            <button
              class="py-3 rounded-lg font-medium text-sm border-2 transition-all flex items-center justify-center gap-2"
              :class="paymentMethod === 'cash'
                ? 'border-green-500 bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:border-gray-300'"
              @click="$emit('update:payment-method', 'cash')"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              {{ t('pos.cash') || 'Cash' }}
            </button>
            <button
              class="py-3 rounded-lg font-medium text-sm border-2 transition-all flex items-center justify-center gap-2"
              :class="paymentMethod === 'card'
                ? 'border-blue-500 bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
                : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:border-gray-300'"
              @click="$emit('update:payment-method', 'card')"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
              </svg>
              {{ t('pos.card') || 'Card' }}
            </button>
          </div>
        </div>

        <!-- Cash Received (only for cash) -->
        <div v-if="paymentMethod === 'cash'">
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 block">
            {{ t('pos.cash_received') || 'Cash Received' }}
          </label>
          <input
            ref="cashInput"
            :value="cashReceivedDisplay"
            type="number"
            step="1"
            min="0"
            class="w-full px-4 py-3 text-xl font-bold text-center bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none"
            @input="handleCashInput"
          />

          <!-- Quick amounts -->
          <div class="grid grid-cols-4 gap-2 mt-3">
            <button
              v-for="amount in quickAmounts"
              :key="amount"
              class="py-2 text-sm font-medium bg-gray-100 dark:bg-gray-700 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
              @click="$emit('update:cash-received', amount)"
            >
              {{ formatPrice(amount) }}
            </button>
          </div>

          <!-- Change -->
          <div v-if="change > 0" class="mt-3 p-3 bg-green-50 dark:bg-green-900/30 rounded-lg text-center">
            <div class="text-sm text-green-600 dark:text-green-400">{{ t('pos.change') || 'Change' }}</div>
            <div class="text-2xl font-bold text-green-700 dark:text-green-300">{{ formatPrice(change) }}</div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
        <button
          :disabled="isProcessing || (paymentMethod === 'cash' && cashReceived < total)"
          class="w-full py-3 rounded-lg text-white font-bold text-base transition-all"
          :class="!isProcessing && (paymentMethod !== 'cash' || cashReceived >= total)
            ? 'bg-green-600 hover:bg-green-700 active:bg-green-800'
            : 'bg-gray-300 cursor-not-allowed'"
          @click="$emit('confirm')"
        >
          <span v-if="isProcessing">{{ t('pos.processing') || 'Processing...' }}</span>
          <span v-else>{{ t('pos.pay') || 'Confirm Payment' }} — {{ formatPrice(total) }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  total: { type: Number, default: 0 },
  paymentMethod: { type: String, default: 'cash' },
  cashReceived: { type: Number, default: 0 },
  change: { type: Number, default: 0 },
  isProcessing: { type: Boolean, default: false },
})

defineEmits(['update:payment-method', 'update:cash-received', 'confirm', 'close'])

const cashInput = ref(null)

const cashReceivedDisplay = computed(() =>
  props.cashReceived > 0 ? Math.round(props.cashReceived / 100) : ''
)

function handleCashInput(e) {
  const mkd = parseInt(e.target.value) || 0
  // Convert MKD to cents
  const cents = mkd * 100
  // Use a proper event instead of directly emitting
  const emit = defineEmits ? null : null
}

// Quick amount buttons — round up to nearest common denomination
const quickAmounts = computed(() => {
  const totalMkd = Math.ceil(props.total / 100)
  const amounts = []
  // Exact amount
  amounts.push(props.total)
  // Round to 50
  const r50 = Math.ceil(totalMkd / 50) * 50 * 100
  if (r50 > props.total) amounts.push(r50)
  // Round to 100
  const r100 = Math.ceil(totalMkd / 100) * 100 * 100
  if (r100 > props.total && r100 !== r50) amounts.push(r100)
  // Round to 500
  const r500 = Math.ceil(totalMkd / 500) * 500 * 100
  if (r500 > props.total && r500 !== r100) amounts.push(r500)
  return amounts.slice(0, 4)
})

function formatPrice(cents) {
  if (!cents) return '0 МКД'
  return (cents / 100).toLocaleString('mk-MK', { minimumFractionDigits: 0 }) + ' МКД'
}

onMounted(async () => {
  await nextTick()
  cashInput.value?.focus()
})
</script>

<!-- CLAUDE-CHECKPOINT -->
