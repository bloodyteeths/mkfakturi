<template>
  <BasePage>
    <BasePageHeader :title="t('title')">
      <template #actions>
        <BaseButton
          v-if="canCreate"
          :disabled="!selectedCompanyId"
          variant="primary"
          @click="showCreateModal = true"
        >
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ t('new_batch') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Company Selector -->
    <div class="mb-6">
      <BaseInputGroup :label="$t('partner.select_company')">
        <BaseMultiselect
          v-model="selectedCompanyId"
          :options="companies"
          :searchable="true"
          label="name"
          value-prop="id"
          :placeholder="$t('partner.select_company_placeholder')"
          @update:model-value="onCompanyChange"
        />
      </BaseInputGroup>
    </div>

    <div v-if="!selectedCompanyId" class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-12">
      <BaseIcon name="BuildingOfficeIcon" class="h-12 w-12 text-gray-400" />
      <p class="mt-2 text-sm text-gray-500">{{ $t('partner.accounting.select_company_to_view') }}</p>
    </div>

    <template v-if="selectedCompanyId">
      <!-- Quick Stats Bar -->
      <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-lg border border-red-200 bg-red-50 p-4">
          <div class="flex items-center">
            <BaseIcon name="ExclamationTriangleIcon" class="h-6 w-6 flex-shrink-0 text-red-500" />
            <div class="ml-3">
              <p class="text-sm font-medium text-red-800">{{ t('overdue') }}</p>
              <p class="text-lg font-bold text-red-900">{{ formatMoney(overdueSummary.overdue?.total || 0) }}</p>
              <p class="text-xs text-red-600">{{ overdueSummary.overdue?.count || 0 }} {{ t('bills') }}</p>
            </div>
          </div>
        </div>
        <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4">
          <div class="flex items-center">
            <BaseIcon name="ClockIcon" class="h-6 w-6 flex-shrink-0 text-yellow-500" />
            <div class="ml-3">
              <p class="text-sm font-medium text-yellow-800">{{ t('due_this_week') }}</p>
              <p class="text-lg font-bold text-yellow-900">{{ formatMoney(overdueSummary.due_this_week?.total || 0) }}</p>
              <p class="text-xs text-yellow-600">{{ overdueSummary.due_this_week?.count || 0 }} {{ t('bills') }}</p>
            </div>
          </div>
        </div>
        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
          <div class="flex items-center">
            <BaseIcon name="CalendarDaysIcon" class="h-6 w-6 flex-shrink-0 text-blue-500" />
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
          </select>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="isLoading" class="rounded-lg bg-white p-6 shadow">
        <div v-for="i in 5" :key="i" class="mb-4 flex animate-pulse space-x-4">
          <div class="h-4 w-24 rounded bg-gray-200"></div>
          <div class="h-4 w-20 rounded bg-gray-200"></div>
          <div class="h-4 w-16 rounded bg-gray-200"></div>
          <div class="h-4 w-24 rounded bg-gray-200"></div>
          <div class="h-4 w-20 rounded bg-gray-200"></div>
        </div>
      </div>

      <!-- Batches Table -->
      <div v-else-if="batches.length > 0" class="rounded-lg bg-white shadow overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ t('batch_number') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ t('date') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ t('format', 'Format') }}</th>
                <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500">{{ t('items', 'Items') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ t('total') }}</th>
                <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500">{{ t('status') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ t('actions') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr
                v-for="batch in batches"
                :key="batch.id"
                class="hover:bg-gray-50"
              >
                <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-primary-600">
                  {{ batch.batch_number }}
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">
                  {{ formatDate(batch.batch_date) }}
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-sm">
                  <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">
                    {{ formatFormatLabel(batch.format) }}
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
                  <div class="flex justify-end space-x-2">
                    <button
                      v-if="['draft', 'pending_approval'].includes(batch.status)"
                      class="text-primary-600 hover:text-primary-800 text-xs font-medium"
                      @click="approveItem(batch.id)"
                    >
                      {{ t('approve', 'Approve') }}
                    </button>
                    <button
                      v-if="['approved', 'exported'].includes(batch.status)"
                      class="text-primary-600 hover:text-primary-800 text-xs font-medium"
                      @click="exportItem(batch.id)"
                    >
                      {{ t('export_file') }}
                    </button>
                    <button
                      v-if="['exported', 'sent_to_bank'].includes(batch.status)"
                      class="text-xs font-medium"
                      :class="pendingAction?.batchId === batch.id && pendingAction?.type === 'confirm' ? 'text-green-800 font-bold' : 'text-green-600 hover:text-green-800'"
                      @click="confirmItem(batch.id)"
                    >
                      {{ pendingAction?.batchId === batch.id && pendingAction?.type === 'confirm' ? t('confirm_warning_short', 'Sure?') : t('confirm_payment') }}
                    </button>
                    <button
                      v-if="['draft', 'pending_approval', 'approved'].includes(batch.status)"
                      class="text-xs font-medium"
                      :class="pendingAction?.batchId === batch.id && pendingAction?.type === 'cancel' ? 'text-red-800 font-bold' : 'text-red-600 hover:text-red-800'"
                      @click="cancelItem(batch.id)"
                    >
                      {{ pendingAction?.batchId === batch.id && pendingAction?.type === 'cancel' ? t('cancel_warning_short', 'Sure?') : t('cancel') }}
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-else class="rounded-lg bg-white p-12 shadow text-center">
        <BaseIcon name="BanknotesIcon" class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ t('no_orders', 'No payment orders') }}</h3>
        <p class="mt-1 text-sm text-gray-500">{{ t('no_orders_hint', 'Create a payment order to pay suppliers.') }}</p>
      </div>
    </template>

    <!-- Create Modal -->
    <teleport to="body">
      <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showCreateModal = false">
        <div class="mx-4 w-full max-w-4xl rounded-lg bg-white shadow-xl" style="max-height: 90vh; overflow-y: auto;">
          <div class="border-b border-gray-200 px-6 py-4">
            <h3 class="text-lg font-medium text-gray-900">{{ t('new_batch') }}</h3>
          </div>
          <div class="p-6">
            <!-- Batch Settings -->
            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
              <BaseInputGroup :label="t('execution_date')" required>
                <BaseDatePicker v-model="createForm.batch_date" :calendar-button="true" calendar-button-icon="CalendarDaysIcon" />
              </BaseInputGroup>
              <BaseInputGroup :label="t('format', 'Format')" required>
                <select
                  v-model="createForm.format"
                  class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
                  <option value="pp30">{{ t('pp30') }}</option>
                  <option value="pp50">{{ t('pp50') }}</option>
                  <option value="sepa_sct">{{ t('sepa') }}</option>
                  <option value="csv">CSV</option>
                </select>
              </BaseInputGroup>
              <BaseInputGroup :label="t('notes', 'Notes')">
                <input
                  v-model="createForm.notes"
                  type="text"
                  class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                />
              </BaseInputGroup>
            </div>

            <!-- Bill Selection -->
            <div v-if="isLoadingBills" class="py-8 text-center text-gray-500">
              {{ t('loading', 'Loading...') }}
            </div>
            <div v-else-if="payableBills.length > 0" class="overflow-x-auto border rounded-lg">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="w-10 px-3 py-2">
                      <input type="checkbox" class="rounded border-gray-300" :checked="allBillsSelected" @change="toggleAllBills" />
                    </th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">{{ t('supplier', 'Supplier') }}</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">{{ t('bill_number', 'Bill') }}</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">{{ t('due_date', 'Due') }}</th>
                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">{{ t('amount') }}</th>
                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500">{{ t('status') }}</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                  <tr v-for="bill in payableBills" :key="bill.id" class="hover:bg-gray-50" :class="{ 'bg-red-50': bill.is_overdue, 'bg-yellow-50': bill.is_due_soon && !bill.is_overdue }">
                    <td class="px-3 py-2">
                      <input type="checkbox" class="rounded border-gray-300" :checked="createForm.bill_ids.includes(bill.id)" @change="toggleCreateBill(bill.id)" />
                    </td>
                    <td class="px-3 py-2 text-sm">{{ bill.supplier?.name }}</td>
                    <td class="px-3 py-2 text-sm font-medium text-primary-600">{{ bill.bill_number }}</td>
                    <td class="px-3 py-2 text-sm">{{ formatDate(bill.due_date) }}</td>
                    <td class="px-3 py-2 text-right text-sm font-medium">{{ formatMoney(bill.due_amount) }}</td>
                    <td class="px-3 py-2 text-center text-sm">
                      <span v-if="bill.is_overdue" class="text-xs text-red-600 font-medium">{{ t('overdue') }}</span>
                      <span v-else-if="bill.is_due_soon" class="text-xs text-yellow-600 font-medium">{{ t('due_this_week') }}</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-else class="py-8 text-center text-gray-500">
              {{ t('no_payable_bills', 'No payable bills') }}
            </div>

            <!-- Total and Submit -->
            <div v-if="createForm.bill_ids.length > 0" class="mt-4 flex items-center justify-between rounded-lg border border-primary-200 bg-primary-50 p-4">
              <div>
                <p class="text-sm text-primary-700">{{ createForm.bill_ids.length }} {{ t('bills_selected', 'bills selected') }}</p>
                <p class="text-xl font-bold text-primary-900">{{ formatMoney(createSelectedTotal) }}</p>
              </div>
              <BaseButton variant="primary" :loading="isCreating" @click="submitCreate">
                {{ t('create_order', 'Create Payment Order') }}
              </BaseButton>
            </div>
          </div>
          <div class="border-t border-gray-200 px-6 py-3 text-right">
            <BaseButton variant="primary-outline" @click="showCreateModal = false">
              {{ t('close') }}
            </BaseButton>
          </div>
        </div>
      </div>
    </teleport>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import { useNotificationStore } from '@/scripts/stores/notification'
import poMessages from '@/scripts/admin/i18n/payment-orders.js'

const consoleStore = useConsoleStore()
const notificationStore = useNotificationStore()

const currentLocale = ref(document.documentElement.lang || 'mk')
const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }
const formattedLocale = computed(() => localeMap[currentLocale.value] || 'mk-MK')

const observer = new MutationObserver(() => {
  currentLocale.value = document.documentElement.lang || 'mk'
})
onMounted(async () => {
  observer.observe(document.documentElement, { attributes: true, attributeFilter: ['lang'] })
  await consoleStore.fetchCompanies()
  if (companies.value.length === 1) {
    selectedCompanyId.value = companies.value[0].id
  }
})
onBeforeUnmount(() => observer.disconnect())

function t(key, fallback) {
  return poMessages[currentLocale.value]?.payment_orders?.[key]
    || poMessages['en']?.payment_orders?.[key]
    || fallback
    || key
}

const selectedCompanyId = ref(null)
const isLoading = ref(false)
const isLoadingBills = ref(false)
const isCreating = ref(false)
const batches = ref([])
const overdueSummary = ref({})
const canCreate = ref(true)
const showCreateModal = ref(false)
const payableBills = ref([])
const pendingAction = ref(null) // { type: 'approve'|'export'|'confirm'|'cancel', batchId: number } | null

const filters = ref({ status: '' })

const createForm = ref({
  batch_date: new Date().toISOString().slice(0, 10),
  format: 'pp30',
  notes: '',
  bill_ids: [],
})

const companies = computed(() => consoleStore.managedCompanies || [])

const allBillsSelected = computed(() => {
  if (payableBills.value.length === 0) return false
  return payableBills.value.every((b) => createForm.value.bill_ids.includes(b.id))
})

const createSelectedTotal = computed(() => {
  return payableBills.value
    .filter((b) => createForm.value.bill_ids.includes(b.id))
    .reduce((sum, b) => sum + (b.due_amount || 0), 0)
})

const apiBase = computed(() => `/partner/companies/${selectedCompanyId.value}/accounting/payment-orders`)

watch(showCreateModal, (val) => {
  if (val && selectedCompanyId.value) {
    loadPayableBills()
    createForm.value.bill_ids = []
  } else if (!val) {
    createForm.value.bill_ids = []
    payableBills.value = []
  }
})

function onCompanyChange() {
  batches.value = []
  overdueSummary.value = {}
  payableBills.value = []
  createForm.value.bill_ids = []
  showCreateModal.value = false
  if (selectedCompanyId.value) {
    loadBatches()
    loadOverdueSummary()
  }
}

async function loadBatches() {
  isLoading.value = true
  try {
    const params = {}
    if (filters.value.status) params.status = filters.value.status
    const response = await window.axios.get(apiBase.value, { params })
    const result = response.data
    batches.value = result.data?.data || result.data || []
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_loading') || 'Failed to load payment orders',
    })
  } finally {
    isLoading.value = false
  }
}

async function loadOverdueSummary() {
  try {
    const response = await window.axios.get(`${apiBase.value}/overdue-summary`)
    overdueSummary.value = response.data?.data || {}
  } catch {
    // silent
  }
}

async function loadPayableBills() {
  isLoadingBills.value = true
  try {
    const response = await window.axios.get(`${apiBase.value}/payable-bills`)
    payableBills.value = response.data?.data || []
  } catch {
    payableBills.value = []
  } finally {
    isLoadingBills.value = false
  }
}

function toggleCreateBill(billId) {
  const idx = createForm.value.bill_ids.indexOf(billId)
  if (idx > -1) {
    createForm.value.bill_ids.splice(idx, 1)
  } else {
    createForm.value.bill_ids.push(billId)
  }
}

function toggleAllBills() {
  if (allBillsSelected.value) {
    createForm.value.bill_ids = []
  } else {
    createForm.value.bill_ids = payableBills.value.map((b) => b.id)
  }
}

async function submitCreate() {
  if (createForm.value.bill_ids.length === 0) return
  isCreating.value = true
  try {
    const response = await window.axios.post(apiBase.value, {
      batch_date: createForm.value.batch_date,
      format: createForm.value.format,
      notes: createForm.value.notes || null,
      bill_ids: createForm.value.bill_ids,
    })
    const warnings = response.data?.warnings
    if (warnings && warnings.length > 0) {
      notificationStore.showNotification({ type: 'warning', message: warnings.join(' ') })
    } else {
      notificationStore.showNotification({ type: 'success', message: t('created_success') || 'Payment order created' })
    }
    showCreateModal.value = false
    await loadBatches()
    await loadOverdueSummary()
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: error.response?.data?.message || t('error_creating') || 'Failed' })
  } finally {
    isCreating.value = false
  }
}

async function approveItem(batchId) {
  try {
    await window.axios.post(`${apiBase.value}/${batchId}/approve`)
    notificationStore.showNotification({ type: 'success', message: t('approved_success') })
    await loadBatches()
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: error.response?.data?.message || t('error_approving') })
  }
}

async function exportItem(batchId) {
  try {
    const response = await window.axios.get(`${apiBase.value}/${batchId}/export`, { responseType: 'blob' })

    // Check if response is actually an error (JSON wrapped in blob)
    const blob = response.data
    if (blob.type === 'application/json') {
      const text = await blob.text()
      const json = JSON.parse(text)
      notificationStore.showNotification({ type: 'error', message: json.message || t('error_exporting') || 'Failed' })
      return
    }

    const contentDisposition = response.headers['content-disposition'] || ''
    const filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/)
    const filename = filenameMatch ? filenameMatch[1].replace(/['"]/g, '') : 'payment_order.csv'
    const downloadBlob = new Blob([blob])
    const url = window.URL.createObjectURL(downloadBlob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    notificationStore.showNotification({ type: 'success', message: t('exported') || 'Exported' })
    await loadBatches()
  } catch (error) {
    let message = t('error_exporting') || 'Failed'
    if (error.response?.data instanceof Blob) {
      try {
        const text = await error.response.data.text()
        const json = JSON.parse(text)
        message = json.message || message
      } catch { /* use default */ }
    } else if (error.response?.data?.message) {
      message = error.response.data.message
    }
    notificationStore.showNotification({ type: 'error', message })
  }
}

async function confirmItem(batchId) {
  if (!pendingAction.value || pendingAction.value.batchId !== batchId || pendingAction.value.type !== 'confirm') {
    pendingAction.value = { type: 'confirm', batchId }
    return
  }
  pendingAction.value = null
  try {
    await window.axios.post(`${apiBase.value}/${batchId}/confirm`)
    notificationStore.showNotification({ type: 'success', message: t('confirmed_success') })
    await loadBatches()
    await loadOverdueSummary()
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: error.response?.data?.message || t('error_confirming') })
  }
}

async function cancelItem(batchId) {
  if (!pendingAction.value || pendingAction.value.batchId !== batchId || pendingAction.value.type !== 'cancel') {
    pendingAction.value = { type: 'cancel', batchId }
    return
  }
  pendingAction.value = null
  try {
    await window.axios.post(`${apiBase.value}/${batchId}/cancel`)
    notificationStore.showNotification({ type: 'success', message: t('cancelled_success') })
    await loadBatches()
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: error.response?.data?.message || t('error_cancelling') })
  }
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

function formatFormatLabel(format) {
  const labels = { pp30: 'PP30', pp50: 'PP50', sepa_sct: 'SEPA', csv: 'CSV' }
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
    sent_to_bank: t('status_sent', 'Sent'),
    confirmed: t('status_confirmed'),
    cancelled: t('cancelled', 'Cancelled'),
  }
  return labels[status] || status
}
</script>

<!-- CLAUDE-CHECKPOINT -->
