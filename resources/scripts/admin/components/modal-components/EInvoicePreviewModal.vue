<template>
  <BaseModal
    :show="modalActive"
    @close="closeModal"
  >
    <template #header>
      <div class="flex justify-between w-full">
        {{ modalTitle }}
        <BaseIcon
          name="XMarkIcon"
          class="w-6 h-6 text-gray-500 cursor-pointer"
          @click="closeModal"
        />
      </div>
    </template>

    <div class="p-6 space-y-6">
      <!-- Header: Invoice Number, Date, Direction -->
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-lg font-semibold text-gray-900">
            {{ invoice.portal_inbox_id || invoice.invoice_number || '-' }}
          </h3>
          <p class="text-sm text-gray-500 mt-1">
            {{ formatDateTime(invoice.received_at || invoice.invoice_date || invoice.created_at) }}
          </p>
        </div>
        <span
          :class="directionBadgeClasses"
          class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium"
        >
          {{ directionLabel }}
        </span>
      </div>

      <!-- Sender / Receiver Info -->
      <div class="bg-gray-50 rounded-lg p-4">
        <h4 class="text-sm font-semibold text-gray-700 mb-2">
          {{ isInbound ? $t('e_invoice.sender') : $t('e_invoice.receiver') }}
        </h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <p class="text-xs text-gray-500">{{ $t('e_invoice.sender_name') }}</p>
            <p class="text-sm font-medium text-gray-900">
              {{ isInbound
                ? (invoice.sender_name || $t('e_invoice.unknown_sender'))
                : (invoice.receiver_name || '-')
              }}
            </p>
          </div>
          <div>
            <p class="text-xs text-gray-500">{{ $t('e_invoice.sender_vat') }}</p>
            <p class="text-sm font-medium text-gray-900">
              {{ isInbound
                ? (invoice.sender_vat_id || '-')
                : (invoice.receiver_vat_id || '-')
              }}
            </p>
          </div>
        </div>
      </div>

      <!-- Status Badge -->
      <div class="flex items-center space-x-3">
        <span class="text-sm font-medium text-gray-700">{{ $t('e_invoice.status') }}:</span>
        <span
          :class="statusBadgeClasses"
          class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
        >
          {{ statusLabel }}
        </span>
      </div>

      <!-- Amounts -->
      <div
        v-if="invoice.total_amount || invoice.vat_amount"
        class="bg-gray-50 rounded-lg p-4"
      >
        <h4 class="text-sm font-semibold text-gray-700 mb-2">
          {{ $t('e_invoice.amounts') }}
        </h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div v-if="invoice.total_amount !== undefined && invoice.total_amount !== null">
            <p class="text-xs text-gray-500">{{ $t('e_invoice.total_amount') }}</p>
            <p class="text-sm font-semibold text-gray-900">
              {{ formatAmount(invoice.total_amount) }}
              <span v-if="invoice.currency" class="text-xs text-gray-500 ml-1">
                {{ invoice.currency }}
              </span>
            </p>
          </div>
          <div v-if="invoice.vat_amount !== undefined && invoice.vat_amount !== null">
            <p class="text-xs text-gray-500">{{ $t('e_invoice.vat_amount') }}</p>
            <p class="text-sm font-semibold text-gray-900">
              {{ formatAmount(invoice.vat_amount) }}
              <span v-if="invoice.currency" class="text-xs text-gray-500 ml-1">
                {{ invoice.currency }}
              </span>
            </p>
          </div>
        </div>
      </div>

      <!-- UBL XML Collapsible Section -->
      <div v-if="xmlContent" class="border border-gray-200 rounded-lg">
        <button
          type="button"
          class="flex items-center justify-between w-full px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
          @click="toggleXml"
        >
          <span class="flex items-center">
            <BaseIcon name="CodeBracketIcon" class="h-4 w-4 mr-2 text-gray-400" />
            {{ $t('e_invoice.raw_xml') }}
          </span>
          <BaseIcon
            :name="showXml ? 'ChevronUpIcon' : 'ChevronDownIcon'"
            class="h-4 w-4 text-gray-400"
          />
        </button>
        <div
          v-if="showXml"
          class="border-t border-gray-200"
        >
          <pre
            class="p-4 text-xs font-mono text-gray-800 bg-gray-50 overflow-auto max-h-96 whitespace-pre-wrap break-words"
          ><code>{{ xmlContent }}</code></pre>
        </div>
      </div>

      <!-- Loading XML State -->
      <div
        v-if="isLoadingXml"
        class="flex justify-center items-center py-6"
      >
        <LoadingIcon class="h-6 w-6 animate-spin text-primary-500" />
        <span class="ml-2 text-sm text-gray-500">{{ $t('e_invoice.loading_xml') }}</span>
      </div>
    </div>

    <!-- Footer Actions -->
    <div class="z-0 flex justify-between items-center p-4 border-t border-gray-200 border-solid">
      <div class="flex space-x-2">
        <!-- Accept / Reject for reviewable inbound invoices -->
        <template v-if="isInbound && canReview">
          <BaseButton
            variant="primary"
            size="sm"
            :disabled="isAccepting"
            @click="handleAccept"
          >
            <LoadingIcon v-if="isAccepting" class="h-4 mr-1 animate-spin" />
            <BaseIcon v-else name="CheckCircleIcon" class="h-4 mr-1" />
            {{ $t('e_invoice.accept') }}
          </BaseButton>
          <BaseButton
            variant="danger-outline"
            size="sm"
            @click="handleReject"
          >
            <BaseIcon name="XCircleIcon" class="h-4 mr-1" />
            {{ $t('e_invoice.reject') }}
          </BaseButton>
        </template>
      </div>
      <BaseButton
        variant="primary-outline"
        type="button"
        @click="closeModal"
      >
        {{ $t('general.close') }}
      </BaseButton>
    </div>
  </BaseModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useModalStore } from '@/scripts/stores/modal'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useI18n } from 'vue-i18n'
import { handleError } from '@/scripts/helpers/error-handling'
import LoadingIcon from '@/scripts/components/icons/LoadingIcon.vue'
import axios from 'axios'
import moment from 'moment'

const modalStore = useModalStore()
const notificationStore = useNotificationStore()
const { t } = useI18n()

const emit = defineEmits(['accepted', 'rejected'])

// State
const showXml = ref(false)
const xmlContent = ref('')
const isLoadingXml = ref(false)
const isAccepting = ref(false)

/**
 * Whether the modal is active.
 */
const modalActive = computed(() => {
  return modalStore.active && modalStore.componentName === 'EInvoicePreviewModal'
})

/**
 * Modal title from store or default.
 */
const modalTitle = computed(() => {
  return modalStore.title || t('e_invoice.preview_title')
})

/**
 * The e-invoice record from modal data.
 */
const invoice = computed(() => {
  return modalStore.data || {}
})

/**
 * Whether this is an inbound e-invoice.
 */
const isInbound = computed(() => {
  return invoice.value.direction === 'inbound' ||
    !!invoice.value.sender_name ||
    !!invoice.value.sender_vat_id
})

/**
 * Whether the invoice can be reviewed (accepted/rejected).
 */
const canReview = computed(() => {
  return ['RECEIVED', 'UNDER_REVIEW'].includes(invoice.value.status)
})

/**
 * Direction badge label.
 */
const directionLabel = computed(() => {
  return isInbound.value
    ? t('e_invoice.direction_inbound')
    : t('e_invoice.direction_outbound')
})

/**
 * Direction badge CSS classes.
 */
const directionBadgeClasses = computed(() => {
  return isInbound.value
    ? 'bg-indigo-100 text-indigo-800'
    : 'bg-emerald-100 text-emerald-800'
})

/**
 * Status badge CSS classes.
 */
const statusBadgeClasses = computed(() => {
  const classes = {
    RECEIVED: 'bg-blue-100 text-blue-800',
    UNDER_REVIEW: 'bg-yellow-100 text-yellow-800',
    ACCEPTED_INCOMING: 'bg-green-100 text-green-800',
    REJECTED_INCOMING: 'bg-red-100 text-red-800',
    DRAFT: 'bg-gray-100 text-gray-800',
    SIGNED: 'bg-purple-100 text-purple-800',
    SUBMITTED: 'bg-blue-100 text-blue-800',
    SENT: 'bg-blue-100 text-blue-800',
    DELIVERED: 'bg-green-100 text-green-800',
    ACCEPTED: 'bg-green-100 text-green-800',
    REJECTED: 'bg-red-100 text-red-800',
  }
  return classes[invoice.value.status] || 'bg-gray-100 text-gray-800'
})

/**
 * Human-readable status label.
 */
const statusLabel = computed(() => {
  const labels = {
    RECEIVED: t('e_invoice.status_received'),
    UNDER_REVIEW: t('e_invoice.status_under_review'),
    ACCEPTED_INCOMING: t('e_invoice.status_accepted'),
    REJECTED_INCOMING: t('e_invoice.status_rejected'),
    DRAFT: t('e_invoice.status_draft'),
    SIGNED: t('e_invoice.status_signed'),
    SUBMITTED: t('e_invoice.status_submitted'),
    SENT: t('e_invoice.status_sent'),
    DELIVERED: t('e_invoice.status_delivered'),
    ACCEPTED: t('e_invoice.status_accepted'),
    REJECTED: t('e_invoice.status_rejected'),
  }
  return labels[invoice.value.status] || invoice.value.status || '-'
})

/**
 * Format a datetime value for display.
 */
function formatDateTime(date) {
  if (!date) return '-'
  return moment(date).format('DD.MM.YYYY HH:mm')
}

/**
 * Format an amount with two decimal places.
 */
function formatAmount(amount) {
  if (amount === null || amount === undefined) return '-'
  const num = parseFloat(amount)
  if (isNaN(num)) return amount
  return num.toLocaleString('mk-MK', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}

/**
 * Toggle the raw XML display section.
 */
function toggleXml() {
  showXml.value = !showXml.value
}

/**
 * Load the full e-invoice data with UBL XML from the API.
 */
async function loadXmlContent() {
  if (!invoice.value.id) return

  isLoadingXml.value = true
  try {
    const response = await axios.get(`/e-invoices/incoming/${invoice.value.id}`)
    const data = response.data.data

    if (data && data.ubl_xml) {
      xmlContent.value = data.ubl_xml
    } else {
      xmlContent.value = ''
    }
  } catch (err) {
    handleError(err)
    xmlContent.value = ''
  } finally {
    isLoadingXml.value = false
  }
}

/**
 * Accept this incoming e-invoice.
 */
async function handleAccept() {
  isAccepting.value = true
  try {
    await axios.post(`/e-invoices/incoming/${invoice.value.id}/accept`)
    notificationStore.showNotification({
      type: 'success',
      message: t('e_invoice.accepted_successfully'),
    })
    emit('accepted', invoice.value.id)
    if (modalStore.refreshData) {
      modalStore.refreshData()
    }
    closeModal()
  } catch (err) {
    handleError(err)
  } finally {
    isAccepting.value = false
  }
}

/**
 * Reject this incoming e-invoice -- close preview and let parent handle rejection modal.
 */
function handleReject() {
  emit('rejected', invoice.value)
  closeModal()
}

/**
 * Close the modal and reset state.
 */
function closeModal() {
  modalStore.closeModal()
  setTimeout(() => {
    showXml.value = false
    xmlContent.value = ''
    isLoadingXml.value = false
  }, 300)
}

/**
 * Watch for modal opening and load XML content.
 */
watch(modalActive, (newValue, oldValue) => {
  if (newValue && !oldValue) {
    showXml.value = false
    xmlContent.value = ''
    loadXmlContent()
  }
})
</script>
// CLAUDE-CHECKPOINT
