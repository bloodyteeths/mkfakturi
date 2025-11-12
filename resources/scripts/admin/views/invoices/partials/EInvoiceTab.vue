<template>
  <div class="py-6">
    <!-- Loading State -->
    <div v-if="isLoading" class="flex justify-center items-center py-12">
      <LoadingIcon class="h-8 w-8 animate-spin text-primary-500" />
    </div>

    <div v-else>
      <!-- No E-Invoice Yet -->
      <div v-if="!eInvoice" class="text-center py-8">
        <BaseCard class="max-w-2xl mx-auto">
          <div class="p-8">
            <BaseIcon name="DocumentTextIcon" class="h-16 w-16 mx-auto text-gray-400 mb-4" />
            <h3 class="text-lg font-medium text-gray-900 mb-2">
              {{ $t('e_invoice.no_einvoice') }}
            </h3>
            <p class="text-sm text-gray-500 mb-6">
              {{ $t('e_invoice.no_einvoice_description') }}
            </p>

            <!-- Generate Button (Only if invoice is SENT) -->
            <BaseButton
              v-if="canGenerate"
              variant="primary"
              :disabled="isGenerating"
              @click="handleGenerate"
            >
              <BaseIcon v-if="!isGenerating" name="DocumentPlusIcon" class="h-5 mr-2" />
              <LoadingIcon v-else class="h-5 mr-2 animate-spin" />
              {{ isGenerating ? $t('e_invoice.generating') : $t('e_invoice.generate') }}
            </BaseButton>

            <BaseAlert v-else variant="info" class="mt-4">
              {{ $t('e_invoice.invoice_must_be_sent') }}
            </BaseAlert>
          </div>
        </BaseCard>
      </div>

      <!-- E-Invoice Exists -->
      <div v-else>
        <!-- Status Section -->
        <BaseCard class="mb-6">
          <div class="p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-medium text-gray-900">
                {{ $t('e_invoice.status_title') }}
              </h3>
              <EInvoiceStatusBadge :status="eInvoice.status" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
              <div>
                <span class="text-gray-500">{{ $t('e_invoice.created_at') }}:</span>
                <span class="ml-2 font-medium">{{ formatDateTime(eInvoice.created_at) }}</span>
              </div>
              <div v-if="eInvoice.signed_at">
                <span class="text-gray-500">{{ $t('e_invoice.signed_at') }}:</span>
                <span class="ml-2 font-medium">{{ formatDateTime(eInvoice.signed_at) }}</span>
              </div>
              <div v-if="eInvoice.submitted_at">
                <span class="text-gray-500">{{ $t('e_invoice.submitted_at') }}:</span>
                <span class="ml-2 font-medium">{{ formatDateTime(eInvoice.submitted_at) }}</span>
              </div>
            </div>
          </div>
        </BaseCard>

        <!-- Actions Section -->
        <BaseCard class="mb-6">
          <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
              {{ $t('e_invoice.actions') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
              <!-- Sign Button -->
              <BaseButton
                v-if="canSign"
                variant="primary"
                @click="handleSign"
              >
                <BaseIcon name="ShieldCheckIcon" class="h-5 mr-2" />
                {{ $t('e_invoice.sign') }}
              </BaseButton>

              <!-- Simulate/Validate Button -->
              <BaseButton
                v-if="canSimulate"
                variant="primary-outline"
                :disabled="isSimulating"
                @click="handleSimulate"
              >
                <BaseIcon v-if="!isSimulating" name="CheckCircleIcon" class="h-5 mr-2" />
                <LoadingIcon v-else class="h-5 mr-2 animate-spin" />
                {{ isSimulating ? $t('e_invoice.validating') : $t('e_invoice.simulate') }}
              </BaseButton>

              <!-- Submit Button -->
              <BaseButton
                v-if="canSubmit"
                variant="primary"
                :disabled="isSubmitting"
                @click="handleSubmit"
              >
                <BaseIcon v-if="!isSubmitting" name="PaperAirplaneIcon" class="h-5 mr-2" />
                <LoadingIcon v-else class="h-5 mr-2 animate-spin" />
                {{ isSubmitting ? $t('e_invoice.submitting') : $t('e_invoice.submit') }}
              </BaseButton>

              <!-- Download XML Button -->
              <BaseButton
                v-if="canDownload"
                variant="primary-outline"
                @click="handleDownload"
              >
                <BaseIcon name="ArrowDownTrayIcon" class="h-5 mr-2" />
                {{ $t('e_invoice.download_xml') }}
              </BaseButton>
            </div>
          </div>
        </BaseCard>

        <!-- Submission History -->
        <BaseCard v-if="submissions.length > 0">
          <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
              {{ $t('e_invoice.submissions') }}
            </h3>

            <BaseTable class="mt-4">
              <template #header>
                <BaseTableHeader>
                  <BaseTableHeaderCell>{{ $t('e_invoice.submission_date') }}</BaseTableHeaderCell>
                  <BaseTableHeaderCell>{{ $t('e_invoice.submission_status') }}</BaseTableHeaderCell>
                  <BaseTableHeaderCell>{{ $t('e_invoice.response') }}</BaseTableHeaderCell>
                  <BaseTableHeaderCell>{{ $t('general.actions') }}</BaseTableHeaderCell>
                </BaseTableHeader>
              </template>

              <template #body>
                <BaseTableRow v-for="submission in submissions" :key="submission.id">
                  <BaseTableCell>
                    {{ formatDateTime(submission.created_at) }}
                  </BaseTableCell>
                  <BaseTableCell>
                    <BaseBadge :variant="getSubmissionStatusVariant(submission.status)">
                      {{ submission.status }}
                    </BaseBadge>
                  </BaseTableCell>
                  <BaseTableCell>
                    <div v-if="submission.response_message" class="text-sm">
                      {{ submission.response_message }}
                    </div>
                    <div v-if="submission.portal_id" class="text-xs text-gray-500 mt-1">
                      Portal ID: {{ submission.portal_id }}
                    </div>
                  </BaseTableCell>
                  <BaseTableCell>
                    <BaseButton
                      v-if="submission.status === 'FAILED'"
                      variant="danger-outline"
                      size="sm"
                      @click="handleResubmit(submission.id)"
                    >
                      <BaseIcon name="ArrowPathIcon" class="h-4 mr-1" />
                      {{ $t('e_invoice.resubmit') }}
                    </BaseButton>
                  </BaseTableCell>
                </BaseTableRow>
              </template>
            </BaseTable>
          </div>
        </BaseCard>

        <!-- No Submissions (shown for non-DRAFT statuses only) -->
        <BaseCard v-else-if="eInvoice.status?.toUpperCase() !== 'DRAFT' && submissions.length === 0">
          <div class="p-6 text-center text-gray-500">
            {{ $t('e_invoice.no_submissions') }}
          </div>
        </BaseCard>
      </div>
    </div>

    <!-- Sign Modal -->
    <EInvoiceSignModal @signed="onSigned" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import moment from 'moment'

import { useEInvoiceStore } from '@/scripts/admin/stores/e-invoice'
import { useModalStore } from '@/scripts/stores/modal'
import { useDialogStore } from '@/scripts/stores/dialog'

import EInvoiceStatusBadge from '@/scripts/admin/components/EInvoiceStatusBadge.vue'
import EInvoiceSignModal from '@/scripts/admin/components/modal-components/EInvoiceSignModal.vue'
import LoadingIcon from '@/scripts/components/icons/LoadingIcon.vue'

const props = defineProps({
  invoice: {
    type: Object,
    required: true,
  },
})

const { t } = useI18n()
const route = useRoute()
const eInvoiceStore = useEInvoiceStore()
const modalStore = useModalStore()
const dialogStore = useDialogStore()

const isLoading = ref(true)
const isGenerating = ref(false)
const isSimulating = ref(false)
const isSubmitting = ref(false)

const eInvoice = computed(() => eInvoiceStore.currentEInvoice)
const submissions = computed(() => eInvoiceStore.submissions || [])

const canGenerate = computed(() => {
  return props.invoice.status === 'SENT' && !eInvoice.value
})

const canSign = computed(() => {
  if (!eInvoice.value) return false
  const status = eInvoice.value.status?.toUpperCase()
  return status === 'DRAFT'
})

const canSimulate = computed(() => {
  if (!eInvoice.value) return false
  const status = eInvoice.value.status?.toUpperCase()
  return status === 'SIGNED'
})

const canSubmit = computed(() => {
  if (!eInvoice.value) return false
  const status = eInvoice.value.status?.toUpperCase()
  return status === 'SIGNED'
})

const canDownload = computed(() => {
  if (!eInvoice.value) return false
  const status = eInvoice.value.status?.toUpperCase()
  return ['SIGNED', 'SUBMITTED', 'ACCEPTED', 'REJECTED'].includes(status)
})

onMounted(async () => {
  await loadEInvoice()
})

// Watch for route changes (when navigating between invoices)
watch(() => route.params.id, async (newId, oldId) => {
  if (newId && newId !== oldId) {
    await loadEInvoice()
  }
})

// Watch for invoice prop changes (when invoice data updates)
watch(() => props.invoice?.id, async (newId, oldId) => {
  if (newId && newId !== oldId) {
    await loadEInvoice()
  }
})

async function loadEInvoice() {
  isLoading.value = true
  try {
    await eInvoiceStore.fetchEInvoiceStatus(route.params.id)
  } catch (error) {
    console.error('Failed to load e-invoice:', error)
  } finally {
    isLoading.value = false
  }
}

async function handleGenerate() {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('e_invoice.confirm_generate'),
      yesLabel: t('general.yes'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'md',
    })
    .then(async (response) => {
      if (response) {
        isGenerating.value = true
        try {
          await eInvoiceStore.generateEInvoice(route.params.id)
        } catch (error) {
          console.error('Generate failed:', error)
        } finally {
          isGenerating.value = false
        }
      }
    })
}

function handleSign() {
  modalStore.openModal({
    title: t('e_invoice.sign'),
    componentName: 'EInvoiceSignModal',
    id: eInvoice.value.id,
  })
}

async function onSigned() {
  await loadEInvoice()
}

async function handleSimulate() {
  isSimulating.value = true
  try {
    await eInvoiceStore.simulateSubmission(eInvoice.value.id)
  } catch (error) {
    console.error('Simulation failed:', error)
  } finally {
    isSimulating.value = false
  }
}

async function handleSubmit() {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('e_invoice.confirm_submit'),
      yesLabel: t('general.yes'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'md',
    })
    .then(async (response) => {
      if (response) {
        isSubmitting.value = true
        try {
          await eInvoiceStore.submitEInvoice(eInvoice.value.id)
        } catch (error) {
          console.error('Submit failed:', error)
        } finally {
          isSubmitting.value = false
        }
      }
    })
}

async function handleDownload() {
  try {
    await eInvoiceStore.downloadXml(eInvoice.value.id)
  } catch (error) {
    console.error('Download failed:', error)
  }
}

async function handleResubmit(submissionId) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('e_invoice.confirm_resubmit'),
      yesLabel: t('general.yes'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'md',
    })
    .then(async (response) => {
      if (response) {
        try {
          await eInvoiceStore.resubmit(submissionId)
          await loadEInvoice()
        } catch (error) {
          console.error('Resubmit failed:', error)
        }
      }
    })
}

function formatDateTime(date) {
  return moment(date).format('DD.MM.YYYY HH:mm')
}

function getSubmissionStatusVariant(status) {
  const variants = {
    PENDING: 'yellow',
    SUCCESS: 'green',
    FAILED: 'red',
  }
  return variants[status] || 'gray'
}
</script>
// CLAUDE-CHECKPOINT
