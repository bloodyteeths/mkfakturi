<template>
  <BasePage>
    <BasePageHeader :title="t('title')" />

    <!-- Intro -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
      <div class="flex gap-3">
        <svg class="h-5 w-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
        </svg>
        <p class="text-sm text-blue-700">{{ t('page_intro') }}</p>
      </div>
    </div>

    <!-- Company Selector -->
    <div class="mb-6">
      <BaseInputGroup :label="$t('partner.select_company')">
        <BaseMultiselect
          v-model="selectedCompanyId"
          :options="companies"
          :searchable="true"
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
      <!-- Create New Report (inline, collapsible) -->
      <div class="mb-6">
        <button
          v-if="!showCreateForm"
          @click="showCreateForm = true"
          class="flex items-center gap-2 text-sm font-medium text-primary-600 hover:text-primary-800"
        >
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
          </svg>
          {{ t('new_report') }}
        </button>

        <!-- Create Form -->
        <div v-if="showCreateForm" class="bg-white rounded-lg shadow p-6 space-y-6">
          <div class="flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-900">{{ t('new_report') }}</h3>
            <button @click="showCreateForm = false" class="text-gray-400 hover:text-gray-600">
              <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Name -->
          <div>
            <BaseInputGroup :label="t('name')" :help-text="t('help_name')" required>
              <BaseInput v-model="form.name" :placeholder="t('name')" />
            </BaseInputGroup>
          </div>

          <!-- Account Filter -->
          <div>
            <label class="text-sm font-medium text-gray-700 block mb-1">{{ t('account_filter') }}</label>
            <p class="text-xs text-gray-500 mb-3">{{ t('help_filter') }}</p>

            <div class="flex flex-wrap gap-2 mb-3">
              <button
                v-for="opt in filterTypeOptions"
                :key="opt.value"
                @click="form.account_filter.type = opt.value"
                class="px-3 py-1.5 rounded-full text-xs font-medium border transition-colors"
                :class="form.account_filter.type === opt.value ? 'bg-primary-100 border-primary-500 text-primary-700' : 'bg-white border-gray-300 text-gray-600 hover:border-gray-400'"
              >
                {{ opt.label }}
              </button>
            </div>

            <!-- Range Inputs -->
            <div v-if="form.account_filter.type === 'range'" class="grid grid-cols-2 gap-4 max-w-sm">
              <p class="col-span-2 text-xs text-gray-500">{{ t('help_filter_range') }}</p>
              <BaseInputGroup :label="t('range_from')">
                <BaseInput v-model="form.account_filter.from" placeholder="1000" />
              </BaseInputGroup>
              <BaseInputGroup :label="t('range_to')">
                <BaseInput v-model="form.account_filter.to" placeholder="1999" />
              </BaseInputGroup>
            </div>

            <!-- Category Selection -->
            <div v-if="form.account_filter.type === 'category'">
              <p class="text-xs text-gray-500 mb-2">{{ t('help_filter_category') }}</p>
              <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                <label
                  v-for="cat in accountCategories"
                  :key="cat.value"
                  class="flex items-center p-2 rounded border cursor-pointer hover:bg-gray-50 text-sm"
                  :class="form.account_filter.categories?.includes(cat.value) ? 'border-primary-500 bg-primary-50' : 'border-gray-200'"
                >
                  <input
                    type="checkbox"
                    :value="cat.value"
                    v-model="form.account_filter.categories"
                    class="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                  />
                  <span class="ml-2">{{ cat.label }}</span>
                </label>
              </div>
            </div>

            <!-- Specific Codes -->
            <div v-if="form.account_filter.type === 'specific'" class="max-w-sm">
              <p class="text-xs text-gray-500 mb-2">{{ t('help_filter_specific') }}</p>
              <BaseInput v-model="codesInput" :placeholder="t('codes_placeholder')" />
            </div>
          </div>

          <!-- Columns -->
          <div>
            <label class="text-sm font-medium text-gray-700 block mb-1">{{ t('columns') }}</label>
            <p class="text-xs text-gray-500 mb-2">{{ t('help_columns') }}</p>
            <div class="flex flex-wrap gap-2">
              <label
                v-for="col in columnOptions"
                :key="col.value"
                class="flex items-center px-3 py-1.5 rounded-lg border cursor-pointer hover:bg-gray-50 text-sm"
                :class="form.columns.includes(col.value) ? 'border-primary-500 bg-primary-50' : 'border-gray-200'"
              >
                <input
                  type="checkbox"
                  :value="col.value"
                  v-model="form.columns"
                  class="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                />
                <span class="ml-2">{{ col.label }}</span>
              </label>
            </div>
          </div>

          <!-- Period, Grouping, Comparison — single row -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <BaseInputGroup :label="t('period')" :help-text="t('help_period')">
                <select v-model="form.period_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                  <option value="month">{{ t('monthly') }}</option>
                  <option value="quarter">{{ t('quarterly') }}</option>
                  <option value="year">{{ t('yearly') }}</option>
                  <option value="custom">{{ t('custom_period') }}</option>
                </select>
              </BaseInputGroup>
            </div>
            <div>
              <BaseInputGroup :label="t('group_by')" :help-text="t('help_group')">
                <select v-model="form.group_by" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                  <option value="">{{ t('no_grouping') }}</option>
                  <option value="month">{{ t('by_month') }}</option>
                  <option value="quarter">{{ t('by_quarter') }}</option>
                  <option value="cost_center">{{ t('by_cost_center') }}</option>
                </select>
              </BaseInputGroup>
            </div>
            <div>
              <BaseInputGroup :label="t('comparison')" :help-text="t('help_comparison')">
                <select v-model="form.comparison" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                  <option value="">{{ t('no_comparison') }}</option>
                  <option value="previous_year">{{ t('previous_year') }}</option>
                  <option value="budget">{{ t('budget_comparison') }}</option>
                </select>
              </BaseInputGroup>
            </div>
          </div>

          <!-- Custom date range -->
          <div v-if="form.period_type === 'custom'" class="grid grid-cols-2 gap-4 max-w-sm">
            <BaseInputGroup :label="t('date_from')">
              <BaseInput v-model="form.date_from" type="date" />
            </BaseInputGroup>
            <BaseInputGroup :label="t('date_to')">
              <BaseInput v-model="form.date_to" type="date" />
            </BaseInputGroup>
          </div>

          <!-- Schedule (optional) -->
          <div class="border-t border-gray-100 pt-4">
            <label class="text-sm font-medium text-gray-700 block mb-1">{{ t('schedule_frequency') }}</label>
            <p class="text-xs text-gray-500 mb-2">{{ t('help_schedule') }}</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-lg">
              <select v-model="scheduleChoice" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                <option value="">{{ t('schedule_none') }}</option>
                <option value="0 8 * * *">{{ t('schedule_daily') }}</option>
                <option value="0 8 * * 1">{{ t('schedule_weekly') }}</option>
                <option value="0 8 1 * *">{{ t('schedule_monthly') }}</option>
                <option value="0 8 1 1,4,7,10 *">{{ t('schedule_quarterly') }}</option>
              </select>
              <BaseInputGroup v-if="scheduleChoice" :label="t('email_recipients')">
                <BaseInput v-model="emailInput" placeholder="cfo@company.mk" />
                <p class="text-xs text-gray-400 mt-1">{{ t('email_hint') }}</p>
              </BaseInputGroup>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-between border-t border-gray-100 pt-4">
            <div class="flex gap-2">
              <BaseButton variant="primary" @click="saveReport" :disabled="isSaving || !form.name || form.columns.length === 0">
                {{ isSaving ? t('creating') : t('save_template') }}
              </BaseButton>
              <BaseButton variant="primary-outline" @click="previewReport" :disabled="isPreviewing || form.columns.length === 0">
                {{ isPreviewing ? t('loading') : t('preview') }}
              </BaseButton>
            </div>
            <BaseButton variant="primary-outline" @click="showCreateForm = false">
              {{ t('cancel_create') }}
            </BaseButton>
          </div>
        </div>
      </div>

      <!-- Preview Results (from create form) -->
      <ReportTable
        v-if="previewData && showCreateForm"
        :data="previewData"
        :title="form.name || t('preview')"
        :t="t"
        :fmtLocale="fmtLocale"
        class="mb-6"
      />

      <!-- Loading Skeleton -->
      <div v-if="isLoading" class="bg-white rounded-lg shadow p-6">
        <div class="space-y-4 animate-pulse">
          <div v-for="i in 4" :key="i" class="flex items-center space-x-4">
            <div class="h-4 bg-gray-200 rounded flex-1"></div>
            <div class="h-4 bg-gray-200 rounded w-24"></div>
            <div class="h-4 bg-gray-200 rounded w-20"></div>
          </div>
        </div>
      </div>

      <!-- Saved Reports -->
      <template v-if="!isLoading">
        <h3 v-if="templates.length > 0" class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">
          {{ t('saved_reports') }}
        </h3>

        <div v-if="templates.length === 0 && !showCreateForm" class="text-center py-12 bg-white rounded-lg shadow">
          <BaseIcon name="DocumentChartBarIcon" class="h-12 w-12 text-gray-400 mx-auto" />
          <p class="text-sm text-gray-500 mt-4">{{ t('no_templates') }}</p>
          <p class="text-xs text-gray-400 mt-1">{{ t('no_templates_desc') }}</p>
          <BaseButton variant="primary" class="mt-4" @click="showCreateForm = true">
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="PlusIcon" />
            </template>
            {{ t('new_report') }}
          </BaseButton>
        </div>

        <!-- Template Cards -->
        <div v-if="templates.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
          <div
            v-for="tpl in templates"
            :key="tpl.id"
            class="bg-white rounded-lg shadow hover:shadow-md transition-shadow cursor-pointer"
            :class="selectedTemplate?.id === tpl.id ? 'ring-2 ring-primary-500' : ''"
            @click="selectTemplate(tpl)"
          >
            <div class="p-4">
              <div class="flex items-center justify-between">
                <div class="flex-1 min-w-0">
                  <h3 class="text-base font-medium text-gray-900 truncate">{{ tpl.name }}</h3>
                  <p class="text-xs text-gray-500 mt-1">
                    {{ periodLabel(tpl.period_type) }}
                    <span v-if="tpl.group_by" class="ml-1 text-gray-400">/ {{ groupByLabel(tpl.group_by) }}</span>
                  </p>
                </div>
                <div class="flex items-center gap-2">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-blue-50 text-blue-700">
                    {{ filterLabel(tpl.account_filter) }}
                  </span>
                  <span v-if="tpl.comparison" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-purple-50 text-purple-700">
                    {{ comparisonLabel(tpl.comparison) }}
                  </span>
                  <button
                    class="text-gray-400 hover:text-red-500 p-1"
                    @click.stop="confirmDelete(tpl)"
                    :title="$t('general.delete')"
                  >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Execute Panel (for selected template) -->
        <div v-if="selectedTemplate" class="bg-white rounded-lg shadow mb-6 p-4">
          <div class="flex flex-wrap items-end gap-4">
            <h3 class="text-sm font-medium text-gray-700 mr-auto">{{ selectedTemplate.name }}</h3>
            <BaseInputGroup :label="t('date_from')">
              <BaseInput v-model="dateFrom" type="date" class="w-36" />
            </BaseInputGroup>
            <BaseInputGroup :label="t('date_to')">
              <BaseInput v-model="dateTo" type="date" class="w-36" />
            </BaseInputGroup>
            <BaseButton variant="primary" size="sm" @click="executeReport" :disabled="isExecuting">
              {{ isExecuting ? t('loading') : t('execute') }}
            </BaseButton>
            <BaseButton v-if="reportData" variant="primary-outline" size="sm" @click="exportCsv">
              {{ t('export_excel') }}
            </BaseButton>
          </div>
        </div>

        <!-- Report Results -->
        <ReportTable
          v-if="reportData && selectedTemplate"
          :data="reportData"
          :title="selectedTemplate.name"
          :t="t"
          :fmtLocale="fmtLocale"
        />

        <!-- Comparison Results -->
        <div v-if="reportData && reportData.comparison_rows && reportData.comparison_rows.length > 0" class="bg-white rounded-lg shadow overflow-hidden mt-6">
          <div class="px-6 py-4 bg-purple-50 border-b border-purple-100">
            <h3 class="text-sm font-medium text-purple-700">
              {{ comparisonLabel(selectedTemplate?.comparison) }} ({{ reportData.comparison_rows.length }} rows)
            </h3>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th
                    v-for="col in comparisonColumns"
                    :key="col"
                    class="px-4 py-3 text-xs font-medium text-gray-500 uppercase whitespace-nowrap"
                    :class="isNumericColumn(col) ? 'text-right' : 'text-left'"
                  >
                    {{ columnLabel(col) }}
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 bg-white">
                <tr v-for="(row, idx) in reportData.comparison_rows" :key="idx" class="hover:bg-gray-50">
                  <td
                    v-for="col in comparisonColumns"
                    :key="col"
                    class="px-4 py-2 text-sm whitespace-nowrap"
                    :class="isNumericColumn(col) ? 'text-right font-mono' : 'text-left'"
                  >
                    {{ isNumericColumn(col) ? formatNumber(row[col]) : (row[col] || '-') }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>
    </template>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted, defineComponent, h } from 'vue'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import crMessages from '@/scripts/admin/i18n/custom-reports.js'

const axios = window.axios
const consoleStore = useConsoleStore()

const locale = document.documentElement.lang || 'mk'
const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }
const fmtLocale = localeMap[locale] || 'mk-MK'

function t(key) {
  return crMessages[locale]?.custom_reports?.[key]
    || crMessages['en']?.custom_reports?.[key]
    || key
}

const notificationStore = useNotificationStore()

// State
const companies = computed(() => consoleStore.managedCompanies || [])
const selectedCompanyId = ref(null)
const templates = ref([])
const selectedTemplate = ref(null)
const reportData = ref(null)
const previewData = ref(null)
const isLoading = ref(false)
const isExecuting = ref(false)
const isSaving = ref(false)
const isPreviewing = ref(false)
const showCreateForm = ref(false)
const dateFrom = ref('')
const dateTo = ref('')
const codesInput = ref('')
const emailInput = ref('')
const scheduleChoice = ref('')

// Create form
const form = ref({
  name: '',
  account_filter: { type: 'all', from: '', to: '', categories: [], codes: [] },
  columns: ['code', 'name', 'opening', 'debit', 'credit', 'closing'],
  period_type: 'year',
  group_by: '',
  comparison: '',
  date_from: '',
  date_to: '',
})

const filterTypeOptions = [
  { value: 'all', label: t('all_accounts') },
  { value: 'range', label: t('account_range') },
  { value: 'category', label: t('account_category') },
  { value: 'specific', label: t('specific_accounts') },
]

const accountCategories = [
  { value: 'OPERATING_REVENUE', label: t('cat_operating_revenue') },
  { value: 'NON_OPERATING_REVENUE', label: t('cat_non_operating_revenue') },
  { value: 'OPERATING_EXPENSE', label: t('cat_operating_expense') },
  { value: 'DIRECT_EXPENSE', label: t('cat_direct_expense') },
  { value: 'OVERHEAD_EXPENSE', label: t('cat_overhead_expense') },
  { value: 'NON_OPERATING_EXPENSE', label: t('cat_non_operating_expense') },
  { value: 'CURRENT_ASSET', label: t('cat_current_asset') },
  { value: 'NON_CURRENT_ASSET', label: t('cat_non_current_asset') },
  { value: 'CURRENT_LIABILITY', label: t('cat_current_liability') },
  { value: 'NON_CURRENT_LIABILITY', label: t('cat_non_current_liability') },
  { value: 'EQUITY', label: t('cat_equity') },
  { value: 'BANK', label: t('cat_bank') },
  { value: 'RECEIVABLE', label: t('cat_receivable') },
  { value: 'PAYABLE', label: t('cat_payable') },
  { value: 'INVENTORY', label: t('cat_inventory') },
]

const columnOptions = [
  { value: 'code', label: t('code') },
  { value: 'name', label: t('account_name') },
  { value: 'opening', label: t('opening') },
  { value: 'debit', label: t('debit') },
  { value: 'credit', label: t('credit') },
  { value: 'closing', label: t('closing') },
  { value: 'budget', label: t('budget') },
  { value: 'variance', label: t('variance') },
  { value: 'variance_pct', label: t('variance_pct') },
]

const numericCols = ['opening', 'debit', 'credit', 'closing', 'budget', 'variance', 'variance_pct']

// Computed
const displayColumns = computed(() => {
  if (!reportData.value?.columns) return []
  const cols = [...reportData.value.columns]
  if (reportData.value.rows?.[0]?.period_group !== undefined && !cols.includes('period_group')) {
    cols.splice(0, 0, 'period_group')
  }
  if (reportData.value.rows?.[0]?.cost_center_name !== undefined && !cols.includes('cost_center_name')) {
    cols.splice(cols.indexOf('code') + 1 || 0, 0, 'cost_center_name')
  }
  return cols
})

const comparisonColumns = computed(() => {
  if (!reportData.value?.comparison_rows?.[0]) return []
  return Object.keys(reportData.value.comparison_rows[0]).filter(k => k !== 'account_id')
})

// Helpers
function isNumericColumn(col) { return numericCols.includes(col) }

function partnerApi(path) {
  return `/partner/companies/${selectedCompanyId.value}/accounting/custom-reports${path}`
}

function periodLabel(type) {
  const labels = { month: t('monthly'), quarter: t('quarterly'), year: t('yearly'), custom: t('custom_period') }
  return labels[type] || type || t('yearly')
}

function groupByLabel(val) {
  if (!val) return ''
  const labels = { month: t('by_month'), quarter: t('by_quarter'), cost_center: t('by_cost_center') }
  return labels[val] || val
}

function comparisonLabel(val) {
  if (!val) return ''
  const labels = { previous_year: t('previous_year'), budget: t('budget_comparison') }
  return labels[val] || val
}

function filterLabel(filter) {
  if (!filter || filter.type === 'all') return t('all_accounts')
  if (filter.type === 'range') return `${filter.from}-${filter.to}`
  if (filter.type === 'category') return `${(filter.categories || []).length} cat.`
  if (filter.type === 'specific') return `${(filter.codes || []).length} codes`
  return t('all_accounts')
}

function columnLabel(col) {
  const labels = {
    code: t('code'), name: t('account_name'), opening: t('opening'),
    debit: t('debit'), credit: t('credit'), closing: t('closing'),
    budget: t('budget'), variance: t('variance'), variance_pct: t('variance_pct'),
    period_group: t('period_group'), cost_center_name: t('cost_center'),
  }
  return labels[col] || col
}

function formatNumber(val) {
  if (val === null || val === undefined) return '-'
  return Number(val).toLocaleString(fmtLocale, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function buildAccountFilter() {
  const filter = { type: form.value.account_filter.type }
  if (filter.type === 'range') {
    filter.from = form.value.account_filter.from || '0'
    filter.to = form.value.account_filter.to || '9999'
  } else if (filter.type === 'category') {
    filter.categories = form.value.account_filter.categories || []
  } else if (filter.type === 'specific') {
    filter.codes = codesInput.value.split(',').map(c => c.trim()).filter(Boolean)
  }
  return filter
}

// API calls
async function onCompanyChange() {
  if (!selectedCompanyId.value) return
  selectedTemplate.value = null
  reportData.value = null
  previewData.value = null
  showCreateForm.value = false
  loadTemplates()
}

async function loadTemplates() {
  isLoading.value = true
  try {
    const { data } = await axios.get(partnerApi(''))
    templates.value = data.data || []
  } catch (e) {
    notificationStore.showNotification({ type: 'error', message: t('error_loading') })
  } finally {
    isLoading.value = false
  }
}

function selectTemplate(tpl) {
  selectedTemplate.value = tpl
  reportData.value = null
  executeReport()
}

async function executeReport() {
  if (!selectedTemplate.value) return
  isExecuting.value = true
  reportData.value = null

  try {
    const params = {}
    if (dateFrom.value) params.date_from = dateFrom.value
    if (dateTo.value) params.date_to = dateTo.value

    const { data } = await axios.get(partnerApi(`/${selectedTemplate.value.id}/execute`), { params })
    reportData.value = data.data?.report || data.data
  } catch (e) {
    notificationStore.showNotification({ type: 'error', message: t('error_executing') })
    reportData.value = null
  } finally {
    isExecuting.value = false
  }
}

async function previewReport() {
  isPreviewing.value = true
  previewData.value = null

  try {
    const payload = {
      account_filter: buildAccountFilter(),
      columns: form.value.columns,
      period_type: form.value.period_type,
      group_by: form.value.group_by || null,
      comparison: form.value.comparison || null,
    }
    if (form.value.period_type === 'custom') {
      payload.date_from = form.value.date_from || null
      payload.date_to = form.value.date_to || null
    }

    const { data } = await axios.post(partnerApi('/preview'), payload)
    previewData.value = data.data || null
  } catch (e) {
    notificationStore.showNotification({ type: 'error', message: t('error_preview') })
  } finally {
    isPreviewing.value = false
  }
}

async function saveReport() {
  isSaving.value = true

  try {
    const scheduleEmails = emailInput.value
      ? emailInput.value.split(',').map(e => e.trim()).filter(Boolean)
      : null

    const payload = {
      name: form.value.name,
      account_filter: buildAccountFilter(),
      columns: form.value.columns,
      period_type: form.value.period_type || null,
      group_by: form.value.group_by || null,
      comparison: form.value.comparison || null,
      schedule_cron: scheduleChoice.value || null,
      schedule_emails: scheduleEmails,
    }

    await axios.post(partnerApi(''), payload)

    notificationStore.showNotification({ type: 'success', message: t('template_saved') })
    showCreateForm.value = false
    previewData.value = null

    // Reset form
    form.value = {
      name: '',
      account_filter: { type: 'all', from: '', to: '', categories: [], codes: [] },
      columns: ['code', 'name', 'opening', 'debit', 'credit', 'closing'],
      period_type: 'year',
      group_by: '',
      comparison: '',
      date_from: '',
      date_to: '',
    }
    scheduleChoice.value = ''
    emailInput.value = ''
    codesInput.value = ''

    await loadTemplates()
  } catch (e) {
    notificationStore.showNotification({ type: 'error', message: e.response?.data?.message || t('error_saving') })
  } finally {
    isSaving.value = false
  }
}

async function confirmDelete(tpl) {
  if (!window.confirm(t('confirm_delete'))) return

  try {
    await axios.delete(partnerApi(`/${tpl.id}`))
    notificationStore.showNotification({ type: 'success', message: t('template_deleted') })
    if (selectedTemplate.value?.id === tpl.id) {
      selectedTemplate.value = null
      reportData.value = null
    }
    await loadTemplates()
  } catch (e) {
    notificationStore.showNotification({ type: 'error', message: t('error_deleting') })
  }
}

function exportCsv() {
  if (!reportData.value?.rows) return
  try {
    const cols = displayColumns.value
    const header = cols.map(c => columnLabel(c)).join(',')
    const rows = reportData.value.rows.map(row =>
      cols.map(c => {
        const val = row[c]
        if (val === null || val === undefined) return ''
        if (typeof val === 'string' && val.includes(',')) return `"${val}"`
        return val
      }).join(',')
    )

    const csv = [header, ...rows].join('\n')
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `${selectedTemplate.value?.name || 'report'}.csv`
    link.click()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: t('error_exporting') })
  }
}

onMounted(() => {
  consoleStore.fetchCompanies()
})

// Report Table subcomponent (inline)
const ReportTable = defineComponent({
  props: ['data', 'title', 't', 'fmtLocale'],
  setup(props) {
    const cols = computed(() => {
      if (!props.data?.columns) return []
      const c = [...props.data.columns]
      if (props.data.rows?.[0]?.period_group !== undefined && !c.includes('period_group')) {
        c.splice(0, 0, 'period_group')
      }
      if (props.data.rows?.[0]?.cost_center_name !== undefined && !c.includes('cost_center_name')) {
        c.splice(c.indexOf('code') + 1 || 0, 0, 'cost_center_name')
      }
      return c
    })

    const isNum = (col) => numericCols.includes(col)
    const fmt = (val) => {
      if (val === null || val === undefined) return '-'
      return Number(val).toLocaleString(props.fmtLocale, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    }
    const colLabel = (col) => columnLabel(col)

    return () => {
      if (!props.data?.rows?.length) {
        return h('div', { class: 'bg-white rounded-lg shadow px-6 py-8 text-center text-sm text-gray-500' }, props.t('no_data'))
      }

      return h('div', { class: 'bg-white rounded-lg shadow overflow-hidden' }, [
        h('div', { class: 'px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between' }, [
          h('h3', { class: 'text-sm font-medium text-gray-700' }, `${props.title} (${props.data.rows.length} rows)`),
          h('span', { class: 'text-xs text-gray-400' }, props.data.period ? `${props.data.period.from} - ${props.data.period.to}` : ''),
        ]),
        h('div', { class: 'overflow-x-auto' }, [
          h('table', { class: 'min-w-full divide-y divide-gray-200' }, [
            h('thead', { class: 'bg-gray-50' }, [
              h('tr', {}, cols.value.map(col =>
                h('th', {
                  class: `px-4 py-3 text-xs font-medium text-gray-500 uppercase whitespace-nowrap ${isNum(col) ? 'text-right' : 'text-left'}`,
                }, colLabel(col))
              )),
            ]),
            h('tbody', { class: 'divide-y divide-gray-100 bg-white' }, [
              ...props.data.rows.map((row, idx) =>
                h('tr', { class: 'hover:bg-gray-50', key: idx }, cols.value.map(col =>
                  h('td', {
                    class: `px-4 py-2 text-sm whitespace-nowrap ${isNum(col) ? 'text-right font-mono' : 'text-left'}`,
                  }, isNum(col) ? fmt(row[col]) : (row[col] || '-'))
                ))
              ),
              props.data.totals ? h('tr', { class: 'bg-primary-50 font-semibold' }, cols.value.map((col, cidx) =>
                h('td', {
                  class: `px-4 py-3 text-sm whitespace-nowrap ${isNum(col) ? 'text-right font-mono text-primary-900' : 'text-left text-primary-900'}`,
                }, cidx === 0 ? props.t('total') : (isNum(col) ? fmt(props.data.totals[col]) : ''))
              )) : null,
            ]),
          ]),
        ]),
      ])
    }
  },
})
</script>

<!-- CLAUDE-CHECKPOINT -->
