<template>
  <div class="px-4 py-2 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shrink-0">
    <div class="relative">
      <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
      </svg>
      <input
        ref="inputRef"
        :value="modelValue"
        type="text"
        :placeholder="t('pos.search_or_scan') || 'Search or scan barcode...'"
        class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none"
        autocomplete="off"
        @input="$emit('update:modelValue', $event.target.value)"
        @keydown.enter="handleEnter"
      />
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

// Track rapid input (barcode scanner sends chars very fast)
let lastInputTime = 0
let inputBuffer = ''

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
