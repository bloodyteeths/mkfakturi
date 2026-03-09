<template>
  <BasePage>
    <BasePageHeader :title="$t('partner.accounting.payroll_reports', 'Payroll Reports')">
    </BasePageHeader>

    <!-- Company Selector -->
    <div class="mb-6">
      <BaseInputGroup :label="$t('partner.select_company')">
        <BaseMultiselect
          v-model="selectedCompanyId"
          :options="companies"
          :searchable="true"
          track-by="id"
          label="name"
          value-prop="id"
          :placeholder="$t('partner.select_company_placeholder')"
          @update:model-value="onCompanyChange"
        />
      </BaseInputGroup>
    </div>

    <!-- Select company message -->
    <div
      v-if="!selectedCompanyId"
      class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-12"
    >
      <BaseIcon name="BuildingOfficeIcon" class="h-12 w-12 text-gray-400" />
      <p class="mt-2 text-sm text-gray-500">
        {{ $t('partner.accounting.select_company_to_view') }}
      </p>
    </div>

    <template v-if="selectedCompanyId">
      <!-- Period Selector -->
      <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
        <BaseInputGroup :label="$t('general.year', 'Year')">
          <BaseMultiselect
            v-model="selectedYear"
            :options="yearOptions"
            label="label"
            value-prop="value"
            @update:model-value="loadData"
          />
        </BaseInputGroup>
        <BaseInputGroup :label="$t('general.month', 'Month')">
          <BaseMultiselect
            v-model="selectedMonth"
            :options="monthOptions"
            label="label"
            value-prop="value"
            @update:model-value="loadData"
          />
        </BaseInputGroup>
        <div class="flex items-end space-x-2">
          <BaseButton
            variant="primary-outline"
            @click="downloadMpinXml"
            :loading="isDownloadingMpin"
            :disabled="!selectedMonth"
          >
            <template #left="slotProps">
              <BaseIcon name="ArrowDownTrayIcon" :class="slotProps.class" />
            </template>
            MPIN XML
          </BaseButton>
          <BaseButton
            variant="primary-outline"
            @click="downloadDdv04Xml"
            :loading="isDownloadingDdv04"
          >
            <template #left="slotProps">
              <BaseIcon name="ArrowDownTrayIcon" :class="slotProps.class" />
            </template>
            DDV-04 XML
          </BaseButton>
          <BaseButton
            variant="primary-outline"
            @click="exportCsv"
            :loading="isExportingCsv"
          >
            <template #left="slotProps">
              <BaseIcon name="ArrowDownTrayIcon" :class="slotProps.class" />
            </template>
            CSV
          </BaseButton>
        </div>
      </div>

      <!-- No payroll data notice -->
      <div
        v-if="!isLoading && statistics && statistics.active_employees === 0 && taxSummary && taxSummary.payroll_run_count === 0"
        class="mb-6 flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-12"
      >
        <BaseIcon name="UserGroupIcon" class="h-12 w-12 text-gray-400" />
        <p class="mt-2 text-sm font-medium text-gray-500">
          {{ $t('payroll.no_data_for_company', 'No payroll data found for this company.') }}
        </p>
        <p class="mt-1 text-xs text-gray-400">
          {{ $t('payroll.create_employees_first', 'Set up employees and run payroll from the company admin panel first.') }}
        </p>
      </div>

      <!-- Statistics Cards -->
      <div v-if="statistics" class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="rounded-lg bg-white shadow p-6">
          <p class="text-sm font-medium text-gray-500">{{ $t('payroll.active_employees', 'Active Employees') }}</p>
          <p class="mt-2 text-3xl font-bold text-gray-900">{{ statistics.active_employees }}</p>
        </div>
        <div class="rounded-lg bg-white shadow p-6">
          <p class="text-sm font-medium text-gray-500">{{ $t('payroll.current_month_gross', 'Current Month Gross') }}</p>
          <p class="mt-2 text-3xl font-bold text-gray-900">{{ formatMoney(statistics.current_month?.total_gross) }}</p>
        </div>
        <div class="rounded-lg bg-white shadow p-6">
          <p class="text-sm font-medium text-gray-500">{{ $t('payroll.current_month_net', 'Current Month Net') }}</p>
          <p class="mt-2 text-3xl font-bold text-gray-900">{{ formatMoney(statistics.current_month?.total_net) }}</p>
        </div>
        <div class="rounded-lg bg-white shadow p-6">
          <p class="text-sm font-medium text-gray-500">{{ $t('payroll.ytd_gross', 'YTD Gross') }}</p>
          <p class="mt-2 text-3xl font-bold text-gray-900">{{ formatMoney(statistics.year_to_date?.total_gross) }}</p>
        </div>
      </div>

      <!-- Tax Summary Table -->
      <div v-if="taxSummary" class="mb-6 rounded-lg bg-white shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <h3 class="text-lg font-medium text-gray-900">
            {{ $t('payroll.tax_summary', 'Payroll Tax Summary') }} - {{ taxSummary.period }}
          </h3>
          <p class="text-sm text-gray-500">
            {{ taxSummary.payroll_run_count }} {{ $t('payroll.runs', 'payroll runs') }}, {{ taxSummary.employee_count }} {{ $t('payroll.employees', 'employees') }}
          </p>
        </div>
        <div class="p-6">
          <dl class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-4">
            <div>
              <dt class="text-sm font-medium text-gray-500">{{ $t('payroll.total_gross', 'Total Gross') }}</dt>
              <dd class="mt-1 text-lg font-semibold text-gray-900">{{ formatMoney(taxSummary.total_gross) }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500">{{ $t('payroll.total_net', 'Total Net') }}</dt>
              <dd class="mt-1 text-lg font-semibold text-gray-900">{{ formatMoney(taxSummary.total_net) }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500">{{ $t('payroll.total_income_tax', 'Income Tax (PIT)') }}</dt>
              <dd class="mt-1 text-lg font-semibold text-red-600">{{ formatMoney(taxSummary.total_income_tax) }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500">{{ $t('payroll.pension_employee', 'Pension (Employee)') }}</dt>
              <dd class="mt-1 text-lg font-semibold text-gray-900">{{ formatMoney(taxSummary.total_pension_employee) }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500">{{ $t('payroll.pension_employer', 'Pension (Employer)') }}</dt>
              <dd class="mt-1 text-lg font-semibold text-gray-900">{{ formatMoney(taxSummary.total_pension_employer) }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500">{{ $t('payroll.health_employee', 'Health (Employee)') }}</dt>
              <dd class="mt-1 text-lg font-semibold text-gray-900">{{ formatMoney(taxSummary.total_health_employee) }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500">{{ $t('payroll.health_employer', 'Health (Employer)') }}</dt>
              <dd class="mt-1 text-lg font-semibold text-gray-900">{{ formatMoney(taxSummary.total_health_employer) }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500">{{ $t('payroll.total_employee_contributions', 'Total Employee Contributions') }}</dt>
              <dd class="mt-1 text-lg font-bold text-primary-600">{{ formatMoney(taxSummary.total_employee_contributions) }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500">{{ $t('payroll.total_employer_cost', 'Total Employer Cost') }}</dt>
              <dd class="mt-1 text-lg font-bold text-red-600">{{ formatMoney(taxSummary.total_employer_cost) }}</dd>
            </div>
          </dl>
        </div>
      </div>

      <!-- Monthly Comparison Table -->
      <div v-if="monthlyData.length > 0" class="rounded-lg bg-white shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <h3 class="text-lg font-medium text-gray-900">
            {{ $t('payroll.monthly_comparison', 'Monthly Comparison') }} - {{ selectedYear }}
          </h3>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('general.month', 'Month') }}</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('payroll.gross', 'Gross') }}</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('payroll.net', 'Net') }}</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('payroll.employer_tax', 'Employer Tax') }}</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('payroll.employees', 'Employees') }}</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ $t('general.status', 'Status') }}</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="month in monthlyData" :key="month.month" :class="{ 'bg-gray-50': !month.has_payroll }">
                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ month.month_name }}</td>
                <td class="px-6 py-4 text-sm text-right text-gray-900">{{ month.has_payroll ? formatMoney(month.total_gross) : '-' }}</td>
                <td class="px-6 py-4 text-sm text-right text-gray-900">{{ month.has_payroll ? formatMoney(month.total_net) : '-' }}</td>
                <td class="px-6 py-4 text-sm text-right text-gray-900">{{ month.has_payroll ? formatMoney(month.total_employer_tax) : '-' }}</td>
                <td class="px-6 py-4 text-sm text-right text-gray-500">{{ month.has_payroll ? month.employee_count : '-' }}</td>
                <td class="px-6 py-4 text-center">
                  <span
                    v-if="month.status"
                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                    :class="statusClass(month.status)"
                  >
                    {{ statusLabel(month.status) }}
                  </span>
                  <span v-else class="text-sm text-gray-400">-</span>
                </td>
              </tr>
            </tbody>
            <tfoot v-if="yearlyTotals" class="bg-gray-50">
              <tr>
                <td class="px-6 py-4 text-sm font-bold text-gray-900">{{ $t('general.total', 'Total') }}</td>
                <td class="px-6 py-4 text-sm text-right font-bold text-gray-900">{{ formatMoney(yearlyTotals.total_gross) }}</td>
                <td class="px-6 py-4 text-sm text-right font-bold text-gray-900">{{ formatMoney(yearlyTotals.total_net) }}</td>
                <td class="px-6 py-4 text-sm text-right font-bold text-gray-900">{{ formatMoney(yearlyTotals.total_employer_tax) }}</td>
                <td colspan="2"></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- Per-Employee Breakdown Table -->
      <div v-if="employeeBreakdown.length > 0" class="mt-6 rounded-lg bg-white shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <h3 class="text-lg font-medium text-gray-900">
            {{ $t('payroll.employee_breakdown', 'Per-Employee Breakdown') }}
          </h3>
          <p class="text-sm text-gray-500">
            {{ taxSummary?.period }}
          </p>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('payroll.employee_name', 'Employee') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ЕМБГ</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('payroll.gross', 'Gross') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('payroll.employee_contributions_short', 'Deductions') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('payroll.income_tax_short', 'PIT') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('payroll.net', 'Net') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('payroll.employer_cost_short', 'Employer Cost') }}</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="emp in employeeBreakdown" :key="emp.employee_id">
                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ emp.full_name }}</td>
                <td class="px-4 py-3 text-sm text-gray-500 font-mono">{{ emp.embg }}</td>
                <td class="px-4 py-3 text-sm text-right text-gray-900">{{ formatMoney(emp.gross_salary) }}</td>
                <td class="px-4 py-3 text-sm text-right text-gray-900">{{ formatMoney(emp.total_employee_contributions) }}</td>
                <td class="px-4 py-3 text-sm text-right text-red-600">{{ formatMoney(emp.income_tax) }}</td>
                <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900">{{ formatMoney(emp.net_salary) }}</td>
                <td class="px-4 py-3 text-sm text-right text-gray-900">{{ formatMoney(emp.employer_cost) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="mt-6 flex justify-center">
        <BaseContentPlaceholders>
          <BaseContentPlaceholdersBox :rounded="true" />
        </BaseContentPlaceholders>
      </div>
    </template>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import { useNotificationStore } from '@/scripts/stores/notification'
// Use window.axios (configured with baseURL + company header interceptor)

const { t } = useI18n()
const consoleStore = useConsoleStore()
const notificationStore = useNotificationStore()

// State
const selectedCompanyId = ref(null)
const selectedYear = ref(new Date().getFullYear())
const selectedMonth = ref(null)
const isLoading = ref(false)
const isDownloadingMpin = ref(false)
const isDownloadingDdv04 = ref(false)
const isExportingCsv = ref(false)

const statistics = ref(null)
const taxSummary = ref(null)
const monthlyData = ref([])
const yearlyTotals = ref(null)
const employeeBreakdown = ref([])

// Computed
const companies = computed(() => consoleStore.managedCompanies || [])

const yearOptions = computed(() => {
  const currentYear = new Date().getFullYear()
  const options = []
  for (let y = currentYear; y >= 2020; y--) {
    options.push({ label: String(y), value: y })
  }
  return options
})

const monthOptions = computed(() => {
  const options = [{ label: t('general.all', 'All Months'), value: null }]
  for (let m = 1; m <= 12; m++) {
    options.push({
      label: new Date(2000, m - 1, 1).toLocaleString('mk-MK', { month: 'long' }),
      value: m,
    })
  }
  return options
})

// Methods
function onCompanyChange() {
  statistics.value = null
  taxSummary.value = null
  monthlyData.value = []
  yearlyTotals.value = null
  employeeBreakdown.value = []

  if (selectedCompanyId.value) {
    loadData()
  }
}

async function loadData() {
  if (!selectedCompanyId.value) return
  isLoading.value = true

  try {
    const base = `/partner/companies/${selectedCompanyId.value}/payroll-reports`
    const [statsRes, summaryRes, comparisonRes, breakdownRes] = await Promise.all([
      window.axios.get(`${base}/statistics`),
      window.axios.get(`${base}/tax-summary`, {
        params: { year: selectedYear.value, month: selectedMonth.value },
      }),
      window.axios.get(`${base}/monthly-comparison`, {
        params: { year: selectedYear.value },
      }),
      window.axios.get(`${base}/employee-breakdown`, {
        params: { year: selectedYear.value, month: selectedMonth.value },
      }),
    ])

    statistics.value = statsRes.data.data
    taxSummary.value = summaryRes.data.data
    monthlyData.value = comparisonRes.data.data?.months || []
    yearlyTotals.value = comparisonRes.data.data?.yearly_totals || null
    employeeBreakdown.value = breakdownRes.data.data || []
  } catch (error) {
    console.error('Failed to load payroll data:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('payroll.failed_to_load', 'Failed to load payroll reports'),
    })
  } finally {
    isLoading.value = false
  }
}

async function downloadMpinXml() {
  if (!selectedMonth.value) return
  isDownloadingMpin.value = true
  try {
    const response = await window.axios.get(
      `/partner/companies/${selectedCompanyId.value}/payroll-reports/download-mpin-xml`,
      {
        params: { year: selectedYear.value, month: selectedMonth.value },
        responseType: 'blob',
      }
    )
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `MPIN_${selectedYear.value}_${String(selectedMonth.value).padStart(2, '0')}.xml`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    let msg = t('payroll.failed_mpin_download', 'Failed to download MPIN XML')
    if (error.response?.data instanceof Blob) {
      try {
        const text = await error.response.data.text()
        const json = JSON.parse(text)
        msg = json.error || json.message || msg
      } catch (e) { /* blob wasn't JSON */ }
    } else if (error.response?.data?.error) {
      msg = error.response.data.error
    }
    notificationStore.showNotification({ type: 'error', message: msg })
  } finally {
    isDownloadingMpin.value = false
  }
}

async function downloadDdv04Xml() {
  isDownloadingDdv04.value = true
  try {
    const response = await window.axios.get(
      `/partner/companies/${selectedCompanyId.value}/payroll-reports/download-ddv04-xml`,
      {
        params: { year: selectedYear.value },
        responseType: 'blob',
      }
    )
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `DDV04_${selectedYear.value}.xml`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    let msg = t('payroll.failed_ddv04_download', 'Failed to download DDV-04 XML')
    if (error.response?.data instanceof Blob) {
      try {
        const text = await error.response.data.text()
        const json = JSON.parse(text)
        msg = json.error || json.message || msg
      } catch (e) { /* blob wasn't JSON */ }
    } else if (error.response?.data?.error) {
      msg = error.response.data.error
    }
    notificationStore.showNotification({ type: 'error', message: msg })
  } finally {
    isDownloadingDdv04.value = false
  }
}

async function exportCsv() {
  isExportingCsv.value = true
  try {
    const response = await window.axios.get(
      `/partner/companies/${selectedCompanyId.value}/payroll-reports/export-csv`,
      {
        params: { year: selectedYear.value, month: selectedMonth.value },
        responseType: 'blob',
      }
    )
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    const period = selectedMonth.value
      ? `${selectedYear.value}_${String(selectedMonth.value).padStart(2, '0')}`
      : `${selectedYear.value}`
    link.setAttribute('download', `payroll_tax_summary_${period}.csv`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    let msg = t('payroll.failed_csv_export', 'Failed to export CSV')
    if (error.response?.data instanceof Blob) {
      try {
        const text = await error.response.data.text()
        const json = JSON.parse(text)
        msg = json.error || json.message || msg
      } catch (e) { /* blob wasn't JSON */ }
    } else if (error.response?.data?.error) {
      msg = error.response.data.error
    }
    notificationStore.showNotification({ type: 'error', message: msg })
  } finally {
    isExportingCsv.value = false
  }
}

const statusLabels = {
  paid: 'Исплатено',
  posted: 'Книжено',
  approved: 'Одобрено',
  calculated: 'Пресметано',
  draft: 'Нацрт',
  not_created: 'Не е креирано',
}

function statusLabel(status) {
  return statusLabels[status] || status
}

function statusClass(status) {
  switch (status) {
    case 'paid': return 'bg-green-100 text-green-800'
    case 'posted': return 'bg-blue-100 text-blue-800'
    case 'approved': return 'bg-indigo-100 text-indigo-800'
    case 'calculated': return 'bg-yellow-100 text-yellow-800'
    case 'draft': return 'bg-gray-100 text-gray-800'
    default: return 'bg-gray-100 text-gray-800'
  }
}

function formatMoney(amount) {
  if (!amount && amount !== 0) return '0.00 ден.'
  // DB stores amounts in subunits (стотинки), divide by 100 for MKD
  const mkd = amount / 100
  return new Intl.NumberFormat('mk-MK', {
    style: 'currency',
    currency: 'MKD',
    minimumFractionDigits: 2,
  }).format(mkd)
}

// Lifecycle
onMounted(async () => {
  await consoleStore.fetchCompanies()
  if (companies.value.length === 1) {
    selectedCompanyId.value = companies.value[0].id
    onCompanyChange()
  }
})
</script>

<!-- CLAUDE-CHECKPOINT -->
