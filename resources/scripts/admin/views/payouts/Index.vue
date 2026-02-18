<template>
  <BasePage>
    <!-- Page Header -->
    <BasePageHeader title="Payouts">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem title="Payouts" to="#" active />
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
            Export CSV
          </BaseButton>
        </div>
      </template>
    </BasePageHeader>

    <!-- Filters -->
    <BaseFilterWrapper :show="showFilters" class="mt-5" @clear="clearFilter">
      <BaseInputGroup label="Search" class="text-left">
        <BaseInput
          v-model="filters.search"
          type="text"
          name="search"
          autocomplete="off"
          placeholder="Partner name or email..."
        />
      </BaseInputGroup>

      <BaseInputGroup label="Status" class="text-left">
        <BaseSelect v-model="filters.status" @change="refreshTable">
          <option value="">All</option>
          <option value="pending">Pending</option>
          <option value="processing">Processing</option>
          <option value="completed">Completed</option>
          <option value="failed">Failed</option>
          <option value="cancelled">Cancelled</option>
        </BaseSelect>
      </BaseInputGroup>

      <BaseInputGroup label="Method" class="text-left">
        <BaseSelect v-model="filters.payout_method" @change="refreshTable">
          <option value="">All</option>
          <option value="bank_transfer">Bank Transfer</option>
          <option value="stripe_connect">Stripe Connect</option>
        </BaseSelect>
      </BaseInputGroup>
    </BaseFilterWrapper>

    <!-- Statistics Cards -->
    <div v-if="stats" class="grid grid-cols-1 gap-4 mt-6 md:grid-cols-4">
      <div class="p-4 bg-white border border-gray-200 rounded-lg">
        <div class="text-sm text-gray-500">Pending Amount</div>
        <div class="text-2xl font-semibold text-orange-600">
          <span v-if="globalStore.companySettings?.currency">
            <BaseFormatMoney
              :amount="stats.total_pending_amount"
              :currency="globalStore.companySettings.currency"
            />
          </span>
          <span v-else>{{ parseFloat(stats.total_pending_amount).toFixed(2) }}</span>
        </div>
      </div>
      <div class="p-4 bg-white border border-gray-200 rounded-lg">
        <div class="text-sm text-gray-500">Pending Payouts</div>
        <div class="text-2xl font-semibold text-orange-600">{{ stats.total_pending_count }}</div>
      </div>
      <div class="p-4 bg-white border border-gray-200 rounded-lg">
        <div class="text-sm text-gray-500">Completed This Month</div>
        <div class="text-2xl font-semibold text-green-600">
          <span v-if="globalStore.companySettings?.currency">
            <BaseFormatMoney
              :amount="stats.completed_this_month"
              :currency="globalStore.companySettings.currency"
            />
          </span>
          <span v-else>{{ parseFloat(stats.completed_this_month).toFixed(2) }}</span>
        </div>
      </div>
      <div class="p-4 bg-white border border-gray-200 rounded-lg">
        <div class="text-sm text-gray-500">Total Paid (All Time)</div>
        <div class="text-2xl font-semibold">
          <span v-if="globalStore.companySettings?.currency">
            <BaseFormatMoney
              :amount="stats.total_completed_all_time"
              :currency="globalStore.companySettings.currency"
            />
          </span>
          <span v-else>{{ parseFloat(stats.total_completed_all_time).toFixed(2) }}</span>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <BaseEmptyPlaceholder
      v-show="showEmptyScreen"
      title="No payouts"
      description="No payout records found. Payouts are created automatically when partner commissions are processed."
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
          <span v-if="globalStore.companySettings?.currency">
            <BaseFormatMoney
              :amount="row.data.amount || 0"
              :currency="globalStore.companySettings.currency"
            />
          </span>
          <span v-else>
            {{ parseFloat(row.data.amount || 0).toFixed(2) }} {{ row.data.currency || 'MKD' }}
          </span>
        </template>

        <template #cell-payout_method="{ row }">
          <span
            class="px-2 py-1 text-xs font-medium rounded"
            :class="row.data.payout_method === 'stripe_connect'
              ? 'bg-purple-100 text-purple-800'
              : 'bg-blue-100 text-blue-800'"
          >
            {{ row.data.payout_method === 'stripe_connect' ? 'Stripe' : 'Bank Transfer' }}
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
            {{ row.data.status }}
          </span>
        </template>

        <template #cell-payout_date="{ row }">
          <span class="text-sm text-gray-600">
            {{ row.data.payout_date ? new Date(row.data.payout_date).toLocaleDateString() : '-' }}
          </span>
        </template>

        <template #cell-bank_info="{ row }">
          <div class="text-xs">
            <div class="text-gray-700">{{ row.data.partner_bank_name }}</div>
            <div class="text-gray-400 font-mono">{{ row.data.partner_bank_account }}</div>
          </div>
        </template>

        <template #cell-actions="{ row }">
          <BaseDropdown>
            <template #activator>
              <BaseIcon name="EllipsisHorizontalIcon" class="h-5 text-gray-500" />
            </template>

            <BaseDropdownItem @click="$router.push(`/admin/payouts/${row.data.id}/view`)">
              <BaseIcon name="EyeIcon" class="mr-3 text-gray-600" />
              View
            </BaseDropdownItem>

            <BaseDropdownItem
              v-if="row.data.status === 'pending' || row.data.status === 'processing'"
              @click="openMarkPaidModal(row.data)"
            >
              <BaseIcon name="CheckCircleIcon" class="mr-3 text-green-600" />
              Mark as Paid
            </BaseDropdownItem>

            <BaseDropdownItem
              v-if="row.data.status === 'pending' || row.data.status === 'processing'"
              @click="cancelPayout(row.data)"
            >
              <BaseIcon name="XCircleIcon" class="mr-3 text-red-600" />
              Cancel
            </BaseDropdownItem>
          </BaseDropdown>
        </template>
      </BaseTable>
    </div>

    <!-- Mark as Paid Modal -->
    <BaseModal :show="showPayModal" @close="showPayModal = false">
      <template #header>
        <h3 class="text-lg font-medium">Mark Payout as Paid</h3>
      </template>

      <div class="p-4">
        <p class="mb-4 text-sm text-gray-600">
          Confirm payment to <strong>{{ selectedPayout?.partner_name }}</strong>
          for <strong>{{ selectedPayout?.amount }} {{ selectedPayout?.currency || 'MKD' }}</strong>
        </p>

        <BaseInputGroup label="Payment Reference (SEPA / Transaction ID)" class="text-left">
          <BaseInput
            v-model="paymentReference"
            type="text"
            placeholder="e.g. SEPA-2026-02-001"
          />
        </BaseInputGroup>
      </div>

      <template #footer>
        <BaseButton variant="primary-outline" class="mr-3" @click="showPayModal = false">
          Cancel
        </BaseButton>
        <BaseButton
          :loading="isProcessing"
          :disabled="!paymentReference.trim()"
          @click="confirmMarkPaid"
        >
          Confirm Payment
        </BaseButton>
      </template>
    </BaseModal>
  </BasePage>
</template>

<script setup>
import { ref, computed, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import AstronautIcon from '@/scripts/components/icons/empty/AstronautIcon.vue'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useGlobalStore } from '@/scripts/admin/stores/global'

const router = useRouter()
const notificationStore = useNotificationStore()
const globalStore = useGlobalStore()

const tableComponent = ref(null)
const showFilters = ref(false)
const stats = ref(null)
const showPayModal = ref(false)
const selectedPayout = ref(null)
const paymentReference = ref('')
const isProcessing = ref(false)

const filters = reactive({
  search: '',
  status: '',
  payout_method: '',
})

const payoutColumns = ref([
  {
    key: 'partner_name',
    label: 'Partner',
    thClass: 'extra',
    tdClass: 'font-medium text-gray-900',
    sortable: false,
  },
  {
    key: 'amount',
    label: 'Amount',
    sortable: true,
  },
  {
    key: 'payout_method',
    label: 'Method',
    sortable: true,
  },
  {
    key: 'status',
    label: 'Status',
    sortable: true,
  },
  {
    key: 'payout_date',
    label: 'Date',
    sortable: true,
  },
  {
    key: 'bank_info',
    label: 'Bank Info',
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
      message: 'Payout marked as completed.',
    })
    showPayModal.value = false
    refreshTable()
    fetchStats()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || 'Failed to mark payout as completed.',
    })
  } finally {
    isProcessing.value = false
  }
}

async function cancelPayout(payout) {
  if (!confirm(`Cancel payout of ${payout.amount} ${payout.currency || 'MKD'} to ${payout.partner_name}?`)) return

  try {
    await axios.post(`/payouts/${payout.id}/cancel`, {
      reason: 'Cancelled by admin',
    })
    notificationStore.showNotification({
      type: 'success',
      message: 'Payout cancelled. Commission events released back to unpaid.',
    })
    refreshTable()
    fetchStats()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || 'Failed to cancel payout.',
    })
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
