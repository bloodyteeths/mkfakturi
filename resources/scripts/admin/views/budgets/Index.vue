<template>
  <BasePage>
    <BasePageHeader :title="t('title')">
      <template #actions>
        <div class="flex items-center space-x-3">
          <!-- Filters -->
          <select
            v-model="filterStatus"
            class="rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
          >
            <option value="">{{ t('status') }}: {{ t('all') }}</option>
            <option value="draft">{{ t('draft') }}</option>
            <option value="approved">{{ t('approved') }}</option>
            <option value="locked">{{ t('locked') }}</option>
            <option value="archived">{{ t('archived') }}</option>
          </select>

          <select
            v-model="filterYear"
            class="rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
          >
            <option value="">{{ t('period') }}: {{ t('all') }}</option>
            <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
          </select>

          <BaseButton variant="primary" @click="$router.push({ name: 'budgets.create' })">
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="PlusIcon" />
            </template>
            {{ t('create') }}
          </BaseButton>
        </div>
      </template>
    </BasePageHeader>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm font-medium text-gray-500">{{ t('total_budgets') }}</p>
        <p class="mt-1 text-2xl font-semibold text-gray-900">{{ budgets.length }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm font-medium text-gray-500">{{ t('active_budgets') }}</p>
        <p class="mt-1 text-2xl font-semibold text-green-600">{{ activeBudgetsCount }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm font-medium text-gray-500">{{ t('current_year_total') }}</p>
        <p class="mt-1 text-2xl font-semibold text-primary-600">{{ currentYearCount }}</p>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="bg-white rounded-lg shadow p-6">
      <div class="space-y-4 animate-pulse">
        <div v-for="i in 5" :key="i" class="flex items-center space-x-4">
          <div class="h-4 bg-gray-200 rounded flex-1"></div>
          <div class="h-4 bg-gray-200 rounded w-24"></div>
          <div class="h-4 bg-gray-200 rounded w-20"></div>
          <div class="h-4 bg-gray-200 rounded w-16"></div>
        </div>
      </div>
    </div>

    <!-- Budget List -->
    <div v-else-if="budgets.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('name') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('period') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('scenario') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('status') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('cost_center') }}</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('general.actions') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
          <tr
            v-for="budget in budgets"
            :key="budget.id"
            class="hover:bg-gray-50 cursor-pointer"
            @click="$router.push({ name: 'budgets.view', params: { id: budget.id } })"
          >
            <td class="px-4 py-3">
              <div class="text-sm font-medium text-gray-900">{{ budget.name }}</div>
              <div class="text-xs text-gray-500">{{ budget.lines_count }} {{ t('lines').toLowerCase() }}</div>
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">
              {{ formatDate(budget.start_date) }} - {{ formatDate(budget.end_date) }}
              <span class="ml-1 text-xs text-gray-400">({{ scenarioLabel(budget.period_type) }})</span>
            </td>
            <td class="whitespace-nowrap px-4 py-3">
              <span
                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                :class="scenarioBadgeClass(budget.scenario)"
              >
                {{ scenarioLabel(budget.scenario) }}
              </span>
            </td>
            <td class="whitespace-nowrap px-4 py-3">
              <span
                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                :class="statusBadgeClass(budget.status)"
              >
                {{ statusLabel(budget.status) }}
              </span>
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">
              <span v-if="budget.cost_center" class="flex items-center">
                <span
                  class="inline-block h-3 w-3 rounded-full mr-1.5"
                  :style="{ backgroundColor: budget.cost_center.color || '#6366f1' }"
                ></span>
                {{ budget.cost_center.name }}
              </span>
              <span v-else class="text-gray-400">-</span>
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
              <button
                v-if="budget.status === 'approved' || budget.status === 'locked'"
                class="text-primary-600 hover:text-primary-800 mr-2"
                @click.stop="$router.push({ name: 'budgets.view', params: { id: budget.id } })"
                :title="t('vs_actual')"
              >
                <BaseIcon name="ChartBarIcon" class="h-4 w-4" />
              </button>
              <button
                v-if="budget.status === 'draft'"
                class="text-red-600 hover:text-red-800"
                @click.stop="confirmDelete(budget)"
              >
                <BaseIcon name="TrashIcon" class="h-4 w-4" />
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="!isLoading"
      class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-white py-16"
    >
      <BaseIcon name="CalculatorIcon" class="h-12 w-12 text-gray-400" />
      <h3 class="mt-4 text-sm font-medium text-gray-900">{{ t('no_budgets') }}</h3>
      <p class="mt-1 text-sm text-gray-500">{{ t('empty_description') }}</p>
      <BaseButton variant="primary" class="mt-4" @click="$router.push({ name: 'budgets.create' })">
        <template #left="slotProps">
          <BaseIcon :class="slotProps.class" name="PlusIcon" />
        </template>
        {{ t('create') }}
      </BaseButton>
    </div>

  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useI18n } from 'vue-i18n'
import { debounce } from 'lodash'
import budgetMessages from '@/scripts/admin/i18n/budgets.js'

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

// State
const budgets = ref([])
const isLoading = ref(false)
const filterStatus = ref('')
const filterYear = ref('')
const deletingBudget = ref(null)

// Computed
const currentYear = new Date().getFullYear()
const yearOptions = computed(() => {
  const years = []
  for (let y = currentYear + 1; y >= currentYear - 3; y--) {
    years.push(y)
  }
  return years
})

const activeBudgetsCount = computed(() => {
  return budgets.value.filter(b => b.status === 'approved' || b.status === 'draft').length
})

const currentYearCount = computed(() => {
  return budgets.value.filter(b => {
    const startYear = new Date(b.start_date).getFullYear()
    const endYear = new Date(b.end_date).getFullYear()
    return startYear <= currentYear && endYear >= currentYear
  }).length
})

// Lifecycle
onMounted(() => {
  loadBudgets()
})

const debouncedLoad = debounce(() => {
  loadBudgets()
}, 300)

watch([filterStatus, filterYear], () => {
  debouncedLoad()
})

// Methods
async function loadBudgets() {
  isLoading.value = true
  try {
    const params = {}
    if (filterStatus.value) params.status = filterStatus.value
    if (filterYear.value) params.year = filterYear.value

    const response = await window.axios.get('/budgets', { params })
    budgets.value = response.data?.data || []
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('error_loading'),
    })
  } finally {
    isLoading.value = false
  }
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString(fmtLocale, { day: '2-digit', month: '2-digit', year: 'numeric' })
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
    monthly: t('period_monthly'),
    quarterly: t('period_quarterly'),
    yearly: t('period_yearly'),
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

function confirmDelete(budget) {
  deletingBudget.value = budget
  dialogStore
    .openDialog({
      title: $t('general.are_you_sure'),
      message: t('confirm_delete') || `Delete "${budget.name}"?`,
      yesLabel: $t('general.ok'),
      noLabel: $t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then(async (res) => {
      if (res) {
        try {
          await window.axios.delete(`/budgets/${deletingBudget.value.id}`)
          notificationStore.showNotification({
            type: 'success',
            message: t('deleted_success') || 'Deleted successfully',
          })
          deletingBudget.value = null
          await loadBudgets()
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
