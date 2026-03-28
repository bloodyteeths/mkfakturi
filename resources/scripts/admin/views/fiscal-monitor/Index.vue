<template>
  <BasePage>
    <BasePageHeader :title="$t('fiscal_monitor.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('fiscal_monitor.title')" to="#" active />
      </BaseBreadcrumb>
      <template #actions>
        <BaseButton variant="primary" class="ml-2" @click="refreshDashboard">
          <template #left="slotProps">
            <BaseIcon name="ArrowPathIcon" :class="slotProps.class" />
          </template>
          {{ $t('general.refresh') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Tab Navigation -->
    <div class="flex border-b border-gray-200 mt-4">
      <button
        class="px-4 py-2 text-sm font-medium border-b-2 -mb-px"
        :class="activeTab === 'dashboard' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
        @click="activeTab = 'dashboard'"
      >
        {{ $t('fiscal_monitor.tab_dashboard') }}
      </button>
      <button
        class="px-4 py-2 text-sm font-medium border-b-2 -mb-px"
        :class="activeTab === 'audit' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
        @click="$router.push('/admin/fiscal-monitor/audit')"
      >
        {{ $t('fiscal_monitor.tab_audit') }}
      </button>
      <button
        class="px-4 py-2 text-sm font-medium border-b-2 -mb-px"
        :class="activeTab === 'receipts' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
        @click="$router.push('/admin/fiscal-receipts')"
      >
        {{ $t('fiscal_monitor.tab_receipts') }}
      </button>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mt-6">
      <BaseCard class="p-4 text-center">
        <p class="text-2xl font-bold text-gray-900">{{ dashboard.summary?.total_devices || 0 }}</p>
        <p class="text-sm text-gray-500">{{ $t('fiscal_monitor.total_devices') }}</p>
      </BaseCard>
      <BaseCard class="p-4 text-center">
        <p class="text-2xl font-bold text-green-600">{{ dashboard.summary?.open_devices || 0 }}</p>
        <p class="text-sm text-gray-500">{{ $t('fiscal_monitor.open_devices') }}</p>
      </BaseCard>
      <BaseCard class="p-4 text-center">
        <p class="text-2xl font-bold text-gray-400">{{ dashboard.summary?.closed_devices || 0 }}</p>
        <p class="text-sm text-gray-500">{{ $t('fiscal_monitor.closed_devices') }}</p>
      </BaseCard>
      <BaseCard class="p-4 text-center">
        <p class="text-2xl font-bold" :class="dashboard.summary?.open_alerts > 0 ? 'text-red-600' : 'text-green-600'">
          {{ dashboard.summary?.open_alerts || 0 }}
        </p>
        <p class="text-sm text-gray-500">{{ $t('fiscal_monitor.open_alerts') }}</p>
      </BaseCard>
      <BaseCard class="p-4 text-center">
        <p class="text-2xl font-bold text-red-700">{{ dashboard.summary?.critical_alerts || 0 }}</p>
        <p class="text-sm text-gray-500">{{ $t('fiscal_monitor.critical_alerts') }}</p>
      </BaseCard>
    </div>

    <!-- Device Status Grid -->
    <div class="mt-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ $t('fiscal_monitor.device_status') }}</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <BaseCard
          v-for="device in dashboard.devices"
          :key="device.device.id"
          class="p-4 cursor-pointer hover:shadow-md transition-shadow"
          @click="$router.push(`/admin/fiscal-monitor/device/${device.device.id}`)"
        >
          <div class="flex items-center justify-between mb-3">
            <div class="flex items-center">
              <span
                class="w-3 h-3 rounded-full mr-2"
                :class="device.status === 'open' ? 'bg-green-500' : 'bg-gray-300'"
              />
              <h4 class="font-medium text-gray-900">{{ device.device.name }}</h4>
            </div>
            <span
              class="px-2 py-1 rounded-full text-xs font-medium"
              :class="device.status === 'open'
                ? 'bg-green-100 text-green-800'
                : 'bg-gray-100 text-gray-600'"
            >
              {{ device.status === 'open' ? $t('fiscal_monitor.status_open') : $t('fiscal_monitor.status_closed') }}
            </span>
          </div>

          <div class="text-sm text-gray-500 space-y-1">
            <p>{{ $t('fiscal_monitor.serial') }}: {{ device.device.serial_number }}</p>
            <p>{{ $t('fiscal_monitor.type') }}: {{ device.device.type }}</p>
          </div>

          <div v-if="device.last_event" class="mt-3 pt-3 border-t border-gray-100 text-sm">
            <p class="text-gray-500">
              {{ $t('fiscal_monitor.last_event') }}: {{ getEventLabel(device.last_event.type) }}
              <span class="text-gray-400">{{ formatTime(device.last_event.at) }}</span>
            </p>
            <p v-if="device.last_event.user" class="text-gray-400">
              {{ device.last_event.user }}
            </p>
          </div>

          <div class="mt-3 pt-3 border-t border-gray-100 grid grid-cols-2 gap-2 text-sm">
            <div>
              <p class="text-gray-500">{{ $t('fiscal_monitor.today_receipts') }}</p>
              <p class="font-medium text-gray-900">{{ device.today.receipt_count }}</p>
            </div>
            <div>
              <p class="text-gray-500">{{ $t('fiscal_monitor.today_revenue') }}</p>
              <p class="font-medium text-gray-900">
                <BaseFormatMoney :amount="device.today.revenue" :currency="companyStore.selectedCompanyCurrency" />
              </p>
            </div>
          </div>

          <!-- Today's event timeline -->
          <div v-if="device.today.events?.length" class="mt-3 pt-3 border-t border-gray-100">
            <p class="text-xs text-gray-400 mb-1">{{ $t('fiscal_monitor.timeline') }}</p>
            <div class="flex flex-wrap gap-1">
              <span
                v-for="(evt, idx) in device.today.events"
                :key="idx"
                class="px-1.5 py-0.5 rounded text-xs"
                :class="getEventBadgeClass(evt.type)"
                :title="`${evt.at} — ${getEventLabel(evt.type)}`"
              >
                {{ evt.at }}
              </span>
            </div>
          </div>
        </BaseCard>
      </div>

      <div v-if="!dashboard.devices?.length && !isLoading" class="text-center py-12 text-gray-400">
        {{ $t('fiscal_monitor.no_devices') }}
      </div>
    </div>

    <!-- Fraud Alerts -->
    <div class="mt-8">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">
        {{ $t('fiscal_monitor.fraud_alerts') }}
        <span v-if="dashboard.alerts?.length" class="ml-2 px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-sm">
          {{ dashboard.alerts.length }}
        </span>
      </h3>

      <div v-if="dashboard.alerts?.length" class="space-y-3">
        <BaseCard
          v-for="alert in visibleAlerts"
          :key="alert.id"
          class="p-4"
          :class="{
            'border-l-4 border-red-500': alert.severity === 'critical',
            'border-l-4 border-orange-400': alert.severity === 'high',
            'border-l-4 border-yellow-400': alert.severity === 'medium',
            'border-l-4 border-blue-300': alert.severity === 'low',
          }"
        >
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-1">
                <span
                  class="px-2 py-0.5 rounded-full text-xs font-medium"
                  :class="getSeverityClass(alert.severity)"
                >
                  {{ alert.severity.toUpperCase() }}
                </span>
                <span class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full text-xs">
                  {{ getAlertTypeLabel(alert.alert_type) }}
                </span>
                <span class="text-xs text-gray-400">
                  {{ alert.fiscal_device?.name || alert.fiscal_device?.serial_number }}
                </span>
              </div>
              <p class="text-sm text-gray-800">{{ alert.description }}</p>
              <p class="text-xs text-gray-400 mt-1">
                {{ formatDateTime(alert.created_at) }}
                <span v-if="alert.user"> — {{ alert.user.name }}</span>
              </p>
            </div>
            <div class="flex gap-1 ml-4">
              <BaseButton
                v-if="alert.status === 'open'"
                size="sm"
                variant="primary-outline"
                @click="updateAlertStatus(alert.id, 'acknowledged')"
              >
                {{ $t('fiscal_monitor.acknowledge') }}
              </BaseButton>
              <BaseButton
                v-if="alert.status !== 'resolved' && alert.status !== 'false_positive'"
                size="sm"
                variant="primary-outline"
                @click="openResolveModal(alert)"
              >
                {{ $t('fiscal_monitor.resolve') }}
              </BaseButton>
            </div>
          </div>
        </BaseCard>

        <!-- Show More / Less -->
        <div v-if="dashboard.alerts.length > 5" class="text-center">
          <button
            class="text-sm text-indigo-600 hover:text-indigo-800"
            @click="showAllAlerts = !showAllAlerts"
          >
            {{ showAllAlerts ? $t('fiscal_monitor.show_less') : $t('fiscal_monitor.show_more') }}
            ({{ $t('fiscal_monitor.showing_of', { count: visibleAlerts.length, total: dashboard.alerts.length }) }})
          </button>
        </div>
      </div>

      <div v-else-if="!isLoading" class="text-center py-8 text-gray-400">
        {{ $t('fiscal_monitor.no_alerts') }}
      </div>
    </div>

    <!-- Log Event Button -->
    <div class="mt-8">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ $t('fiscal_monitor.log_event') }}</h3>
      <BaseCard class="p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('fiscal_monitor.device') }}</label>
            <select v-model="newEvent.fiscal_device_id" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
              <option value="">{{ $t('fiscal_monitor.select_device') }}</option>
              <option v-for="d in dashboard.devices" :key="d.device.id" :value="d.device.id">
                {{ d.device.name || d.device.serial_number }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('fiscal_monitor.event_type') }}</label>
            <select v-model="newEvent.event_type" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
              <option value="open">{{ $t('fiscal_monitor.event_open') }}</option>
              <option value="close">{{ $t('fiscal_monitor.event_close') }}</option>
              <option value="z_report">{{ $t('fiscal_monitor.event_z_report') }}</option>
              <option value="void">{{ $t('fiscal_monitor.event_void') }}</option>
              <option value="error">{{ $t('fiscal_monitor.event_error') }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('fiscal_monitor.cash_amount') }}</label>
            <input
              v-model.number="newEvent.cash_amount"
              type="number"
              class="w-full border-gray-300 rounded-md shadow-sm text-sm"
              :placeholder="$t('fiscal_monitor.amount_in_mkd')"
            />
          </div>
          <div class="flex items-end">
            <BaseButton
              variant="primary"
              :disabled="!newEvent.fiscal_device_id || !newEvent.event_type"
              @click="submitEvent"
            >
              {{ $t('fiscal_monitor.submit_event') }}
            </BaseButton>
          </div>
        </div>
        <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-4">
          <input
            v-model="newEvent.notes"
            type="text"
            class="w-full border-gray-300 rounded-md shadow-sm text-sm"
            :placeholder="$t('fiscal_monitor.notes_placeholder')"
          />
          <!-- Void reason dropdown (only when event_type is void) -->
          <select
            v-if="newEvent.event_type === 'void'"
            v-model="newEvent.void_reason"
            class="w-full border-gray-300 rounded-md shadow-sm text-sm"
          >
            <option value="">{{ $t('fiscal_monitor.void_reason') }}</option>
            <option value="customer_request">{{ $t('fiscal_monitor.reason_customer_request') }}</option>
            <option value="error">{{ $t('fiscal_monitor.reason_error') }}</option>
            <option value="duplicate">{{ $t('fiscal_monitor.reason_duplicate') }}</option>
            <option value="other">{{ $t('fiscal_monitor.reason_other') }}</option>
          </select>
        </div>
      </BaseCard>
    </div>

    <!-- Resolve Alert Modal -->
    <div
      v-if="showResolveModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="showResolveModal = false"
    >
      <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ $t('fiscal_monitor.resolution_modal_title') }}</h3>
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('fiscal_monitor.resolution_status') }}</label>
          <select v-model="resolveStatus" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
            <option value="resolved">{{ $t('fiscal_monitor.mark_resolved') }}</option>
            <option value="false_positive">{{ $t('fiscal_monitor.mark_false_positive') }}</option>
          </select>
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('fiscal_monitor.resolution_notes_prompt') }}</label>
          <textarea
            v-model="resolveNotes"
            rows="3"
            class="w-full border-gray-300 rounded-md shadow-sm text-sm"
          />
        </div>
        <div class="flex justify-end gap-2">
          <BaseButton variant="primary-outline" @click="showResolveModal = false">
            {{ $t('fiscal_monitor.cancel') }}
          </BaseButton>
          <BaseButton variant="primary" @click="confirmResolve">
            {{ $t('fiscal_monitor.confirm') }}
          </BaseButton>
        </div>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useNotificationStore } from '@/scripts/stores/notification'
import axios from 'axios'

const { t } = useI18n()
const companyStore = useCompanyStore()
const notificationStore = useNotificationStore()

const activeTab = ref('dashboard')
const isLoading = ref(true)
const dashboard = ref({ devices: [], alerts: [], summary: {} })
const newEvent = ref({ fiscal_device_id: '', event_type: 'open', cash_amount: null, notes: '', void_reason: '' })
const showAllAlerts = ref(false)
const showResolveModal = ref(false)
const resolveAlertId = ref(null)
const resolveNotes = ref('')
const resolveStatus = ref('resolved')
let refreshInterval = null

const visibleAlerts = computed(() => {
  const alerts = dashboard.value.alerts || []
  return showAllAlerts.value ? alerts : alerts.slice(0, 5)
})

onMounted(async () => {
  await refreshDashboard()
  refreshInterval = setInterval(refreshDashboard, 60000)
})

onUnmounted(() => {
  if (refreshInterval) clearInterval(refreshInterval)
})

async function refreshDashboard() {
  try {
    const res = await axios.get('fiscal-monitor/dashboard')
    if (res.data?.data) {
      dashboard.value = res.data.data
    }
  } catch (err) {
    console.error('Failed to load fiscal monitor dashboard:', err)
  } finally {
    isLoading.value = false
  }
}

async function submitEvent() {
  try {
    const payload = {
      fiscal_device_id: newEvent.value.fiscal_device_id,
      event_type: newEvent.value.event_type,
      notes: newEvent.value.notes || null,
    }
    if (newEvent.value.cash_amount) {
      payload.cash_amount = Math.round(newEvent.value.cash_amount * 100)
    }
    if (newEvent.value.event_type === 'void' && newEvent.value.void_reason) {
      payload.metadata = { void_reason: newEvent.value.void_reason }
    }

    await axios.post('fiscal-monitor/events', payload)

    notificationStore.showNotification({
      type: 'success',
      message: t('fiscal_monitor.event_logged'),
    })

    newEvent.value = { fiscal_device_id: '', event_type: 'open', cash_amount: null, notes: '', void_reason: '' }
    await refreshDashboard()
  } catch (err) {
    notificationStore.showNotification({
      type: 'error',
      message: err.response?.data?.message || t('general.something_went_wrong'),
    })
  }
}

async function updateAlertStatus(alertId, status, notes) {
  try {
    await axios.patch(`fiscal-monitor/alerts/${alertId}`, { status, resolution_notes: notes })
    await refreshDashboard()
  } catch (err) {
    notificationStore.showNotification({
      type: 'error',
      message: err.response?.data?.message || t('general.something_went_wrong'),
    })
  }
}

function openResolveModal(alert) {
  resolveAlertId.value = alert.id
  resolveNotes.value = ''
  resolveStatus.value = 'resolved'
  showResolveModal.value = true
}

async function confirmResolve() {
  await updateAlertStatus(resolveAlertId.value, resolveStatus.value, resolveNotes.value)
  showResolveModal.value = false
}

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
  }
  return classes[type] || 'bg-gray-100 text-gray-600'
}

function getAlertTypeLabel(type) {
  const labels = {
    unexpected_close: t('fiscal_monitor.alert_unexpected_close'),
    off_hours_activity: t('fiscal_monitor.alert_off_hours'),
    gap_in_receipts: t('fiscal_monitor.alert_gap_receipts'),
    cash_discrepancy: t('fiscal_monitor.alert_cash_discrepancy'),
    frequent_voids: t('fiscal_monitor.alert_frequent_voids'),
    no_z_report: t('fiscal_monitor.alert_no_z_report'),
    rapid_open_close: t('fiscal_monitor.alert_rapid_open_close'),
  }
  return labels[type] || type
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

function formatTime(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleTimeString('mk-MK', { hour: '2-digit', minute: '2-digit' })
}

function formatDateTime(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleString('mk-MK', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>

// CLAUDE-CHECKPOINT
