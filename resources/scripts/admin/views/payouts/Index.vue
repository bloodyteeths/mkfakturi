<template>
  <BasePage>
    <!-- Page Header -->
    <BasePageHeader :title="$t('payouts.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('payouts.title')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <div class="flex items-center justify-end space-x-5">
          <BaseButton variant="primary-outline" @click="toggleFilter">
            {{ $t('general.filter') }}
            <template #right="slotProps">
              <BaseIcon
                v-if="!showFilters"
                name="FunnelIcon"
                :class="slotProps.class"
              />
              <BaseIcon v-else name="XMarkIcon" :class="slotProps.class" />
            </template>
          </BaseButton>

          <BaseButton variant="primary-outline" @click="exportCsv">
            <template #left="slotProps">
              <BaseIcon name="ArrowDownTrayIcon" :class="slotProps.class" />
            </template>
            {{ $t('payouts.export_pending_csv') }}
          </BaseButton>
        </div>
      </template>
    </BasePageHeader>

    <!-- Filters -->
    <BaseFilterWrapper :show="showFilters" class="mt-5" @clear="clearFilter">
      <BaseInputGroup :label="$t('payouts.search')" class="text-left">
        <BaseInput
          v-model="filters.search"
          type="text"
          name="search"
          autocomplete="off"
          :placeholder="$t('payouts.search_placeholder')"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('payouts.status')" class="text-left">
        <BaseSelect v-model="filters.status" @change="refreshTable">
          <option value="">{{ $t('payouts.all') }}</option>
          <option value="pending">{{ $t('payouts.pending') }}</option>
          <option value="processing">{{ $t('payouts.processing') }}</option>
          <option value="completed">{{ $t('payouts.completed') }}</option>
          <option value="failed">{{ $t('payouts.failed') }}</option>
          <option value="cancelled">{{ $t('payouts.cancelled') }}</option>
        </BaseSelect>
      </BaseInputGroup>

      <BaseInputGroup :label="$t('payouts.method')" class="text-left">
        <BaseSelect v-model="filters.payout_method" @change="refreshTable">
          <option value="">{{ $t('payouts.all') }}</option>
          <option value="bank_transfer">{{ $t('payouts.bank_transfer') }}</option>
          <option value="stripe_connect">{{ $t('payouts.stripe_connect') }}</option>
        </BaseSelect>
      </BaseInputGroup>

      <BaseInputGroup :label="$t('payouts.date_from')" class="text-left">
        <BaseInput
          v-model="filters.date_from"
          type="date"
          name="date_from"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('payouts.date_to')" class="text-left">
        <BaseInput
          v-model="filters.date_to"
          type="date"
          name="date_to"
        />
      </BaseInputGroup>
    </BaseFilterWrapper>

    <!-- Statistics Cards -->
    <div v-if="stats" class="grid grid-cols-1 gap-4 mt-6 md:grid-cols-4">
      <div class="p-4 bg-white border border-gray-200 rounded-lg">
        <div class="text-sm text-gray-500">{{ $t('payouts.pending_amount') }}</div>
        <div class="text-2xl font-semibold text-orange-600">
          <BaseFormatMoney
            :amount="stats.total_pending_amount"
            :currency="mkdCurrency"
          />
        </div>
      </div>
      <div class="p-4 bg-white border border-gray-200 rounded-lg">
        <div class="text-sm text-gray-500">{{ $t('payouts.pending_payouts') }}</div>
        <div class="text-2xl font-semibold text-orange-600">{{ stats.total_pending_count }}</div>
      </div>
      <div class="p-4 bg-white border border-gray-200 rounded-lg">
        <div class="text-sm text-gray-500">{{ $t('payouts.completed_this_month') }}</div>
        <div class="text-2xl font-semibold text-green-600">
          <BaseFormatMoney
            :amount="stats.completed_this_month"
            :currency="mkdCurrency"
          />
        </div>
      </div>
      <div class="p-4 bg-white border border-gray-200 rounded-lg">
        <div class="text-sm text-gray-500">{{ $t('payouts.total_paid_all_time') }}</div>
        <div class="text-2xl font-semibold">
          <BaseFormatMoney
            :amount="stats.total_completed_all_time"
            :currency="mkdCurrency"
          />
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <BaseEmptyPlaceholder
      v-show="showEmptyScreen"
      :title="$t('payouts.no_payouts')"
      :description="$t('payouts.no_payouts_desc')"
    >
      <AstronautIcon class="mt-5 mb-4" />
    </BaseEmptyPlaceholder>

    <!-- Payouts Table -->
    <div v-show="!showEmptyScreen" class="relative mt-6 table-container">
      <BaseTable
        ref="tableComponent"
        :data="fetchData"
        :columns="payoutColumns"
        class="mt-3"
      >
        <template #cell-partner_name="{ row }">
          <router-link :to="{ path: `/admin/partners/${row.data.partner_id}/view` }">
            <BaseText
              :text="row.data.partner_name"
              tag="span"
              class="font-medium text-primary-500"
            />
            <div class="text-xs text-gray-400">{{ row.data.partner_email }}</div>
          </router-link>
        </template>

        <template #cell-amount="{ row }">
          <BaseFormatMoney
            :amount="row.data.amount || 0"
            :currency="mkdCurrency"
          />
        </template>

        <template #cell-payout_method="{ row }">
          <span
            class="px-2 py-1 text-xs font-medium rounded"
            :class="row.data.payout_method === 'stripe_connect'
              ? 'bg-purple-100 text-purple-800'
              : 'bg-blue-100 text-blue-800'"
          >
            {{ row.data.payout_method === 'stripe_connect' ? $t('payouts.stripe') : $t('payouts.bank_transfer') }}
          </span>
        </template>

        <template #cell-status="{ row }">
          <span
            class="px-2 py-1 text-xs font-medium rounded"
            :class="{
              'bg-yellow-100 text-yellow-800': row.data.status === 'pending',
              'bg-blue-100 text-blue-800': row.data.status === 'processing',
              'bg-green-100 text-green-800': row.data.status === 'completed',
              'bg-red-100 text-red-800': row.data.status === 'failed',
              'bg-gray-100 text-gray-800': row.data.status === 'cancelled',
            }"
          >
            {{ $t(`payouts.${row.data.status}`) }}
          </span>
        </template>

        <template #cell-payout_date="{ row }">
          <span class="text-sm text-gray-600">
            {{ row.data.payout_date ? new Date(row.data.payout_date).toLocaleDateString() : '-' }}
          </span>
        </template>

        <template #cell-bank_info="{ row }">
          <div class="text-xs">
            <div class="text-gray-700">{{ row.data.partner_bank_name || $t('payouts.not_provided') }}</div>
            <div class="text-gray-400 font-mono">{{ row.data.partner_bank_account || $t('payouts.not_provided') }}</div>
          </div>
        </template>

        <template #cell-actions="{ row }">
          <BaseDropdown>
            <template #activator>
              <BaseIcon name="EllipsisHorizontalIcon" class="h-5 text-gray-500" />
            </template>

            <BaseDropdownItem @click="$router.push(`/admin/payouts/${row.data.id}/view`)">
              <BaseIcon name="EyeIcon" class="mr-3 text-gray-600" />
              {{ $t('payouts.view') }}
            </BaseDropdownItem>

            <BaseDropdownItem
              v-if="row.data.status === 'pending' || row.data.status === 'processing'"
              @click="openMarkPaidModal(row.data)"
            >
              <BaseIcon name="CheckCircleIcon" class="mr-3 text-green-600" />
              {{ $t('payouts.mark_as_paid') }}
            </BaseDropdownItem>

            <BaseDropdownItem
              v-if="row.data.status === 'pending' || row.data.status === 'processing'"
              @click="openMarkFailedModal(row.data)"
            >
              <BaseIcon name="ExclamationTriangleIcon" class="mr-3 text-orange-600" />
              {{ $t('payouts.mark_as_failed') }}
            </BaseDropdownItem>

            <BaseDropdownItem
              v-if="row.data.status === 'pending' || row.data.status === 'processing'"
              @click="openCancelModal(row.data)"
            >
              <BaseIcon name="XCircleIcon" class="mr-3 text-red-600" />
              {{ $t('payouts.cancel') }}
            </BaseDropdownItem>
          </BaseDropdown>
        </template>
      </BaseTable>
    </div>

    <!-- Mark as Paid Modal -->
    <BaseModal :show="showPayModal" @close="showPayModal = false">
      <template #header>
        <h3 class="text-lg font-medium">{{ $t('payouts.mark_as_paid') }}</h3>
      </template>

      <div class="p-4">
        <p class="mb-4 text-sm text-gray-600">
          {{ $t('payouts.confirm_mark_paid', { name: selectedPayout?.partner_name, amount: formatMkd(selectedPayout?.amount) }) }}
        </p>

        <BaseInputGroup :label="$t('payouts.payment_reference')" class="text-left">
          <BaseInput
            v-model="paymentReference"
            type="text"
            :placeholder="$t('payouts.payment_ref_placeholder')"
          />
        </BaseInputGroup>
      </div>

      <template #footer>
        <BaseButton variant="primary-outline" class="mr-3" @click="showPayModal = false">
          {{ $t('payouts.cancel') }}
        </BaseButton>
        <BaseButton
          :loading="isProcessing"
          :disabled="!paymentReference.trim()"
          @click="confirmMarkPaid"
        >
          {{ $t('payouts.confirm_payment') }}
        </BaseButton>
      </template>
    </BaseModal>

    <!-- Mark as Failed Modal -->
    <BaseModal :show="showFailModal" @close="showFailModal = false">
      <template #header>
        <h3 class="text-lg font-medium">{{ $t('payouts.mark_as_failed') }}</h3>
      </template>

      <div class="p-4">
        <p class="mb-4 text-sm text-gray-600">
          {{ $t('payouts.confirm_mark_failed', { name: selectedPayout?.partner_name, amount: formatMkd(selectedPayout?.amount) }) }}
        </p>

        <BaseInputGroup :label="$t('payouts.failure_reason')" class="text-left">
          <BaseInput
            v-model="failReason"
            type="text"
            :placeholder="$t('payouts.failure_reason_placeholder')"
          />
        </BaseInputGroup>
      </div>

      <template #footer>
        <BaseButton variant="primary-outline" class="mr-3" @click="showFailModal = false">
          {{ $t('payouts.cancel') }}
        </BaseButton>
        <BaseButton
          variant="danger"
          :loading="isProcessing"
          :disabled="!failReason.trim()"
          @click="confirmMarkFailed"
        >
          {{ $t('payouts.mark_as_failed') }}
        </BaseButton>
      </template>
    </BaseModal>

    <!-- Cancel Payout Modal -->
    <BaseModal :show="showCancelModal" @close="showCancelModal = false">
      <template #header>
        <h3 class="text-lg font-medium">{{ $t('payouts.cancel_payout') }}</h3>
      </template>

      <div class="p-4">
        <p class="mb-4 text-sm text-gray-600">
          {{ $t('payouts.confirm_cancel', { name: selectedPayout?.partner_name, amount: formatMkd(selectedPayout?.amount) }) }}
        </p>

        <BaseInputGroup :label="$t('payouts.cancel_reason')" class="text-left">
          <BaseInput
            v-model="cancelReason"
            type="text"
            :placeholder="$t('payouts.cancel_reason_placeholder')"
          />
        </BaseInputGroup>
      </div>

      <template #footer>
        <BaseButton variant="primary-outline" class="mr-3" @click="showCancelModal = false">
          {{ $t('payouts.cancel') }}
        </BaseButton>
        <BaseButton
          variant="danger"
          :loading="isProcessing"
          :disabled="!cancelReason.trim()"
          @click="confirmCancel"
        >
          {{ $t('payouts.cancel_payout') }}
        </BaseButton>
      </template>
    </BaseModal>
  </BasePage>
</template>

<script setup>
import { ref, computed, reactive, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import AstronautIcon from '@/scripts/components/icons/empty/AstronautIcon.vue'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useGlobalStore } from '@/scripts/admin/stores/global'

const { t } = useI18n()
const router = useRouter()
const notificationStore = useNotificationStore()
const globalStore = useGlobalStore()

const mkdCurrency = { id: 0, name: 'Macedonian Denar', code: 'MKD', symbol: 'ден', precision: 2, thousand_separator: '.', decimal_separator: ',' }

function formatMkd(amount) {
  return `${parseFloat(amount || 0).toLocaleString('mk-MK', { minimumFractionDigits: 2 })} ден`
}

const tableComponent = ref(null)
const showFilters = ref(false)
const stats = ref(null)
const showPayModal = ref(false)
const selectedPayout = ref(null)
const paymentReference = ref('')
const isProcessing = ref(false)
const showFailModal = ref(false)
const failReason = ref('')
const showCancelModal = ref(false)
const cancelReason = ref('')

const filters = reactive({
  search: '',
  status: '',
  payout_method: '',
  date_from: '',
  date_to: '',
})

// Debounced search auto-trigger
let searchTimeout = null
watch(() => filters.search, () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    refreshTable()
  }, 400)
})

// Date filter auto-trigger
watch(() => filters.date_from, () => refreshTable())
watch(() => filters.date_to, () => refreshTable())

const payoutColumns = computed(() => [
  {
    key: 'partner_name',
    label: t('payouts.partner'),
    thClass: 'extra',
    tdClass: 'font-medium text-gray-900',
    sortable: false,
  },
  {
    key: 'amount',
    label: t('payouts.amount'),
    sortable: true,
  },
  {
    key: 'payout_method',
    label: t('payouts.method'),
    sortable: true,
  },
  {
    key: 'status',
    label: t('payouts.status'),
    sortable: true,
  },
  {
    key: 'payout_date',
    label: t('payouts.date'),
    sortable: true,
  },
  {
    key: 'bank_info',
    label: t('payouts.bank_info'),
    sortable: false,
  },
  {
    key: 'actions',
    label: '',
    tdClass: 'text-right',
  },
])

const showEmptyScreen = computed(() => totalPayouts.value === 0)
const totalPayouts = ref(null)

async function fetchData({ page, filter, sort }) {
  const params = {
    page,
    per_page: 15,
    search: filters.search,
    status: filters.status,
    payout_method: filters.payout_method,
    date_from: filters.date_from || undefined,
    date_to: filters.date_to || undefined,
    sort_by: sort.fieldName || 'created_at',
    sort_order: sort.order || 'desc',
  }

  const response = await axios.get('/payouts', { params })
  totalPayouts.value = response.data.total

  return {
    data: response.data.data,
    pagination: {
      totalPages: response.data.last_page,
      currentPage: response.data.current_page,
      count: response.data.total,
    },
  }
}

async function fetchStats() {
  try {
    const response = await axios.get('/payouts/stats')
    stats.value = response.data
  } catch (error) {
    console.error('Failed to fetch payout stats:', error)
  }
}

function toggleFilter() {
  showFilters.value = !showFilters.value
}

function clearFilter() {
  filters.search = ''
  filters.status = ''
  filters.payout_method = ''
  filters.date_from = ''
  filters.date_to = ''
  refreshTable()
}

function refreshTable() {
  tableComponent.value?.refresh()
}

function openMarkPaidModal(payout) {
  selectedPayout.value = payout
  paymentReference.value = ''
  showPayModal.value = true
}

async function confirmMarkPaid() {
  if (!paymentReference.value.trim()) return

  isProcessing.value = true
  try {
    await axios.post(`/payouts/${selectedPayout.value.id}/complete`, {
      payment_reference: paymentReference.value,
    })
    notificationStore.showNotification({
      type: 'success',
      message: t('payouts.mark_as_paid'),
    })
    showPayModal.value = false
    refreshTable()
    fetchStats()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('payouts.mark_as_failed'),
    })
  } finally {
    isProcessing.value = false
  }
}

function openMarkFailedModal(payout) {
  selectedPayout.value = payout
  failReason.value = ''
  showFailModal.value = true
}

async function confirmMarkFailed() {
  if (!failReason.value.trim()) return

  isProcessing.value = true
  try {
    await axios.post(`/payouts/${selectedPayout.value.id}/fail`, {
      reason: failReason.value,
    })
    notificationStore.showNotification({
      type: 'success',
      message: t('payouts.mark_as_failed'),
    })
    showFailModal.value = false
    refreshTable()
    fetchStats()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('payouts.mark_as_failed'),
    })
  } finally {
    isProcessing.value = false
  }
}

function openCancelModal(payout) {
  selectedPayout.value = payout
  cancelReason.value = ''
  showCancelModal.value = true
}

async function confirmCancel() {
  if (!cancelReason.value.trim()) return

  isProcessing.value = true
  try {
    await axios.post(`/payouts/${selectedPayout.value.id}/cancel`, {
      reason: cancelReason.value,
    })
    notificationStore.showNotification({
      type: 'success',
      message: t('payouts.cancel_payout'),
    })
    showCancelModal.value = false
    refreshTable()
    fetchStats()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('payouts.cancel_payout'),
    })
  } finally {
    isProcessing.value = false
  }
}

async function exportCsv() {
  try {
    const response = await axios.get('/payouts/export', {
      responseType: 'blob',
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `payouts-pending-${new Date().toISOString().slice(0, 10)}.csv`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: 'Failed to export CSV.',
    })
  }
}

onMounted(() => {
  fetchStats()
})
</script>
