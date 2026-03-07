<template>
  <BasePage>
    <BasePageHeader :title="`${t('title')} #${batch?.batch_number || ''}`">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="t('home')" to="dashboard" />
        <BaseBreadcrumbItem :title="t('title')" to="/admin/payment-orders" />
        <BaseBreadcrumbItem :title="batch?.batch_number || '...'" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <div class="flex space-x-2">
          <!-- Approve Button -->
          <BaseButton
            v-if="canApprove"
            variant="primary-outline"
            :loading="isApproving"
            @click="approveBatch"
          >
            <template #left="slotProps">
              <BaseIcon name="CheckCircleIcon" :class="slotProps.class" />
            </template>
            {{ t('approve', 'Approve') }}
          </BaseButton>

          <!-- Export Button -->
          <BaseButton
            v-if="canExport"
            variant="primary"
            :loading="isExporting"
            @click="exportBatch"
          >
            <template #left="slotProps">
              <BaseIcon name="ArrowDownTrayIcon" :class="slotProps.class" />
            </template>
            {{ t('export_file') }}
          </BaseButton>

          <!-- Confirm Button -->
          <BaseButton
            v-if="canConfirm"
            variant="success"
            :loading="isConfirming"
            @click="confirmBatch"
          >
            <template #left="slotProps">
              <BaseIcon name="CheckBadgeIcon" :class="slotProps.class" />
            </template>
            {{ t('confirm_payment') }}
          </BaseButton>

          <!-- Cancel Button -->
          <BaseButton
            v-if="canCancel"
            variant="danger-outline"
            :loading="isCancelling"
            @click="cancelBatch"
          >
            <template #left="slotProps">
              <BaseIcon name="XCircleIcon" :class="slotProps.class" />
            </template>
            {{ t('cancel') }}
          </BaseButton>
        </div>
      </template>
    </BasePageHeader>

    <!-- Loading -->
    <div v-if="isLoading" class="rounded-lg bg-white p-6 shadow">
      <div v-for="i in 4" :key="i" class="mb-4 flex animate-pulse space-x-4">
        <div class="h-4 w-32 rounded bg-gray-200"></div>
        <div class="h-4 w-48 rounded bg-gray-200"></div>
      </div>
    </div>

    <template v-else-if="batch">
      <!-- Batch Header Card -->
      <div class="mb-6 rounded-lg bg-white p-6 shadow">
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4 lg:grid-cols-6">
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('batch_number') }}</p>
            <p class="text-sm font-bold text-gray-900">{{ batch.batch_number }}</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('execution_date') }}</p>
            <p class="text-sm text-gray-900">{{ formatDate(batch.batch_date) }}</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('format', 'Format') }}</p>
            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">
              {{ formatLabel(batch.format) }}
            </span>
          </div>
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('status') }}</p>
            <span
              class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
              :class="statusClass(batch.status)"
            >
              {{ statusLabel(batch.status) }}
            </span>
          </div>
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('items', 'Items') }}</p>
            <p class="text-sm text-gray-900">{{ batch.item_count }}</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('total') }}</p>
            <p class="text-lg font-bold text-primary-600">{{ formatMoney(batch.total_amount) }}</p>
          </div>
        </div>

        <!-- Status Pipeline -->
        <div class="mt-6 border-t border-gray-200 pt-4">
          <div class="flex items-center justify-between">
            <div v-for="(step, idx) in statusPipeline" :key="step.key" class="flex items-center">
              <div
                class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold"
                :class="stepClass(step.key)"
              >
                {{ idx + 1 }}
              </div>
              <span class="ml-2 text-xs" :class="isStepActive(step.key) ? 'font-medium text-gray-900' : 'text-gray-400'">
                {{ step.label }}
              </span>
              <div v-if="idx < statusPipeline.length - 1" class="mx-3 h-px w-8 bg-gray-300 sm:w-12"></div>
            </div>
          </div>
        </div>

        <!-- Additional Details -->
        <div v-if="batch.notes" class="mt-4 border-t border-gray-200 pt-4">
          <p class="text-xs font-medium uppercase text-gray-500">{{ t('notes', 'Notes') }}</p>
          <p class="mt-1 text-sm text-gray-700">{{ batch.notes }}</p>
        </div>

        <div class="mt-4 flex gap-6 text-xs text-gray-500">
          <span v-if="batch.created_by">
            {{ t('created_by', 'Created by') }}: {{ batch.created_by?.name || '-' }}
          </span>
          <span v-if="batch.approved_by">
            {{ t('approved_by', 'Approved by') }}: {{ batch.approved_by?.name || '-' }}
            ({{ formatDate(batch.approved_at) }})
          </span>
          <span v-if="batch.bank_account">
            {{ t('bank_account', 'Bank Account') }}: {{ batch.bank_account?.account_name || batch.bank_account?.iban }}
          </span>
        </div>
      </div>

      <!-- Items Table -->
      <div class="rounded-lg bg-white shadow overflow-hidden">
        <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
          <h3 class="text-lg font-medium text-gray-900">{{ t('payment_items', 'Payment Items') }}</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">#</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ t('creditor') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">IBAN</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ t('bill_number', 'Bill') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ t('description', 'Description') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ t('amount') }}</th>
                <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500">{{ t('status') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="(item, idx) in (batch.items || [])" :key="item.id" class="hover:bg-gray-50">
                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ idx + 1 }}</td>
                <td class="px-4 py-3 text-sm">
                  <div class="font-medium text-gray-900">{{ item.creditor_name }}</div>
                  <div v-if="item.creditor_bank_name" class="text-xs text-gray-500">{{ item.creditor_bank_name }}</div>
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 font-mono">
                  {{ item.creditor_iban || '-' }}
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-sm">
                  <span v-if="item.bill" class="text-primary-600 font-medium">{{ item.bill.bill_number }}</span>
                  <span v-else class="text-gray-400">-</span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-500 max-w-xs truncate">
                  {{ item.description || '-' }}
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-gray-900">
                  {{ formatMoney(item.amount) }}
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-center text-sm">
                  <span
                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                    :class="itemStatusClass(item.status)"
                  >
                    {{ itemStatusLabel(item.status) }}
                  </span>
                  <div v-if="item.reconciled_at" class="text-xs text-green-600 mt-0.5">
                    {{ t('reconciled', 'Reconciled') }}
                  </div>
                </td>
              </tr>
            </tbody>
            <tfoot class="bg-gray-100 font-semibold">
              <tr>
                <td colspan="5" class="px-4 py-3 text-sm">{{ t('total') }} ({{ batch.items?.length || 0 }})</td>
                <td class="whitespace-nowrap px-4 py-3 text-right text-sm">{{ formatMoney(batch.total_amount) }}</td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </template>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useNotificationStore } from '@/scripts/stores/notification'
import poMessages from '@/scripts/admin/i18n/payment-orders.js'

const route = useRoute()
const router = useRouter()
const notificationStore = useNotificationStore()

const locale = document.documentElement.lang || 'mk'
const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }
const formattedLocale = localeMap[locale] || 'mk-MK'

function t(key) {
  return poMessages[locale]?.payment_orders?.[key]
    || poMessages['en']?.payment_orders?.[key]
    || key
}

const isLoading = ref(false)
const isApproving = ref(false)
const isExporting = ref(false)
const isConfirming = ref(false)
const isCancelling = ref(false)
const batch = ref(null)

const batchId = computed(() => route.params.id)

const canApprove = computed(() => batch.value && ['draft', 'pending_approval'].includes(batch.value.status))
const canExport = computed(() => batch.value && ['approved', 'exported'].includes(batch.value.status))
const canConfirm = computed(() => batch.value && ['exported', 'sent_to_bank'].includes(batch.value.status))
const canCancel = computed(() => batch.value && ['draft', 'pending_approval'].includes(batch.value.status))

const statusPipeline = computed(() => [
  { key: 'draft', label: t('status_draft') },
  { key: 'approved', label: t('status_approved') },
  { key: 'exported', label: t('status_exported') },
  { key: 'confirmed', label: t('status_confirmed') },
])

const statusOrder = ['draft', 'pending_approval', 'approved', 'exported', 'sent_to_bank', 'confirmed']

onMounted(() => {
  loadBatch()
})

async function loadBatch() {
  isLoading.value = true
  try {
    const response = await window.axios.get(`/payment-orders/${batchId.value}`)
    batch.value = response.data?.data || response.data
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_loading') || 'Failed to load payment order',
    })
    router.push('/admin/payment-orders')
  } finally {
    isLoading.value = false
  }
}

async function approveBatch() {
  isApproving.value = true
  try {
    await window.axios.post(`/payment-orders/${batchId.value}/approve`)
    notificationStore.showNotification({ type: 'success', message: t('approved_success') || 'Approved' })
    await loadBatch()
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: error.response?.data?.message || t('error_approving') || 'Failed to approve' })
  } finally {
    isApproving.value = false
  }
}

async function exportBatch() {
  isExporting.value = true
  try {
    const response = await window.axios.get(`/payment-orders/${batchId.value}/export`, {
      responseType: 'blob',
    })

    // Extract filename from content-disposition header
    const contentDisposition = response.headers['content-disposition'] || ''
    const filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/)
    const filename = filenameMatch ? filenameMatch[1].replace(/['"]/g, '') : `payment_order_${batch.value.batch_number}.csv`

    const blob = new Blob([response.data])
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)

    notificationStore.showNotification({ type: 'success', message: t('exported') || 'File exported' })
    await loadBatch()
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: error.response?.data?.message || t('error_exporting') || 'Failed to export' })
  } finally {
    isExporting.value = false
  }
}

async function confirmBatch() {
  if (!window.confirm(t('confirm_warning', 'This will create bill payments and mark bills as paid. Continue?'))) {
    return
  }

  isConfirming.value = true
  try {
    await window.axios.post(`/payment-orders/${batchId.value}/confirm`)
    notificationStore.showNotification({ type: 'success', message: t('confirmed_success') || 'Payment confirmed' })
    await loadBatch()
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: error.response?.data?.message || t('error_confirming') || 'Failed to confirm' })
  } finally {
    isConfirming.value = false
  }
}

async function cancelBatch() {
  if (!window.confirm(t('cancel_warning', 'Are you sure you want to cancel this payment order?'))) {
    return
  }

  isCancelling.value = true
  try {
    await window.axios.post(`/payment-orders/${batchId.value}/cancel`)
    notificationStore.showNotification({ type: 'success', message: t('cancelled_success') || 'Cancelled' })
    await loadBatch()
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: error.response?.data?.message || t('error_cancelling') || 'Failed to cancel' })
  } finally {
    isCancelling.value = false
  }
}

function isStepActive(stepKey) {
  if (!batch.value) return false
  const currentIdx = statusOrder.indexOf(batch.value.status)
  const stepIdx = statusOrder.indexOf(stepKey)
  return stepIdx <= currentIdx
}

function stepClass(stepKey) {
  if (!batch.value) return 'bg-gray-200 text-gray-500'
  if (batch.value.status === 'cancelled') return 'bg-red-200 text-red-700'
  if (isStepActive(stepKey)) return 'bg-primary-500 text-white'
  return 'bg-gray-200 text-gray-500'
}

function formatMoney(amount) {
  if (amount === null || amount === undefined) return '-'
  const value = Math.abs(amount) / 100
  const sign = amount < 0 ? '-' : ''
  return sign + new Intl.NumberFormat(formattedLocale, { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value) + ' \u0434\u0435\u043d.'
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString(formattedLocale, { year: 'numeric', month: '2-digit', day: '2-digit' })
}

function formatLabel(format) {
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
    sent_to_bank: t('status_sent', 'Sent to Bank'),
    confirmed: t('status_confirmed'),
    cancelled: t('cancelled', 'Cancelled'),
  }
  return labels[status] || status
}

function itemStatusClass(status) {
  const classes = {
    pending: 'bg-gray-100 text-gray-700',
    exported: 'bg-indigo-100 text-indigo-700',
    confirmed: 'bg-green-100 text-green-700',
    failed: 'bg-red-100 text-red-700',
  }
  return classes[status] || 'bg-gray-100 text-gray-700'
}

function itemStatusLabel(status) {
  const labels = {
    pending: t('item_pending', 'Pending'),
    exported: t('item_exported', 'Exported'),
    confirmed: t('item_confirmed', 'Confirmed'),
    failed: t('item_failed', 'Failed'),
  }
  return labels[status] || status
}
</script>

<!-- CLAUDE-CHECKPOINT -->
