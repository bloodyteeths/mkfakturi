<template>
  <BasePage v-if="!isLoading">
    <BasePageHeader :title="device.name || device.serial_number">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('fiscal_monitor.title')" to="/admin/fiscal-monitor" />
        <BaseBreadcrumbItem :title="device.name || device.serial_number" to="#" active />
      </BaseBreadcrumb>
      <template #actions>
        <span
          class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium mr-3"
          :class="deviceStatus === 'open' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'"
        >
          <span class="w-2 h-2 rounded-full mr-2" :class="deviceStatus === 'open' ? 'bg-green-500' : 'bg-gray-400'" />
          {{ deviceStatus === 'open' ? $t('fiscal_monitor.status_open') : $t('fiscal_monitor.status_closed') }}
        </span>
      </template>
    </BasePageHeader>

    <!-- Device Info -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
      <BaseCard class="p-4">
        <p class="text-sm text-gray-500">{{ $t('fiscal_monitor.serial') }}</p>
        <p class="text-base font-medium text-gray-900">{{ device.serial_number }}</p>
      </BaseCard>
      <BaseCard class="p-4">
        <p class="text-sm text-gray-500">{{ $t('fiscal_monitor.type') }}</p>
        <p class="text-base font-medium text-gray-900">{{ device.device_type }}</p>
      </BaseCard>
      <BaseCard class="p-4">
        <p class="text-sm text-gray-500">{{ $t('fiscal_monitor.connection') }}</p>
        <p class="text-base font-medium text-gray-900">{{ device.connection_type }}</p>
      </BaseCard>
      <BaseCard class="p-4">
        <p class="text-sm text-gray-500">{{ $t('fiscal_monitor.last_event') }}</p>
        <p class="text-base font-medium text-gray-900">
          {{ data.last_event ? formatDateTime(data.last_event.at) : '—' }}
        </p>
      </BaseCard>
    </div>

    <!-- Alerts for this device -->
    <div v-if="data.alerts?.length" class="mt-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-3">
        {{ $t('fiscal_monitor.fraud_alerts') }}
        <span class="ml-2 px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-sm">{{ data.alerts.length }}</span>
      </h3>
      <div class="space-y-2">
        <BaseCard
          v-for="alert in data.alerts"
          :key="alert.id"
          class="p-3"
          :class="{
            'border-l-4 border-red-500': alert.severity === 'critical',
            'border-l-4 border-orange-400': alert.severity === 'high',
            'border-l-4 border-yellow-400': alert.severity === 'medium',
          }"
        >
          <div class="flex items-center gap-2">
            <span class="px-2 py-0.5 rounded-full text-xs font-medium" :class="getSeverityClass(alert.severity)">
              {{ alert.severity.toUpperCase() }}
            </span>
            <span class="text-sm text-gray-800 flex-1">{{ alert.description }}</span>
            <span class="text-xs text-gray-400">{{ formatDateTime(alert.created_at) }}</span>
          </div>
        </BaseCard>
      </div>
    </div>

    <!-- Operators -->
    <div v-if="data.operators?.length" class="mt-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-3">{{ $t('fiscal_monitor.operators') }}</h3>
      <BaseCard class="p-4">
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="text-left py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.employee') }}</th>
                <th class="text-left py-2 px-3 text-gray-600 font-medium">Email</th>
                <th class="text-center py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.total_events') }}</th>
                <th class="text-left py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.first_seen') }}</th>
                <th class="text-left py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.last_seen') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="op in data.operators" :key="op.user_id" class="border-b border-gray-100 hover:bg-gray-50">
                <td class="py-2 px-3 font-medium">{{ op.user_name || 'Систем' }}</td>
                <td class="py-2 px-3 text-gray-500">{{ op.user_email || '—' }}</td>
                <td class="py-2 px-3 text-center">{{ op.event_count }}</td>
                <td class="py-2 px-3 text-gray-500">{{ formatDateTime(op.first_event) }}</td>
                <td class="py-2 px-3 text-gray-500">{{ formatDateTime(op.last_event) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </BaseCard>
    </div>

    <!-- Daily Stats -->
    <div v-if="data.daily_stats?.length" class="mt-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-3">{{ $t('fiscal_monitor.daily_summary') }}</h3>
      <BaseCard class="p-4">
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="text-left py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.date') }}</th>
                <th class="text-center py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.event_open') }}</th>
                <th class="text-center py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.event_close') }}</th>
                <th class="text-center py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.event_receipt') }}</th>
                <th class="text-center py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.event_void') }}</th>
                <th class="text-center py-2 px-3 text-gray-600 font-medium">Z</th>
                <th class="text-right py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.revenue') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="day in data.daily_stats" :key="day.date" class="border-b border-gray-100 hover:bg-gray-50">
                <td class="py-2 px-3 font-medium">{{ day.date }}</td>
                <td class="py-2 px-3 text-center text-green-600">{{ day.opens }}</td>
                <td class="py-2 px-3 text-center">{{ day.closes }}</td>
                <td class="py-2 px-3 text-center text-indigo-600">{{ day.receipts }}</td>
                <td class="py-2 px-3 text-center" :class="day.voids > 3 ? 'text-red-600 font-bold' : ''">{{ day.voids }}</td>
                <td class="py-2 px-3 text-center text-blue-600">{{ day.z_reports }}</td>
                <td class="py-2 px-3 text-right">
                  <BaseFormatMoney :amount="day.revenue" :currency="companyStore.selectedCompanyCurrency" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </BaseCard>
    </div>

    <!-- Event Log (Audit Trail) -->
    <div class="mt-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-3">{{ $t('fiscal_monitor.event_log') }}</h3>

      <!-- Filters -->
      <div class="flex gap-3 mb-3">
        <select v-model="eventFilter" class="border-gray-300 rounded-md shadow-sm text-sm" @change="filterEvents">
          <option value="">{{ $t('fiscal_monitor.all_events') }}</option>
          <option value="open">{{ $t('fiscal_monitor.event_open') }}</option>
          <option value="close">{{ $t('fiscal_monitor.event_close') }}</option>
          <option value="receipt">{{ $t('fiscal_monitor.event_receipt') }}</option>
          <option value="void">{{ $t('fiscal_monitor.event_void') }}</option>
          <option value="z_report">{{ $t('fiscal_monitor.event_z_report') }}</option>
          <option value="error">{{ $t('fiscal_monitor.event_error') }}</option>
        </select>
      </div>

      <BaseCard class="p-4">
        <div class="space-y-2 max-h-96 overflow-y-auto">
          <div
            v-for="event in filteredEvents"
            :key="event.id"
            class="flex items-center gap-3 py-2 border-b border-gray-50 last:border-0"
          >
            <span
              class="px-2 py-0.5 rounded text-xs font-medium min-w-[60px] text-center"
              :class="getEventBadgeClass(event.event_type)"
            >
              {{ getEventLabel(event.event_type) }}
            </span>
            <span class="text-sm text-gray-500 min-w-[120px]">{{ formatDateTime(event.event_at) }}</span>
            <span class="text-sm text-gray-700 flex-1">
              {{ event.user ? event.user.name : 'Систем' }}
            </span>
            <span v-if="event.cash_amount" class="text-sm text-gray-500">
              <BaseFormatMoney :amount="event.cash_amount" :currency="companyStore.selectedCompanyCurrency" />
            </span>
            <span v-if="event.notes" class="text-xs text-gray-400 max-w-[200px] truncate" :title="event.notes">
              {{ event.notes }}
            </span>
          </div>

          <div v-if="!filteredEvents.length" class="text-center py-6 text-gray-400">
            {{ $t('fiscal_monitor.no_events') }}
          </div>
        </div>
      </BaseCard>
    </div>
  </BasePage>

  <div v-else class="flex items-center justify-center min-h-screen">
    <BaseSpinner />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import axios from 'axios'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const companyStore = useCompanyStore()

const isLoading = ref(true)
const data = ref({})
const device = ref({})
const deviceStatus = ref('closed')
const eventFilter = ref('')

const filteredEvents = computed(() => {
  const events = data.value.recent_events || []
  if (!eventFilter.value) return events
  return events.filter(e => e.event_type === eventFilter.value)
})

onMounted(async () => {
  try {
    const res = await axios.get(`fiscal-monitor/devices/${route.params.id}`)
    if (res.data?.data) {
      data.value = res.data.data
      device.value = res.data.data.device || {}
      deviceStatus.value = res.data.data.status || 'closed'
    }
  } catch (err) {
    console.error('Failed to load device detail:', err)
    if (err.response?.status === 403) {
      router.push('/admin/dashboard')
    } else {
      router.push('/admin/fiscal-monitor')
    }
  } finally {
    isLoading.value = false
  }
})

function getEventLabel(type) {
  const labels = {
    open: t('fiscal_monitor.event_open'),
    close: t('fiscal_monitor.event_close'),
    z_report: t('fiscal_monitor.event_z_report'),
    receipt: t('fiscal_monitor.event_receipt'),
    void: t('fiscal_monitor.event_void'),
    error: t('fiscal_monitor.event_error'),
    status_check: t('fiscal_monitor.event_status_check'),
  }
  return labels[type] || type
}

function getEventBadgeClass(type) {
  const classes = {
    open: 'bg-green-100 text-green-700',
    close: 'bg-gray-200 text-gray-700',
    z_report: 'bg-blue-100 text-blue-700',
    receipt: 'bg-indigo-50 text-indigo-600',
    void: 'bg-red-100 text-red-700',
    error: 'bg-red-200 text-red-800',
    status_check: 'bg-gray-100 text-gray-500',
  }
  return classes[type] || 'bg-gray-100 text-gray-600'
}

function getSeverityClass(severity) {
  const classes = {
    critical: 'bg-red-100 text-red-800',
    high: 'bg-orange-100 text-orange-800',
    medium: 'bg-yellow-100 text-yellow-800',
    low: 'bg-blue-100 text-blue-800',
  }
  return classes[severity] || 'bg-gray-100 text-gray-600'
}

function formatDateTime(dateStr) {
  if (!dateStr) return '—'
  const d = new Date(dateStr)
  return d.toLocaleString('mk-MK', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>

// CLAUDE-CHECKPOINT
