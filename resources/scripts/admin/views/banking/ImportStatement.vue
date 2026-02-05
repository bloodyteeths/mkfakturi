<template>
  <BasePage>
    <!-- Page Header -->
    <BasePageHeader :title="$t('banking.import_statement')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('banking.title')" :to="{ name: 'banking.dashboard' }" />
        <BaseBreadcrumbItem :title="$t('banking.import_statement')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <!-- Import Steps -->
    <div class="mt-6">
      <!-- Step Indicator -->
      <nav aria-label="Progress" class="mb-8">
        <ol role="list" class="flex items-center">
          <li
            v-for="(step, stepIdx) in steps"
            :key="step.name"
            :class="[stepIdx !== steps.length - 1 ? 'pr-8 sm:pr-20' : '', 'relative']"
          >
            <template v-if="step.status === 'complete'">
              <div class="absolute inset-0 flex items-center" aria-hidden="true">
                <div class="h-0.5 w-full bg-primary-600" />
              </div>
              <div
                class="relative flex h-8 w-8 items-center justify-center rounded-full bg-primary-600 hover:bg-primary-700"
              >
                <CheckIcon class="h-5 w-5 text-white" aria-hidden="true" />
              </div>
            </template>
            <template v-else-if="step.status === 'current'">
              <div class="absolute inset-0 flex items-center" aria-hidden="true">
                <div class="h-0.5 w-full bg-gray-200" />
              </div>
              <div
                class="relative flex h-8 w-8 items-center justify-center rounded-full border-2 border-primary-600 bg-white"
              >
                <span class="h-2.5 w-2.5 rounded-full bg-primary-600" aria-hidden="true" />
              </div>
            </template>
            <template v-else>
              <div class="absolute inset-0 flex items-center" aria-hidden="true">
                <div class="h-0.5 w-full bg-gray-200" />
              </div>
              <div
                class="relative flex h-8 w-8 items-center justify-center rounded-full border-2 border-gray-300 bg-white"
              >
                <span class="h-2.5 w-2.5 rounded-full bg-transparent" aria-hidden="true" />
              </div>
            </template>
            <span class="absolute top-10 text-xs font-medium text-gray-500 whitespace-nowrap">
              {{ step.name }}
            </span>
          </li>
        </ol>
      </nav>

      <!-- Step 1: Select Bank & Upload File -->
      <div v-if="currentStep === 1" class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
          {{ $t('banking.select_bank_upload') }}
        </h2>

        <!-- Bank Selection -->
        <div class="mb-6">
          <BaseInputGroup :label="$t('banking.select_bank')" required>
            <BaseSelect
              v-model="selectedBank"
              :options="bankOptions"
              :searchable="true"
              :placeholder="$t('banking.select_bank_placeholder')"
              label="label"
              value-prop="value"
            />
          </BaseInputGroup>
          <p class="mt-1 text-sm text-gray-500">
            {{ $t('banking.bank_auto_detect_hint') }}
          </p>
        </div>

        <!-- Account Selection -->
        <div class="mb-6">
          <BaseInputGroup :label="$t('banking.select_account')" required>
            <BaseSelect
              v-model="selectedAccount"
              :options="accountOptions"
              :searchable="true"
              :placeholder="$t('banking.select_account_placeholder')"
              label="label"
              value-prop="value"
              :disabled="!selectedBank"
            />
          </BaseInputGroup>
        </div>

        <!-- File Upload -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ $t('banking.upload_csv') }} <span class="text-red-500">*</span>
          </label>
          <div
            class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-dashed rounded-lg transition-colors"
            :class="[
              isDragging ? 'border-primary-500 bg-primary-50' : 'border-gray-300 hover:border-gray-400',
              uploadedFile ? 'border-green-500 bg-green-50' : ''
            ]"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleFileDrop"
          >
            <div class="space-y-1 text-center">
              <template v-if="!uploadedFile">
                <DocumentArrowUpIcon class="mx-auto h-12 w-12 text-gray-400" />
                <div class="flex text-sm text-gray-600">
                  <label
                    for="file-upload"
                    class="relative cursor-pointer rounded-md bg-white font-medium text-primary-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-primary-500 focus-within:ring-offset-2 hover:text-primary-500"
                  >
                    <span>{{ $t('banking.upload_file') }}</span>
                    <input
                      id="file-upload"
                      ref="fileInput"
                      type="file"
                      class="sr-only"
                      accept=".csv,.txt"
                      @change="handleFileSelect"
                    />
                  </label>
                  <p class="pl-1">{{ $t('banking.or_drag_drop') }}</p>
                </div>
                <p class="text-xs text-gray-500">CSV {{ $t('banking.up_to_10mb') }}</p>
              </template>
              <template v-else>
                <DocumentCheckIcon class="mx-auto h-12 w-12 text-green-500" />
                <p class="text-sm font-medium text-gray-900">{{ uploadedFile.name }}</p>
                <p class="text-xs text-gray-500">{{ formatFileSize(uploadedFile.size) }}</p>
                <button
                  type="button"
                  class="text-sm text-red-600 hover:text-red-500"
                  @click="clearFile"
                >
                  {{ $t('general.remove') }}
                </button>
              </template>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-3">
          <BaseButton variant="gray" :to="{ name: 'banking.dashboard' }">
            {{ $t('general.cancel') }}
          </BaseButton>
          <BaseButton
            variant="primary"
            :loading="isUploading"
            :disabled="!canProceedToPreview"
            @click="uploadAndPreview"
          >
            {{ $t('banking.preview_import') }}
          </BaseButton>
        </div>
      </div>

      <!-- Step 2: Preview & Confirm -->
      <div v-if="currentStep === 2" class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
          {{ $t('banking.preview_transactions') }}
        </h2>

        <!-- Preview Summary -->
        <div class="grid grid-cols-3 gap-4 mb-6">
          <div class="bg-blue-50 rounded-lg p-4">
            <p class="text-sm text-blue-600">{{ $t('banking.total_transactions') }}</p>
            <p class="text-2xl font-bold text-blue-900">{{ previewData.total || 0 }}</p>
          </div>
          <div class="bg-green-50 rounded-lg p-4">
            <p class="text-sm text-green-600">{{ $t('banking.new_transactions') }}</p>
            <p class="text-2xl font-bold text-green-900">{{ previewData.new || 0 }}</p>
          </div>
          <div class="bg-yellow-50 rounded-lg p-4">
            <p class="text-sm text-yellow-600">{{ $t('banking.duplicates') }}</p>
            <p class="text-2xl font-bold text-yellow-900">{{ previewData.duplicates || 0 }}</p>
          </div>
        </div>

        <!-- Detected Bank -->
        <div v-if="previewData.detected_bank" class="mb-4 p-3 bg-gray-50 rounded-lg">
          <p class="text-sm text-gray-600">
            {{ $t('banking.detected_bank') }}:
            <span class="font-medium text-gray-900">{{ previewData.detected_bank }}</span>
          </p>
        </div>

        <!-- Preview Table -->
        <div class="overflow-x-auto mb-6">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  {{ $t('banking.date') }}
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  {{ $t('banking.description') }}
                </th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                  {{ $t('banking.amount') }}
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  {{ $t('banking.counterparty') }}
                </th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">
                  {{ $t('banking.status') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="(tx, index) in previewData.transactions" :key="index">
                <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
                  {{ formatDate(tx.transaction_date) }}
                </td>
                <td class="px-4 py-3 text-sm text-gray-600 max-w-xs truncate">
                  {{ tx.description }}
                </td>
                <td
                  class="px-4 py-3 text-sm font-medium text-right whitespace-nowrap"
                  :class="tx.amount >= 0 ? 'text-green-600' : 'text-red-600'"
                >
                  {{ formatMoney(tx.amount, tx.currency) }}
                </td>
                <td class="px-4 py-3 text-sm text-gray-600 max-w-xs truncate">
                  {{ tx.counterparty_name || '-' }}
                </td>
                <td class="px-4 py-3 text-center">
                  <span
                    v-if="tx.is_duplicate"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"
                  >
                    {{ $t('banking.duplicate') }}
                  </span>
                  <span
                    v-else
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                  >
                    {{ $t('banking.new') }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Show more link -->
        <p v-if="previewData.total > 10" class="text-sm text-gray-500 text-center mb-6">
          {{ $t('banking.showing_first_10', { total: previewData.total }) }}
        </p>

        <!-- Actions -->
        <div class="flex justify-between">
          <BaseButton variant="gray" @click="goBack">
            {{ $t('general.back') }}
          </BaseButton>
          <div class="space-x-3">
            <BaseButton variant="gray" :to="{ name: 'banking.dashboard' }">
              {{ $t('general.cancel') }}
            </BaseButton>
            <BaseButton
              variant="primary"
              :loading="isImporting"
              :disabled="!previewData.new || previewData.new === 0"
              @click="confirmImport"
            >
              {{ $t('banking.import_transactions', { count: previewData.new || 0 }) }}
            </BaseButton>
          </div>
        </div>
      </div>

      <!-- Step 3: Import Complete -->
      <div v-if="currentStep === 3" class="bg-white rounded-lg shadow-md p-6 text-center">
        <CheckCircleIcon class="mx-auto h-16 w-16 text-green-500 mb-4" />
        <h2 class="text-lg font-semibold text-gray-900 mb-2">
          {{ $t('banking.import_complete') }}
        </h2>
        <p class="text-gray-600 mb-6">
          {{ $t('banking.import_success_message', { count: importResult.imported || 0 }) }}
        </p>

        <!-- Import Result Summary -->
        <div class="grid grid-cols-3 gap-4 mb-8 max-w-md mx-auto">
          <div class="bg-green-50 rounded-lg p-3">
            <p class="text-xs text-green-600">{{ $t('banking.imported') }}</p>
            <p class="text-xl font-bold text-green-900">{{ importResult.imported || 0 }}</p>
          </div>
          <div class="bg-yellow-50 rounded-lg p-3">
            <p class="text-xs text-yellow-600">{{ $t('banking.skipped') }}</p>
            <p class="text-xl font-bold text-yellow-900">{{ importResult.duplicates || 0 }}</p>
          </div>
          <div class="bg-red-50 rounded-lg p-3">
            <p class="text-xs text-red-600">{{ $t('banking.failed') }}</p>
            <p class="text-xl font-bold text-red-900">{{ importResult.failed || 0 }}</p>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-center space-x-3">
          <BaseButton variant="primary-outline" @click="startNewImport">
            {{ $t('banking.import_another') }}
          </BaseButton>
          <BaseButton variant="primary" :to="{ name: 'banking.reconciliation' }">
            {{ $t('banking.go_to_reconciliation') }}
          </BaseButton>
        </div>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'
import axios from 'axios'
import {
  CheckIcon,
  DocumentArrowUpIcon,
  DocumentCheckIcon,
  CheckCircleIcon,
} from '@heroicons/vue/24/outline'

const { t } = useI18n()
const notificationStore = useNotificationStore()

// State
const currentStep = ref(1)
const selectedBank = ref('auto')
const selectedAccount = ref(null)
const uploadedFile = ref(null)
const isDragging = ref(false)
const isUploading = ref(false)
const isImporting = ref(false)
const previewData = ref({
  total: 0,
  new: 0,
  duplicates: 0,
  detected_bank: null,
  transactions: [],
})
const importResult = ref({
  imported: 0,
  duplicates: 0,
  failed: 0,
})
const importId = ref(null)
const accounts = ref([])

// Bank options
const bankOptions = computed(() => [
  { label: t('banking.auto_detect'), value: 'auto' },
  { label: 'NLB Banka', value: 'nlb' },
  { label: 'Stopanska Banka', value: 'stopanska' },
  { label: 'Komercijalna Banka', value: 'komercijalna' },
])

// Account options
const accountOptions = computed(() => {
  return accounts.value.map(acc => ({
    label: `${acc.bank_name} - ${acc.account_number}`,
    value: acc.id,
  }))
})

// Steps
const steps = computed(() => [
  {
    name: t('banking.step_upload'),
    status: currentStep.value === 1 ? 'current' : currentStep.value > 1 ? 'complete' : 'upcoming',
  },
  {
    name: t('banking.step_preview'),
    status: currentStep.value === 2 ? 'current' : currentStep.value > 2 ? 'complete' : 'upcoming',
  },
  {
    name: t('banking.step_complete'),
    status: currentStep.value === 3 ? 'current' : 'upcoming',
  },
])

// Computed
const canProceedToPreview = computed(() => {
  return selectedAccount.value && uploadedFile.value
})

// Methods
const handleFileSelect = (event) => {
  const file = event.target.files[0]
  if (file) {
    validateAndSetFile(file)
  }
}

const handleFileDrop = (event) => {
  isDragging.value = false
  const file = event.dataTransfer.files[0]
  if (file) {
    validateAndSetFile(file)
  }
}

const validateAndSetFile = (file) => {
  // Check file type
  const validTypes = ['text/csv', 'text/plain', 'application/vnd.ms-excel']
  const validExtensions = ['.csv', '.txt']
  const hasValidExtension = validExtensions.some(ext => file.name.toLowerCase().endsWith(ext))

  if (!validTypes.includes(file.type) && !hasValidExtension) {
    notificationStore.showNotification({
      type: 'error',
      message: t('banking.invalid_file_type'),
    })
    return
  }

  // Check file size (10MB max)
  if (file.size > 10 * 1024 * 1024) {
    notificationStore.showNotification({
      type: 'error',
      message: t('banking.file_too_large'),
    })
    return
  }

  uploadedFile.value = file
}

const clearFile = () => {
  uploadedFile.value = null
}

const uploadAndPreview = async () => {
  if (!uploadedFile.value || !selectedAccount.value) return

  isUploading.value = true

  try {
    const formData = new FormData()
    formData.append('file', uploadedFile.value)
    formData.append('bank_code', selectedBank.value)
    formData.append('account_id', selectedAccount.value)

    const response = await axios.post('/banking/import/preview', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })

    previewData.value = response.data.data
    importId.value = response.data.data.import_id
    currentStep.value = 2
  } catch (error) {
    console.error('Preview failed:', error)
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('banking.preview_failed'),
    })
  } finally {
    isUploading.value = false
  }
}

const confirmImport = async () => {
  if (!importId.value) return

  isImporting.value = true

  try {
    const response = await axios.post('/banking/import/confirm', {
      import_id: importId.value,
    })

    importResult.value = response.data.data
    currentStep.value = 3

    notificationStore.showNotification({
      type: 'success',
      message: t('banking.import_successful'),
    })
  } catch (error) {
    console.error('Import failed:', error)
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('banking.import_failed'),
    })
  } finally {
    isImporting.value = false
  }
}

const goBack = () => {
  currentStep.value = 1
}

const startNewImport = () => {
  currentStep.value = 1
  uploadedFile.value = null
  selectedBank.value = 'auto'
  previewData.value = {
    total: 0,
    new: 0,
    duplicates: 0,
    detected_bank: null,
    transactions: [],
  }
  importResult.value = {
    imported: 0,
    duplicates: 0,
    failed: 0,
  }
  importId.value = null
}

const formatFileSize = (bytes) => {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

const formatMoney = (amount, currency) => {
  return new Intl.NumberFormat('mk-MK', {
    style: 'currency',
    currency: currency || 'MKD',
  }).format(amount)
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('mk-MK')
}

// Fetch accounts on mount
const fetchAccounts = async () => {
  try {
    const response = await axios.get('/banking/accounts')
    accounts.value = response.data.data || []
    if (accounts.value.length === 1) {
      selectedAccount.value = accounts.value[0].id
    }
  } catch (error) {
    console.error('Failed to fetch accounts:', error)
  }
}

// Initialize
fetchAccounts()
</script>

<!-- CLAUDE-CHECKPOINT -->
