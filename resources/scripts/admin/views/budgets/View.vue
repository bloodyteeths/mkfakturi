<template>
  <BasePage>
    <BasePageHeader :title="budget ? budget.name : t('view')">
      <template #actions>
        <div v-if="budget" class="flex items-center space-x-3">
          <!-- Status badge -->
          <span
            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium"
            :class="statusBadgeClass(budget.status)"
          >
            {{ statusLabel(budget.status) }}
          </span>

          <!-- Primary action: next status transition -->
          <BaseButton
            v-if="budget.status === 'draft'"
            variant="primary"
            @click="confirmApprove"
          >
            {{ t('approve') }}
          </BaseButton>

          <BaseButton
            v-if="budget.status === 'approved'"
            variant="primary"
            @click="confirmLock"
          >
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="LockClosedIcon" />
            </template>
            {{ t('lock') }}
          </BaseButton>

          <!-- More dropdown -->
          <div class="relative">
            <BaseButton
              variant="primary-outline"
              @click="showMoreMenu = !showMoreMenu"
            >
              <template #left="slotProps">
                <BaseIcon :class="slotProps.class" name="EllipsisVerticalIcon" />
              </template>
              {{ t('more') || 'Повеќе' }}
            </BaseButton>

            <!-- Backdrop to close menu on outside click -->
            <div
              v-show="showMoreMenu"
              class="fixed inset-0 z-10"
              @click="showMoreMenu = false"
            ></div>

            <!-- Dropdown menu -->
            <div
              v-show="showMoreMenu"
              class="absolute right-0 mt-1 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-20"
            >
              <!-- Edit (draft only) -->
              <button
                v-if="budget.status === 'draft'"
                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center"
                @click="showMoreMenu = false; $router.push({ name: 'budgets.edit', params: { id: budget.id } })"
              >
                <BaseIcon class="h-4 w-4 mr-2 text-gray-400" name="PencilSquareIcon" />
                {{ t('edit') }}
              </button>

              <!-- Clone (always) -->
              <button
                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center"
                @click="showMoreMenu = false; cloneBudget()"
              >
                <BaseIcon class="h-4 w-4 mr-2 text-gray-400" name="DocumentDuplicateIcon" />
                {{ t('clone') || 'Клонирај' }}
              </button>

              <div class="border-t border-gray-100 my-1"></div>

              <!-- Export CSV -->
              <button
                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center"
                @click="showMoreMenu = false; exportCsv()"
              >
                <BaseIcon class="h-4 w-4 mr-2 text-gray-400" name="TableCellsIcon" />
                CSV
              </button>

              <!-- Export PDF -->
              <button
                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center"
                @click="showMoreMenu = false; exportPdf()"
              >
                <BaseIcon class="h-4 w-4 mr-2 text-gray-400" name="DocumentTextIcon" />
                PDF
              </button>

              <!-- Export Comparison PDF (only if comparison exists) -->
              <button
                v-if="comparison && (budget.status === 'approved' || budget.status === 'locked')"
                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center"
                @click="showMoreMenu = false; exportComparisonPdf()"
              >
                <BaseIcon class="h-4 w-4 mr-2 text-gray-400" name="ChartBarIcon" />
                {{ t('export_comparison_pdf') }}
              </button>

              <!-- Archive (approved/locked) -->
              <template v-if="budget.status === 'approved' || budget.status === 'locked'">
                <div class="border-t border-gray-100 my-1"></div>
                <button
                  class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center"
                  @click="showMoreMenu = false; confirmArchive()"
                >
                  <BaseIcon class="h-4 w-4 mr-2 text-gray-400" name="ArchiveBoxIcon" />
                  {{ t('archive') || 'Архивирај' }}
                </button>
              </template>

              <!-- Delete (draft only, danger) -->
              <template v-if="budget.status === 'draft'">
                <div class="border-t border-gray-100 my-1"></div>
                <button
                  class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center"
                  @click="showMoreMenu = false; confirmDelete()"
                >
                  <BaseIcon class="h-4 w-4 mr-2 text-red-400" name="TrashIcon" />
                  {{ t('delete') }}
                </button>
              </template>
            </div>
          </div>

          <!-- Back button -->
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
      <!-- Status Timeline Breadcrumb -->
      <div class="bg-white rounded-lg shadow px-6 py-4 mb-6">
        <div class="flex items-center justify-between max-w-lg mx-auto">
          <template v-for="(step, idx) in statusSteps" :key="step.key">
            <!-- Step circle + label -->
            <div class="flex flex-col items-center">
              <div
                class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium border-2 transition-colors"
                :class="stepCircleClass(step.key)"
              >
                <!-- Completed: green checkmark -->
                <svg
                  v-if="isStepCompleted(step.key)"
                  class="w-4 h-4 text-white"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="3"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <span v-else>{{ idx + 1 }}</span>
              </div>
              <span
                class="text-xs mt-1 font-medium"
                :class="isStepCurrent(step.key) ? 'text-primary-500' : isStepCompleted(step.key) ? 'text-green-600' : 'text-gray-400'"
              >
                {{ step.label }}
              </span>
            </div>
            <!-- Connecting line -->
            <div
              v-if="idx < statusSteps.length - 1"
              class="flex-1 h-0.5 mx-2 mb-5"
              :class="isStepCompleted(statusSteps[idx + 1].key) || isStepCurrent(statusSteps[idx + 1].key) ? 'bg-green-400' : 'bg-gray-200'"
            ></div>
          </template>
        </div>
      </div>

      <!-- Budget Info Card -->
      <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div v-if="budget.number">
            <p class="text-xs text-gray-500">{{ t('budget_number') }}</p>
            <p class="text-sm font-medium text-gray-900">{{ budget.number }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">{{ t('period') }}</p>
            <p class="text-sm font-medium text-gray-900">
              {{ formatDate(budget.start_date) }} - {{ formatDate(budget.end_date) }}
            </p>
          </div>
          <div>
            <p class="text-xs text-gray-500">{{ t('period_type') }}</p>
            <p class="text-sm font-medium text-gray-900">{{ periodTypeLabel(budget.period_type) }}</p>
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
                  :class="barVarianceClass(row)"
                  :style="{ width: barWidth(row.actual, row) + '%' }"
                ></div>
              </div>
            </div>
            <div class="w-24 text-right text-xs font-medium" :class="barVarianceTextClass(row)">
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

function periodTypeLabel(periodType) {
  const labels = {
    monthly: t('period_monthly'),
    quarterly: t('period_quarterly'),
    yearly: t('period_yearly'),
  }
  return labels[periodType] || periodType
}

// State
const budget = ref(null)
const comparison = ref(null)
const isLoading = ref(false)
const showMoreMenu = ref(false)

// Status timeline steps
const statusSteps = [
  { key: 'draft', label: t('draft') },
  { key: 'approved', label: t('approved') },
  { key: 'locked', label: t('locked') },
  { key: 'archived', label: t('archived') },
]

const statusOrder = ['draft', 'approved', 'locked', 'archived']

function isStepCompleted(stepKey) {
  if (!budget.value) return false
  const currentIdx = statusOrder.indexOf(budget.value.status)
  const stepIdx = statusOrder.indexOf(stepKey)
  return stepIdx < currentIdx
}

function isStepCurrent(stepKey) {
  if (!budget.value) return false
  return budget.value.status === stepKey
}

function stepCircleClass(stepKey) {
  if (isStepCompleted(stepKey)) {
    return 'bg-green-500 border-green-500 text-white'
  }
  if (isStepCurrent(stepKey)) {
    return 'bg-primary-500 border-primary-500 text-white'
  }
  return 'bg-white border-gray-300 text-gray-400'
}

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
  if (variance === 0) return 'text-gray-500'
  const isRevenue = line.account_type.includes('REVENUE')
  // For revenue: positive variance (earned more than budgeted) = green
  // For expenses: positive variance (spent more than budgeted) = red
  if (isRevenue) return variance > 0 ? 'text-green-600' : 'text-red-600'
  return variance > 0 ? 'text-red-600' : 'text-green-600'
}

function barVarianceClass(row) {
  const isRevenue = row.account_type?.includes('REVENUE')
  const isPositive = row.variance > 0
  // Revenue: positive variance = good (green), Expenses: positive variance = bad (red)
  if (isRevenue) return isPositive ? 'bg-green-500' : 'bg-red-500'
  return isPositive ? 'bg-red-500' : 'bg-green-500'
}

function barVarianceTextClass(row) {
  const isRevenue = row.account_type?.includes('REVENUE')
  const isPositive = row.variance > 0
  if (isRevenue) return isPositive ? 'text-green-600' : 'text-red-600'
  return isPositive ? 'text-red-600' : 'text-green-600'
}

function barWidth(value, row) {
  const maxVal = Math.max(Math.abs(row.budgeted), Math.abs(row.actual), 1)
  return Math.min(100, (Math.abs(value) / maxVal) * 100)
}

function confirmDelete() {
  dialogStore
    .openDialog({
      title: t('delete'),
      message: t('confirm_delete') || 'Are you sure you want to delete this budget?',
      yesLabel: $t('general.ok'),
      noLabel: $t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then(async (res) => {
      if (res) {
        try {
          await window.axios.delete(`/budgets/${budget.value.id}`)
          notificationStore.showNotification({
            type: 'success',
            message: t('deleted_success'),
          })
          router.push({ name: 'budgets.index' })
        } catch (error) {
          notificationStore.showNotification({
            type: 'error',
            message: error.response?.data?.error || t('error_loading'),
          })
        }
      }
    })
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

function exportCsv() {
  const baseURL = window.axios.defaults.baseURL || '/api/v1'
  window.open(`${baseURL}/budgets/${budget.value.id}/export-csv`, '_blank')
}

function exportPdf() {
  const baseURL = window.axios.defaults.baseURL || '/api/v1'
  window.open(`${baseURL}/budgets/${budget.value.id}/export-pdf`, '_blank')
}

function exportComparisonPdf() {
  const baseURL = window.axios.defaults.baseURL || '/api/v1'
  window.open(`${baseURL}/budgets/${budget.value.id}/export-comparison-pdf`, '_blank')
}

function confirmArchive() {
  dialogStore
    .openDialog({
      title: t('archive') || 'Архивирај',
      message: t('confirm_archive') || 'Are you sure you want to archive this budget?',
      yesLabel: $t('general.ok'),
      noLabel: $t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'lg',
    })
    .then(async (res) => {
      if (res) {
        try {
          await window.axios.post(`/budgets/${budget.value.id}/archive`)
          notificationStore.showNotification({
            type: 'success',
            message: t('archived') || 'Budget archived',
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

async function cloneBudget() {
  try {
    const response = await window.axios.post(`/budgets/${budget.value.id}/clone`)
    const newId = response.data?.data?.id
    if (newId) {
      notificationStore.showNotification({
        type: 'success',
        message: t('clone_success') || 'Budget cloned successfully',
      })
      router.push({ name: 'budgets.view', params: { id: newId } })
    }
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('error_loading'),
    })
  }
}
</script>

<!-- CLAUDE-CHECKPOINT -->
