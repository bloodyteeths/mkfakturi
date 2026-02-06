<template>
  <BasePage>
    <BasePageHeader :title="$t('banking.analytics_title') || 'Reconciliation Analytics'">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('banking.title')" to="/admin/banking" />
        <BaseBreadcrumbItem
          :title="$t('banking.analytics_title') || 'Analytics'"
          to="#"
          active
        />
      </BaseBreadcrumb>

      <template #actions>
        <div class="flex items-center justify-end space-x-4">
          <BaseButton
            variant="primary-outline"
            :to="{ name: 'banking.dashboard' }"
          >
            <template #left="slotProps">
              <BaseIcon name="ArrowLeftIcon" :class="slotProps.class" />
            </template>
            {{ $t('banking.title') || 'Banking' }}
          </BaseButton>
          <BaseButton
            variant="primary-outline"
            @click="refreshData"
            :disabled="isLoading"
          >
            <template #left="slotProps">
              <BaseIcon name="ArrowPathIcon" :class="[slotProps.class, { 'animate-spin': isLoading }]" />
            </template>
            {{ $t('general.refresh') || 'Refresh' }}
          </BaseButton>
        </div>
      </template>
    </BasePageHeader>

    <!-- Period Selector -->
    <div class="mt-6 bg-white rounded-lg shadow p-4">
      <div class="flex flex-wrap items-end gap-4">
        <BaseInputGroup :label="$t('general.from') || 'From'" class="text-left w-40">
          <BaseDatePicker
            v-model="filters.from"
            :calendar-button="true"
            calendar-button-icon="calendar"
          />
        </BaseInputGroup>
        <BaseInputGroup :label="$t('general.to') || 'To'" class="text-left w-40">
          <BaseDatePicker
            v-model="filters.to"
            :calendar-button="true"
            calendar-button-icon="calendar"
          />
        </BaseInputGroup>
        <div class="flex items-center space-x-2 pb-1">
          <BaseButton
            variant="primary-outline"
            size="sm"
            @click="setQuickPeriod('this_month')"
          >
            {{ $t('banking.this_month') || 'This Month' }}
          </BaseButton>
          <BaseButton
            variant="primary-outline"
            size="sm"
            @click="setQuickPeriod('last_month')"
          >
            {{ $t('banking.last_month') || 'Last Month' }}
          </BaseButton>
          <BaseButton
            variant="primary-outline"
            size="sm"
            @click="setQuickPeriod('last_30_days')"
          >
            {{ $t('banking.last_30_days') || 'Last 30 Days' }}
          </BaseButton>
        </div>
        <div class="pb-1">
          <BaseButton
            variant="primary"
            size="sm"
            @click="fetchAnalytics"
            :disabled="isLoading"
          >
            {{ $t('general.apply') || 'Apply' }}
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="mt-6 flex justify-center py-12">
      <BaseContentPlaceholders>
        <BaseContentPlaceholdersBox :rounded="true" />
        <BaseContentPlaceholdersBox :rounded="true" />
      </BaseContentPlaceholders>
    </div>

    <template v-else-if="analytics">
      <!-- Summary Cards Row -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
        <!-- Total Transactions -->
        <div class="bg-white rounded-lg shadow p-5">
          <p class="text-sm font-medium text-gray-500">
            {{ $t('banking.total_transactions') || 'Total Transactions' }}
          </p>
          <p class="mt-2 text-3xl font-bold text-gray-900">
            {{ analytics.total_transactions }}
          </p>
          <p class="mt-1 text-xs text-gray-400">
            {{ analytics.period.from }} - {{ analytics.period.to }}
          </p>
        </div>

        <!-- Auto-Match Rate -->
        <div class="bg-white rounded-lg shadow p-5">
          <p class="text-sm font-medium text-gray-500">
            {{ $t('banking.auto_match_rate') || 'Auto-Match Rate' }}
          </p>
          <p
            class="mt-2 text-3xl font-bold"
            :class="autoMatchRateColor"
          >
            {{ formatPercent(analytics.auto_match_rate) }}
          </p>
          <p class="mt-1 text-xs text-gray-400">
            {{ analytics.auto_matched }} / {{ analytics.total_transactions }}
            {{ $t('banking.auto_matched') || 'auto-matched' }}
          </p>
        </div>

        <!-- Average Confidence -->
        <div class="bg-white rounded-lg shadow p-5">
          <p class="text-sm font-medium text-gray-500">
            {{ $t('banking.avg_confidence') || 'Avg Confidence' }}
          </p>
          <p class="mt-2 text-3xl font-bold text-blue-600">
            {{ analytics.avg_confidence.toFixed(1) }}%
          </p>
          <p class="mt-1 text-xs text-gray-400">
            {{ $t('banking.across_all_matches') || 'across all matches' }}
          </p>
        </div>

        <!-- Pending -->
        <div class="bg-white rounded-lg shadow p-5">
          <p class="text-sm font-medium text-gray-500">
            {{ $t('banking.pending') || 'Pending' }}
          </p>
          <p
            class="mt-2 text-3xl font-bold"
            :class="pendingColor"
          >
            {{ analytics.pending }}
          </p>
          <p class="mt-1 text-xs text-gray-400">
            {{ $t('banking.unmatched_transactions') || 'unmatched transactions' }}
          </p>
        </div>
      </div>

      <!-- Amount Summary -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
        <div class="bg-white rounded-lg shadow p-5">
          <p class="text-sm font-medium text-gray-500">
            {{ $t('banking.total_amount_matched') || 'Total Amount Matched' }}
          </p>
          <p class="mt-2 text-2xl font-bold text-green-600">
            {{ formatMoney(analytics.total_amount_matched) }}
          </p>
        </div>
        <div class="bg-white rounded-lg shadow p-5">
          <p class="text-sm font-medium text-gray-500">
            {{ $t('banking.total_amount_pending') || 'Total Amount Pending' }}
          </p>
          <p class="mt-2 text-2xl font-bold text-yellow-600">
            {{ formatMoney(analytics.total_amount_pending) }}
          </p>
        </div>
      </div>

      <!-- Charts Section -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <!-- Daily Trend Chart -->
        <div class="bg-white rounded-lg shadow p-5">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $t('banking.daily_trend') || 'Daily Trend' }}
          </h3>
          <div v-if="analytics.daily_trend.length === 0" class="text-center py-8 text-gray-400">
            {{ $t('banking.no_data_for_period') || 'No data for this period' }}
          </div>
          <div v-else class="space-y-1">
            <div
              v-for="day in analytics.daily_trend"
              :key="day.date"
              class="flex items-center text-xs"
            >
              <span class="w-20 text-gray-500 flex-shrink-0">{{ formatShortDate(day.date) }}</span>
              <div class="flex-1 flex items-center h-5">
                <div
                  class="h-4 bg-green-500 rounded-l"
                  :style="{ width: getTrendBarWidth(day.matched, day) + '%' }"
                  :title="day.matched + ' matched'"
                ></div>
                <div
                  class="h-4 bg-yellow-400 rounded-r"
                  :style="{ width: getTrendBarWidth(day.unmatched, day) + '%' }"
                  :title="day.unmatched + ' unmatched'"
                ></div>
              </div>
              <span class="w-16 text-right text-gray-500 flex-shrink-0">
                {{ day.matched + day.unmatched }}
              </span>
            </div>
          </div>
          <div class="flex items-center justify-center space-x-6 mt-4 text-xs text-gray-500">
            <div class="flex items-center">
              <div class="w-3 h-3 bg-green-500 rounded mr-1"></div>
              {{ $t('banking.matched') || 'Matched' }}
            </div>
            <div class="flex items-center">
              <div class="w-3 h-3 bg-yellow-400 rounded mr-1"></div>
              {{ $t('banking.unmatched') || 'Unmatched' }}
            </div>
          </div>
        </div>

        <!-- Match Method Breakdown -->
        <div class="bg-white rounded-lg shadow p-5">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $t('banking.match_method_breakdown') || 'Match Method Breakdown' }}
          </h3>
          <div v-if="totalMatchMethods === 0" class="text-center py-8 text-gray-400">
            {{ $t('banking.no_matches_yet') || 'No matches yet' }}
          </div>
          <div v-else class="space-y-4">
            <div v-for="method in matchMethodItems" :key="method.key">
              <div class="flex items-center justify-between mb-1">
                <span class="text-sm font-medium text-gray-700">{{ method.label }}</span>
                <span class="text-sm text-gray-500">
                  {{ method.count }} ({{ method.percent }}%)
                </span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-3">
                <div
                  class="h-3 rounded-full transition-all duration-300"
                  :class="method.color"
                  :style="{ width: method.percent + '%' }"
                ></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Per-Bank Parse Accuracy Table -->
      <div class="mt-6 bg-white rounded-lg shadow">
        <div class="p-5">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $t('banking.per_bank_accuracy') || 'Per-Bank Parse Accuracy' }}
          </h3>
          <div v-if="Object.keys(analytics.parse_accuracy).length === 0" class="text-center py-8 text-gray-400">
            {{ $t('banking.no_bank_data') || 'No bank data available' }}
          </div>
          <table v-else class="min-w-full divide-y divide-gray-200">
            <thead>
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ $t('banking.bank_name') || 'Bank' }}
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ $t('banking.parse_accuracy') || 'Parse Accuracy' }}
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ $t('general.status') || 'Status' }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr
                v-for="(accuracy, bankName) in analytics.parse_accuracy"
                :key="bankName"
              >
                <td class="px-4 py-3 text-sm font-medium text-gray-900 capitalize">
                  {{ formatBankName(bankName) }}
                </td>
                <td class="px-4 py-3 text-sm">
                  <div class="flex items-center">
                    <div class="w-32 bg-gray-200 rounded-full h-2 mr-3">
                      <div
                        class="h-2 rounded-full"
                        :class="getAccuracyBarColor(accuracy)"
                        :style="{ width: (accuracy * 100) + '%' }"
                      ></div>
                    </div>
                    <span class="text-gray-700 font-medium">
                      {{ (accuracy * 100).toFixed(1) }}%
                    </span>
                  </div>
                </td>
                <td class="px-4 py-3 text-sm">
                  <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                    :class="getAccuracyBadgeColor(accuracy)"
                  >
                    {{ getAccuracyLabel(accuracy) }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Reconciliation Speed -->
      <div class="mt-6 bg-white rounded-lg shadow p-5">
        <h3 class="text-lg font-semibold text-gray-900 mb-2">
          {{ $t('banking.reconciliation_speed') || 'Reconciliation Speed' }}
        </h3>
        <p class="text-sm text-gray-500">
          {{ $t('banking.avg_time_to_reconcile') || 'Average time from import to match' }}:
          <span class="font-semibold text-gray-900">
            {{ formatDuration(analytics.avg_time_to_reconcile_seconds) }}
          </span>
        </p>
      </div>
    </template>

    <!-- Empty State -->
    <div v-else class="mt-6 text-center py-12 text-gray-400">
      <BaseIcon name="ChartBarIcon" class="h-12 w-12 mx-auto text-gray-300 mb-4" />
      <p>{{ $t('banking.no_analytics_data') || 'No analytics data available. Select a period and click Apply.' }}</p>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'
import axios from 'axios'

const { t } = useI18n()
const notificationStore = useNotificationStore()

// State
const isLoading = ref(false)
const analytics = ref(null)

const filters = ref({
  from: null,
  to: null,
})

// Computed
const autoMatchRateColor = computed(() => {
  if (!analytics.value) return 'text-gray-900'
  const rate = analytics.value.auto_match_rate
  if (rate >= 0.7) return 'text-green-600'
  if (rate >= 0.5) return 'text-yellow-600'
  return 'text-red-600'
})

const pendingColor = computed(() => {
  if (!analytics.value || analytics.value.total_transactions === 0) return 'text-gray-900'
  const pendingRate = analytics.value.pending / analytics.value.total_transactions
  if (pendingRate > 0.2) return 'text-red-600'
  return 'text-yellow-600'
})

const totalMatchMethods = computed(() => {
  if (!analytics.value) return 0
  const m = analytics.value.match_by_method
  return (m.amount || 0) + (m.reference || 0) + (m.customer || 0) + (m.rule || 0)
})

const matchMethodItems = computed(() => {
  if (!analytics.value) return []
  const m = analytics.value.match_by_method
  const total = totalMatchMethods.value
  if (total === 0) return []

  return [
    {
      key: 'amount',
      label: t('banking.match_by_amount') || 'Amount Match',
      count: m.amount || 0,
      percent: Math.round(((m.amount || 0) / total) * 100),
      color: 'bg-blue-500',
    },
    {
      key: 'reference',
      label: t('banking.match_by_reference') || 'Reference Match',
      count: m.reference || 0,
      percent: Math.round(((m.reference || 0) / total) * 100),
      color: 'bg-green-500',
    },
    {
      key: 'customer',
      label: t('banking.match_by_customer') || 'Customer Match',
      count: m.customer || 0,
      percent: Math.round(((m.customer || 0) / total) * 100),
      color: 'bg-purple-500',
    },
    {
      key: 'rule',
      label: t('banking.match_by_rule') || 'Rule-Based Match',
      count: m.rule || 0,
      percent: Math.round(((m.rule || 0) / total) * 100),
      color: 'bg-orange-500',
    },
  ]
})

// Methods
const fetchAnalytics = async () => {
  isLoading.value = true
  try {
    const params = {}
    if (filters.value.from) params.from = formatDateParam(filters.value.from)
    if (filters.value.to) params.to = formatDateParam(filters.value.to)

    const response = await axios.get('/banking/reconciliation/analytics', { params })
    analytics.value = response.data.data
  } catch (error) {
    console.error('Failed to fetch analytics:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('banking.failed_to_load_analytics') || 'Failed to load analytics data',
    })
  } finally {
    isLoading.value = false
  }
}

const refreshData = () => {
  fetchAnalytics()
}

const setQuickPeriod = (period) => {
  const now = new Date()
  if (period === 'this_month') {
    filters.value.from = new Date(now.getFullYear(), now.getMonth(), 1)
    filters.value.to = now
  } else if (period === 'last_month') {
    filters.value.from = new Date(now.getFullYear(), now.getMonth() - 1, 1)
    filters.value.to = new Date(now.getFullYear(), now.getMonth(), 0)
  } else if (period === 'last_30_days') {
    const thirtyDaysAgo = new Date(now)
    thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30)
    filters.value.from = thirtyDaysAgo
    filters.value.to = now
  }
  fetchAnalytics()
}

const formatDateParam = (date) => {
  if (!date) return null
  if (typeof date === 'string') return date
  const d = new Date(date)
  return d.getFullYear() + '-' +
    String(d.getMonth() + 1).padStart(2, '0') + '-' +
    String(d.getDate()).padStart(2, '0')
}

const formatPercent = (rate) => {
  if (rate === null || rate === undefined) return '0%'
  return (rate * 100).toFixed(1) + '%'
}

const formatMoney = (amount) => {
  if (amount === null || amount === undefined) return 'MKD 0.00'
  return new Intl.NumberFormat('mk-MK', {
    style: 'currency',
    currency: 'MKD',
  }).format(amount)
}

const formatShortDate = (dateStr) => {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return String(d.getDate()).padStart(2, '0') + '/' +
    String(d.getMonth() + 1).padStart(2, '0')
}

const formatDuration = (seconds) => {
  if (!seconds || seconds === 0) return t('banking.instant') || 'Instant'
  if (seconds < 60) return seconds + 's'
  if (seconds < 3600) return Math.round(seconds / 60) + ' min'
  const hours = Math.floor(seconds / 3600)
  const mins = Math.round((seconds % 3600) / 60)
  return hours + 'h ' + mins + 'm'
}

const formatBankName = (name) => {
  if (!name) return 'Unknown'
  return name.replace(/_/g, ' ')
}

const getTrendBarWidth = (value, day) => {
  const total = day.matched + day.unmatched
  if (total === 0) return 0
  return Math.round((value / total) * 100)
}

const getAccuracyBarColor = (accuracy) => {
  if (accuracy >= 0.95) return 'bg-green-500'
  if (accuracy >= 0.85) return 'bg-yellow-500'
  return 'bg-red-500'
}

const getAccuracyBadgeColor = (accuracy) => {
  if (accuracy >= 0.95) return 'bg-green-100 text-green-800'
  if (accuracy >= 0.85) return 'bg-yellow-100 text-yellow-800'
  return 'bg-red-100 text-red-800'
}

const getAccuracyLabel = (accuracy) => {
  if (accuracy >= 0.95) return t('banking.excellent') || 'Excellent'
  if (accuracy >= 0.85) return t('banking.good') || 'Good'
  return t('banking.needs_improvement') || 'Needs Improvement'
}

// Lifecycle
onMounted(() => {
  fetchAnalytics()
})
</script>

<!-- CLAUDE-CHECKPOINT -->
