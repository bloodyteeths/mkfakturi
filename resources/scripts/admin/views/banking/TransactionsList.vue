<template>
  <div class="relative">
    <!-- Bulk Action Bar -->
    <div
      v-if="selectedIds.length > 0"
      class="mb-3 bg-primary-50 border border-primary-200 rounded-lg p-3 flex items-center justify-between"
    >
      <span class="text-sm font-medium text-primary-800">
        {{ selectedIds.length }} {{ $t('general.selected') || 'selected' }}
      </span>
      <div class="flex items-center space-x-2">
        <BaseButton
          variant="primary-outline"
          size="sm"
          @click="showBulkCategorizeModal = true"
        >
          <template #left="slotProps">
            <BaseIcon name="TagIcon" :class="slotProps.class" />
          </template>
          {{ $t('banking.bulk_categorize', 'Bulk Categorize') }}
        </BaseButton>
        <BaseButton
          variant="danger"
          size="sm"
          @click="bulkDelete"
          :disabled="isBulkDeleting"
        >
          <template #left="slotProps">
            <BaseIcon name="TrashIcon" :class="slotProps.class" />
          </template>
          {{ $t('general.delete') }}
        </BaseButton>
        <BaseButton
          variant="secondary"
          size="sm"
          @click="selectedIds = []"
        >
          {{ $t('general.cancel') }}
        </BaseButton>
      </div>
    </div>

    <!-- Transactions Table -->
    <BaseTable
      ref="tableComponent"
      :data="fetchTransactions"
      :columns="transactionColumns"
      class="mt-3"
    >
      <template #cell-select="{ row }">
        <input
          type="checkbox"
          class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
          :checked="selectedIds.includes(row.data.id)"
          @change="toggleSelect(row.data.id)"
        />
      </template>

      <template #cell-account="{ row }">
        <span class="text-sm text-gray-900">
          {{ row.data.bank_account_name }}
        </span>
      </template>

      <template #cell-date="{ row }">
        <span class="text-sm text-gray-900">
          {{ formatDate(row.data.transaction_date) }}
        </span>
      </template>

      <template #cell-description="{ row }">
        <div class="max-w-xs">
          <p class="text-sm font-medium text-gray-900 truncate">
            {{ row.data.description || row.data.remittance_info }}
          </p>
          <p v-if="row.data.counterparty_name" class="text-xs text-gray-500 truncate">
            {{ row.data.counterparty_name }}
          </p>
        </div>
      </template>

      <template #cell-amount="{ row }">
        <div class="text-right">
          <span
            class="text-sm font-semibold"
            :class="isCredit(row.data) ? 'text-green-600' : 'text-red-600'"
          >
            {{ formatAmount(row.data) }}
          </span>
        </div>
      </template>

      <template #cell-type="{ row }">
        <span
          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
          :class="isCredit(row.data)
            ? 'bg-green-100 text-green-800'
            : 'bg-red-100 text-red-800'"
        >
          {{ isCredit(row.data) ? $t('banking.credit') : $t('banking.debit') }}
        </span>
      </template>

      <template #cell-category="{ row }">
        <div v-if="row.data.linked_type || row.data.category">
          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
            {{ row.data.category?.name || linkedTypeLabel(row.data.linked_type) }}
          </span>
        </div>
        <div v-else-if="!row.data.matched_invoice_id">
          <BaseButton
            variant="primary-outline"
            size="sm"
            @click="$emit('reconcile', row.data)"
          >
            <template #left="slotProps">
              <BaseIcon name="SparklesIcon" :class="slotProps.class" />
            </template>
            {{ $t('banking.reconcile', 'Reconcile') }}
          </BaseButton>
        </div>
      </template>

      <template #cell-status="{ row }">
        <span
          v-if="row.data.matched_invoice_id"
          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
        >
          <BaseIcon name="CheckCircleIcon" class="h-3 w-3 mr-1" />
          {{ $t('banking.matched') }}
        </span>
        <span
          v-else-if="row.data.processing_status === 'processed'"
          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
        >
          {{ $t('banking.processed') }}
        </span>
        <span
          v-else
          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"
        >
          {{ $t('banking.pending') }}
        </span>
      </template>

      <template #cell-actions="{ row }">
        <BaseDropdown>
          <template #activator>
            <BaseIcon
              name="EllipsisVerticalIcon"
              class="h-5 w-5 text-gray-400 cursor-pointer"
            />
          </template>
          <BaseDropdownItem @click="viewTransactionDetails(row.data)">
            <BaseIcon name="EyeIcon" class="mr-3 text-gray-600" />
            {{ $t('general.view') }}
          </BaseDropdownItem>
          <BaseDropdownItem
            v-if="!row.data.matched_invoice_id && !row.data.linked_type"
            @click="$emit('reconcile', row.data)"
          >
            <BaseIcon name="SparklesIcon" class="mr-3 text-primary-600" />
            {{ $t('banking.reconcile', 'Reconcile') }}
          </BaseDropdownItem>
          <BaseDropdownItem
            v-if="row.data.linked_type || row.data.matched_invoice_id"
            @click="reclassifyTransaction(row.data)"
          >
            <BaseIcon name="ArrowPathIcon" class="mr-3 text-amber-600" />
            {{ $t('banking.reclassify', 'Re-classify') }}
          </BaseDropdownItem>
          <BaseDropdownItem
            v-if="!row.data.matched_invoice_id"
            @click="deleteTransaction(row.data)"
          >
            <BaseIcon name="TrashIcon" class="mr-3 text-red-600" />
            {{ $t('general.delete') }}
          </BaseDropdownItem>
        </BaseDropdown>
      </template>
    </BaseTable>

    <!-- Transaction Details Modal -->
    <BaseModal
      :show="showDetailsModal"
      @close="showDetailsModal = false"
      @update:show="showDetailsModal = $event"
    >
      <template #header>
        <h3 class="text-lg font-semibold text-gray-900">
          {{ $t('banking.transaction_details') }}
        </h3>
      </template>

      <div v-if="selectedTransactionDetails" class="p-6 space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="text-sm font-medium text-gray-500">{{ $t('banking.date') }}</p>
            <p class="text-sm text-gray-900">{{ formatDate(selectedTransactionDetails.transaction_date) }}</p>
          </div>
          <div>
            <p class="text-sm font-medium text-gray-500">{{ $t('banking.amount') }}</p>
            <p class="text-sm font-semibold" :class="isCredit(selectedTransactionDetails) ? 'text-green-600' : 'text-red-600'">
              {{ formatAmount(selectedTransactionDetails) }}
            </p>
          </div>
          <div class="col-span-2">
            <p class="text-sm font-medium text-gray-500">{{ $t('banking.description') }}</p>
            <p class="text-sm text-gray-900">{{ selectedTransactionDetails.description }}</p>
          </div>
          <div v-if="selectedTransactionDetails.remittance_info" class="col-span-2">
            <p class="text-sm font-medium text-gray-500">{{ $t('banking.remittance_info') }}</p>
            <p class="text-sm text-gray-900">{{ selectedTransactionDetails.remittance_info }}</p>
          </div>
          <div v-if="selectedTransactionDetails.counterparty_name">
            <p class="text-sm font-medium text-gray-500">{{ $t('banking.counterparty') }}</p>
            <p class="text-sm text-gray-900">{{ selectedTransactionDetails.counterparty_name }}</p>
          </div>
          <div v-if="selectedTransactionDetails.counterparty_iban">
            <p class="text-sm font-medium text-gray-500">{{ $t('banking.iban') }}</p>
            <p class="text-sm text-gray-900">{{ selectedTransactionDetails.counterparty_iban }}</p>
          </div>
          <div v-if="selectedTransactionDetails.transaction_reference">
            <p class="text-sm font-medium text-gray-500">{{ $t('banking.reference') }}</p>
            <p class="text-sm text-gray-900">{{ selectedTransactionDetails.transaction_reference }}</p>
          </div>
          <div>
            <p class="text-sm font-medium text-gray-500">{{ $t('banking.type') }}</p>
            <p class="text-sm text-gray-900">{{ isCredit(selectedTransactionDetails) ? 'Credit' : 'Debit' }}</p>
          </div>
          <div>
            <p class="text-sm font-medium text-gray-500">{{ $t('banking.status') }}</p>
            <p class="text-sm text-gray-900">{{ selectedTransactionDetails.booking_status }}</p>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end">
          <BaseButton
            variant="secondary"
            @click="showDetailsModal = false"
          >
            {{ $t('general.close') }}
          </BaseButton>
        </div>
      </template>
    </BaseModal>

    <!-- Bulk Categorize Modal -->
    <BaseModal
      :show="showBulkCategorizeModal"
      @close="showBulkCategorizeModal = false"
      @update:show="showBulkCategorizeModal = $event"
    >
      <template #header>
        <h3 class="text-lg font-semibold text-gray-900">
          {{ $t('banking.bulk_categorize', 'Bulk Categorize') }}
        </h3>
      </template>
      <div class="p-6">
        <p class="text-sm text-gray-600 mb-4">
          {{ selectedIds.length }} {{ $t('banking.transactions', 'transactions') }}
        </p>
        <select
          v-model="bulkCategory"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500"
        >
          <option :value="null" disabled>{{ $t('banking.select_category', 'Select category...') }}</option>
          <option value="salary">{{ $t('banking.category_salary', 'Плата') }}</option>
          <option value="tax_payment">{{ $t('banking.category_tax', 'Даночно плаќање') }}</option>
          <option value="bank_fee">{{ $t('banking.category_bank_fee', 'Банкарска провизија') }}</option>
          <option value="supplier_payment">{{ $t('banking.category_supplier', 'Плаќање кон добавувач') }}</option>
          <option value="internal_transfer">{{ $t('banking.category_transfer', 'Интерен трансфер') }}</option>
          <option value="utility">{{ $t('banking.category_utility', 'Ко��унални') }}</option>
          <option value="rent">{{ $t('banking.category_rent', 'Кирија') }}</option>
          <option value="other">{{ $t('banking.category_other', 'Друго') }}</option>
        </select>
      </div>
      <template #footer>
        <div class="flex justify-end space-x-3">
          <BaseButton variant="secondary" @click="showBulkCategorizeModal = false">
            {{ $t('general.cancel') }}
          </BaseButton>
          <BaseButton variant="primary" :disabled="!bulkCategory" @click="confirmBulkCategorize">
            {{ $t('banking.apply_category', 'Apply Category') }}
          </BaseButton>
        </div>
      </template>
    </BaseModal>

    <!-- Delete Confirm Modal -->
    <BaseModal
      :show="showDeleteConfirm"
      @close="showDeleteConfirm = false"
      @update:show="showDeleteConfirm = $event"
    >
      <template #header>
        <h3 class="text-lg font-semibold text-red-600">
          {{ $t('general.confirm_delete') || 'Confirm Delete' }}
        </h3>
      </template>

      <div class="p-6">
        <p class="text-sm text-gray-700">
          {{ deleteConfirmMessage }}
        </p>
      </div>

      <template #footer>
        <div class="flex justify-end space-x-3">
          <BaseButton variant="secondary" @click="showDeleteConfirm = false">
            {{ $t('general.cancel') }}
          </BaseButton>
          <BaseButton variant="danger" @click="confirmDelete" :disabled="isDeleting">
            {{ $t('general.delete') }}
          </BaseButton>
        </div>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/scripts/stores/notification'
import axios from 'axios'

const props = defineProps({
  filters: {
    type: Object,
    default: () => ({})
  },
  accounts: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['categorize', 'reconcile', 'refresh', 'reclassify'])

const { t } = useI18n()
const router = useRouter()
const notificationStore = useNotificationStore()

// State
const tableComponent = ref(null)
const showDetailsModal = ref(false)
const selectedTransactionDetails = ref(null)
const selectedIds = ref([])
const showDeleteConfirm = ref(false)
const deleteConfirmMessage = ref('')
const pendingDeleteAction = ref(null)
const isDeleting = ref(false)
const isBulkDeleting = ref(false)
const showBulkCategorizeModal = ref(false)
const bulkCategory = ref(null)

// Re-fetch when filters change (account_id, dates)
watch(() => props.filters, () => {
  if (tableComponent.value) {
    tableComponent.value.refresh()
  }
}, { deep: true })

// Helpers
const isCredit = (tx) => tx.transaction_type === 'credit'

const linkedTypeLabel = (type) => {
  const labels = {
    expense: t('banking.expense', 'Expense'),
    bill_payment: t('banking.bill_payment', 'Bill Payment'),
    payroll_run: t('banking.payroll', 'Payroll'),
    reviewed: t('banking.reviewed', 'Reviewed'),
    income: t('banking.income', 'Income'),
    owner_contribution: t('banking.owner_contribution', 'Capital Contribution'),
    owner_withdrawal: t('banking.owner_withdrawal', 'Capital Withdrawal'),
    loan_received: t('banking.loan_received', 'Loan Received'),
    loan_repayment: t('banking.loan_repayment', 'Loan Repayment'),
    tax_payment: t('banking.tax_payment', 'Tax Payment'),
    internal_transfer: t('banking.internal_transfer', 'Internal Transfer'),
  }
  return labels[type] || type || ''
}

// Table columns
const transactionColumns = computed(() => [
  {
    label: '',
    key: 'select',
    thClass: 'w-10 text-center',
    tdClass: 'text-center'
  },
  {
    label: t('banking.account'),
    key: 'account',
    thClass: 'text-left',
    tdClass: 'text-left'
  },
  {
    label: t('banking.date'),
    key: 'date',
    thClass: 'text-left',
    tdClass: 'text-left'
  },
  {
    label: t('banking.description'),
    key: 'description',
    thClass: 'text-left',
    tdClass: 'text-left'
  },
  {
    label: t('banking.amount'),
    key: 'amount',
    thClass: 'text-right',
    tdClass: 'text-right'
  },
  {
    label: t('banking.type'),
    key: 'type',
    thClass: 'text-center',
    tdClass: 'text-center'
  },
  {
    label: t('banking.category'),
    key: 'category',
    thClass: 'text-left',
    tdClass: 'text-left'
  },
  {
    label: t('banking.status'),
    key: 'status',
    thClass: 'text-center',
    tdClass: 'text-center'
  },
  {
    label: '',
    key: 'actions',
    thClass: 'text-right',
    tdClass: 'text-right'
  }
])

// Methods
const fetchTransactions = async ({ page, limit, search, orderByField, orderBy }) => {
  try {
    const params = {
      page,
      limit,
      search,
      orderByField: orderByField || 'transaction_date',
      orderBy: orderBy || 'desc',
      ...props.filters
    }

    const response = await axios.get('/banking/transactions', { params })

    return {
      data: response.data.data || [],
      pagination: {
        totalPages: response.data.meta?.last_page || 1,
        currentPage: response.data.meta?.current_page || 1,
        totalCount: response.data.meta?.total || 0,
        limit: response.data.meta?.per_page || limit
      }
    }
  } catch (error) {
    console.error('Failed to fetch transactions:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('banking.failed_to_load_transactions')
    })
    return {
      data: [],
      pagination: {
        totalPages: 1,
        currentPage: 1,
        totalCount: 0,
        limit
      }
    }
  }
}

const toggleSelect = (id) => {
  const idx = selectedIds.value.indexOf(id)
  if (idx >= 0) {
    selectedIds.value.splice(idx, 1)
  } else {
    selectedIds.value.push(id)
  }
}

const viewTransactionDetails = (transaction) => {
  selectedTransactionDetails.value = transaction
  showDetailsModal.value = true
}

const matchToInvoice = (transaction) => {
  router.push('/admin/banking/reconciliation')
}

const deleteTransaction = (transaction) => {
  deleteConfirmMessage.value = t('banking.confirm_delete_transaction') || `Delete this transaction? (${formatAmount(transaction)})`
  pendingDeleteAction.value = async () => {
    try {
      await axios.delete(`/banking/transactions/${transaction.id}`)
      notificationStore.showNotification({
        type: 'success',
        message: t('banking.transaction_deleted') || 'Transaction deleted'
      })
      refresh()
    } catch (error) {
      notificationStore.showNotification({
        type: 'error',
        message: error.response?.data?.message || 'Failed to delete transaction'
      })
    }
  }
  showDeleteConfirm.value = true
}

const unmatchTransaction = async (transaction) => {
  try {
    await axios.post(`/banking/transactions/${transaction.id}/unmatch`)
    notificationStore.showNotification({
      type: 'success',
      message: t('banking.transaction_unmatched') || 'Transaction unmatched'
    })
    refresh()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || 'Failed to unmatch transaction'
    })
  }
}

const reclassifyTransaction = async (transaction) => {
  try {
    await axios.post('/banking/reconciliation/undo', {
      transaction_id: transaction.id,
    })
    notificationStore.showNotification({
      type: 'success',
      message: t('banking.reconciliation_undone', 'Reconciliation undone — you can now re-classify'),
    })
    refresh()
    // Open the reconcile drawer for immediate re-classification
    emit('reconcile', { ...transaction, linked_type: null, matched_invoice_id: null })
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || 'Failed to undo reconciliation',
    })
  }
}

const confirmBulkCategorize = async () => {
  if (!bulkCategory.value || selectedIds.value.length === 0) return
  try {
    await axios.post('/banking/transactions/bulk-categorize', {
      ids: selectedIds.value,
      category: bulkCategory.value,
    })
    notificationStore.showNotification({
      type: 'success',
      message: t('banking.bulk_categorize_success', 'Transactions categorized successfully'),
    })
    showBulkCategorizeModal.value = false
    bulkCategory.value = null
    selectedIds.value = []
    refresh()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || 'Failed to categorize',
    })
  }
}

const bulkDelete = () => {
  deleteConfirmMessage.value = `${t('banking.confirm_bulk_delete') || 'Delete'} ${selectedIds.value.length} ${t('banking.transactions') || 'transactions'}?`
  pendingDeleteAction.value = async () => {
    isBulkDeleting.value = true
    try {
      const response = await axios.post('/banking/transactions/bulk-delete', {
        ids: selectedIds.value
      })
      notificationStore.showNotification({
        type: 'success',
        message: response.data.message
      })
      selectedIds.value = []
      refresh()
    } catch (error) {
      notificationStore.showNotification({
        type: 'error',
        message: error.response?.data?.message || 'Failed to delete transactions'
      })
    } finally {
      isBulkDeleting.value = false
    }
  }
  showDeleteConfirm.value = true
}

const confirmDelete = async () => {
  isDeleting.value = true
  try {
    if (pendingDeleteAction.value) {
      await pendingDeleteAction.value()
    }
  } finally {
    isDeleting.value = false
    showDeleteConfirm.value = false
    pendingDeleteAction.value = null
  }
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('mk-MK', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatAmount = (tx) => {
  const amount = tx.amount
  if (amount === null || amount === undefined) return '-'

  const sign = isCredit(tx) ? '+' : '-'

  return `${sign} ${new Intl.NumberFormat('mk-MK', {
    style: 'currency',
    currency: tx.currency || 'MKD'
  }).format(Math.abs(amount))}`
}

// Public methods for parent component
const refresh = () => {
  if (tableComponent.value) {
    tableComponent.value.refresh()
  }
  emit('refresh')
}

defineExpose({
  refresh
})
</script>

<!-- CLAUDE-CHECKPOINT -->
