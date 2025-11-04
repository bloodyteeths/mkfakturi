<template>
  <div class="relative">
    <!-- Transactions Table -->
    <BaseTable
      ref="tableComponent"
      :data="fetchTransactions"
      :columns="transactionColumns"
      class="mt-3"
    >
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
            :class="row.data.amount > 0 ? 'text-green-600' : 'text-red-600'"
          >
            {{ formatAmount(row.data.amount, row.data.currency) }}
          </span>
        </div>
      </template>

      <template #cell-type="{ row }">
        <span
          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
          :class="row.data.amount > 0
            ? 'bg-green-100 text-green-800'
            : 'bg-red-100 text-red-800'"
        >
          {{ row.data.amount > 0 ? $t('banking.credit') : $t('banking.debit') }}
        </span>
      </template>

      <template #cell-category="{ row }">
        <div v-if="row.data.category">
          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
            {{ row.data.category.name }}
          </span>
        </div>
        <div v-else>
          <BaseButton
            variant="link"
            size="sm"
            @click="$emit('categorize', row.data)"
          >
            {{ $t('banking.categorize') }}
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
          <BaseDropdownItem
            v-if="!row.data.category"
            @click="$emit('categorize', row.data)"
          >
            <BaseIcon name="TagIcon" class="mr-3 text-gray-600" />
            {{ $t('banking.categorize') }}
          </BaseDropdownItem>
          <BaseDropdownItem @click="viewTransactionDetails(row.data)">
            <BaseIcon name="EyeIcon" class="mr-3 text-gray-600" />
            {{ $t('general.view') }}
          </BaseDropdownItem>
          <BaseDropdownItem
            v-if="!row.data.matched_invoice_id"
            @click="matchToInvoice(row.data)"
          >
            <BaseIcon name="LinkIcon" class="mr-3 text-gray-600" />
            {{ $t('banking.match_invoice') }}
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
            <p class="text-sm font-semibold" :class="selectedTransactionDetails.amount > 0 ? 'text-green-600' : 'text-red-600'">
              {{ formatAmount(selectedTransactionDetails.amount, selectedTransactionDetails.currency) }}
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
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
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

const emit = defineEmits(['categorize'])

const { t } = useI18n()
const notificationStore = useNotificationStore()

// State
const tableComponent = ref(null)
const showDetailsModal = ref(false)
const selectedTransactionDetails = ref(null)

// Table columns
const transactionColumns = computed(() => [
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

    const response = await axios.get('/api/v1/banking/transactions', { params })

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

const viewTransactionDetails = (transaction) => {
  selectedTransactionDetails.value = transaction
  showDetailsModal.value = true
}

const matchToInvoice = async (transaction) => {
  // TODO: Implement invoice matching logic
  notificationStore.showNotification({
    type: 'info',
    message: t('banking.match_invoice_coming_soon')
  })
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatAmount = (amount, currency) => {
  if (amount === null || amount === undefined) return '-'

  const absAmount = Math.abs(amount)
  const sign = amount >= 0 ? '+' : '-'

  return `${sign} ${new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency || 'MKD'
  }).format(absAmount)}`
}

// Public methods for parent component
const refresh = () => {
  if (tableComponent.value) {
    tableComponent.value.refresh()
  }
}

defineExpose({
  refresh
})
</script>

<!-- CLAUDE-CHECKPOINT -->
