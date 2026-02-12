<template>
  <div class="py-6">
    <!-- Header with Poll Button -->
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-medium text-gray-900">
        {{ $t('e_invoice.incoming_inbox') }}
      </h3>
      <BaseButton
        variant="primary-outline"
        :disabled="isPolling"
        @click="handlePollInbox"
      >
        <LoadingIcon v-if="isPolling" class="h-5 mr-2 animate-spin" />
        <BaseIcon v-else name="ArrowPathIcon" class="h-5 mr-2" />
        {{ isPolling ? $t('e_invoice.polling') : $t('e_invoice.poll_inbox') }}
      </BaseButton>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="flex justify-center items-center py-12">
      <LoadingIcon class="h-8 w-8 animate-spin text-primary-500" />
    </div>

    <!-- Empty State -->
    <div v-else-if="!incomingInvoices.length" class="text-center py-12">
      <BaseCard class="max-w-2xl mx-auto">
        <div class="p-8">
          <BaseIcon name="InboxIcon" class="h-16 w-16 mx-auto text-gray-400 mb-4" />
          <h3 class="text-lg font-medium text-gray-900 mb-2">
            {{ $t('e_invoice.no_incoming') }}
          </h3>
          <p class="text-sm text-gray-500">
            {{ $t('e_invoice.no_incoming_description') }}
          </p>
        </div>
      </BaseCard>
    </div>

    <!-- Inbox Table -->
    <div v-else>
      <BaseCard>
        <div class="p-6">
          <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-300">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">
                    {{ $t('e_invoice.sender') }}
                  </th>
                  <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                    {{ $t('e_invoice.invoice_number') }}
                  </th>
                  <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                    {{ $t('e_invoice.received_date') }}
                  </th>
                  <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                    {{ $t('e_invoice.sender_vat') }}
                  </th>
                  <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                    {{ $t('e_invoice.status') }}
                  </th>
                  <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">
                    {{ $t('general.actions') }}
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 bg-white">
                <tr v-for="einvoice in incomingInvoices" :key="einvoice.id">
                  <!-- Sender -->
                  <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                    {{ einvoice.sender_name || $t('e_invoice.unknown_sender') }}
                  </td>

                  <!-- Invoice Number (from portal_inbox_id) -->
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    {{ einvoice.portal_inbox_id || '-' }}
                  </td>

                  <!-- Received Date -->
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    {{ formatDateTime(einvoice.received_at) }}
                  </td>

                  <!-- Sender VAT -->
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    {{ einvoice.sender_vat_id || '-' }}
                  </td>

                  <!-- Status Badge -->
                  <td class="whitespace-nowrap px-3 py-4 text-sm">
                    <span
                      :class="getStatusBadgeClasses(einvoice.status)"
                      class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                    >
                      {{ getStatusLabel(einvoice.status) }}
                    </span>
                  </td>

                  <!-- Actions -->
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-right">
                    <div class="flex items-center justify-end space-x-2">
                      <!-- View XML -->
                      <BaseButton
                        variant="primary-outline"
                        size="sm"
                        @click="handleViewXml(einvoice)"
                      >
                        <BaseIcon name="DocumentTextIcon" class="h-4 mr-1" />
                        {{ $t('e_invoice.view_xml') }}
                      </BaseButton>

                      <!-- Accept -->
                      <BaseButton
                        v-if="canReview(einvoice)"
                        variant="primary"
                        size="sm"
                        :disabled="isAccepting === einvoice.id"
                        @click="handleAccept(einvoice)"
                      >
                        <LoadingIcon v-if="isAccepting === einvoice.id" class="h-4 mr-1 animate-spin" />
                        <BaseIcon v-else name="CheckCircleIcon" class="h-4 mr-1" />
                        {{ $t('e_invoice.accept') }}
                      </BaseButton>

                      <!-- Reject -->
                      <BaseButton
                        v-if="canReview(einvoice)"
                        variant="danger-outline"
                        size="sm"
                        @click="openRejectModal(einvoice)"
                      >
                        <BaseIcon name="XCircleIcon" class="h-4 mr-1" />
                        {{ $t('e_invoice.reject') }}
                      </BaseButton>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="totalPages > 1" class="mt-4 flex items-center justify-between">
            <p class="text-sm text-gray-700">
              {{ $t('general.showing') }} {{ currentPage }} {{ $t('general.of') }} {{ totalPages }}
            </p>
            <div class="flex space-x-2">
              <BaseButton
                variant="primary-outline"
                size="sm"
                :disabled="currentPage <= 1"
                @click="changePage(currentPage - 1)"
              >
                {{ $t('general.previous') }}
              </BaseButton>
              <BaseButton
                variant="primary-outline"
                size="sm"
                :disabled="currentPage >= totalPages"
                @click="changePage(currentPage + 1)"
              >
                {{ $t('general.next') }}
              </BaseButton>
            </div>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- E-Invoice Preview Modal -->
    <EInvoicePreviewModal
      @accepted="onPreviewAccepted"
      @rejected="onPreviewRejected"
    />

    <!-- Reject Modal -->
    <teleport to="body">
      <div
        v-if="showRejectModal"
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="reject-modal-title"
        role="dialog"
        aria-modal="true"
      >
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
          <!-- Backdrop -->
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeRejectModal" />

          <!-- Modal Panel -->
          <div class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            <div>
              <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <BaseIcon name="XCircleIcon" class="h-6 w-6 text-red-600" />
              </div>
              <div class="mt-3 text-center sm:mt-5">
                <h3 id="reject-modal-title" class="text-lg leading-6 font-medium text-gray-900">
                  {{ $t('e_invoice.reject_incoming_title') }}
                </h3>
                <div class="mt-2">
                  <p class="text-sm text-gray-500">
                    {{ $t('e_invoice.reject_incoming_description') }}
                  </p>
                </div>
                <div class="mt-4">
                  <textarea
                    v-model="rejectionReason"
                    rows="3"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                    :placeholder="$t('e_invoice.rejection_reason_placeholder')"
                  />
                </div>
              </div>
            </div>
            <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
              <BaseButton
                variant="danger"
                :disabled="!rejectionReason.trim() || isRejecting"
                class="w-full sm:col-start-2"
                @click="handleReject"
              >
                <LoadingIcon v-if="isRejecting" class="h-4 mr-1 animate-spin" />
                {{ $t('e_invoice.confirm_reject') }}
              </BaseButton>
              <BaseButton
                variant="primary-outline"
                class="mt-3 w-full sm:col-start-1 sm:mt-0"
                @click="closeRejectModal"
              >
                {{ $t('general.cancel') }}
              </BaseButton>
            </div>
          </div>
        </div>
      </div>
    </teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import moment from 'moment'

import { useNotificationStore } from '@/scripts/stores/notification'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useModalStore } from '@/scripts/stores/modal'
import { handleError } from '@/scripts/helpers/error-handling'
import LoadingIcon from '@/scripts/components/icons/LoadingIcon.vue'
import EInvoicePreviewModal from '@/scripts/admin/components/modal-components/EInvoicePreviewModal.vue'

const { t } = useI18n()
const notificationStore = useNotificationStore()
const dialogStore = useDialogStore()
const modalStore = useModalStore()

// State
const isLoading = ref(true)
const isPolling = ref(false)
const isAccepting = ref(null) // holds the einvoice ID being accepted
const isRejecting = ref(false)
const incomingInvoices = ref([])
const currentPage = ref(1)
const totalPages = ref(1)
const limit = ref(10)

// Reject modal state
const showRejectModal = ref(false)
const rejectionReason = ref('')
const rejectTarget = ref(null)

// Lifecycle
onMounted(async () => {
  await loadIncoming()
})

/**
 * Load incoming e-invoices from the API.
 */
async function loadIncoming(page = 1) {
  isLoading.value = true
  try {
    const response = await axios.get('/e-invoices/incoming', {
      params: {
        page,
        limit: limit.value,
      },
    })
    incomingInvoices.value = response.data.data || []
    currentPage.value = response.data.meta?.current_page || page
    totalPages.value = response.data.meta?.last_page || 1
  } catch (err) {
    handleError(err)
  } finally {
    isLoading.value = false
  }
}

/**
 * Change pagination page.
 */
function changePage(page) {
  loadIncoming(page)
}

/**
 * Check if the e-invoice can be reviewed (accepted/rejected).
 */
function canReview(einvoice) {
  return ['RECEIVED', 'UNDER_REVIEW'].includes(einvoice.status)
}

/**
 * Poll portal inbox for new invoices.
 */
async function handlePollInbox() {
  isPolling.value = true
  try {
    await axios.post('/e-invoices/incoming/poll')
    notificationStore.showNotification({
      type: 'success',
      message: t('e_invoice.poll_queued'),
    })
    // Reload after a short delay to allow job to process
    setTimeout(() => loadIncoming(), 3000)
  } catch (err) {
    handleError(err)
  } finally {
    isPolling.value = false
  }
}

/**
 * Accept an incoming e-invoice.
 */
async function handleAccept(einvoice) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('e_invoice.confirm_accept_incoming'),
      yesLabel: t('e_invoice.accept'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'md',
    })
    .then(async (response) => {
      if (response) {
        isAccepting.value = einvoice.id
        try {
          await axios.post(`/e-invoices/incoming/${einvoice.id}/accept`)
          notificationStore.showNotification({
            type: 'success',
            message: t('e_invoice.accepted_successfully'),
          })
          await loadIncoming(currentPage.value)
        } catch (err) {
          handleError(err)
        } finally {
          isAccepting.value = null
        }
      }
    })
}

/**
 * Open the reject modal.
 */
function openRejectModal(einvoice) {
  rejectTarget.value = einvoice
  rejectionReason.value = ''
  showRejectModal.value = true
}

/**
 * Close the reject modal.
 */
function closeRejectModal() {
  showRejectModal.value = false
  rejectTarget.value = null
  rejectionReason.value = ''
}

/**
 * Reject an incoming e-invoice with reason.
 */
async function handleReject() {
  if (!rejectTarget.value || !rejectionReason.value.trim()) return

  isRejecting.value = true
  try {
    await axios.post(`/e-invoices/incoming/${rejectTarget.value.id}/reject`, {
      rejection_reason: rejectionReason.value.trim(),
    })
    notificationStore.showNotification({
      type: 'success',
      message: t('e_invoice.rejected_successfully'),
    })
    closeRejectModal()
    await loadIncoming(currentPage.value)
  } catch (err) {
    handleError(err)
  } finally {
    isRejecting.value = false
  }
}

/**
 * View the e-invoice in a rich preview modal.
 */
function handleViewXml(einvoice) {
  modalStore.openModal({
    title: t('e_invoice.preview_title'),
    componentName: 'EInvoicePreviewModal',
    size: 'lg',
    data: einvoice,
    refreshData: () => loadIncoming(currentPage.value),
  })
}

/**
 * Handle accept event from the preview modal.
 */
function onPreviewAccepted() {
  loadIncoming(currentPage.value)
}

/**
 * Handle reject event from the preview modal — open the reject modal.
 */
function onPreviewRejected(einvoice) {
  openRejectModal(einvoice)
}

/**
 * Format datetime for display.
 */
function formatDateTime(date) {
  if (!date) return '-'
  return moment(date).format('DD.MM.YYYY HH:mm')
}

/**
 * Get CSS classes for the status badge.
 */
function getStatusBadgeClasses(status) {
  const classes = {
    RECEIVED: 'bg-blue-100 text-blue-800',
    UNDER_REVIEW: 'bg-yellow-100 text-yellow-800',
    ACCEPTED_INCOMING: 'bg-green-100 text-green-800',
    REJECTED_INCOMING: 'bg-red-100 text-red-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

/**
 * Get human-readable status label.
 */
function getStatusLabel(status) {
  const labels = {
    RECEIVED: t('e_invoice.status_received'),
    UNDER_REVIEW: t('e_invoice.status_under_review'),
    ACCEPTED_INCOMING: t('e_invoice.status_accepted'),
    REJECTED_INCOMING: t('e_invoice.status_rejected'),
  }
  return labels[status] || status
}

</script>
// CLAUDE-CHECKPOINT
