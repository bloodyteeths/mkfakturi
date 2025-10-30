<template>
  <div class="space-y-6">
    <!-- Step Header -->
    <div class="text-center">
      <BaseHeading tag="h2" class="text-2xl font-bold text-gray-900 mb-2">
        {{ $t('imports.commit_import') }}
      </BaseHeading>
      <p class="text-gray-600 max-w-3xl mx-auto">
        {{ $t('imports.commit_import_description_detailed') }}
      </p>
    </div>

    <div class="max-w-6xl mx-auto space-y-6">
      <!-- Import Summary -->
      <BaseCard>
        <template #header>
          <h3 class="text-lg font-medium text-gray-900">
            {{ $t('imports.import_summary') }}
          </h3>
        </template>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <!-- Data Statistics -->
          <div class="space-y-4">
            <h4 class="font-medium text-gray-900 flex items-center">
              <BaseIcon name="ChartBarIcon" class="w-4 h-4 mr-2" />
              {{ $t('imports.data_statistics') }}
            </h4>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-500">{{ $t('imports.total_records') }}</span>
                <span class="font-medium">{{ importStore.totalRecords }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">{{ $t('imports.valid_records') }}</span>
                <span class="font-medium text-green-600">{{ importStore.validRecords }}</span>
              </div>
              <div v-if="importStore.invalidRecords > 0" class="flex justify-between">
                <span class="text-gray-500">{{ $t('imports.invalid_records') }}</span>
                <span class="font-medium text-red-600">{{ importStore.invalidRecords }}</span>
              </div>
            </div>
          </div>

          <!-- File Information -->
          <div class="space-y-4">
            <h4 class="font-medium text-gray-900 flex items-center">
              <BaseIcon name="DocumentIcon" class="w-4 h-4 mr-2" />
              {{ $t('imports.file_information') }}
            </h4>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-500">{{ $t('imports.filename') }}</span>
                <span class="font-medium">{{ importStore.fileInfo?.name }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">{{ $t('imports.format') }}</span>
                <span class="font-medium uppercase">{{ importStore.fileType }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">{{ $t('imports.file_size') }}</span>
                <span class="font-medium">{{ formatFileSize(importStore.fileInfo?.size) }}</span>
              </div>
            </div>
          </div>

          <!-- Mapping Summary -->
          <div class="space-y-4">
            <h4 class="font-medium text-gray-900 flex items-center">
              <BaseIcon name="ArrowsRightLeftIcon" class="w-4 h-4 mr-2" />
              {{ $t('imports.field_mapping') }}
            </h4>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-500">{{ $t('imports.mapped_fields') }}</span>
                <span class="font-medium">{{ mappedFieldsCount }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">{{ $t('imports.auto_mapped') }}</span>
                <span class="font-medium">{{ autoMappedCount }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">{{ $t('imports.confidence') }}</span>
                <span class="font-medium">{{ Math.round(importStore.autoMappingConfidence * 100) }}%</span>
              </div>
            </div>
          </div>

          <!-- Time Estimation -->
          <div class="space-y-4">
            <h4 class="font-medium text-gray-900 flex items-center">
              <BaseIcon name="ClockIcon" class="w-4 h-4 mr-2" />
              {{ $t('imports.time_estimation') }}
            </h4>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-500">{{ $t('imports.estimated_time') }}</span>
                <span class="font-medium">{{ estimatedTime }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">{{ $t('imports.started_at') }}</span>
                <span class="font-medium">{{ formatDateTime(importStore.importJob?.created_at) }}</span>
              </div>
              <div v-if="importStore.commitStatus === 'completed'" class="flex justify-between">
                <span class="text-gray-500">{{ $t('imports.completed_at') }}</span>
                <span class="font-medium">{{ formatDateTime(importStore.commitResults?.completed_at) }}</span>
              </div>
            </div>
          </div>
        </div>
      </BaseCard>

      <!-- Import Progress -->
      <BaseCard v-if="importStore.isCommitting || importStore.commitStatus !== 'pending'">
        <template #header>
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900">
              {{ $t('imports.import_progress') }}
            </h3>
            <BaseBadge
              :variant="getCommitStatusVariant()"
              class="px-3 py-1 text-sm font-medium"
            >
              {{ getCommitStatusText() }}
            </BaseBadge>
          </div>
        </template>

        <div class="space-y-6">
          <!-- Overall Progress -->
          <div>
            <div class="flex justify-between text-sm font-medium text-gray-900 mb-2">
              <span>{{ $t('imports.overall_progress') }}</span>
              <span>{{ Math.round(importStore.commitProgress) }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
              <div
                :class="[
                  'h-3 rounded-full transition-all duration-300',
                  {
                    'bg-blue-500': importStore.isCommitting,
                    'bg-green-500': importStore.commitStatus === 'completed',
                    'bg-red-500': importStore.commitStatus === 'failed',
                  }
                ]"
                :style="{ width: `${importStore.commitProgress}%` }"
              ></div>
            </div>
          </div>

          <!-- Progress Steps -->
          <div class="space-y-3">
            <div
              v-for="step in progressSteps"
              :key="step.key"
              class="flex items-center"
            >
              <div
                :class="[
                  'flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-full text-xs mr-3',
                  {
                    'bg-blue-600 text-white': step.status === 'in_progress',
                    'bg-green-500 text-white': step.status === 'completed',
                    'bg-red-500 text-white': step.status === 'failed',
                    'bg-gray-300 text-gray-600': step.status === 'pending',
                  }
                ]"
              >
                <BaseIcon v-if="step.status === 'completed'" name="CheckIcon" class="w-4 h-4" />
                <BaseIcon v-else-if="step.status === 'failed'" name="XMarkIcon" class="w-4 h-4" />
                <BaseSpinner v-else-if="step.status === 'in_progress'" class="w-4 h-4" />
                <span v-else>{{ step.number }}</span>
              </div>
              <div class="flex-1">
                <div class="text-sm font-medium">{{ step.title }}</div>
                <div class="text-xs text-gray-500">{{ step.description }}</div>
              </div>
              <div v-if="step.count !== undefined" class="text-sm text-gray-500">
                {{ step.count }} {{ $t('imports.records') }}
              </div>
            </div>
          </div>

          <!-- Current Activity -->
          <div v-if="importStore.isCommitting" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-center">
              <BaseSpinner class="w-5 h-5 text-blue-600 mr-3" />
              <div>
                <div class="text-sm font-medium text-blue-900">
                  {{ currentActivity }}
                </div>
                <div class="text-xs text-blue-700">
                  {{ $t('imports.please_wait_importing') }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </BaseCard>

      <!-- Import Results -->
      <BaseCard v-if="importStore.commitResults && importStore.commitStatus === 'completed'">
        <template #header>
          <div class="flex items-center">
            <BaseIcon name="CheckCircleIcon" class="w-5 h-5 text-green-500 mr-2" />
            <h3 class="text-lg font-medium text-gray-900">
              {{ $t('imports.import_results') }}
            </h3>
          </div>
        </template>

        <div class="space-y-6">
          <!-- Success Message -->
          <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-start">
              <BaseIcon name="CheckCircleIcon" class="w-5 h-5 text-green-400 mt-0.5 mr-3 flex-shrink-0" />
              <div>
                <h4 class="text-sm font-medium text-green-800 mb-1">
                  {{ $t('imports.import_completed_successfully') }}
                </h4>
                <p class="text-sm text-green-700">
                  {{ $t('imports.import_success_message', { 
                    records: importStore.commitResults.imported_records,
                    duration: formatDuration(importStore.commitResults.duration)
                  }) }}
                </p>
              </div>
            </div>
          </div>

          <!-- Detailed Results -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div
              v-for="result in detailedResults"
              :key="result.key"
              class="text-center p-4 bg-gray-50 rounded-lg"
            >
              <div :class="['text-2xl font-bold mb-1', result.color]">
                {{ result.count }}
              </div>
              <div class="text-sm text-gray-600">
                {{ result.label }}
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-center space-x-4">
            <BaseButton
              variant="secondary"
              @click="viewImportedData"
            >
              {{ $t('imports.view_imported_data') }}
            </BaseButton>
            <BaseButton
              variant="secondary"
              @click="downloadImportLog"
            >
              {{ $t('imports.download_log') }}
            </BaseButton>
          </div>
        </div>
      </BaseCard>

      <!-- Import Failed -->
      <BaseCard v-if="importStore.commitStatus === 'failed'">
        <template #header>
          <div class="flex items-center">
            <BaseIcon name="XCircleIcon" class="w-5 h-5 text-red-500 mr-2" />
            <h3 class="text-lg font-medium text-gray-900">
              {{ $t('imports.import_failed') }}
            </h3>
          </div>
        </template>

        <div class="space-y-4">
          <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-start">
              <BaseIcon name="XCircleIcon" class="w-5 h-5 text-red-400 mt-0.5 mr-3 flex-shrink-0" />
              <div>
                <h4 class="text-sm font-medium text-red-800 mb-1">
                  {{ $t('imports.import_failed_title') }}
                </h4>
                <p class="text-sm text-red-700">
                  {{ importStore.getError('commit') || $t('imports.unknown_error') }}
                </p>
              </div>
            </div>
          </div>

          <div class="flex justify-center space-x-4">
            <BaseButton
              variant="primary"
              @click="retryImport"
              :loading="importStore.isCommitting"
            >
              {{ $t('imports.retry_import') }}
            </BaseButton>
            <BaseButton
              variant="secondary"
              @click="viewLogs"
            >
              {{ $t('imports.view_logs') }}
            </BaseButton>
          </div>
        </div>
      </BaseCard>

      <!-- Pre-commit Actions -->
      <BaseCard v-if="importStore.commitStatus === 'pending'">
        <template #header>
          <h3 class="text-lg font-medium text-gray-900">
            {{ $t('imports.ready_to_import') }}
          </h3>
        </template>

        <div class="space-y-4">
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-start">
              <BaseIcon name="InformationCircleIcon" class="w-5 h-5 text-blue-400 mt-0.5 mr-3 flex-shrink-0" />
              <div>
                <h4 class="text-sm font-medium text-blue-800 mb-1">
                  {{ $t('imports.final_check') }}
                </h4>
                <p class="text-sm text-blue-700 mb-3">
                  {{ $t('imports.final_check_description') }}
                </p>
                <ul class="text-sm text-blue-700 space-y-1">
                  <li class="flex items-center">
                    <BaseIcon name="CheckIcon" class="w-4 h-4 mr-2" />
                    {{ $t('imports.backup_completed') }}
                  </li>
                  <li class="flex items-center">
                    <BaseIcon name="CheckIcon" class="w-4 h-4 mr-2" />
                    {{ $t('imports.validation_passed') }}
                  </li>
                  <li class="flex items-center">
                    <BaseIcon name="CheckIcon" class="w-4 h-4 mr-2" />
                    {{ $t('imports.mapping_verified') }}
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <!-- Important Notes -->
          <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex items-start">
              <BaseIcon name="ExclamationTriangleIcon" class="w-5 h-5 text-yellow-400 mt-0.5 mr-3 flex-shrink-0" />
              <div>
                <h4 class="text-sm font-medium text-yellow-800 mb-2">
                  {{ $t('imports.important_notes') }}
                </h4>
                <ul class="text-sm text-yellow-700 space-y-1">
                  <li>• {{ $t('imports.note_irreversible') }}</li>
                  <li>• {{ $t('imports.note_backup_created') }}</li>
                  <li>• {{ $t('imports.note_audit_logged') }}</li>
                  <li>• {{ $t('imports.note_notifications_sent') }}</li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </BaseCard>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'

// Components
import BaseHeading from '@/scripts/components/base/BaseHeading.vue'
import BaseCard from '@/scripts/components/base/BaseCard.vue'
import BaseButton from '@/scripts/components/base/BaseButton.vue'
import BaseIcon from '@/scripts/components/base/BaseIcon.vue'
import BaseBadge from '@/scripts/components/base/BaseBadge.vue'
import BaseSpinner from '@/scripts/components/base/BaseSpinner.vue'

// Store
import { useImportStore } from '@/scripts/admin/stores/import'

const { t } = useI18n()
const router = useRouter()
const importStore = useImportStore()

// Computed
const mappedFieldsCount = computed(() => {
  return Object.keys(importStore.fieldMappings).filter(field => importStore.fieldMappings[field]).length
})

const autoMappedCount = computed(() => {
  return Object.keys(importStore.mappingSuggestions).filter(field => 
    importStore.fieldMappings[field] === importStore.mappingSuggestions[field]
  ).length
})

const estimatedTime = computed(() => {
  const records = importStore.totalRecords
  if (records <= 100) return '< 1 min'
  if (records <= 1000) return '1-3 min'
  if (records <= 5000) return '3-10 min'
  return '10+ min'
})

const progressSteps = computed(() => [
  {
    key: 'prepare',
    number: 1,
    title: t('imports.preparing_data'),
    description: t('imports.preparing_data_desc'),
    status: getStepStatus('prepare'),
    count: importStore.commitResults?.prepared_records,
  },
  {
    key: 'customers',
    number: 2,
    title: t('imports.importing_customers'),
    description: t('imports.importing_customers_desc'),
    status: getStepStatus('customers'),
    count: importStore.commitResults?.imported_customers,
  },
  {
    key: 'invoices',
    number: 3,
    title: t('imports.importing_invoices'),
    description: t('imports.importing_invoices_desc'),
    status: getStepStatus('invoices'),
    count: importStore.commitResults?.imported_invoices,
  },
  {
    key: 'items',
    number: 4,
    title: t('imports.importing_items'),
    description: t('imports.importing_items_desc'),
    status: getStepStatus('items'),
    count: importStore.commitResults?.imported_items,
  },
  {
    key: 'payments',
    number: 5,
    title: t('imports.importing_payments'),
    description: t('imports.importing_payments_desc'),
    status: getStepStatus('payments'),
    count: importStore.commitResults?.imported_payments,
  },
  {
    key: 'finalize',
    number: 6,
    title: t('imports.finalizing'),
    description: t('imports.finalizing_desc'),
    status: getStepStatus('finalize'),
  },
])

const currentActivity = computed(() => {
  const currentStep = progressSteps.value.find(step => step.status === 'in_progress')
  return currentStep ? currentStep.title : t('imports.processing')
})

const detailedResults = computed(() => {
  if (!importStore.commitResults) return []
  
  return [
    {
      key: 'customers',
      label: t('imports.customers_imported'),
      count: importStore.commitResults.imported_customers || 0,
      color: 'text-blue-600',
    },
    {
      key: 'invoices',
      label: t('imports.invoices_imported'),
      count: importStore.commitResults.imported_invoices || 0,
      color: 'text-green-600',
    },
    {
      key: 'items',
      label: t('imports.items_imported'),
      count: importStore.commitResults.imported_items || 0,
      color: 'text-purple-600',
    },
    {
      key: 'payments',
      label: t('imports.payments_imported'),
      count: importStore.commitResults.imported_payments || 0,
      color: 'text-orange-600',
    },
  ]
})

// Methods
const getCommitStatusVariant = () => {
  switch (importStore.commitStatus) {
    case 'processing': return 'info'
    case 'completed': return 'success'
    case 'failed': return 'danger'
    default: return 'secondary'
  }
}

const getCommitStatusText = () => {
  switch (importStore.commitStatus) {
    case 'processing': return t('imports.importing')
    case 'completed': return t('imports.completed')
    case 'failed': return t('imports.failed')
    default: return t('imports.pending')
  }
}

const getStepStatus = (stepKey) => {
  if (!importStore.commitResults || !importStore.commitResults.step_status) {
    return importStore.isCommitting ? (stepKey === 'prepare' ? 'in_progress' : 'pending') : 'pending'
  }
  
  return importStore.commitResults.step_status[stepKey] || 'pending'
}

const retryImport = async () => {
  try {
    await importStore.commitImport()
  } catch (error) {
    console.error('Retry import failed:', error)
  }
}

const viewImportedData = () => {
  // Navigate to appropriate data view based on import type
  router.push('/admin/customers') // Or dynamic based on imported data
}

const downloadImportLog = async () => {
  try {
    const logs = await importStore.fetchLogs()
    const blob = new Blob([JSON.stringify(logs, null, 2)], { type: 'application/json' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `import-log-${importStore.importId}.json`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Failed to download log:', error)
  }
}

const viewLogs = () => {
  // This could open a modal or navigate to a logs page
  console.log('View logs')
}

const formatFileSize = (bytes) => {
  if (!bytes) return '0 Bytes'
  
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const formatDateTime = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleString()
}

const formatDuration = (seconds) => {
  if (!seconds) return '0s'
  
  const minutes = Math.floor(seconds / 60)
  const remainingSeconds = seconds % 60
  
  if (minutes > 0) {
    return `${minutes}m ${remainingSeconds}s`
  }
  return `${seconds}s`
}
</script>