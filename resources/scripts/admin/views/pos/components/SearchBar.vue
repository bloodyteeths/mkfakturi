<template>
  <div class="px-4 py-3 bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-800 shrink-0">
    <div class="relative group">
      <!-- Search icon -->
      <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 dark:text-gray-600 group-focus-within:text-primary-500 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </div>

      <input
        ref="inputRef"
        :value="modelValue"
        type="text"
        :placeholder="t('pos.search_or_scan') || 'Search or scan barcode...'"
        class="w-full pl-12 pr-12 py-3 bg-gray-50 dark:bg-gray-900 border-2 border-gray-100 dark:border-gray-700 rounded-xl text-sm font-medium text-gray-800 dark:text-gray-200 placeholder-gray-300 dark:placeholder-gray-600 focus:border-primary-400 dark:focus:border-primary-600 focus:ring-2 focus:ring-primary-500/20 focus:bg-white dark:focus:bg-gray-800 outline-none transition-all"
        @input="$emit('update:modelValue', $event.target.value)"
        @keydown.enter.prevent="handleEnter"
      />

      <!-- Keyboard shortcut hint -->
      <div class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-mono font-bold text-gray-300 dark:text-gray-600 bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded">
        F1
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineProps({
  modelValue: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue', 'barcode'])

const inputRef = ref(null)

function handleEnter() {
  const val = inputRef.value?.value?.trim()
  if (val) {
    emit('barcode', val)
    emit('update:modelValue', '')
    if (inputRef.value) inputRef.value.value = ''
  }
}

function focus() {
  inputRef.value?.focus()
}

defineExpose({ focus })
</script>

<!-- CLAUDE-CHECKPOINT -->
