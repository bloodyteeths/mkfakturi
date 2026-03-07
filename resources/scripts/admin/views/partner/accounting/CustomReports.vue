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

      <!-- Templates List -->
      <div v-else-if="templates.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
        <BaseIcon name="DocumentChartBarIcon" class="h-12 w-12 text-gray-400 mx-auto" />
        <p class="text-sm text-gray-500 mt-4">{{ t('no_templates') }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ t('no_templates_desc') }}</p>
      </div>

      <!-- Template Cards -->
      <div v-if="templates.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div
          v-for="tpl in templates"
          :key="tpl.id"
          class="bg-white rounded-lg shadow p-4 hover:shadow-md transition-shadow cursor-pointer"
          @click="selectTemplate(tpl)"
        >
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-base font-medium text-gray-900">{{ tpl.name }}</h3>
              <p class="text-xs text-gray-500 mt-1">
                {{ periodLabel(tpl.period_type) }}
                <span v-if="tpl.group_by" class="ml-1 text-gray-400">/ {{ groupByLabel(tpl.group_by) }}</span>
              </p>
            </div>
            <div class="flex items-center gap-2">
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-blue-50 text-blue-700">
                {{ filterLabel(tpl.account_filter) }}
              </span>
              <span v-if="tpl.comparison" class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-purple-50 text-purple-700">
                {{ comparisonLabel(tpl.comparison) }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Execute Panel -->
      <div v-if="selectedTemplate" class="bg-white rounded-lg shadow mb-6">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
          <h3 class="text-sm font-medium text-gray-700">{{ selectedTemplate.name }}</h3>
          <div class="flex items-center space-x-3">
            <BaseInputGroup :label="t('date_from')">
              <BaseInput v-model="dateFrom" type="date" class="w-36" />
            </BaseInputGroup>
            <BaseInputGroup :label="t('date_to')">
              <BaseInput v-model="dateTo" type="date" class="w-36" />
            </BaseInputGroup>
            <BaseButton variant="primary" size="sm" @click="executeReport" :disabled="isExecuting">
              {{ isExecuting ? t('loading') : t('execute') }}
            </BaseButton>
          </div>
        </div>
      </div>

      <!-- Report Results -->
      <div v-if="reportData" class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
          <h3 class="text-sm font-medium text-gray-700">
            {{ selectedTemplate?.name }} ({{ reportData.rows?.length || 0 }} rows)
          </h3>
          <div class="flex items-center space-x-2">
            <span class="text-xs text-gray-400">
              {{ reportData.period?.from }} - {{ reportData.period?.to }}
            </span>
            <button
              class="text-primary-600 hover:text-primary-800 text-xs font-medium"
              @click="exportCsv"
            >
              {{ t('export_excel') }}
            </button>
          </div>
        </div>

        <div v-if="reportData.rows && reportData.rows.length > 0" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th
                  v-for="col in displayColumns"
                  :key="col"
                  class="px-4 py-3 text-xs font-medium text-gray-500 uppercase whitespace-nowrap"
                  :class="isNumericColumn(col) ? 'text-right' : 'text-left'"
                >
                  {{ columnLabel(col) }}
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
              <tr v-for="(row, idx) in reportData.rows" :key="idx" class="hover:bg-gray-50">
                <td
                  v-for="col in displayColumns"
                  :key="col"
                  class="px-4 py-2 text-sm whitespace-nowrap"
                  :class="isNumericColumn(col) ? 'text-right font-mono' : 'text-left'"
                >
                  {{ isNumericColumn(col) ? formatNumber(row[col]) : (row[col] || '-') }}
                </td>
              </tr>
              <!-- Totals -->
              <tr v-if="reportData.totals" class="bg-primary-50 font-semibold">
                <td
                  v-for="(col, cidx) in displayColumns"
                  :key="col"
                  class="px-4 py-3 text-sm whitespace-nowrap"
                  :class="isNumericColumn(col) ? 'text-right font-mono text-primary-900' : 'text-left text-primary-900'"
                >
                  {{ cidx === 0 ? t('total') : (isNumericColumn(col) ? formatNumber(reportData.totals[col]) : '') }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="px-6 py-8 text-center text-sm text-gray-500">
          {{ t('no_data') }}
        </div>
      </div>

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
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
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

const companies = computed(() => consoleStore.managedCompanies || [])
const selectedCompanyId = ref(null)
const templates = ref([])
const selectedTemplate = ref(null)
const reportData = ref(null)
const isLoading = ref(false)
const isExecuting = ref(false)
const dateFrom = ref('')
const dateTo = ref('')

const numericCols = ['opening', 'debit', 'credit', 'closing', 'budget', 'variance', 'variance_pct']

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

function isNumericColumn(col) {
  return numericCols.includes(col)
}

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
    account_type: t('account_filter'),
  }
  return labels[col] || col
}

function formatNumber(val) {
  if (val === null || val === undefined) return '-'
  return Number(val).toLocaleString(fmtLocale, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

async function loadCompanies() {
  await consoleStore.fetchCompanies()
}

async function onCompanyChange() {
  if (!selectedCompanyId.value) return
  selectedTemplate.value = null
  reportData.value = null
  loadTemplates()
}

async function loadTemplates() {
  isLoading.value = true
  try {
    const { data } = await axios.get(partnerApi(''))
    templates.value = data.data || []
  } catch (e) {
    notificationStore.showNotification({
      type: 'error',
      message: t('error_loading'),
    })
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
    const result = data.data

    if (result?.report) {
      reportData.value = result.report
    } else {
      reportData.value = result
    }
  } catch (e) {
    notificationStore.showNotification({
      type: 'error',
      message: t('error_executing'),
    })
    reportData.value = null
  } finally {
    isExecuting.value = false
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
    notificationStore.showNotification({
      type: 'error',
      message: t('error_exporting'),
    })
  }
}

onMounted(() => {
  loadCompanies()
})
</script>

<!-- CLAUDE-CHECKPOINT -->
