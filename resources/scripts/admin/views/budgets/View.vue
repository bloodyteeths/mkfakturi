<template>
  <BasePage>
    <BasePageHeader :title="budget ? budget.name : t('view')">
      <template #actions>
        <div v-if="budget" class="flex items-center space-x-2">
          <span
            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium"
            :class="statusBadgeClass(budget.status)"
          >
            {{ statusLabel(budget.status) }}
          </span>

          <BaseButton
            v-if="budget.status === 'draft'"
            variant="primary"
            @click="confirmApprove"
          >
            {{ t('approve') }}
          </BaseButton>

          <BaseButton
            v-if="budget.status === 'approved'"
            variant="primary-outline"
            @click="confirmLock"
          >
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="LockClosedIcon" />
            </template>
            {{ t('lock') }}
          </BaseButton>

          <BaseButton variant="primary-outline" @click="$router.push({ name: 'budgets.index' })">
            {{ $t('general.back') }}
          </BaseButton>
        </div>
      </template>
    </BasePageHeader>

    <!-- Loading -->
    <div v-if="isLoading" class="bg-white rounded-lg shadow p-6">
      <div class="space-y-4 animate-pulse">
        <div class="h-6 bg-gray-200 rounded w-1/3"></div>
        <div class="h-4 bg-gray-200 rounded w-2/3"></div>
        <div class="space-y-2 mt-6">
          <div v-for="i in 8" :key="i" class="h-10 bg-gray-200 rounded"></div>
        </div>
      </div>
    </div>

    <template v-else-if="budget">
      <!-- Budget Info Card -->
      <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div>
            <p class="text-xs text-gray-500">{{ t('period') }}</p>
            <p class="text-sm font-medium text-gray-900">
              {{ formatDate(budget.start_date) }} - {{ formatDate(budget.end_date) }}
            </p>
          </div>
          <div>
            <p class="text-xs text-gray-500">{{ t('scenario') }}</p>
            <span
              class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
              :class="scenarioBadgeClass(budget.scenario)"
            >
              {{ scenarioLabel(budget.scenario) }}
            </span>
          </div>
          <div v-if="budget.cost_center">
            <p class="text-xs text-gray-500">{{ t('cost_center') }}</p>
            <span class="flex items-center text-sm">
              <span
                class="inline-block h-3 w-3 rounded-full mr-1.5"
                :style="{ backgroundColor: budget.cost_center.color || '#6366f1' }"
              ></span>
              {{ budget.cost_center.name }}
            </span>
          </div>
          <div v-if="budget.approved_by_user">
            <p class="text-xs text-gray-500">{{ t('approved') }}</p>
            <p class="text-sm text-gray-900">{{ budget.approved_by_user.name }}</p>
            <p class="text-xs text-gray-400">{{ formatDateTime(budget.approved_at) }}</p>
          </div>
        </div>
      </div>

      <!-- Budget vs Actual Summary (for approved/locked budgets) -->
      <div v-if="comparison && (budget.status === 'approved' || budget.status === 'locked')" class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500">{{ t('total_budgeted') }}</p>
            <p class="text-xl font-bold text-gray-900">{{ formatNumber(comparison.summary.total_budgeted) }}</p>
          </div>
          <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500">{{ t('total_actual') }}</p>
            <p class="text-xl font-bold text-gray-900">{{ formatNumber(comparison.summary.total_actual) }}</p>
          </div>
          <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500">{{ t('variance') }}</p>
            <p
              class="text-xl font-bold"
              :class="comparison.summary.total_variance >= 0 ? 'text-red-600' : 'text-green-600'"
            >
              {{ formatNumber(comparison.summary.total_variance) }}
            </p>
          </div>
          <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500">{{ t('variance_pct') }}</p>
            <p
              class="text-xl font-bold"
              :class="comparison.summary.total_variance_pct >= 0 ? 'text-red-600' : 'text-green-600'"
            >
              {{ comparison.summary.total_variance_pct }}%
            </p>
          </div>
        </div>
      </div>

      <!-- Budget Lines Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
          <h3 class="text-sm font-medium text-gray-700">{{ t('lines') }} ({{ budget.lines?.length || 0 }})</h3>
        </div>

        <div v-if="budget.lines && budget.lines.length > 0" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('account_type') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('period') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('budgeted') }}</th>
                <th v-if="comparison" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('actual') }}</th>
                <th v-if="comparison" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('variance') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('notes') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
              <tr v-for="line in sortedLines" :key="line.id" class="hover:bg-gray-50">
                <td class="px-4 py-2 text-sm text-gray-900">{{ accountTypeLabel(line.account_type) }}</td>
                <td class="whitespace-nowrap px-4 py-2 text-sm text-gray-600">
                  {{ formatDate(line.period_start) }} - {{ formatDate(line.period_end) }}
                </td>
                <td class="whitespace-nowrap px-4 py-2 text-sm text-right font-medium text-gray-900">
                  {{ formatNumber(line.amount) }}
                </td>
                <td v-if="comparison" class="whitespace-nowrap px-4 py-2 text-sm text-right">
                  {{ formatNumber(getActualForLine(line)) }}
                </td>
                <td v-if="comparison" class="whitespace-nowrap px-4 py-2 text-sm text-right font-medium">
                  <span :class="getVarianceClass(line)">
                    {{ formatNumber(getVarianceForLine(line)) }}
                  </span>
                </td>
                <td class="px-4 py-2 text-sm text-gray-500 max-w-xs truncate">{{ line.notes || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="px-6 py-8 text-center text-sm text-gray-500">
          {{ t('no_budget_lines') }}
        </div>
      </div>

      <!-- Bar Charts (Budget vs Actual) -->
      <div v-if="comparison && comparison.comparison.length > 0" class="bg-white rounded-lg shadow p-6 mt-6">
        <h3 class="text-sm font-medium text-gray-700 mb-4">{{ t('vs_actual') }}</h3>
        <div class="space-y-3">
          <div v-for="row in comparison.comparison" :key="row.account_type + row.period_start" class="flex items-center">
            <div class="w-40 text-xs text-gray-600 truncate flex-shrink-0">{{ row.account_type_label }}</div>
            <div class="flex-1 flex items-center space-x-2">
              <!-- Budgeted bar -->
              <div class="flex-1 h-5 bg-gray-100 rounded-full overflow-hidden relative">
                <div
                  class="h-full bg-blue-500 rounded-full"
                  :style="{ width: barWidth(row.budgeted, row) + '%' }"
                ></div>
              </div>
              <!-- Actual bar -->
              <div class="flex-1 h-5 bg-gray-100 rounded-full overflow-hidden relative">
                <div
                  class="h-full rounded-full"
                  :class="row.variance > 0 ? 'bg-red-500' : 'bg-green-500'"
                  :style="{ width: barWidth(row.actual, row) + '%' }"
                ></div>
              </div>
            </div>
            <div class="w-24 text-right text-xs font-medium" :class="row.variance > 0 ? 'text-red-600' : 'text-green-600'">
              {{ row.variance_pct > 0 ? '+' : '' }}{{ row.variance_pct }}%
            </div>
          </div>
        </div>
        <div class="flex items-center space-x-4 mt-4 text-xs text-gray-500">
          <span class="flex items-center"><span class="h-3 w-3 bg-blue-500 rounded-full mr-1"></span> {{ t('budgeted') }}</span>
          <span class="flex items-center"><span class="h-3 w-3 bg-green-500 rounded-full mr-1"></span> {{ t('under_budget') }}</span>
          <span class="flex items-center"><span class="h-3 w-3 bg-red-500 rounded-full mr-1"></span> {{ t('over_budget') }}</span>
        </div>
      </div>
    </template>

  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useI18n } from 'vue-i18n'
import budgetMessages from '@/scripts/admin/i18n/budgets.js'

const route = useRoute()
const router = useRouter()
const notificationStore = useNotificationStore()
const dialogStore = useDialogStore()
const { t: $t } = useI18n()

const locale = document.documentElement.lang || 'mk'
const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }
const fmtLocale = localeMap[locale] || 'mk-MK'
function t(key) {
  return budgetMessages[locale]?.budgets?.[key]
    || budgetMessages['en']?.budgets?.[key]
    || key
}

function accountTypeLabel(type) {
  const typeKey = 'type_' + type.toLowerCase()
  const translated = t(typeKey)
  return translated !== typeKey ? translated : type
}

// State
const budget = ref(null)
const comparison = ref(null)
const isLoading = ref(false)

// Computed
const sortedLines = computed(() => {
  if (!budget.value?.lines) return []
  return [...budget.value.lines].sort((a, b) => {
    const periodCmp = a.period_start.localeCompare(b.period_start)
    if (periodCmp !== 0) return periodCmp
    return a.account_type.localeCompare(b.account_type)
  })
})

// Lifecycle
onMounted(async () => {
  await loadBudget()
})

// Methods
async function loadBudget() {
  isLoading.value = true
  try {
    const id = route.params.id
    const response = await window.axios.get(`/budgets/${id}`)
    budget.value = response.data?.data

    // Load comparison for approved/locked budgets
    if (budget.value && (budget.value.status === 'approved' || budget.value.status === 'locked')) {
      try {
        const compResponse = await window.axios.get(`/budgets/${id}/vs-actual`)
        comparison.value = compResponse.data?.data
      } catch {
        // Comparison might fail if no actuals exist
        comparison.value = null
      }
    }
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('error_loading'),
    })
    router.push({ name: 'budgets.index' })
  } finally {
    isLoading.value = false
  }
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString(fmtLocale, { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function formatDateTime(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleString(fmtLocale, { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function formatNumber(val) {
  return Number(val || 0).toLocaleString(fmtLocale, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function statusLabel(status) {
  const labels = {
    draft: t('draft'),
    approved: t('approved'),
    locked: t('locked'),
    archived: t('archived'),
  }
  return labels[status] || status
}

function statusBadgeClass(status) {
  const classes = {
    draft: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    locked: 'bg-blue-100 text-blue-800',
    archived: 'bg-gray-100 text-gray-600',
  }
  return classes[status] || 'bg-gray-100 text-gray-600'
}

function scenarioLabel(scenario) {
  const labels = {
    expected: t('scenario_expected'),
    optimistic: t('scenario_optimistic'),
    pessimistic: t('scenario_pessimistic'),
  }
  return labels[scenario] || scenario
}

function scenarioBadgeClass(scenario) {
  const classes = {
    expected: 'bg-blue-100 text-blue-800',
    optimistic: 'bg-green-100 text-green-800',
    pessimistic: 'bg-red-100 text-red-800',
  }
  return classes[scenario] || 'bg-gray-100 text-gray-600'
}

function getActualForLine(line) {
  if (!comparison.value?.comparison) return 0
  const match = comparison.value.comparison.find(
    c => c.account_type === line.account_type && c.period_start === line.period_start
  )
  return match?.actual ?? 0
}

function getVarianceForLine(line) {
  if (!comparison.value?.comparison) return 0
  const match = comparison.value.comparison.find(
    c => c.account_type === line.account_type && c.period_start === line.period_start
  )
  return match?.variance ?? 0
}

function getVarianceClass(line) {
  const variance = getVarianceForLine(line)
  if (variance > 0) return 'text-red-600'
  if (variance < 0) return 'text-green-600'
  return 'text-gray-500'
}

function barWidth(value, row) {
  const maxVal = Math.max(Math.abs(row.budgeted), Math.abs(row.actual), 1)
  return Math.min(100, (Math.abs(value) / maxVal) * 100)
}

function confirmApprove() {
  dialogStore
    .openDialog({
      title: t('approve'),
      message: t('confirm_approve') || 'Are you sure you want to approve this budget?',
      yesLabel: $t('general.ok'),
      noLabel: $t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'lg',
    })
    .then(async (res) => {
      if (res) {
        try {
          await window.axios.post(`/budgets/${budget.value.id}/approve`)
          notificationStore.showNotification({
            type: 'success',
            message: t('approved'),
          })
          await loadBudget()
        } catch (error) {
          notificationStore.showNotification({
            type: 'error',
            message: error.response?.data?.error || t('error_loading'),
          })
        }
      }
    })
}

function confirmLock() {
  dialogStore
    .openDialog({
      title: t('lock'),
      message: t('confirm_lock') || 'Are you sure you want to lock this budget?',
      yesLabel: $t('general.ok'),
      noLabel: $t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'lg',
    })
    .then(async (res) => {
      if (res) {
        try {
          await window.axios.post(`/budgets/${budget.value.id}/lock`)
          notificationStore.showNotification({
            type: 'success',
            message: t('locked'),
          })
          await loadBudget()
        } catch (error) {
          notificationStore.showNotification({
            type: 'error',
            message: error.response?.data?.error || t('error_loading'),
          })
        }
      }
    })
}
</script>

<!-- CLAUDE-CHECKPOINT -->
