<template>
  <BasePage>
    <BasePageHeader :title="$t('certificates.title')">
      <template #actions>
        <BaseButton
          v-if="currentCertificate"
          variant="primary-outline"
          @click="showCertificateInfo = true"
        >
          <template #left>
            <InfoIcon class="h-5 w-5" />
          </template>
          {{ $t('certificates.view_certificate') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <!-- Upload Section -->
      <div class="md:col-span-2">
        <BaseCard>
          <template #header>
            <div class="flex items-center">
              <KeyIcon class="h-5 w-5 text-primary-500 mr-2" />
              <span class="text-lg font-medium">{{ $t('certificates.upload_title') }}</span>
            </div>
          </template>

          <div class="space-y-6">
            <!-- Certificate Upload -->
            <div>
              <BaseLabel>{{ $t('certificates.certificate_file') }}</BaseLabel>
              <BaseFileUploader
                ref="certificateUploader"
                accept=".p12,.pfx"
                :preserve-local-files="false"
                recommended-text=".p12 or .pfx file (max 5MB)"
                @change="onCertificateSelected"
                @remove="onCertificateCleared"
              />
            </div>

            <!-- Password Field -->
            <div v-if="selectedFile">
              <BaseLabel for="password" required>
                {{ $t('certificates.certificate_password') }}
              </BaseLabel>
              <BaseInput
                id="password"
                v-model="certificateForm.password"
                type="password"
                :placeholder="$t('certificates.password_placeholder')"
                required
                autocomplete="new-password"
                :invalid="!!passwordError"
              />
              <p v-if="passwordError" class="mt-1 text-sm text-red-600">
                {{ passwordError }}
              </p>
              <p class="mt-1 text-xs text-gray-500">
                {{ $t('certificates.password_help') }}
              </p>
            </div>

            <!-- Description Field -->
            <div v-if="selectedFile">
              <BaseLabel for="description">
                {{ $t('certificates.description') }}
              </BaseLabel>
              <BaseTextarea
                id="description"
                v-model="certificateForm.description"
                :placeholder="$t('certificates.description_placeholder')"
                rows="3"
              />
            </div>

            <!-- Upload Button -->
            <div v-if="selectedFile" class="flex justify-end">
              <BaseButton
                :loading="isUploading"
                :disabled="!certificateForm.password || isUploading"
                @click="uploadCertificate"
              >
                <template #left>
                  <UploadIcon class="h-4 w-4" />
                </template>
                {{ $t('certificates.upload_certificate') }}
              </BaseButton>
            </div>
          </div>
        </BaseCard>
      </div>

      <!-- Current Certificate Section -->
      <div>
        <BaseCard>
          <template #header>
            <div class="flex items-center">
              <ShieldCheckIcon 
                class="h-5 w-5 mr-2"
                :class="currentCertificate?.is_valid ? 'text-green-500' : 'text-gray-400'"
              />
              <span class="text-lg font-medium">{{ $t('certificates.current_certificate') }}</span>
            </div>
          </template>

          <div v-if="currentCertificate" class="space-y-4">
            <!-- Certificate Status -->
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('certificates.status') }}</span>
              <BaseBadge
                :variant="currentCertificate.is_valid ? 'green' : 'red'"
                size="sm"
              >
                {{ currentCertificate.is_valid ? $t('certificates.valid') : $t('certificates.expired') }}
              </BaseBadge>
            </div>

            <!-- Subject -->
            <div>
              <span class="text-sm text-gray-600">{{ $t('certificates.subject') }}</span>
              <p class="text-sm font-medium break-all">
                {{ currentCertificate.subject?.CN || $t('certificates.unknown') }}
              </p>
            </div>

            <!-- Valid Until -->
            <div>
              <span class="text-sm text-gray-600">{{ $t('certificates.valid_until') }}</span>
              <p class="text-sm font-medium">
                {{ formatDate(currentCertificate.valid_to) }}
              </p>
            </div>

            <!-- Fingerprint -->
            <div>
              <span class="text-sm text-gray-600">{{ $t('certificates.fingerprint') }}</span>
              <p class="text-xs font-mono break-all bg-gray-50 p-2 rounded">
                {{ currentCertificate.fingerprint }}
              </p>
            </div>

            <!-- Upload Date -->
            <div>
              <span class="text-sm text-gray-600">{{ $t('certificates.uploaded_at') }}</span>
              <p class="text-sm">
                {{ formatDateTime(currentCertificate.uploaded_at) }}
              </p>
            </div>

            <!-- Delete Button -->
            <div class="pt-4 border-t">
              <BaseButton
                variant="danger"
                size="sm"
                @click="() => { console.log('Delete button clicked'); showDeleteConfirmation = true; }"
              >
                <template #left>
                  <TrashIcon class="h-4 w-4" />
                </template>
                {{ $t('certificates.delete_certificate') }}
              </BaseButton>
            </div>
          </div>

          <div v-else class="text-center py-8">
            <ShieldExclamationIcon class="mx-auto h-16 w-16 text-gray-300" />
            <p class="mt-4 text-gray-500">{{ $t('certificates.no_certificate') }}</p>
            <p class="text-sm text-gray-400 mt-1">
              {{ $t('certificates.upload_help') }}
            </p>
          </div>
        </BaseCard>
      </div>
    </div>

    <!-- Certificate Information Modal -->
    <BaseModal :show="showCertificateInfo" @close="showCertificateInfo = false">
      <template #header>
        <span class="text-lg font-medium">{{ $t('certificates.certificate_details') }}</span>
      </template>
      <div v-if="currentCertificate" class="space-y-4 px-6 py-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">
              {{ $t('certificates.common_name') }}
            </label>
            <p class="mt-1 text-sm text-gray-900">
              {{ currentCertificate.subject?.CN || $t('certificates.unknown') }}
            </p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">
              {{ $t('certificates.organization') }}
            </label>
            <p class="mt-1 text-sm text-gray-900">
              {{ currentCertificate.subject?.O || $t('certificates.unknown') }}
            </p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">
              {{ $t('certificates.country') }}
            </label>
            <p class="mt-1 text-sm text-gray-900">
              {{ currentCertificate.subject?.C || $t('certificates.unknown') }}
            </p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">
              {{ $t('certificates.serial_number') }}
            </label>
            <p class="mt-1 text-sm text-gray-900 font-mono">
              {{ currentCertificate.serial_number }}
            </p>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">
            {{ $t('certificates.issuer') }}
          </label>
          <p class="mt-1 text-sm text-gray-900">
            {{ currentCertificate.issuer?.CN || $t('certificates.unknown') }}
          </p>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">
              {{ $t('certificates.valid_from') }}
            </label>
            <p class="mt-1 text-sm text-gray-900">
              {{ formatDateTime(currentCertificate.valid_from) }}
            </p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">
              {{ $t('certificates.valid_until') }}
            </label>
            <p class="mt-1 text-sm text-gray-900">
              {{ formatDateTime(currentCertificate.valid_to) }}
            </p>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
          <BaseButton variant="primary-outline" @click="showCertificateInfo = false">
            {{ $t('general.close') }}
          </BaseButton>
        </div>
      </template>
    </BaseModal>

    <!-- Delete Confirmation Modal -->
    <BaseModal
      :show="showDeleteConfirmation"
      @close="showDeleteConfirmation = false"
    >
      <template #header>
        <span class="text-lg font-medium">{{ $t('certificates.delete_confirmation_title') }}</span>
      </template>
      <div class="space-y-4 px-6 py-4">
        <p class="text-gray-700">
          {{ $t('certificates.delete_confirmation_message') }}
        </p>
        <div class="bg-yellow-50 border border-yellow-200 rounded-md p-3">
          <div class="flex">
            <ExclamationTriangleIcon class="h-5 w-5 text-yellow-400 mr-2 flex-shrink-0" />
            <p class="text-sm text-yellow-800">
              {{ $t('certificates.delete_warning') }}
            </p>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end space-x-3 px-6 py-4 bg-gray-50 border-t border-gray-200">
          <BaseButton variant="white" @click="showDeleteConfirmation = false">
            {{ $t('general.cancel') }}
          </BaseButton>
          <BaseButton
            variant="danger"
            :loading="isDeleting"
            @click="deleteCertificate"
          >
            {{ $t('certificates.delete_confirm') }}
          </BaseButton>
        </div>
      </template>
    </BaseModal>
  </BasePage>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'
import axios from 'axios'

// Icons
import {
  KeyIcon,
  ArrowUpTrayIcon as UploadIcon,
  CheckCircleIcon,
  ShieldCheckIcon,
  ShieldExclamationIcon,
  TrashIcon,
  InformationCircleIcon as InfoIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'

// Composables
const { t } = useI18n()
const notificationStore = useNotificationStore()

// Reactive state
const selectedFile = ref(null)
const currentCertificate = ref(null)
const isUploading = ref(false)
const isDeleting = ref(false)
const showCertificateInfo = ref(false)
const showDeleteConfirmation = ref(false)
const passwordError = ref('')
const certificateUploader = ref(null)

const certificateForm = reactive({
  password: '',
  description: ''
})

// Computed
const canUpload = computed(() => {
  return selectedFile.value && certificateForm.password.trim()
})

// Methods
const onCertificateSelected = (fieldName, file, fileCount) => {
  if (file) {
    selectedFile.value = file
    passwordError.value = ''

    // Validate file type
    const fileName = file.name.toLowerCase()
    if (!fileName.endsWith('.p12') && !fileName.endsWith('.pfx')) {
      notificationStore.showNotification({
        type: 'error',
        message: t('certificates.invalid_file_type')
      })
      onCertificateCleared()
      return
    }

    // Validate file size (5MB max)
    if (file.size > 5 * 1024 * 1024) {
      notificationStore.showNotification({
        type: 'error',
        message: t('certificates.file_too_large')
      })
      onCertificateCleared()
      return
    }
  }
}

const onCertificateCleared = () => {
  selectedFile.value = null
  certificateForm.password = ''
  certificateForm.description = ''
  passwordError.value = ''
}

const uploadCertificate = async () => {
  if (!selectedFile.value || !certificateForm.password) {
    return
  }

  isUploading.value = true
  passwordError.value = ''

  try {
    const formData = new FormData()
    formData.append('certificate', selectedFile.value)
    formData.append('password', certificateForm.password)
    formData.append('description', certificateForm.description)

    const response = await axios.post('/api/v1/certificates/upload', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    notificationStore.showNotification({
      type: 'success',
      message: t('certificates.upload_success')
    })

    // Clear form and refresh certificate info
    onCertificateCleared()
    await fetchCurrentCertificate()

  } catch (error) {
    console.error('Certificate upload error:', error)
    
    if (error.response?.data?.errors?.password) {
      passwordError.value = error.response.data.errors.password[0]
    } else if (error.response?.data?.message) {
      notificationStore.showNotification({
        type: 'error',
        message: error.response.data.message
      })
    } else {
      notificationStore.showNotification({
        type: 'error',
        message: t('certificates.upload_error')
      })
    }
  } finally {
    isUploading.value = false
  }
}

const deleteCertificate = async () => {
  console.log('deleteCertificate called', {
    certificateId: currentCertificate.value?.id,
    hasCertificate: !!currentCertificate.value
  })

  if (!currentCertificate.value?.id) {
    console.log('No certificate ID found')
    notificationStore.showNotification({
      type: 'error',
      message: t('certificates.no_certificate')
    })
    return
  }

  isDeleting.value = true
  console.log('Making DELETE request to:', `/api/v1/certificates/${currentCertificate.value.id}`)

  try {
    await axios.delete(`/api/v1/certificates/${currentCertificate.value.id}`)
    console.log('Delete request successful')

    notificationStore.showNotification({
      type: 'success',
      message: t('certificates.delete_success')
    })

    showDeleteConfirmation.value = false
    currentCertificate.value = null

  } catch (error) {
    console.error('Certificate delete error:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('certificates.delete_error')
    })
  } finally {
    isDeleting.value = false
  }
}

const fetchCurrentCertificate = async () => {
  try {
    const response = await axios.get('/api/v1/certificates/current')
    currentCertificate.value = response.data.data

    // Show warning if certificate files are missing
    if (response.data.warning && response.data.message) {
      notificationStore.showNotification({
        type: 'warning',
        message: response.data.message
      })
    }
  } catch (error) {
    if (error.response?.status !== 404) {
      console.error('Failed to fetch certificate:', error)
    }
    currentCertificate.value = null
  }
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const formatDate = (dateString) => {
  if (!dateString) return t('certificates.unknown')
  try {
    return new Date(dateString).toLocaleDateString()
  } catch {
    return t('certificates.unknown')
  }
}

const formatDateTime = (dateString) => {
  if (!dateString) return t('certificates.unknown')
  try {
    return new Date(dateString).toLocaleString()
  } catch {
    return t('certificates.unknown')
  }
}

// Lifecycle
onMounted(() => {
  fetchCurrentCertificate()
})
</script>

