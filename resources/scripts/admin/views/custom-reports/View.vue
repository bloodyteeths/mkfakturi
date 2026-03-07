<template>
  <BasePage>
    <BasePageHeader :title="template ? template.name : t('title')">
      <template #actions>
        <div class="flex items-center space-x-2">
          <BaseButton
            v-if="reportData"
            variant="primary-outline"
            @click="exportPdf"
            :disabled="isExporting"
          >
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="DocumentArrowDownIcon" />
            </template>
            {{ t('export_pdf') }}
          </BaseButton>
          <BaseButton
            v-if="reportData"
            variant="primary-outline"
            @click="exportExcel"
            :disabled="isExporting"
          >
            {{ t('export_excel') }}
          </BaseButton>
          <BaseButton variant="primary-outline" @click="$router.push({ name: 'custom-reports.index' })">
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

    <template v-else-if="template">
      <!-- Template Info Card -->
      <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
          <div>
            <p class="text-xs text-gray-500">{{ t('account_filter') }}</p>
            <p class="text-sm font-medium text-gray-900">{{ filterLabel(template.account_filter) }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">{{ t('columns') }}</p>
            <p class="text-sm font-medium text-gray-900">{{ (template.columns || []).join(', ') }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">{{ t('period') }}</p>
            <p class="text-sm font-medium text-gray-900">{{ periodLabel(template.period_type) }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">{{ t('group_by') }}</p>
            <p class="text-sm font-medium text-gray-900">{{ groupByLabel(template.group_by) }}</p>
          </div>
          <div v-if="template.comparison">
            <p class="text-xs text-gray-500">{{ t('comparison') }}</p>
            <p class="text-sm font-medium text-gray-900">{{ comparisonLabel(template.comparison) }}</p>
          </div>
        </div>

        <!-- Custom date override -->
        <div class="mt-4 flex items-end space-x-4">
          <BaseInputGroup :label="t('date_from')">
            <BaseInput v-model="dateFrom" type="date" class="w-40" />
          </BaseInputGroup>
          <BaseInputGroup :label="t('date_to')">
            <BaseInput v-model="dateTo" type="date" class="w-40" />
          </BaseInputGroup>
          <BaseButton variant="primary" @click="executeReport" :disabled="isExecuting">
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="PlayIcon" />
            </template>
            {{ isExecuting ? t('loading') : t('execute') }}
          </BaseButton>
        </div>
      </div>

      <!-- Report Results -->
      <div v-if="reportData" class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
          <h3 class="text-sm font-medium text-gray-700">
            {{ template.name }} ({{ reportData.rows?.length || 0 }} rows)
          </h3>
          <span class="text-xs text-gray-400">
            {{ reportData.period?.from }} - {{ reportData.period?.to }}
          </span>
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
                  :class="getCellClass(col, row[col])"
                >
                  {{ isNumericColumn(col) ? formatNumber(row[col]) : (row[col] || '-') }}
                </td>
              </tr>

              <!-- Totals row -->
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
            {{ comparisonLabel(template.comparison) }} ({{ reportData.comparison_rows.length }} rows)
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
                  :class="getCellClass(col, row[col])"
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
import { useRoute, useRouter } from 'vue-router'
import { useNotificationStore } from '@/scripts/stores/notification'
import crMessages from '@/scripts/admin/i18n/custom-reports.js'

const locale = document.documentElement.lang || 'mk'
const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }
const fmtLocale = localeMap[locale] || 'mk-MK'

function t(key) {
  return crMessages[locale]?.custom_reports?.[key]
    || crMessages['en']?.custom_reports?.[key]
    || key
}

const route = useRoute()
const router = useRouter()
const notificationStore = useNotificationStore()

const template = ref(null)
const reportData = ref(null)
const isLoading = ref(false)
const isExecuting = ref(false)
const isExporting = ref(false)
const dateFrom = ref('')
const dateTo = ref('')

const numericCols = ['opening', 'debit', 'credit', 'closing', 'budget', 'variance', 'variance_pct']

const displayColumns = computed(() => {
  if (!reportData.value?.columns) return []
  const cols = [...reportData.value.columns]
  // Add extra columns from data rows
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

function getCellClass(col, val) {
  if (!isNumericColumn(col)) return 'text-left'
  const classes = ['text-right', 'font-mono']
  if (col === 'variance' || col === 'variance_pct') {
    if (val > 0) classes.push('text-red-600')
    else if (val < 0) classes.push('text-green-600')
    else classes.push('text-gray-500')
  }
  return classes.join(' ')
}

function periodLabel(type) {
  const labels = {
    month: t('monthly'),
    quarter: t('quarterly'),
    year: t('yearly'),
    custom: t('custom_period'),
  }
  return labels[type] || type || '-'
}

function groupByLabel(val) {
  if (!val) return t('no_grouping')
  const labels = {
    month: t('by_month'),
    quarter: t('by_quarter'),
    cost_center: t('by_cost_center'),
  }
  return labels[val] || val
}

function comparisonLabel(val) {
  if (!val) return ''
  const labels = {
    previous_year: t('previous_year'),
    budget: t('budget_comparison'),
  }
  return labels[val] || val
}

function filterLabel(filter) {
  if (!filter || filter.type === 'all') return t('all_accounts')
  if (filter.type === 'range') return `${t('account_range')}: ${filter.from}-${filter.to}`
  if (filter.type === 'category') return `${t('account_category')} (${(filter.categories || []).length})`
  if (filter.type === 'specific') return `${t('specific_accounts')} (${(filter.codes || []).length})`
  return t('all_accounts')
}

function columnLabel(col) {
  const labels = {
    code: t('code'),
    name: t('account_name'),
    opening: t('opening'),
    debit: t('debit'),
    credit: t('credit'),
    closing: t('closing'),
    budget: t('budget'),
    variance: t('variance'),
    variance_pct: t('variance_pct'),
    period_group: t('period_group'),
    cost_center_name: t('cost_center'),
    account_type: t('account_filter'),
  }
  return labels[col] || col
}

function formatNumber(val) {
  if (val === null || val === undefined) return '-'
  return Number(val).toLocaleString(fmtLocale, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

onMounted(async () => {
  await loadTemplate()
  // Auto-execute on load
  if (template.value) {
    await executeReport()
  }
})

async function loadTemplate() {
  isLoading.value = true
  try {
    const id = route.params.id
    const response = await window.axios.get(`/custom-reports/${id}`)
    template.value = response.data?.data
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('error_loading'),
    })
    router.push({ name: 'custom-reports.index' })
  } finally {
    isLoading.value = false
  }
}

async function executeReport() {
  if (!template.value) return
  isExecuting.value = true
  reportData.value = null

  try {
    const params = {}
    if (dateFrom.value) params.date_from = dateFrom.value
    if (dateTo.value) params.date_to = dateTo.value

    const response = await window.axios.get(`/custom-reports/${template.value.id}/execute`, { params })
    const data = response.data?.data

    if (data?.report) {
      reportData.value = data.report
    } else {
      reportData.value = data
    }
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('error_executing'),
    })
  } finally {
    isExecuting.value = false
  }
}

async function exportPdf() {
  if (!template.value) return
  isExporting.value = true

  try {
    const response = await window.axios.get(`/custom-reports/${template.value.id}/export-pdf`, {
      responseType: 'blob',
    })

    // If response is JSON (fallback), just show notification
    const contentType = response.headers?.['content-type'] || ''
    if (contentType.includes('application/json')) {
      notificationStore.showNotification({
        type: 'info',
        message: t('pdf_export_ready'),
      })
      return
    }

    // Download blob
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `${template.value.name}.pdf`
    link.click()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('error_exporting'),
    })
  } finally {
    isExporting.value = false
  }
}

async function exportExcel() {
  if (!reportData.value?.rows) return

  try {
    // Client-side CSV export
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
    link.download = `${template.value?.name || 'report'}.csv`
    link.click()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: t('error_exporting'),
    })
  }
}
</script>

<!-- CLAUDE-CHECKPOINT -->
