<template>
  <div class="numpad-grid">
    <button
      v-for="key in keys"
      :key="key.value"
      class="numpad-btn"
      :class="key.class || ''"
      @click="handleKey(key.value)"
    >
      <component :is="key.icon ? 'span' : 'span'">
        <svg v-if="key.value === 'backspace'" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l6.414-6.414A2 2 0 0110.828 5H19a2 2 0 012 2v10a2 2 0 01-2 2h-8.172a2 2 0 01-1.414-.586L3 12z" />
        </svg>
        <template v-else>{{ key.label }}</template>
      </component>
    </button>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const emit = defineEmits(['input', 'clear', 'backspace', 'confirm'])

const keys = [
  { value: '7', label: '7' },
  { value: '8', label: '8' },
  { value: '9', label: '9' },
  { value: '4', label: '4' },
  { value: '5', label: '5' },
  { value: '6', label: '6' },
  { value: '1', label: '1' },
  { value: '2', label: '2' },
  { value: '3', label: '3' },
  { value: 'clear', label: 'C', class: 'numpad-clear' },
  { value: '0', label: '0' },
  { value: 'backspace', label: '', class: 'numpad-backspace' },
]

function handleKey(value) {
  if (value === 'clear') {
    emit('clear')
  } else if (value === 'backspace') {
    emit('backspace')
  } else {
    emit('input', value)
  }
}
</script>

<style scoped>
.numpad-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
}

.numpad-btn {
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  font-weight: 700;
  border-radius: 12px;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  color: #111827;
  transition: all 0.1s;
  user-select: none;
  -webkit-tap-highlight-color: transparent;
}

.numpad-btn:active {
  transform: scale(0.95);
  background: #e5e7eb;
}

.dark .numpad-btn {
  background: #1f2937;
  border-color: #374151;
  color: #f9fafb;
}

.dark .numpad-btn:active {
  background: #374151;
}

.numpad-clear {
  color: #ef4444;
  font-size: 1.25rem;
}

.numpad-backspace {
  color: #6b7280;
}
</style>

<!-- CLAUDE-CHECKPOINT -->
