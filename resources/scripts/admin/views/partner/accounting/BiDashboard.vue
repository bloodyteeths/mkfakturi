<template>
  <BasePage>
    <BasePageHeader :title="t('title')" />

    <!-- Company Selector -->
    <div class="mb-6">
      <BaseInputGroup :label="$t('partner.select_company')">
        <BaseMultiselect
          v-model="selectedCompanyId"
          :options="companies"
          :searchable="true"
          track-by="name"
          label="name"
          value-prop="id"
          :placeholder="$t('partner.select_company_placeholder')"
          @update:model-value="onCompanyChange"
        />
      </BaseInputGroup>
    </div>

    <div v-if="!selectedCompanyId" class="text-center py-12 bg-white rounded-lg shadow">
      <p class="text-sm text-gray-500">{{ $t('partner.select_company_placeholder') }}</p>
    </div>

    <template v-if="selectedCompanyId">
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
            <div>
              <p class="text-sm text-gray-500">{{ t('overall_health') }}</p>
              <p class="text-lg font-semibold" :class="zoneTextColor(altmanZone)">
                {{ t(altmanZone) }}
              </p>
            </div>
            <div class="ml-auto flex gap-5">
              <div v-for="(status, key) in healthIndicators" :key="key" class="text-center">
                <div class="w-3 h-3 rounded-full mx-auto mb-1" :class="zoneDot(status)"></div>
                <p class="text-[11px] text-gray-500">{{ t(key) }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- 4 Key Metric Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
          <div class="bg-white rounded-lg shadow p-5">
            <p class="text-xs font-medium text-gray-500 uppercase mb-2">{{ t('revenue') }}</p>
            <p class="text-xl font-bold text-gray-900">{{ formatCurrency(revenueValue) }}</p>
          </div>
          <div class="bg-white rounded-lg shadow p-5">
            <p class="text-xs font-medium text-gray-500 uppercase mb-2">{{ t('net_margin') }}</p>
            <p class="text-xl font-bold" :class="netMarginValue >= 0 ? 'text-green-600' : 'text-red-600'">
              {{ formatPercent(netMarginValue) }}
            </p>
          </div>
          <div class="bg-white rounded-lg shadow p-5">
            <p class="text-xs font-medium text-gray-500 uppercase mb-2">{{ t('cash_position') }}</p>
            <p class="text-xl font-bold text-gray-900">{{ formatCurrency(cashValue) }}</p>
          </div>
          <div class="bg-white rounded-lg shadow p-5">
            <p class="text-xs font-medium text-gray-500 uppercase mb-2">{{ t('receivable_days') }}</p>
            <p class="text-xl font-bold" :class="receivableDaysValue <= 45 ? 'text-green-600' : receivableDaysValue <= 90 ? 'text-yellow-600' : 'text-red-600'">
              {{ Math.round(receivableDaysValue) }} <span class="text-sm font-normal text-gray-500">{{ t('days_label') }}</span>
            </p>
          </div>
        </div>

        <!-- Trend Chart -->
        <div class="bg-white rounded-lg shadow p-5 mb-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-gray-700">{{ t('trends') }} — {{ t('last_12_months') }}</h3>
            <BaseMultiselect
              v-model="selectedRatio"
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
          <div v-else-if="trendData.length" class="flex items-end gap-1 h-40">
            <div
              v-for="(point, idx) in trendData"
              :key="idx"
              class="flex-1 flex flex-col items-center justify-end"
            >
              <span class="text-[9px] text-gray-500 mb-1">{{ formatTrendValue(point.value) }}</span>
              <div
                class="w-full rounded-t transition-all"
                :class="point.value < 0 ? 'bg-red-400' : 'bg-primary-500'"
                :style="{ height: barHeight(point.value) + '%' }"
              ></div>
              <span class="text-[9px] text-gray-400 mt-1">{{ formatMonth(point.date) }}</span>
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
            <div class="px-5 py-3 border-b border-gray-100">
              <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">{{ t('liquidity') }}</h4>
              <div class="grid grid-cols-3 gap-4">
                <div v-for="(val, key) in liquidityRatios" :key="key">
                  <p class="text-xs text-gray-500">{{ t(key) }}</p>
                  <p class="text-sm font-medium">{{ Number(val).toFixed(2) }}</p>
                </div>
              </div>
            </div>
            <div class="px-5 py-3 border-b border-gray-100">
              <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">{{ t('profitability') }}</h4>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div v-for="(val, key) in profitabilityRatios" :key="key">
                  <p class="text-xs text-gray-500">{{ t(key) }}</p>
                  <p class="text-sm font-medium">{{ (Number(val) * 100).toFixed(1) }}%</p>
                </div>
              </div>
            </div>
            <div class="px-5 py-3 border-b border-gray-100">
              <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">{{ t('solvency') }}</h4>
              <div class="grid grid-cols-2 gap-4">
                <div v-for="(val, key) in solvencyRatios" :key="key">
                  <p class="text-xs text-gray-500">{{ t(key) }}</p>
                  <p class="text-sm font-medium">{{ Number(val).toFixed(2) }}</p>
                </div>
              </div>
            </div>
            <div class="px-5 py-3">
              <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">{{ t('activity') }}</h4>
              <div class="grid grid-cols-3 gap-4">
                <div v-for="(val, key) in activityRatios" :key="key">
                  <p class="text-xs text-gray-500">{{ t(key) }}</p>
                  <p class="text-sm font-medium">
                    {{ key === 'inventory_turnover' ? Number(val).toFixed(2) : Math.round(Number(val)) + ' ' + t('days_label') }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </template>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import biMessages from '@/scripts/admin/i18n/bi-dashboard.js'

const notificationStore = useNotificationStore()
const consoleStore = useConsoleStore()

const locale = document.documentElement.lang || 'mk'
function t(key) {
  return biMessages[locale]?.bi_dashboard?.[key]
    || biMessages['en']?.bi_dashboard?.[key]
    || key
}

const companies = computed(() => consoleStore.managedCompanies || [])
const selectedCompanyId = ref(null)
const isLoading = ref(false)
const summaryData = ref(null)
const notInitialized = ref(false)
const selectedPeriod = ref('this_month')
const selectedRatio = ref('gross_margin')
const trendData = ref([])
const trendLoading = ref(false)
const showDetails = ref(false)

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

const liquidityRatios = computed(() => summaryData.value?.ratios?.liquidity || {})
const profitabilityRatios = computed(() => summaryData.value?.ratios?.profitability || {})
const solvencyRatios = computed(() => summaryData.value?.ratios?.solvency || {})
const activityRatios = computed(() => summaryData.value?.ratios?.activity || {})
const healthIndicators = computed(() => summaryData.value?.health || {})
const altmanZone = computed(() => summaryData.value?.ratios?.altman_z?.zone || 'danger')

const revenueValue = computed(() => summaryData.value?.raw?.revenue || 0)
const netMarginValue = computed(() => summaryData.value?.ratios?.profitability?.net_margin || 0)
const cashValue = computed(() => summaryData.value?.raw?.cash || 0)
const receivableDaysValue = computed(() => summaryData.value?.ratios?.activity?.receivable_days || 0)

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
  if (percentRatios.includes(selectedRatio.value)) {
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

function zoneColor(zone) {
  return { safe: 'bg-green-500', caution: 'bg-yellow-500', danger: 'bg-red-500' }[zone] || 'bg-gray-400'
}

function zoneTextColor(zone) {
  return { safe: 'text-green-700', caution: 'text-yellow-700', danger: 'text-red-700' }[zone] || 'text-gray-700'
}

function zoneDot(status) {
  return { safe: 'bg-green-500', caution: 'bg-yellow-500', danger: 'bg-red-500' }[status] || 'bg-gray-400'
}

function barHeight(value) {
  if (!trendData.value.length) return 0
  const maxVal = Math.max(...trendData.value.map(p => Math.abs(p.value)), 0.001)
  return Math.max((Math.abs(value) / maxVal) * 100, 3)
}

function partnerApi(path) {
  return `/partner/companies/${selectedCompanyId.value}/accounting/bi-dashboard${path}`
}

function selectPeriod(val) {
  selectedPeriod.value = val
  loadSummary()
}

async function onCompanyChange() {
  if (!selectedCompanyId.value) return
  summaryData.value = null
  notInitialized.value = false
  trendData.value = []
  loadSummary()
  loadTrend()
}

async function loadSummary() {
  if (!selectedCompanyId.value) return
  isLoading.value = true
  notInitialized.value = false
  try {
    const { data } = await axios.get(partnerApi('/summary'), {
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
  if (!selectedCompanyId.value) return
  trendLoading.value = true
  try {
    const { data } = await axios.get(partnerApi('/trends'), {
      params: { ratio_type: selectedRatio.value, months: 12 },
    })
    trendData.value = data.data?.trends || []
  } catch (e) {
    notificationStore.showNotification({ type: 'error', message: t('error_loading_trends') })
    trendData.value = []
  } finally {
    trendLoading.value = false
  }
}

onMounted(() => {
  consoleStore.fetchCompanies()
})
</script>

// CLAUDE-CHECKPOINT
