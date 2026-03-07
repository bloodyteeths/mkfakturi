<template>
  <BasePage>
    <BasePageHeader :title="t('title')">
      <template #actions>
        <div class="flex items-center gap-3">
          <BaseMultiselect
            v-model="selectedPeriod"
            :options="periodOptions"
            :searchable="false"
            label="label"
            value-prop="value"
            class="w-48"
            @update:model-value="loadSummary"
          />
          <BaseButton variant="primary-outline" size="sm" @click="refreshData">
            {{ t('refresh') }}
          </BaseButton>
        </div>
      </template>
    </BasePageHeader>

    <!-- Loading -->
    <div v-if="isLoading" class="text-center py-12">
      <p class="text-sm text-gray-500">{{ $t('general.loading') }}...</p>
    </div>

    <!-- No Data -->
    <div v-else-if="!summaryData" class="text-center py-12 bg-white rounded-lg shadow">
      <p class="text-sm text-gray-500">{{ t('no_data') }}</p>
    </div>

    <template v-else>
      <!-- Health Score Card -->
      <div class="mb-6">
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-sm font-medium text-gray-500 uppercase mb-3">{{ t('health_score') }}</h3>
          <div class="flex items-center gap-6">
            <div class="flex-shrink-0">
              <div
                class="w-20 h-20 rounded-full flex items-center justify-center text-lg font-bold text-white"
                :class="zoneColor(altmanZone)"
              >
                {{ altmanZScore }}
              </div>
            </div>
            <div class="flex-1">
              <p class="text-lg font-semibold" :class="zoneTextColor(altmanZone)">
                {{ t('altman_z') }}: {{ t(altmanZone) }}
              </p>
              <p class="text-sm text-gray-500 mt-1">
                Z-Score: {{ altmanZScore }} (>2.99 = {{ t('safe') }}, 1.81-2.99 = {{ t('caution') }}, &lt;1.81 = {{ t('danger') }})
              </p>
            </div>
            <!-- Health indicators -->
            <div class="flex gap-4">
              <div v-for="(status, key) in healthIndicators" :key="key" class="text-center">
                <div
                  class="w-3 h-3 rounded-full mx-auto mb-1"
                  :class="zoneDot(status)"
                ></div>
                <p class="text-xs text-gray-500">{{ t(key) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Ratio Sections -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Liquidity -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-sm font-medium text-gray-500 uppercase mb-4">{{ t('liquidity') }}</h3>
          <div class="space-y-4">
            <div v-for="(value, key) in liquidityRatios" :key="key">
              <div class="flex items-center justify-between mb-1">
                <span class="text-sm text-gray-700">{{ t(key) }}</span>
                <span class="text-sm font-medium">{{ formatRatio(value) }}</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div
                  class="h-2 rounded-full transition-all"
                  :class="ratioBarColor(key, value)"
                  :style="{ width: ratioBarWidth(key, value) + '%' }"
                ></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Profitability -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-sm font-medium text-gray-500 uppercase mb-4">{{ t('profitability') }}</h3>
          <div class="space-y-4">
            <div v-for="(value, key) in profitabilityRatios" :key="key">
              <div class="flex items-center justify-between mb-1">
                <span class="text-sm text-gray-700">{{ t(key) }}</span>
                <span class="text-sm font-medium">{{ formatPercent(value) }}</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div
                  class="h-2 rounded-full transition-all"
                  :class="value >= 0 ? 'bg-green-500' : 'bg-red-500'"
                  :style="{ width: Math.min(Math.abs(value) * 100, 100) + '%' }"
                ></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Activity -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-sm font-medium text-gray-500 uppercase mb-4">{{ t('activity') }}</h3>
          <div class="space-y-4">
            <div v-for="(value, key) in activityRatios" :key="key">
              <div class="flex items-center justify-between mb-1">
                <span class="text-sm text-gray-700">{{ t(key) }}</span>
                <span class="text-sm font-medium">
                  {{ key === 'inventory_turnover' ? formatRatio(value) : formatDays(value) }}
                </span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div
                  class="h-2 rounded-full bg-indigo-500 transition-all"
                  :style="{ width: Math.min((key === 'inventory_turnover' ? value * 10 : value / 3.65), 100) + '%' }"
                ></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Solvency -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-sm font-medium text-gray-500 uppercase mb-4">{{ t('solvency') }}</h3>
          <div class="space-y-4">
            <div v-for="(value, key) in solvencyRatios" :key="key">
              <div class="flex items-center justify-between mb-1">
                <span class="text-sm text-gray-700">{{ t(key) }}</span>
                <span class="text-sm font-medium">{{ formatRatio(value) }}</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div
                  class="h-2 rounded-full transition-all"
                  :class="key === 'debt_to_equity' ? (value <= 1 ? 'bg-green-500' : value <= 2 ? 'bg-yellow-500' : 'bg-red-500') : 'bg-blue-500'"
                  :style="{ width: Math.min(key === 'debt_to_equity' ? value * 33 : Math.abs(value) * 10, 100) + '%' }"
                ></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Navigation to detailed views -->
      <div class="flex gap-4">
        <router-link :to="{ name: 'bi-dashboard.ratios' }" class="flex-1 bg-white rounded-lg shadow p-4 hover:shadow-md transition-shadow text-center">
          <p class="text-sm font-medium text-primary-500">{{ t('ratios') }}</p>
          <p class="text-xs text-gray-500 mt-1">{{ t('financial_ratios') }}</p>
        </router-link>
        <router-link :to="{ name: 'bi-dashboard.trends' }" class="flex-1 bg-white rounded-lg shadow p-4 hover:shadow-md transition-shadow text-center">
          <p class="text-sm font-medium text-primary-500">{{ t('trends') }}</p>
          <p class="text-xs text-gray-500 mt-1">{{ t('last_12_months') }}</p>
        </router-link>
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
const selectedPeriod = ref('this_month')

const periodOptions = [
  { value: 'this_month', label: t('this_month') },
  { value: 'last_month', label: t('last_month') },
  { value: 'last_quarter', label: t('last_quarter') },
  { value: 'this_year', label: t('this_year') },
]

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
    case 'this_year': {
      return `${now.getFullYear()}-12-31`
    }
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
const altmanZScore = computed(() => summaryData.value?.ratios?.altman_z?.z_score?.toFixed(2) || '0.00')
const altmanZone = computed(() => summaryData.value?.ratios?.altman_z?.zone || 'danger')

function formatRatio(val) {
  if (val === null || val === undefined) return '0.00'
  return Number(val).toFixed(2)
}

function formatPercent(val) {
  if (val === null || val === undefined) return '0.0%'
  return (Number(val) * 100).toFixed(1) + '%'
}

function formatDays(val) {
  if (val === null || val === undefined) return '0'
  return Math.round(Number(val)) + ' ' + t('days_label')
}

function zoneColor(zone) {
  const map = { safe: 'bg-green-500', caution: 'bg-yellow-500', danger: 'bg-red-500' }
  return map[zone] || 'bg-gray-400'
}

function zoneTextColor(zone) {
  const map = { safe: 'text-green-700', caution: 'text-yellow-700', danger: 'text-red-700' }
  return map[zone] || 'text-gray-700'
}

function zoneDot(status) {
  const map = { safe: 'bg-green-500', caution: 'bg-yellow-500', danger: 'bg-red-500' }
  return map[status] || 'bg-gray-400'
}

function ratioBarColor(key, value) {
  if (key === 'current_ratio') return value >= 1.5 ? 'bg-green-500' : value >= 1 ? 'bg-yellow-500' : 'bg-red-500'
  if (key === 'quick_ratio') return value >= 1 ? 'bg-green-500' : value >= 0.5 ? 'bg-yellow-500' : 'bg-red-500'
  if (key === 'cash_ratio') return value >= 0.5 ? 'bg-green-500' : value >= 0.2 ? 'bg-yellow-500' : 'bg-red-500'
  return 'bg-blue-500'
}

function ratioBarWidth(key, value) {
  if (key === 'current_ratio') return Math.min(value * 33, 100)
  if (key === 'quick_ratio') return Math.min(value * 50, 100)
  if (key === 'cash_ratio') return Math.min(value * 100, 100)
  return Math.min(value * 50, 100)
}

async function loadSummary() {
  isLoading.value = true
  try {
    const { data } = await axios.get('/bi-dashboard/summary', {
      params: { date: getPeriodDate() },
    })
    summaryData.value = data.data || null
  } catch (e) {
    notificationStore.showNotification({ type: 'error', message: t('error_loading_summary') })
    summaryData.value = null
  } finally {
    isLoading.value = false
  }
}

async function refreshData() {
  isLoading.value = true
  try {
    const { data } = await axios.post('/bi-dashboard/refresh', null, {
      params: { date: getPeriodDate() },
    })
    summaryData.value = data.data || null
  } catch (e) {
    notificationStore.showNotification({ type: 'error', message: t('error_refreshing') })
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadSummary()
})
</script>

// CLAUDE-CHECKPOINT
