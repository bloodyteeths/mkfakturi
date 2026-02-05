<template>
  <BasePage>
    <BasePageHeader :title="$t('banking.reconciliation')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('banking.title')" to="/admin/banking" />
        <BaseBreadcrumbItem :title="$t('banking.reconciliation')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <div class="flex items-center justify-end space-x-4">
          <BaseButton
            variant="primary-outline"
            @click="refreshData"
            :disabled="isLoading"
          >
            <template #left="slotProps">
              <BaseIcon name="ArrowPathIcon" :class="[slotProps.class, { 'animate-spin': isLoading }]" />
            </template>
            {{ $t('general.refresh') }}
          </BaseButton>
          <BaseButton
            variant="primary"
            @click="runAutoMatch"
            :disabled="isLoading || unmatchedTransactions.length === 0"
          >
            <template #left="slotProps">
              <BaseIcon name="SparklesIcon" :class="slotProps.class" />
            </template>
            {{ $t('banking.auto_match') }}
          </BaseButton>
        </div>
      </template>
    </BasePageHeader>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-500">{{ $t('banking.total_transactions') }}</p>
        <p class="text-2xl font-bold text-gray-900">{{ stats.total_transactions }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-500">{{ $t('banking.matched') }}</p>
        <p class="text-2xl font-bold text-green-600">{{ stats.matched_transactions }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-500">{{ $t('banking.unmatched') }}</p>
        <p class="text-2xl font-bold text-yellow-600">{{ stats.unmatched_transactions }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-500">{{ $t('banking.match_rate') }}</p>
        <p class="text-2xl font-bold text-blue-600">{{ stats.match_rate }}%</p>
      </div>
    </div>

    <!-- Unmatched Transactions -->
    <div class="mt-8">
      <h2 class="text-xl font-semibold text-gray-900 mb-4">
        {{ $t('banking.unmatched_transactions') }}
      </h2>

      <div v-if="isLoading" class="flex justify-center py-8">
        <BaseContentPlaceholders>
          <BaseContentPlaceholdersBox :rounded="true" />
        </BaseContentPlaceholders>
      </div>

      <div v-else-if="unmatchedTransactions.length === 0" class="text-center py-8 text-gray-500">
        <BaseIcon name="CheckCircleIcon" class="h-12 w-12 mx-auto text-green-500 mb-4" />
        <p>{{ $t('banking.all_transactions_matched') }}</p>
      </div>

      <div v-else class="space-y-4">
        <div
          v-for="tx in unmatchedTransactions"
          :key="tx.id"
          class="bg-white rounded-lg shadow p-6 border border-gray-200"
        >
          <div class="flex items-start justify-between">
            <!-- Transaction Info -->
            <div class="flex-1">
              <div class="flex items-center space-x-4 mb-2">
                <span class="text-lg font-semibold text-green-600">
                  +{{ formatMoney(tx.amount, tx.currency) }}
                </span>
                <span class="text-sm text-gray-500">
                  {{ formatDate(tx.transaction_date) }}
                </span>
              </div>
              <p class="text-sm text-gray-900 mb-1">{{ tx.description }}</p>
              <p v-if="tx.counterparty_name" class="text-xs text-gray-500">
                {{ $t('banking.from') }}: {{ tx.counterparty_name }}
              </p>
              <p v-if="tx.remittance_info" class="text-xs text-gray-400 mt-1">
                {{ tx.remittance_info }}
              </p>
            </div>

            <!-- Suggested Match -->
            <div v-if="tx.suggested_match" class="ml-6 p-4 bg-blue-50 rounded-lg min-w-[300px]">
              <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-blue-800">
                  {{ $t('banking.suggested_match') }}
                </span>
                <span
                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                  :class="getConfidenceClass(tx.suggested_match.confidence)"
                >
                  {{ tx.suggested_match.confidence }}% {{ $t('banking.confidence') }}
                </span>
              </div>
              <p class="text-sm font-semibold text-gray-900">
                {{ tx.suggested_match.invoice_number }}
              </p>
              <p class="text-sm text-gray-600">
                {{ formatMoney(tx.suggested_match.invoice_total, tx.currency) }}
              </p>
              <div class="flex space-x-2 mt-3">
                <BaseButton
                  variant="primary"
                  size="sm"
                  @click="acceptMatch(tx, tx.suggested_match)"
                >
                  {{ $t('general.accept') }}
                </BaseButton>
                <BaseButton
                  variant="secondary"
                  size="sm"
                  @click="openManualMatchModal(tx)"
                >
                  {{ $t('banking.choose_different') }}
                </BaseButton>
              </div>
            </div>

            <!-- No Match Found -->
            <div v-else class="ml-6">
              <BaseButton
                variant="primary-outline"
                size="sm"
                @click="openManualMatchModal(tx)"
              >
                <template #left="slotProps">
                  <BaseIcon name="LinkIcon" :class="slotProps.class" />
                </template>
                {{ $t('banking.match_manually') }}
              </BaseButton>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="meta.total > meta.per_page" class="mt-4 flex justify-center">
        <BasePagination
          :current-page="meta.current_page"
          :total-pages="meta.last_page"
          @change-page="changePage"
        />
      </div>
    </div>

    <!-- Manual Match Modal -->
    <BaseModal
      :show="showManualMatchModal"
      @close="showManualMatchModal = false"
      @update:show="showManualMatchModal = $event"
    >
      <template #header>
        <h3 class="text-lg font-semibold text-gray-900">
          {{ $t('banking.select_invoice_to_match') }}
        </h3>
      </template>

      <div class="p-6">
        <div v-if="selectedTransaction" class="mb-4 p-4 bg-gray-50 rounded-lg">
          <p class="text-sm text-gray-500">{{ $t('banking.matching_transaction') }}</p>
          <p class="font-semibold text-green-600">
            +{{ formatMoney(selectedTransaction.amount, selectedTransaction.currency) }}
          </p>
          <p class="text-sm text-gray-900">{{ selectedTransaction.description }}</p>
        </div>

        <div v-if="unpaidInvoices.length === 0" class="text-center py-4 text-gray-500">
          {{ $t('banking.no_unpaid_invoices') }}
        </div>

        <div v-else class="space-y-2 max-h-96 overflow-y-auto">
          <div
            v-for="invoice in unpaidInvoices"
            :key="invoice.id"
            class="flex items-center justify-between p-3 border rounded-lg cursor-pointer hover:bg-blue-50"
            :class="{ 'border-blue-500 bg-blue-50': selectedInvoiceId === invoice.id }"
            @click="selectedInvoiceId = invoice.id"
          >
            <div>
              <p class="font-medium text-gray-900">{{ invoice.invoice_number }}</p>
              <p class="text-sm text-gray-500">{{ invoice.customer_name }}</p>
              <p class="text-xs text-gray-400">{{ $t('general.due') }}: {{ formatDate(invoice.due_date) }}</p>
            </div>
            <div class="text-right">
              <p class="font-semibold text-gray-900">{{ formatMoney(invoice.total) }}</p>
              <span
                v-if="selectedTransaction && Math.abs(invoice.total - selectedTransaction.amount) < 1"
                class="text-xs text-green-600"
              >
                {{ $t('banking.exact_match') }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end space-x-3">
          <BaseButton
            variant="secondary"
            @click="showManualMatchModal = false"
          >
            {{ $t('general.cancel') }}
          </BaseButton>
          <BaseButton
            variant="primary"
            :disabled="!selectedInvoiceId"
            @click="confirmManualMatch"
          >
            {{ $t('banking.confirm_match') }}
          </BaseButton>
        </div>
      </template>
    </BaseModal>
  </BasePage>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'
import axios from 'axios'

const { t } = useI18n()
const notificationStore = useNotificationStore()

// State
const isLoading = ref(false)
const unmatchedTransactions = ref([])
const unpaidInvoices = ref([])
const stats = ref({
  total_transactions: 0,
  matched_transactions: 0,
  unmatched_transactions: 0,
  match_rate: 0
})
const meta = ref({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0
})

const showManualMatchModal = ref(false)
const selectedTransaction = ref(null)
const selectedInvoiceId = ref(null)

// Methods
const fetchData = async (page = 1) => {
  isLoading.value = true
  try {
    const [transactionsRes, statsRes] = await Promise.all([
      axios.get('/api/v1/banking/reconciliation', { params: { page, limit: 20 } }),
      axios.get('/api/v1/banking/reconciliation/stats')
    ])

    unmatchedTransactions.value = transactionsRes.data.data || []
    meta.value = transactionsRes.data.meta || meta.value
    stats.value = statsRes.data.stats || stats.value
  } catch (error) {
    console.error('Failed to fetch reconciliation data:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('banking.failed_to_load_data')
    })
  } finally {
    isLoading.value = false
  }
}

const fetchUnpaidInvoices = async () => {
  try {
    const response = await axios.get('/api/v1/banking/reconciliation/unpaid-invoices')
    unpaidInvoices.value = response.data.data || []
  } catch (error) {
    console.error('Failed to fetch unpaid invoices:', error)
  }
}

const refreshData = () => {
  fetchData(meta.value.current_page)
}

const changePage = (page) => {
  fetchData(page)
}

const runAutoMatch = async () => {
  isLoading.value = true
  try {
    const response = await axios.post('/api/v1/banking/reconciliation/auto-match')

    notificationStore.showNotification({
      type: 'success',
      message: t('banking.auto_match_complete', { count: response.data.matches_found })
    })

    // Refresh data
    await fetchData()
  } catch (error) {
    console.error('Auto-match failed:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('banking.auto_match_failed')
    })
  } finally {
    isLoading.value = false
  }
}

const acceptMatch = async (transaction, match) => {
  try {
    await axios.post('/api/v1/banking/reconciliation/manual-match', {
      transaction_id: transaction.id,
      invoice_id: match.invoice_id
    })

    notificationStore.showNotification({
      type: 'success',
      message: t('banking.match_successful')
    })

    // Refresh data
    await fetchData(meta.value.current_page)
  } catch (error) {
    console.error('Match failed:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('banking.match_failed')
    })
  }
}

const openManualMatchModal = async (transaction) => {
  selectedTransaction.value = transaction
  selectedInvoiceId.value = null
  await fetchUnpaidInvoices()
  showManualMatchModal.value = true
}

const confirmManualMatch = async () => {
  if (!selectedTransaction.value || !selectedInvoiceId.value) return

  try {
    await axios.post('/api/v1/banking/reconciliation/manual-match', {
      transaction_id: selectedTransaction.value.id,
      invoice_id: selectedInvoiceId.value
    })

    notificationStore.showNotification({
      type: 'success',
      message: t('banking.match_successful')
    })

    showManualMatchModal.value = false
    selectedTransaction.value = null
    selectedInvoiceId.value = null

    // Refresh data
    await fetchData(meta.value.current_page)
  } catch (error) {
    console.error('Manual match failed:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('banking.match_failed')
    })
  }
}

const getConfidenceClass = (confidence) => {
  if (confidence >= 85) return 'bg-green-100 text-green-800'
  if (confidence >= 70) return 'bg-yellow-100 text-yellow-800'
  return 'bg-red-100 text-red-800'
}

const formatMoney = (amount, currency = 'MKD') => {
  if (!amount) return '0.00'
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency || 'MKD'
  }).format(amount)
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

// Lifecycle
onMounted(() => {
  fetchData()
})
</script>

<!-- CLAUDE-CHECKPOINT -->
