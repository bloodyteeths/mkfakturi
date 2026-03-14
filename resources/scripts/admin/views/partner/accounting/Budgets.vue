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
      <!-- Filters -->
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-4">
          <BaseInputGroup :label="t('status')">
            <BaseMultiselect
              v-model="filters.status"
              :options="statusOptions"
              :searchable="false"
              label="label"
              value-prop="value"
              class="w-48"
            />
          </BaseInputGroup>
          <BaseInputGroup :label="t('scenario')">
            <BaseMultiselect
              v-model="filters.scenario"
              :options="scenarioOptions"
              :searchable="false"
              label="label"
              value-prop="value"
              class="w-52"
            />
          </BaseInputGroup>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="isLoading" class="flex items-center justify-center py-12">
        <svg class="animate-spin h-8 w-8 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
      </div>

      <!-- Budgets List -->
      <div v-else-if="budgets.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
        <p class="text-sm text-gray-500">{{ $t('general.no_data') }}</p>
      </div>

      <div v-if="!isLoading && budgets.length > 0" class="grid grid-cols-1 gap-4">
        <div
          v-for="budget in budgets"
          :key="budget.id"
          class="bg-white rounded-lg shadow p-4 hover:shadow-md transition-shadow cursor-pointer"
          @click="viewBudget(budget)"
        >
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-base font-medium text-gray-900">{{ budget.name }}</h3>
              <p class="text-sm text-gray-500 mt-1">
                {{ formatDate(budget.start_date) }} — {{ formatDate(budget.end_date) }}
                <span v-if="budget.cost_center" class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs bg-indigo-50 text-indigo-700">
                  {{ budget.cost_center.name }}
                </span>
              </p>
            </div>
            <div class="flex items-center gap-3">
              <span :class="scenarioBadge(budget.scenario)" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium">
                {{ t('scenario_' + budget.scenario) }}
              </span>
              <span :class="statusBadge(budget.status)" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium">
                {{ statusLabel(budget.status) }}
              </span>
            </div>
          </div>

          <!-- Budget vs Actual mini-bar (if comparison loaded) -->
          <div v-if="budget._summary" class="mt-3 grid grid-cols-3 gap-4">
            <div>
              <p class="text-xs text-gray-500">{{ t('budgeted') }}</p>
              <p class="text-sm font-medium">{{ formatNumber(budget._summary.total_budgeted) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">{{ t('actual') }}</p>
              <p class="text-sm font-medium">{{ formatNumber(budget._summary.total_actual) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">{{ t('variance') }}</p>
              <p :class="budget._summary.total_variance >= 0 ? 'text-green-600' : 'text-red-600'" class="text-sm font-medium">
                {{ formatNumber(budget._summary.total_variance) }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Budget Detail Modal -->
      <BaseModal :show="showDetail" @close="closeDetail" size="xl">
        <template #header>
          <h3 class="text-lg font-medium">{{ selectedBudget?.name }} — {{ t('vs_actual') }}</h3>
        </template>
        <div v-if="detailLoading" class="flex items-center justify-center py-12">
          <svg class="animate-spin h-8 w-8 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
        </div>
        <div v-else-if="budgetDetail">
          <!-- Status & Actions -->
          <div class="flex items-center justify-between mb-4">
            <span :class="statusBadge(selectedBudget?.status)" class="inline-flex items-center px-2.5 py-1 rounded text-sm font-medium">
              {{ statusLabel(selectedBudget?.status) }}
            </span>
            <div class="flex items-center gap-2">
              <BaseButton
                v-if="selectedBudget?.status === 'draft'"
                variant="primary"
                size="sm"
                @click="approveBudget(selectedBudget)"
              >
                {{ t('approve') }}
              </BaseButton>
              <BaseButton
                v-if="selectedBudget?.status === 'approved'"
                variant="primary-outline"
                size="sm"
                @click="lockBudget(selectedBudget)"
              >
                {{ t('lock') }}
              </BaseButton>
              <BaseButton
                v-if="selectedBudget?.status !== 'locked'"
                variant="danger"
                size="sm"
                @click="deleteBudget(selectedBudget)"
              >
                {{ t('delete') }}
              </BaseButton>
            </div>
          </div>

          <!-- Variance Summary -->
          <div v-if="budgetDetail.summary" class="mb-6">
            <div class="grid grid-cols-2 gap-4 mb-4">
              <div class="bg-green-50 rounded p-3">
                <p class="text-xs text-green-600 uppercase">{{ t('under_budget') }}</p>
                <div v-for="item in budgetDetail.summary.top_under_budget" :key="item.account_type" class="text-sm text-green-800">
                  {{ item.account_type_label }}: {{ formatNumber(Math.abs(item.variance)) }}
                </div>
                <p v-if="!budgetDetail.summary.top_under_budget?.length" class="text-sm text-green-600 italic">—</p>
              </div>
              <div class="bg-red-50 rounded p-3">
                <p class="text-xs text-red-600 uppercase">{{ t('over_budget') }}</p>
                <div v-for="item in budgetDetail.summary.top_over_budget" :key="item.account_type" class="text-sm text-red-800">
                  {{ item.account_type_label }}: {{ formatNumber(Math.abs(item.variance)) }}
                </div>
                <p v-if="!budgetDetail.summary.top_over_budget?.length" class="text-sm text-red-600 italic">—</p>
              </div>
            </div>
          </div>

          <!-- Comparison Table -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('account_type') }}</th>
                  <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('budget_amount') }}</th>
                  <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('actual_amount') }}</th>
                  <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('variance') }}</th>
                  <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">%</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="row in budgetDetail.comparison" :key="row.account_type + row.period_start" class="hover:bg-gray-50">
                  <td class="px-4 py-2 text-sm text-gray-900">{{ row.account_type_label || row.account_type }}</td>
                  <td class="px-4 py-2 text-sm text-right">{{ formatNumber(row.budgeted) }}</td>
                  <td class="px-4 py-2 text-sm text-right">{{ formatNumber(row.actual) }}</td>
                  <td class="px-4 py-2 text-sm text-right" :class="row.variance >= 0 ? 'text-green-600' : 'text-red-600'">
                    {{ formatNumber(row.variance) }}
                  </td>
                  <td class="px-4 py-2 text-sm text-right" :class="row.variance_pct >= 0 ? 'text-green-600' : 'text-red-600'">
                    {{ row.variance_pct?.toFixed(1) || '0.0' }}%
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div v-else class="text-center py-8 text-sm text-gray-500">
          {{ t('no_budget_lines') }}
        </div>
        <template #footer>
          <BaseButton variant="primary-outline" @click="closeDetail">{{ $t('general.close') }}</BaseButton>
        </template>
      </BaseModal>
    </template>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import axios from 'axios'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import budgetMessages from '@/scripts/admin/i18n/budgets.js'

const notificationStore = useNotificationStore()
const consoleStore = useConsoleStore()

const locale = document.documentElement.lang || 'mk'
const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }
const fmtLocale = localeMap[locale] || 'mk-MK'
function t(key) {
  return budgetMessages[locale]?.budgets?.[key]
    || budgetMessages['en']?.budgets?.[key]
    || key
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

const companies = computed(() => consoleStore.managedCompanies || [])
const selectedCompanyId = ref(null)
const budgets = ref([])
const isLoading = ref(false)
const showDetail = ref(false)
const detailLoading = ref(false)
const selectedBudget = ref(null)
const budgetDetail = ref(null)

const filters = reactive({ status: null, scenario: null })

const statusOptions = [
  { value: null, label: t('status_all') },
  { value: 'draft', label: t('status_draft') },
  { value: 'approved', label: t('status_approved') },
  { value: 'locked', label: t('status_locked') },
]

const scenarioOptions = [
  { value: null, label: t('scenario_all') },
  { value: 'expected', label: t('scenario_expected_label') },
  { value: 'optimistic', label: t('scenario_optimistic_label') },
  { value: 'pessimistic', label: t('scenario_pessimistic_label') },
]

function formatNumber(val) {
  if (val === null || val === undefined) return '0.00'
  return Number(val).toLocaleString(fmtLocale, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString(fmtLocale, { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function statusBadge(s) {
  const map = {
    draft: 'bg-gray-100 text-gray-800',
    approved: 'bg-green-100 text-green-800',
    locked: 'bg-blue-100 text-blue-800',
    archived: 'bg-gray-100 text-gray-500',
  }
  return map[s] || 'bg-gray-100 text-gray-800'
}

function scenarioBadge(s) {
  const map = {
    expected: 'bg-indigo-50 text-indigo-700',
    optimistic: 'bg-green-50 text-green-700',
    pessimistic: 'bg-red-50 text-red-700',
  }
  return map[s] || 'bg-gray-50 text-gray-700'
}

function partnerApi(path) {
  return `/partner/companies/${selectedCompanyId.value}/accounting/budgets${path}`
}

async function loadCompanies() {
  await consoleStore.fetchCompanies()
}

async function onCompanyChange() {
  if (!selectedCompanyId.value) return
  loadBudgets()
}

async function loadBudgets() {
  isLoading.value = true
  try {
    const params = {}
    if (filters.status) params.status = filters.status
    if (filters.scenario) params.scenario = filters.scenario
    const { data } = await axios.get(partnerApi(''), { params })
    budgets.value = data.data || []
  } catch (e) {
    notificationStore.showNotification({
      type: 'error',
      message: t('error_loading'),
    })
  } finally {
    isLoading.value = false
  }
}

async function viewBudget(budget) {
  selectedBudget.value = budget
  showDetail.value = true
  detailLoading.value = true
  budgetDetail.value = null
  try {
    const { data } = await axios.get(partnerApi(`/${budget.id}/vs-actual`))
    budgetDetail.value = data.data || null
    // Also store summary on the budget card for the mini-bar
    if (data.data?.summary) {
      budget._summary = data.data.summary
    }
  } catch (e) {
    notificationStore.showNotification({
      type: 'error',
      message: t('error_loading'),
    })
    budgetDetail.value = null
  } finally {
    detailLoading.value = false
  }
}

function closeDetail() {
  showDetail.value = false
  selectedBudget.value = null
  budgetDetail.value = null
}

async function approveBudget(budget) {
  if (!confirm(t('confirm_approve'))) return
  try {
    await axios.post(partnerApi(`/${budget.id}/approve`))
    notificationStore.showNotification({ type: 'success', message: t('approved') })
    closeDetail()
    loadBudgets()
  } catch (e) {
    notificationStore.showNotification({
      type: 'error',
      message: e.response?.data?.message || t('error_loading'),
    })
  }
}

async function lockBudget(budget) {
  if (!confirm(t('confirm_lock'))) return
  try {
    await axios.post(partnerApi(`/${budget.id}/lock`))
    notificationStore.showNotification({ type: 'success', message: t('locked') })
    closeDetail()
    loadBudgets()
  } catch (e) {
    notificationStore.showNotification({
      type: 'error',
      message: e.response?.data?.message || t('error_loading'),
    })
  }
}

async function deleteBudget(budget) {
  if (!confirm(t('confirm_delete'))) return
  try {
    await axios.delete(partnerApi(`/${budget.id}`))
    notificationStore.showNotification({ type: 'success', message: t('delete') })
    closeDetail()
    loadBudgets()
  } catch (e) {
    notificationStore.showNotification({
      type: 'error',
      message: e.response?.data?.message || t('error_loading'),
    })
  }
}

watch([() => filters.status, () => filters.scenario], () => {
  if (selectedCompanyId.value) loadBudgets()
})

onMounted(() => {
  loadCompanies()
})
</script>

// CLAUDE-CHECKPOINT
