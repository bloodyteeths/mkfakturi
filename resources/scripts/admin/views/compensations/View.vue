<template>
  <BasePage>
    <BasePageHeader :title="compensation ? compensation.compensation_number : t('title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="t('title')" to="../compensations" />
        <BaseBreadcrumbItem
          :title="compensation ? compensation.compensation_number : '...'"
          to="#"
          active
        />
      </BaseBreadcrumb>

      <template #actions>
        <div v-if="compensation" class="flex flex-wrap items-center space-x-2 gap-y-2">
          <!-- Confirm button (draft only) -->
          <BaseButton
            v-if="compensation.status === 'draft'"
            variant="primary"
            :loading="isConfirming"
            @click="showConfirmDialog = true"
          >
            <template #left="slotProps">
              <BaseIcon name="CheckIcon" :class="slotProps.class" />
            </template>
            {{ t('confirm') }}
          </BaseButton>

          <!-- Cancel button (draft only) -->
          <BaseButton
            v-if="compensation.status === 'draft'"
            variant="danger"
            :loading="isCancelling"
            @click="showCancelDialog = true"
          >
            <template #left="slotProps">
              <BaseIcon name="XMarkIcon" :class="slotProps.class" />
            </template>
            {{ t('cancel') }}
          </BaseButton>

          <!-- Download PDF -->
          <BaseButton
            variant="primary-outline"
            :loading="isDownloading"
            @click="downloadPdf"
          >
            <template #left="slotProps">
              <BaseIcon name="ArrowDownTrayIcon" :class="slotProps.class" />
            </template>
            {{ t('download_pdf') }}
          </BaseButton>

          <!-- Preview PDF -->
          <BaseButton
            variant="primary-outline"
            :loading="isPreviewing"
            @click="previewPdf"
          >
            <template #left="slotProps">
              <BaseIcon name="EyeIcon" :class="slotProps.class" />
            </template>
            {{ t('preview_pdf') }}
          </BaseButton>

          <!-- Print -->
          <BaseButton
            variant="primary-outline"
            @click="printPdf"
          >
            <template #left="slotProps">
              <BaseIcon name="PrinterIcon" :class="slotProps.class" />
            </template>
            {{ t('print') }}
          </BaseButton>

          <!-- Edit (draft only) -->
          <router-link
            v-if="compensation.status === 'draft'"
            :to="`/admin/compensations/${compensation.id}/edit`"
          >
            <BaseButton variant="primary-outline">
              <template #left="slotProps">
                <BaseIcon name="PencilIcon" :class="slotProps.class" />
              </template>
              {{ t('edit') }}
            </BaseButton>
          </router-link>
        </div>
      </template>
    </BasePageHeader>

    <!-- Loading -->
    <div v-if="isLoading" class="bg-white rounded-lg shadow p-6">
      <div class="space-y-4">
        <div v-for="i in 6" :key="i" class="flex space-x-4 animate-pulse">
          <div class="h-4 bg-gray-200 rounded w-32"></div>
          <div class="h-4 bg-gray-200 rounded flex-1"></div>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div v-else-if="compensation" class="space-y-6">
      <!-- Workflow status bar -->
      <div class="bg-white rounded-lg shadow px-6 py-3">
        <div class="flex items-center space-x-3 text-sm">
          <span class="text-gray-500">{{ t('workflow_draft') }}</span>
          <div class="flex items-center space-x-1">
            <span :class="['px-2 py-0.5 rounded text-xs font-medium', compensation.status === 'draft' ? 'bg-gray-200 text-gray-800 ring-2 ring-gray-400' : 'bg-gray-100 text-gray-500']">{{ t('status_draft') }}</span>
            <span class="text-gray-300">→</span>
            <span :class="['px-2 py-0.5 rounded text-xs font-medium', compensation.status === 'confirmed' ? 'bg-green-200 text-green-800 ring-2 ring-green-400' : 'bg-gray-100 text-gray-500']">{{ t('status_confirmed') }}</span>
            <span class="text-gray-300">→</span>
            <span :class="['px-2 py-0.5 rounded text-xs font-medium', compensation.status === 'cancelled' ? 'bg-red-200 text-red-800 ring-2 ring-red-400' : 'bg-gray-100 text-gray-500']">{{ t('status_cancelled') }}</span>
          </div>
        </div>
      </div>

      <!-- Header Card -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
          <div>
            <h3 class="text-lg font-medium text-gray-900">{{ compensation.compensation_number }}</h3>
            <p class="text-sm text-gray-500">{{ formatDate(compensation.compensation_date) }}</p>
          </div>
          <span :class="statusBadgeClass(compensation.status)" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium">
            {{ statusLabel(compensation.status) }}
          </span>
        </div>

        <div class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div>
              <p class="text-xs text-gray-500 uppercase font-medium">{{ t('type') }}</p>
              <p class="text-sm font-medium text-gray-900 mt-1">
                {{ compensation.type === 'bilateral' ? t('bilateral') : t('unilateral') }}
              </p>
            </div>
            <div>
              <p class="text-xs text-gray-500 uppercase font-medium">{{ t('counterparty') }}</p>
              <p class="text-sm font-medium text-gray-900 mt-1">
                {{ compensation.customer?.name || compensation.supplier?.name || '-' }}
              </p>
            </div>
            <div>
              <p class="text-xs text-gray-500 uppercase font-medium">{{ t('created_by') }}</p>
              <p class="text-sm font-medium text-gray-900 mt-1">
                {{ compensation.created_by_user?.name || '-' }}
              </p>
            </div>
            <div v-if="compensation.confirmed_at">
              <p class="text-xs text-gray-500 uppercase font-medium">{{ t('confirmed_at') }}</p>
              <p class="text-sm font-medium text-gray-900 mt-1">
                {{ formatDate(compensation.confirmed_at) }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
          <p class="text-xs text-blue-600 uppercase font-medium">{{ t('total_receivables') }}</p>
          <p class="text-2xl font-bold text-blue-800">{{ formatMoney(compensation.receivables_total) }}</p>
          <p v-if="compensation.receivables_remaining > 0" class="text-xs text-blue-600 mt-1">
            {{ t('remaining_receivable') }}: {{ formatMoney(compensation.receivables_remaining) }}
          </p>
        </div>
        <div class="bg-amber-50 rounded-lg p-4 border border-amber-200">
          <p class="text-xs text-amber-600 uppercase font-medium">{{ t('total_payables') }}</p>
          <p class="text-2xl font-bold text-amber-800">{{ formatMoney(compensation.payables_total) }}</p>
          <p v-if="compensation.payables_remaining > 0" class="text-xs text-amber-600 mt-1">
            {{ t('remaining_payable') }}: {{ formatMoney(compensation.payables_remaining) }}
          </p>
        </div>
        <div class="bg-green-50 rounded-lg p-4 border border-green-200">
          <p class="text-xs text-green-600 uppercase font-medium">{{ t('offset_amount') }}</p>
          <p class="text-2xl font-bold text-green-800">{{ formatMoney(compensation.total_amount) }}</p>
        </div>
      </div>

      <!-- Receivable Items -->
      <div v-if="receivableItems.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-3 bg-blue-50 border-b border-blue-200">
          <h3 class="text-sm font-semibold text-blue-800">{{ t('our_receivables') }}</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('document_number') }}</th>
                <th class="hidden sm:table-cell px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('document_date') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('document_total') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('amount_to_offset') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('remaining_after') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="item in receivableItems" :key="item.id" class="hover:bg-gray-50">
                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ item.document_number }}</td>
                <td class="hidden sm:table-cell px-4 py-3 text-sm text-gray-500">{{ formatDate(item.document_date) }}</td>
                <td class="px-4 py-3 text-sm text-right text-gray-500">{{ formatMoney(item.document_total) }}</td>
                <td class="px-4 py-3 text-sm text-right font-medium text-blue-700">{{ formatMoney(item.amount_offset) }}</td>
                <td class="px-4 py-3 text-sm text-right text-gray-500">{{ formatMoney(item.remaining_after) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Payable Items -->
      <div v-if="payableItems.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-3 bg-amber-50 border-b border-amber-200">
          <h3 class="text-sm font-semibold text-amber-800">{{ t('our_payables') }}</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('document_number') }}</th>
                <th class="hidden sm:table-cell px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('document_date') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('document_total') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('amount_to_offset') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('remaining_after') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="item in payableItems" :key="item.id" class="hover:bg-gray-50">
                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ item.document_number }}</td>
                <td class="hidden sm:table-cell px-4 py-3 text-sm text-gray-500">{{ formatDate(item.document_date) }}</td>
                <td class="px-4 py-3 text-sm text-right text-gray-500">{{ formatMoney(item.document_total) }}</td>
                <td class="px-4 py-3 text-sm text-right font-medium text-amber-700">{{ formatMoney(item.amount_offset) }}</td>
                <td class="px-4 py-3 text-sm text-right text-gray-500">{{ formatMoney(item.remaining_after) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Notes -->
      <div v-if="compensation.notes" class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-2">{{ t('notes') }}</h3>
        <p class="text-sm text-gray-600 whitespace-pre-line">{{ compensation.notes }}</p>
      </div>
    </div>

    <!-- Not found -->
    <div v-else class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-16">
      <BaseIcon name="ExclamationCircleIcon" class="h-12 w-12 text-gray-400" />
      <p class="mt-2 text-sm text-gray-500">{{ t('not_found') || 'Compensation not found' }}</p>
    </div>

    <!-- Confirm Dialog -->
    <div v-if="showConfirmDialog" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="showConfirmDialog = false" />
      <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ t('confirm_title') }}</h3>
        <p class="text-sm text-gray-500 mb-6">{{ t('confirm_message') }}</p>
        <div class="flex justify-end space-x-3">
          <BaseButton variant="primary-outline" @click="showConfirmDialog = false">
            {{ t('cancel') }}
          </BaseButton>
          <BaseButton variant="primary" :loading="isConfirming" @click="confirmCompensation">
            {{ t('confirm') }}
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Cancel Dialog -->
    <div v-if="showCancelDialog" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="showCancelDialog = false" />
      <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ t('cancel_title') }}</h3>
        <p class="text-sm text-gray-500 mb-6">{{ t('cancel_message') }}</p>
        <div class="flex justify-end space-x-3">
          <BaseButton variant="primary-outline" @click="showCancelDialog = false">
            {{ t('back') }}
          </BaseButton>
          <BaseButton variant="danger" :loading="isCancelling" @click="cancelCompensation">
            {{ t('cancel') }}
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- PDF Preview Modal -->
    <div v-if="showPdfPreview" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="closePdfPreview" />
      <div class="relative bg-white rounded-lg shadow-xl w-full max-w-4xl mx-4 h-5/6 flex flex-col">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">{{ t('generate_pdf') }}</h3>
          <div class="flex items-center space-x-2">
            <BaseButton variant="primary" size="sm" @click="downloadFromPreview">
              <template #left="slotProps">
                <BaseIcon name="ArrowDownTrayIcon" :class="slotProps.class" />
              </template>
              {{ t('download_pdf') }}
            </BaseButton>
            <button class="text-gray-400 hover:text-gray-600" @click="closePdfPreview">
              <BaseIcon name="XMarkIcon" class="h-5 w-5" />
            </button>
          </div>
        </div>
        <div class="flex-1 p-2">
          <iframe
            v-if="previewPdfUrl"
            :src="previewPdfUrl"
            class="w-full h-full border-0 rounded"
          />
        </div>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useNotificationStore } from '@/scripts/stores/notification'
import compensationMessages from '@/scripts/admin/i18n/compensations.js'

const route = useRoute()
const router = useRouter()
const notificationStore = useNotificationStore()

const locale = document.documentElement.lang || 'mk'
function t(key) {
  return compensationMessages[locale]?.compensations?.[key]
    || compensationMessages['en']?.compensations?.[key]
    || key
}

// State
const compensation = ref(null)
const isLoading = ref(false)
const isConfirming = ref(false)
const isCancelling = ref(false)
const isDownloading = ref(false)
const isPreviewing = ref(false)
const showConfirmDialog = ref(false)
const showCancelDialog = ref(false)
const showPdfPreview = ref(false)
const previewPdfUrl = ref(null)
const pdfBlob = ref(null)

// Computed
const receivableItems = computed(() => {
  return (compensation.value?.items || []).filter(i => i.side === 'receivable')
})

const payableItems = computed(() => {
  return (compensation.value?.items || []).filter(i => i.side === 'payable')
})

const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }
const formattedLocale = localeMap[locale] || 'mk-MK'

// Methods
function formatMoney(cents) {
  if (!cents && cents !== 0) return '-'
  return new Intl.NumberFormat(formattedLocale, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(cents / 100)
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString(formattedLocale, { year: 'numeric', month: '2-digit', day: '2-digit' })
}

function statusBadgeClass(status) {
  switch (status) {
    case 'draft': return 'bg-gray-100 text-gray-700'
    case 'confirmed': return 'bg-green-100 text-green-800'
    case 'cancelled': return 'bg-red-100 text-red-800'
    default: return 'bg-gray-100 text-gray-700'
  }
}

function statusLabel(status) {
  switch (status) {
    case 'draft': return t('status_draft')
    case 'confirmed': return t('status_confirmed')
    case 'cancelled': return t('status_cancelled')
    default: return status
  }
}

async function fetchCompensation() {
  const id = route.params.id
  if (!id) return

  isLoading.value = true
  try {
    const response = await window.axios.get(`/compensations/${id}`)
    compensation.value = response.data?.data || null
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_loading') || 'Failed to load compensation',
    })
  } finally {
    isLoading.value = false
  }
}

async function confirmCompensation() {
  isConfirming.value = true
  try {
    const response = await window.axios.post(`/compensations/${compensation.value.id}/confirm`)
    compensation.value = response.data?.data || compensation.value
    showConfirmDialog.value = false
    notificationStore.showNotification({
      type: 'success',
      message: response.data?.message || t('confirmed_success') || 'Compensation confirmed',
    })
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_confirming') || 'Failed to confirm',
    })
  } finally {
    isConfirming.value = false
  }
}

async function cancelCompensation() {
  isCancelling.value = true
  try {
    const response = await window.axios.post(`/compensations/${compensation.value.id}/cancel`)
    compensation.value = response.data?.data || compensation.value
    showCancelDialog.value = false
    notificationStore.showNotification({
      type: 'success',
      message: response.data?.message || t('cancelled_success') || 'Compensation cancelled',
    })
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_cancelling') || 'Failed to cancel',
    })
  } finally {
    isCancelling.value = false
  }
}

async function downloadPdf() {
  isDownloading.value = true
  try {
    const response = await window.axios.get(`/compensations/${compensation.value.id}/pdf`, {
      responseType: 'blob',
    })
    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `kompenzacija_${compensation.value.compensation_number}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: t('error_download_pdf') || 'Failed to download PDF',
    })
  } finally {
    isDownloading.value = false
  }
}

async function previewPdf() {
  isPreviewing.value = true
  try {
    const response = await window.axios.get(`/compensations/${compensation.value.id}/pdf`, {
      responseType: 'blob',
    })
    pdfBlob.value = new Blob([response.data], { type: 'application/pdf' })
    previewPdfUrl.value = window.URL.createObjectURL(pdfBlob.value)
    showPdfPreview.value = true
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: t('error_preview_pdf') || 'Failed to preview PDF',
    })
  } finally {
    isPreviewing.value = false
  }
}

function downloadFromPreview() {
  if (!pdfBlob.value) return
  const url = window.URL.createObjectURL(pdfBlob.value)
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', `kompenzacija_${compensation.value.compensation_number}.pdf`)
  document.body.appendChild(link)
  link.click()
  link.remove()
  window.URL.revokeObjectURL(url)
}

function closePdfPreview() {
  showPdfPreview.value = false
  if (previewPdfUrl.value) {
    window.URL.revokeObjectURL(previewPdfUrl.value)
    previewPdfUrl.value = null
  }
}

async function printPdf() {
  try {
    const response = await window.axios.get(`/compensations/${compensation.value.id}/pdf`, {
      responseType: 'blob',
    })
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const win = window.open(url)
    if (win) {
      win.addEventListener('load', () => win.print())
    }
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: t('error_preview_pdf') || 'Failed to print',
    })
  }
}

// Lifecycle
onMounted(() => {
  fetchCompensation()
})

onUnmounted(() => {
  closePdfPreview()
})
</script>

<!-- CLAUDE-CHECKPOINT -->
