<template>
  <BasePage>
    <BasePageHeader :title="t('title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="t('home')" to="dashboard" />
        <BaseBreadcrumbItem :title="t('title')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <router-link to="/admin/payment-orders/create">
          <BaseButton variant="primary">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ t('new_batch') }}
          </BaseButton>
        </router-link>
      </template>
    </BasePageHeader>

    <!-- Quick Stats Bar -->
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
      <div class="rounded-lg border border-red-200 bg-red-50 p-4">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <BaseIcon name="ExclamationTriangleIcon" class="h-6 w-6 text-red-500" />
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-red-800">{{ t('overdue') }}</p>
            <p class="text-lg font-bold text-red-900">{{ formatMoney(overdueSummary.overdue?.total || 0) }}</p>
            <p class="text-xs text-red-600">{{ overdueSummary.overdue?.count || 0 }} {{ t('bills') }}</p>
          </div>
        </div>
      </div>

      <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <BaseIcon name="ClockIcon" class="h-6 w-6 text-yellow-500" />
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-yellow-800">{{ t('due_this_week') }}</p>
            <p class="text-lg font-bold text-yellow-900">{{ formatMoney(overdueSummary.due_this_week?.total || 0) }}</p>
            <p class="text-xs text-yellow-600">{{ overdueSummary.due_this_week?.count || 0 }} {{ t('bills') }}</p>
          </div>
        </div>
      </div>

      <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <BaseIcon name="CalendarDaysIcon" class="h-6 w-6 text-blue-500" />
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-blue-800">{{ t('due_this_month') }}</p>
            <p class="text-lg font-bold text-blue-900">{{ formatMoney(overdueSummary.due_this_month?.total || 0) }}</p>
            <p class="text-xs text-blue-600">{{ overdueSummary.due_this_month?.count || 0 }} {{ t('bills') }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="mb-6 flex flex-wrap items-center gap-4 rounded-lg bg-white p-4 shadow">
      <div class="flex items-center gap-2">
        <label class="text-sm font-medium text-gray-700">{{ t('status') }}:</label>
        <select
          v-model="filters.status"
          class="rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
          @change="loadBatches"
        >
          <option value="">{{ t('all') }}</option>
          <option value="draft">{{ t('status_draft') }}</option>
          <option value="approved">{{ t('status_approved') }}</option>
          <option value="exported">{{ t('status_exported') }}</option>
          <option value="confirmed">{{ t('status_confirmed') }}</option>
          <option value="cancelled">{{ t('cancelled', 'Cancelled') }}</option>
        </select>
      </div>
      <div class="flex items-center gap-2">
        <label class="text-sm font-medium text-gray-700">{{ t('format') }}:</label>
        <select
          v-model="filters.format"
          class="rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
          @change="loadBatches"
        >
          <option value="">{{ t('all_formats') }}</option>
          <option value="pp30">PP30</option>
          <option value="pp50">PP50</option>
          <option value="sepa_sct">SEPA</option>
          <option value="csv">CSV</option>
        </select>
      </div>
      <div class="flex items-center gap-2">
        <label class="text-sm font-medium text-gray-700">{{ t('search') }}:</label>
        <input
          v-model="filters.search"
          type="text"
          class="rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
          :placeholder="t('batch_number')"
          @input="debouncedLoadBatches"
        />
      </div>
      <div class="flex items-center gap-2">
        <label class="text-sm font-medium text-gray-700">{{ t('date') }}:</label>
        <input v-model="filters.from_date" type="date" class="rounded-md border-gray-300 text-sm shadow-sm" @change="loadBatches" />
        <span class="text-gray-400">&ndash;</span>
        <input v-model="filters.to_date" type="date" class="rounded-md border-gray-300 text-sm shadow-sm" @change="loadBatches" />
      </div>
    </div>

    <!-- Bulk Actions Bar -->
    <div v-if="selectedBatchIds.length > 0" class="mb-4 flex items-center gap-3 rounded-lg border border-primary-200 bg-primary-50 p-3">
      <span class="text-sm font-medium text-primary-800">
        {{ selectedBatchIds.length }} {{ t('selected_count') }}
      </span>
      <BaseButton variant="primary-outline" size="sm" :loading="isBulkApproving" @click="bulkApprove">
        {{ t('bulk_approve') }}
      </BaseButton>
      <BaseButton variant="primary" size="sm" :loading="isBulkExporting" @click="bulkExport">
        {{ t('bulk_export') }}
      </BaseButton>
      <BaseButton variant="danger-outline" size="sm" :loading="isBulkCancelling" @click="bulkCancel">
        {{ t('bulk_cancel') }}
      </BaseButton>
      <BaseButton variant="primary-outline" size="sm" @click="selectedBatchIds = []">
        {{ t('clear') }}
      </BaseButton>
    </div>

    <!-- Batches Table -->
    <div class="rounded-lg bg-white shadow overflow-hidden">
      <div v-if="isLoading" class="p-6">
        <div v-for="i in 5" :key="i" class="mb-4 flex animate-pulse space-x-4">
          <div class="h-4 w-24 rounded bg-gray-200"></div>
          <div class="h-4 w-20 rounded bg-gray-200"></div>
          <div class="h-4 w-16 rounded bg-gray-200"></div>
          <div class="h-4 w-12 rounded bg-gray-200"></div>
          <div class="h-4 w-24 rounded bg-gray-200"></div>
          <div class="h-4 w-20 rounded bg-gray-200"></div>
        </div>
      </div>

      <template v-else-if="batches.length > 0">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="w-12 px-4 py-3">
                  <input
                    type="checkbox"
                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                    :checked="allSelected"
                    @change="toggleSelectAll"
                  />
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 cursor-pointer select-none" @click="toggleSort('batch_number')">
                  {{ t('batch_number') }}
                  <span v-if="sortBy === 'batch_number'" class="ml-1">{{ sortOrder === 'asc' ? '\u2191' : '\u2193' }}</span>
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 cursor-pointer select-none" @click="toggleSort('batch_date')">
                  {{ t('date') }}
                  <span v-if="sortBy === 'batch_date'" class="ml-1">{{ sortOrder === 'asc' ? '\u2191' : '\u2193' }}</span>
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ t('format', 'Format') }}</th>
                <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500">{{ t('items', 'Items') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 cursor-pointer select-none" @click="toggleSort('total_amount')">
                  {{ t('total') }}
                  <span v-if="sortBy === 'total_amount'" class="ml-1">{{ sortOrder === 'asc' ? '\u2191' : '\u2193' }}</span>
                </th>
                <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 cursor-pointer select-none" @click="toggleSort('status')">
                  {{ t('status') }}
                  <span v-if="sortBy === 'status'" class="ml-1">{{ sortOrder === 'asc' ? '\u2191' : '\u2193' }}</span>
                </th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ t('actions') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr
                v-for="batch in batches"
                :key="batch.id"
                class="cursor-pointer hover:bg-gray-50"
                @click="viewBatch(batch.id)"
              >
                <td class="px-4 py-3" @click.stop>
                  <input
                    type="checkbox"
                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                    :checked="selectedBatchIds.includes(batch.id)"
                    @change="toggleBatchSelect(batch.id)"
                  />
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-primary-600">
                  {{ batch.batch_number }}
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">
                  {{ formatDate(batch.batch_date) }}
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-sm">
                  <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">
                    {{ formatLabel(batch.format) }}
                  </span>
                  <span v-if="batch.urgency === 'itno'" class="ml-1 inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">
                    {{ t('urgency_urgent') }}
                  </span>
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-center text-sm text-gray-900">
                  {{ batch.item_count }}
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-gray-900">
                  {{ formatMoney(batch.total_amount) }}
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-center text-sm">
                  <span
                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                    :class="statusClass(batch.status)"
                  >
                    {{ statusLabel(batch.status) }}
                  </span>
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                  <router-link
                    :to="`/admin/payment-orders/${batch.id}`"
                    class="text-primary-600 hover:text-primary-800"
                    @click.stop
                  >
                    {{ t('view') }}
                  </router-link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <div v-else class="p-12 text-center">
        <BaseIcon name="BanknotesIcon" class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ t('no_orders', 'No payment orders yet') }}</h3>
        <p class="mt-1 text-sm text-gray-500">{{ t('no_orders_hint', 'Create a payment order to pay your suppliers.') }}</p>
        <div class="mt-4">
          <router-link to="/admin/payment-orders/create">
            <BaseButton variant="primary">
              <template #left="slotProps">
                <BaseIcon name="PlusIcon" :class="slotProps.class" />
              </template>
              {{ t('new_batch') }}
            </BaseButton>
          </router-link>
        </div>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/scripts/stores/notification'
import poMessages from '@/scripts/admin/i18n/payment-orders.js'

const router = useRouter()
const notificationStore = useNotificationStore()

const currentLocale = ref(document.documentElement.lang || 'mk')
const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }
const formattedLocale = computed(() => localeMap[currentLocale.value] || 'mk-MK')

// Watch for locale changes
const observer = new MutationObserver(() => {
  currentLocale.value = document.documentElement.lang || 'mk'
})
onMounted(() => {
  observer.observe(document.documentElement, { attributes: true, attributeFilter: ['lang'] })
})
onBeforeUnmount(() => observer.disconnect())

function t(key) {
  return poMessages[currentLocale.value]?.payment_orders?.[key]
    || poMessages['en']?.payment_orders?.[key]
    || key
}

const isLoading = ref(false)
const batches = ref([])
const overdueSummary = ref({})
const selectedBatchIds = ref([])
const isBulkApproving = ref(false)
const isBulkExporting = ref(false)
const isBulkCancelling = ref(false)
const sortBy = ref('batch_date')
const sortOrder = ref('desc')

const filters = ref({
  status: '',
  format: '',
  search: '',
  from_date: '',
  to_date: '',
})

const allSelected = computed(() => {
  if (batches.value.length === 0) return false
  return batches.value.every((b) => selectedBatchIds.value.includes(b.id))
})

let searchTimeout = null
function debouncedLoadBatches() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => loadBatches(), 300)
}

onMounted(() => {
  loadBatches()
  loadOverdueSummary()
})

function toggleSort(column) {
  if (sortBy.value === column) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortBy.value = column
    sortOrder.value = 'desc'
  }
  loadBatches()
}

function toggleBatchSelect(id) {
  const idx = selectedBatchIds.value.indexOf(id)
  if (idx > -1) {
    selectedBatchIds.value.splice(idx, 1)
  } else {
    selectedBatchIds.value.push(id)
  }
}

function toggleSelectAll() {
  if (allSelected.value) {
    selectedBatchIds.value = []
  } else {
    selectedBatchIds.value = batches.value.map((b) => b.id)
  }
}

async function loadBatches() {
  isLoading.value = true
  try {
    const params = { sort_by: sortBy.value, sort_order: sortOrder.value }
    if (filters.value.status) params.status = filters.value.status
    if (filters.value.format) params.format = filters.value.format
    if (filters.value.search) params.search = filters.value.search
    if (filters.value.from_date) params.from_date = filters.value.from_date
    if (filters.value.to_date) params.to_date = filters.value.to_date
    const response = await window.axios.get('/payment-orders', { params })
    const result = response.data
    batches.value = result.data?.data || result.data || []
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_loading'),
    })
  } finally {
    isLoading.value = false
  }
}

async function loadOverdueSummary() {
  try {
    const response = await window.axios.get('/payment-orders/overdue-summary')
    overdueSummary.value = response.data?.data || {}
  } catch {
    // Silently fail for summary
  }
}

async function bulkApprove() {
  if (!confirm(t('confirm_bulk_approve').replace('{count}', selectedBatchIds.value.length))) return
  isBulkApproving.value = true
  try {
    await window.axios.post('/payment-orders/bulk-approve', { batch_ids: selectedBatchIds.value })
    notificationStore.showNotification({
      type: 'success',
      message: t('bulk_approve_success').replace('{count}', selectedBatchIds.value.length),
    })
    selectedBatchIds.value = []
    await loadBatches()
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: error.response?.data?.message || t('error_approving') })
  } finally {
    isBulkApproving.value = false
  }
}

async function bulkExport() {
  isBulkExporting.value = true
  try {
    const response = await window.axios.post('/payment-orders/bulk-export', { batch_ids: selectedBatchIds.value }, { responseType: 'blob' })
    const blob = response.data
    const contentDisposition = response.headers['content-disposition'] || ''
    const filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/)
    const filename = filenameMatch ? filenameMatch[1].replace(/['"]/g, '') : 'payment_orders_export.zip'
    const url = window.URL.createObjectURL(new Blob([blob]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    notificationStore.showNotification({
      type: 'success',
      message: t('bulk_export_success').replace('{count}', selectedBatchIds.value.length),
    })
    selectedBatchIds.value = []
    await loadBatches()
  } catch (error) {
    let message = t('error_exporting')
    if (error.response?.data instanceof Blob) {
      try {
        const text = await error.response.data.text()
        const json = JSON.parse(text)
        message = json.message || message
      } catch { /* use default */ }
    }
    notificationStore.showNotification({ type: 'error', message })
  } finally {
    isBulkExporting.value = false
  }
}

async function bulkCancel() {
  if (!confirm(t('confirm_bulk_cancel').replace('{count}', selectedBatchIds.value.length))) return
  isBulkCancelling.value = true
  try {
    await window.axios.post('/payment-orders/bulk-cancel', { batch_ids: selectedBatchIds.value })
    notificationStore.showNotification({
      type: 'success',
      message: t('bulk_cancel_success').replace('{count}', selectedBatchIds.value.length),
    })
    selectedBatchIds.value = []
    await loadBatches()
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: error.response?.data?.message || t('error_cancelling') })
  } finally {
    isBulkCancelling.value = false
  }
}

function viewBatch(id) {
  router.push(`/admin/payment-orders/${id}`)
}

function formatMoney(amount) {
  if (amount === null || amount === undefined) return '-'
  const value = Math.abs(amount) / 100
  const sign = amount < 0 ? '-' : ''
  return sign + new Intl.NumberFormat(formattedLocale.value, { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value) + ' \u0434\u0435\u043d.'
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString(formattedLocale.value, { year: 'numeric', month: '2-digit', day: '2-digit' })
}

function formatLabel(format) {
  const labels = {
    pp30: 'PP30',
    pp50: 'PP50',
    sepa_sct: 'SEPA',
    csv: 'CSV',
  }
  return labels[format] || format
}

function statusClass(status) {
  const classes = {
    draft: 'bg-gray-100 text-gray-700',
    pending_approval: 'bg-yellow-100 text-yellow-700',
    approved: 'bg-blue-100 text-blue-700',
    exported: 'bg-indigo-100 text-indigo-700',
    sent_to_bank: 'bg-purple-100 text-purple-700',
    confirmed: 'bg-green-100 text-green-700',
    cancelled: 'bg-red-100 text-red-700',
  }
  return classes[status] || 'bg-gray-100 text-gray-700'
}

function statusLabel(status) {
  const labels = {
    draft: t('status_draft'),
    pending_approval: t('status_pending', 'Pending'),
    approved: t('status_approved'),
    exported: t('status_exported'),
    sent_to_bank: t('status_sent', 'Sent to Bank'),
    confirmed: t('status_confirmed'),
    cancelled: t('cancelled', 'Cancelled'),
  }
  return labels[status] || status
}
</script>

<!-- CLAUDE-CHECKPOINT -->
