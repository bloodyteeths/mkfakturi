<template>
  <BasePage class="payments">
    <SendPaymentModal />
    <BasePageHeader :title="$t('payments.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('payments.payment', 2)" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <!-- Actions for Received Payments tab -->
        <template v-if="activeTab === 'received'">
          <BaseButton
            v-show="paymentStore.paymentTotalCount"
            variant="primary-outline"
            @click="toggleFilter"
          >
            {{ $t('general.filter') }}

            <template #right="slotProps">
              <BaseIcon
                v-if="!showFilters"
                :class="slotProps.class"
                name="FunnelIcon"
              />
              <BaseIcon v-else name="XMarkIcon" :class="slotProps.class" />
            </template>
          </BaseButton>

          <ExportButton
            v-show="paymentStore.paymentTotalCount"
            type="payments"
            :filters="filters"
            class="ml-4"
          />

          <BaseButton
            v-if="userStore.hasAbilities(abilities.CREATE_PAYMENT)"
            variant="primary"
            class="ml-4"
            @click="$router.push('/admin/payments/create')"
          >
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>

            {{ $t('payments.add_payment') }}
          </BaseButton>
        </template>

        <!-- Actions for Bill Payments tab -->
        <template v-if="activeTab === 'bill_payments'">
          <router-link to="/admin/payment-orders/create">
            <BaseButton variant="primary">
              <template #left="slotProps">
                <BaseIcon name="PlusIcon" :class="slotProps.class" />
              </template>
              {{ bp('create_payment_order') }}
            </BaseButton>
          </router-link>
        </template>
      </template>
    </BasePageHeader>

    <!-- Tab Switcher -->
    <div class="mt-4 mb-6 flex border-b border-gray-200">
      <button
        class="px-4 py-2 text-sm font-medium border-b-2 transition-colors duration-150"
        :class="activeTab === 'received'
          ? 'border-primary-500 text-primary-600'
          : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
        @click="switchTab('received')"
      >
        {{ bp('tab_received') }}
      </button>
      <button
        class="px-4 py-2 text-sm font-medium border-b-2 transition-colors duration-150"
        :class="activeTab === 'bill_payments'
          ? 'border-primary-500 text-primary-600'
          : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
        @click="switchTab('bill_payments')"
      >
        {{ bp('tab_bill_payments') }}
        <span
          v-if="billPaymentTotalCount > 0"
          class="ml-1.5 inline-flex items-center rounded-full bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-700"
        >
          {{ billPaymentTotalCount }}
        </span>
      </button>
    </div>

    <!-- ==================== RECEIVED PAYMENTS TAB ==================== -->
    <template v-if="activeTab === 'received'">
      <BaseFilterWrapper :show="showFilters" class="mt-3" @clear="clearFilter">
        <BaseInputGroup :label="$t('payments.customer')">
          <BaseCustomerSelectInput
            v-model="filters.customer_id"
            :placeholder="$t('customers.type_or_click')"
            value-prop="id"
            label="name"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('payments.payment_number')">
          <BaseInput v-model="filters.payment_number">
            <template #left="slotProps">
              <BaseIcon name="HashtagIcon" :class="slotProps.class" />
            </template>
          </BaseInput>
        </BaseInputGroup>

        <BaseInputGroup :label="$t('payments.payment_mode')">
          <BaseMultiselect
            v-model="filters.payment_mode"
            value-prop="id"
            track-by="name"
            :filter-results="false"
            label="name"
            resolve-on-load
            :delay="500"
            searchable
            :options="searchPayment"
          />
        </BaseInputGroup>
      </BaseFilterWrapper>

      <BaseEmptyPlaceholder
        v-if="showEmptyScreen"
        :title="$t('payments.no_payments')"
        :description="$t('payments.list_of_payments')"
      >
        <CapsuleIcon class="mt-5 mb-4" />

        <template
          v-if="userStore.hasAbilities(abilities.CREATE_PAYMENT)"
          #actions
        >
          <BaseButton
            variant="primary-outline"
            @click="$router.push('/admin/payments/create')"
          >
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ $t('payments.add_new_payment') }}
          </BaseButton>
        </template>
      </BaseEmptyPlaceholder>

      <div v-show="!showEmptyScreen" class="relative table-container">
        <!-- Multiple Select Actions -->
        <div class="relative flex items-center justify-end h-5">
          <BaseDropdown v-if="paymentStore.selectedPayments.length">
            <template #activator>
              <span
                class="
                  flex
                  text-sm
                  font-medium
                  cursor-pointer
                  select-none
                  text-primary-400
                "
              >
                {{ $t('general.actions') }}
                <BaseIcon name="ChevronDownIcon" />
              </span>
            </template>
            <BaseDropdownItem @click="removeMultiplePayments">
              <BaseIcon name="TrashIcon" class="mr-3 text-gray-600" />
              {{ $t('general.delete') }}
            </BaseDropdownItem>
          </BaseDropdown>
        </div>

        <BaseTable
          ref="tableComponent"
          :data="fetchData"
          :columns="paymentColumns"
          :placeholder-count="paymentStore.paymentTotalCount >= 20 ? 10 : 5"
          class="mt-3"
        >
          <!-- Select All Checkbox -->
          <template #header>
            <div class="absolute items-center left-6 top-2.5 select-none">
              <BaseCheckbox
                v-model="selectAllFieldStatus"
                variant="primary"
                @change="paymentStore.selectAllPayments"
              />
            </div>
          </template>

          <template #cell-status="{ row }">
            <div class="relative block">
              <BaseCheckbox
                :id="row.id"
                v-model="selectField"
                :value="row.data.id"
                variant="primary"
              />
            </div>
          </template>

          <template #cell-payment_date="{ row }">
            {{ row.data.formatted_payment_date }}
          </template>

          <template #cell-payment_number="{ row }">
            <router-link
              :to="{ path: `payments/${row.data.id}/view` }"
              class="font-medium text-primary-500"
            >
              {{ row.data.payment_number }}
            </router-link>
          </template>

          <template #cell-name="{ row }">
            <BaseText :text="row.data.customer.name" tag="span" />
          </template>

          <template #cell-payment_mode="{ row }">
            <span>
              {{ row.data.payment_method ? row.data.payment_method.name : '-' }}
            </span>
          </template>

          <template #cell-invoice_number="{ row }">
            <span>
              {{
                row?.data?.invoice?.invoice_number
                  ? row?.data?.invoice?.invoice_number
                  : '-'
              }}
            </span>
          </template>

          <template #cell-amount="{ row }">
            <BaseFormatMoney
              :amount="row.data.amount"
              :currency="row.data.customer.currency"
            />
          </template>

          <template v-if="hasAtleastOneAbility()" #cell-actions="{ row }">
            <PaymentDropdown :row="row.data" :table="tableComponent" />
          </template>
        </BaseTable>
      </div>
    </template>

    <!-- ==================== BILL PAYMENTS TAB ==================== -->
    <template v-if="activeTab === 'bill_payments'">
      <!-- Search filter -->
      <div class="mb-4">
        <BaseInput
          v-model="billPaymentSearch"
          :placeholder="bp('search_placeholder')"
        >
          <template #left="slotProps">
            <BaseIcon name="MagnifyingGlassIcon" :class="slotProps.class" />
          </template>
        </BaseInput>
      </div>

      <!-- Loading state -->
      <div v-if="isFetchingBillPayments" class="rounded-lg bg-white shadow p-6">
        <div v-for="i in 5" :key="i" class="mb-4 flex animate-pulse space-x-4">
          <div class="h-4 w-24 rounded bg-gray-200"></div>
          <div class="h-4 w-20 rounded bg-gray-200"></div>
          <div class="h-4 w-32 rounded bg-gray-200"></div>
          <div class="h-4 w-20 rounded bg-gray-200"></div>
          <div class="h-4 w-16 rounded bg-gray-200"></div>
        </div>
      </div>

      <!-- Empty state -->
      <div
        v-else-if="billPayments.length === 0 && !isFetchingBillPayments"
        class="rounded-lg bg-white shadow p-12 text-center"
      >
        <BaseIcon name="BanknotesIcon" class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ bp('no_bill_payments') }}</h3>
        <p class="mt-1 text-sm text-gray-500">{{ bp('no_bill_payments_description') }}</p>
        <div class="mt-4">
          <router-link to="/admin/payment-orders/create">
            <BaseButton variant="primary">
              <template #left="slotProps">
                <BaseIcon name="PlusIcon" :class="slotProps.class" />
              </template>
              {{ bp('create_payment_order') }}
            </BaseButton>
          </router-link>
        </div>
      </div>

      <!-- Bill Payments Table -->
      <div v-else class="rounded-lg bg-white shadow overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                  {{ bp('date') }}
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                  {{ bp('payment_number') }}
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                  {{ bp('bill_number') }}
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                  {{ bp('supplier') }}
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                  {{ bp('payment_method') }}
                </th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">
                  {{ bp('amount') }}
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                  {{ bp('notes') }}
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr
                v-for="payment in billPayments"
                :key="payment.id"
                class="hover:bg-gray-50"
              >
                <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900">
                  {{ payment.formatted_payment_date }}
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-primary-600">
                  {{ payment.payment_number }}
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">
                  <router-link
                    v-if="payment.bill"
                    :to="`/admin/bills/${payment.bill_id}/view`"
                    class="text-primary-500 hover:text-primary-700"
                  >
                    {{ payment.bill.bill_number }}
                  </router-link>
                  <span v-else class="text-gray-400">-</span>
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">
                  {{ payment.bill?.supplier?.name || '-' }}
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">
                  {{ payment.payment_method?.name || '-' }}
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-sm text-right font-medium text-gray-900">
                  {{ formatBillPaymentAmount(payment) }}
                </td>
                <td class="px-4 py-3 text-sm text-gray-500 max-w-xs truncate">
                  {{ payment.notes || '-' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div
          v-if="billPaymentPagination.totalPages > 1"
          class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3"
        >
          <div class="text-sm text-gray-700">
            {{ billPaymentPagination.currentPage }} / {{ billPaymentPagination.totalPages }}
          </div>
          <div class="flex gap-2">
            <BaseButton
              variant="primary-outline"
              size="sm"
              :disabled="billPaymentPagination.currentPage <= 1"
              @click="loadBillPayments(billPaymentPagination.currentPage - 1)"
            >
              &larr;
            </BaseButton>
            <BaseButton
              variant="primary-outline"
              size="sm"
              :disabled="billPaymentPagination.currentPage >= billPaymentPagination.totalPages"
              @click="loadBillPayments(billPaymentPagination.currentPage + 1)"
            >
              &rarr;
            </BaseButton>
          </div>
        </div>
      </div>
    </template>
  </BasePage>
</template>

<script setup>
import { debouncedWatch } from '@vueuse/core'

import { ref, reactive, computed, onUnmounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useDialogStore } from '@/scripts/stores/dialog'
import { usePaymentStore } from '@/scripts/admin/stores/payment'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useUserStore } from '@/scripts/admin/stores/user'
import abilities from '@/scripts/admin/stub/abilities'
import CapsuleIcon from '@/scripts/components/icons/empty/CapsuleIcon.vue'
import ExportButton from '@/scripts/admin/components/ExportButton.vue'
import PaymentDropdown from '@/scripts/admin/components/dropdowns/PaymentIndexDropdown.vue'
import SendPaymentModal from '@/scripts/admin/components/modal-components/SendPaymentModal.vue'
import bpMessages from '@/scripts/admin/i18n/bill-payments.js'

const { t } = useI18n()
const route = useRoute()
let showFilters = ref(false)
let isFetchingInitialData = ref(true)
let tableComponent = ref(null)

// Tab state
const activeTab = ref(route.query.tab === 'bill_payments' ? 'bill_payments' : 'received')

// Bill payments i18n helper
const locale = document.documentElement.lang || 'mk'
function bp(key) {
  return bpMessages[locale]?.bill_payments?.[key]
    || bpMessages['en']?.bill_payments?.[key]
    || key
}

// Locale for formatting
const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }
const formattedLocale = localeMap[locale] || 'mk-MK'

const filters = reactive({
  customer_id: '',
  payment_mode: '',
  payment_number: '',
  project_id: route.query.project_id || '',
})

const paymentStore = usePaymentStore()
const companyStore = useCompanyStore()
const dialogStore = useDialogStore()
const userStore = useUserStore()

const showEmptyScreen = computed(() => {
  return !paymentStore.paymentTotalCount && !isFetchingInitialData.value
})

const paymentColumns = computed(() => {
  return [
    {
      key: 'status',
      sortable: false,
      thClass: 'extra w-10',
      tdClass: 'text-left text-sm font-medium extra',
    },
    {
      key: 'payment_date',
      label: t('payments.date'),
      thClass: 'extra',
      tdClass: 'font-medium text-gray-900',
    },
    { key: 'payment_number', label: t('payments.payment_number') },
    { key: 'name', label: t('payments.customer') },
    { key: 'payment_mode', label: t('payments.payment_mode') },
    { key: 'invoice_number', label: t('payments.invoice') },
    { key: 'amount', label: t('payments.amount') },
    {
      key: 'actions',
      label: '',
      tdClass: 'text-right text-sm font-medium',
      sortable: false,
    },
  ]
})

const selectField = computed({
  get: () => paymentStore.selectedPayments,
  set: (value) => {
    return paymentStore.selectPayment(value)
  },
})

const selectAllFieldStatus = computed({
  get: () => paymentStore.selectAllField,
  set: (value) => {
    return paymentStore.setSelectAllState(value)
  },
})

debouncedWatch(
  filters,
  () => {
    setFilters()
  },
  { debounce: 500 }
)

onUnmounted(() => {
  if (paymentStore.selectAllField) {
    paymentStore.selectAllPayments()
  }
})

paymentStore.fetchPaymentModes({ limit: 'all' })

async function searchPayment(search) {
  let res = await paymentStore.fetchPaymentModes({ search })
  return res.data.data
}

function hasAtleastOneAbility() {
  return userStore.hasAbilities([
    abilities.DELETE_PAYMENT,
    abilities.EDIT_PAYMENT,
    abilities.VIEW_PAYMENT,
    abilities.SEND_PAYMENT,
  ])
}

async function fetchData({ page, filter, sort }) {
  let data = {
    customer_id: filters.customer_id,
    payment_method_id:
      filters.payment_mode !== null ? filters.payment_mode : '',
    payment_number: filters.payment_number,
    project_id: filters.project_id,
    orderByField: sort.fieldName || 'created_at',
    orderBy: sort.order || 'desc',
    page,
  }

  isFetchingInitialData.value = true

  let response = await paymentStore.fetchPayments(data)

  isFetchingInitialData.value = false

  return {
    data: response.data.data,
    pagination: {
      totalPages: response.data.meta.last_page,
      currentPage: page,
      totalCount: response.data.meta.total,
      limit: 10,
    },
  }
}

function refreshTable() {
  tableComponent.value && tableComponent.value.refresh()
}

function setFilters() {
  refreshTable()
}

function clearFilter() {
  filters.customer_id = ''
  filters.payment_mode = ''
  filters.payment_number = ''
  filters.project_id = ''
}

function toggleFilter() {
  if (showFilters.value) {
    clearFilter()
  }

  showFilters.value = !showFilters.value
}

function removeMultiplePayments() {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('payments.confirm_delete', 2),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then((res) => {
      if (res) {
        paymentStore.deleteMultiplePayments().then((response) => {
          if (response.data.success) {
            refreshTable()
          }
        })
      }
    })
}

// ==================== BILL PAYMENTS TAB ====================

const billPayments = ref([])
const billPaymentTotalCount = ref(0)
const isFetchingBillPayments = ref(false)
const billPaymentSearch = ref('')
const billPaymentPagination = ref({
  currentPage: 1,
  totalPages: 1,
})

function switchTab(tab) {
  activeTab.value = tab
  if (tab === 'bill_payments' && billPayments.value.length === 0 && !isFetchingBillPayments.value) {
    loadBillPayments(1)
  }
}

async function loadBillPayments(page = 1) {
  isFetchingBillPayments.value = true
  try {
    const params = {
      page,
      limit: 10,
      orderByField: 'payment_date',
      orderBy: 'desc',
    }
    if (billPaymentSearch.value) {
      params.search = billPaymentSearch.value
    }
    const response = await window.axios.get('/bill-payments', { params })
    const result = response.data
    billPayments.value = result.data || []
    billPaymentTotalCount.value = result.meta?.bill_payment_total_count || 0
    billPaymentPagination.value = {
      currentPage: result.meta?.current_page || page,
      totalPages: result.meta?.last_page || 1,
    }
  } catch (error) {
    console.error('Failed to load bill payments:', error)
    billPayments.value = []
  } finally {
    isFetchingBillPayments.value = false
  }
}

function formatBillPaymentAmount(payment) {
  const amount = payment.amount || 0
  const value = Math.abs(amount) / 100
  const sign = amount < 0 ? '-' : ''
  const currencySymbol = payment.bill?.supplier?.currency?.symbol || ''
  const formatted = new Intl.NumberFormat(formattedLocale, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(value)
  return sign + formatted + (currencySymbol ? ' ' + currencySymbol : '')
}

// Debounced search for bill payments
debouncedWatch(
  billPaymentSearch,
  () => {
    if (activeTab.value === 'bill_payments') {
      loadBillPayments(1)
    }
  },
  { debounce: 500 }
)

// If tab=bill_payments in query, load immediately
if (activeTab.value === 'bill_payments') {
  loadBillPayments(1)
}
</script>
<!-- CLAUDE-CHECKPOINT -->
