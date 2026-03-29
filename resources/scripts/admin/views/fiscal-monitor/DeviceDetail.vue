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
          class="inline-flex items-center px-2.5 py-1 rounded-full text-xs sm:text-sm font-medium"
          :class="deviceStatus === 'open' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'"
        >
          <span class="w-2 h-2 rounded-full mr-1.5" :class="deviceStatus === 'open' ? 'bg-green-500' : 'bg-gray-400'" />
          {{ deviceStatus === 'open' ? $t('fiscal_monitor.status_open') : $t('fiscal_monitor.status_closed') }}
        </span>
      </template>
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
        class="px-3 sm:px-4 py-2 text-sm font-medium border-b-2 -mb-px whitespace-nowrap border-transparent text-gray-500 hover:text-gray-700"
        @click="$router.push('/admin/fiscal-monitor/audit')"
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

    <!-- Device Info — 2 cols mobile, 4 desktop -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mt-5">
      <BaseCard class="p-3">
        <p class="text-xs text-gray-400">{{ $t('fiscal_monitor.serial') }}</p>
        <p class="text-sm font-medium text-gray-900 truncate">{{ device.serial_number }}</p>
      </BaseCard>
      <BaseCard class="p-3">
        <p class="text-xs text-gray-400">{{ $t('fiscal_monitor.type') }}</p>
        <p class="text-sm font-medium text-gray-900">{{ device.device_type }}</p>
      </BaseCard>
      <BaseCard class="p-3">
        <p class="text-xs text-gray-400">{{ $t('fiscal_monitor.connection') }}</p>
        <p class="text-sm font-medium text-gray-900">{{ device.connection_type }}</p>
      </BaseCard>
      <BaseCard class="p-3">
        <p class="text-xs text-gray-400">{{ $t('fiscal_monitor.last_event') }}</p>
        <p class="text-sm font-medium text-gray-900">
          {{ data.last_event ? formatDateTime(data.last_event.at) : '—' }}
        </p>
      </BaseCard>
    </div>

    <!-- Alerts for this device — shown first if any -->
    <div v-if="data.alerts?.length" class="mt-5">
      <h3 class="text-sm sm:text-base font-semibold text-gray-900 mb-2 flex items-center">
        {{ $t('fiscal_monitor.fraud_alerts') }}
        <span class="ml-2 px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs">{{ data.alerts.length }}</span>
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
          <div class="flex flex-col sm:flex-row sm:items-center gap-1.5">
            <span class="px-2 py-0.5 rounded-full text-xs font-medium self-start" :class="getSeverityClass(alert.severity)">
              {{ alert.severity.toUpperCase() }}
            </span>
            <span class="text-sm text-gray-800 flex-1">{{ alert.description }}</span>
            <span class="text-xs text-gray-400">{{ formatDateTime(alert.created_at) }}</span>
          </div>
        </BaseCard>
      </div>
    </div>

    <!-- Collapsible Sections -->
    <div class="mt-5 space-y-3">

      <!-- Event Log (most used — open by default) -->
      <BaseCard class="overflow-hidden">
        <button
          class="w-full flex items-center justify-between p-3 sm:p-4 text-left hover:bg-gray-50"
          @click="sections.events = !sections.events"
        >
          <h3 class="text-sm sm:text-base font-semibold text-gray-900">{{ $t('fiscal_monitor.event_log') }}</h3>
          <svg
            class="w-4 h-4 text-gray-400 transition-transform flex-shrink-0"
            :class="sections.events ? 'rotate-180' : ''"
            fill="none" stroke="currentColor" viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <div v-if="sections.events" class="px-3 sm:px-4 pb-3 sm:pb-4 border-t border-gray-100">
          <!-- Filter -->
          <div class="py-2">
            <select v-model="eventFilter" class="border-gray-300 rounded-md shadow-sm text-sm w-full sm:w-auto">
              <option value="">{{ $t('fiscal_monitor.all_events') }}</option>
              <option value="open">{{ $t('fiscal_monitor.event_open') }}</option>
              <option value="close">{{ $t('fiscal_monitor.event_close') }}</option>
              <option value="receipt">{{ $t('fiscal_monitor.event_receipt') }}</option>
              <option value="void">{{ $t('fiscal_monitor.event_void') }}</option>
              <option value="z_report">{{ $t('fiscal_monitor.event_z_report') }}</option>
              <option value="error">{{ $t('fiscal_monitor.event_error') }}</option>
            </select>
          </div>

          <div class="space-y-1">
            <div
              v-for="event in paginatedEvents"
              :key="event.id"
              class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3 py-2 border-b border-gray-50 last:border-0"
            >
              <!-- Mobile: badge + time on one line, user + details on next -->
              <div class="flex items-center gap-2 sm:contents">
                <span
                  class="px-1.5 py-0.5 rounded text-xs font-medium min-w-[50px] text-center flex-shrink-0"
                  :class="getEventBadgeClass(event.event_type)"
                >
                  {{ getEventLabel(event.event_type) }}
                </span>
                <span class="text-xs sm:text-sm text-gray-500 sm:min-w-[120px]">{{ formatDateTime(event.event_at) }}</span>
                <span class="text-xs sm:text-sm text-gray-700 flex-1 truncate">
                  {{ event.user ? event.user.name : 'Систем' }}
                </span>
              </div>
              <div class="flex items-center gap-2 sm:contents pl-14 sm:pl-0">
                <span v-if="event.cash_amount" class="text-xs text-gray-500">
                  <BaseFormatMoney :amount="event.cash_amount" :currency="companyStore.selectedCompanyCurrency" />
                </span>
                <span v-if="event.notes" class="text-xs text-gray-400 truncate max-w-[200px]" :title="event.notes">
                  {{ event.notes }}
                </span>
              </div>
            </div>

            <div v-if="!filteredEvents.length" class="text-center py-6 text-gray-400 text-sm">
              {{ $t('fiscal_monitor.no_events') }}
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="totalEventPages > 1" class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100">
            <button
              class="text-sm text-indigo-600 hover:text-indigo-800 disabled:text-gray-300"
              :disabled="eventPage === 1"
              @click="eventPage--"
            >
              {{ $t('fiscal_monitor.previous') }}
            </button>
            <span class="text-xs text-gray-500">
              {{ $t('fiscal_monitor.page_of', { page: eventPage, total: totalEventPages }) }}
            </span>
            <button
              class="text-sm text-indigo-600 hover:text-indigo-800 disabled:text-gray-300"
              :disabled="eventPage >= totalEventPages"
              @click="eventPage++"
            >
              {{ $t('fiscal_monitor.next') }}
            </button>
          </div>
        </div>
      </BaseCard>

      <!-- Daily Stats (collapsed by default) -->
      <BaseCard v-if="data.daily_stats?.length" class="overflow-hidden">
        <button
          class="w-full flex items-center justify-between p-3 sm:p-4 text-left hover:bg-gray-50"
          @click="sections.stats = !sections.stats"
        >
          <h3 class="text-sm sm:text-base font-semibold text-gray-900">{{ $t('fiscal_monitor.daily_summary') }}</h3>
          <svg
            class="w-4 h-4 text-gray-400 transition-transform flex-shrink-0"
            :class="sections.stats ? 'rotate-180' : ''"
            fill="none" stroke="currentColor" viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <div v-if="sections.stats" class="border-t border-gray-100">
          <!-- Mobile: card list. Desktop: table -->
          <div class="hidden sm:block overflow-x-auto">
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

          <!-- Mobile card list -->
          <div class="sm:hidden divide-y divide-gray-100">
            <div v-for="day in data.daily_stats" :key="day.date" class="p-3">
              <p class="text-sm font-medium text-gray-900 mb-1">{{ day.date }}</p>
              <div class="grid grid-cols-3 gap-2 text-xs">
                <div>
                  <span class="text-gray-400">{{ $t('fiscal_monitor.event_receipt') }}</span>
                  <p class="font-medium text-indigo-600">{{ day.receipts }}</p>
                </div>
                <div>
                  <span class="text-gray-400">{{ $t('fiscal_monitor.event_void') }}</span>
                  <p class="font-medium" :class="day.voids > 3 ? 'text-red-600' : ''">{{ day.voids }}</p>
                </div>
                <div>
                  <span class="text-gray-400">{{ $t('fiscal_monitor.revenue') }}</span>
                  <p class="font-medium">
                    <BaseFormatMoney :amount="day.revenue" :currency="companyStore.selectedCompanyCurrency" />
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </BaseCard>

      <!-- Operators (collapsed by default) -->
      <BaseCard v-if="data.operators?.length" class="overflow-hidden">
        <button
          class="w-full flex items-center justify-between p-3 sm:p-4 text-left hover:bg-gray-50"
          @click="sections.operators = !sections.operators"
        >
          <h3 class="text-sm sm:text-base font-semibold text-gray-900">
            {{ $t('fiscal_monitor.operators') }}
            <span class="text-xs text-gray-400 font-normal ml-1">({{ data.operators.length }})</span>
          </h3>
          <svg
            class="w-4 h-4 text-gray-400 transition-transform flex-shrink-0"
            :class="sections.operators ? 'rotate-180' : ''"
            fill="none" stroke="currentColor" viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <div v-if="sections.operators" class="border-t border-gray-100">
          <!-- Desktop table -->
          <div class="hidden sm:block overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="border-b border-gray-200">
                  <th class="text-left py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.employee') }}</th>
                  <th class="text-center py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.total_events') }}</th>
                  <th class="text-left py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.first_seen') }}</th>
                  <th class="text-left py-2 px-3 text-gray-600 font-medium">{{ $t('fiscal_monitor.last_seen') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="op in data.operators" :key="op.user_id" class="border-b border-gray-100 hover:bg-gray-50">
                  <td class="py-2 px-3 font-medium">{{ op.user_name || 'Систем' }}</td>
                  <td class="py-2 px-3 text-center">{{ op.event_count }}</td>
                  <td class="py-2 px-3 text-gray-500">{{ formatDateTime(op.first_event) }}</td>
                  <td class="py-2 px-3 text-gray-500">{{ formatDateTime(op.last_event) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Mobile card list -->
          <div class="sm:hidden divide-y divide-gray-100">
            <div v-for="op in data.operators" :key="op.user_id" class="p-3">
              <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-gray-900">{{ op.user_name || 'Систем' }}</p>
                <span class="text-xs bg-gray-100 text-gray-600 rounded-full px-2 py-0.5">{{ op.event_count }} {{ $t('fiscal_monitor.events_short') }}</span>
              </div>
              <p class="text-xs text-gray-400 mt-0.5">{{ formatDateTime(op.last_event) }}</p>
            </div>
          </div>
        </div>
      </BaseCard>

      <!-- Business Hours Config (collapsed, settings-like) -->
      <BaseCard class="overflow-hidden">
        <button
          class="w-full flex items-center justify-between p-3 sm:p-4 text-left hover:bg-gray-50"
          @click="sections.settings = !sections.settings"
        >
          <h3 class="text-sm sm:text-base font-semibold text-gray-900 flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            {{ $t('fiscal_monitor.device_settings') }}
          </h3>
          <svg
            class="w-4 h-4 text-gray-400 transition-transform flex-shrink-0"
            :class="sections.settings ? 'rotate-180' : ''"
            fill="none" stroke="currentColor" viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <div v-if="sections.settings" class="px-3 sm:px-4 pb-3 sm:pb-4 border-t border-gray-100 pt-3">
          <p class="text-xs text-gray-400 mb-3">{{ $t('fiscal_monitor.business_hours_desc') }}</p>
          <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
            <div class="flex items-center gap-3">
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('fiscal_monitor.opening_hour') }}</label>
                <input
                  v-model.number="businessHours.open"
                  type="number"
                  min="0"
                  max="23"
                  class="w-20 border-gray-300 rounded-md shadow-sm text-sm"
                />
              </div>
              <span class="text-gray-400 mt-5">—</span>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('fiscal_monitor.closing_hour') }}</label>
                <input
                  v-model.number="businessHours.close"
                  type="number"
                  min="0"
                  max="23"
                  class="w-20 border-gray-300 rounded-md shadow-sm text-sm"
                />
              </div>
            </div>
            <div class="flex items-center gap-2 sm:mt-5">
              <BaseButton size="sm" variant="primary" @click="saveBusinessHours">
                {{ $t('fiscal_monitor.save') }}
              </BaseButton>
              <span v-if="savedMsg" class="text-xs text-green-600">{{ savedMsg }}</span>
            </div>
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
import { ref, computed, onMounted, watch } from 'vue'
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
const eventPage = ref(1)
const eventsPerPage = 20
const businessHours = ref({ open: 8, close: 20 })
const savedMsg = ref('')

// Accordion sections: events open by default, rest collapsed
const sections = ref({
  events: true,
  stats: false,
  operators: false,
  settings: false,
})

const filteredEvents = computed(() => {
  const events = data.value.recent_events || []
  if (!eventFilter.value) return events
  return events.filter(e => e.event_type === eventFilter.value)
})

const totalEventPages = computed(() => {
  return Math.max(1, Math.ceil(filteredEvents.value.length / eventsPerPage))
})

const paginatedEvents = computed(() => {
  const start = (eventPage.value - 1) * eventsPerPage
  return filteredEvents.value.slice(start, start + eventsPerPage)
})

watch(eventFilter, () => {
  eventPage.value = 1
})

onMounted(async () => {
  try {
    const res = await axios.get(`fiscal-monitor/devices/${route.params.id}`)
    if (res.data?.data) {
      data.value = res.data.data
      device.value = res.data.data.device || {}
      deviceStatus.value = res.data.data.status || 'closed'

      if (device.value.metadata?.business_hours) {
        businessHours.value = {
          open: device.value.metadata.business_hours.open ?? 8,
          close: device.value.metadata.business_hours.close ?? 20,
        }
      }
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

async function saveBusinessHours() {
  try {
    await axios.patch(`fiscal-devices/${route.params.id}`, {
      metadata: { business_hours: businessHours.value },
    })
    savedMsg.value = t('fiscal_monitor.saved_successfully')
    setTimeout(() => { savedMsg.value = '' }, 3000)
  } catch (err) {
    console.error('Failed to save business hours:', err)
  }
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
