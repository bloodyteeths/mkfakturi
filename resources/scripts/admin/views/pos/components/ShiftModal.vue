<template>
  <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="$emit('close')">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-sm">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          {{ mode === 'open' ? (t('pos.open_shift') || 'Open Shift') : (t('pos.close_shift') || 'Close Shift') }}
        </h3>
      </div>

      <!-- Step 1: Enter cash amount (blind counting for close mode) -->
      <div v-if="!showComparison" class="p-6 space-y-4">
        <!-- Cash label -->
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block">
          {{ mode === 'open' ? (t('pos.opening_cash') || 'Opening Cash (MKD)') : (t('pos.count_cash') || 'Count your cash (MKD)') }}
        </label>

        <p v-if="mode === 'close'" class="text-xs text-gray-400">
          {{ t('pos.blind_count_hint') || 'Enter the amount you counted — system total will be shown after.' }}
        </p>

        <!-- Amount display -->
        <div class="w-full px-4 py-3 text-2xl font-bold text-center bg-gray-50 dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl tabular-nums">
          {{ cashDisplay }} МКД
        </div>

        <!-- Quick preset buttons -->
        <div class="grid grid-cols-4 gap-2">
          <button
            v-for="preset in presets"
            :key="preset"
            class="py-2.5 text-sm font-bold bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl hover:bg-primary-50 hover:border-primary-300 dark:hover:bg-primary-900/20 active:scale-95 transition-all"
            @click="setFromPreset(preset)"
          >
            {{ preset.toLocaleString('mk-MK') }}
          </button>
        </div>

        <!-- Numpad -->
        <NumPad
          @input="onNumpadInput"
          @backspace="onNumpadBackspace"
          @clear="onNumpadClear"
        />

        <!-- Notes (close mode only) -->
        <textarea
          v-if="mode === 'close'"
          v-model="notes"
          :placeholder="t('pos.shift_notes') || 'Notes (optional)'"
          class="w-full px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none resize-none"
          rows="2"
        />
      </div>

      <!-- Step 2: Blind count comparison (close mode only) -->
      <div v-if="showComparison" class="p-6 space-y-4">
        <div class="space-y-3">
          <div class="flex justify-between items-center text-sm">
            <span class="text-gray-500">{{ t('pos.your_count') || 'Your count' }}</span>
            <span class="font-bold text-gray-900 dark:text-white tabular-nums">{{ formatMkd(countedCash) }} МКД</span>
          </div>
          <div class="flex justify-between items-center text-sm">
            <span class="text-gray-500">{{ t('pos.expected_cash') || 'Expected cash' }}</span>
            <span class="font-bold text-gray-900 dark:text-white tabular-nums">{{ formatMkd(expectedCash) }} МКД</span>
          </div>
          <div class="border-t border-gray-200 dark:border-gray-700 pt-3 flex justify-between items-center">
            <span class="text-sm font-bold" :class="cashDifference === 0 ? 'text-emerald-600' : cashDifference > 0 ? 'text-blue-600' : 'text-red-600'">
              {{ t('pos.difference') || 'Difference' }}
            </span>
            <span class="text-lg font-black tabular-nums" :class="cashDifference === 0 ? 'text-emerald-600' : cashDifference > 0 ? 'text-blue-600' : 'text-red-600'">
              {{ cashDifference > 0 ? '+' : '' }}{{ formatMkd(cashDifference) }} МКД
            </span>
          </div>
        </div>

        <div v-if="cashDifference !== 0" class="text-xs text-center px-4 py-2 rounded-lg" :class="cashDifference > 0 ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400' : 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400'">
          {{ cashDifference > 0 ? (t('pos.cash_over') || 'Cash over — more than expected') : (t('pos.cash_short') || 'Cash short — less than expected') }}
        </div>
      </div>

      <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex gap-3">
        <button
          class="flex-1 py-2.5 text-sm font-medium border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
          @click="showComparison ? (showComparison = false) : $emit('close')"
        >
          {{ showComparison ? (t('pos.back') || 'Back') : (t('pos.cancel') || 'Cancel') }}
        </button>
        <button
          class="flex-1 py-2.5 text-sm font-medium bg-primary-600 text-white rounded-lg hover:bg-primary-700"
          @click="handleConfirm"
        >
          {{ mode === 'open' ? (t('pos.open') || 'Open') : showComparison ? (t('pos.confirm_close') || 'Confirm Close') : (t('pos.next') || 'Next') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import NumPad from './NumPad.vue'

const { t } = useI18n()

const props = defineProps({
  mode: { type: String, default: 'open' },
  shift: { type: Object, default: null },
})

const emit = defineEmits(['confirm', 'close'])

const numpadBuffer = ref('0')
const notes = ref('')
const showComparison = ref(false)
const countedCash = ref(0)

const presets = [1000, 2000, 5000, 10000]

const cashDisplay = computed(() => {
  const val = parseInt(numpadBuffer.value) || 0
  return val.toLocaleString('mk-MK')
})

const expectedCash = computed(() => {
  if (!props.shift) return 0
  const openingMkd = (props.shift.opening_cash || 0) / 100
  const cashSalesMkd = (props.shift.cash_sales_total || 0) / 100
  return Math.round(openingMkd + cashSalesMkd)
})

const cashDifference = computed(() => countedCash.value - expectedCash.value)

function formatMkd(val) {
  return val.toLocaleString('mk-MK')
}

function onNumpadInput(digit) {
  if (numpadBuffer.value === '0') {
    numpadBuffer.value = digit
  } else {
    numpadBuffer.value += digit
  }
}

function onNumpadBackspace() {
  if (numpadBuffer.value.length <= 1) {
    numpadBuffer.value = '0'
  } else {
    numpadBuffer.value = numpadBuffer.value.slice(0, -1)
  }
}

function onNumpadClear() {
  numpadBuffer.value = '0'
}

function setFromPreset(mkd) {
  numpadBuffer.value = String(mkd)
}

function handleConfirm() {
  const mkd = parseInt(numpadBuffer.value) || 0
  const cents = mkd * 100
  if (props.mode === 'open') {
    emit('confirm', cents)
  } else if (!showComparison.value) {
    // Step 1 → show comparison
    countedCash.value = mkd
    showComparison.value = true
  } else {
    // Step 2 → confirm close with counted amount
    emit('confirm', { closingCash: cents, notes: notes.value })
  }
}
</script>

<!-- CLAUDE-CHECKPOINT -->
