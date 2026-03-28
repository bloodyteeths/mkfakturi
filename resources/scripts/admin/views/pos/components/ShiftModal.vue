<template>
  <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="$emit('close')">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-sm">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          {{ mode === 'open' ? (t('pos.open_shift') || 'Open Shift') : (t('pos.close_shift') || 'Close Shift') }}
        </h3>
      </div>

      <div class="p-6 space-y-4">
        <!-- Cash label -->
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block">
          {{ mode === 'open' ? (t('pos.opening_cash') || 'Opening Cash (MKD)') : (t('pos.closing_cash') || 'Closing Cash (MKD)') }}
        </label>

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

      <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex gap-3">
        <button
          class="flex-1 py-2.5 text-sm font-medium border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
          @click="$emit('close')"
        >
          {{ t('pos.cancel') || 'Cancel' }}
        </button>
        <button
          class="flex-1 py-2.5 text-sm font-medium bg-primary-600 text-white rounded-lg hover:bg-primary-700"
          @click="handleConfirm"
        >
          {{ mode === 'open' ? t('pos.open') || 'Open' : t('pos.close') || 'Close' }}
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

const presets = [1000, 2000, 5000, 10000]

const cashDisplay = computed(() => {
  const val = parseInt(numpadBuffer.value) || 0
  return val.toLocaleString('mk-MK')
})

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
  } else {
    emit('confirm', { closingCash: cents, notes: notes.value })
  }
}
</script>

<!-- CLAUDE-CHECKPOINT -->
