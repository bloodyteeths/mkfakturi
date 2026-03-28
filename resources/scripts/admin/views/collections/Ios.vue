<template>
  <div>
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-medium text-gray-900">{{ t('ios_title') }}</h3>
      <div class="flex items-center gap-2">
        <BaseButton size="sm" variant="primary-outline" @click="exportIosCsv" :loading="isExportingCsv">
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="ArrowDownTrayIcon" />
          </template>
          CSV
        </BaseButton>
        <BaseButton size="sm" variant="primary" @click="bulkSendIos" :loading="isBulkSending" :disabled="selectedCustomerIds.length === 0">
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="PaperAirplaneIcon" />
          </template>
          {{ t('ios_send_all') }} {{ selectedCustomerIds.length > 0 ? `(${selectedCustomerIds.length})` : '' }}
        </BaseButton>
      </div>
    </div>

    <!-- Filters -->
    <div class="p-4 bg-white rounded-lg shadow mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <BaseInputGroup :label="t('customer')">
          <BaseInput v-model="filters.search" :placeholder="t('search_placeholder')" type="text" @input="debouncedLoad" />
        </BaseInputGroup>
        <label class="flex items-center gap-2 self-end pb-1">
          <input type="checkbox" v-model="filters.include_current" @change="loadIos" class="rounded border-gray-300" />
          <span class="text-sm text-gray-700">{{ t('ios_include_current') }}</span>
        </label>
        <div class="flex items-end">
          <BaseButton variant="primary-outline" @click="loadIos">{{ $t('general.filter') }}</BaseButton>
        </div>
      </div>
    </div>

    <!-- Grand Totals -->
    <div v-if="data" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase">{{ t('customer_count') }}</p>
        <p class="text-2xl font-bold text-gray-900">{{ data.customer_count || 0 }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase">{{ t('invoice_count') }}</p>
        <p class="text-2xl font-bold text-gray-900">{{ data.invoice_count || 0 }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase">{{ t('ios_total_due') }}</p>
        <p class="text-2xl font-bold text-red-600">{{ formatMoney(data.grand_total_due) }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase">{{ t('total_with_interest') }}</p>
        <p class="text-2xl font-bold text-red-700">{{ formatMoney(data.grand_total_with_interest) }}</p>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="bg-white rounded-lg shadow p-6">
      <div v-for="i in 3" :key="i" class="flex space-x-4 animate-pulse mb-4">
        <div class="h-4 bg-gray-200 rounded w-32"></div>
        <div class="h-4 bg-gray-200 rounded flex-1"></div>
        <div class="h-4 bg-gray-200 rounded w-20"></div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else-if="!data || (data.customers || []).length === 0" class="text-center py-16 bg-white rounded-lg shadow">
      <BaseIcon name="CheckCircleIcon" class="h-12 w-12 text-green-400 mx-auto mb-4" />
      <h3 class="text-lg font-medium text-gray-900">{{ t('ios_no_items') }}</h3>
      <p class="text-sm text-gray-500 mt-1">{{ t('ios_no_items_desc') }}</p>
    </div>

    <!-- Customer Groups -->
    <div v-else class="space-y-6">
      <div v-for="cust in data.customers" :key="cust.customer_id" class="bg-white rounded-lg shadow overflow-hidden">
        <!-- Customer Header -->
        <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-200">
          <div class="flex items-center gap-3">
            <input type="checkbox" :checked="selectedCustomerIds.includes(cust.customer_id)" @change="toggleCustomer(cust.customer_id)" class="rounded border-gray-300" />
            <div>
              <p class="font-medium text-gray-900">{{ cust.customer_name }}</p>
              <p v-if="cust.customer_email" class="text-xs text-gray-500">{{ cust.customer_email }}</p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-sm font-bold text-red-600">{{ formatMoney(cust.subtotal_total_with_interest) }}</span>
            <BaseButton size="sm" variant="primary-outline" @click="downloadIosPdf(cust.customer_id)" :loading="downloadingIos === cust.customer_id">
              PDF
            </BaseButton>
            <BaseButton size="sm" variant="primary-outline" @click="downloadInterestNote(cust.customer_id)" :loading="downloadingInterest === cust.customer_id" :title="t('interest_note')">
              {{ t('interest_note_short') }}
            </BaseButton>
          </div>
        </div>
        <!-- Items Table -->
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">#</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('invoice_number') }}</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('ios_invoice_date') }}</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('due_date') }}</th>
              <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('ios_total') }}</th>
              <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('ios_paid') }}</th>
              <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('ios_remaining') }}</th>
              <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('days_overdue') }}</th>
              <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('interest') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="(item, idx) in cust.items" :key="item.id" class="hover:bg-gray-50">
              <td class="px-4 py-2 text-sm text-gray-500">{{ idx + 1 }}</td>
              <td class="px-4 py-2 text-sm">
                <router-link :to="`/admin/invoices/${item.id}/view`" class="text-primary-500 hover:text-primary-700 font-medium">
                  {{ item.invoice_number }}
                </router-link>
              </td>
              <td class="px-4 py-2 text-sm text-gray-600">{{ formatDate(item.invoice_date) }}</td>
              <td class="px-4 py-2 text-sm text-gray-600">{{ formatDate(item.due_date) }}</td>
              <td class="px-4 py-2 text-sm text-right text-gray-900">{{ formatMoney(item.total) }}</td>
              <td class="px-4 py-2 text-sm text-right text-green-600">{{ formatMoney(item.total - item.due_amount) }}</td>
              <td class="px-4 py-2 text-sm text-right font-medium text-gray-900">{{ formatMoney(item.due_amount) }}</td>
              <td class="px-4 py-2 text-sm text-right">
                <span v-if="item.days_overdue > 0" :class="daysClass(item.days_overdue)" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium">
                  {{ item.days_overdue }}
                </span>
                <span v-else class="text-gray-400">-</span>
              </td>
              <td class="px-4 py-2 text-sm text-right text-red-600">{{ item.interest > 0 ? formatMoney(item.interest) : '-' }}</td>
            </tr>
          </tbody>
          <tfoot class="bg-gray-50">
            <tr class="font-bold">
              <td colspan="6" class="px-4 py-2 text-sm text-right text-gray-700">{{ t('ios_subtotal') }}:</td>
              <td class="px-4 py-2 text-sm text-right text-gray-900">{{ formatMoney(cust.subtotal_due) }}</td>
              <td></td>
              <td class="px-4 py-2 text-sm text-right text-red-600">{{ formatMoney(cust.subtotal_interest) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'
import collectionMessages from '@/scripts/admin/i18n/collections.js'

const { locale } = useI18n()
const notificationStore = useNotificationStore()

function t(key) {
  const loc = locale.value || 'mk'
  return collectionMessages[loc]?.collections?.[key]
    || collectionMessages['en']?.collections?.[key]
    || key
}

const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }
const fmtLocale = computed(() => localeMap[locale.value] || 'mk-MK')

const data = ref(null)
const isLoading = ref(false)
const isBulkSending = ref(false)
const isExportingCsv = ref(false)
const downloadingIos = ref(null)
const downloadingInterest = ref(null)
const selectedCustomerIds = ref([])

const filters = reactive({ search: '', include_current: false })

let searchTimeout = null
function debouncedLoad() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(loadIos, 400)
}

function formatMoney(cents) {
  if (cents === null || cents === undefined) return '0 ден.'
  return (cents / 100).toLocaleString(fmtLocale.value, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ден.'
}

function formatDate(d) {
  if (!d) return '-'
  return new Date(d).toLocaleDateString(fmtLocale.value, { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function daysClass(days) {
  if (days > 60) return 'bg-red-100 text-red-800'
  if (days > 30) return 'bg-orange-100 text-orange-800'
  if (days > 7) return 'bg-yellow-100 text-yellow-800'
  return 'bg-gray-100 text-gray-800'
}

function toggleCustomer(id) {
  const idx = selectedCustomerIds.value.indexOf(id)
  if (idx >= 0) selectedCustomerIds.value.splice(idx, 1)
  else selectedCustomerIds.value.push(id)
}

async function loadIos() {
  isLoading.value = true
  try {
    const params = {}
    if (filters.search) params.search = filters.search
    if (filters.include_current) params.include_current = 1
    const { data: res } = await window.axios.get('/collections/ios', { params })
    data.value = res.data || res
  } catch (e) {
    console.error('Failed to load IOS', e)
    notificationStore.showNotification({ type: 'error', message: t('error_loading') })
  } finally {
    isLoading.value = false
  }
}

async function downloadIosPdf(customerId) {
  downloadingIos.value = customerId
  try {
    const response = await window.axios.get(`/collections/ios/${customerId}/pdf`, { responseType: 'blob' })
    const url = URL.createObjectURL(response.data)
    const a = document.createElement('a')
    a.href = url
    a.download = `ios-${customerId}-${new Date().toISOString().slice(0,10)}.pdf`
    a.click()
    URL.revokeObjectURL(url)
  } catch (e) {
    console.error('Failed to download IOS PDF', e)
    notificationStore.showNotification({ type: 'error', message: t('error_loading') })
  } finally {
    downloadingIos.value = null
  }
}

async function downloadInterestNote(customerId) {
  downloadingInterest.value = customerId
  try {
    const response = await window.axios.get(`/collections/interest-note/${customerId}/pdf`, { responseType: 'blob' })
    const url = URL.createObjectURL(response.data)
    const a = document.createElement('a')
    a.href = url
    a.download = `kamatna-nota-${customerId}-${new Date().toISOString().slice(0,10)}.pdf`
    a.click()
    URL.revokeObjectURL(url)
  } catch (e) {
    console.error('Failed to download interest note', e)
    notificationStore.showNotification({ type: 'error', message: t('error_loading') })
  } finally {
    downloadingInterest.value = null
  }
}

async function bulkSendIos() {
  if (selectedCustomerIds.value.length === 0) return
  if (!confirm(t('ios_confirm_send') + ` (${selectedCustomerIds.value.length})?`)) return
  isBulkSending.value = true
  try {
    const { data: res } = await window.axios.post('/collections/ios/bulk-send', {
      customer_ids: selectedCustomerIds.value,
    })
    const sent = res.sent || 0
    const failed = res.failed || 0
    notificationStore.showNotification({
      type: sent > 0 ? 'success' : 'error',
      message: `${t('ios_sent')}: ${sent}` + (failed > 0 ? `, ${t('ios_failed')}: ${failed}` : ''),
    })
    selectedCustomerIds.value = []
  } catch (e) {
    console.error('Failed to bulk send IOS', e)
    notificationStore.showNotification({ type: 'error', message: t('error_sending') })
  } finally {
    isBulkSending.value = false
  }
}

async function exportIosCsv() {
  if (!data.value?.customers) return
  isExportingCsv.value = true
  try {
    const headers = [t('customer'), t('invoice_number'), t('ios_invoice_date'), t('due_date'), t('ios_total'), t('ios_paid'), t('ios_remaining'), t('days_overdue'), t('interest')]
    const rows = [headers.join(',')]
    for (const cust of data.value.customers) {
      for (const item of cust.items) {
        rows.push([
          `"${(cust.customer_name || '').replace(/"/g, '""')}"`,
          `"${item.invoice_number}"`,
          item.invoice_date,
          item.due_date,
          (item.total / 100).toFixed(2),
          ((item.total - item.due_amount) / 100).toFixed(2),
          (item.due_amount / 100).toFixed(2),
          item.days_overdue || 0,
          (item.interest / 100).toFixed(2),
        ].join(','))
      }
    }
    const blob = new Blob(['\uFEFF' + rows.join('\n')], { type: 'text/csv;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `ios-${new Date().toISOString().slice(0,10)}.csv`
    a.click()
    URL.revokeObjectURL(url)
  } finally {
    isExportingCsv.value = false
  }
}

onMounted(() => {
  loadIos()
})
</script>

<!-- CLAUDE-CHECKPOINT -->
