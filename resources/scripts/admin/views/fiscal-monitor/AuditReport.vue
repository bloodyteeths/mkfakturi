<template>
  <BasePage>
    <BasePageHeader :title="$t('fiscal_monitor.audit_report')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('fiscal_monitor.title')" to="/admin/fiscal-monitor" />
        <BaseBreadcrumbItem :title="$t('fiscal_monitor.audit_report')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <!-- Tab Navigation — scrollable on mobile -->
    <div class="flex border-b border-gray-200 mt-4 overflow-x-auto -mx-4 px-4 sm:mx-0 sm:px-0">
      <button
        class="px-3 sm:px-4 py-2 text-sm font-medium border-b-2 -mb-px whitespace-nowrap border-transparent text-gray-500 hover:text-gray-700"
        @click="$router.push('/admin/fiscal-monitor')"
      >
        {{ $t('fiscal_monitor.tab_dashboard') }}
      </button>
      <button
        class="px-3 sm:px-4 py-2 text-sm font-medium border-b-2 -mb-px whitespace-nowrap border-indigo-500 text-indigo-600"
      >
        {{ $t('fiscal_monitor.tab_audit') }}
      </button>
      <button
        class="px-3 sm:px-4 py-2 text-sm font-medium border-b-2 -mb-px whitespace-nowrap border-transparent text-gray-500 hover:text-gray-700"
        @click="$router.push('/admin/fiscal-receipts')"
      >
        {{ $t('fiscal_monitor.tab_receipts') }}
      </button>
    </div>

    <!-- Filters — stacked on mobile, inline on desktop -->
    <BaseCard class="p-3 sm:p-4 mt-5">
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('fiscal_monitor.from_date') }}</label>
          <input v-model="filters.from" type="date" class="w-full border-gray-300 rounded-md shadow-sm text-sm" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('fiscal_monitor.to_date') }}</label>
          <input v-model="filters.to" type="date" class="w-full border-gray-300 rounded-md shadow-sm text-sm" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('fiscal_monitor.device') }}</label>
          <select v-model="filters.device_id" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
            <option value="">{{ $t('fiscal_monitor.all_devices') }}</option>
            <option v-for="d in devices" :key="d.id" :value="d.id">{{ d.name || d.serial_number }}</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('fiscal_monitor.filter_employee') }}</label>
          <select v-model="filters.user_id" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
            <option value="">{{ $t('fiscal_monitor.all_employees') }}</option>
            <option v-for="emp in employees" :key="emp.user_id" :value="emp.user_id">{{ emp.user_name }}</option>
          </select>
        </div>
        <div class="flex items-end">
          <BaseButton variant="primary" size="sm" class="w-full" @click="loadReport">
            {{ $t('fiscal_monitor.generate_report') }}
          </BaseButton>
        </div>
        <div v-if="report" class="flex items-end gap-2">
          <BaseButton variant="primary-outline" size="sm" class="flex-1" @click="exportCsv">
            {{ $t('fiscal_monitor.export_csv') }}
          </BaseButton>
          <BaseButton variant="primary-outline" size="sm" class="flex-1" @click="exportPdf">
            {{ $t('fiscal_monitor.export_pdf') }}
          </BaseButton>
        </div>
      </div>
    </BaseCard>

    <div v-if="report" class="mt-5 space-y-4">

      <!-- By Employee -->
      <BaseCard class="overflow-hidden">
        <div class="p-3 sm:p-4 border-b border-gray-100">
          <h4 class="text-sm sm:text-base font-semibold text-gray-900">{{ $t('fiscal_monitor.by_employee') }}</h4>
        </div>

        <!-- Desktop table -->
        <div class="hidden sm:block overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="text-left py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.employee') }}</th>
                <th class="text-center py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.total_events') }}</th>
                <th class="text-center py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.event_open') }}</th>
                <th class="text-center py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.event_close') }}</th>
                <th class="text-center py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.event_receipt') }}</th>
                <th class="text-center py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.event_void') }}</th>
                <th class="text-center py-2 px-3 text-gray-600 font-medium">Z</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in report.by_user" :key="row.user_id" class="border-b border-gray-100 hover:bg-gray-50">
                <td class="py-2 px-3 font-medium">{{ row.user_name }}</td>
                <td class="py-2 px-3 text-center">{{ row.total_events }}</td>
                <td class="py-2 px-3 text-center text-green-600">{{ row.opens }}</td>
                <td class="py-2 px-3 text-center">{{ row.closes }}</td>
                <td class="py-2 px-3 text-center text-indigo-600">{{ row.receipts }}</td>
                <td class="py-2 px-3 text-center" :class="row.voids > 3 ? 'text-red-600 font-bold' : ''">{{ row.voids }}</td>
                <td class="py-2 px-3 text-center text-blue-600">{{ row.z_reports }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile card list -->
        <div class="sm:hidden divide-y divide-gray-100">
          <div v-for="row in report.by_user" :key="row.user_id" class="p-3">
            <p class="text-sm font-medium text-gray-900 mb-1">{{ row.user_name }}</p>
            <div class="grid grid-cols-3 gap-2 text-xs">
              <div>
                <span class="text-gray-400">{{ $t('fiscal_monitor.event_receipt') }}</span>
                <p class="font-medium text-indigo-600">{{ row.receipts }}</p>
              </div>
              <div>
                <span class="text-gray-400">{{ $t('fiscal_monitor.event_void') }}</span>
                <p class="font-medium" :class="row.voids > 3 ? 'text-red-600' : ''">{{ row.voids }}</p>
              </div>
              <div>
                <span class="text-gray-400">{{ $t('fiscal_monitor.total_events') }}</span>
                <p class="font-medium">{{ row.total_events }}</p>
              </div>
            </div>
          </div>
        </div>
      </BaseCard>

      <!-- By Device -->
      <BaseCard class="overflow-hidden">
        <div class="p-3 sm:p-4 border-b border-gray-100">
          <h4 class="text-sm sm:text-base font-semibold text-gray-900">{{ $t('fiscal_monitor.by_device') }}</h4>
        </div>

        <div class="hidden sm:block overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="text-left py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.device') }}</th>
                <th class="text-center py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.total_events') }}</th>
                <th class="text-center py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.unique_users') }}</th>
                <th class="text-center py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.event_receipt') }}</th>
                <th class="text-center py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.event_void') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in report.by_device" :key="row.device_id" class="border-b border-gray-100 hover:bg-gray-50">
                <td class="py-2 px-3 font-medium">{{ row.device_name }}</td>
                <td class="py-2 px-3 text-center">{{ row.total_events }}</td>
                <td class="py-2 px-3 text-center">{{ row.unique_users }}</td>
                <td class="py-2 px-3 text-center text-indigo-600">{{ row.receipts }}</td>
                <td class="py-2 px-3 text-center" :class="row.voids > 3 ? 'text-red-600 font-bold' : ''">{{ row.voids }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="sm:hidden divide-y divide-gray-100">
          <div v-for="row in report.by_device" :key="row.device_id" class="p-3">
            <div class="flex items-center justify-between mb-1">
              <p class="text-sm font-medium text-gray-900">{{ row.device_name }}</p>
              <span class="text-xs bg-gray-100 text-gray-600 rounded-full px-2 py-0.5">{{ row.unique_users }} {{ $t('fiscal_monitor.unique_users') }}</span>
            </div>
            <div class="grid grid-cols-3 gap-2 text-xs">
              <div>
                <span class="text-gray-400">{{ $t('fiscal_monitor.event_receipt') }}</span>
                <p class="font-medium text-indigo-600">{{ row.receipts }}</p>
              </div>
              <div>
                <span class="text-gray-400">{{ $t('fiscal_monitor.event_void') }}</span>
                <p class="font-medium" :class="row.voids > 3 ? 'text-red-600' : ''">{{ row.voids }}</p>
              </div>
              <div>
                <span class="text-gray-400">{{ $t('fiscal_monitor.total_events') }}</span>
                <p class="font-medium">{{ row.total_events }}</p>
              </div>
            </div>
          </div>
        </div>
      </BaseCard>

      <!-- Daily Summary -->
      <BaseCard class="overflow-hidden">
        <div class="p-3 sm:p-4 border-b border-gray-100">
          <h4 class="text-sm sm:text-base font-semibold text-gray-900">{{ $t('fiscal_monitor.daily_summary') }}</h4>
        </div>

        <div class="hidden sm:block overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="text-left py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.date') }}</th>
                <th class="text-center py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.total_events') }}</th>
                <th class="text-center py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.event_receipt') }}</th>
                <th class="text-center py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.event_void') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in report.by_day" :key="row.date" class="border-b border-gray-100 hover:bg-gray-50">
                <td class="py-2 px-3 font-medium">{{ row.date }}</td>
                <td class="py-2 px-3 text-center">{{ row.total_events }}</td>
                <td class="py-2 px-3 text-center text-indigo-600">{{ row.receipts }}</td>
                <td class="py-2 px-3 text-center" :class="row.voids > 3 ? 'text-red-600 font-bold' : ''">{{ row.voids }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="sm:hidden divide-y divide-gray-100">
          <div v-for="row in report.by_day" :key="row.date" class="p-3">
            <p class="text-sm font-medium text-gray-900 mb-1">{{ row.date }}</p>
            <div class="grid grid-cols-3 gap-2 text-xs">
              <div>
                <span class="text-gray-400">{{ $t('fiscal_monitor.event_receipt') }}</span>
                <p class="font-medium text-indigo-600">{{ row.receipts }}</p>
              </div>
              <div>
                <span class="text-gray-400">{{ $t('fiscal_monitor.event_void') }}</span>
                <p class="font-medium" :class="row.voids > 3 ? 'text-red-600' : ''">{{ row.voids }}</p>
              </div>
              <div>
                <span class="text-gray-400">{{ $t('fiscal_monitor.total_events') }}</span>
                <p class="font-medium">{{ row.total_events }}</p>
              </div>
            </div>
          </div>
        </div>
      </BaseCard>
    </div>

    <div v-else-if="!isLoading" class="text-center py-12 text-gray-400 mt-5">
      <svg class="w-10 h-10 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      <p class="text-sm">{{ $t('fiscal_monitor.select_filters') }}</p>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'

const { t } = useI18n()
const isLoading = ref(false)
const devices = ref([])
const employees = ref([])
const report = ref(null)

const today = new Date()
const thirtyDaysAgo = new Date(today)
thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30)

const filters = ref({
  from: `${thirtyDaysAgo.getFullYear()}-${String(thirtyDaysAgo.getMonth() + 1).padStart(2, '0')}-${String(thirtyDaysAgo.getDate()).padStart(2, '0')}`,
  to: `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`,
  device_id: '',
  user_id: '',
})

onMounted(async () => {
  try {
    const res = await axios.get('fiscal-monitor/dashboard')
    if (res.data?.data?.devices) {
      devices.value = res.data.data.devices.map(d => d.device)
    }
  } catch (err) {
    console.error('Failed to load devices:', err)
  }
})

async function loadReport() {
  isLoading.value = true
  try {
    const params = { from: filters.value.from, to: filters.value.to }
    if (filters.value.device_id) params.device_id = filters.value.device_id
    if (filters.value.user_id) params.user_id = filters.value.user_id

    const res = await axios.get('fiscal-monitor/audit-report', { params })
    report.value = res.data?.data || null

    if (report.value?.by_user) {
      employees.value = report.value.by_user
    }
  } catch (err) {
    console.error('Failed to load audit report:', err)
  } finally {
    isLoading.value = false
  }
}

function exportCsv() {
  if (!report.value) return

  const rows = [['Employee', 'Total Events', 'Open', 'Close', 'Receipt', 'Void', 'Z-Report']]
  for (const row of (report.value.by_user || [])) {
    rows.push([row.user_name, row.total_events, row.opens, row.closes, row.receipts, row.voids, row.z_reports])
  }
  rows.push([])
  rows.push(['Device', 'Total Events', 'Users', 'Open', 'Close', 'Receipt', 'Void'])
  for (const row of (report.value.by_device || [])) {
    rows.push([row.device_name, row.total_events, row.unique_users, row.opens, row.closes, row.receipts, row.voids])
  }
  rows.push([])
  rows.push(['Date', 'Total Events', 'Open', 'Close', 'Receipt', 'Void'])
  for (const row of (report.value.by_day || [])) {
    rows.push([row.date, row.total_events, row.opens, row.closes, row.receipts, row.voids])
  }

  const csvContent = rows.map(r => r.join(',')).join('\n')
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `fiscal-audit-${filters.value.from}-${filters.value.to}.csv`
  a.click()
  URL.revokeObjectURL(url)
}

function exportPdf() {
  const params = new URLSearchParams({ from: filters.value.from, to: filters.value.to })
  if (filters.value.device_id) params.append('device_id', filters.value.device_id)
  if (filters.value.user_id) params.append('user_id', filters.value.user_id)
  window.open(`/api/v1/fiscal-monitor/audit-report-pdf?${params.toString()}`, '_blank')
}
</script>

// CLAUDE-CHECKPOINT
