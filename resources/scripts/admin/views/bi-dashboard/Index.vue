<template>
  <BasePage>
    <BasePageHeader :title="t('title')">
      <template #actions>
        <BaseButton variant="primary-outline" size="sm" @click="refreshData" :disabled="isLoading">
          {{ t('refresh') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Subtitle -->
    <p class="text-sm text-gray-500 -mt-4 mb-6">{{ t('subtitle') }}</p>

    <!-- Period Selector Pills -->
    <div class="flex flex-wrap gap-2 mb-6">
      <button
        v-for="opt in periodOptions"
        :key="opt.value"
        class="px-4 py-2 text-sm font-medium rounded-full border transition-colors"
        :class="selectedPeriod === opt.value
          ? 'bg-primary-500 text-white border-primary-500'
          : 'bg-white text-gray-700 border-gray-300 hover:border-primary-300 hover:text-primary-600'"
        @click="selectPeriod(opt.value)"
      >
        {{ opt.label }}
      </button>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="text-center py-12">
      <div class="inline-block w-8 h-8 border-4 border-primary-200 border-t-primary-500 rounded-full animate-spin"></div>
      <p class="text-sm text-gray-500 mt-3">{{ $t('general.loading') }}...</p>
    </div>

    <!-- Not Initialized -->
    <div v-else-if="notInitialized" class="text-center py-16 bg-white rounded-lg shadow">
      <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
      </div>
      <p class="text-sm text-gray-500 max-w-md mx-auto px-4">{{ t('not_initialized') }}</p>
    </div>

    <!-- No Data -->
    <div v-else-if="!summaryData" class="text-center py-12 bg-white rounded-lg shadow">
      <p class="text-sm text-gray-500">{{ t('no_data') }}</p>
    </div>

    <template v-else>
      <!-- Health Indicator -->
      <div class="mb-6 bg-white rounded-lg shadow p-5">
        <div class="flex items-center gap-4">
          <div
            class="w-14 h-14 rounded-full flex items-center justify-center flex-shrink-0"
            :class="zoneColor(altmanZone)"
          >
            <svg v-if="altmanZone === 'safe'" class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <svg v-else-if="altmanZone === 'caution'" class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
            <svg v-else class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div class="flex-1">
            <p class="text-sm text-gray-500">{{ t('overall_health') }}</p>
            <p class="text-lg font-semibold" :class="zoneTextColor(altmanZone)">
              {{ t(altmanZone) }}
            </p>
            <p class="text-xs text-gray-400 mt-0.5">{{ t('health_desc') }}</p>
          </div>
          <!-- Health dots -->
          <div class="flex gap-5">
            <div v-for="(status, key) in healthIndicators" :key="key" class="text-center">
              <div class="w-3 h-3 rounded-full mx-auto mb-1" :class="zoneDot(status)"></div>
              <p class="text-[11px] text-gray-500">{{ t(key) }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- 4 Key Metric Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Revenue -->
        <div class="bg-white rounded-lg shadow p-5">
          <p class="text-xs font-medium text-gray-500 uppercase mb-1">{{ t('revenue') }}</p>
          <p class="text-xl font-bold text-gray-900">{{ formatCurrency(revenueValue) }}</p>
          <p class="text-[11px] text-gray-400 mt-1.5 leading-snug">{{ t('revenue_desc') }}</p>
        </div>

        <!-- Net Margin -->
        <div class="bg-white rounded-lg shadow p-5">
          <p class="text-xs font-medium text-gray-500 uppercase mb-1">{{ t('net_margin') }}</p>
          <p class="text-xl font-bold" :class="netMarginValue >= 0 ? 'text-green-600' : 'text-red-600'">
            {{ formatPercent(netMarginValue) }}
          </p>
          <p class="text-[11px] text-gray-400 mt-1.5 leading-snug">{{ t('net_margin_desc') }}</p>
        </div>

        <!-- Cash Position -->
        <div class="bg-white rounded-lg shadow p-5">
          <p class="text-xs font-medium text-gray-500 uppercase mb-1">{{ t('cash_position') }}</p>
          <p class="text-xl font-bold text-gray-900">{{ formatCurrency(cashValue) }}</p>
          <p class="text-[11px] text-gray-400 mt-1.5 leading-snug">{{ t('cash_position_desc') }}</p>
        </div>

        <!-- Receivable Days -->
        <div class="bg-white rounded-lg shadow p-5">
          <p class="text-xs font-medium text-gray-500 uppercase mb-1">{{ t('receivable_days') }}</p>
          <p class="text-xl font-bold" :class="receivableDaysValue <= 45 ? 'text-green-600' : receivableDaysValue <= 90 ? 'text-yellow-600' : 'text-red-600'">
            {{ Math.round(receivableDaysValue) }} <span class="text-sm font-normal text-gray-500">{{ t('days_label') }}</span>
          </p>
          <p class="text-[11px] text-gray-400 mt-1.5 leading-snug">{{ t('receivable_days_desc') }}</p>
        </div>
      </div>

      <!-- Revenue Trend Chart -->
      <div class="bg-white rounded-lg shadow p-5 mb-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-gray-700">{{ t('revenue_trend') }} — {{ t('last_12_months') }}</h3>
          <BaseMultiselect
            v-model="selectedTrendRatio"
            :options="trendRatioOptions"
            :searchable="false"
            label="label"
            value-prop="value"
            class="w-52"
            @update:model-value="loadTrend"
          />
        </div>
        <div v-if="trendLoading" class="text-center py-8">
          <p class="text-xs text-gray-400">{{ $t('general.loading') }}...</p>
        </div>
        <div v-else-if="paddedTrendData.length" class="h-40">
          <div class="flex items-end gap-1 h-full">
            <div
              v-for="(point, idx) in paddedTrendData"
              :key="idx"
              class="flex flex-col items-center justify-end"
              :style="{ width: (100 / 12) + '%' }"
            >
              <template v-if="point.hasData">
                <span class="text-[9px] text-gray-500 mb-1">{{ formatTrendValue(point.value) }}</span>
                <div
                  class="w-full max-w-[2rem] mx-auto rounded-t transition-all"
                  :class="point.value < 0 ? 'bg-red-400' : 'bg-primary-500'"
                  :style="{ height: barHeight(point.value) + '%' }"
                ></div>
              </template>
              <template v-else>
                <span class="text-[9px] text-gray-300 mb-1">—</span>
                <div class="w-full max-w-[2rem] mx-auto rounded-t bg-gray-100" style="height: 3%"></div>
              </template>
              <span class="text-[9px] mt-1" :class="point.hasData ? 'text-gray-400' : 'text-gray-300'">{{ formatMonth(point.date) }}</span>
            </div>
          </div>
        </div>
        <div v-else class="text-center py-8">
          <p class="text-xs text-gray-400">{{ t('no_data') }}</p>
        </div>
      </div>

      <!-- Collapsible Detailed Ratios -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <button
          class="w-full px-5 py-3 flex items-center justify-between text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
          @click="showDetails = !showDetails"
        >
          <span>{{ showDetails ? t('hide_details') : t('show_details') }}</span>
          <svg
            class="w-5 h-5 text-gray-400 transition-transform"
            :class="{ 'rotate-180': showDetails }"
            fill="none" stroke="currentColor" viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <div v-if="showDetails" class="border-t border-gray-200">
          <!-- Liquidity -->
          <div class="px-5 py-3 border-b border-gray-100">
            <h4 class="text-xs font-bold text-gray-500 uppercase mb-1">{{ t('liquidity') }}</h4>
            <p class="text-[11px] text-gray-400 mb-3 leading-snug">{{ t('liquidity_desc') }}</p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div v-for="(val, key) in liquidityRatios" :key="key" class="flex items-start gap-2">
                <span class="inline-block w-2 h-2 rounded-full mt-1.5 flex-shrink-0" :class="ratioStatusDot(key, val)"></span>
                <div class="min-w-0">
                  <p class="text-xs text-gray-700 font-medium">{{ t(key) }}: {{ Number(val).toFixed(2) }}</p>
                  <p class="text-[10px] text-gray-400 leading-snug">{{ t(key + '_tip') }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Profitability -->
          <div class="px-5 py-3 border-b border-gray-100">
            <h4 class="text-xs font-bold text-gray-500 uppercase mb-1">{{ t('profitability') }}</h4>
            <p class="text-[11px] text-gray-400 mb-3 leading-snug">{{ t('profitability_desc') }}</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
              <div v-for="(val, key) in profitabilityRatios" :key="key" class="flex items-start gap-2">
                <span class="inline-block w-2 h-2 rounded-full mt-1.5 flex-shrink-0" :class="ratioStatusDot(key, val)"></span>
                <div class="min-w-0">
                  <p class="text-xs text-gray-700 font-medium">{{ t(key) }}: {{ (Number(val) * 100).toFixed(1) }}%</p>
                  <p class="text-[10px] text-gray-400 leading-snug">{{ t(key + '_tip') }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Solvency -->
          <div class="px-5 py-3 border-b border-gray-100">
            <h4 class="text-xs font-bold text-gray-500 uppercase mb-1">{{ t('solvency') }}</h4>
            <p class="text-[11px] text-gray-400 mb-3 leading-snug">{{ t('solvency_desc') }}</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div v-for="(val, key) in solvencyRatios" :key="key" class="flex items-start gap-2">
                <span class="inline-block w-2 h-2 rounded-full mt-1.5 flex-shrink-0" :class="ratioStatusDot(key, val)"></span>
                <div class="min-w-0">
                  <p class="text-xs text-gray-700 font-medium">{{ t(key) }}: {{ Number(val).toFixed(2) }}</p>
                  <p class="text-[10px] text-gray-400 leading-snug">{{ t(key + '_tip') }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Activity -->
          <div class="px-5 py-3">
            <h4 class="text-xs font-bold text-gray-500 uppercase mb-1">{{ t('activity') }}</h4>
            <p class="text-[11px] text-gray-400 mb-3 leading-snug">{{ t('activity_desc') }}</p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div v-for="(val, key) in activityRatios" :key="key" class="flex items-start gap-2">
                <span class="inline-block w-2 h-2 rounded-full mt-1.5 flex-shrink-0" :class="ratioStatusDot(key, val)"></span>
                <div class="min-w-0">
                  <p class="text-xs text-gray-700 font-medium">
                    {{ t(key) }}: {{ key === 'inventory_turnover' ? Number(val).toFixed(2) : Math.round(Number(val)) + ' ' + t('days_label') }}
                  </p>
                  <p class="text-[10px] text-gray-400 leading-snug">{{ t(key + '_tip') }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { useNotificationStore } from '@/scripts/stores/notification'
import biMessages from '@/scripts/admin/i18n/bi-dashboard.js'

const notificationStore = useNotificationStore()

const locale = document.documentElement.lang || 'mk'
function t(key) {
  return biMessages[locale]?.bi_dashboard?.[key]
    || biMessages['en']?.bi_dashboard?.[key]
    || key
}

const isLoading = ref(false)
const summaryData = ref(null)
const notInitialized = ref(false)
const selectedPeriod = ref('this_month')
const showDetails = ref(false)

// Trend
const trendLoading = ref(false)
const trendData = ref([])
const selectedTrendRatio = ref('gross_margin')

const periodOptions = [
  { value: 'this_month', label: t('this_month') },
  { value: 'last_month', label: t('last_month') },
  { value: 'last_quarter', label: t('last_quarter') },
  { value: 'this_year', label: t('this_year') },
]

const trendRatioOptions = [
  { value: 'gross_margin', label: t('gross_margin') },
  { value: 'net_margin', label: t('net_margin') },
  { value: 'current_ratio', label: t('current_ratio') },
  { value: 'debt_to_equity', label: t('debt_to_equity') },
  { value: 'receivable_days', label: t('receivable_days') },
  { value: 'inventory_turnover', label: t('inventory_turnover') },
]

const percentRatios = ['gross_margin', 'net_margin', 'roe', 'roa']

function getPeriodDate() {
  const now = new Date()
  switch (selectedPeriod.value) {
    case 'last_month': {
      const d = new Date(now.getFullYear(), now.getMonth(), 0)
      return d.toISOString().split('T')[0]
    }
    case 'last_quarter': {
      const currentQ = Math.floor(now.getMonth() / 3)
      const prevQEnd = currentQ * 3
      const d = new Date(now.getFullYear(), prevQEnd, 0)
      return d.toISOString().split('T')[0]
    }
    case 'this_year':
      return `${now.getFullYear()}-12-31`
    default: {
      const d = new Date(now.getFullYear(), now.getMonth() + 1, 0)
      return d.toISOString().split('T')[0]
    }
  }
}

// Computed data
const liquidityRatios = computed(() => summaryData.value?.ratios?.liquidity || {})
const profitabilityRatios = computed(() => summaryData.value?.ratios?.profitability || {})
const solvencyRatios = computed(() => summaryData.value?.ratios?.solvency || {})
const activityRatios = computed(() => summaryData.value?.ratios?.activity || {})
const healthIndicators = computed(() => summaryData.value?.health || {})
const altmanZone = computed(() => summaryData.value?.ratios?.altman_z?.zone || 'danger')

const revenueValue = computed(() => {
  return summaryData.value?.raw?.revenue || 0
})

const netMarginValue = computed(() => summaryData.value?.ratios?.profitability?.net_margin || 0)
const cashValue = computed(() => summaryData.value?.raw?.cash || 0)
const receivableDaysValue = computed(() => summaryData.value?.ratios?.activity?.receivable_days || 0)

// Pad trend data to always show 12 months
const paddedTrendData = computed(() => {
  const now = new Date()
  const months = []

  // Generate last 12 months
  for (let i = 11; i >= 0; i--) {
    const d = new Date(now.getFullYear(), now.getMonth() - i, 1)
    const dateStr = d.toISOString().split('T')[0].substring(0, 7) // YYYY-MM
    months.push({ date: dateStr + '-01', month: dateStr, hasData: false, value: 0 })
  }

  // Map actual trend data onto the 12-month grid
  if (trendData.value.length) {
    for (const point of trendData.value) {
      const pointMonth = point.date?.substring(0, 7)
      const match = months.find(m => m.month === pointMonth)
      if (match) {
        match.value = point.value || 0
        match.hasData = true
        match.date = point.date
      }
    }
  }

  return months
})

// Formatting
function formatCurrency(val) {
  if (val === 0 || val === null || val === undefined) return '0'
  return Number(val).toLocaleString(locale === 'mk' ? 'mk-MK' : locale === 'sq' ? 'sq-AL' : locale === 'tr' ? 'tr-TR' : 'en-US', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  })
}

function formatPercent(val) {
  if (val === null || val === undefined) return '0.0%'
  return (Number(val) * 100).toFixed(1) + '%'
}

function formatTrendValue(val) {
  if (percentRatios.includes(selectedTrendRatio.value)) {
    return (val * 100).toFixed(1) + '%'
  }
  return Number(val).toFixed(1)
}

function formatMonth(dateStr) {
  if (!dateStr) return ''
  const parts = dateStr.split('-')
  const monthNames = {
    mk: ['Јан', 'Фев', 'Мар', 'Апр', 'Мај', 'Јун', 'Јул', 'Авг', 'Сеп', 'Окт', 'Ное', 'Дек'],
    en: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    tr: ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'],
    sq: ['Jan', 'Shk', 'Mar', 'Pri', 'Maj', 'Qer', 'Kor', 'Gus', 'Sht', 'Tet', 'Nën', 'Dhj'],
  }
  const names = monthNames[locale] || monthNames['en']
  const monthIdx = parseInt(parts[1], 10) - 1
  return names[monthIdx] || parts[1]
}

// Status colors
function zoneColor(zone) {
  return { safe: 'bg-green-500', caution: 'bg-yellow-500', danger: 'bg-red-500' }[zone] || 'bg-gray-400'
}

function zoneTextColor(zone) {
  return { safe: 'text-green-700', caution: 'text-yellow-700', danger: 'text-red-700' }[zone] || 'text-gray-700'
}

function zoneDot(status) {
  return { safe: 'bg-green-500', caution: 'bg-yellow-500', danger: 'bg-red-500' }[status] || 'bg-gray-400'
}

function ratioStatusDot(key, value) {
  if (key === 'current_ratio') return value >= 1.5 ? 'bg-green-500' : value >= 1 ? 'bg-yellow-500' : 'bg-red-500'
  if (key === 'quick_ratio') return value >= 1 ? 'bg-green-500' : value >= 0.5 ? 'bg-yellow-500' : 'bg-red-500'
  if (key === 'cash_ratio') return value >= 0.2 ? 'bg-green-500' : value >= 0.1 ? 'bg-yellow-500' : 'bg-red-500'
  if (key === 'gross_margin') return value >= 0.3 ? 'bg-green-500' : value >= 0.1 ? 'bg-yellow-500' : 'bg-red-500'
  if (key === 'net_margin') return value >= 0.1 ? 'bg-green-500' : value >= 0 ? 'bg-yellow-500' : 'bg-red-500'
  if (key === 'roe') return value >= 0.15 ? 'bg-green-500' : value >= 0.05 ? 'bg-yellow-500' : 'bg-red-500'
  if (key === 'roa') return value >= 0.05 ? 'bg-green-500' : value >= 0.02 ? 'bg-yellow-500' : 'bg-red-500'
  if (key === 'debt_to_equity') return value <= 1 ? 'bg-green-500' : value <= 2 ? 'bg-yellow-500' : 'bg-red-500'
  if (key === 'interest_coverage') return value >= 3 ? 'bg-green-500' : value >= 1.5 ? 'bg-yellow-500' : 'bg-red-500'
  if (key === 'receivable_days') return value <= 45 ? 'bg-green-500' : value <= 90 ? 'bg-yellow-500' : 'bg-red-500'
  if (key === 'payable_days') return value <= 60 ? 'bg-green-500' : value <= 90 ? 'bg-yellow-500' : 'bg-red-500'
  if (key === 'inventory_turnover') return value >= 5 ? 'bg-green-500' : value >= 2 ? 'bg-yellow-500' : 'bg-red-500'
  return 'bg-gray-400'
}

function barHeight(value) {
  // Use paddedTrendData for max calculation, only consider data points
  const dataPoints = paddedTrendData.value.filter(p => p.hasData)
  if (!dataPoints.length) return 0
  const maxVal = Math.max(...dataPoints.map(p => Math.abs(p.value)), 0.001)
  return Math.max((Math.abs(value) / maxVal) * 100, 5)
}

// API calls
function selectPeriod(val) {
  selectedPeriod.value = val
  loadSummary()
}

async function loadSummary() {
  isLoading.value = true
  notInitialized.value = false
  try {
    const { data } = await axios.get('/bi-dashboard/summary', {
      params: { date: getPeriodDate() },
    })
    if (data.message === 'accounting_not_initialized') {
      notInitialized.value = true
      summaryData.value = null
    } else {
      summaryData.value = data.data || null
    }
  } catch (e) {
    notificationStore.showNotification({ type: 'error', message: t('error_loading_summary') })
    summaryData.value = null
  } finally {
    isLoading.value = false
  }
}

async function loadTrend() {
  trendLoading.value = true
  try {
    const { data } = await axios.get('/bi-dashboard/trends', {
      params: { ratio_type: selectedTrendRatio.value, months: 12 },
    })
    trendData.value = data.data?.trends || []
  } catch (e) {
    notificationStore.showNotification({ type: 'error', message: t('error_loading_trends') })
    trendData.value = []
  } finally {
    trendLoading.value = false
  }
}

async function refreshData() {
  isLoading.value = true
  notInitialized.value = false
  try {
    const { data } = await axios.post('/bi-dashboard/refresh', null, {
      params: { date: getPeriodDate() },
    })
    if (data.message === 'accounting_not_initialized') {
      notInitialized.value = true
      summaryData.value = null
    } else {
      summaryData.value = data.data || null
    }
  } catch (e) {
    notificationStore.showNotification({ type: 'error', message: t('error_refreshing') })
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadSummary()
  loadTrend()
})
</script>

// CLAUDE-CHECKPOINT
