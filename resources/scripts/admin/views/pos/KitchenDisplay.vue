<template>
  <div class="h-screen bg-gray-900 text-white flex flex-col overflow-hidden">
    <!-- Header -->
    <div class="h-14 bg-black flex items-center justify-between px-6 shrink-0">
      <span class="font-black text-lg tracking-tight">Kitchen Display</span>
      <div class="flex items-center gap-4">
        <span class="text-lg font-mono text-gray-400">{{ clock }}</span>
        <button
          class="text-xs font-medium bg-gray-800 hover:bg-gray-700 px-4 py-2.5 rounded-lg ring-1 ring-gray-700 transition-all"
          @click="$router.push('/admin/pos')"
        >
          Back to POS
        </button>
      </div>
    </div>

    <!-- Orders Grid -->
    <div class="flex-1 p-4 overflow-y-auto">
      <div v-if="orders.length === 0" class="flex items-center justify-center h-full">
        <div class="text-center">
          <div class="text-6xl mb-4">&#x1F373;</div>
          <div class="text-xl font-bold text-gray-500">No pending orders</div>
          <div class="text-sm text-gray-600 mt-1">Orders from tables will appear here</div>
        </div>
      </div>

      <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
        <div
          v-for="order in orders"
          :key="order.tableNumber"
          class="rounded-2xl p-4 flex flex-col"
          :class="orderCardClass(order)"
        >
          <!-- Table header -->
          <div class="flex items-center justify-between mb-3">
            <span class="text-2xl font-black">T{{ order.tableNumber }}</span>
            <span class="text-xs font-medium px-2 py-1 rounded-full" :class="timeClass(order)">
              {{ formatElapsed(order.updatedAt) }}
            </span>
          </div>

          <!-- Items -->
          <div class="flex-1 space-y-1.5">
            <div
              v-for="(item, idx) in order.items"
              :key="idx"
              class="flex items-center gap-2 text-sm"
            >
              <span class="font-black text-lg w-6 text-center">{{ item.quantity }}</span>
              <span class="font-medium truncate">{{ item.name }}</span>
            </div>
          </div>

          <!-- Ready button -->
          <button
            class="mt-3 w-full py-3 rounded-xl font-bold text-sm bg-emerald-600 hover:bg-emerald-500 active:scale-95 transition-all"
            @click="markReady(order.tableNumber)"
          >
            READY
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const clock = ref('')
const tableOrders = ref({})
let clockInterval = null
let pollInterval = null

function updateClock() {
  const now = new Date()
  clock.value = now.toLocaleTimeString('mk-MK', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
}

const orders = computed(() => {
  return Object.entries(tableOrders.value)
    .filter(([, order]) => order && order.items && order.items.length > 0)
    .map(([tableNumber, order]) => ({
      tableNumber: parseInt(tableNumber),
      items: order.items,
      total: order.total || 0,
      updatedAt: order.updatedAt,
    }))
    .sort((a, b) => new Date(a.updatedAt) - new Date(b.updatedAt))
})

function loadOrders() {
  try {
    const saved = localStorage.getItem('pos_table_orders')
    if (saved) tableOrders.value = JSON.parse(saved)
  } catch (e) {
    tableOrders.value = {}
  }
}

function markReady(tableNumber) {
  delete tableOrders.value[tableNumber]
  localStorage.setItem('pos_table_orders', JSON.stringify(tableOrders.value))
}

function formatElapsed(iso) {
  if (!iso) return ''
  const diff = Math.floor((Date.now() - new Date(iso).getTime()) / 60000)
  if (diff < 1) return 'now'
  if (diff < 60) return `${diff}m`
  return `${Math.floor(diff / 60)}h ${diff % 60}m`
}

function getElapsedMinutes(iso) {
  if (!iso) return 0
  return Math.floor((Date.now() - new Date(iso).getTime()) / 60000)
}

function orderCardClass(order) {
  const mins = getElapsedMinutes(order.updatedAt)
  if (mins >= 20) return 'bg-red-900/80 ring-2 ring-red-500'
  if (mins >= 10) return 'bg-amber-900/60 ring-1 ring-amber-500'
  return 'bg-gray-800 ring-1 ring-gray-700'
}

function timeClass(order) {
  const mins = getElapsedMinutes(order.updatedAt)
  if (mins >= 20) return 'bg-red-500 text-white'
  if (mins >= 10) return 'bg-amber-500 text-black'
  return 'bg-gray-700 text-gray-300'
}

onMounted(() => {
  updateClock()
  loadOrders()
  clockInterval = setInterval(updateClock, 1000)
  pollInterval = setInterval(loadOrders, 5000) // Poll localStorage every 5s
})

onUnmounted(() => {
  clearInterval(clockInterval)
  clearInterval(pollInterval)
})
</script>

<!-- CLAUDE-CHECKPOINT -->
