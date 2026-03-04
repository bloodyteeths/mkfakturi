<template>
  <div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-6">
        <router-link
          :to="{ name: 'partner.portfolio' }"
          class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1 mb-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          Back to Portfolio
        </router-link>
        <h1 class="text-2xl font-bold text-gray-900">Import Companies</h1>
        <p class="mt-1 text-sm text-gray-600">
          Upload a CSV or Excel file to bulk-import companies into your portfolio.
        </p>
      </div>

      <!-- Step Indicator -->
      <nav class="mb-8">
        <ol class="flex items-center">
          <li v-for="(step, idx) in steps" :key="idx" class="flex items-center" :class="idx < steps.length - 1 ? 'flex-1' : ''">
            <div class="flex items-center">
              <span
                class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium border-2"
                :class="{
                  'bg-blue-600 border-blue-600 text-white': step.status === 'current',
                  'bg-green-500 border-green-500 text-white': step.status === 'complete',
                  'bg-white border-gray-300 text-gray-500': step.status === 'upcoming',
                }"
              >
                <svg v-if="step.status === 'complete'" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                <span v-else>{{ idx + 1 }}</span>
              </span>
              <span class="ml-2 text-sm font-medium" :class="step.status === 'current' ? 'text-blue-600' : step.status === 'complete' ? 'text-green-600' : 'text-gray-500'">
                {{ step.label }}
              </span>
            </div>
            <div v-if="idx < steps.length - 1" class="flex-1 mx-4 h-0.5" :class="step.status === 'complete' ? 'bg-green-500' : 'bg-gray-200'"></div>
          </li>
        </ol>
      </nav>

      <!-- Step 1: Upload -->
      <div v-if="currentStep === 1" class="bg-white shadow rounded-lg p-6">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-lg font-medium text-gray-900">Upload File</h2>
          <a
            href="/api/v1/partner/portfolio-companies/template"
            class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            Download Template
          </a>
        </div>

        <!-- Drop zone -->
        <div
          class="border-2 border-dashed rounded-lg p-8 text-center transition-colors cursor-pointer"
          :class="isDragging ? 'border-blue-500 bg-blue-50' : uploadedFile ? 'border-green-300 bg-green-50' : 'border-gray-300 hover:border-gray-400'"
          @dragover.prevent="isDragging = true"
          @dragleave.prevent="isDragging = false"
          @drop.prevent="handleFileDrop"
          @click="$refs.fileInput.click()"
        >
          <input
            ref="fileInput"
            type="file"
            accept=".csv,.xlsx,.xls,.txt"
            class="hidden"
            @change="handleFileSelect"
          />

          <template v-if="!uploadedFile">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
            <p class="mt-2 text-sm text-gray-600">
              <span class="font-medium text-blue-600">Click to browse</span> or drag and drop
            </p>
            <p class="mt-1 text-xs text-gray-500">CSV, XLSX, or XLS (max 5MB)</p>
          </template>

          <template v-else>
            <div class="flex items-center justify-center gap-3">
              <svg class="h-8 w-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <div class="text-left">
                <p class="text-sm font-medium text-gray-900">{{ uploadedFile.name }}</p>
                <p class="text-xs text-gray-500">{{ formatFileSize(uploadedFile.size) }}</p>
              </div>
              <button
                class="ml-4 text-sm text-red-600 hover:text-red-800"
                @click.stop="uploadedFile = null"
              >
                Remove
              </button>
            </div>
          </template>
        </div>

        <div v-if="uploadError" class="mt-3 text-sm text-red-600">
          {{ uploadError }}
        </div>

        <div class="mt-6 flex justify-end">
          <button
            :disabled="!uploadedFile || isUploading"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
            @click="uploadAndPreview"
          >
            <svg v-if="isUploading" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            {{ isUploading ? 'Uploading...' : 'Upload & Preview' }}
          </button>
        </div>
      </div>

      <!-- Step 2: Preview -->
      <div v-if="currentStep === 2" class="space-y-4">
        <!-- Summary cards -->
        <div class="grid grid-cols-3 gap-4">
          <div class="bg-white shadow rounded-lg p-4 text-center">
            <p class="text-2xl font-bold text-green-600">{{ previewData.valid }}</p>
            <p class="text-sm text-gray-500">Valid</p>
          </div>
          <div class="bg-white shadow rounded-lg p-4 text-center">
            <p class="text-2xl font-bold text-yellow-600">{{ previewData.duplicates }}</p>
            <p class="text-sm text-gray-500">Duplicates (skipped)</p>
          </div>
          <div class="bg-white shadow rounded-lg p-4 text-center">
            <p class="text-2xl font-bold text-red-600">{{ previewData.invalid }}</p>
            <p class="text-sm text-gray-500">Invalid</p>
          </div>
        </div>

        <!-- Valid companies preview -->
        <div v-if="previewData.preview.valid.length > 0" class="bg-white shadow rounded-lg overflow-hidden">
          <div class="px-4 py-3 border-b bg-gray-50">
            <h3 class="text-sm font-medium text-gray-700">
              Companies to import
              <span v-if="previewData.valid > 20" class="text-gray-400">(showing first 20 of {{ previewData.valid }})</span>
            </h3>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tax ID</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">City</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="(row, idx) in previewData.preview.valid" :key="'v-' + idx" class="hover:bg-gray-50">
                  <td class="px-4 py-2 text-sm text-gray-900">{{ row.company_name }}</td>
                  <td class="px-4 py-2 text-sm text-gray-500">{{ row.tax_id }}</td>
                  <td class="px-4 py-2 text-sm text-gray-500">{{ row.city || '-' }}</td>
                  <td class="px-4 py-2 text-sm text-gray-500">{{ row.email || '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Duplicates -->
        <div v-if="previewData.preview.duplicates.length > 0" class="bg-white shadow rounded-lg overflow-hidden">
          <div class="px-4 py-3 border-b bg-yellow-50">
            <h3 class="text-sm font-medium text-yellow-700">
              Duplicates (will be skipped)
            </h3>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Row</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tax ID</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="(row, idx) in previewData.preview.duplicates" :key="'d-' + idx" class="bg-yellow-50/50">
                  <td class="px-4 py-2 text-sm text-gray-500">{{ row._row }}</td>
                  <td class="px-4 py-2 text-sm text-gray-900">{{ row.company_name }}</td>
                  <td class="px-4 py-2 text-sm text-gray-500">{{ row.tax_id }}</td>
                  <td class="px-4 py-2 text-sm text-yellow-600">{{ row._error }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Invalid rows -->
        <div v-if="previewData.preview.invalid.length > 0" class="bg-white shadow rounded-lg overflow-hidden">
          <div class="px-4 py-3 border-b bg-red-50">
            <h3 class="text-sm font-medium text-red-700">
              Invalid rows (will be skipped)
            </h3>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Row</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tax ID</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Error</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="(row, idx) in previewData.preview.invalid" :key="'i-' + idx" class="bg-red-50/50">
                  <td class="px-4 py-2 text-sm text-gray-500">{{ row._row }}</td>
                  <td class="px-4 py-2 text-sm text-gray-900">{{ row.company_name || '-' }}</td>
                  <td class="px-4 py-2 text-sm text-gray-500">{{ row.tax_id || '-' }}</td>
                  <td class="px-4 py-2 text-sm text-red-600">{{ row._error }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-between">
          <button
            class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 text-sm font-medium"
            @click="goBack"
          >
            Back
          </button>
          <button
            :disabled="previewData.valid === 0 || isImporting"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
            @click="confirmImport"
          >
            <svg v-if="isImporting" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            {{ isImporting ? 'Importing...' : `Import ${previewData.valid} Companies` }}
          </button>
        </div>
      </div>

      <!-- Step 3: Done -->
      <div v-if="currentStep === 3" class="bg-white shadow rounded-lg p-8 text-center">
        <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
          <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <h2 class="text-xl font-bold text-gray-900 mb-2">Import Complete</h2>
        <p class="text-gray-600 mb-6">
          {{ importResult.imported }} {{ importResult.imported === 1 ? 'company' : 'companies' }} imported successfully.
        </p>

        <!-- Result summary -->
        <div class="grid grid-cols-2 gap-4 max-w-sm mx-auto mb-6">
          <div class="bg-green-50 rounded-lg p-3">
            <p class="text-2xl font-bold text-green-600">{{ importResult.imported }}</p>
            <p class="text-xs text-gray-500">Imported</p>
          </div>
          <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-2xl font-bold" :class="importResult.errors.length > 0 ? 'text-red-600' : 'text-gray-400'">{{ importResult.errors.length }}</p>
            <p class="text-xs text-gray-500">Failed</p>
          </div>
        </div>

        <!-- Errors list -->
        <div v-if="importResult.errors.length > 0" class="bg-red-50 rounded-lg p-4 mb-6 text-left max-w-lg mx-auto">
          <h3 class="text-sm font-medium text-red-800 mb-2">Failed rows:</h3>
          <ul class="text-sm text-red-700 space-y-1">
            <li v-for="(err, idx) in importResult.errors" :key="idx">
              Row {{ err.row }}: {{ err.name }} — {{ err.error }}
            </li>
          </ul>
        </div>

        <div class="flex justify-center gap-3">
          <button
            class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 text-sm font-medium"
            @click="startNewImport"
          >
            Import More
          </button>
          <router-link
            :to="{ name: 'partner.portfolio' }"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium"
          >
            Go to Portfolio
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'

const currentStep = ref(1)
const uploadedFile = ref(null)
const uploadError = ref('')
const isDragging = ref(false)
const isUploading = ref(false)
const isImporting = ref(false)
const importId = ref(null)

const previewData = ref({
  total: 0,
  valid: 0,
  invalid: 0,
  duplicates: 0,
  preview: { valid: [], invalid: [], duplicates: [] },
})

const importResult = ref({
  imported: 0,
  errors: [],
})

const steps = computed(() => [
  { label: 'Upload', status: currentStep.value === 1 ? 'current' : 'complete' },
  { label: 'Preview', status: currentStep.value === 2 ? 'current' : currentStep.value > 2 ? 'complete' : 'upcoming' },
  { label: 'Done', status: currentStep.value === 3 ? 'current' : 'upcoming' },
])

const formatFileSize = (bytes) => {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

const validateAndSetFile = (file) => {
  uploadError.value = ''

  const allowedTypes = [
    'text/csv',
    'text/plain',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  ]
  const allowedExts = ['csv', 'xlsx', 'xls', 'txt']
  const ext = file.name.split('.').pop().toLowerCase()

  if (!allowedTypes.includes(file.type) && !allowedExts.includes(ext)) {
    uploadError.value = 'Invalid file type. Please upload CSV, XLSX, or XLS.'
    return
  }

  if (file.size > 5 * 1024 * 1024) {
    uploadError.value = 'File too large. Maximum size is 5MB.'
    return
  }

  uploadedFile.value = file
}

const handleFileSelect = (event) => {
  const file = event.target.files[0]
  if (file) validateAndSetFile(file)
}

const handleFileDrop = (event) => {
  isDragging.value = false
  const file = event.dataTransfer.files[0]
  if (file) validateAndSetFile(file)
}

const uploadAndPreview = async () => {
  if (!uploadedFile.value) return

  isUploading.value = true
  uploadError.value = ''

  try {
    const formData = new FormData()
    formData.append('file', uploadedFile.value)

    const { data } = await axios.post('/partner/portfolio-companies/import-preview', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    importId.value = data.import_id
    previewData.value = data
    currentStep.value = 2
  } catch (e) {
    uploadError.value = e.response?.data?.error || e.response?.data?.message || 'Failed to parse file'
  } finally {
    isUploading.value = false
  }
}

const confirmImport = async () => {
  if (!importId.value) return

  isImporting.value = true

  try {
    const { data } = await axios.post('/partner/portfolio-companies/import-confirm', {
      import_id: importId.value,
    })

    importResult.value = {
      imported: data.imported,
      errors: data.errors || [],
    }
    currentStep.value = 3
  } catch (e) {
    alert(e.response?.data?.error || 'Import failed')
  } finally {
    isImporting.value = false
  }
}

const goBack = () => {
  currentStep.value = 1
  importId.value = null
  previewData.value = { total: 0, valid: 0, invalid: 0, duplicates: 0, preview: { valid: [], invalid: [], duplicates: [] } }
}

const startNewImport = () => {
  currentStep.value = 1
  uploadedFile.value = null
  importId.value = null
  uploadError.value = ''
  previewData.value = { total: 0, valid: 0, invalid: 0, duplicates: 0, preview: { valid: [], invalid: [], duplicates: [] } }
  importResult.value = { imported: 0, errors: [] }
}
</script>
