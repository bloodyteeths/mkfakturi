<template>
  <div class="space-y-6">
    <!-- Step Header -->
    <div class="text-center">
      <BaseHeading tag="h2" class="text-2xl font-bold text-gray-900 mb-2">
        {{ $t('imports.upload_file') }}
      </BaseHeading>
      <p class="text-gray-600 max-w-2xl mx-auto">
        {{ $t('imports.upload_file_description_detailed') }}
      </p>
    </div>

    <!-- File Upload Area -->
    <div class="max-w-4xl mx-auto">
      <div v-if="!importStore.uploadedFile" class="space-y-6">
        <!-- Drag & Drop Upload Area -->
        <div
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

        <!-- Supported Formats Info -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <div class="flex items-start">
            <BaseIcon name="InformationCircleIcon" class="w-5 h-5 text-blue-400 mt-0.5 mr-3 flex-shrink-0" />
            <div>
              <h4 class="text-sm font-medium text-blue-900 mb-1">
                {{ $t('imports.supported_formats') }}
              </h4>
              <div class="text-sm text-blue-700 space-y-2">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <p class="font-medium mb-1">{{ $t('imports.file_formats') }}:</p>
                    <ul class="space-y-1">
                      <li class="flex items-center">
                        <BaseIcon name="DocumentIcon" class="w-4 h-4 mr-2" />
                        CSV (.csv)
                      </li>
                      <li class="flex items-center">
                        <BaseIcon name="DocumentIcon" class="w-4 h-4 mr-2" />
                        Excel (.xls, .xlsx)
                      </li>
                      <li class="flex items-center">
                        <BaseIcon name="DocumentIcon" class="w-4 h-4 mr-2" />
                        XML (.xml)
                      </li>
                    </ul>
                  </div>
                  <div>
                    <p class="font-medium mb-1">{{ $t('imports.supported_sources') }}:</p>
                    <ul class="space-y-1">
                      <li class="flex items-center">
                        <BaseIcon name="BuildingOfficeIcon" class="w-4 h-4 mr-2" />
                        Onivo
                      </li>
                      <li class="flex items-center">
                        <BaseIcon name="BuildingOfficeIcon" class="w-4 h-4 mr-2" />
                        Megasoft
                      </li>
                      <li class="flex items-center">
                        <BaseIcon name="BuildingOfficeIcon" class="w-4 h-4 mr-2" />
                        Pantheon
                      </li>
                      <li class="flex items-center">
                        <BaseIcon name="DocumentIcon" class="w-4 h-4 mr-2" />
                        {{ $t('imports.generic_formats') }}
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Uploaded File Info -->
      <div v-else class="space-y-6">
        <div class="bg-green-50 border border-green-200 rounded-lg p-6">
          <div class="flex items-start">
            <BaseIcon name="CheckCircleIcon" class="w-6 h-6 text-green-400 mt-1 mr-4 flex-shrink-0" />
            <div class="flex-1">
              <h3 class="text-lg font-medium text-green-900 mb-2">
                {{ $t('imports.file_uploaded_successfully') }}
              </h3>
              
              <!-- File Details -->
              <div class="bg-white rounded-lg border border-green-200 p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="space-y-2">
                    <div class="flex justify-between">
                      <span class="text-sm font-medium text-gray-500">{{ $t('imports.filename') }}:</span>
                      <span class="text-sm text-gray-900">{{ importStore.fileInfo.name }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-sm font-medium text-gray-500">{{ $t('imports.format') }}:</span>
                      <span class="text-sm text-gray-900 uppercase">
                        {{ importStore.fileType }}
                      </span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-sm font-medium text-gray-500">{{ $t('imports.file_size') }}:</span>
                      <span class="text-sm text-gray-900">{{ formatFileSize(importStore.fileInfo.size) }}</span>
                    </div>
                  </div>
                  <div class="space-y-2">
                    <div class="flex justify-between">
                      <span class="text-sm font-medium text-gray-500">{{ $t('imports.import_id') }}:</span>
                      <span class="text-sm text-gray-900 font-mono">{{ importStore.importId }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-sm font-medium text-gray-500">{{ $t('imports.status') }}:</span>
                      <span class="text-sm">
                        <BaseBadge variant="success">{{ $t('imports.uploaded') }}</BaseBadge>
                      </span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-sm font-medium text-gray-500">{{ $t('imports.uploaded_at') }}:</span>
                      <span class="text-sm text-gray-900">{{ formatDate(importStore.fileInfo.lastModified) }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex justify-between items-center mt-4">
                <BaseButton
                  variant="secondary"
                  size="sm"
                  @click="removeFile"
                >
                  {{ $t('imports.upload_different_file') }}
                </BaseButton>

                <div class="text-sm text-green-700">
                  {{ $t('imports.ready_for_mapping') }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Import Job Information -->
        <div v-if="importStore.importJob" class="bg-gray-50 border border-gray-200 rounded-lg p-4">
          <h4 class="text-sm font-medium text-gray-900 mb-2">
            {{ $t('imports.import_job_details') }}
          </h4>
          <div class="grid grid-cols-2 gap-4 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-500">{{ $t('imports.job_type') }}:</span>
              <span class="text-gray-900">{{ importStore.importJob.type }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">{{ $t('imports.company') }}:</span>
              <span class="text-gray-900">{{ importStore.importJob.company?.name || '-' }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Upload Error -->
    <BaseErrorAlert v-if="uploadError" class="max-w-4xl mx-auto">
      {{ uploadError }}
    </BaseErrorAlert>

    <!-- Tips Section -->
    <div class="max-w-4xl mx-auto">
      <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
        <h4 class="text-sm font-medium text-gray-900 mb-3">
          {{ $t('imports.tips_for_best_results') }}
        </h4>
        <ul class="space-y-2 text-sm text-gray-600">
          <li class="flex items-start">
            <BaseIcon name="LightBulbIcon" class="w-4 h-4 mt-0.5 mr-2 text-yellow-500 flex-shrink-0" />
            {{ $t('imports.tip_column_headers') }}
          </li>
          <li class="flex items-start">
            <BaseIcon name="LightBulbIcon" class="w-4 h-4 mt-0.5 mr-2 text-yellow-500 flex-shrink-0" />
            {{ $t('imports.tip_macedonian_headers') }}
          </li>
          <li class="flex items-start">
            <BaseIcon name="LightBulbIcon" class="w-4 h-4 mt-0.5 mr-2 text-yellow-500 flex-shrink-0" />
            {{ $t('imports.tip_file_size') }}
          </li>
          <li class="flex items-start">
            <BaseIcon name="LightBulbIcon" class="w-4 h-4 mt-0.5 mr-2 text-yellow-500 flex-shrink-0" />
            {{ $t('imports.tip_backup_data') }}
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'

// Components
import BaseHeading from '@/scripts/components/base/BaseHeading.vue'
import BaseButton from '@/scripts/components/base/BaseButton.vue'
import BaseIcon from '@/scripts/components/base/BaseIcon.vue'
import BaseSpinner from '@/scripts/components/base/BaseSpinner.vue'
import BaseErrorAlert from '@/scripts/components/base/BaseErrorAlert.vue'
import BaseBadge from '@/scripts/components/base/BaseBadge.vue'

// Store
import { useImportStore } from '@/scripts/admin/stores/import'

const { t } = useI18n()
const importStore = useImportStore()

// Local state
const isDragOver = ref(false)
const uploadError = ref(null)

// Computed
const acceptedFileTypes = computed(() => {
  return importStore.supportedFormats.map(format => `.${format}`).join(',')
})

// Methods
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
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const formatDate = (timestamp) => {
  return new Date(timestamp).toLocaleString()
}
</script>