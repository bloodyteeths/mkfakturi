<template>
  <BasePage>
    <BasePageHeader :title="$t('imports.universal_migration_wizard')" class="import-wizard-header">
      <template #actions>
        <div class="flex items-center space-x-3">
          <!-- Help Guide Toggle Button -->
          <BaseButton
            variant="secondary"
            size="sm"
            @click="showHelp = !showHelp"
          >
            <BaseIcon name="QuestionMarkCircleIcon" class="w-4 h-4" />
          </BaseButton>

          <!-- Start Tour Button -->
          <BaseButton
            variant="primary-outline"
            size="sm"
            @click="startTour"
          >
            <BaseIcon name="AcademicCapIcon" class="w-4 h-4 mr-2" />
            {{ $t('imports.start_tour') }}
          </BaseButton>

          <BaseButton
            v-if="importStore.importId"
            variant="danger"
            size="sm"
            @click="confirmCancel"
          >
            {{ $t('general.cancel') }}
          </BaseButton>
        </div>
      </template>
    </BasePageHeader>

    <div class="grid grid-cols-12 gap-6">
      <!-- Progress Sidebar -->
      <div class="col-span-3 progress-sidebar">
        <BaseCard>
          <template #header>
            <h3 class="text-lg font-medium leading-6 text-gray-900">
              {{ $t('imports.progress') }}
            </h3>
          </template>

          <div class="space-y-6">
            <!-- Overall Progress Bar -->
            <div>
              <div class="flex justify-between text-sm font-medium text-gray-900 mb-2">
                <span>{{ $t('imports.overall_progress') }}</span>
                <span>{{ Math.round(importStore.overallProgress) }}%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div
                  class="bg-primary-600 h-2 rounded-full transition-all duration-300"
                  :style="{ width: `${importStore.overallProgress}%` }"
                ></div>
              </div>
            </div>

            <!-- Step Navigation -->
            <nav class="space-y-2">
              <div
                v-for="step in steps"
                :key="step.number"
                :class="[
                  'flex items-center px-3 py-2 rounded-lg text-sm font-medium cursor-pointer transition-colors',
                  {
                    'bg-primary-100 text-primary-700 border border-primary-200': step.number === importStore.currentStep,
                    'text-gray-500 hover:text-gray-700 hover:bg-gray-50': step.number !== importStore.currentStep && !step.completed,
                    'text-green-600 hover:text-green-700 hover:bg-green-50': step.completed,
                    'opacity-50 cursor-not-allowed': !step.accessible,
                  }
                ]"
                @click="goToStep(step.number)"
              >
                <span
                  :class="[
                    'flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-full text-xs mr-3',
                    {
                      'bg-primary-600 text-white': step.number === importStore.currentStep,
                      'bg-gray-300 text-gray-600': step.number !== importStore.currentStep && !step.completed,
                      'bg-green-500 text-white': step.completed,
                    }
                  ]"
                >
                  <BaseIcon v-if="step.completed" name="CheckIcon" class="w-4 h-4" />
                  <span v-else>{{ step.number }}</span>
                </span>
                <div class="flex-1">
                  <div class="font-medium">{{ step.title }}</div>
                  <div class="text-xs text-gray-500 mt-1">{{ step.description }}</div>
                </div>
              </div>
            </nav>

            <!-- Import Statistics (if available) -->
            <div v-if="importStore.validationResults" class="pt-4 border-t border-gray-200">
              <h4 class="text-sm font-medium text-gray-900 mb-3">
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
          </div>
        </BaseCard>
      </div>

      <!-- Main Content -->
      <div class="col-span-9">
        <BaseCard>
          <BaseWizard
            :current-step="importStore.currentStep - 1"
            :steps="4"
            wizard-steps-container-class="min-h-64 p-6"
          >
            <!-- Step 1: Upload -->
            <BaseWizardStep v-show="importStore.currentStep === 1">
              <Step1Upload />
            </BaseWizardStep>

            <!-- Step 2: Match Columns -->
            <BaseWizardStep v-show="importStore.currentStep === 2">
              <Step2Mapping />
            </BaseWizardStep>

            <!-- Step 3: Review -->
            <BaseWizardStep v-show="importStore.currentStep === 3">
              <Step3Validation @go-back="importStore.previousStep()" />
            </BaseWizardStep>

            <!-- Step 4: Import -->
            <BaseWizardStep v-show="importStore.currentStep === 4">
              <Step4Commit />
            </BaseWizardStep>
          </BaseWizard>

          <!-- Navigation Footer -->
          <template #footer>
            <div class="flex justify-between">
              <BaseButton
                v-if="importStore.currentStep > 1"
                variant="secondary"
                @click="importStore.previousStep()"
                :disabled="importStore.isLoading || importStore.isUploading || importStore.isValidating || importStore.isCommitting"
              >
                {{ $t('general.previous') }}
              </BaseButton>
              <div v-else></div>

              <div class="flex space-x-3">
                <BaseButton
                  v-if="importStore.currentStep < 4"
                  variant="primary"
                  @click="handleNext"
                  :disabled="!importStore.canProceed || importStore.isLoading"
                  :loading="isProcessingNext"
                >
                  {{ $t('general.next') }}
                </BaseButton>

                <BaseButton
                  v-if="importStore.currentStep === 4 && importStore.commitStatus !== 'completed'"
                  variant="primary"
                  @click="commitImport"
                  :disabled="!importStore.isStep3Valid || importStore.isCommitting"
                  :loading="importStore.isCommitting"
                >
                  {{ $t('imports.commit_import') }}
                </BaseButton>

                <BaseButton
                  v-if="importStore.currentStep === 4 && importStore.commitStatus === 'completed'"
                  variant="primary"
                  @click="finishImport"
                >
                  {{ $t('imports.finish') }}
                </BaseButton>
              </div>
            </div>
          </template>
        </BaseCard>
      </div>
    </div>

    <!-- Help Slide-Over Panel -->
    <Teleport to="body">
      <Transition name="help-panel">
        <div v-if="showHelp" class="fixed inset-0 z-50 flex justify-end" @click.self="showHelp = false">
          <!-- Backdrop -->
          <div class="fixed inset-0 bg-black/30 transition-opacity" @click="showHelp = false"></div>

          <!-- Panel -->
          <div class="relative w-full max-w-md bg-white shadow-xl overflow-y-auto">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">{{ $t('imports.help_guide') }}</h3>
              <button
                class="text-gray-400 hover:text-gray-600"
                @click="showHelp = false"
              >
                <BaseIcon name="XMarkIcon" class="w-5 h-5" />
              </button>
            </div>
            <div class="p-4">
              <HelpGuidePanel :current-step="importStore.currentStep" @close="showHelp = false" />
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Interactive Tour -->
    <InteractiveTour
      v-model:isActive="isTourActive"
      :wizard-step="importStore.currentStep"
      @tourComplete="handleTourComplete"
    />

    <!-- Cancel Confirmation Dialog -->
    <BaseModal :show="showCancelDialog" :title="$t('imports.cancel_import')" @close="showCancelDialog = false">
      <div class="text-sm text-gray-500">
        {{ $t('imports.cancel_import_confirmation') }}
      </div>

      <template #footer>
        <div class="flex justify-end space-x-3">
          <BaseButton variant="secondary" @click="showCancelDialog = false">
            {{ $t('general.no') }}
          </BaseButton>
          <BaseButton variant="danger" @click="cancelImport" :loading="isCancelling">
            {{ $t('general.yes') }}
          </BaseButton>
        </div>
      </template>
    </BaseModal>

    <!-- Error Alert -->
    <BaseErrorAlert v-if="importStore.hasErrors" class="mt-6">
      <ul class="list-disc list-inside space-y-1">
        <li v-for="(error, field) in importStore.errors" :key="field">
          {{ error }}
        </li>
      </ul>
    </BaseErrorAlert>
  </BasePage>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'

// Components
import BasePage from '@/scripts/components/base/BasePage.vue'
import BasePageHeader from '@/scripts/components/base/BasePageHeader.vue'
import BaseCard from '@/scripts/components/base/BaseCard.vue'
import BaseButton from '@/scripts/components/base/BaseButton.vue'
import BaseWizard from '@/scripts/components/base/BaseWizard.vue'
import BaseWizardStep from '@/scripts/components/base/BaseWizardStep.vue'
import BaseModal from '@/scripts/components/base/BaseModal.vue'
import BaseErrorAlert from '@/scripts/components/base/BaseErrorAlert.vue'
import BaseIcon from '@/scripts/components/base/BaseIcon.vue'

// Step Components
import Step1Upload from './components/Step1Upload.vue'
import Step2Mapping from './components/Step2Mapping.vue'
import Step3Validation from './components/Step3Validation.vue'
import Step4Commit from './components/Step4Commit.vue'

// Help Components
import HelpGuidePanel from './components/HelpGuidePanel.vue'
import InteractiveTour from './components/InteractiveTour.vue'

// Store
import { useImportStore } from '@/scripts/admin/stores/import'
import { useNotificationStore } from '@/scripts/stores/notification'

const { t } = useI18n()
const router = useRouter()
const importStore = useImportStore()
const notificationStore = useNotificationStore()

// Local state
const isProcessingNext = ref(false)
const showCancelDialog = ref(false)
const isCancelling = ref(false)
const showHelp = ref(false)
const isTourActive = ref(false)

// Computed
const steps = computed(() => [
  {
    number: 1,
    title: t('imports.upload_file'),
    description: t('imports.upload_file_description'),
    completed: importStore.currentStep > 1 && importStore.isStep1Valid,
    accessible: true,
  },
  {
    number: 2,
    title: t('imports.match_columns') || 'Match Columns',
    description: t('imports.match_columns_description') || t('imports.map_fields_description'),
    completed: importStore.currentStep > 2 && importStore.isStep2Valid,
    accessible: importStore.isStep1Valid,
  },
  {
    number: 3,
    title: t('imports.review') || 'Review',
    description: t('imports.review_description') || t('imports.validate_data_description'),
    completed: importStore.currentStep > 3 && importStore.isStep3Valid,
    accessible: importStore.isStep2Valid,
  },
  {
    number: 4,
    title: t('imports.import') || 'Import',
    description: t('imports.import_description') || t('imports.commit_import_description'),
    completed: importStore.isStep4Valid,
    accessible: importStore.isStep3Valid,
  },
])

/**
 * Check whether all required fields for the detected import type
 * are already covered by the auto-mapping suggestions.
 */
const allRequiredFieldsMapped = () => {
  const importType = importStore.importJob?.type || 'customers'
  const requiredFieldsByType = {
    customers: ['customer_name', 'customer_email'],
    invoices: ['invoice_number', 'customer_name', 'invoice_date', 'total_amount'],
    items: ['item_name', 'unit_price'],
    payments: ['payment_date', 'payment_amount'],
    expenses: ['expense_date', 'payment_amount'],
  }

  const requiredFields = requiredFieldsByType[importType] || []
  const mappedTargets = Object.values(importStore.fieldMappings)

  return requiredFields.every(field => mappedTargets.includes(field))
}

// Methods
const goToStep = (stepNumber) => {
  const step = steps.value.find(s => s.number === stepNumber)
  if (step && step.accessible && !importStore.isLoading) {
    importStore.goToStep(stepNumber)
  }
}

const handleNext = async () => {
  isProcessingNext.value = true

  try {
    switch (importStore.currentStep) {
      case 1:
        // Auto-detect fields when moving from upload to mapping
        await importStore.detectFields()

        // Auto-skip step 2 if mapping confidence is high and all required fields are mapped
        if (importStore.autoMappingConfidence >= 0.7 && allRequiredFieldsMapped()) {
          // Save the auto-mapping so step 3 (validation) has data
          await importStore.saveMapping()

          notificationStore.showNotification({
            type: 'success',
            message: t('imports.fields_auto_mapped') || 'Fields auto-mapped successfully',
          })

          // Jump directly to step 3 (Review), skipping step 2
          importStore.goToStep(3)
          return
        }
        break

      case 2:
        // Save mapping when moving from mapping to validation
        await importStore.saveMapping()
        break
    }

    importStore.nextStep()

  } catch (error) {
    console.error('Error proceeding to next step:', error)
    // Error handling is done in the store
  } finally {
    isProcessingNext.value = false
  }
}

const commitImport = async () => {
  try {
    await importStore.commitImport()
  } catch (error) {
    console.error('Error committing import:', error)
  }
}

const confirmCancel = () => {
  showCancelDialog.value = true
}

const cancelImport = async () => {
  isCancelling.value = true

  try {
    await importStore.cancelImport()
    router.push('/admin/dashboard')
  } catch (error) {
    console.error('Error cancelling import:', error)
  } finally {
    isCancelling.value = false
    showCancelDialog.value = false
  }
}

const finishImport = () => {
  router.push('/admin/dashboard')
}

const startTour = () => {
  isTourActive.value = true
}

const handleTourComplete = () => {
  // Tour completion handled silently
}

// Lifecycle
onMounted(() => {
  // Reset store state when component mounts
  importStore.resetState()
})

onUnmounted(() => {
  // Stop any active polling when component unmounts
  importStore.stopProgressPolling()
})
// CLAUDE-CHECKPOINT
</script>

<style scoped>
.help-panel-enter-active,
.help-panel-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.help-panel-enter-active > div:last-child,
.help-panel-leave-active > div:last-child {
  transition: transform 0.2s ease;
}
.help-panel-enter-from,
.help-panel-leave-to {
  opacity: 0;
}
.help-panel-enter-from > div:last-child,
.help-panel-leave-to > div:last-child {
  transform: translateX(100%);
}
</style>