<template>
  <div class="space-y-6">
    <!-- Step Header -->
    <div class="text-center">
      <BaseHeading tag="h2" class="text-xl font-bold text-gray-900 mb-1">
        {{ $t('imports.upload_file') }}
      </BaseHeading>
      <p class="text-sm text-gray-600 max-w-xl mx-auto">
        {{ $t('imports.upload_file_description_detailed') }}
      </p>
    </div>

    <!-- File Upload Area -->
    <div class="max-w-4xl mx-auto">
      <div v-if="!importStore.uploadedFile" class="space-y-6">
        <!-- Source System Selector -->
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
          <label class="block text-sm font-medium text-gray-900 mb-2">
            {{ $t('imports.source_system') }}
          </label>
          <BaseSelectInput
            :value="importStore.sourceSystem"
            @input="handleSourceSystemChange"
            :options="sourceSystemOptions"
            size="sm"
            class="max-w-xs"
          />
          <p class="text-xs text-gray-500 mt-1.5">
            {{ $t('imports.source_system_hint') }}
          </p>
        </div>

        <!-- Drag & Drop Upload Area -->
        <div
          class="upload-area"
          :class="[
            'border-2 border-dashed rounded-lg p-8 text-center transition-colors',
            {
              'border-primary-300 bg-primary-50': isDragOver,
              'border-gray-300 hover:border-gray-400': !isDragOver && !importStore.isUploading,
              'border-gray-200 bg-gray-50': importStore.isUploading,
            }
          ]"
          @dragover.prevent="handleDragOver"
          @dragleave.prevent="handleDragLeave"
          @drop.prevent="handleDrop"
        >
          <div class="space-y-4">
            <!-- Upload Icon -->
            <div class="mx-auto w-16 h-16 flex items-center justify-center">
              <BaseIcon
                v-if="!importStore.isUploading"
                name="CloudUploadIcon"
                class="w-12 h-12 text-gray-400"
              />
              <BaseSpinner v-else class="w-12 h-12 text-primary-600" />
            </div>

            <!-- Upload Text -->
            <div>
              <p class="text-lg font-medium text-gray-900 mb-1">
                {{ importStore.isUploading ? $t('imports.uploading') : $t('imports.drag_drop_files') }}
              </p>
              <p class="text-sm text-gray-500">
                {{ $t('imports.or_click_to_browse') }}
              </p>
            </div>

            <!-- Upload Progress -->
            <div v-if="importStore.isUploading" class="max-w-xs mx-auto">
              <div class="flex justify-between text-sm font-medium text-gray-900 mb-2">
                <span>{{ $t('imports.uploading') }}</span>
                <span>{{ importStore.uploadProgress }}%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div
                  class="bg-primary-600 h-2 rounded-full transition-all duration-300"
                  :style="{ width: `${importStore.uploadProgress}%` }"
                ></div>
              </div>
            </div>

            <!-- File Input -->
            <input
              ref="fileInput"
              type="file"
              :accept="acceptedFileTypes"
              @change="handleFileSelect"
              :disabled="importStore.isUploading"
              class="hidden"
            />

            <!-- Browse Button -->
            <BaseButton
              v-if="!importStore.isUploading"
              variant="primary"
              @click="$refs.fileInput.click()"
            >
              {{ $t('imports.browse_files') }}
            </BaseButton>
          </div>
        </div>

        <!-- Compact format + template info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Supported Formats -->
          <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-900 mb-2">
              {{ $t('imports.supported_formats') }}
            </h4>
            <div class="flex flex-wrap gap-2 text-xs">
              <span class="px-2 py-1 bg-white border border-gray-200 rounded">CSV</span>
              <span class="px-2 py-1 bg-white border border-gray-200 rounded">XLS / XLSX</span>
              <span class="px-2 py-1 bg-white border border-gray-200 rounded">XML</span>
            </div>
            <p v-if="isPartnerOrAccountant" class="text-xs text-gray-500 mt-2">Onivo, Megasoft, Pantheon, {{ $t('imports.generic_formats') }}</p>
          </div>

          <!-- CSV Templates -->
          <div class="template-download-section bg-gray-50 border border-gray-200 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-900 mb-2">
              {{ $t('imports.download_csv_templates') }}
            </h4>
            <div class="grid grid-cols-2 gap-2">
              <button
                @click="downloadTemplate('customers')"
                class="flex items-center px-2 py-1.5 bg-white border border-gray-200 rounded hover:bg-gray-100 transition-colors text-xs text-gray-700"
              >
                <BaseIcon name="ArrowDownTrayIcon" class="w-3 h-3 mr-1.5 text-gray-400" />
                {{ $t('imports.customer_template') }}
              </button>
              <button
                @click="downloadTemplate('items')"
                class="flex items-center px-2 py-1.5 bg-white border border-gray-200 rounded hover:bg-gray-100 transition-colors text-xs text-gray-700"
              >
                <BaseIcon name="ArrowDownTrayIcon" class="w-3 h-3 mr-1.5 text-gray-400" />
                {{ $t('imports.items_template') }}
              </button>
              <button
                @click="downloadTemplate('invoices')"
                class="flex items-center px-2 py-1.5 bg-white border border-gray-200 rounded hover:bg-gray-100 transition-colors text-xs text-gray-700"
              >
                <BaseIcon name="ArrowDownTrayIcon" class="w-3 h-3 mr-1.5 text-gray-400" />
                {{ $t('imports.invoice_template') }}
              </button>
              <button
                @click="downloadTemplate('invoice_with_items')"
                class="flex items-center px-2 py-1.5 bg-white border border-gray-200 rounded hover:bg-gray-100 transition-colors text-xs text-gray-700"
              >
                <BaseIcon name="ArrowDownTrayIcon" class="w-3 h-3 mr-1.5 text-gray-400" />
                {{ $t('imports.invoice_with_items_template') }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Uploaded File Info -->
      <div v-else class="space-y-4">
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
          <div class="flex items-center">
            <BaseIcon name="CheckCircleIcon" class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" />
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between">
                <div class="flex items-center min-w-0">
                  <span class="text-sm font-medium text-green-900 truncate">{{ importStore.fileInfo.name }}</span>
                  <span class="ml-2 text-xs text-green-700 uppercase bg-green-100 px-1.5 py-0.5 rounded">{{ importStore.fileType }}</span>
                  <span class="ml-2 text-xs text-green-700">{{ formatFileSize(importStore.fileInfo.size) }}</span>
                </div>
                <BaseButton
                  variant="secondary"
                  size="sm"
                  @click="removeFile"
                  class="ml-3 flex-shrink-0"
                >
                  {{ $t('imports.upload_different_file') }}
                </BaseButton>
              </div>
            </div>
          </div>

          <!-- Detected Type + Override -->
          <div v-if="importStore.detectedImportType" class="mt-3 pt-3 border-t border-green-200">
            <div class="flex items-center justify-between">
              <div class="flex items-center text-sm">
                <span class="text-gray-600 mr-2">{{ $t('imports.detected_type') }}:</span>
                <BaseBadge :variant="getDetectionBadgeVariant()">
                  {{ formatDetectedType(importStore.detectedImportType) }}
                </BaseBadge>
              </div>
              <div class="w-48">
                <BaseSelectInput
                  v-model="selectedTypeOverride"
                  :options="typeOptions"
                  :placeholder="$t('imports.override_type')"
                  @update:modelValue="handleTypeOverrideChange"
                  size="sm"
                />
              </div>
            </div>
          </div>
        </div>

        <div class="text-center text-sm text-green-700">
          {{ $t('imports.ready_for_mapping') }}
        </div>
      </div>
    </div>

    <!-- Upload Error -->
    <BaseErrorAlert v-if="uploadError" class="max-w-4xl mx-auto">
      {{ uploadError }}
    </BaseErrorAlert>

    <!-- Tips Section -->
    <div class="tips-section max-w-4xl mx-auto">
      <div class="flex items-start text-xs text-gray-500 space-x-4">
        <BaseIcon name="LightBulbIcon" class="w-4 h-4 text-yellow-400 flex-shrink-0 mt-0.5" />
        <div class="flex flex-wrap gap-x-4 gap-y-1">
          <span>{{ $t('imports.tip_column_headers') }}</span>
          <span>{{ $t('imports.tip_macedonian_headers') }}</span>
          <span>{{ $t('imports.tip_file_size') }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

// Components
import BaseHeading from '@/scripts/components/base/BaseHeading.vue'
import BaseButton from '@/scripts/components/base/BaseButton.vue'
import BaseIcon from '@/scripts/components/base/BaseIcon.vue'
import BaseSpinner from '@/scripts/components/base/BaseSpinner.vue'
import BaseErrorAlert from '@/scripts/components/base/BaseErrorAlert.vue'
import BaseBadge from '@/scripts/components/base/BaseBadge.vue'
import BaseSelectInput from '@/scripts/components/base/BaseSelectInput.vue'

// Store
import { useImportStore } from '@/scripts/admin/stores/import'
import { useUserStore } from '@/scripts/admin/stores/user'

const { t } = useI18n()
const importStore = useImportStore()
const userStore = useUserStore()
// Local state
const isDragOver = ref(false)
const uploadError = ref(null)
const selectedTypeOverride = ref(null)

// Computed
const isPartnerOrAccountant = computed(() => {
  const user = userStore.currentUser
  if (!user) return false
  return user.role === 'partner' || user.role === 'super admin' ||
         user.account_type === 'accountant' || user.is_partner
})

const acceptedFileTypes = computed(() => {
  return importStore.supportedFormats.map(format => `.${format}`).join(',')
})

const sourceSystemOptions = computed(() => {
  return [
    { label: t('imports.source_auto_detect'), value: 'auto' },
    { label: 'Onivo', value: 'onivo' },
    { label: 'Megasoft', value: 'megasoft' },
    { label: 'Effect Plus', value: 'effect_plus' },
    { label: 'Eurofaktura', value: 'eurofaktura' },
    { label: 'Manager.io', value: 'manager_io' },
    { label: t('imports.source_generic_csv'), value: 'generic' },
  ]
})

const typeOptions = computed(() => {
  return [
    { id: 'auto', value: null, label: t('imports.use_detected_type') },
    { id: 'customers', value: 'customers', label: t('imports.type_customers') },
    { id: 'invoices', value: 'invoices', label: t('imports.type_invoices') },
    { id: 'items', value: 'items', label: t('imports.type_items') },
    { id: 'payments', value: 'payments', label: t('imports.type_payments') },
    { id: 'expenses', value: 'expenses', label: t('imports.type_expenses') },
  ]
})

// Methods
const handleSourceSystemChange = (value) => {
  importStore.sourceSystem = value || 'auto'
}

const handleTypeOverrideChange = (option) => {
  const value = option?.value ?? null
  importStore.manualTypeOverride = value
}
const handleDragOver = (event) => {
  event.preventDefault()
  isDragOver.value = true
}

const handleDragLeave = (event) => {
  event.preventDefault()
  isDragOver.value = false
}

const handleDrop = (event) => {
  event.preventDefault()
  isDragOver.value = false
  
  const files = event.dataTransfer.files
  if (files.length > 0) {
    handleFile(files[0])
  }
}

const handleFileSelect = (event) => {
  const file = event.target.files[0]
  if (file) {
    handleFile(file)
  }
  // Reset the input value so the same file can be selected again
  event.target.value = ''
}

const handleFile = async (file) => {
  uploadError.value = null
  
  // Validate file type
  const fileExtension = file.name.split('.').pop().toLowerCase()
  if (!importStore.supportedFormats.includes(fileExtension)) {
    uploadError.value = t('imports.unsupported_file_format', { 
      format: fileExtension,
      supported: importStore.supportedFormats.join(', ')
    })
    return
  }
  
  // Validate file size (50MB limit)
  const maxSize = 50 * 1024 * 1024 // 50MB in bytes
  if (file.size > maxSize) {
    uploadError.value = t('imports.file_too_large', { 
      size: formatFileSize(file.size),
      maxSize: formatFileSize(maxSize)
    })
    return
  }
  
  try {
    await importStore.uploadFile(file)
  } catch (error) {
    uploadError.value = error.response?.data?.message || t('imports.upload_failed')
  }
}

const removeFile = () => {
  importStore.removeFile()
  uploadError.value = null
  selectedTypeOverride.value = null
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const downloadTemplate = async (type) => {
  try {
    const response = await window.axios.get(`/migration/templates/${type}`, {
      responseType: 'blob',
    })

    // Create a blob URL and trigger download
    const blob = new Blob([response.data], { type: 'text/csv; charset=UTF-8' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url

    // Extract filename from Content-Disposition header or use default
    const contentDisposition = response.headers['content-disposition']
    let filename = `${type}_import_template.csv`
    if (contentDisposition) {
      const filenameMatch = contentDisposition.match(/filename="?(.+)"?/)
      if (filenameMatch) {
        filename = filenameMatch[1]
      }
    }

    link.download = filename
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Template download failed:', error)
    uploadError.value = t('imports.template_download_failed')
  }
}

const formatDetectedType = (type) => {
  if (!type) return '-'

  const typeLabels = {
    customers: t('imports.type_customers'),
    invoices: t('imports.type_invoices'),
    items: t('imports.type_items'),
    payments: t('imports.type_payments'),
  }

  return typeLabels[type] || type.charAt(0).toUpperCase() + type.slice(1)
}

const getDetectionBadgeVariant = () => {
  const confidence = importStore.detectionConfidence

  if (confidence >= 0.7) {
    return 'success'
  } else if (confidence >= 0.5) {
    return 'warning'
  } else {
    return 'info'
  }
}

// Lifecycle
onMounted(() => {
  selectedTypeOverride.value = importStore.manualTypeOverride
})
</script>