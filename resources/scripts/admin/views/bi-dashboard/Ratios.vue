<template>
  <BasePage>
    <BasePageHeader :title="t('financial_ratios')">
      <template #actions>
        <div class="flex items-center gap-3">
          <BaseMultiselect
            v-model="selectedPeriod"
            :options="periodOptions"
            :searchable="false"
            label="label"
            value-prop="value"
            class="w-48"
            @update:model-value="loadRatios"
          />
          <router-link :to="{ name: 'bi-dashboard.index' }">
            <BaseButton variant="primary-outline" size="sm">{{ t('summary') }}</BaseButton>
          </router-link>
        </div>
      </template>
    </BasePageHeader>

    <!-- Loading -->
    <div v-if="isLoading" class="text-center py-12">
      <p class="text-sm text-gray-500">{{ $t('general.loading') }}...</p>
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
    <div v-else-if="!ratiosData" class="text-center py-12 bg-white rounded-lg shadow">
      <p class="text-sm text-gray-500">{{ t('no_data') }}</p>
    </div>

    <template v-else>
      <!-- All Ratios Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('financial_ratios') }}</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('value') }}</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('benchmark') }}</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ t('status') }}</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ t('trend') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <!-- Liquidity Section -->
            <tr class="bg-gray-50">
              <td colspan="5" class="px-6 py-2 text-xs font-bold text-gray-600 uppercase">{{ t('liquidity') }}</td>
            </tr>
            <tr v-for="ratio in liquidityRows" :key="ratio.key" class="hover:bg-gray-50 cursor-pointer" @click="showTrend(ratio.key)">
              <td class="px-6 py-3 text-sm text-gray-900">{{ t(ratio.key) }}</td>
              <td class="px-6 py-3 text-sm text-right font-medium">{{ ratio.formatted }}</td>
              <td class="px-6 py-3 text-sm text-right text-gray-500">{{ ratio.benchmark }}</td>
              <td class="px-6 py-3 text-center">
                <span class="inline-flex w-3 h-3 rounded-full" :class="ratio.statusColor"></span>
              </td>
              <td class="px-6 py-3 text-center">
                <div v-if="ratio.trend !== null" class="inline-flex items-center gap-1">
                  <span :class="ratio.trend >= 0 ? 'text-green-600' : 'text-red-600'" class="text-xs">
                    {{ ratio.trend >= 0 ? '+' : '' }}{{ ratio.trend.toFixed(2) }}
                  </span>
                </div>
                <span v-else class="text-xs text-gray-400">--</span>
              </td>
            </tr>

            <!-- Profitability Section -->
            <tr class="bg-gray-50">
              <td colspan="5" class="px-6 py-2 text-xs font-bold text-gray-600 uppercase">{{ t('profitability') }}</td>
            </tr>
            <tr v-for="ratio in profitabilityRows" :key="ratio.key" class="hover:bg-gray-50 cursor-pointer" @click="showTrend(ratio.key)">
              <td class="px-6 py-3 text-sm text-gray-900">{{ t(ratio.key) }}</td>
              <td class="px-6 py-3 text-sm text-right font-medium">{{ ratio.formatted }}</td>
              <td class="px-6 py-3 text-sm text-right text-gray-500">{{ ratio.benchmark }}</td>
              <td class="px-6 py-3 text-center">
                <span class="inline-flex w-3 h-3 rounded-full" :class="ratio.statusColor"></span>
              </td>
              <td class="px-6 py-3 text-center">
                <div v-if="ratio.trend !== null" class="inline-flex items-center gap-1">
                  <span :class="ratio.trend >= 0 ? 'text-green-600' : 'text-red-600'" class="text-xs">
                    {{ ratio.trend >= 0 ? '+' : '' }}{{ (ratio.trend * 100).toFixed(1) }}%
                  </span>
                </div>
                <span v-else class="text-xs text-gray-400">--</span>
              </td>
            </tr>

            <!-- Solvency Section -->
            <tr class="bg-gray-50">
              <td colspan="5" class="px-6 py-2 text-xs font-bold text-gray-600 uppercase">{{ t('solvency') }}</td>
            </tr>
            <tr v-for="ratio in solvencyRows" :key="ratio.key" class="hover:bg-gray-50 cursor-pointer" @click="showTrend(ratio.key)">
              <td class="px-6 py-3 text-sm text-gray-900">{{ t(ratio.key) }}</td>
              <td class="px-6 py-3 text-sm text-right font-medium">{{ ratio.formatted }}</td>
              <td class="px-6 py-3 text-sm text-right text-gray-500">{{ ratio.benchmark }}</td>
              <td class="px-6 py-3 text-center">
                <span class="inline-flex w-3 h-3 rounded-full" :class="ratio.statusColor"></span>
              </td>
              <td class="px-6 py-3 text-center">
                <div v-if="ratio.trend !== null" class="inline-flex items-center gap-1">
                  <span :class="ratio.trend >= 0 ? 'text-green-600' : 'text-red-600'" class="text-xs">
                    {{ ratio.trend >= 0 ? '+' : '' }}{{ ratio.trend.toFixed(2) }}
                  </span>
                </div>
                <span v-else class="text-xs text-gray-400">--</span>
              </td>
            </tr>

            <!-- Activity Section -->
            <tr class="bg-gray-50">
              <td colspan="5" class="px-6 py-2 text-xs font-bold text-gray-600 uppercase">{{ t('activity') }}</td>
            </tr>
            <tr v-for="ratio in activityRows" :key="ratio.key" class="hover:bg-gray-50 cursor-pointer" @click="showTrend(ratio.key)">
              <td class="px-6 py-3 text-sm text-gray-900">{{ t(ratio.key) }}</td>
              <td class="px-6 py-3 text-sm text-right font-medium">{{ ratio.formatted }}</td>
              <td class="px-6 py-3 text-sm text-right text-gray-500">{{ ratio.benchmark }}</td>
              <td class="px-6 py-3 text-center">
                <span class="inline-flex w-3 h-3 rounded-full" :class="ratio.statusColor"></span>
              </td>
              <td class="px-6 py-3 text-center">
                <div v-if="ratio.trend !== null" class="inline-flex items-center gap-1">
                  <span :class="ratio.trend >= 0 ? 'text-green-600' : 'text-red-600'" class="text-xs">
                    {{ ratio.trend >= 0 ? '+' : '' }}{{ ratio.trend.toFixed(1) }}
                  </span>
                </div>
                <span v-else class="text-xs text-gray-400">--</span>
              </td>
            </tr>

            <!-- Altman Z-Score -->
            <tr class="bg-gray-50">
              <td colspan="5" class="px-6 py-2 text-xs font-bold text-gray-600 uppercase">{{ t('altman_z') }}</td>
            </tr>
            <tr class="hover:bg-gray-50 cursor-pointer" @click="showTrend('altman_z')">
              <td class="px-6 py-3 text-sm text-gray-900">{{ t('altman_z') }}</td>
              <td class="px-6 py-3 text-sm text-right font-medium">{{ altmanZValue }}</td>
              <td class="px-6 py-3 text-sm text-right text-gray-500">> 2.99</td>
              <td class="px-6 py-3 text-center">
                <span class="inline-flex w-3 h-3 rounded-full" :class="altmanStatusColor"></span>
              </td>
              <td class="px-6 py-3 text-center">
                <span class="text-xs text-gray-400">--</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Inline Trend Chart -->
      <div v-if="selectedTrendKey" class="mt-6 bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-gray-700">{{ t(selectedTrendKey) }} — {{ t('last_12_months') }}</h3>
          <button class="text-gray-400 hover:text-gray-600 text-sm" @click="selectedTrendKey = null">X</button>
        </div>
        <div v-if="trendLoading" class="text-center py-8">
          <p class="text-xs text-gray-400">{{ $t('general.loading') }}...</p>
        </div>
        <div v-else-if="trendData.length" class="flex items-end gap-1 h-32">
          <div
            v-for="(point, idx) in trendData"
            :key="idx"
            class="flex-1 flex flex-col items-center justify-end"
          >
            <div
              class="w-full bg-primary-500 rounded-t transition-all"
              :style="{ height: trendBarHeight(point.value) + '%' }"
              :title="point.date + ': ' + point.value"
            ></div>
            <span class="text-[9px] text-gray-400 mt-1">{{ point.date.substring(5, 7) }}</span>
          </div>
        </div>
        <div v-else class="text-center py-8">
          <p class="text-xs text-gray-400">{{ t('no_data') }}</p>
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
const ratiosData = ref(null)
const notInitialized = ref(false)
const selectedPeriod = ref('this_month')
const selectedTrendKey = ref(null)
const trendData = ref([])
const trendLoading = ref(false)

const periodOptions = [
  { value: 'this_month', label: t('this_month') },
  { value: 'last_month', label: t('last_month') },
  { value: 'last_quarter', label: t('last_quarter') },
  { value: 'this_year', label: t('this_year') },
]

const benchmarks = {
  current_ratio: '>= 1.50',
  quick_ratio: '>= 1.00',
  cash_ratio: '>= 0.20',
  gross_margin: '>= 30%',
  net_margin: '>= 10%',
  roe: '>= 15%',
  roa: '>= 5%',
  debt_to_equity: '<= 1.00',
  interest_coverage: '>= 3.00',
  receivable_days: '<= 45',
  payable_days: '<= 60',
  inventory_turnover: '>= 5.00',
}

function getPeriodDate() {
  const now = new Date()
  switch (selectedPeriod.value) {
    case 'last_month': {
      const d = new Date(now.getFullYear(), now.getMonth() - 1 + 1, 0)
      return d.toISOString().split('T')[0]
    }
    case 'last_quarter': {
      const qMonth = Math.floor((now.getMonth()) / 3) * 3
      const d = new Date(now.getFullYear(), qMonth, 0)
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

function statusColor(key, value) {
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

function buildRows(group, formatFn) {
  if (!ratiosData.value?.[group]) return []
  return Object.entries(ratiosData.value[group]).map(([key, value]) => ({
    key,
    value,
    formatted: formatFn(value),
    benchmark: benchmarks[key] || '--',
    statusColor: statusColor(key, value),
    trend: null,
  }))
}

const liquidityRows = computed(() => buildRows('liquidity', v => Number(v).toFixed(2)))
const profitabilityRows = computed(() => buildRows('profitability', v => (Number(v) * 100).toFixed(1) + '%'))
const solvencyRows = computed(() => buildRows('solvency', v => Number(v).toFixed(2)))
const activityRows = computed(() => buildRows('activity', v => {
  return Number(v).toFixed(1)
}))

const altmanZValue = computed(() => ratiosData.value?.altman_z?.z_score?.toFixed(2) || '0.00')
const altmanStatusColor = computed(() => {
  const zone = ratiosData.value?.altman_z?.zone || 'danger'
  return { safe: 'bg-green-500', caution: 'bg-yellow-500', danger: 'bg-red-500' }[zone] || 'bg-gray-400'
})

function trendBarHeight(value) {
  if (!trendData.value.length) return 0
  const maxVal = Math.max(...trendData.value.map(p => Math.abs(p.value)), 0.001)
  return Math.max((Math.abs(value) / maxVal) * 100, 2)
}

async function loadRatios() {
  isLoading.value = true
  notInitialized.value = false
  try {
    const { data } = await axios.get('/bi-dashboard/ratios', {
      params: { date: getPeriodDate() },
    })
    if (data.message === 'accounting_not_initialized') {
      notInitialized.value = true
      ratiosData.value = null
    } else {
      ratiosData.value = data.data || null
    }
  } catch (e) {
    notificationStore.showNotification({ type: 'error', message: t('error_loading_ratios') })
    ratiosData.value = null
  } finally {
    isLoading.value = false
  }
}

async function showTrend(ratioKey) {
  selectedTrendKey.value = ratioKey
  trendLoading.value = true
  try {
    const { data } = await axios.get('/bi-dashboard/trends', {
      params: { ratio_type: ratioKey, months: 12 },
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
  loadRatios()
})
</script>

// CLAUDE-CHECKPOINT
