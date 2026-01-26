<template>
  <BasePage>
    <BasePageHeader :title="$t('partner.accounting.journal_export')">
      <template #actions>
        <BaseButton
          v-if="currentStep > 1"
          variant="gray"
          @click="previousStep"
          :aria-label="$t('general.back')"
        >
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="ChevronLeftIcon" />
          </template>
          {{ $t('general.back') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Wizard Steps Indicator -->
    <div class="mb-8">
      <nav aria-label="Progress">
        <ol role="list" class="flex items-center">
          <li
            v-for="(step, index) in steps"
            :key="index"
            :class="[
              index !== steps.length - 1 ? 'pr-8 sm:pr-20' : '',
              'relative',
            ]"
          >
            <!-- Connector Line -->
            <div
              v-if="index !== steps.length - 1"
              class="absolute inset-0 flex items-center"
              aria-hidden="true"
            >
              <div
                :class="[
                  currentStep > index + 1 ? 'bg-primary-600' : 'bg-gray-200',
                  'h-0.5 w-full',
                ]"
              />
            </div>

            <!-- Step Circle -->
            <button
              type="button"
              class="relative flex h-8 w-8 items-center justify-center rounded-full"
              :class="[
                currentStep > index + 1
                  ? 'bg-primary-600 hover:bg-primary-900'
                  : currentStep === index + 1
                  ? 'border-2 border-primary-600 bg-white'
                  : 'border-2 border-gray-300 bg-white hover:border-gray-400',
              ]"
              :disabled="!canGoToStep(index + 1)"
              :aria-label="step.name"
              @click="goToStep(index + 1)"
            >
              <span
                v-if="currentStep > index + 1"
                class="flex h-full w-full items-center justify-center"
              >
                <BaseIcon name="CheckIcon" class="h-5 w-5 text-white" />
              </span>
              <span
                v-else
                :class="[
                  currentStep === index + 1
                    ? 'text-primary-600'
                    : 'text-gray-500',
                  'text-sm font-medium',
                ]"
              >
                {{ index + 1 }}
              </span>
            </button>
            <span
              class="absolute mt-10 w-max text-xs font-medium"
              :class="[
                currentStep === index + 1 ? 'text-primary-600' : 'text-gray-500',
              ]"
            >
              {{ step.name }}
            </span>
          </li>
        </ol>
      </nav>
    </div>

    <!-- Step 1: Select Scope -->
    <div v-if="currentStep === 1" class="mx-auto max-w-3xl">
      <div class="rounded-lg bg-white p-6 shadow">
        <h3 class="mb-4 text-lg font-medium text-gray-900">
          {{ $t('partner.accounting.select_export_scope') }}
        </h3>

        <div class="grid gap-6">
          <!-- Company Selector -->
          <BaseInputGroup :label="$t('partner.select_company')" required>
            <BaseMultiselect
              v-model="exportForm.company_id"
              :options="companies"
              :searchable="true"
              track-by="id"
              label="name"
              value-prop="id"
              :placeholder="$t('partner.select_company_placeholder')"
              @update:model-value="onCompanyChange"
              aria-label="Select company"
            />
          </BaseInputGroup>

          <!-- Date Range -->
          <div class="grid grid-cols-2 gap-4">
            <BaseInputGroup :label="$t('general.from')" required>
              <BaseDatePicker
                v-model="exportForm.start_date"
                :calendar-button="true"
                calendar-button-icon="CalendarDaysIcon"
                @update:model-value="onDateChange"
                aria-label="Start date"
              />
            </BaseInputGroup>

            <BaseInputGroup :label="$t('general.to')" required>
              <BaseDatePicker
                v-model="exportForm.end_date"
                :calendar-button="true"
                calendar-button-icon="CalendarDaysIcon"
                @update:model-value="onDateChange"
                aria-label="End date"
              />
            </BaseInputGroup>
          </div>

          <!-- Loading indicator for entries count -->
          <div v-if="isLoadingEntries" class="rounded-md bg-gray-50 p-4 animate-pulse">
            <div class="flex">
              <div class="flex-shrink-0">
                <div class="h-5 w-5 bg-gray-200 rounded"></div>
              </div>
              <div class="ml-3 flex-1">
                <div class="h-4 bg-gray-200 rounded w-48"></div>
              </div>
            </div>
          </div>

          <!-- Entry Count -->
          <div
            v-else-if="entriesCount !== null"
            class="rounded-md bg-blue-50 p-4"
          >
            <div class="flex">
              <div class="flex-shrink-0">
                <BaseIcon name="InformationCircleIcon" class="h-5 w-5 text-blue-400" />
              </div>
              <div class="ml-3">
                <p class="text-sm text-blue-700">
                  {{ $t('partner.accounting.entries_in_range', { count: entriesCount }) }}
                </p>
                <p
                  v-if="unconfirmedCount > 0"
                  class="mt-1 text-sm text-blue-600"
                >
                  {{ $t('partner.accounting.unconfirmed_entries_warning', { count: unconfirmedCount }) }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-6 flex justify-end">
          <BaseButton
            variant="primary"
            :disabled="!canProceedFromStep1"
            @click="nextStep"
          >
            {{ $t('general.next') }}
            <template #right="slotProps">
              <BaseIcon :class="slotProps.class" name="ChevronRightIcon" />
            </template>
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Step 2: Review Entries -->
    <div v-if="currentStep === 2" class="mx-auto max-w-5xl">
      <div class="rounded-lg bg-white p-6 shadow">
        <h3 class="mb-4 text-lg font-medium text-gray-900">
          {{ $t('partner.accounting.review_entries') }}
        </h3>

        <div v-if="unconfirmedCount > 0" class="mb-6">
          <div class="rounded-md bg-yellow-50 p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <BaseIcon name="ExclamationTriangleIcon" class="h-5 w-5 text-yellow-400" />
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">
                  {{ $t('partner.accounting.unconfirmed_entries_title') }}
                </h3>
                <div class="mt-2 text-sm text-yellow-700">
                  <p>{{ $t('partner.accounting.unconfirmed_entries_message', { count: unconfirmedCount }) }}</p>
                </div>
                <div class="mt-4 flex gap-3">
                  <BaseButton
                    variant="primary"
                    size="sm"
                    :loading="isConfirmingAll"
                    @click="confirmAllInRange"
                  >
                    {{ $t('partner.accounting.confirm_all_in_range') }}
                  </BaseButton>
                  <BaseButton
                    variant="gray"
                    size="sm"
                    @click="skipReview"
                  >
                    {{ $t('partner.accounting.skip_review') }}
                  </BaseButton>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="mb-6">
          <div class="rounded-md bg-green-50 p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <BaseIcon name="CheckCircleIcon" class="h-5 w-5 text-green-400" />
              </div>
              <div class="ml-3">
                <p class="text-sm text-green-700">
                  {{ $t('partner.accounting.all_entries_confirmed') }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-6 flex justify-between">
          <BaseButton
            variant="gray"
            @click="previousStep"
          >
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="ChevronLeftIcon" />
            </template>
            {{ $t('general.back') }}
          </BaseButton>
          <BaseButton
            variant="primary"
            @click="nextStep"
          >
            {{ $t('general.next') }}
            <template #right="slotProps">
              <BaseIcon :class="slotProps.class" name="ChevronRightIcon" />
            </template>
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Step 3: Choose Format -->
    <div v-if="currentStep === 3" class="mx-auto max-w-3xl">
      <div class="rounded-lg bg-white p-6 shadow">
        <h3 class="mb-4 text-lg font-medium text-gray-900">
          {{ $t('partner.accounting.choose_export_format') }}
        </h3>

        <div class="space-y-4" role="radiogroup" aria-label="Export format">
          <!-- Pantheon XML -->
          <label
            class="relative flex cursor-pointer rounded-lg border p-4 hover:bg-gray-50"
            :class="[
              exportForm.format === 'pantheon'
                ? 'border-primary-600 bg-primary-50'
                : 'border-gray-300',
            ]"
          >
            <input
              v-model="exportForm.format"
              type="radio"
              value="pantheon"
              class="sr-only"
              aria-label="Pantheon XML format"
            />
            <span class="flex flex-1">
              <span class="flex flex-col">
                <span class="block text-sm font-medium text-gray-900">
                  {{ $t('partner.accounting.format_pantheon_xml') }}
                </span>
                <span class="mt-1 flex items-center text-sm text-gray-500">
                  {{ $t('partner.accounting.format_pantheon_xml_description') }}
                </span>
              </span>
            </span>
            <BaseIcon
              v-if="exportForm.format === 'pantheon'"
              name="CheckCircleIcon"
              class="h-5 w-5 text-primary-600"
            />
          </label>

          <!-- Zonel CSV -->
          <label
            class="relative flex cursor-pointer rounded-lg border p-4 hover:bg-gray-50"
            :class="[
              exportForm.format === 'zonel'
                ? 'border-primary-600 bg-primary-50'
                : 'border-gray-300',
            ]"
          >
            <input
              v-model="exportForm.format"
              type="radio"
              value="zonel"
              class="sr-only"
              aria-label="Zonel CSV format"
            />
            <span class="flex flex-1">
              <span class="flex flex-col">
                <span class="block text-sm font-medium text-gray-900">
                  {{ $t('partner.accounting.format_zonel_csv') }}
                </span>
                <span class="mt-1 flex items-center text-sm text-gray-500">
                  {{ $t('partner.accounting.format_zonel_csv_description') }}
                </span>
              </span>
            </span>
            <BaseIcon
              v-if="exportForm.format === 'zonel'"
              name="CheckCircleIcon"
              class="h-5 w-5 text-primary-600"
            />
          </label>

          <!-- Generic CSV -->
          <label
            class="relative flex cursor-pointer rounded-lg border p-4 hover:bg-gray-50"
            :class="[
              exportForm.format === 'csv'
                ? 'border-primary-600 bg-primary-50'
                : 'border-gray-300',
            ]"
          >
            <input
              v-model="exportForm.format"
              type="radio"
              value="csv"
              class="sr-only"
              aria-label="Generic CSV format"
            />
            <span class="flex flex-1">
              <span class="flex flex-col">
                <span class="block text-sm font-medium text-gray-900">
                  {{ $t('partner.accounting.format_generic_csv') }}
                </span>
                <span class="mt-1 flex items-center text-sm text-gray-500">
                  {{ $t('partner.accounting.format_generic_csv_description') }}
                </span>
              </span>
            </span>
            <BaseIcon
              v-if="exportForm.format === 'csv'"
              name="CheckCircleIcon"
              class="h-5 w-5 text-primary-600"
            />
          </label>
        </div>

        <div class="mt-6 flex justify-between">
          <BaseButton
            variant="gray"
            @click="previousStep"
          >
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="ChevronLeftIcon" />
            </template>
            {{ $t('general.back') }}
          </BaseButton>
          <BaseButton
            variant="primary"
            :disabled="!exportForm.format"
            @click="nextStep"
          >
            {{ $t('general.next') }}
            <template #right="slotProps">
              <BaseIcon :class="slotProps.class" name="ChevronRightIcon" />
            </template>
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Step 4: Export -->
    <div v-if="currentStep === 4" class="mx-auto max-w-3xl">
      <div class="rounded-lg bg-white p-6 shadow">
        <h3 class="mb-4 text-lg font-medium text-gray-900">
          {{ $t('partner.accounting.export_ready') }}
        </h3>

        <!-- Export Summary -->
        <div class="mb-6 rounded-md bg-gray-50 p-4">
          <dl class="space-y-3 text-sm">
            <div class="flex justify-between">
              <dt class="font-medium text-gray-700">{{ $t('partner.select_company') }}:</dt>
              <dd class="text-gray-900">{{ selectedCompanyName }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="font-medium text-gray-700">{{ $t('partner.accounting.date_range') }}:</dt>
              <dd class="text-gray-900">
                {{ formatDate(exportForm.start_date) }} - {{ formatDate(exportForm.end_date) }}
              </dd>
            </div>
            <div class="flex justify-between">
              <dt class="font-medium text-gray-700">{{ $t('partner.accounting.entries_count') }}:</dt>
              <dd class="text-gray-900">{{ entriesCount ?? 0 }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="font-medium text-gray-700">{{ $t('partner.accounting.export_format') }}:</dt>
              <dd class="text-gray-900">{{ getFormatLabel(exportForm.format) }}</dd>
            </div>
          </dl>
        </div>

        <!-- Export Success Message -->
        <div
          v-if="exportSuccess"
          class="mb-6 rounded-md bg-green-50 p-4"
        >
          <div class="flex">
            <div class="flex-shrink-0">
              <BaseIcon name="CheckCircleIcon" class="h-5 w-5 text-green-400" />
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-green-800">
                {{ $t('partner.accounting.export_success') }}
              </h3>
              <div class="mt-2 text-sm text-green-700">
                <p>{{ $t('partner.accounting.export_success_message', { filename: exportedFileName }) }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Export Error Message -->
        <div
          v-if="exportError"
          class="mb-6 rounded-md bg-red-50 p-4"
        >
          <div class="flex">
            <div class="flex-shrink-0">
              <BaseIcon name="ExclamationCircleIcon" class="h-5 w-5 text-red-400" />
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-red-800">
                {{ $t('partner.accounting.export_failed') }}
              </h3>
              <div class="mt-2 text-sm text-red-700">
                <p>{{ exportError }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-6 flex justify-between">
          <BaseButton
            v-if="!exportSuccess"
            variant="gray"
            @click="previousStep"
          >
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="ChevronLeftIcon" />
            </template>
            {{ $t('general.back') }}
          </BaseButton>
          <div v-else />
          <div class="flex gap-3">
            <BaseButton
              v-if="exportSuccess"
              variant="gray"
              @click="resetWizard"
            >
              {{ $t('partner.accounting.export_another') }}
            </BaseButton>
            <BaseButton
              v-else
              variant="primary"
              :loading="isExporting"
              @click="performExport"
            >
              <template #left="slotProps">
                <BaseIcon :class="slotProps.class" name="ArrowDownTrayIcon" />
              </template>
              {{ $t('partner.accounting.download_export') }}
            </BaseButton>
          </div>
        </div>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import { usePartnerAccountingStore } from '@/scripts/admin/stores/partner-accounting'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useNotificationStore } from '@/scripts/stores/notification'
import { debounce } from 'lodash'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const consoleStore = useConsoleStore()
const partnerAccountingStore = usePartnerAccountingStore()
const dialogStore = useDialogStore()
const notificationStore = useNotificationStore()

// State
const currentStep = ref(1)
const entriesCount = ref(null)
const unconfirmedCount = ref(0)
const exportSuccess = ref(false)
const exportError = ref(null)
const exportedFileName = ref('')
const isLoadingEntries = ref(false)
const isConfirmingAll = ref(false)
const isExporting = ref(false)

// AbortController for cancelling requests
let abortController = null

// Get current date in local timezone as YYYY-MM-DD
function getLocalDateString(date = new Date()) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const exportForm = reactive({
  company_id: null,
  start_date: null,
  end_date: null,
  format: 'pantheon',
})

const steps = computed(() => [
  { name: t('partner.accounting.select_scope'), number: 1 },
  { name: t('partner.accounting.review_entries'), number: 2 },
  { name: t('partner.accounting.choose_format'), number: 3 },
  { name: t('partner.accounting.export'), number: 4 },
])

// Computed
const companies = computed(() => {
  return consoleStore.managedCompanies || []
})

const selectedCompanyName = computed(() => {
  if (!exportForm.company_id) return '-'
  const company = companies.value.find((c) => c.id === exportForm.company_id)
  return company?.name || '-'
})

const canProceedFromStep1 = computed(() => {
  return (
    exportForm.company_id &&
    exportForm.start_date &&
    exportForm.end_date &&
    entriesCount.value !== null &&
    !isLoadingEntries.value
  )
})

// Track if coming from review page
const fromReview = ref(false)

// Lifecycle
onMounted(async () => {
  try {
    await consoleStore.fetchCompanies()

    // Check if coming from Journal Review page with pre-filled data
    const query = route.query
    if (query.from_review === 'true' && query.company_id && query.start_date && query.end_date) {
      // Pre-fill form from Review page
      fromReview.value = true
      exportForm.company_id = parseInt(query.company_id)
      exportForm.start_date = query.start_date
      exportForm.end_date = query.end_date

      // Fetch entries count to validate
      await fetchEntriesCount()

      // Skip directly to format selection (step 3)
      // Steps 1 (scope) and 2 (review) already done on Review page
      currentStep.value = 3
    } else {
      // Normal flow - auto-select first company if available
      if (companies.value.length > 0) {
        exportForm.company_id = companies.value[0].id
        await onCompanyChange()
      }
    }
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: t('errors.failed_to_load_companies'),
    })
  }
})

onUnmounted(() => {
  // Cancel any pending requests on unmount
  if (abortController) {
    abortController.abort()
  }
})

// Methods
async function onCompanyChange() {
  entriesCount.value = null
  unconfirmedCount.value = 0

  if (exportForm.company_id && exportForm.start_date && exportForm.end_date) {
    await fetchEntriesCount()
  }
}

// Debounced date change handler
const debouncedFetchEntries = debounce(async () => {
  if (exportForm.company_id && exportForm.start_date && exportForm.end_date) {
    await fetchEntriesCount()
  }
}, 300)

async function onDateChange() {
  debouncedFetchEntries()
}

async function fetchEntriesCount() {
  if (!exportForm.company_id || !exportForm.start_date || !exportForm.end_date) return

  // Cancel previous request if still pending
  if (abortController) {
    abortController.abort()
  }
  abortController = new AbortController()

  isLoadingEntries.value = true

  try {
    await partnerAccountingStore.fetchJournalEntries(
      exportForm.company_id,
      {
        start_date: exportForm.start_date,
        end_date: exportForm.end_date,
      }
    )

    const entries = partnerAccountingStore.journalEntries || []
    entriesCount.value = entries.length
    // Count entries that are unconfirmed (not confirmed) or have low confidence (< 0.8)
    unconfirmedCount.value = entries.filter(
      (e) => !e?.confirmed || e?.confidence < 0.8
    ).length
  } catch (error) {
    // Don't show error for cancelled requests
    if (error.name === 'CanceledError' || error.name === 'AbortError') {
      return
    }

    notificationStore.showNotification({
      type: 'error',
      message: t('errors.failed_to_load_data'),
    })
    entriesCount.value = 0
    unconfirmedCount.value = 0
  } finally {
    isLoadingEntries.value = false
  }
}

function canGoToStep(stepNumber) {
  // Can always go back
  if (stepNumber < currentStep.value) {
    // If coming from review page, don't allow going back to steps 1-2
    if (fromReview.value && stepNumber < 3) {
      return false
    }
    return true
  }
  return false
}

function nextStep() {
  if (currentStep.value < steps.value.length) {
    currentStep.value++
  }
}

function previousStep() {
  // If on step 3 and coming from review, go back to review page
  if (currentStep.value === 3 && fromReview.value) {
    router.push({
      name: 'partner.accounting.review',
    })
    return
  }

  if (currentStep.value > 1) {
    currentStep.value--
  }
}

function goToStep(stepNumber) {
  // Only allow going back, not forward
  if (canGoToStep(stepNumber)) {
    currentStep.value = stepNumber
  }
}

function skipReview() {
  nextStep()
}

async function confirmAllInRange() {
  const confirmed = await dialogStore.openDialog({
    title: t('general.are_you_sure'),
    message: t('partner.accounting.confirm_all_in_range_message', {
      count: unconfirmedCount.value,
    }),
    yesLabel: t('general.ok'),
    noLabel: t('general.cancel'),
    variant: 'primary',
    hideNoButton: false,
    size: 'lg',
  })

  if (!confirmed) return

  isConfirmingAll.value = true

  try {
    // Call the store action to confirm all entries above threshold in the date range
    const result = await partnerAccountingStore.acceptAllSuggestions(
      exportForm.company_id,
      0.8, // Minimum confidence threshold
      exportForm.start_date,
      exportForm.end_date
    )

    // Refresh the entries count to reflect the confirmed state
    await fetchEntriesCount()

    notificationStore.showNotification({
      type: 'success',
      message: t('partner.accounting.entries_confirmed_success'),
    })
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: t('errors.something_went_wrong'),
    })
  } finally {
    isConfirmingAll.value = false
  }
}

async function performExport() {
  if (!exportForm.company_id || !exportForm.start_date || !exportForm.end_date || !exportForm.format) {
    notificationStore.showNotification({
      type: 'error',
      message: t('errors.missing_required_fields'),
    })
    return
  }

  isExporting.value = true
  exportError.value = null

  try {
    const params = {
      start_date: exportForm.start_date,
      end_date: exportForm.end_date,
      format: exportForm.format,
    }

    await partnerAccountingStore.exportJournal(exportForm.company_id, params)

    // Set success state
    exportSuccess.value = true
    const extension = exportForm.format === 'pantheon' ? 'xml' : 'csv'
    exportedFileName.value = `journal_export_${exportForm.format}_${exportForm.start_date}_${exportForm.end_date}.${extension}`

    notificationStore.showNotification({
      type: 'success',
      message: t('partner.accounting.export_success'),
    })
  } catch (error) {
    const errorMessage = error.response?.data?.message || t('errors.export_failed')
    exportError.value = errorMessage
    notificationStore.showNotification({
      type: 'error',
      message: errorMessage,
    })
  } finally {
    isExporting.value = false
  }
}

function resetWizard() {
  currentStep.value = 1
  exportForm.start_date = null
  exportForm.end_date = null
  exportForm.format = 'pantheon'
  entriesCount.value = null
  unconfirmedCount.value = 0
  exportSuccess.value = false
  exportError.value = null
  exportedFileName.value = ''
  fromReview.value = false
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  try {
    const date = new Date(dateStr)
    if (isNaN(date.getTime())) return '-'
    return date.toLocaleDateString(undefined, {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      timeZone: 'UTC',
    })
  } catch {
    return '-'
  }
}

function getFormatLabel(format) {
  switch (format) {
    case 'pantheon':
      return t('partner.accounting.format_pantheon_xml')
    case 'zonel':
      return t('partner.accounting.format_zonel_csv')
    case 'csv':
      return t('partner.accounting.format_generic_csv')
    default:
      return format || '-'
  }
}
</script>

// CLAUDE-CHECKPOINT
