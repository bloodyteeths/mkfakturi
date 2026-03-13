<template>
  <div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-6 flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">
            {{ $t('documents.title', 'Documents') }}
          </h1>
          <p class="mt-1 text-sm text-gray-600">
            {{ $t('documents.subtitle', 'Upload documents — AI classifies, extracts data, and creates draft bills automatically') }}
          </p>
        </div>
        <button
          @click="showUploadModal = true"
          class="inline-flex items-center px-4 py-2 bg-primary-500 text-white rounded-md font-medium hover:bg-primary-600 transition-colors"
        >
          <DocumentArrowUpIcon class="h-5 w-5 mr-2" />
          {{ $t('documents.upload', 'Upload Document') }}
        </button>
      </div>

      <!-- Stats Bar -->
      <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4 text-center">
          <div class="text-2xl font-bold text-gray-900">{{ store.stats.total }}</div>
          <div class="text-xs text-gray-500">{{ $t('documents.stat_total', 'Total') }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
          <div class="text-2xl font-bold text-blue-600">{{ store.stats.processing + store.stats.pending }}</div>
          <div class="text-xs text-gray-500">{{ $t('documents.stat_processing', 'Processing') }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
          <div class="text-2xl font-bold text-amber-600">{{ store.stats.extracted }}</div>
          <div class="text-xs text-gray-500">{{ $t('documents.stat_ready', 'Ready for Review') }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
          <div class="text-2xl font-bold text-green-600">{{ store.stats.confirmed }}</div>
          <div class="text-xs text-gray-500">{{ $t('documents.stat_confirmed', 'Confirmed') }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
          <div class="text-2xl font-bold text-red-600">{{ store.stats.failed }}</div>
          <div class="text-xs text-gray-500">{{ $t('documents.stat_failed', 'Failed') }}</div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white shadow rounded-lg p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('documents.filter_category', 'Category') }}</label>
            <select
              v-model="filterCategory"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"
              @change="loadDocuments"
            >
              <option value="">{{ $t('documents.all', 'All') }}</option>
              <option value="invoice">{{ $t('documents.type_invoice', 'Invoice') }}</option>
              <option value="receipt">{{ $t('documents.type_receipt', 'Receipt') }}</option>
              <option value="contract">{{ $t('documents.type_contract', 'Contract') }}</option>
              <option value="bank_statement">{{ $t('documents.type_bank_statement', 'Bank Statement') }}</option>
              <option value="tax_form">{{ $t('documents.type_tax_form', 'Tax Form') }}</option>
              <option value="product_list">{{ $t('documents.type_product_list', 'Product List') }}</option>
              <option value="other">{{ $t('documents.type_other', 'Other') }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('documents.filter_processing', 'Processing Status') }}</label>
            <select
              v-model="filterProcessing"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"
              @change="loadDocuments"
            >
              <option value="">{{ $t('documents.all', 'All') }}</option>
              <option value="pending">{{ $t('documents.status_pending', 'Pending') }}</option>
              <option value="classifying">{{ $t('documents.status_classifying', 'Classifying...') }}</option>
              <option value="extracting">{{ $t('documents.status_extracting', 'Extracting...') }}</option>
              <option value="extracted">{{ $t('documents.status_extracted', 'Ready for Review') }}</option>
              <option value="confirmed">{{ $t('documents.status_confirmed', 'Confirmed') }}</option>
              <option value="failed">{{ $t('documents.status_failed', 'Failed') }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('documents.filter_status', 'Review Status') }}</label>
            <select
              v-model="filterStatus"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"
              @change="loadDocuments"
            >
              <option value="">{{ $t('documents.all', 'All') }}</option>
              <option value="pending_review">{{ $t('documents.review_pending', 'Pending Review') }}</option>
              <option value="reviewed">{{ $t('documents.review_done', 'Reviewed') }}</option>
              <option value="rejected">{{ $t('documents.review_rejected', 'Rejected') }}</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Upload Modal -->
      <Teleport to="body">
        <div v-if="showUploadModal" class="fixed inset-0 z-50 overflow-y-auto">
          <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeUploadModal"></div>
            <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full">
              <div class="px-6 pt-5 pb-4">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                  {{ $t('documents.upload_title', 'Upload Document') }}
                </h3>

                <!-- Drop Zone -->
                <div
                  @dragover.prevent="isDragging = true"
                  @dragleave.prevent="isDragging = false"
                  @drop.prevent="handleDrop"
                  @click="$refs.fileInput?.click()"
                  :class="[
                    'border-2 border-dashed rounded-lg p-8 text-center cursor-pointer transition-colors',
                    isDragging ? 'border-primary-500 bg-primary-50' : 'border-gray-300 hover:border-gray-400'
                  ]"
                >
                  <input
                    ref="fileInput"
                    type="file"
                    class="hidden"
                    accept=".pdf,.png,.jpg,.jpeg,.xlsx,.csv"
                    multiple
                    @change="handleFileSelect"
                  />
                  <DocumentArrowUpIcon class="mx-auto h-12 w-12 text-gray-400" />
                  <p v-if="!selectedFiles.length" class="mt-2 text-sm text-gray-600">
                    {{ $t('documents.drop_hint', 'Drag files here or click to select') }}
                  </p>
                  <div v-else class="mt-2 space-y-1">
                    <p v-for="(f, i) in selectedFiles" :key="i" class="text-sm text-primary-600 font-medium">
                      {{ f.name }} ({{ formatFileSize(f.size) }})
                    </p>
                  </div>
                  <p class="mt-1 text-xs text-gray-500">PDF, PNG, JPEG, XLSX, CSV - max 10MB</p>
                </div>

                <!-- Category Selector -->
                <div class="mt-4">
                  <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('documents.category', 'Category') }}</label>
                  <select
                    v-model="uploadCategory"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"
                  >
                    <option value="">{{ $t('documents.auto_detect', 'Auto-detect (AI)') }}</option>
                    <option value="invoice">{{ $t('documents.type_invoice', 'Invoice') }}</option>
                    <option value="receipt">{{ $t('documents.type_receipt', 'Receipt') }}</option>
                    <option value="contract">{{ $t('documents.type_contract', 'Contract') }}</option>
                    <option value="bank_statement">{{ $t('documents.type_bank_statement', 'Bank Statement') }}</option>
                    <option value="other">{{ $t('documents.type_other', 'Other') }}</option>
                  </select>
                  <p class="text-xs text-gray-500 mt-1">{{ $t('documents.auto_detect_hint', 'Leave empty for AI auto-classification') }}</p>
                </div>

                <!-- Notes -->
                <div class="mt-4">
                  <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('documents.notes', 'Notes (optional)') }}</label>
                  <textarea
                    v-model="uploadNotes"
                    rows="2"
                    maxlength="500"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"
                  ></textarea>
                </div>

                <div v-if="uploadError" class="mt-4 bg-red-50 border border-red-200 rounded-md p-3">
                  <p class="text-sm text-red-700">{{ uploadError }}</p>
                </div>
              </div>

              <div class="bg-gray-50 px-6 py-3 flex justify-end space-x-3 rounded-b-lg">
                <button @click="closeUploadModal" :disabled="isUploading" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-md text-sm font-medium disabled:opacity-50">
                  {{ $t('general.cancel', 'Cancel') }}
                </button>
                <button @click="uploadDocuments" :disabled="!selectedFiles.length || isUploading" class="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-md text-sm font-medium disabled:opacity-50">
                  {{ isUploading ? $t('documents.uploading', 'Uploading...') : $t('documents.upload', 'Upload') }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </Teleport>

      <!-- Delete Confirmation Modal -->
      <Teleport to="body">
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
          <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showDeleteModal = false"></div>
            <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
              <h3 class="text-lg font-medium text-gray-900 mb-2">{{ $t('documents.delete_title', 'Delete Document') }}</h3>
              <p class="text-sm text-gray-500 mb-4">
                {{ $t('documents.delete_confirm', 'Are you sure you want to delete') }} "{{ documentToDelete?.original_filename }}"?
              </p>
              <div class="flex justify-end space-x-3">
                <button @click="showDeleteModal = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-md text-sm font-medium">
                  {{ $t('general.cancel', 'Cancel') }}
                </button>
                <button @click="confirmDelete" :disabled="isDeleting" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm font-medium disabled:opacity-50">
                  {{ isDeleting ? '...' : $t('general.delete', 'Delete') }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </Teleport>

      <!-- Loading State -->
      <div v-if="store.isLoading" class="space-y-4">
        <div v-for="i in 5" :key="i" class="bg-white shadow rounded-lg p-4 animate-pulse">
          <div class="flex items-center space-x-4">
            <div class="h-10 w-10 bg-gray-200 rounded"></div>
            <div class="flex-1">
              <div class="h-4 bg-gray-200 rounded w-48 mb-2"></div>
              <div class="h-3 bg-gray-200 rounded w-32"></div>
            </div>
            <div class="h-6 bg-gray-200 rounded w-24"></div>
          </div>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="store.error" class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
        <p class="text-sm text-red-700">{{ store.error }}</p>
        <button @click="loadDocuments" class="mt-2 text-sm font-medium text-red-600 hover:text-red-500">
          {{ $t('general.retry', 'Try Again') }}
        </button>
      </div>

      <!-- Documents List -->
      <div v-else>
        <!-- Empty State -->
        <div v-if="!store.documents.length" class="bg-white shadow rounded-lg py-12 text-center">
          <DocumentArrowUpIcon class="mx-auto h-16 w-16 text-gray-400" />
          <h3 class="mt-4 text-lg font-medium text-gray-900">{{ $t('documents.empty_title', 'No Documents') }}</h3>
          <p class="mt-2 text-sm text-gray-500">{{ $t('documents.empty_hint', 'Upload your first document to get started.') }}</p>
          <button @click="showUploadModal = true" class="mt-4 inline-flex items-center px-4 py-2 bg-primary-500 text-white rounded-md font-medium hover:bg-primary-600">
            {{ $t('documents.upload', 'Upload Document') }}
          </button>
        </div>

        <!-- Document Table -->
        <div v-else class="bg-white shadow overflow-hidden sm:rounded-lg">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('documents.col_document', 'Document') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('documents.col_classification', 'AI Classification') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('documents.col_summary', 'AI Summary') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('documents.col_status', 'Status') }}</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('documents.col_actions', 'Actions') }}</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="doc in store.documents" :key="doc.id" class="hover:bg-gray-50 cursor-pointer" @click="navigateToReview(doc)">
                <!-- File Info -->
                <td class="px-6 py-4">
                  <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10 rounded bg-gray-100 flex items-center justify-center">
                      <component :is="getFileIcon(doc.mime_type)" class="h-6 w-6 text-gray-500" />
                    </div>
                    <div class="ml-3">
                      <div class="text-sm font-medium text-gray-900 truncate max-w-[200px]" :title="doc.original_filename">
                        {{ doc.original_filename }}
                      </div>
                      <div class="text-xs text-gray-500">
                        {{ formatFileSize(doc.file_size) }} &middot; {{ formatDate(doc.created_at) }}
                      </div>
                    </div>
                  </div>
                </td>

                <!-- AI Classification -->
                <td class="px-6 py-4">
                  <div v-if="isProcessing(doc)" class="flex items-center space-x-2">
                    <svg class="animate-spin h-4 w-4 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-sm text-primary-600">{{ getProcessingLabel(doc.processing_status) }}</span>
                  </div>
                  <div v-else-if="doc.ai_classification" class="space-y-1">
                    <span :class="getCategoryBadgeClass(doc.ai_classification.type)">
                      {{ getCategoryLabel(doc.ai_classification.type) }}
                    </span>
                    <div v-if="doc.ai_classification.confidence" class="text-xs text-gray-400">
                      {{ Math.round(doc.ai_classification.confidence * 100) }}% {{ $t('documents.confidence', 'confidence') }}
                    </div>
                  </div>
                  <span v-else class="text-xs text-gray-400">-</span>
                </td>

                <!-- AI Summary -->
                <td class="px-6 py-4">
                  <div v-if="doc.ai_classification?.summary" class="text-sm text-gray-700 max-w-[350px] line-clamp-3" :title="doc.ai_classification.summary">
                    {{ doc.ai_classification.summary }}
                  </div>
                  <div v-else-if="doc.error_message" class="text-sm text-red-600 max-w-[350px] line-clamp-2" :title="doc.error_message">
                    {{ doc.error_message }}
                  </div>
                  <span v-else class="text-xs text-gray-400">-</span>
                </td>

                <!-- Processing Status -->
                <td class="px-6 py-4">
                  <span :class="getProcessingBadgeClass(doc.processing_status)">
                    {{ getProcessingLabel(doc.processing_status) }}
                  </span>
                  <div v-if="doc.linked_bill_id" class="mt-1">
                    <router-link
                      :to="{ name: 'bills.view', params: { id: doc.linked_bill_id } }"
                      class="text-xs text-primary-600 hover:text-primary-800"
                      @click.stop
                    >
                      {{ $t('documents.view_bill', 'View Bill') }} #{{ doc.linked_bill_id }}
                    </router-link>
                  </div>
                </td>

                <!-- Actions -->
                <td class="px-6 py-4 text-right text-sm space-x-1">
                  <button
                    v-if="doc.processing_status === 'extracted'"
                    @click.stop="$router.push({ name: 'documents.review', params: { id: doc.id } })"
                    class="inline-flex items-center px-2.5 py-1.5 bg-primary-50 text-primary-700 rounded text-xs font-medium hover:bg-primary-100"
                  >
                    {{ $t('documents.review', 'Review') }}
                  </button>
                  <button
                    v-if="doc.processing_status === 'confirmed'"
                    @click.stop="$router.push({ name: 'documents.review', params: { id: doc.id } })"
                    class="inline-flex items-center px-2.5 py-1.5 bg-green-50 text-green-700 rounded text-xs font-medium hover:bg-green-100"
                  >
                    {{ $t('documents.view', 'View') }}
                  </button>
                  <button
                    v-if="doc.processing_status === 'failed'"
                    @click.stop="reprocessDoc(doc.id)"
                    class="inline-flex items-center px-2.5 py-1.5 bg-amber-50 text-amber-700 rounded text-xs font-medium hover:bg-amber-100"
                  >
                    {{ $t('documents.reprocess', 'Reprocess') }}
                  </button>
                  <button
                    @click.stop="downloadDocument(doc)"
                    class="inline-flex items-center px-2.5 py-1.5 bg-gray-50 text-gray-700 rounded text-xs font-medium hover:bg-gray-100"
                  >
                    {{ $t('general.download', 'Download') }}
                  </button>
                  <button
                    v-if="doc.status === 'pending_review' && doc.processing_status !== 'confirmed'"
                    @click.stop="openDeleteModal(doc)"
                    class="inline-flex items-center px-2.5 py-1.5 bg-red-50 text-red-700 rounded text-xs font-medium hover:bg-red-100"
                  >
                    {{ $t('general.delete', 'Delete') }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Pagination -->
          <div v-if="store.pagination.lastPage > 1" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
            <div class="text-sm text-gray-700">
              {{ store.pagination.from }}-{{ store.pagination.to }} / {{ store.pagination.total }}
            </div>
            <div class="flex space-x-2">
              <button
                @click="goToPage(store.pagination.currentPage - 1)"
                :disabled="store.pagination.currentPage === 1"
                class="px-3 py-1 border border-gray-300 rounded text-sm disabled:opacity-50"
              >
                &laquo;
              </button>
              <button
                @click="goToPage(store.pagination.currentPage + 1)"
                :disabled="store.pagination.currentPage === store.pagination.lastPage"
                class="px-3 py-1 border border-gray-300 rounded text-sm disabled:opacity-50"
              >
                &raquo;
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useDocumentHubStore } from '@/scripts/admin/stores/document-hub'
import { useNotificationStore } from '@/scripts/stores/notification'
import { DocumentArrowUpIcon, DocumentTextIcon, PhotoIcon, TableCellsIcon } from '@heroicons/vue/24/outline'

const router = useRouter()
const { t } = useI18n()
const store = useDocumentHubStore()
const notificationStore = useNotificationStore()

// Filters
const filterCategory = ref('')
const filterStatus = ref('')
const filterProcessing = ref('')

// Upload state
const showUploadModal = ref(false)
const selectedFiles = ref([])
const uploadCategory = ref('')
const uploadNotes = ref('')
const isUploading = ref(false)
const uploadError = ref(null)
const isDragging = ref(false)

// Delete state
const showDeleteModal = ref(false)
const documentToDelete = ref(null)
const isDeleting = ref(false)

const loadDocuments = () => {
  const params = {}
  if (filterCategory.value) params.category = filterCategory.value
  if (filterStatus.value) params.status = filterStatus.value
  if (filterProcessing.value) params.processing_status = filterProcessing.value
  store.fetchDocuments(params)
}

const handleFileSelect = (event) => {
  const files = Array.from(event.target.files || [])
  files.forEach(validateAndAddFile)
}

const handleDrop = (event) => {
  isDragging.value = false
  const files = Array.from(event.dataTransfer?.files || [])
  files.forEach(validateAndAddFile)
}

const validateAndAddFile = (file) => {
  uploadError.value = null
  if (file.size > 10485760) {
    uploadError.value = t('documents.error_too_large', 'File is too large. Maximum size is 10MB.')
    return
  }
  const allowedTypes = ['application/pdf', 'image/png', 'image/jpeg', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv']
  if (!allowedTypes.includes(file.type)) {
    uploadError.value = t('documents.error_invalid_format', 'Invalid format. Allowed: PDF, PNG, JPEG, XLSX, CSV.')
    return
  }
  selectedFiles.value.push(file)
}

const uploadDocuments = async () => {
  if (!selectedFiles.value.length) return

  isUploading.value = true
  uploadError.value = null

  try {
    for (const file of selectedFiles.value) {
      await store.uploadDocument(file, uploadCategory.value || 'other', uploadNotes.value)
    }

    notificationStore.showNotification({
      type: 'success',
      message: t('documents.upload_success', 'Document(s) uploaded. AI processing started.'),
    })

    closeUploadModal()
  } catch (err) {
    const errors = err?.response?.data?.errors
    if (errors) {
      const firstError = Object.values(errors)[0]
      uploadError.value = Array.isArray(firstError) ? firstError[0] : firstError
    } else {
      uploadError.value = err?.response?.data?.message || 'Upload failed.'
    }
  } finally {
    isUploading.value = false
  }
}

const closeUploadModal = () => {
  showUploadModal.value = false
  selectedFiles.value = []
  uploadCategory.value = ''
  uploadNotes.value = ''
  uploadError.value = null
  isDragging.value = false
}

const openDeleteModal = (doc) => {
  documentToDelete.value = doc
  showDeleteModal.value = true
}

const confirmDelete = async () => {
  if (!documentToDelete.value?.id) return
  isDeleting.value = true
  try {
    await store.deleteDocument(documentToDelete.value.id)
    notificationStore.showNotification({ type: 'success', message: t('documents.deleted', 'Document deleted.') })
    showDeleteModal.value = false
    documentToDelete.value = null
  } catch (err) {
    notificationStore.showNotification({ type: 'error', message: err?.response?.data?.message || 'Delete failed.' })
  } finally {
    isDeleting.value = false
  }
}

const reprocessDoc = async (id) => {
  try {
    await store.reprocessDocument(id)
    notificationStore.showNotification({ type: 'success', message: t('documents.reprocess_started', 'Reprocessing started.') })
  } catch (err) {
    notificationStore.showNotification({ type: 'error', message: err?.response?.data?.message || 'Reprocess failed.' })
  }
}

const downloadDocument = (doc) => {
  const link = document.createElement('a')
  link.href = `/api/v1/client-documents/${doc.id}/download`
  link.download = doc.original_filename
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

const goToPage = (page) => {
  if (page < 1 || page > store.pagination.lastPage) return
  store.fetchDocuments({ page })
}

const navigateToReview = (doc) => {
  if (['extracted', 'confirmed'].includes(doc.processing_status)) {
    router.push({ name: 'documents.review', params: { id: doc.id } })
  }
}

// Helpers
const isProcessing = (doc) => ['pending', 'classifying', 'extracting'].includes(doc.processing_status)

const getFileIcon = (mimeType) => {
  if (mimeType?.startsWith('image/')) return PhotoIcon
  if (mimeType?.includes('spreadsheet') || mimeType === 'text/csv') return TableCellsIcon
  return DocumentTextIcon
}

const getCategoryLabel = (type) => {
  const labels = {
    invoice: t('documents.type_invoice', 'Invoice'),
    receipt: t('documents.type_receipt', 'Receipt'),
    contract: t('documents.type_contract', 'Contract'),
    bank_statement: t('documents.type_bank_statement', 'Bank Statement'),
    tax_form: t('documents.type_tax_form', 'Tax Form'),
    product_list: t('documents.type_product_list', 'Product List'),
    other: t('documents.type_other', 'Other'),
  }
  return labels[type] || type
}

const getCategoryBadgeClass = (type) => {
  const classes = {
    invoice: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800',
    receipt: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800',
    contract: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800',
    bank_statement: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-cyan-100 text-cyan-800',
    tax_form: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800',
    product_list: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800',
    other: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800',
  }
  return classes[type] || classes.other
}

const getProcessingLabel = (status) => {
  const labels = {
    pending: t('documents.status_pending', 'Pending'),
    classifying: t('documents.status_classifying', 'AI classifying...'),
    extracting: t('documents.status_extracting', 'AI extracting...'),
    extracted: t('documents.status_extracted', 'Ready for Review'),
    confirmed: t('documents.status_confirmed', 'Confirmed'),
    failed: t('documents.status_failed', 'Failed'),
  }
  return labels[status] || status
}

const getProcessingBadgeClass = (status) => {
  const classes = {
    pending: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700',
    classifying: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700',
    extracting: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700',
    extracted: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800',
    confirmed: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800',
    failed: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800',
  }
  return classes[status] || classes.pending
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  try {
    return new Date(dateString).toLocaleDateString('mk-MK', { year: 'numeric', month: 'short', day: 'numeric' })
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

onMounted(() => loadDocuments())

onBeforeUnmount(() => store.stopAllPolling())
</script>
