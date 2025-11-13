<template>
  <div class="space-y-6">
    <!-- Step Header -->
    <div class="text-center">
      <BaseHeading tag="h2" class="text-2xl font-bold text-gray-900 mb-2">
        {{ $t('imports.validate_data') }}
      </BaseHeading>
      <p class="text-gray-600 max-w-3xl mx-auto">
        {{ $t('imports.validate_data_description_detailed') }}
      </p>
    </div>

    <!-- Validation in Progress -->
    <div v-if="importStore.isValidating" class="max-w-4xl mx-auto">
      <BaseCard>
        <div class="text-center py-8">
          <BaseSpinner class="w-12 h-12 text-primary-600 mx-auto mb-4" />
          <h3 class="text-lg font-medium text-gray-900 mb-2">
            {{ $t('imports.validating_data') }}
          </h3>
          <p class="text-gray-600 mb-4">
            {{ $t('imports.validation_in_progress') }}
          </p>
          <div class="max-w-md mx-auto">
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div class="bg-primary-600 h-2 rounded-full animate-pulse" style="width: 60%"></div>
            </div>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- Validation Results -->
    <div v-else-if="importStore.validationResults" class="max-w-6xl mx-auto space-y-6">
      <!-- Validation Summary -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <BaseCard>
          <div class="text-center">
            <div class="text-3xl font-bold text-gray-900 mb-1">
              {{ importStore.totalRecords }}
            </div>
            <div class="text-sm text-gray-500">
              {{ $t('imports.total_records') }}
            </div>
          </div>
        </BaseCard>

        <BaseCard>
          <div class="text-center">
            <div class="text-3xl font-bold text-green-600 mb-1">
              {{ importStore.validRecords }}
            </div>
            <div class="text-sm text-gray-500">
              {{ $t('imports.valid_records') }}
            </div>
          </div>
        </BaseCard>

        <BaseCard>
          <div class="text-center">
            <div class="text-3xl font-bold text-yellow-600 mb-1">
              {{ importStore.validationWarnings.length }}
            </div>
            <div class="text-sm text-gray-500">
              {{ $t('imports.warnings') }}
            </div>
          </div>
        </BaseCard>

        <BaseCard>
          <div class="text-center">
            <div class="text-3xl font-bold text-red-600 mb-1">
              {{ importStore.validationErrors.length }}
            </div>
            <div class="text-sm text-gray-500">
              {{ $t('imports.errors') }}
            </div>
          </div>
        </BaseCard>
      </div>

      <!-- Validation Status -->
      <BaseCard>
        <template #header>
          <div class="flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900">
              {{ $t('imports.validation_status') }}
            </h3>
            <BaseBadge
              :variant="getValidationBadgeVariant()"
              class="px-3 py-1 text-sm font-medium"
            >
              {{ getValidationStatusText() }}
            </BaseBadge>
          </div>
        </template>

        <div class="space-y-4">
          <!-- Overall Progress -->
          <div>
            <div class="flex justify-between text-sm font-medium text-gray-900 mb-2">
              <span>{{ $t('imports.data_quality') }}</span>
              <span>{{ Math.round(dataQualityScore * 100) }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
              <div
                :class="[
                  'h-3 rounded-full transition-all duration-300',
                  {
                    'bg-green-500': dataQualityScore >= 0.8,
                    'bg-yellow-500': dataQualityScore >= 0.6 && dataQualityScore < 0.8,
                    'bg-red-500': dataQualityScore < 0.6,
                  }
                ]"
                :style="{ width: `${dataQualityScore * 100}%` }"
              ></div>
            </div>
          </div>

          <!-- Status Message -->
          <div
            :class="[
              'p-4 rounded-lg border',
              {
                'bg-green-50 border-green-200': canProceed,
                'bg-yellow-50 border-yellow-200': hasWarnings && !hasErrors,
                'bg-red-50 border-red-200': hasErrors,
              }
            ]"
          >
            <div class="flex items-start">
              <BaseIcon
                :name="getStatusIcon()"
                :class="[
                  'w-5 h-5 mt-0.5 mr-3 flex-shrink-0',
                  {
                    'text-green-400': canProceed,
                    'text-yellow-400': hasWarnings && !hasErrors,
                    'text-red-400': hasErrors,
                  }
                ]"
              />
              <div>
                <p class="text-sm font-medium mb-1">
                  {{ getStatusMessage() }}
                </p>
                <p class="text-sm text-gray-600">
                  {{ getStatusDescription() }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </BaseCard>

      <!-- Validation Errors -->
      <BaseCard v-if="importStore.validationErrors.length > 0">
        <template #header>
          <div class="flex items-center">
            <BaseIcon name="ExclamationTriangleIcon" class="w-5 h-5 text-red-500 mr-2" />
            <h3 class="text-lg font-medium text-gray-900">
              {{ $t('imports.validation_errors') }}
              <span class="text-red-600">({{ importStore.validationErrors.length }})</span>
            </h3>
          </div>
        </template>

        <div class="space-y-4">
          <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="text-sm text-red-800 mb-2">
              {{ $t('imports.errors_must_be_fixed') }}
            </div>
          </div>

          <div class="max-h-96 overflow-y-auto">
            <div class="space-y-3">
              <div
                v-for="(error, index) in importStore.validationErrors"
                :key="index"
                class="bg-white border border-red-200 rounded-lg p-4"
              >
                <div class="flex justify-between items-start">
                  <div class="flex-1">
                    <div class="flex items-center mb-2">
                      <span class="text-sm font-medium text-gray-900 mr-2">
                        {{ $t('imports.row') }} {{ error.row_number }}:
                      </span>
                      <span class="text-sm text-red-600 font-medium">
                        {{ error.field }}
                      </span>
                    </div>
                    <p class="text-sm text-red-800 mb-2">
                      {{ error.message }}
                    </p>
                    <div v-if="error.value" class="text-xs text-gray-600 bg-gray-100 px-2 py-1 rounded">
                      {{ $t('imports.invalid_value') }}: "{{ error.value }}"
                    </div>
                  </div>
                  
                  <!-- Error Actions -->
                  <div class="flex space-x-2 ml-4">
                    <BaseButton
                      v-if="error.suggestion"
                      variant="secondary"
                      size="xs"
                      @click="applySuggestion(error)"
                    >
                      {{ $t('imports.apply_suggestion') }}
                    </BaseButton>
                    <BaseButton
                      variant="danger"
                      size="xs"
                      @click="ignoreError(error)"
                    >
                      {{ $t('imports.ignore') }}
                    </BaseButton>
                  </div>
                </div>
                
                <!-- Suggestion -->
                <div v-if="error.suggestion" class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded">
                  <div class="flex items-center mb-1">
                    <BaseIcon name="LightBulbIcon" class="w-4 h-4 text-blue-500 mr-2" />
                    <span class="text-sm font-medium text-blue-800">{{ $t('imports.suggestion') }}:</span>
                  </div>
                  <p class="text-sm text-blue-700">{{ error.suggestion }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </BaseCard>

      <!-- Validation Warnings -->
      <BaseCard v-if="importStore.validationWarnings.length > 0">
        <template #header>
          <div class="flex items-center">
            <BaseIcon name="ExclamationTriangleIcon" class="w-5 h-5 text-yellow-500 mr-2" />
            <h3 class="text-lg font-medium text-gray-900">
              {{ $t('imports.validation_warnings') }}
              <span class="text-yellow-600">({{ importStore.validationWarnings.length }})</span>
            </h3>
          </div>
        </template>

        <div class="space-y-4">
          <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="text-sm text-yellow-800 mb-2">
              {{ $t('imports.warnings_can_proceed') }}
            </div>
          </div>

          <div class="max-h-64 overflow-y-auto">
            <div class="space-y-3">
              <div
                v-for="(warning, index) in importStore.validationWarnings"
                :key="index"
                class="bg-white border border-yellow-200 rounded-lg p-4"
              >
                <div class="flex justify-between items-start">
                  <div class="flex-1">
                    <div class="flex items-center mb-2">
                      <span class="text-sm font-medium text-gray-900 mr-2">
                        {{ $t('imports.row') }} {{ warning.row_number }}:
                      </span>
                      <span class="text-sm text-yellow-600 font-medium">
                        {{ warning.field }}
                      </span>
                    </div>
                    <p class="text-sm text-yellow-800 mb-2">
                      {{ warning.message }}
                    </p>
                    <div v-if="warning.value" class="text-xs text-gray-600 bg-gray-100 px-2 py-1 rounded">
                      {{ $t('imports.current_value') }}: "{{ warning.value }}"
                    </div>
                  </div>
                  
                  <!-- Warning Actions -->
                  <div class="flex space-x-2 ml-4">
                    <BaseButton
                      v-if="warning.suggestion"
                      variant="secondary"
                      size="xs"
                      @click="applySuggestion(warning)"
                    >
                      {{ $t('imports.apply_suggestion') }}
                    </BaseButton>
                    <BaseButton
                      variant="secondary"
                      size="xs"
                      @click="ignoreWarning(warning)"
                    >
                      {{ $t('imports.ignore') }}
                    </BaseButton>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </BaseCard>

      <!-- Data Preview -->
      <BaseCard>
        <template #header>
          <div class="flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900">
              {{ $t('imports.data_preview') }}
            </h3>
            <BaseButton
              variant="secondary"
              size="sm"
              @click="showFullPreview = !showFullPreview"
            >
              {{ showFullPreview ? $t('imports.show_less') : $t('imports.show_more') }}
            </BaseButton>
          </div>
        </template>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ $t('imports.row') }}
                </th>
                <th
                  v-for="field in previewFields"
                  :key="field"
                  class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  {{ field }}
                </th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ $t('imports.status') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr
                v-for="(record, index) in previewRecords"
                :key="index"
                :class="{
                  'bg-red-50': record.has_errors,
                  'bg-yellow-50': record.has_warnings && !record.has_errors,
                  'bg-green-50': !record.has_errors && !record.has_warnings,
                }"
              >
                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">
                  {{ record.row_number }}
                </td>
                <td
                  v-for="field in previewFields"
                  :key="field"
                  class="px-3 py-2 text-sm text-gray-900 max-w-xs truncate"
                >
                  {{ record.data[field] || '-' }}
                </td>
                <td class="px-3 py-2 whitespace-nowrap">
                  <BaseBadge
                    :variant="getRecordStatusVariant(record)"
                    size="sm"
                  >
                    {{ getRecordStatusText(record) }}
                  </BaseBadge>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </BaseCard>
    </div>

    <!-- No Validation Results -->
    <div v-else class="max-w-4xl mx-auto">
      <BaseCard>
        <div class="text-center py-8">
          <BaseIcon name="ExclamationTriangleIcon" class="w-12 h-12 text-gray-400 mx-auto mb-4" />
          <h3 class="text-lg font-medium text-gray-900 mb-2">
            {{ $t('imports.no_validation_results') }}
          </h3>
          <p class="text-gray-600 mb-4">
            {{ $t('imports.validation_not_started') }}
          </p>
          <BaseButton
            variant="primary"
            @click="startValidation"
            :loading="importStore.isValidating"
          >
            {{ $t('imports.start_validation') }}
          </BaseButton>
        </div>
      </BaseCard>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

// Components
import BaseHeading from '@/scripts/components/base/BaseHeading.vue'
import BaseCard from '@/scripts/components/base/BaseCard.vue'
import BaseButton from '@/scripts/components/base/BaseButton.vue'
import BaseIcon from '@/scripts/components/base/BaseIcon.vue'
import BaseSpinner from '@/scripts/components/base/BaseSpinner.vue'
import BaseBadge from '@/scripts/components/base/BaseBadge.vue'

// Store
import { useImportStore } from '@/scripts/admin/stores/import'

const { t } = useI18n()
const importStore = useImportStore()

// Local state
const showFullPreview = ref(false)

// Auto-trigger validation when entering Step 3
onMounted(async () => {
  if (!importStore.validationResults && !importStore.isValidating) {
    await startValidation()
  }
})

// Computed
const hasErrors = computed(() => importStore.validationErrors.length > 0)
const hasWarnings = computed(() => importStore.validationWarnings.length > 0)
const canProceed = computed(() => !hasErrors.value && importStore.validationResults)

const dataQualityScore = computed(() => {
  if (!importStore.validationResults) return 0
  
  const total = importStore.totalRecords
  const valid = importStore.validRecords
  const errors = importStore.validationErrors.length
  const warnings = importStore.validationWarnings.length
  
  if (total === 0) return 0
  
  // Calculate quality score based on valid records and issues
  const validRatio = valid / total
  const errorPenalty = Math.min(errors / total, 0.5) // Max 50% penalty for errors
  const warningPenalty = Math.min(warnings / total, 0.2) // Max 20% penalty for warnings
  
  return Math.max(0, validRatio - errorPenalty - warningPenalty)
})

const previewFields = computed(() => {
  if (!importStore.validationResults || !importStore.validationResults.preview) return []
  
  const sampleRecord = importStore.validationResults.preview[0]
  if (!sampleRecord) return []
  
  return Object.keys(sampleRecord.data).slice(0, showFullPreview.value ? undefined : 5)
})

const previewRecords = computed(() => {
  if (!importStore.validationResults || !importStore.validationResults.preview) return []
  
  return importStore.validationResults.preview.slice(0, showFullPreview.value ? 50 : 10)
})

// Methods
const getValidationBadgeVariant = () => {
  if (hasErrors.value) return 'danger'
  if (hasWarnings.value) return 'warning'
  return 'success'
}

const getValidationStatusText = () => {
  if (hasErrors.value) return t('imports.validation_failed')
  if (hasWarnings.value) return t('imports.validation_warnings')
  return t('imports.validation_passed')
}

const getStatusIcon = () => {
  if (hasErrors.value) return 'XCircleIcon'
  if (hasWarnings.value) return 'ExclamationTriangleIcon'
  return 'CheckCircleIcon'
}

const getStatusMessage = () => {
  if (hasErrors.value) {
    return t('imports.validation_failed_message', { count: importStore.validationErrors.length })
  }
  if (hasWarnings.value) {
    return t('imports.validation_warnings_message', { count: importStore.validationWarnings.length })
  }
  return t('imports.validation_passed_message')
}

const getStatusDescription = () => {
  if (hasErrors.value) {
    return t('imports.fix_errors_to_proceed')
  }
  if (hasWarnings.value) {
    return t('imports.warnings_can_be_ignored')
  }
  return t('imports.data_ready_for_import')
}

const getRecordStatusVariant = (record) => {
  if (record.has_errors) return 'danger'
  if (record.has_warnings) return 'warning'
  return 'success'
}

const getRecordStatusText = (record) => {
  if (record.has_errors) return t('imports.error')
  if (record.has_warnings) return t('imports.warning')
  return t('imports.valid')
}

const startValidation = async () => {
  try {
    await importStore.validateData()
  } catch (error) {
    console.error('Validation failed:', error)
  }
}

const applySuggestion = (issue) => {
  // This would apply the suggested fix to the data
  console.log('Applying suggestion for:', issue)
}

const ignoreError = (error) => {
  // Remove the error from the list (this would typically make an API call)
  const index = importStore.validationErrors.indexOf(error)
  if (index > -1) {
    importStore.validationErrors.splice(index, 1)
  }
}

const ignoreWarning = (warning) => {
  // Remove the warning from the list
  const index = importStore.validationWarnings.indexOf(warning)
  if (index > -1) {
    importStore.validationWarnings.splice(index, 1)
  }
}
</script>