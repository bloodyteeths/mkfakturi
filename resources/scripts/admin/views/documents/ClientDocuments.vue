<template>
  <div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8 flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">
            Документи
          </h1>
          <p class="mt-2 text-sm text-gray-600">
            Прикачете фактури, сметки, договори и други документи за вашата сметководствена канцеларија
          </p>
        </div>
        <button
          @click="showUploadModal = true"
          class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md font-medium hover:bg-blue-700 transition-colors"
        >
          <svg class="h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
          </svg>
          Прикачи документ
        </button>
      </div>

      <!-- Filters -->
      <div class="bg-white shadow rounded-lg p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="filter-category" class="block text-sm font-medium text-gray-700 mb-1">Категорија</label>
            <select
              id="filter-category"
              v-model="filterCategory"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
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
            <label for="filter-status" class="block text-sm font-medium text-gray-700 mb-1">Статус</label>
            <select
              id="filter-status"
              v-model="filterStatus"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
              @change="loadDocuments"
            >
              <option value="">Сите</option>
              <option value="pending_review">Чека преглед</option>
              <option value="reviewed">Прегледан</option>
              <option value="rejected">Одбиен</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Upload Modal -->
      <div v-if="showUploadModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="upload-modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeUploadModal"></div>
          <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-6 pt-5 pb-4">
              <h3 id="upload-modal-title" class="text-lg font-medium text-gray-900 mb-4">
                Прикачи документ
              </h3>

              <!-- Drop Zone -->
              <div
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="handleDrop"
                @click="triggerFileInput"
                :class="[
                  'border-2 border-dashed rounded-lg p-8 text-center cursor-pointer transition-colors',
                  isDragging ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-gray-400'
                ]"
              >
                <input
                  ref="fileInput"
                  type="file"
                  class="hidden"
                  accept=".pdf,.png,.jpg,.jpeg,.xlsx,.csv"
                  @change="handleFileSelect"
                />
                <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                <p v-if="!selectedFile" class="mt-2 text-sm text-gray-600">
                  Повлечете датотека овде или кликнете за да изберете
                </p>
                <p v-else class="mt-2 text-sm text-blue-600 font-medium">
                  {{ selectedFile.name }} ({{ formatFileSize(selectedFile.size) }})
                </p>
                <p class="mt-1 text-xs text-gray-500">
                  PDF, PNG, JPEG, XLSX, CSV - максимум 10MB
                </p>
              </div>

              <!-- Category Selector -->
              <div class="mt-4">
                <label for="upload-category" class="block text-sm font-medium text-gray-700 mb-1">Категорија *</label>
                <select
                  id="upload-category"
                  v-model="uploadCategory"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                >
                  <option value="">Изберете категорија</option>
                  <option value="invoice">Фактура</option>
                  <option value="receipt">Сметка</option>
                  <option value="contract">Договор</option>
                  <option value="bank_statement">Банкарски извод</option>
                  <option value="other">Друго</option>
                </select>
              </div>

              <!-- Notes -->
              <div class="mt-4">
                <label for="upload-notes" class="block text-sm font-medium text-gray-700 mb-1">Забелешки (опционално)</label>
                <textarea
                  id="upload-notes"
                  v-model="uploadNotes"
                  rows="3"
                  maxlength="500"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                  placeholder="Додајте забелешки за документот..."
                ></textarea>
                <p class="text-xs text-gray-500 mt-1">{{ uploadNotes.length }}/500</p>
              </div>

              <!-- Upload Error -->
              <div v-if="uploadError" class="mt-4 bg-red-50 border border-red-200 rounded-md p-3">
                <p class="text-sm text-red-700">{{ uploadError }}</p>
              </div>
            </div>

            <div class="bg-gray-50 px-6 py-3 flex justify-end space-x-3">
              <button
                @click="closeUploadModal"
                :disabled="isUploading"
                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-md text-sm font-medium disabled:opacity-50"
              >
                Откажи
              </button>
              <button
                @click="uploadDocument"
                :disabled="!selectedFile || !uploadCategory || isUploading"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm font-medium disabled:opacity-50"
              >
                {{ isUploading ? 'Се прикачува...' : 'Прикачи' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Delete Confirmation Modal -->
      <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="delete-modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showDeleteModal = false"></div>
          <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-6 pt-5 pb-4">
              <div class="sm:flex sm:items-start">
                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                  <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                  </svg>
                </div>
                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                  <h3 id="delete-modal-title" class="text-lg leading-6 font-medium text-gray-900">
                    Избриши документ
                  </h3>
                  <div class="mt-2">
                    <p class="text-sm text-gray-500">
                      Дали сте сигурни дека сакате да го избришете документот "{{ documentToDelete?.original_filename }}"?
                    </p>
                  </div>
                </div>
              </div>
            </div>
            <div class="bg-gray-50 px-6 py-3 flex justify-end space-x-3">
              <button
                @click="showDeleteModal = false"
                :disabled="isDeleting"
                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-md text-sm font-medium disabled:opacity-50"
              >
                Откажи
              </button>
              <button
                @click="confirmDelete"
                :disabled="isDeleting"
                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm font-medium disabled:opacity-50"
              >
                {{ isDeleting ? 'Се брише...' : 'Избриши' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Preview Modal -->
      <div v-if="showPreviewModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="preview-modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4">
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showPreviewModal = false"></div>
          <div class="relative bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b">
              <h3 id="preview-modal-title" class="text-lg font-medium text-gray-900 truncate">{{ previewDoc?.original_filename }}</h3>
              <button @click="showPreviewModal = false" class="text-gray-400 hover:text-gray-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>
            <div class="p-6 overflow-auto" style="max-height: calc(90vh - 80px)">
              <img v-if="previewDoc?.mime_type?.startsWith('image/')" :src="previewUrl" class="max-w-full mx-auto" :alt="previewDoc?.original_filename" />
              <iframe v-else-if="previewDoc?.mime_type === 'application/pdf'" :src="previewUrl" class="w-full" style="height: 70vh" />
            </div>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="space-y-4">
        <div v-for="i in 5" :key="i" class="bg-white shadow rounded-lg p-4 animate-pulse">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <div class="h-10 w-10 bg-gray-200 rounded"></div>
              <div>
                <div class="h-4 bg-gray-200 rounded w-48 mb-2"></div>
                <div class="h-3 bg-gray-200 rounded w-32"></div>
              </div>
            </div>
            <div class="h-6 bg-gray-200 rounded w-20"></div>
          </div>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-md p-4 mb-6" role="alert">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm text-red-700">{{ error }}</p>
            <button @click="loadDocuments" class="mt-2 text-sm font-medium text-red-600 hover:text-red-500">
              Обиди се повторно
            </button>
          </div>
        </div>
      </div>

      <!-- Documents List -->
      <div v-else>
        <!-- Empty State -->
        <div v-if="documents.length === 0" class="bg-white shadow rounded-lg py-12 text-center">
          <svg class="mx-auto h-16 w-16 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <h3 class="mt-4 text-lg font-medium text-gray-900">Нема документи</h3>
          <p class="mt-2 text-sm text-gray-500">Прикачете го вашиот прв документ.</p>
          <button
            @click="showUploadModal = true"
            class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md font-medium hover:bg-blue-700"
          >
            Прикачи документ
          </button>
        </div>

        <!-- Document Table -->
        <div v-else class="bg-white shadow overflow-hidden sm:rounded-lg">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Документ</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Категорија</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Датум</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Акции</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="doc in documents" :key="doc.id" class="hover:bg-gray-50">
                <td class="px-6 py-4">
                  <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10 rounded bg-gray-100 flex items-center justify-center">
                      <svg class="h-6 w-6 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-medium text-gray-900 truncate max-w-xs" :title="doc.original_filename">
                        {{ doc.original_filename }}
                      </div>
                      <div class="text-xs text-gray-500">
                        {{ formatFileSize(doc.file_size) }}
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-700">
                  {{ getCategoryLabel(doc.category) }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">
                  {{ formatDate(doc.created_at) }}
                </td>
                <td class="px-6 py-4">
                  <span :class="getStatusBadgeClass(doc.status)">
                    {{ getStatusLabel(doc.status) }}
                  </span>
                  <div v-if="doc.status === 'rejected' && doc.rejection_reason" class="mt-1 text-xs text-red-600">
                    {{ doc.rejection_reason }}
                  </div>
                </td>
                <td class="px-6 py-4 text-right text-sm space-x-2">
                  <button
                    @click="downloadDocument(doc)"
                    class="text-blue-600 hover:text-blue-800 font-medium"
                    :title="doc.original_filename"
                  >
                    Преземи
                  </button>
                  <button
                    v-if="isPreviewable(doc)"
                    @click="previewDocument(doc)"
                    class="text-green-600 hover:text-green-800 font-medium"
                  >
                    Прегледај
                  </button>
                  <button
                    v-if="doc.status === 'pending_review'"
                    @click="openDeleteModal(doc)"
                    class="text-red-600 hover:text-red-800 font-medium"
                  >
                    Избриши
                  </button>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Pagination -->
          <div v-if="pagination.lastPage > 1" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
              <div>
                <p class="text-sm text-gray-700">
                  Прикажани <span class="font-medium">{{ pagination.from }}</span> до <span class="font-medium">{{ pagination.to }}</span> од
                  <span class="font-medium">{{ pagination.total }}</span> документи
                </p>
              </div>
              <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Пагинација">
                  <button
                    @click="goToPage(pagination.currentPage - 1)"
                    :disabled="pagination.currentPage === 1"
                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                      <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                  </button>
                  <button
                    @click="goToPage(pagination.currentPage + 1)"
                    :disabled="pagination.currentPage === pagination.lastPage"
                    class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                      <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                  </button>
                </nav>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useNotificationStore } from '@/scripts/stores/notification'

const notificationStore = useNotificationStore()

// State
const documents = ref([])
const isLoading = ref(true)
const error = ref(null)
const filterCategory = ref('')
const filterStatus = ref('')

// Upload modal state
const showUploadModal = ref(false)
const selectedFile = ref(null)
const uploadCategory = ref('')
const uploadNotes = ref('')
const isUploading = ref(false)
const uploadError = ref(null)
const isDragging = ref(false)
const fileInput = ref(null)

// Delete modal state
const showDeleteModal = ref(false)
const documentToDelete = ref(null)
const isDeleting = ref(false)

// Preview modal state
const showPreviewModal = ref(false)
const previewDoc = ref(null)
const previewUrl = ref('')

// Pagination
const pagination = ref({
  currentPage: 1,
  lastPage: 1,
  perPage: 15,
  total: 0,
  from: 0,
  to: 0,
})

// Methods
const loadDocuments = async () => {
  isLoading.value = true
  error.value = null

  try {
    const params = {
      page: pagination.value.currentPage,
      per_page: pagination.value.perPage,
    }

    if (filterCategory.value) {
      params.category = filterCategory.value
    }
    if (filterStatus.value) {
      params.status = filterStatus.value
    }

    const { data } = await window.axios.get('/client-documents', { params })

    documents.value = data.data || []
    pagination.value = {
      currentPage: data.current_page || 1,
      lastPage: data.last_page || 1,
      perPage: data.per_page || 15,
      total: data.total || 0,
      from: data.from || 0,
      to: data.to || 0,
    }
  } catch (err) {
    error.value = err?.response?.data?.message || 'Failed to load documents.'
  } finally {
    isLoading.value = false
  }
}

const triggerFileInput = () => {
  fileInput.value?.click()
}

const handleFileSelect = (event) => {
  const file = event.target.files?.[0]
  if (file) {
    validateAndSetFile(file)
  }
}

const handleDrop = (event) => {
  isDragging.value = false
  const file = event.dataTransfer?.files?.[0]
  if (file) {
    validateAndSetFile(file)
  }
}

const validateAndSetFile = (file) => {
  uploadError.value = null

  // Check file size (10MB)
  if (file.size > 10485760) {
    uploadError.value = 'Датотеката е преголема. Максималната големина е 10MB.'
    return
  }

  // Check mime type
  const allowedTypes = [
    'application/pdf',
    'image/png',
    'image/jpeg',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'text/csv',
    'text/plain',
  ]

  if (!allowedTypes.includes(file.type)) {
    uploadError.value = 'Невалиден формат. Дозволени: PDF, PNG, JPEG, XLSX, CSV.'
    return
  }

  selectedFile.value = file
}

const uploadDocument = async () => {
  if (!selectedFile.value || !uploadCategory.value) return

  isUploading.value = true
  uploadError.value = null

  const formData = new FormData()
  formData.append('file', selectedFile.value)
  formData.append('category', uploadCategory.value)
  if (uploadNotes.value) {
    formData.append('notes', uploadNotes.value)
  }

  try {
    await window.axios.post('/client-documents/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    notificationStore.showNotification({
      type: 'success',
      message: 'Документот е успешно прикачен.',
    })

    closeUploadModal()
    loadDocuments()
  } catch (err) {
    const errors = err?.response?.data?.errors
    if (errors) {
      const firstError = Object.values(errors)[0]
      uploadError.value = Array.isArray(firstError) ? firstError[0] : firstError
    } else {
      uploadError.value = err?.response?.data?.message || 'Failed to upload document.'
    }
  } finally {
    isUploading.value = false
  }
}

const closeUploadModal = () => {
  showUploadModal.value = false
  selectedFile.value = null
  uploadCategory.value = ''
  uploadNotes.value = ''
  uploadError.value = null
  isDragging.value = false
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

const openDeleteModal = (doc) => {
  documentToDelete.value = doc
  showDeleteModal.value = true
}

const confirmDelete = async () => {
  if (!documentToDelete.value?.id) return

  isDeleting.value = true

  try {
    await window.axios.delete(`/client-documents/${documentToDelete.value.id}`)

    notificationStore.showNotification({
      type: 'success',
      message: 'Документот е успешно избришан.',
    })

    showDeleteModal.value = false
    documentToDelete.value = null
    loadDocuments()
  } catch (err) {
    notificationStore.showNotification({
      type: 'error',
      message: err?.response?.data?.message || 'Failed to delete document.',
    })
  } finally {
    isDeleting.value = false
  }
}

const downloadDocument = (doc) => {
  const url = `/api/v1/client-documents/${doc.id}/download`
  const link = document.createElement('a')
  link.href = url
  link.download = doc.original_filename
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

const isPreviewable = (doc) => {
  return doc.mime_type?.startsWith('image/') || doc.mime_type === 'application/pdf'
}

const previewDocument = (doc) => {
  previewDoc.value = doc
  previewUrl.value = `/api/v1/client-documents/${doc.id}/download`
  showPreviewModal.value = true
}

const goToPage = (page) => {
  if (page < 1 || page > pagination.value.lastPage) return
  pagination.value.currentPage = page
  loadDocuments()
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
    pending_review: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800',
    reviewed: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800',
    rejected: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800',
  }
  return classes[status] || 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800'
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

// Lifecycle
onMounted(() => {
  loadDocuments()
})
</script>

