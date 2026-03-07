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
      <!-- Period Selector -->
      <div class="flex items-center justify-between mb-6">
        <BaseMultiselect
          v-model="selectedPeriod"
          :options="periodOptions"
          :searchable="false"
          label="label"
          value-prop="value"
          class="w-48"
          @update:model-value="loadSummary"
        />
      </div>

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
                  Z-Score: {{ altmanZScore }}
                </p>
              </div>
              <div class="flex gap-4">
                <div v-for="(status, key) in healthIndicators" :key="key" class="text-center">
                  <div class="w-3 h-3 rounded-full mx-auto mb-1" :class="zoneDot(status)"></div>
                  <p class="text-xs text-gray-500">{{ t(key) }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Ratio Groups -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
          <!-- Liquidity -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 uppercase mb-4">{{ t('liquidity') }}</h3>
            <div class="space-y-3">
              <div v-for="(value, key) in liquidityRatios" :key="key" class="flex items-center justify-between">
                <span class="text-sm text-gray-700">{{ t(key) }}</span>
                <span class="text-sm font-medium">{{ Number(value).toFixed(2) }}</span>
              </div>
            </div>
          </div>

          <!-- Profitability -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 uppercase mb-4">{{ t('profitability') }}</h3>
            <div class="space-y-3">
              <div v-for="(value, key) in profitabilityRatios" :key="key" class="flex items-center justify-between">
                <span class="text-sm text-gray-700">{{ t(key) }}</span>
                <span class="text-sm font-medium">{{ (Number(value) * 100).toFixed(1) }}%</span>
              </div>
            </div>
          </div>

          <!-- Activity -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 uppercase mb-4">{{ t('activity') }}</h3>
            <div class="space-y-3">
              <div v-for="(value, key) in activityRatios" :key="key" class="flex items-center justify-between">
                <span class="text-sm text-gray-700">{{ t(key) }}</span>
                <span class="text-sm font-medium">{{ Number(value).toFixed(1) }}</span>
              </div>
            </div>
          </div>

          <!-- Solvency -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 uppercase mb-4">{{ t('solvency') }}</h3>
            <div class="space-y-3">
              <div v-for="(value, key) in solvencyRatios" :key="key" class="flex items-center justify-between">
                <span class="text-sm text-gray-700">{{ t(key) }}</span>
                <span class="text-sm font-medium">{{ Number(value).toFixed(2) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Trend Section -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-gray-500 uppercase">{{ t('trends') }}</h3>
            <BaseMultiselect
              v-model="selectedRatio"
              :options="ratioOptions"
              :searchable="true"
              label="label"
              value-prop="value"
              class="w-56"
              @update:model-value="loadTrend"
            />
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

const companies = ref([])
const selectedCompanyId = ref(null)
const isLoading = ref(false)
const summaryData = ref(null)
const selectedPeriod = ref('this_month')
const selectedRatio = ref('current_ratio')
const trendData = ref([])
const trendLoading = ref(false)

const periodOptions = [
  { value: 'this_month', label: t('this_month') },
  { value: 'last_month', label: t('last_month') },
  { value: 'last_quarter', label: t('last_quarter') },
  { value: 'this_year', label: t('this_year') },
]

const ratioOptions = [
  { value: 'current_ratio', label: t('current_ratio') },
  { value: 'quick_ratio', label: t('quick_ratio') },
  { value: 'cash_ratio', label: t('cash_ratio') },
  { value: 'gross_margin', label: t('gross_margin') },
  { value: 'net_margin', label: t('net_margin') },
  { value: 'roe', label: t('roe') },
  { value: 'roa', label: t('roa') },
  { value: 'debt_to_equity', label: t('debt_to_equity') },
  { value: 'interest_coverage', label: t('interest_coverage') },
  { value: 'receivable_days', label: t('receivable_days') },
  { value: 'payable_days', label: t('payable_days') },
  { value: 'inventory_turnover', label: t('inventory_turnover') },
  { value: 'altman_z', label: t('altman_z') },
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
const altmanZScore = computed(() => summaryData.value?.ratios?.altman_z?.z_score?.toFixed(2) || '0.00')
const altmanZone = computed(() => summaryData.value?.ratios?.altman_z?.zone || 'danger')

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

function trendBarHeight(value) {
  if (!trendData.value.length) return 0
  const maxVal = Math.max(...trendData.value.map(p => Math.abs(p.value)), 0.001)
  return Math.max((Math.abs(value) / maxVal) * 100, 2)
}

function partnerApi(path) {
  return `/partner/companies/${selectedCompanyId.value}/accounting/bi-dashboard${path}`
}

async function loadCompanies() {
  try {
    const { data } = await axios.get('/partner/companies')
    companies.value = data.data || data.companies || data || []
  } catch (e) {
    notificationStore.showNotification({ type: 'error', message: t('error_loading_summary') })
  }
}

async function onCompanyChange() {
  if (!selectedCompanyId.value) return
  summaryData.value = null
  trendData.value = []
  loadSummary()
}

async function loadSummary() {
  if (!selectedCompanyId.value) return
  isLoading.value = true
  try {
    const { data } = await axios.get(partnerApi('/summary'), {
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
  loadCompanies()
})
</script>

// CLAUDE-CHECKPOINT
