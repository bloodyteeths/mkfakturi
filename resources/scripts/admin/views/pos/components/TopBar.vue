<template>
  <div class="h-14 bg-gradient-to-r from-gray-900 via-gray-900 to-gray-800 text-white flex items-center justify-between px-5 shrink-0 shadow-lg">
    <!-- Left: Company + Fiscal + Shift -->
    <div class="flex items-center gap-3">
      <span class="font-black text-sm tracking-tight bg-gradient-to-r from-white to-gray-300 bg-clip-text text-transparent">
        Facturino POS
      </span>

      <!-- Fiscal status pill -->
      <div
        class="flex items-center gap-1.5 text-[11px] font-medium px-2.5 py-1 rounded-full backdrop-blur-sm transition-colors"
        :class="fiscalConnected
          ? 'bg-emerald-500/20 text-emerald-300 ring-1 ring-emerald-500/30'
          : 'bg-gray-700/50 text-gray-500 ring-1 ring-gray-600/30'"
      >
        <span class="w-1.5 h-1.5 rounded-full" :class="fiscalConnected ? 'bg-emerald-400 animate-pulse' : 'bg-gray-600'"></span>
        {{ fiscalConnected ? 'Fiscal' : 'No Fiscal' }}
      </div>

      <!-- Shift indicator -->
      <div v-if="shift" class="flex items-center gap-1.5 text-[11px] font-medium bg-blue-500/20 text-blue-300 ring-1 ring-blue-500/30 px-2.5 py-1 rounded-full">
        <span class="w-1.5 h-1.5 bg-blue-400 rounded-full animate-pulse"></span>
        {{ formatDuration }}
      </div>

      <!-- Shift buttons -->
      <button
        v-if="!shift"
        class="text-xs font-medium bg-blue-600/80 hover:bg-blue-500 px-4 py-2.5 rounded-lg transition-all hover:shadow-md hover:shadow-blue-500/20"
        @click="$emit('open-shift')"
      >
        {{ t('pos.open_shift') || 'Open Shift' }}
      </button>
      <button
        v-else
        class="text-xs font-medium bg-gray-700/80 hover:bg-gray-600 px-4 py-2.5 rounded-lg transition-colors"
        @click="$emit('close-shift')"
      >
        {{ t('pos.close_shift') || 'Close Shift' }}
      </button>
    </div>

    <!-- Center: Clock -->
    <div class="text-lg font-mono font-light tracking-widest text-gray-300">{{ clock }}</div>

    <!-- Right: Returns + Usage + Exit -->
    <div class="flex items-center gap-3">
      <!-- Returns button -->
      <button
        v-if="returnEnabled"
        class="flex items-center gap-1.5 text-xs font-medium bg-amber-600/80 hover:bg-amber-500 px-4 py-2.5 rounded-lg transition-all"
        @click="$emit('open-return')"
      >
        {{ t('pos.return_sale') || 'Return' }}
      </button>
      <span v-if="usage?.limit" class="text-[11px] font-medium text-gray-500 tabular-nums">
        {{ usage.used }}/{{ usage.limit }}
      </span>
      <button
        class="flex items-center gap-1.5 text-xs font-medium bg-gray-800 hover:bg-gray-700 px-4 py-2.5 rounded-lg ring-1 ring-gray-700 hover:ring-gray-600 transition-all"
        @click="$emit('exit')"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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
  returnEnabled: { type: Boolean, default: false },
})

defineEmits(['open-shift', 'close-shift', 'exit', 'open-return'])

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
