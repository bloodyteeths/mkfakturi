<template>
  <div class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="$emit('close')">
    <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-2xl w-full max-w-md overflow-hidden ring-1 ring-gray-200 dark:ring-gray-800">
      <!-- Header -->
      <div class="px-6 py-5 bg-gradient-to-b from-gray-50 to-white dark:from-gray-800 dark:to-gray-900 border-b border-gray-100 dark:border-gray-800">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">
            {{ t('pos.payment_method') || 'Payment' }}
          </h3>
          <button class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-400 transition-colors" @click="$emit('close')">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <!-- Total display -->
        <div class="mt-3 text-center">
          <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">{{ t('pos.total') || 'Total' }}</span>
          <div class="text-4xl font-black text-gray-900 dark:text-white tabular-nums mt-1">
            {{ formatPrice(total) }}
          </div>
        </div>
      </div>

      <div class="p-6 space-y-5">
        <!-- Payment type toggle -->
        <div class="flex gap-2 p-1 bg-gray-100 dark:bg-gray-800 rounded-xl">
          <button
            class="flex-1 py-2.5 rounded-lg text-sm font-bold transition-all duration-200"
            :class="localMethod === 'cash'
              ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
              : 'text-gray-500 hover:text-gray-700'"
            @click="setMethod('cash')"
          >
            {{ t('pos.cash') || 'Cash' }}
          </button>
          <button
            class="flex-1 py-2.5 rounded-lg text-sm font-bold transition-all duration-200"
            :class="localMethod === 'card'
              ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
              : 'text-gray-500 hover:text-gray-700'"
            @click="setMethod('card')"
          >
            {{ t('pos.card') || 'Card' }}
          </button>
          <button
            v-if="splitEnabled"
            class="flex-1 py-2.5 rounded-lg text-sm font-bold transition-all duration-200"
            :class="localMethod === 'mixed'
              ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
              : 'text-gray-500 hover:text-gray-700'"
            @click="setMethod('mixed')"
          >
            {{ t('pos_settings.split_payment') || 'Split' }}
          </button>
        </div>

        <!-- Cash received input (only for cash) -->
        <div v-if="localMethod === 'cash'" class="space-y-3">
          <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">
            {{ t('pos.cash_received') || 'Cash Received' }}
          </label>

          <!-- Amount display -->
          <div
            class="w-full px-4 py-3.5 text-2xl font-bold text-center bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl tabular-nums cursor-text"
            @click="numpadActive = true"
          >
            {{ cashDisplay }} МКД
          </div>

          <!-- Quick amount buttons -->
          <div class="grid grid-cols-4 gap-2">
            <button
              v-for="amount in quickAmounts"
              :key="amount"
              class="py-2.5 text-sm font-bold bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl hover:bg-primary-50 hover:border-primary-300 dark:hover:bg-primary-900/20 dark:hover:border-primary-700 active:scale-95 transition-all"
              @click="setCashFromAmount(amount)"
            >
              {{ (amount / 100).toLocaleString('mk-MK') }}
            </button>
          </div>

          <!-- Numpad -->
          <NumPad
            @input="onNumpadInput"
            @backspace="onNumpadBackspace"
            @clear="onNumpadClear"
          />

          <!-- Change display -->
          <div
            v-if="localChange > 0"
            class="flex items-center justify-between p-4 bg-gradient-to-r from-emerald-50 to-green-50 dark:from-emerald-900/20 dark:to-green-900/20 rounded-xl ring-1 ring-emerald-200 dark:ring-emerald-800"
          >
            <span class="text-sm font-bold text-emerald-700 dark:text-emerald-300">{{ t('pos.change') || 'Change' }}</span>
            <span class="text-2xl font-black text-emerald-600 dark:text-emerald-400 tabular-nums">
              {{ formatPrice(localChange) }}
            </span>
          </div>
        </div>

        <!-- Split payment inputs (only for mixed) -->
        <div v-if="localMethod === 'mixed'" class="space-y-3">
          <div>
            <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">
              {{ t('pos.cash') || 'Cash' }}
            </label>
            <div
              class="w-full px-4 py-3 text-xl font-bold text-center bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl tabular-nums cursor-text mt-1"
              :class="splitFocus === 'cash' ? 'ring-2 ring-primary-500 border-primary-500' : ''"
              @click="splitFocus = 'cash'"
            >
              {{ splitCashDisplay }} МКД
            </div>
          </div>
          <div>
            <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">
              {{ t('pos.card') || 'Card' }}
            </label>
            <div
              class="w-full px-4 py-3 text-xl font-bold text-center bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl tabular-nums cursor-text mt-1"
              :class="splitFocus === 'card' ? 'ring-2 ring-primary-500 border-primary-500' : ''"
              @click="splitFocus = 'card'"
            >
              {{ splitCardDisplay }} МКД
            </div>
          </div>

          <NumPad
            @input="onSplitNumpadInput"
            @backspace="onSplitNumpadBackspace"
            @clear="onSplitNumpadClear"
          />

          <!-- Remaining display -->
          <div
            v-if="splitRemaining !== 0"
            class="flex items-center justify-between p-3 rounded-xl ring-1"
            :class="splitRemaining > 0
              ? 'bg-amber-50 dark:bg-amber-900/20 ring-amber-200 dark:ring-amber-800'
              : 'bg-emerald-50 dark:bg-emerald-900/20 ring-emerald-200 dark:ring-emerald-800'"
          >
            <span class="text-sm font-bold" :class="splitRemaining > 0 ? 'text-amber-700 dark:text-amber-300' : 'text-emerald-700 dark:text-emerald-300'">
              {{ splitRemaining > 0 ? 'Remaining' : 'Overpaid' }}
            </span>
            <span class="text-lg font-black tabular-nums" :class="splitRemaining > 0 ? 'text-amber-600' : 'text-emerald-600'">
              {{ formatPrice(Math.abs(splitRemaining)) }}
            </span>
          </div>
        </div>
        <!-- Card with CASYS QR option -->
        <div v-if="localMethod === 'card' && casysEnabled" class="space-y-3">
          <div v-if="!casysQr" class="text-center">
            <button
              class="w-full py-3 rounded-xl text-sm font-bold bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 ring-1 ring-blue-200 dark:ring-blue-700 hover:bg-blue-100 dark:hover:bg-blue-900/30 active:scale-[0.98] transition-all"
              :disabled="casysLoading"
              @click="generateCasysQr"
            >
              <template v-if="casysLoading">
                <span class="inline-block w-4 h-4 border-2 border-blue-300 border-t-blue-600 rounded-full animate-spin mr-2 align-middle"></span>
                {{ t('pos.generating_qr') || 'Generating QR...' }}
              </template>
              <template v-else>
                {{ t('pos.generate_casys_qr') || 'Generate QR for Card Payment (CASYS)' }}
              </template>
            </button>
            <p class="text-xs text-gray-400 mt-2">{{ t('pos.casys_qr_hint') || 'Customer scans QR to pay with their bank card' }}</p>
          </div>

          <!-- QR code display -->
          <div v-if="casysQr" class="text-center space-y-3">
            <div class="bg-white p-4 rounded-2xl inline-block ring-1 ring-gray-200">
              <img :src="casysQr.qr_data_uri" alt="Payment QR" class="w-64 h-64 mx-auto" />
            </div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-300">
              {{ t('pos.scan_to_pay') || 'Customer: scan this QR to pay' }}
            </p>
            <div class="flex items-center justify-center gap-2 text-xs">
              <span class="inline-block w-2 h-2 rounded-full animate-pulse" :class="casysStatus === 'completed' ? 'bg-emerald-500' : casysStatus === 'failed' ? 'bg-red-500' : 'bg-amber-500'"></span>
              <span :class="casysStatus === 'completed' ? 'text-emerald-600 font-bold' : casysStatus === 'failed' ? 'text-red-600' : 'text-amber-600'">
                {{ casysStatus === 'completed' ? (t('pos.payment_received') || 'Payment received!') : casysStatus === 'failed' ? (t('pos.payment_failed') || 'Payment failed') : (t('pos.waiting_for_payment') || 'Waiting for payment...') }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Confirm button -->
      <div class="px-6 pb-6">
        <button
          :disabled="isProcessing || !canConfirm"
          class="w-full py-4 rounded-2xl text-white font-bold text-lg transition-all duration-200 flex items-center justify-center gap-2"
          :class="!isProcessing && canConfirm
            ? 'bg-gradient-to-r from-emerald-500 to-green-500 hover:from-emerald-600 hover:to-green-600 shadow-lg shadow-green-500/25 hover:shadow-xl hover:-translate-y-0.5'
            : 'bg-gray-200 dark:bg-gray-700 cursor-not-allowed text-gray-400'"
          @click="$emit('confirm')"
        >
          <template v-if="isProcessing">
            <div class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
            {{ t('pos.processing') || 'Processing...' }}
          </template>
          <template v-else>
            {{ t('pos.confirm_payment') || 'Confirm Payment' }}
          </template>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import NumPad from './NumPad.vue'

const { t } = useI18n()

const props = defineProps({
  total: { type: Number, default: 0 },
  paymentMethod: { type: String, default: 'cash' },
  cashReceived: { type: Number, default: 0 },
  change: { type: Number, default: 0 },
  isProcessing: { type: Boolean, default: false },
  splitEnabled: { type: Boolean, default: false },
  casysEnabled: { type: Boolean, default: false },
})

const emit = defineEmits(['update:payment-method', 'update:cash-received', 'update:split-amounts', 'confirm', 'close'])

const localMethod = ref(props.paymentMethod)
const localCash = ref(props.cashReceived || props.total)
const numpadActive = ref(true)

// String buffer for numpad entry (in MKD, not cents)
const numpadBuffer = ref(String(Math.round(localCash.value / 100)))

// Display value from numpad buffer
const cashDisplay = computed(() => {
  const val = parseInt(numpadBuffer.value) || 0
  return val.toLocaleString('mk-MK')
})

function syncCashFromBuffer() {
  const mkd = parseInt(numpadBuffer.value) || 0
  localCash.value = mkd * 100
  emit('update:cash-received', localCash.value)
}

function onNumpadInput(digit) {
  if (numpadBuffer.value === '0') {
    numpadBuffer.value = digit
  } else {
    numpadBuffer.value += digit
  }
  syncCashFromBuffer()
}

function onNumpadBackspace() {
  if (numpadBuffer.value.length <= 1) {
    numpadBuffer.value = '0'
  } else {
    numpadBuffer.value = numpadBuffer.value.slice(0, -1)
  }
  syncCashFromBuffer()
}

function onNumpadClear() {
  numpadBuffer.value = '0'
  syncCashFromBuffer()
}

function setCashFromAmount(amount) {
  localCash.value = amount
  numpadBuffer.value = String(Math.round(amount / 100))
  emit('update:cash-received', amount)
}

function setMethod(method) {
  localMethod.value = method
  emit('update:payment-method', method)
  if (method === 'mixed') {
    // Default: half cash, half card
    const halfMkd = Math.round(props.total / 200) // half in MKD
    splitCashBuffer.value = String(halfMkd)
    splitCardBuffer.value = String(Math.round(props.total / 100) - halfMkd)
    splitFocus.value = 'cash'
    emitSplitAmounts()
  }
}

const localChange = computed(() => Math.max(0, localCash.value - props.total))

// --- Split payment ---
const splitFocus = ref('cash')
const splitCashBuffer = ref('0')
const splitCardBuffer = ref('0')

const splitCashDisplay = computed(() => (parseInt(splitCashBuffer.value) || 0).toLocaleString('mk-MK'))
const splitCardDisplay = computed(() => (parseInt(splitCardBuffer.value) || 0).toLocaleString('mk-MK'))

const splitRemaining = computed(() => {
  const cashCents = (parseInt(splitCashBuffer.value) || 0) * 100
  const cardCents = (parseInt(splitCardBuffer.value) || 0) * 100
  return props.total - cashCents - cardCents
})

function emitSplitAmounts() {
  emit('update:split-amounts', {
    cash_amount: (parseInt(splitCashBuffer.value) || 0) * 100,
    card_amount: (parseInt(splitCardBuffer.value) || 0) * 100,
  })
}

function onSplitNumpadInput(digit) {
  const buf = splitFocus.value === 'cash' ? 'splitCashBuffer' : 'splitCardBuffer'
  if (splitFocus.value === 'cash') {
    splitCashBuffer.value = splitCashBuffer.value === '0' ? digit : splitCashBuffer.value + digit
  } else {
    splitCardBuffer.value = splitCardBuffer.value === '0' ? digit : splitCardBuffer.value + digit
  }
  emitSplitAmounts()
}

function onSplitNumpadBackspace() {
  if (splitFocus.value === 'cash') {
    splitCashBuffer.value = splitCashBuffer.value.length <= 1 ? '0' : splitCashBuffer.value.slice(0, -1)
  } else {
    splitCardBuffer.value = splitCardBuffer.value.length <= 1 ? '0' : splitCardBuffer.value.slice(0, -1)
  }
  emitSplitAmounts()
}

function onSplitNumpadClear() {
  if (splitFocus.value === 'cash') {
    splitCashBuffer.value = '0'
  } else {
    splitCardBuffer.value = '0'
  }
  emitSplitAmounts()
}

// --- CASYS QR payment ---
const casysQr = ref(null)
const casysLoading = ref(false)
const casysStatus = ref('pending')
let casysPollingInterval = null

async function generateCasysQr() {
  casysLoading.value = true
  try {
    const { data } = await axios.post('pos/casys-checkout', {
      amount: props.total,
      description: 'POS Sale',
    })
    casysQr.value = data
    casysStatus.value = 'pending'
    // Start polling for payment status
    startCasysPolling(data.order_id)
  } catch (e) {
    alert(e.response?.data?.error || 'Failed to generate CASYS QR')
  } finally {
    casysLoading.value = false
  }
}

function startCasysPolling(orderId) {
  stopCasysPolling()
  casysPollingInterval = setInterval(async () => {
    try {
      const { data } = await axios.get(`pos/casys-status/${orderId}`)
      casysStatus.value = data.status
      if (data.status === 'completed') {
        stopCasysPolling()
        // Auto-confirm payment
        emit('confirm')
      } else if (data.status === 'failed') {
        stopCasysPolling()
      }
    } catch {
      // Ignore polling errors
    }
  }, 3000)
}

function stopCasysPolling() {
  if (casysPollingInterval) {
    clearInterval(casysPollingInterval)
    casysPollingInterval = null
  }
}

// --- Confirm validation ---
const canConfirm = computed(() => {
  if (localMethod.value === 'cash') return localCash.value >= props.total
  if (localMethod.value === 'card') return true
  if (localMethod.value === 'mixed') return splitRemaining.value <= 0
  return false
})

// Quick amount buttons — round up to nearest common denominations
const quickAmounts = computed(() => {
  const total = props.total
  const amounts = new Set()
  amounts.add(total)
  for (const round of [5000, 10000, 50000, 100000]) {
    const rounded = Math.ceil(total / round) * round
    if (rounded >= total && rounded <= total * 3) amounts.add(rounded)
  }
  return [...amounts].sort((a, b) => a - b).slice(0, 4)
})

onUnmounted(() => {
  stopCasysPolling()
})

function formatPrice(cents) {
  if (!cents) return '0 МКД'
  return (cents / 100).toLocaleString('mk-MK', { minimumFractionDigits: 0 }) + ' МКД'
}
</script>

<!-- CLAUDE-CHECKPOINT -->
