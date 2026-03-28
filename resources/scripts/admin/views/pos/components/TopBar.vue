<template>
  <div class="h-12 bg-gray-900 text-white flex items-center justify-between px-4 shrink-0">
    <!-- Left: Company + Shift -->
    <div class="flex items-center gap-3">
      <span class="font-bold text-sm">Facturino POS</span>
      <!-- Fiscal status indicator -->
      <div
        class="flex items-center gap-1 text-xs px-2 py-0.5 rounded-full"
        :class="fiscalConnected ? 'bg-green-700 text-green-200' : 'bg-gray-700 text-gray-400'"
        :title="fiscalConnected ? 'Fiscal printer connected' : 'No fiscal printer'"
      >
        <span class="w-1.5 h-1.5 rounded-full" :class="fiscalConnected ? 'bg-green-300' : 'bg-gray-500'"></span>
        {{ fiscalConnected ? '🖨️' : '🖨️' }}
      </div>
      <div v-if="shift" class="flex items-center gap-1.5 text-xs bg-green-600 px-2 py-0.5 rounded-full">
        <span class="w-1.5 h-1.5 bg-green-300 rounded-full animate-pulse"></span>
        {{ formatDuration }}
      </div>
      <button
        v-if="!shift"
        class="text-xs bg-blue-600 hover:bg-blue-700 px-2.5 py-1 rounded transition-colors"
        @click="$emit('open-shift')"
      >
        {{ t('pos.open_shift') || 'Open Shift' }}
      </button>
      <button
        v-else
        class="text-xs bg-gray-700 hover:bg-gray-600 px-2.5 py-1 rounded transition-colors"
        @click="$emit('close-shift')"
      >
        {{ t('pos.close_shift') || 'Close Shift' }}
      </button>
    </div>

    <!-- Center: Clock -->
    <div class="text-sm font-mono">{{ clock }}</div>

    <!-- Right: Usage + Exit -->
    <div class="flex items-center gap-3">
      <span v-if="usage?.limit" class="text-xs text-gray-400">
        {{ usage.used }}/{{ usage.limit }}
      </span>
      <button
        class="text-xs bg-gray-700 hover:bg-gray-600 px-3 py-1 rounded flex items-center gap-1.5 transition-colors"
        @click="$emit('exit')"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
        </svg>
        {{ t('pos.exit_pos') || 'Exit' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  shift: { type: Object, default: null },
  usage: { type: Object, default: () => ({}) },
  fiscalConnected: { type: Boolean, default: false },
})

defineEmits(['open-shift', 'close-shift', 'exit'])

const clock = ref('')
let clockInterval = null

function updateClock() {
  const now = new Date()
  clock.value = now.toLocaleTimeString('mk-MK', { hour: '2-digit', minute: '2-digit' })
}

const formatDuration = computed(() => {
  if (!props.shift?.opened_at) return ''
  const opened = new Date(props.shift.opened_at)
  const diff = Math.floor((Date.now() - opened.getTime()) / 60000)
  const h = Math.floor(diff / 60)
  const m = diff % 60
  return h > 0 ? `${h}h ${m}m` : `${m}m`
})

onMounted(() => {
  updateClock()
  clockInterval = setInterval(updateClock, 10000)
})

onUnmounted(() => {
  clearInterval(clockInterval)
})
</script>

<!-- CLAUDE-CHECKPOINT -->
