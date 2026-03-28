<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40 backdrop-blur-sm"
      @click.self="$emit('close')"
    >
      <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-sm mx-4" @click.stop>
        <!-- Header -->
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800">
          <h2 class="font-bold text-gray-900 dark:text-white">{{ t('pos.cash_in_out') || 'Cash In/Out' }}</h2>
        </div>

        <div class="p-5 space-y-4">
          <!-- Type toggle -->
          <div class="flex rounded-xl bg-gray-100 dark:bg-gray-800 p-1">
            <button
              class="flex-1 py-2.5 text-sm font-bold rounded-lg transition-all"
              :class="type === 'in' ? 'bg-emerald-500 text-white shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
              @click="type = 'in'"
            >
              {{ t('pos.cash_in') || 'Cash In' }}
            </button>
            <button
              class="flex-1 py-2.5 text-sm font-bold rounded-lg transition-all"
              :class="type === 'out' ? 'bg-red-500 text-white shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
              @click="type = 'out'"
            >
              {{ t('pos.cash_out') || 'Cash Out' }}
            </button>
          </div>

          <!-- Amount display -->
          <div class="w-full px-4 py-3 text-2xl font-bold text-center bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl tabular-nums">
            {{ amountDisplay }} МКД
          </div>

          <!-- Numpad -->
          <NumPad
            @input="onInput"
            @backspace="onBackspace"
            @clear="onClear"
          />

          <!-- Reason field -->
          <input
            v-model="reason"
            type="text"
            :placeholder="t('pos.cash_reason') || 'Reason (required)'"
            class="w-full px-4 py-2.5 text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none"
          />
        </div>

        <!-- Actions -->
        <div class="px-5 pb-5 flex gap-3">
          <button
            class="flex-1 py-3 text-sm font-medium border border-gray-200 dark:border-gray-700 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
            @click="$emit('close')"
          >
            {{ t('pos.cancel') || 'Cancel' }}
          </button>
          <button
            :disabled="!canConfirm"
            class="flex-1 py-3 text-sm font-bold text-white rounded-xl transition-colors"
            :class="canConfirm
              ? (type === 'in' ? 'bg-emerald-500 hover:bg-emerald-600' : 'bg-red-500 hover:bg-red-600')
              : 'bg-gray-300 dark:bg-gray-700 cursor-not-allowed'"
            @click="handleConfirm"
          >
            {{ t('pos.confirm') || 'Confirm' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import NumPad from './NumPad.vue'

const { t } = useI18n()

defineProps({
  show: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'confirm'])

const type = ref('in')
const buffer = ref('0')
const reason = ref('')

const amountDisplay = computed(() => {
  const val = parseInt(buffer.value) || 0
  return val.toLocaleString('mk-MK')
})

const canConfirm = computed(() => {
  const val = parseInt(buffer.value) || 0
  return val > 0 && reason.value.trim().length > 0
})

function onInput(digit) {
  buffer.value = buffer.value === '0' ? digit : buffer.value + digit
}

function onBackspace() {
  buffer.value = buffer.value.length <= 1 ? '0' : buffer.value.slice(0, -1)
}

function onClear() {
  buffer.value = '0'
}

function handleConfirm() {
  const mkd = parseInt(buffer.value) || 0
  if (mkd <= 0 || !reason.value.trim()) return
  emit('confirm', {
    type: type.value,
    amount: mkd * 100, // cents
    reason: reason.value.trim(),
    timestamp: new Date().toISOString(),
  })
  // Reset
  buffer.value = '0'
  reason.value = ''
  type.value = 'in'
}
</script>

<!-- CLAUDE-CHECKPOINT -->
