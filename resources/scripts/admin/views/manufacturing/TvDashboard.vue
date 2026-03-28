<template>
  <div class="min-h-screen bg-gray-900 text-white p-6" @click="toggleFullscreen">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-4">
        <img src="/images/facturino-logo-white.svg" alt="Facturino" class="h-8" onerror="this.style.display='none'" />
        <h1 class="text-2xl font-bold">{{ t('manufacturing.tv_title') }}</h1>
      </div>
      <div class="flex items-center gap-4">
        <span class="text-lg text-gray-400">{{ currentTime }}</span>
        <span v-if="fetchErrorCount >= 3" class="text-sm text-red-400 font-medium">{{ t('manufacturing.connection_lost') }}</span>
        <span class="inline-flex h-3 w-3 rounded-full animate-pulse" :class="isLive ? 'bg-green-500' : 'bg-red-500'"></span>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-32">
      <div class="h-16 w-16 animate-spin rounded-full border-4 border-blue-400 border-t-transparent"></div>
    </div>

    <template v-else>
      <!-- KPI Cards Row -->
      <div class="grid grid-cols-5 gap-4 mb-6">
        <div class="rounded-xl bg-gray-800 p-5 text-center">
          <p class="text-sm text-gray-400 uppercase tracking-wider">{{ t('manufacturing.tv_active') }}</p>
          <p class="mt-2 text-4xl font-bold text-blue-400">{{ kpi.active }}</p>
        </div>
        <div class="rounded-xl bg-gray-800 p-5 text-center">
          <p class="text-sm text-gray-400 uppercase tracking-wider">{{ t('manufacturing.tv_completed_today') }}</p>
          <p class="mt-2 text-4xl font-bold text-green-400">{{ kpi.completedToday }}</p>
        </div>
        <div class="rounded-xl bg-gray-800 p-5 text-center">
          <p class="text-sm text-gray-400 uppercase tracking-wider">{{ t('manufacturing.tv_overdue') }}</p>
          <p class="mt-2 text-4xl font-bold" :class="kpi.overdue > 0 ? 'text-red-400' : 'text-gray-500'">{{ kpi.overdue }}</p>
        </div>
        <div class="rounded-xl bg-gray-800 p-5 text-center">
          <p class="text-sm text-gray-400 uppercase tracking-wider">{{ t('manufacturing.tv_output_today') }}</p>
          <p class="mt-2 text-4xl font-bold text-purple-400">{{ kpi.outputToday }}</p>
        </div>
        <div class="rounded-xl bg-gray-800 p-5 text-center">
          <p class="text-sm text-gray-400 uppercase tracking-wider">{{ t('manufacturing.tv_qc_rate') }}</p>
          <p class="mt-2 text-4xl font-bold" :class="kpi.qcRate >= 95 ? 'text-green-400' : kpi.qcRate >= 80 ? 'text-yellow-400' : 'text-red-400'">{{ kpi.qcRate }}%</p>
        </div>
      </div>

      <!-- Active Orders Table -->
      <div class="rounded-xl bg-gray-800 overflow-hidden">
        <div class="grid grid-cols-6 gap-2 bg-gray-700 px-6 py-3 text-xs font-bold uppercase tracking-wider text-gray-300">
          <span>{{ t('manufacturing.order_number') }}</span>
          <span>{{ t('manufacturing.output_item') }}</span>
          <span>{{ t('manufacturing.work_centers') }}</span>
          <span class="text-center">{{ t('manufacturing.status') }}</span>
          <span class="text-center">{{ t('manufacturing.shop_floor_progress') }}</span>
          <span class="text-right">{{ t('manufacturing.expected_completion') }}</span>
        </div>
        <div
          v-for="order in activeOrders"
          :key="order.id"
          class="grid grid-cols-6 gap-2 border-t border-gray-700 px-6 py-4 items-center transition"
          :class="order.is_overdue ? 'bg-red-900/20' : ''"
        >
          <span class="text-sm font-mono text-gray-200">{{ order.order_number }}</span>
          <span class="text-sm font-semibold text-white truncate">{{ order.item_name }}</span>
          <span class="text-sm text-gray-400">{{ order.work_center || '-' }}</span>
          <div class="text-center">
            <span
              class="inline-flex rounded-full px-3 py-1 text-xs font-bold uppercase"
              :class="{
                'bg-blue-500/20 text-blue-300': order.status === 'in_progress',
                'bg-gray-600 text-gray-300': order.status === 'draft',
                'bg-green-500/20 text-green-300': order.status === 'completed',
              }"
            >
              {{ t('manufacturing.status_' + order.status) }}
            </span>
          </div>
          <div class="px-2">
            <div class="flex items-center gap-2">
              <div class="flex-1 h-4 rounded-full bg-gray-700 overflow-hidden">
                <div
                  class="h-full rounded-full transition-all duration-1000"
                  :class="order.is_overdue ? 'bg-red-500' : 'bg-blue-500'"
                  :style="{ width: progressPercent(order) + '%' }"
                ></div>
              </div>
              <span class="text-sm font-bold text-gray-300 w-12 text-right">{{ progressPercent(order) }}%</span>
            </div>
          </div>
          <span class="text-sm text-right" :class="order.is_overdue ? 'text-red-400 font-bold' : 'text-gray-400'">
            {{ fmtDate(order.expected_completion) }}
          </span>
        </div>

        <div v-if="activeOrders.length === 0" class="px-6 py-12 text-center text-gray-500 text-lg">
          {{ t('manufacturing.tv_no_active') }}
        </div>
      </div>
    </template>

    <!-- Fullscreen hint -->
    <p class="mt-4 text-center text-xs text-gray-600">{{ t('manufacturing.tv_fullscreen_hint') }}</p>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const loading = ref(true)
const orders = ref([])
const qcData = ref(null)
const isLive = ref(true)
const currentTime = ref('')
const fetchErrorCount = ref(0)

const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }

function fmtDate(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString(localeMap[locale.value] || 'mk-MK', { day: '2-digit', month: '2-digit' })
}

function progressPercent(order) {
  if (!order.planned_quantity || order.planned_quantity <= 0) return 0
  return Math.min(100, Math.round(((order.actual_quantity || 0) / order.planned_quantity) * 100))
}

const activeOrders = computed(() => {
  return orders.value
    .filter(o => o.status === 'in_progress' || o.status === 'draft')
    .sort((a, b) => {
      // Overdue first, then by expected completion
      if (a.is_overdue && !b.is_overdue) return -1
      if (!a.is_overdue && b.is_overdue) return 1
      return 0
    })
})

const kpi = computed(() => {
  const active = orders.value.filter(o => o.status === 'in_progress').length
  const overdue = orders.value.filter(o => o.is_overdue).length
  const completedToday = orders.value.filter(o => {
    if (o.status !== 'completed') return false
    const completed = new Date(o.completed_at)
    const today = new Date()
    return completed.toDateString() === today.toDateString()
  }).length
  const outputToday = orders.value
    .filter(o => o.status === 'completed' && o.completed_at)
    .reduce((sum, o) => {
      const completed = new Date(o.completed_at)
      const today = new Date()
      if (completed.toDateString() === today.toDateString()) {
        return sum + (parseFloat(o.actual_quantity) || 0)
      }
      return sum
    }, 0)
  const qcRate = qcData.value?.summary?.pass_rate ?? 100

  return { active, completedToday, overdue, outputToday: Math.round(outputToday), qcRate: Math.round(qcRate) }
})

function updateClock() {
  const now = new Date()
  currentTime.value = now.toLocaleTimeString(localeMap[locale.value] || 'mk-MK', { hour: '2-digit', minute: '2-digit' })
}

async function fetchData() {
  try {
    const [ordersRes, qcRes] = await Promise.all([
      window.axios.get('/manufacturing/orders', { params: { limit: 100 } }),
      window.axios.get('/manufacturing/reports/qc-metrics').catch(() => ({ data: { data: null } })),
    ])

    orders.value = (ordersRes.data?.data || []).map(o => ({
      id: o.id,
      order_number: o.order_number,
      item_name: o.output_item?.name || '-',
      work_center: o.work_center?.name,
      status: o.status,
      planned_quantity: parseFloat(o.planned_quantity) || 0,
      actual_quantity: parseFloat(o.actual_quantity) || 0,
      expected_completion: o.expected_completion_date,
      completed_at: o.completed_at,
      is_overdue: o.expected_completion_date && new Date(o.expected_completion_date) < new Date() && o.status !== 'completed',
    }))
    qcData.value = qcRes.data?.data || null
    isLive.value = true
    fetchErrorCount.value = 0
  } catch (error) {
    console.error('TV fetch failed:', error)
    fetchErrorCount.value++
    isLive.value = false
    // Stop retrying after 3 consecutive failures
    if (fetchErrorCount.value >= 3 && refreshInterval) {
      clearInterval(refreshInterval)
      refreshInterval = null
    }
  } finally {
    loading.value = false
  }
}

function toggleFullscreen() {
  if (document.fullscreenElement) {
    document.exitFullscreen()
  } else {
    document.documentElement.requestFullscreen().catch(() => {})
  }
}

let refreshInterval = null
let clockInterval = null

onMounted(async () => {
  updateClock()
  clockInterval = setInterval(updateClock, 1000)
  await fetchData()
  // Auto-refresh every 30 seconds
  refreshInterval = setInterval(fetchData, 30000)
})

onBeforeUnmount(() => {
  if (refreshInterval) clearInterval(refreshInterval)
  if (clockInterval) clearInterval(clockInterval)
})
// CLAUDE-CHECKPOINT
</script>
