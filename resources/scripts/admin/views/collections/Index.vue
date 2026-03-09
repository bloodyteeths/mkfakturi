<template>
  <BasePage>
    <BasePageHeader :title="t('title')">
      <template #actions>
        <BaseButton
          variant="primary-outline"
          @click="activeTab = 'templates'"
          v-if="activeTab !== 'templates'"
        >
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="DocumentTextIcon" />
          </template>
          {{ t('templates') }}
        </BaseButton>
        <BaseButton
          variant="primary-outline"
          @click="activeTab = 'history'"
          v-if="activeTab !== 'history'"
        >
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="ClockIcon" />
          </template>
          {{ t('history') }}
        </BaseButton>
        <BaseButton
          variant="primary"
          @click="activeTab = 'overdue'"
          v-if="activeTab !== 'overdue'"
        >
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="ExclamationTriangleIcon" />
          </template>
          {{ t('total_overdue') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Summary Cards -->
    <div v-if="summary" class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase">{{ t('total_overdue') }}</p>
        <p class="text-2xl font-bold text-red-600">{{ formatMoney(summary.total_overdue_amount) }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase">{{ t('invoice_count') }}</p>
        <p class="text-2xl font-bold text-gray-900">{{ summary.invoice_count || 0 }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase">{{ t('customer_count') }}</p>
        <p class="text-2xl font-bold text-gray-900">{{ summary.customer_count || 0 }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase">{{ t('avg_days') }}</p>
        <p class="text-2xl font-bold text-amber-600">{{ summary.avg_days_overdue || 0 }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase">{{ t('total_interest') }}</p>
        <p class="text-2xl font-bold text-red-500">{{ formatMoney(summary.total_interest) }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ summary.interest_rate || 0 }}% {{ t('interest_rate') }}</p>
      </div>
    </div>

    <!-- Overdue Invoices Tab -->
    <div v-if="activeTab === 'overdue'">
      <!-- Filters -->
      <div class="p-4 bg-white rounded-lg shadow mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <BaseInputGroup :label="t('filter_level')">
            <BaseMultiselect
              v-model="filters.escalation_level"
              :options="levelOptions"
              :searchable="false"
              label="label"
              value-prop="value"
              :placeholder="$t('general.all')"
            />
          </BaseInputGroup>
          <BaseInputGroup :label="t('customer')">
            <BaseInput
              v-model="filters.search"
              :placeholder="t('search_placeholder')"
              type="text"
              @input="debouncedLoadOverdue"
            />
          </BaseInputGroup>
          <div class="flex items-end">
            <BaseButton variant="primary-outline" @click="loadOverdue">
              {{ $t('general.filter') }}
            </BaseButton>
          </div>
        </div>
      </div>

      <!-- Aging Report -->
      <div v-if="aging" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-3 border-l-4 border-green-400">
          <p class="text-xs text-gray-500">{{ t('aging_0_30') }}</p>
          <p class="text-lg font-bold text-gray-900">{{ formatMoney(aging['0_30']) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-3 border-l-4 border-yellow-400">
          <p class="text-xs text-gray-500">{{ t('aging_31_60') }}</p>
          <p class="text-lg font-bold text-gray-900">{{ formatMoney(aging['31_60']) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-3 border-l-4 border-orange-400">
          <p class="text-xs text-gray-500">{{ t('aging_61_90') }}</p>
          <p class="text-lg font-bold text-gray-900">{{ formatMoney(aging['61_90']) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-3 border-l-4 border-red-400">
          <p class="text-xs text-gray-500">{{ t('aging_90_plus') }}</p>
          <p class="text-lg font-bold text-gray-900">{{ formatMoney(aging['90_plus']) }}</p>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="isLoading" class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6 space-y-4">
          <div v-for="i in 5" :key="i" class="flex space-x-4 animate-pulse">
            <div class="h-4 bg-gray-200 rounded w-24"></div>
            <div class="h-4 bg-gray-200 rounded w-20"></div>
            <div class="h-4 bg-gray-200 rounded flex-1"></div>
            <div class="h-4 bg-gray-200 rounded w-16"></div>
            <div class="h-4 bg-gray-200 rounded w-20"></div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="overdueInvoices.length === 0" class="text-center py-16 bg-white rounded-lg shadow">
        <BaseIcon name="CheckCircleIcon" class="h-12 w-12 text-green-400 mx-auto mb-4" />
        <h3 class="text-lg font-medium text-gray-900">{{ t('no_overdue') }}</h3>
        <p class="text-sm text-gray-500 mt-1">{{ t('no_overdue_description') }}</p>
      </div>

      <!-- Table -->
      <div v-else class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('customer') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('invoice_number') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('due_date') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('amount_due') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('interest') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('total_with_interest') }}</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ t('days_overdue') }}</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ t('escalation') }}</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ t('reminders_sent') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('general.actions') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="inv in overdueInvoices" :key="inv.id" class="hover:bg-gray-50">
                <td class="px-4 py-3 text-sm text-gray-900">{{ inv.customer_name }}</td>
                <td class="px-4 py-3 text-sm text-gray-600">{{ inv.invoice_number }}</td>
                <td class="px-4 py-3 text-sm text-gray-600">{{ formatDate(inv.due_date) }}</td>
                <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">{{ formatMoney(inv.due_amount) }}</td>
                <td class="px-4 py-3 text-sm text-right text-red-600">{{ formatMoney(inv.interest) }}</td>
                <td class="px-4 py-3 text-sm text-right font-bold text-red-700">{{ formatMoney(inv.total_with_interest) }}</td>
                <td class="px-4 py-3 text-center">
                  <span :class="daysOverdueClass(inv.days_overdue)" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium">
                    {{ inv.days_overdue }}
                  </span>
                </td>
                <td class="px-4 py-3 text-center">
                  <span :class="levelBadgeClass(inv.escalation_level)" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium">
                    {{ t(inv.escalation_level) }}
                  </span>
                </td>
                <td class="px-4 py-3 text-center text-sm text-gray-500">{{ inv.reminder_count || 0 }}</td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                  <div class="flex items-center justify-end gap-1">
                    <BaseButton
                      size="sm"
                      variant="primary"
                      @click="openSendDialog(inv)"
                      :disabled="!inv.can_send"
                      :title="inv.can_send ? '' : t('cooldown_active')"
                    >
                      {{ t('send_reminder') }}
                    </BaseButton>
                    <BaseButton
                      size="sm"
                      variant="primary-outline"
                      @click="downloadOpomena(inv.id)"
                      :title="t('download_opomena')"
                    >
                      {{ t('opomena') }}
                    </BaseButton>
                  </div>
                  <p v-if="!inv.can_send" class="text-xs text-amber-600 mt-1">{{ t('cooldown_active') }}</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination && pagination.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-200 bg-gray-50">
          <p class="text-sm text-gray-500">{{ pagination.total }} {{ t('invoice_count').toLowerCase() }}</p>
          <div class="flex items-center gap-2">
            <BaseButton size="sm" variant="primary-outline" :disabled="pagination.page <= 1" @click="goToPage(pagination.page - 1)">&laquo;</BaseButton>
            <span class="text-sm text-gray-700">{{ pagination.page }} / {{ pagination.last_page }}</span>
            <BaseButton size="sm" variant="primary-outline" :disabled="pagination.page >= pagination.last_page" @click="goToPage(pagination.page + 1)">&raquo;</BaseButton>
          </div>
        </div>
      </div>
    </div>

    <!-- Templates Tab -->
    <div v-if="activeTab === 'templates'">
      <CollectionTemplates ref="templatesRef" />
    </div>

    <!-- History Tab -->
    <div v-if="activeTab === 'history'">
      <CollectionHistory ref="historyRef" />
    </div>

    <!-- Send Reminder Dialog -->
    <BaseModal :show="showSendDialog" @close="showSendDialog = false">
      <template #header>
        <h3 class="text-lg font-medium">{{ t('confirm_send_title') }}</h3>
      </template>
      <div v-if="selectedInvoice" class="space-y-4">
        <p class="text-sm text-gray-600">{{ t('confirm_send') }}</p>
        <div class="bg-gray-50 rounded p-3">
          <p class="text-sm"><strong>{{ t('customer') }}:</strong> {{ selectedInvoice.customer_name }}</p>
          <p class="text-sm"><strong>{{ t('invoice_number') }}:</strong> {{ selectedInvoice.invoice_number }}</p>
          <p class="text-sm"><strong>{{ t('amount_due') }}:</strong> {{ formatMoney(selectedInvoice.due_amount) }}</p>
          <p class="text-sm"><strong>{{ t('interest') }}:</strong> {{ formatMoney(selectedInvoice.interest) }}</p>
          <p class="text-sm font-bold"><strong>{{ t('total_with_interest') }}:</strong> {{ formatMoney(selectedInvoice.total_with_interest) }}</p>
        </div>
        <BaseInputGroup :label="t('escalation')">
          <BaseMultiselect
            v-model="sendLevel"
            :options="levelOptions.filter(l => l.value)"
            label="label"
            value-prop="value"
          />
        </BaseInputGroup>
      </div>
      <template #footer>
        <BaseButton variant="primary-outline" @click="showSendDialog = false">{{ $t('general.cancel') }}</BaseButton>
        <BaseButton variant="primary" :loading="isSending" @click="confirmSend">{{ t('send_reminder') }}</BaseButton>
      </template>
    </BaseModal>
  </BasePage>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useNotificationStore } from '@/scripts/stores/notification'
import collectionMessages from '@/scripts/admin/i18n/collections.js'
import CollectionTemplates from './Templates.vue'
import CollectionHistory from './History.vue'

const notificationStore = useNotificationStore()

const locale = document.documentElement.lang || 'mk'
function t(key) {
  return collectionMessages[locale]?.collections?.[key]
    || collectionMessages['en']?.collections?.[key]
    || key
}

const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }
const fmtLocale = localeMap[locale] || 'mk-MK'

const activeTab = ref('overdue')
const isLoading = ref(false)
const isSending = ref(false)
const overdueInvoices = ref([])
const summary = ref(null)
const aging = ref(null)
const pagination = ref(null)
const showSendDialog = ref(false)
const selectedInvoice = ref(null)
const sendLevel = ref('friendly')

const filters = reactive({
  escalation_level: null,
  search: '',
  page: 1,
})

const levelOptions = [
  { value: null, label: t('level_all') || 'All' },
  { value: 'friendly', label: t('level_friendly') || 'Friendly' },
  { value: 'firm', label: t('level_firm') || 'Firm' },
  { value: 'final', label: t('level_final') || 'Final' },
  { value: 'legal', label: t('level_legal') || 'Legal' },
]

// Debounce search
let searchTimeout = null
function debouncedLoadOverdue() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    filters.page = 1
    loadOverdue()
  }, 400)
}

function formatMoney(cents) {
  if (cents === null || cents === undefined) return '0.00'
  return (cents / 100).toLocaleString(fmtLocale, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function formatDate(d) {
  if (!d) return '-'
  return new Date(d).toLocaleDateString(fmtLocale, { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function daysOverdueClass(days) {
  if (days > 60) return 'bg-red-100 text-red-800'
  if (days > 30) return 'bg-orange-100 text-orange-800'
  if (days > 7) return 'bg-yellow-100 text-yellow-800'
  return 'bg-gray-100 text-gray-800'
}

function levelBadgeClass(level) {
  const map = {
    friendly: 'bg-blue-100 text-blue-800',
    firm: 'bg-yellow-100 text-yellow-800',
    final: 'bg-orange-100 text-orange-800',
    legal: 'bg-red-100 text-red-800',
  }
  return map[level] || 'bg-gray-100 text-gray-800'
}

async function loadOverdue() {
  isLoading.value = true
  try {
    const params = {}
    if (filters.escalation_level) params.escalation_level = filters.escalation_level
    if (filters.search) params.search = filters.search
    params.page = filters.page
    const { data } = await window.axios.get('/collections/overdue', { params })
    overdueInvoices.value = data.data || []
    summary.value = data.summary || null
    aging.value = data.aging || null
    pagination.value = data.pagination || null
  } catch (e) {
    console.error('Failed to load overdue invoices', e)
    notificationStore.showNotification({
      type: 'error',
      message: t('error_loading') || 'Failed to load overdue invoices',
    })
  } finally {
    isLoading.value = false
  }
}

function goToPage(page) {
  filters.page = page
  loadOverdue()
}

function openSendDialog(inv) {
  selectedInvoice.value = inv
  sendLevel.value = inv.escalation_level || 'friendly'
  showSendDialog.value = true
}

async function confirmSend() {
  if (!selectedInvoice.value) return
  isSending.value = true
  try {
    await window.axios.post('/collections/send-reminder', {
      invoice_id: selectedInvoice.value.id,
      level: sendLevel.value,
    })
    showSendDialog.value = false
    notificationStore.showNotification({
      type: 'success',
      message: t('reminder_sent_success') || 'Reminder sent successfully.',
    })
    loadOverdue()
  } catch (e) {
    console.error('Failed to send reminder', e)
    const msg = e.response?.data?.message || t('error_sending') || 'Failed to send reminder'
    notificationStore.showNotification({ type: 'error', message: msg })
  } finally {
    isSending.value = false
  }
}

async function downloadOpomena(invoiceId) {
  try {
    const response = await window.axios.get(`/collections/opomena/${invoiceId}`, {
      responseType: 'blob',
    })
    if (response.data.type && !response.data.type.includes('pdf')) {
      const text = await response.data.text()
      const err = JSON.parse(text)
      notificationStore.showNotification({ type: 'error', message: err.message || t('error_loading') })
      return
    }
    const url = URL.createObjectURL(response.data)
    const a = document.createElement('a')
    a.href = url
    a.download = `opomena-${invoiceId}.pdf`
    a.click()
    URL.revokeObjectURL(url)
  } catch (e) {
    console.error('Failed to download opomena', e)
    let msg = t('error_loading')
    if (e.response?.data instanceof Blob) {
      try {
        const text = await e.response.data.text()
        const err = JSON.parse(text)
        msg = err.message || msg
      } catch (_) {}
    }
    notificationStore.showNotification({ type: 'error', message: msg })
  }
}

onMounted(() => {
  loadOverdue()
})
</script>

<!-- CLAUDE-CHECKPOINT -->
