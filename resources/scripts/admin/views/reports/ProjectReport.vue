<template>
  <BasePage>
    <BasePageHeader :title="$t('reports.projects.title')">
      <template #actions>
        <BaseButton
          variant="primary-outline"
          @click="exportToCSV"
        >
          <BaseIcon name="ArrowDownTrayIcon" class="h-5 mr-2" />
          {{ $t('general.export_to_csv') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <div class="grid gap-8 md:grid-cols-12 pt-6">
      <!-- Filters Sidebar -->
      <div class="col-span-12 md:col-span-4">
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-medium mb-4">{{ $t('general.filters') }}</h3>

          <BaseInputGroup
            :label="$t('reports.projects.date_range')"
            class="mb-4"
          >
            <BaseMultiselect
              v-model="selectedRange"
              :options="dateRange"
              value-prop="key"
              track-by="key"
              label="label"
              object
              @update:modelValue="onChangeDateRange"
            />
          </BaseInputGroup>

          <div class="flex flex-col mb-4 space-y-4">
            <BaseInputGroup :label="$t('reports.projects.from_date')">
              <BaseDatePicker v-model="filters.from_date" />
            </BaseInputGroup>

            <BaseInputGroup :label="$t('reports.projects.to_date')">
              <BaseDatePicker v-model="filters.to_date" />
            </BaseInputGroup>
          </div>

          <BaseButton
            variant="primary"
            class="w-full"
            @click="loadReports"
          >
            {{ $t('reports.update_report') }}
          </BaseButton>
        </div>
      </div>

      <!-- Reports Table -->
      <div class="col-span-12 md:col-span-8">
        <div class="bg-white rounded-lg shadow">
          <!-- Summary Cards -->
          <div v-if="reportData" class="grid grid-cols-2 md:grid-cols-4 gap-4 p-6 border-b">
            <div>
              <p class="text-sm text-gray-500">{{ $t('reports.projects.total_invoiced') }}</p>
              <p class="text-xl font-semibold text-primary-500">
                {{ formatMoney(reportData.grand_total?.total_invoiced || 0) }}
              </p>
            </div>
            <div>
              <p class="text-sm text-gray-500">{{ $t('reports.projects.total_paid') }}</p>
              <p class="text-xl font-semibold text-green-600">
                {{ formatMoney(reportData.grand_total?.total_paid || 0) }}
              </p>
            </div>
            <div>
              <p class="text-sm text-gray-500">{{ $t('reports.projects.total_expenses') }}</p>
              <p class="text-xl font-semibold text-red-600">
                {{ formatMoney(reportData.grand_total?.total_expenses || 0) }}
              </p>
            </div>
            <div>
              <p class="text-sm text-gray-500">{{ $t('reports.projects.net_profit') }}</p>
              <p
                class="text-xl font-semibold"
                :class="(reportData.grand_total?.net_profit || 0) >= 0 ? 'text-green-600' : 'text-red-600'"
              >
                {{ formatMoney(reportData.grand_total?.net_profit || 0) }}
              </p>
            </div>
          </div>

          <!-- Projects Table -->
          <div class="overflow-x-auto">
            <table v-if="!isLoading && reportData?.projects?.length" class="w-full">
              <thead class="bg-gray-50 border-b">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ $t('projects.name') }}
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ $t('customers.customer') }}
                  </th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ $t('reports.projects.total_invoiced') }}
                  </th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ $t('reports.projects.total_expenses') }}
                  </th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ $t('reports.projects.net_profit') }}
                  </th>
                  <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ $t('general.actions') }}
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr
                  v-for="project in reportData.projects"
                  :key="project.id"
                  class="hover:bg-gray-50 cursor-pointer"
                  @click="viewProjectDetail(project.id)"
                >
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div>
                        <div class="text-sm font-medium text-gray-900">{{ project.name }}</div>
                        <div v-if="project.code" class="text-sm text-gray-500">{{ project.code }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">{{ project.customer?.name || '-' }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                    {{ formatMoney(project.total_invoiced) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-red-600">
                    {{ formatMoney(project.total_expenses) }}
                  </td>
                  <td
                    class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
                    :class="project.net_profit >= 0 ? 'text-green-600' : 'text-red-600'"
                  >
                    {{ formatMoney(project.net_profit) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                    <BaseButton
                      variant="primary-outline"
                      size="sm"
                      @click.stop="viewProjectDetail(project.id)"
                    >
                      {{ $t('general.view') }}
                    </BaseButton>
                  </td>
                </tr>
              </tbody>
            </table>

            <div v-else-if="isLoading" class="p-12 text-center">
              <BaseIcon name="ArrowPathIcon" class="h-8 w-8 mx-auto animate-spin text-gray-400" />
              <p class="mt-2 text-gray-500">{{ $t('general.loading') }}</p>
            </div>

            <div v-else class="p-12 text-center">
              <BaseIcon name="FolderOpenIcon" class="h-12 w-12 mx-auto text-gray-400" />
              <p class="mt-2 text-gray-500">{{ $t('reports.projects.no_projects') }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Project Detail Modal -->
    <BaseModal
      :show="showDetailModal"
      @close="showDetailModal = false"
    >
      <template #header>
        {{ selectedProject?.name || $t('reports.projects.detail') }}
      </template>
      <div v-if="projectDetail" class="p-6 space-y-6">
        <!-- Project Info -->
        <div class="bg-gray-50 p-4 rounded-lg">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-gray-500">{{ $t('customers.customer') }}</p>
              <p class="font-medium">{{ projectDetail.project?.customer?.name || '-' }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">{{ $t('projects.status') }}</p>
              <p class="font-medium capitalize">{{ projectDetail.project?.status || '-' }}</p>
            </div>
          </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="bg-blue-50 p-4 rounded-lg">
            <p class="text-sm text-gray-600">{{ $t('reports.projects.total_invoiced') }}</p>
            <p class="text-xl font-semibold text-blue-600">
              {{ formatMoney(projectDetail.summary?.total_invoiced || 0) }}
            </p>
          </div>
          <div class="bg-green-50 p-4 rounded-lg">
            <p class="text-sm text-gray-600">{{ $t('reports.projects.total_paid') }}</p>
            <p class="text-xl font-semibold text-green-600">
              {{ formatMoney(projectDetail.summary?.total_payments || 0) }}
            </p>
          </div>
          <div class="bg-red-50 p-4 rounded-lg">
            <p class="text-sm text-gray-600">{{ $t('reports.projects.total_expenses') }}</p>
            <p class="text-xl font-semibold text-red-600">
              {{ formatMoney(projectDetail.summary?.total_expenses || 0) }}
            </p>
          </div>
          <div
            class="p-4 rounded-lg"
            :class="(projectDetail.summary?.net_result || 0) >= 0 ? 'bg-green-50' : 'bg-red-50'"
          >
            <p class="text-sm text-gray-600">{{ $t('reports.projects.net_profit') }}</p>
            <p
              class="text-xl font-semibold"
              :class="(projectDetail.summary?.net_result || 0) >= 0 ? 'text-green-600' : 'text-red-600'"
            >
              {{ formatMoney(projectDetail.summary?.net_result || 0) }}
            </p>
          </div>
        </div>

        <!-- Monthly Breakdown -->
        <div v-if="projectDetail.monthly_breakdown?.length">
          <h4 class="text-lg font-medium mb-3">{{ $t('reports.projects.monthly_breakdown') }}</h4>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-2 text-left">{{ $t('general.month') }}</th>
                  <th class="px-4 py-2 text-right">{{ $t('reports.projects.total_invoiced') }}</th>
                  <th class="px-4 py-2 text-right">{{ $t('reports.projects.total_expenses') }}</th>
                  <th class="px-4 py-2 text-right">{{ $t('reports.projects.net_profit') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y">
                <tr v-for="month in projectDetail.monthly_breakdown" :key="month.month">
                  <td class="px-4 py-2">{{ month.month_name }}</td>
                  <td class="px-4 py-2 text-right">{{ formatMoney(month.total_invoiced) }}</td>
                  <td class="px-4 py-2 text-right text-red-600">{{ formatMoney(month.total_expenses) }}</td>
                  <td
                    class="px-4 py-2 text-right font-medium"
                    :class="month.net_result >= 0 ? 'text-green-600' : 'text-red-600'"
                  >
                    {{ formatMoney(month.net_result) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Invoices, Expenses, Payments Lists -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <h5 class="font-medium mb-2">{{ $t('invoices.invoices') }} ({{ projectDetail.invoices?.length || 0 }})</h5>
            <div class="space-y-1 max-h-40 overflow-y-auto">
              <div
                v-for="invoice in projectDetail.invoices"
                :key="invoice.id"
                class="text-sm p-2 bg-gray-50 rounded"
              >
                <div class="flex justify-between">
                  <span>{{ invoice.invoice_number }}</span>
                  <span class="font-medium">{{ formatMoney(invoice.total) }}</span>
                </div>
              </div>
            </div>
          </div>

          <div>
            <h5 class="font-medium mb-2">{{ $t('expenses.expenses') }} ({{ projectDetail.expenses?.length || 0 }})</h5>
            <div class="space-y-1 max-h-40 overflow-y-auto">
              <div
                v-for="expense in projectDetail.expenses"
                :key="expense.id"
                class="text-sm p-2 bg-gray-50 rounded"
              >
                <div class="flex justify-between">
                  <span>{{ expense.category || $t('general.expense') }}</span>
                  <span class="font-medium text-red-600">{{ formatMoney(expense.amount) }}</span>
                </div>
              </div>
            </div>
          </div>

          <div>
            <h5 class="font-medium mb-2">{{ $t('payments.payments') }} ({{ projectDetail.payments?.length || 0 }})</h5>
            <div class="space-y-1 max-h-40 overflow-y-auto">
              <div
                v-for="payment in projectDetail.payments"
                :key="payment.id"
                class="text-sm p-2 bg-gray-50 rounded"
              >
                <div class="flex justify-between">
                  <span>{{ payment.payment_number }}</span>
                  <span class="font-medium text-green-600">{{ formatMoney(payment.amount) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <template #footer>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
          <BaseButton variant="primary-outline" @click="showDetailModal = false">
            {{ $t('general.close') }}
          </BaseButton>
        </div>
      </template>
    </BaseModal>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import moment from 'moment'
import axios from 'axios'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useModalStore } from '@/scripts/stores/modal'

const { t } = useI18n()
const companyStore = useCompanyStore()
const notificationStore = useNotificationStore()
const modalStore = useModalStore()

const isLoading = ref(false)
const reportData = ref(null)
const showDetailModal = ref(false)
const projectDetail = ref(null)
const selectedProject = ref(null)

const dateRange = reactive([
  { label: t('dateRange.today'), key: 'Today' },
  { label: t('dateRange.this_week'), key: 'This Week' },
  { label: t('dateRange.this_month'), key: 'This Month' },
  { label: t('dateRange.this_quarter'), key: 'This Quarter' },
  { label: t('dateRange.this_year'), key: 'This Year' },
  { label: t('dateRange.previous_week'), key: 'Previous Week' },
  { label: t('dateRange.previous_month'), key: 'Previous Month' },
  { label: t('dateRange.previous_quarter'), key: 'Previous Quarter' },
  { label: t('dateRange.previous_year'), key: 'Previous Year' },
  { label: t('dateRange.custom'), key: 'Custom' },
])

const selectedRange = ref(dateRange[2]) // Default to "This Month"

const filters = reactive({
  from_date: moment().startOf('month').format('YYYY-MM-DD'),
  to_date: moment().endOf('month').format('YYYY-MM-DD'),
})

const formatMoney = (amount, currencyCode = null) => {
  const currency = currencyCode || companyStore.selectedCompanyCurrency?.code || 'USD'
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency,
  }).format(amount / 100) // Assuming amounts are in cents
}

function onChangeDateRange() {
  const key = selectedRange.value.key

  switch (key) {
    case 'Today':
      filters.from_date = moment().format('YYYY-MM-DD')
      filters.to_date = moment().format('YYYY-MM-DD')
      break
    case 'This Week':
      filters.from_date = moment().startOf('isoWeek').format('YYYY-MM-DD')
      filters.to_date = moment().endOf('isoWeek').format('YYYY-MM-DD')
      break
    case 'This Month':
      filters.from_date = moment().startOf('month').format('YYYY-MM-DD')
      filters.to_date = moment().endOf('month').format('YYYY-MM-DD')
      break
    case 'This Quarter':
      filters.from_date = moment().startOf('quarter').format('YYYY-MM-DD')
      filters.to_date = moment().endOf('quarter').format('YYYY-MM-DD')
      break
    case 'This Year':
      filters.from_date = moment().startOf('year').format('YYYY-MM-DD')
      filters.to_date = moment().endOf('year').format('YYYY-MM-DD')
      break
    case 'Previous Week':
      filters.from_date = moment().subtract(1, 'week').startOf('isoWeek').format('YYYY-MM-DD')
      filters.to_date = moment().subtract(1, 'week').endOf('isoWeek').format('YYYY-MM-DD')
      break
    case 'Previous Month':
      filters.from_date = moment().subtract(1, 'month').startOf('month').format('YYYY-MM-DD')
      filters.to_date = moment().subtract(1, 'month').endOf('month').format('YYYY-MM-DD')
      break
    case 'Previous Quarter':
      filters.from_date = moment().subtract(1, 'quarter').startOf('quarter').format('YYYY-MM-DD')
      filters.to_date = moment().subtract(1, 'quarter').endOf('quarter').format('YYYY-MM-DD')
      break
    case 'Previous Year':
      filters.from_date = moment().subtract(1, 'year').startOf('year').format('YYYY-MM-DD')
      filters.to_date = moment().subtract(1, 'year').endOf('year').format('YYYY-MM-DD')
      break
  }
}

async function loadReports() {
  isLoading.value = true
  try {
    const params = {
      from_date: filters.from_date,
      to_date: filters.to_date,
    }

    const response = await axios.get('/reports/projects', { params })
    reportData.value = response.data
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: t('reports.projects.error_loading'),
    })
  } finally {
    isLoading.value = false
  }
}

async function viewProjectDetail(projectId) {
  try {
    const params = {
      from_date: filters.from_date,
      to_date: filters.to_date,
    }

    const response = await axios.get(`/reports/projects/${projectId}`, { params })
    projectDetail.value = response.data
    selectedProject.value = response.data.project
    modalStore.size = 'lg'
    showDetailModal.value = true
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: t('reports.projects.error_loading_detail'),
    })
  }
}

function exportToCSV() {
  if (!reportData.value?.projects?.length) {
    notificationStore.showNotification({
      type: 'warning',
      message: t('reports.projects.no_data_to_export'),
    })
    return
  }

  const csvRows = []
  const headers = [
    t('projects.name'),
    t('projects.code'),
    t('customers.customer'),
    t('reports.projects.total_invoiced'),
    t('reports.projects.total_paid'),
    t('reports.projects.total_expenses'),
    t('reports.projects.net_profit'),
  ]
  csvRows.push(headers.join(','))

  reportData.value.projects.forEach((project) => {
    const row = [
      `"${project.name}"`,
      `"${project.code || ''}"`,
      `"${project.customer?.name || ''}"`,
      project.total_invoiced / 100,
      project.total_paid / 100,
      project.total_expenses / 100,
      project.net_profit / 100,
    ]
    csvRows.push(row.join(','))
  })

  const csvContent = csvRows.join('\n')
  const blob = new Blob([csvContent], { type: 'text/csv' })
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `project-reports-${moment().format('YYYY-MM-DD')}.csv`
  a.click()
  window.URL.revokeObjectURL(url)
}

onMounted(async () => {
  await loadReports()
})
</script>

// CLAUDE-CHECKPOINT
