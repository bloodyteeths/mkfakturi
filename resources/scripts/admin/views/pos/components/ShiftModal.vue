<template>
  <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="$emit('close')">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-sm">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          {{ mode === 'open' ? (t('pos.open_shift') || 'Open Shift') : (t('pos.close_shift') || 'Close Shift') }}
        </h3>
      </div>

      <div class="p-6 space-y-4">
        <!-- Opening cash -->
        <div v-if="mode === 'open'">
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 block">
            {{ t('pos.opening_cash') || 'Opening Cash (MKD)' }}
          </label>
          <input
            v-model.number="cashAmount"
            type="number"
            min="0"
            class="w-full px-4 py-2.5 text-lg font-bold text-center bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none"
            placeholder="0"
          />
        </div>

        <!-- Closing cash + notes -->
        <div v-if="mode === 'close'">
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 block">
            {{ t('pos.closing_cash') || 'Closing Cash (MKD)' }}
          </label>
          <input
            v-model.number="cashAmount"
            type="number"
            min="0"
            class="w-full px-4 py-2.5 text-lg font-bold text-center bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none"
            placeholder="0"
          />
          <textarea
            v-model="notes"
            :placeholder="t('pos.shift_notes') || 'Notes (optional)'"
            class="w-full mt-3 px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none resize-none"
            rows="2"
          />
        </div>
      </div>

      <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex gap-3">
        <button
          class="flex-1 py-2.5 text-sm font-medium border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
          @click="$emit('close')"
        >
          Cancel
        </button>
        <button
          class="flex-1 py-2.5 text-sm font-medium bg-primary-600 text-white rounded-lg hover:bg-primary-700"
          @click="handleConfirm"
        >
          {{ mode === 'open' ? 'Open' : 'Close' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  mode: { type: String, default: 'open' },  // 'open' or 'close'
  shift: { type: Object, default: null },
})

const emit = defineEmits(['confirm', 'close'])

const cashAmount = ref(0)
const notes = ref('')

function handleConfirm() {
  const cents = (cashAmount.value || 0) * 100
  if (props.mode === 'open') {
    emit('confirm', cents)
  } else {
    emit('confirm', { closingCash: cents, notes: notes.value })
  }
}
</script>

<!-- CLAUDE-CHECKPOINT -->
