<template>
  <div class="commissions-page min-h-screen overflow-auto pb-8">
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $t('partner.console.commissions') }}</h1>
      <p class="text-gray-600">{{ $t('partner.commissions.description') }}</p>
    </div>

    <!-- Loading Skeleton -->
    <div v-if="loading" class="space-y-8">
      <!-- KPI Cards Skeleton -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div v-for="n in 3" :key="n" class="bg-white rounded-lg shadow p-6 animate-pulse">
          <div class="h-4 w-24 bg-gray-200 rounded mb-3"></div>
          <div class="h-8 w-32 bg-gray-200 rounded mb-2"></div>
          <div class="h-3 w-20 bg-gray-200 rounded"></div>
        </div>
      </div>

      <!-- Monthly Trend Skeleton -->
      <div class="bg-white rounded-lg shadow animate-pulse">
        <div class="px-6 py-4 border-b border-gray-200">
          <div class="h-5 w-32 bg-gray-200 rounded"></div>
        </div>
        <div class="p-6 space-y-3">
          <div v-for="n in 4" :key="n" class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
            <div class="h-4 w-24 bg-gray-200 rounded"></div>
            <div class="flex items-center gap-4">
              <div class="w-32 bg-gray-200 rounded-full h-2"></div>
              <div class="h-4 w-20 bg-gray-200 rounded"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Table Skeleton -->
      <div class="bg-white rounded-lg shadow animate-pulse">
        <div class="px-6 py-4 border-b border-gray-200">
          <div class="h-5 w-40 bg-gray-200 rounded"></div>
        </div>
        <div class="p-6">
          <div v-for="n in 3" :key="n" class="flex items-center py-4 border-b border-gray-100">
            <div class="h-10 w-10 rounded-full bg-gray-200"></div>
            <div class="ml-4 flex-1">
              <div class="h-4 w-32 bg-gray-200 rounded mb-2"></div>
              <div class="h-3 w-20 bg-gray-200 rounded"></div>
            </div>
            <div class="h-4 w-16 bg-gray-200 rounded ml-4"></div>
            <div class="h-4 w-20 bg-gray-200 rounded ml-4"></div>
            <div class="h-4 w-24 bg-gray-200 rounded ml-4"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6" role="alert" aria-live="polite">
      <p class="text-red-800">{{ error }}</p>
      <button
        @click="fetchCommissions"
        class="mt-2 text-red-600 underline"
        :aria-label="$t('general.retry')"
      >
        {{ $t('general.retry') }}
      </button>
    </div>

    <template v-else>
      <!-- KPI Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8" role="region" :aria-label="$t('partner.commissions.kpi_summary')">
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-sm font-medium text-gray-600 mb-2">{{ $t('partner.commissions.total_earnings') }}</h3>
          <div class="text-3xl font-bold text-green-600">{{ formatCurrency(kpis.total_earnings) }}</div>
          <p class="text-xs text-gray-500 mt-1">{{ $t('partner.commissions.from_start') }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-sm font-medium text-gray-600 mb-2">{{ $t('partner.commissions.this_month') }}</h3>
          <div class="text-3xl font-bold text-blue-600">{{ formatCurrency(kpis.this_month) }}</div>
          <p class="text-xs text-gray-500 mt-1">{{ $t('partner.commissions.current_month') }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-sm font-medium text-gray-600 mb-2">{{ $t('partner.commissions.pending_payout') }}</h3>
          <div class="text-3xl font-bold text-orange-600">{{ formatCurrency(kpis.pending_payout) }}</div>
          <p class="text-xs text-gray-500 mt-1">{{ $t('partner.commissions.payout_date') }}</p>
        </div>
      </div>

      <!-- Monthly Trend -->
      <div class="bg-white rounded-lg shadow mb-8">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">{{ $t('partner.commissions.monthly_trend') }}</h3>
        </div>
        <div class="p-6">
          <div v-if="monthlyTrend.length > 0" class="space-y-3" role="list" :aria-label="$t('partner.commissions.monthly_trend')">
            <div
              v-for="item in monthlyTrend"
              :key="item.month"
              class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition"
              role="listitem"
            >
              <span class="text-sm font-medium text-gray-700">{{ item.month }}</span>
              <div class="flex items-center gap-4">
                <div class="w-32 bg-gray-200 rounded-full h-2" role="progressbar" :aria-valuenow="getBarWidth(item.total)" aria-valuemin="0" aria-valuemax="100">
                  <div
                    class="bg-green-500 h-2 rounded-full transition-all duration-300"
                    :style="{ width: getBarWidth(item.total) + '%' }"
                  ></div>
                </div>
                <span class="text-sm font-semibold text-green-600 min-w-[100px] text-right">
                  {{ formatCurrency(item.total) }}
                </span>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8 text-gray-500" role="status">
            {{ $t('partner.commissions.no_monthly_trend') }}
          </div>
        </div>
      </div>

      <!-- Per-Company Breakdown -->
      <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">{{ $t('partner.commissions.by_company') }}</h3>
        </div>
        <div class="overflow-x-auto">
          <table v-if="perCompany.length > 0" class="min-w-full divide-y divide-gray-200" :aria-label="$t('partner.commissions.by_company')">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ $t('partner.commissions.company') }}
                </th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ $t('partner.commissions.rate') }}
                </th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ $t('partner.commissions.this_month') }}
                </th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ $t('partner.commissions.total') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="company in perCompany" :key="company.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center" aria-hidden="true">
                      <span class="text-sm font-bold text-blue-600">
                        {{ company.name.charAt(0).toUpperCase() }}
                      </span>
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-medium text-gray-900">{{ company.name }}</div>
                      <div class="text-xs text-gray-500">{{ company.subscription_status || $t('partner.commissions.status_active') }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-600">
                  {{ company.commission_rate || 0 }}%
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-blue-600 font-medium">
                  {{ formatCurrency(company.this_month || 0) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold text-green-600">
                  {{ formatCurrency(company.total) }}
                </td>
              </tr>
            </tbody>
            <tfoot class="bg-gray-50">
              <tr>
                <td colspan="2" class="px-6 py-4 text-sm font-medium text-gray-900">
                  {{ $t('partner.commissions.total') }}
                </td>
                <td class="px-6 py-4 text-sm text-right font-bold text-blue-600">
                  {{ formatCurrency(totalThisMonth) }}
                </td>
                <td class="px-6 py-4 text-sm text-right font-bold text-green-600">
                  {{ formatCurrency(totalAll) }}
                </td>
              </tr>
            </tfoot>
          </table>
          <div v-else class="px-6 py-12 text-center text-gray-500" role="status">
            {{ $t('partner.commissions.no_company_data') }}
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useCompanyStore } from '@/scripts/admin/stores/company'

const { t } = useI18n()
const notificationStore = useNotificationStore()
const companyStore = useCompanyStore()

// State
const loading = ref(true)
const error = ref(null)
const kpis = ref({
  total_earnings: 0,
  this_month: 0,
  pending_payout: 0
})
const monthlyTrend = ref([])
const perCompany = ref([])

// AbortController for cancelling pending requests
let abortController = null

// Computed
const maxMonthlyValue = computed(() => {
  if (monthlyTrend.value.length === 0) return 1
  return Math.max(...monthlyTrend.value.map(m => m.total), 1)
})

const totalThisMonth = computed(() => {
  return perCompany.value.reduce((sum, c) => sum + (c.this_month || 0), 0)
})

const totalAll = computed(() => {
  return perCompany.value.reduce((sum, c) => sum + (c.total || 0), 0)
})

// Get currency from company store or fallback to EUR
const currencyCode = computed(() => {
  return companyStore.selectedCompanyCurrency?.code || 'EUR'
})

onMounted(() => {
  fetchCommissions()
})

onBeforeUnmount(() => {
  // Cancel any pending requests
  if (abortController) {
    abortController.abort()
  }
})

async function fetchCommissions() {
  // Cancel any pending request before starting a new one
  if (abortController) {
    abortController.abort()
  }
  abortController = new AbortController()

  loading.value = true
  error.value = null

  try {
    const response = await axios.get('/console/commissions', {
      signal: abortController.signal
    })
    const data = response.data || {}
    kpis.value = data.kpis || { total_earnings: 0, this_month: 0, pending_payout: 0 }
    monthlyTrend.value = data.monthly_trend || []
    perCompany.value = data.per_company || []
  } catch (err) {
    // Ignore abort errors - they are expected when cancelling requests
    if (err.name === 'AbortError' || err.message === 'canceled') {
      return
    }
    error.value = t('partner.commissions.load_error')
    notificationStore.showNotification({
      type: 'error',
      message: t('partner.commissions.load_error')
    })
  } finally {
    loading.value = false
  }
}

function formatCurrency(amount) {
  // Database stores amounts as decimal (e.g., 100.00), not cents
  return new Intl.NumberFormat('mk-MK', {
    style: 'currency',
    currency: currencyCode.value
  }).format(amount || 0)
}

function getBarWidth(value) {
  if (!value || !maxMonthlyValue.value) return 0
  return Math.min((value / maxMonthlyValue.value) * 100, 100)
}
</script>

<style scoped>
.commissions-page {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}
</style>

// CLAUDE-CHECKPOINT
