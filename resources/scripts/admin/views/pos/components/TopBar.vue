<template>
  <div class="h-14 bg-gradient-to-r from-gray-900 via-gray-900 to-gray-800 text-white flex items-center justify-between px-5 shrink-0 shadow-lg">
    <!-- Left: Company + Fiscal + Shift -->
    <div class="flex items-center gap-3">
      <!-- POS Menu Dropdown -->
      <div class="relative">
        <button
          class="font-black text-sm tracking-tight bg-gradient-to-r from-white to-gray-300 bg-clip-text text-transparent flex items-center gap-1"
          @click="showMenu = !showMenu"
        >
          Facturino POS
          <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <!-- Dropdown Menu -->
        <div
          v-if="showMenu"
          class="absolute top-full left-0 mt-2 w-52 bg-gray-800 rounded-xl shadow-2xl ring-1 ring-gray-700 py-1 z-50"
          @click="showMenu = false"
        >
          <button
            class="w-full px-4 py-2.5 text-left text-sm font-medium text-gray-200 hover:bg-gray-700 flex items-center gap-3 transition-colors"
            @click="$router.push('/admin/pos')"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17" />
            </svg>
            {{ t('pos.title') || 'Point of Sale' }}
          </button>
          <button
            v-if="restaurantMode"
            class="w-full px-4 py-2.5 text-left text-sm font-medium text-gray-200 hover:bg-gray-700 flex items-center gap-3 transition-colors"
            @click="$router.push('/admin/pos/kitchen')"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            Kitchen Display
          </button>
          <button
            v-if="returnEnabled"
            class="w-full px-4 py-2.5 text-left text-sm font-medium text-gray-200 hover:bg-gray-700 flex items-center gap-3 transition-colors"
            @click="$emit('open-return')"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
            </svg>
            {{ t('pos.return_sale') || 'Returns' }}
          </button>
          <div class="border-t border-gray-700 my-1"></div>
          <button
            class="w-full px-4 py-2.5 text-left text-sm font-medium text-gray-200 hover:bg-gray-700 flex items-center gap-3 transition-colors"
            @click="$router.push('/admin/settings/pos')"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            {{ t('pos_settings.title') || 'POS Settings' }}
          </button>
        </div>
      </div>

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

      <!-- Warehouse selector (only if > 1 warehouse) -->
      <div v-if="warehouses.length > 1" class="flex items-center gap-1.5">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
        </svg>
        <select
          :value="selectedWarehouse"
          class="bg-gray-800 text-gray-300 text-[11px] font-medium border-0 ring-1 ring-gray-700 rounded-lg pl-2 pr-6 py-1 focus:ring-blue-500 focus:outline-none cursor-pointer appearance-none"
          style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2210%22 height=%2210%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%239ca3af%22 stroke-width=%222%22><path d=%22M6 9l6 6 6-6%22/></svg>'); background-repeat: no-repeat; background-position: right 6px center;"
          @change="$emit('warehouse-change', Number($event.target.value))"
        >
          <option
            v-for="wh in warehouses"
            :key="wh.id"
            :value="wh.id"
          >
            {{ wh.name }}
          </option>
        </select>
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

    <!-- Right: Usage + Exit -->
    <div class="flex items-center gap-3">
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

  <!-- Click-away overlay for dropdown -->
  <div v-if="showMenu" class="fixed inset-0 z-40" @click="showMenu = false"></div>
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
  restaurantMode: { type: Boolean, default: false },
  warehouses: { type: Array, default: () => [] },
  selectedWarehouse: { type: Number, default: null },
})

defineEmits(['open-shift', 'close-shift', 'exit', 'open-return', 'warehouse-change'])

const showMenu = ref(false)
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
