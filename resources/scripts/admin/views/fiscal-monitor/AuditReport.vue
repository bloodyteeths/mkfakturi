<template>
  <BasePage>
    <BasePageHeader :title="$t('fiscal_monitor.audit_report')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('fiscal_monitor.title')" to="/admin/fiscal-monitor" />
        <BaseBreadcrumbItem :title="$t('fiscal_monitor.audit_report')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <!-- Filters -->
    <BaseCard class="p-4 mt-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('fiscal_monitor.from_date') }}</label>
          <input v-model="filters.from" type="date" class="w-full border-gray-300 rounded-md shadow-sm text-sm" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('fiscal_monitor.to_date') }}</label>
          <input v-model="filters.to" type="date" class="w-full border-gray-300 rounded-md shadow-sm text-sm" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('fiscal_monitor.device') }}</label>
          <select v-model="filters.device_id" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
            <option value="">{{ $t('fiscal_monitor.all_devices') }}</option>
            <option v-for="d in devices" :key="d.id" :value="d.id">{{ d.name || d.serial_number }}</option>
          </select>
        </div>
        <div class="flex items-end">
          <BaseButton variant="primary" @click="loadReport">
            {{ $t('fiscal_monitor.generate_report') }}
          </BaseButton>
        </div>
      </div>
    </BaseCard>

    <div v-if="report" class="mt-6 space-y-6">
      <!-- By Employee -->
      <BaseCard class="p-4">
        <h4 class="text-base font-semibold text-gray-900 mb-4">{{ $t('fiscal_monitor.by_employee') }}</h4>
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="text-left py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.employee') }}</th>
                <th class="text-center py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.total_events') }}</th>
                <th class="text-center py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.event_open') }}</th>
                <th class="text-center py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.event_close') }}</th>
                <th class="text-center py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.event_receipt') }}</th>
                <th class="text-center py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.event_void') }}</th>
                <th class="text-center py-2 px-3 text-gray-600 font-medium">Z-{{ $t('fiscal_monitor.event_z_report') }}</th>
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
      </BaseCard>

      <!-- By Device -->
      <BaseCard class="p-4">
        <h4 class="text-base font-semibold text-gray-900 mb-4">{{ $t('fiscal_monitor.by_device') }}</h4>
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="text-left py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.device') }}</th>
                <th class="text-center py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.total_events') }}</th>
                <th class="text-center py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.unique_users') }}</th>
                <th class="text-center py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.event_open') }}</th>
                <th class="text-center py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.event_close') }}</th>
                <th class="text-center py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.event_receipt') }}</th>
                <th class="text-center py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.event_void') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in report.by_device" :key="row.device_id" class="border-b border-gray-100 hover:bg-gray-50">
                <td class="py-2 px-3 font-medium">{{ row.device_name }}</td>
                <td class="py-2 px-3 text-center">{{ row.total_events }}</td>
                <td class="py-2 px-3 text-center">{{ row.unique_users }}</td>
                <td class="py-2 px-3 text-center text-green-600">{{ row.opens }}</td>
                <td class="py-2 px-3 text-center">{{ row.closes }}</td>
                <td class="py-2 px-3 text-center text-indigo-600">{{ row.receipts }}</td>
                <td class="py-2 px-3 text-center" :class="row.voids > 3 ? 'text-red-600 font-bold' : ''">{{ row.voids }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </BaseCard>

      <!-- Daily Summary -->
      <BaseCard class="p-4">
        <h4 class="text-base font-semibold text-gray-900 mb-4">{{ $t('fiscal_monitor.daily_summary') }}</h4>
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="text-left py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.date') }}</th>
                <th class="text-center py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.total_events') }}</th>
                <th class="text-center py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.event_open') }}</th>
                <th class="text-center py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.event_close') }}</th>
                <th class="text-center py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.event_receipt') }}</th>
                <th class="text-center py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.event_void') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in report.by_day" :key="row.date" class="border-b border-gray-100 hover:bg-gray-50">
                <td class="py-2 px-3 font-medium">{{ row.date }}</td>
                <td class="py-2 px-3 text-center">{{ row.total_events }}</td>
                <td class="py-2 px-3 text-center text-green-600">{{ row.opens }}</td>
                <td class="py-2 px-3 text-center">{{ row.closes }}</td>
                <td class="py-2 px-3 text-center text-indigo-600">{{ row.receipts }}</td>
                <td class="py-2 px-3 text-center" :class="row.voids > 3 ? 'text-red-600 font-bold' : ''">{{ row.voids }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </BaseCard>
    </div>

    <div v-else-if="!isLoading" class="text-center py-12 text-gray-400 mt-6">
      {{ $t('fiscal_monitor.select_filters') }}
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
const report = ref(null)

const today = new Date()
const thirtyDaysAgo = new Date(today)
thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30)

const filters = ref({
  from: thirtyDaysAgo.toISOString().split('T')[0],
  to: today.toISOString().split('T')[0],
  device_id: '',
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

    const res = await axios.get('fiscal-monitor/audit-report', { params })
    report.value = res.data?.data || null
  } catch (err) {
    console.error('Failed to load audit report:', err)
  } finally {
    isLoading.value = false
  }
}
</script>

// CLAUDE-CHECKPOINT
