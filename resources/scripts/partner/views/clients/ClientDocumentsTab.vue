<template>
  <div class="space-y-6">
    <!-- Header with pending badge and bulk download -->
    <div class="flex items-center justify-between">
      <div class="flex items-center">
        <h4 class="text-sm font-medium text-gray-900">Документи</h4>
        <span
          v-if="pendingCount > 0"
          class="ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-yellow-800 bg-yellow-100 rounded-full"
        >
          {{ pendingCount }} чекаат
        </span>
      </div>
      <button
        v-if="pendingCount > 0"
        @click="bulkDownloadDocuments"
        :disabled="isBulkDownloading"
        class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 disabled:opacity-50 transition-colors"
      >
        <svg v-if="isBulkDownloading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <svg v-else class="h-4 w-4 mr-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
        </svg>
        {{ isBulkDownloading ? 'Се преземаат...' : 'Преземи сите' }}
      </button>
    </div>

    <!-- Filters -->
    <div class="grid grid-cols-2 gap-3">
      <div>
        <label for="doc-filter-category" class="block text-xs font-medium text-gray-500 mb-1">Категорија</label>
        <select
          id="doc-filter-category"
          v-model="filterCategory"
          class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
          @change="loadDocuments"
        >
          <option value="">Сите</option>
          <option value="invoice">Фактура</option>
          <option value="receipt">Сметка</option>
          <option value="contract">Договор</option>
          <option value="bank_statement">Банкарски извод</option>
          <option value="other">Друго</option>
        </select>
      </div>
      <div>
        <label for="doc-filter-status" class="block text-xs font-medium text-gray-500 mb-1">Статус</label>
        <select
          id="doc-filter-status"
          v-model="filterStatus"
          class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
          @change="loadDocuments"
        >
          <option value="">Сите</option>
          <option value="pending_review">Чека преглед</option>
          <option value="reviewed">Прегледан</option>
          <option value="rejected">Одбиен</option>
        </select>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="flex justify-center py-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500" aria-label="Се вчитува..."></div>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-md p-3">
      <p class="text-sm text-red-700">{{ error }}</p>
      <button @click="loadDocuments" class="mt-1 text-sm font-medium text-red-600 hover:text-red-500">
        Обиди се повторно
      </button>
    </div>

    <!-- Empty -->
    <div v-else-if="documents.length === 0" class="text-center py-8">
      <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      <p class="mt-3 text-sm text-gray-500">Нема документи од клиентот.</p>
    </div>

    <!-- Documents List -->
    <div v-else class="space-y-3">
      <div
        v-for="doc in documents"
        :key="doc.id"
        class="border border-gray-200 rounded-lg p-4 hover:border-gray-300 transition-colors"
      >
        <div class="flex items-start justify-between">
          <div class="flex items-start space-x-3 flex-1 min-w-0">
            <!-- File icon -->
            <div class="flex-shrink-0 h-10 w-10 rounded bg-gray-100 flex items-center justify-center">
              <svg class="h-6 w-6 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-medium text-gray-900 truncate" :title="doc.original_filename">
                {{ doc.original_filename }}
              </p>
              <div class="flex items-center mt-1 space-x-3">
                <span class="text-xs text-gray-500">{{ getCategoryLabel(doc.category) }}</span>
                <span class="text-xs text-gray-400">{{ formatDate(doc.created_at) }}</span>
                <span class="text-xs text-gray-400">{{ formatFileSize(doc.file_size) }}</span>
              </div>
              <div v-if="doc.notes" class="mt-1 text-xs text-gray-600">
                {{ doc.notes }}
              </div>
              <div v-if="doc.uploader" class="mt-1 text-xs text-gray-400">
                Прикачено од: {{ doc.uploader.name }}
              </div>
            </div>
          </div>

          <!-- Status & Actions -->
          <div class="flex-shrink-0 ml-4 flex flex-col items-end space-y-2">
            <span :class="getStatusBadgeClass(doc.status)">
              {{ getStatusLabel(doc.status) }}
            </span>

            <!-- Rejection reason -->
            <div v-if="doc.status === 'rejected' && doc.rejection_reason" class="text-xs text-red-600 max-w-xs text-right">
              {{ doc.rejection_reason }}
            </div>

            <!-- Action buttons -->
            <div class="flex items-center space-x-2">
              <!-- Download -->
              <button
                @click="downloadDocument(doc)"
                class="text-blue-600 hover:text-blue-800 text-xs font-medium"
                :title="'Преземи ' + doc.original_filename"
              >
                Преземи
              </button>

              <!-- Review (only for pending docs) -->
              <button
                v-if="doc.status === 'pending_review'"
                @click="openReviewModal(doc)"
                class="text-green-600 hover:text-green-800 text-xs font-medium"
              >
                Прегледај
              </button>

              <!-- Reject (only for pending docs) -->
              <button
                v-if="doc.status === 'pending_review'"
                @click="openRejectModal(doc)"
                class="text-red-600 hover:text-red-800 text-xs font-medium"
              >
                Одбиј
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Review Modal -->
    <div v-if="showReviewModal" class="fixed inset-0 z-[60] overflow-y-auto" aria-labelledby="review-modal-title" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showReviewModal = false"></div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
          <div class="bg-white px-6 pt-5 pb-4">
            <h3 id="review-modal-title" class="text-lg font-medium text-gray-900 mb-4">
              Потврди преглед
            </h3>
            <p class="text-sm text-gray-500 mb-4">
              Потврдете дека документот "{{ reviewingDoc?.original_filename }}" е прегледан.
            </p>
            <div>
              <label for="review-notes" class="block text-sm font-medium text-gray-700 mb-1">Забелешки (опционално)</label>
              <textarea
                id="review-notes"
                v-model="reviewNotes"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500"
                placeholder="Додајте забелешки..."
              ></textarea>
            </div>
          </div>
          <div class="bg-gray-50 px-6 py-3 flex justify-end space-x-3">
            <button
              @click="showReviewModal = false"
              :disabled="isProcessing"
              class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-md text-sm font-medium disabled:opacity-50"
            >
              Откажи
            </button>
            <button
              @click="confirmReview"
              :disabled="isProcessing"
              class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md text-sm font-medium disabled:opacity-50"
            >
              {{ isProcessing ? 'Се обработува...' : 'Потврди' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Reject Modal -->
    <div v-if="showRejectModal" class="fixed inset-0 z-[60] overflow-y-auto" aria-labelledby="reject-modal-title" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showRejectModal = false"></div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
          <div class="bg-white px-6 pt-5 pb-4">
            <h3 id="reject-modal-title" class="text-lg font-medium text-gray-900 mb-4">
              Одбиј документ
            </h3>
            <p class="text-sm text-gray-500 mb-4">
              Наведете причина за одбивање на "{{ rejectingDoc?.original_filename }}".
            </p>
            <div>
              <label for="reject-reason" class="block text-sm font-medium text-gray-700 mb-1">Причина *</label>
              <textarea
                id="reject-reason"
                v-model="rejectReason"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-red-500 focus:border-red-500"
                placeholder="Наведете причина за одбивање..."
              ></textarea>
              <p v-if="rejectError" class="mt-1 text-xs text-red-600">{{ rejectError }}</p>
            </div>
          </div>
          <div class="bg-gray-50 px-6 py-3 flex justify-end space-x-3">
            <button
              @click="showRejectModal = false"
              :disabled="isProcessing"
              class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-md text-sm font-medium disabled:opacity-50"
            >
              Откажи
            </button>
            <button
              @click="confirmReject"
              :disabled="isProcessing || !rejectReason.trim()"
              class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm font-medium disabled:opacity-50"
            >
              {{ isProcessing ? 'Се обработува...' : 'Одбиј' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { useNotificationStore } from '@/scripts/stores/notification'

const props = defineProps({
  companyId: {
    type: [Number, String],
    required: true,
  },
})

const notificationStore = useNotificationStore()

// State
const documents = ref([])
const isLoading = ref(false)
const error = ref(null)
const pendingCount = ref(0)
const filterCategory = ref('')
const filterStatus = ref('')

// Review modal
const showReviewModal = ref(false)
const reviewingDoc = ref(null)
const reviewNotes = ref('')
const isProcessing = ref(false)

// Reject modal
const showRejectModal = ref(false)
const rejectingDoc = ref(null)
const rejectReason = ref('')
const rejectError = ref(null)

// Bulk download
const isBulkDownloading = ref(false)

// Methods
const loadDocuments = async () => {
  if (!props.companyId) return

  isLoading.value = true
  error.value = null

  try {
    const params = {}
    if (filterCategory.value) params.category = filterCategory.value
    if (filterStatus.value) params.status = filterStatus.value

    const { data } = await window.axios.get(`/partner/companies/${props.companyId}/documents`, { params })

    documents.value = data.data || []
    pendingCount.value = data.pending_count || 0
  } catch (err) {
    error.value = err?.response?.data?.message || 'Failed to load documents.'
  } finally {
    isLoading.value = false
  }
}

const downloadDocument = async (doc) => {
  try {
    const response = await window.axios.get(
      `/partner/companies/${props.companyId}/documents/${doc.id}/download`,
      { responseType: 'blob' }
    )

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', doc.original_filename)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (err) {
    notificationStore.showNotification({
      type: 'error',
      message: err?.response?.data?.message || 'Failed to download document.',
    })
  }
}

const bulkDownloadDocuments = async () => {
  isBulkDownloading.value = true

  try {
    const response = await window.axios.get(
      `/partner/companies/${props.companyId}/documents/download-all`,
      { responseType: 'blob' }
    )

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `documents-${props.companyId}.zip`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (err) {
    notificationStore.showNotification({
      type: 'error',
      message: err?.response?.data?.message || 'Failed to download documents.',
    })
  } finally {
    isBulkDownloading.value = false
  }
}

const openReviewModal = (doc) => {
  reviewingDoc.value = doc
  reviewNotes.value = ''
  showReviewModal.value = true
}

const confirmReview = async () => {
  if (!reviewingDoc.value?.id) return

  isProcessing.value = true

  try {
    await window.axios.post(
      `/partner/companies/${props.companyId}/documents/${reviewingDoc.value.id}/review`,
      { notes: reviewNotes.value || null }
    )

    notificationStore.showNotification({
      type: 'success',
      message: 'Документот е означен како прегледан.',
    })

    showReviewModal.value = false
    reviewingDoc.value = null
    loadDocuments()
  } catch (err) {
    notificationStore.showNotification({
      type: 'error',
      message: err?.response?.data?.message || 'Failed to review document.',
    })
  } finally {
    isProcessing.value = false
  }
}

const openRejectModal = (doc) => {
  rejectingDoc.value = doc
  rejectReason.value = ''
  rejectError.value = null
  showRejectModal.value = true
}

const confirmReject = async () => {
  if (!rejectingDoc.value?.id) return

  if (!rejectReason.value.trim()) {
    rejectError.value = 'Причината е задолжителна.'
    return
  }

  isProcessing.value = true

  try {
    await window.axios.post(
      `/partner/companies/${props.companyId}/documents/${rejectingDoc.value.id}/reject`,
      { reason: rejectReason.value }
    )

    notificationStore.showNotification({
      type: 'success',
      message: 'Документот е одбиен.',
    })

    showRejectModal.value = false
    rejectingDoc.value = null
    loadDocuments()
  } catch (err) {
    notificationStore.showNotification({
      type: 'error',
      message: err?.response?.data?.message || 'Failed to reject document.',
    })
  } finally {
    isProcessing.value = false
  }
}

// Helpers
const getCategoryLabel = (category) => {
  const labels = {
    invoice: 'Фактура',
    receipt: 'Сметка',
    contract: 'Договор',
    bank_statement: 'Банкарски извод',
    other: 'Друго',
  }
  return labels[category] || category
}

const getStatusLabel = (status) => {
  const labels = {
    pending_review: 'Чека преглед',
    reviewed: 'Прегледан',
    rejected: 'Одбиен',
  }
  return labels[status] || status
}

const getStatusBadgeClass = (status) => {
  const classes = {
    pending_review: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800',
    reviewed: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800',
    rejected: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800',
  }
  return classes[status] || 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800'
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  try {
    return new Date(dateString).toLocaleDateString('mk-MK', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    })
  } catch {
    return dateString
  }
}

const formatFileSize = (bytes) => {
  if (!bytes) return '0 B'
  const units = ['B', 'KB', 'MB', 'GB']
  let i = 0
  let size = bytes
  while (size >= 1024 && i < units.length - 1) {
    size /= 1024
    i++
  }
  return `${size.toFixed(1)} ${units[i]}`
}

// Watch companyId and reload
watch(() => props.companyId, (newVal) => {
  if (newVal) {
    loadDocuments()
  }
})

// Load on mount
onMounted(() => {
  if (props.companyId) {
    loadDocuments()
  }
})
</script>

// CLAUDE-CHECKPOINT
