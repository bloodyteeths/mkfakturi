<template>
  <BasePage>
    <!-- Page Header -->
    <BasePageHeader :title="$t('fiscal_receipts.title')">
      <template #actions>
        <BaseButton
          v-show="hasDevices"
          variant="primary-outline"
          @click="toggleFilter"
        >
          {{ $t('fiscal_receipts.filter') }}
          <template #right="slotProps">
            <BaseIcon
              v-if="!showFilters"
              name="FunnelIcon"
              :class="slotProps.class"
            />
            <BaseIcon v-else name="XMarkIcon" :class="slotProps.class" />
          </template>
        </BaseButton>

        <BaseInput
          v-show="hasDevices"
          v-model="filters.search"
          :placeholder="$t('fiscal_receipts.search_placeholder')"
          class="w-48"
          type="search"
        />

        <button
          v-show="hasDevices"
          type="button"
          class="inline-flex items-center rounded-md px-3 py-1.5 text-sm font-medium"
          :class="showDailySummary
            ? 'bg-primary-600 text-white'
            : 'bg-white text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50'"
          @click="toggleSummary"
        >
          {{ $t('fiscal_receipts.daily_summary') }}
        </button>

        <BaseButton
          v-show="hasDevices"
          variant="primary-outline"
          @click="exportCsv"
        >
          {{ $t('fiscal_receipts.export_csv') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Filter Panel -->
    <BaseFilterWrapper :show="showFilters" class="mt-5" @clear="clearFilter">
      <BaseInputGroup :label="$t('fiscal_receipts.source')">
        <select
          v-model="filters.source"
          class="w-full rounded-md border-gray-300 text-sm"
          @change="refreshTable"
        >
          <option value="">{{ $t('fiscal_receipts.all_sources') }}</option>
          <option value="webserial">WebSerial</option>
          <option value="erpnet-fp">ErpNet.FP</option>
          <option value="manual">{{ $t('fiscal_receipts.manual') }}</option>
        </select>
      </BaseInputGroup>

      <BaseInputGroup v-if="devices.length > 0" :label="$t('fiscal_receipts.device')">
        <select
          v-model="filters.deviceId"
          class="w-full rounded-md border-gray-300 text-sm"
          @change="refreshTable"
        >
          <option value="">{{ $t('fiscal_receipts.all_devices') }}</option>
          <option v-for="d in devices" :key="d.id" :value="d.id">
            {{ d.name || d.device_type }}
          </option>
        </select>
      </BaseInputGroup>

      <BaseInputGroup :label="$t('fiscal_receipts.payment_type')">
        <select
          v-model="filters.paymentType"
          class="w-full rounded-md border-gray-300 text-sm"
          @change="refreshTable"
        >
          <option value="">{{ $t('fiscal_receipts.all_payments') }}</option>
          <option value="cash">{{ $t('fiscal_receipts.cash') }}</option>
          <option value="card">{{ $t('fiscal_receipts.card') }}</option>
          <option value="check">{{ $t('fiscal_receipts.check') }}</option>
          <option value="bank_transfer">{{ $t('fiscal_receipts.bank_transfer') }}</option>
        </select>
      </BaseInputGroup>

      <BaseInputGroup :label="$t('fiscal_receipts.date_from')">
        <input
          v-model="filters.fromDate"
          type="date"
          class="w-full rounded-md border-gray-300 text-sm"
          @change="refreshTable"
        />
      </BaseInputGroup>

      <div
        class="hidden w-8 h-0 mx-4 border border-gray-400 border-solid xl:block"
        style="margin-top: 1.5rem"
      />

      <BaseInputGroup :label="$t('fiscal_receipts.date_to')">
        <input
          v-model="filters.toDate"
          type="date"
          class="w-full rounded-md border-gray-300 text-sm"
          @change="refreshTable"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('fiscal_receipts.storno')">
        <label class="flex items-center gap-2 mt-2 cursor-pointer">
          <input
            v-model="filters.stornoOnly"
            type="checkbox"
            class="rounded border-gray-300 text-red-600 focus:ring-red-500"
            @change="refreshTable"
          />
          <span class="text-sm text-gray-700">{{ $t('fiscal_receipts.storno') }}</span>
        </label>
      </BaseInputGroup>
    </BaseFilterWrapper>

    <!-- Summary Card -->
    <div
      v-if="showDailySummary && summaryData.count > 0"
      class="mt-5 rounded-lg bg-gray-50 border border-gray-200 p-5"
    >
      <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ $t('fiscal_receipts.summary_all_pages') }}</h3>
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-4">
        <div>
          <p class="text-xs text-gray-500">{{ $t('fiscal_receipts.total_receipts') }}</p>
          <p class="text-lg font-semibold text-gray-900">{{ summaryData.count }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">{{ $t('fiscal_receipts.storno_count') }}</p>
          <p class="text-lg font-semibold text-red-600">{{ summaryData.storno_count }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">{{ $t('fiscal_receipts.total_amount') }}</p>
          <p class="text-lg font-semibold text-gray-900">{{ formatMoney(summaryData.total_amount) }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">{{ $t('fiscal_receipts.total_vat') }}</p>
          <p class="text-lg font-semibold text-gray-900">{{ formatMoney(summaryData.total_vat) }}</p>
        </div>
        <div v-if="summaryData.tax_a > 0">
          <p class="text-xs text-gray-500">{{ $t('fiscal_receipts.tax_group_a') }}</p>
          <p class="text-sm font-medium text-gray-800">{{ formatMoney(summaryData.tax_a) }}</p>
        </div>
        <div v-if="summaryData.tax_b > 0">
          <p class="text-xs text-gray-500">{{ $t('fiscal_receipts.tax_group_b') }}</p>
          <p class="text-sm font-medium text-gray-800">{{ formatMoney(summaryData.tax_b) }}</p>
        </div>
        <div v-if="summaryData.tax_v > 0">
          <p class="text-xs text-gray-500">{{ $t('fiscal_receipts.tax_group_v') }}</p>
          <p class="text-sm font-medium text-gray-800">{{ formatMoney(summaryData.tax_v) }}</p>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <BaseEmptyPlaceholder
      v-show="showEmptyScreen"
      :title="$t('fiscal_receipts.no_receipts_title')"
      :description="$t('fiscal_receipts.no_receipts_desc')"
    >
      <svg class="h-16 w-16 text-gray-300 mt-5 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18.75 9H5.25" />
      </svg>
      <template #actions>
        <router-link
          to="/admin/settings/fiscal-devices"
          class="inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700"
        >
          {{ $t('fiscal_receipts.go_to_settings') }}
        </router-link>
      </template>
    </BaseEmptyPlaceholder>

    <!-- Receipts Table -->
    <div v-show="!showEmptyScreen" class="relative table-container">
      <BaseTable
        ref="tableComponent"
        :data="fetchData"
        :columns="columns"
        class="mt-3"
      >
        <template #cell-receipt_number="{ row }">
          <span
            class="font-mono text-sm font-medium text-primary-600 cursor-pointer hover:text-primary-700"
            @click="openDetail(row.data)"
          >
            {{ row.data.receipt_number }}
          </span>
        </template>

        <template #cell-invoice="{ row }">
          <router-link
            v-if="row.data.invoice"
            :to="`/admin/invoices/${row.data.invoice_id}/view`"
            class="text-sm font-medium text-primary-600 hover:text-primary-700"
          >
            {{ row.data.invoice.invoice_number || `#${row.data.invoice_id}` }}
          </router-link>
          <span v-else class="text-xs text-gray-400">—</span>
        </template>

        <template #cell-amount="{ row }">
          <div class="flex items-center gap-1.5">
            <span
              v-if="row.data.is_storno"
              class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-bold bg-red-100 text-red-700"
            >
              {{ $t('fiscal_receipts.storno_badge') }}
            </span>
            <span
              class="text-sm font-medium"
              :class="row.data.is_storno ? 'text-red-600' : 'text-gray-900'"
            >
              {{ row.data.is_storno ? '−' : '' }}{{ formatMoney(row.data.amount) }}
            </span>
          </div>
        </template>

        <template #cell-payment_type="{ row }">
          <span
            v-if="row.data.payment_type"
            class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium"
            :class="paymentBadgeClass(row.data.payment_type)"
          >
            {{ paymentLabel(row.data.payment_type) }}
          </span>
          <span v-else class="text-xs text-gray-400">—</span>
        </template>

        <template #cell-source="{ row }">
          <span
            class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium"
            :class="sourceBadgeClass(row.data.source)"
          >
            {{ sourceLabel(row.data.source) }}
          </span>
        </template>

        <template #cell-operator="{ row }">
          <span class="text-sm text-gray-700">
            {{ row.data.operator_name || row.data.operator?.name || '—' }}
          </span>
        </template>

        <template #cell-created_at="{ row }">
          <span class="text-sm text-gray-600">
            {{ formatDate(row.data.created_at) }}
          </span>
        </template>

        <template #cell-actions="{ row }">
          <BaseDropdown>
            <template #activator>
              <BaseIcon name="DotsHorizontalIcon" class="h-5 text-gray-500 cursor-pointer" />
            </template>
            <BaseDropdownItem @click="openDetail(row.data)">
              <BaseIcon name="EyeIcon" class="h-5 mr-3 text-gray-600" />
              {{ $t('fiscal_receipts.view_detail') }}
            </BaseDropdownItem>
            <BaseDropdownItem
              v-if="row.data.invoice_id"
              @click="$router.push(`/admin/invoices/${row.data.invoice_id}/view`)"
            >
              <BaseIcon name="DocumentTextIcon" class="h-5 mr-3 text-gray-600" />
              {{ $t('fiscal_receipts.view_invoice') }}
            </BaseDropdownItem>
            <BaseDropdownItem
              v-if="!row.data.is_storno"
              @click="confirmStorno(row.data)"
            >
              <BaseIcon name="ReceiptRefundIcon" class="h-5 mr-3 text-red-500" />
              {{ $t('fiscal_receipts.create_storno') }}
            </BaseDropdownItem>
          </BaseDropdown>
        </template>
      </BaseTable>
    </div>

    <!-- Detail Slide-over Panel -->
    <div
      v-if="selectedReceipt"
      class="fixed inset-0 z-50 flex justify-end"
    >
      <div
        class="fixed inset-0 bg-black/30"
        @click="selectedReceipt = null"
      />
      <div class="relative w-full max-w-[480px] h-full bg-white shadow-xl overflow-y-auto">
        <!-- Sticky header -->
        <div class="sticky top-0 z-10 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900">
            {{ $t('fiscal_receipts.receipt_detail') }}
          </h2>
          <button
            type="button"
            class="rounded-md p-1 text-gray-400 hover:text-gray-600"
            @click="selectedReceipt = null"
          >
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
          </button>
        </div>

        <div class="px-6 py-5 space-y-5">
          <!-- Storno alert -->
          <div
            v-if="selectedReceipt.is_storno"
            class="rounded-md bg-red-50 border border-red-200 px-4 py-3"
          >
            <span class="text-sm font-semibold text-red-700">{{ $t('fiscal_receipts.storno_badge') }}</span>
            <span class="ml-1 text-sm text-red-600">{{ $t('fiscal_receipts.is_storno') }}</span>
          </div>

          <!-- Main fields -->
          <dl class="grid grid-cols-2 gap-x-4 gap-y-3">
            <div>
              <dt class="text-xs text-gray-500">{{ $t('fiscal_receipts.receipt_number') }}</dt>
              <dd class="text-sm font-mono font-medium text-gray-900">{{ selectedReceipt.receipt_number }}</dd>
            </div>
            <div>
              <dt class="text-xs text-gray-500">{{ $t('fiscal_receipts.fiscal_id') }}</dt>
              <dd class="text-sm font-mono text-gray-900">{{ selectedReceipt.fiscal_id }}</dd>
            </div>
            <div>
              <dt class="text-xs text-gray-500">{{ $t('fiscal_receipts.unique_sale_number') }}</dt>
              <dd class="text-sm font-mono text-gray-900">{{ selectedReceipt.unique_sale_number || '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs text-gray-500">{{ $t('fiscal_receipts.device') }}</dt>
              <dd class="text-sm text-gray-900">{{ selectedReceipt.fiscal_device?.name || selectedReceipt.fiscal_device?.device_type || '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs text-gray-500">{{ $t('fiscal_receipts.date') }}</dt>
              <dd class="text-sm text-gray-900">{{ formatDate(selectedReceipt.created_at) }}</dd>
            </div>
            <div>
              <dt class="text-xs text-gray-500">{{ $t('fiscal_receipts.source') }}</dt>
              <dd>
                <span
                  class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium"
                  :class="sourceBadgeClass(selectedReceipt.source)"
                >
                  {{ sourceLabel(selectedReceipt.source) }}
                </span>
              </dd>
            </div>
            <div>
              <dt class="text-xs text-gray-500">{{ $t('fiscal_receipts.operator') }}</dt>
              <dd class="text-sm text-gray-900">{{ selectedReceipt.operator_name || selectedReceipt.operator?.name || '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs text-gray-500">{{ $t('fiscal_receipts.payment_type') }}</dt>
              <dd>
                <span
                  v-if="selectedReceipt.payment_type"
                  class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium"
                  :class="paymentBadgeClass(selectedReceipt.payment_type)"
                >
                  {{ paymentLabel(selectedReceipt.payment_type) }}
                </span>
                <span v-else class="text-sm text-gray-400">—</span>
              </dd>
            </div>
            <div>
              <dt class="text-xs text-gray-500">{{ $t('fiscal_receipts.invoice') }}</dt>
              <dd>
                <router-link
                  v-if="selectedReceipt.invoice"
                  :to="`/admin/invoices/${selectedReceipt.invoice_id}/view`"
                  class="text-sm font-medium text-primary-600 hover:text-primary-700"
                  @click="selectedReceipt = null"
                >
                  {{ selectedReceipt.invoice.invoice_number || `#${selectedReceipt.invoice_id}` }}
                </router-link>
                <span v-else class="text-sm text-gray-400">—</span>
              </dd>
            </div>
          </dl>

          <!-- Amount + VAT -->
          <div class="rounded-md bg-gray-50 p-4">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm text-gray-600">{{ $t('fiscal_receipts.amount') }}</span>
              <span
                class="text-lg font-semibold"
                :class="selectedReceipt.is_storno ? 'text-red-600' : 'text-gray-900'"
              >
                {{ selectedReceipt.is_storno ? '−' : '' }}{{ formatMoney(selectedReceipt.amount) }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-500">ДДВ</span>
              <span class="text-sm font-medium text-gray-700">{{ formatMoney(selectedReceipt.vat_amount) }}</span>
            </div>
          </div>

          <!-- Tax Group Breakdown -->
          <div v-if="selectedReceipt.tax_breakdown && Object.keys(selectedReceipt.tax_breakdown).length > 0">
            <h4 class="text-sm font-semibold text-gray-700 mb-2">{{ $t('fiscal_receipts.tax_breakdown') }}</h4>
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-gray-200">
                  <th class="text-left py-1.5 text-xs font-medium text-gray-500">{{ $t('fiscal_receipts.tax_group') }}</th>
                  <th class="text-right py-1.5 text-xs font-medium text-gray-500">{{ $t('fiscal_receipts.base_amount') }}</th>
                  <th class="text-right py-1.5 text-xs font-medium text-gray-500">ДДВ</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(group, key) in selectedReceipt.tax_breakdown" :key="key" class="border-b border-gray-100">
                  <td class="py-1.5 text-gray-700">{{ taxGroupLabel(key) }}</td>
                  <td class="py-1.5 text-right text-gray-700">{{ formatMoney(group.base || group.amount || group.turnover) }}</td>
                  <td class="py-1.5 text-right text-gray-700">{{ formatMoney(group.vat || group.tax) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Items Snapshot -->
          <div v-if="selectedReceipt.items_snapshot && selectedReceipt.items_snapshot.length > 0">
            <h4 class="text-sm font-semibold text-gray-700 mb-2">{{ $t('fiscal_receipts.items') }}</h4>
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-gray-200">
                  <th class="text-left py-1.5 text-xs font-medium text-gray-500">{{ $t('fiscal_receipts.items') }}</th>
                  <th class="text-right py-1.5 text-xs font-medium text-gray-500">{{ $t('fiscal_receipts.qty') }}</th>
                  <th class="text-right py-1.5 text-xs font-medium text-gray-500">{{ $t('fiscal_receipts.amount') }}</th>
                  <th class="text-right py-1.5 text-xs font-medium text-gray-500">{{ $t('fiscal_receipts.tax_group') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, idx) in selectedReceipt.items_snapshot" :key="idx" class="border-b border-gray-100">
                  <td class="py-1.5 text-gray-700">{{ item.name }}</td>
                  <td class="py-1.5 text-right text-gray-600">{{ item.quantity }}</td>
                  <td class="py-1.5 text-right text-gray-700">{{ formatMoney(item.amount || item.price * item.quantity) }}</td>
                  <td class="py-1.5 text-right text-gray-600">{{ item.tax_group || '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Storno links -->
          <div v-if="selectedReceipt.storno_of_receipt_id || selectedReceipt.storno_receipt">
            <h4 class="text-sm font-semibold text-gray-700 mb-2">{{ $t('fiscal_receipts.storno') }}</h4>
            <div v-if="selectedReceipt.storno_of_receipt_id" class="text-sm text-gray-600">
              Original: <span class="font-mono text-primary-600">#{{ selectedReceipt.storno_of_receipt_id }}</span>
            </div>
            <div v-if="selectedReceipt.storno_receipt" class="text-sm text-gray-600">
              Storno: <span class="font-mono text-red-600">#{{ selectedReceipt.storno_receipt.id }}</span>
            </div>
          </div>

          <!-- Raw Response (collapsible) -->
          <div v-if="selectedReceipt.raw_response">
            <button
              type="button"
              class="flex items-center gap-1 text-sm font-medium text-gray-600 hover:text-gray-800"
              @click="showRawResponse = !showRawResponse"
            >
              <svg
                class="h-4 w-4 transition-transform"
                :class="showRawResponse ? 'rotate-90' : ''"
                viewBox="0 0 20 20" fill="currentColor"
              >
                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
              </svg>
              Raw Response
            </button>
            <pre
              v-if="showRawResponse"
              class="mt-2 rounded-md bg-gray-900 text-green-400 text-xs p-4 overflow-x-auto whitespace-pre-wrap max-h-64 overflow-y-auto"
            >{{ typeof selectedReceipt.raw_response === 'string' ? selectedReceipt.raw_response : JSON.stringify(selectedReceipt.raw_response, null, 2) }}</pre>
          </div>
        </div>

        <!-- Footer -->
        <div class="sticky bottom-0 bg-white border-t border-gray-200 px-6 py-3">
          <button
            type="button"
            class="w-full rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200"
            @click="selectedReceipt = null"
          >
            {{ $t('fiscal_receipts.close') }}
          </button>
        </div>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'
import axios from 'axios'

const { t } = useI18n()
const notificationStore = useNotificationStore()

const tableComponent = ref(null)
const devices = ref([])
const hasDevices = ref(true)
const isLoading = ref(true)
const showEmptyScreen = ref(false)
const showFilters = ref(false)
const showDailySummary = ref(false)
const selectedReceipt = ref(null)
const showRawResponse = ref(false)

const summaryData = ref({
  count: 0,
  storno_count: 0,
  total_amount: 0,
  total_vat: 0,
  tax_a: 0,
  tax_b: 0,
  tax_v: 0,
})

const filters = ref({
  search: '',
  source: '',
  deviceId: '',
  fromDate: '',
  toDate: '',
  paymentType: '',
  stornoOnly: false,
})

onMounted(async () => {
  try {
    const { data } = await axios.get('/fiscal-devices')
    devices.value = data.data || []
    hasDevices.value = devices.value.length > 0
    showEmptyScreen.value = !hasDevices.value
  } catch (_e) {
    hasDevices.value = false
    showEmptyScreen.value = true
  } finally {
    isLoading.value = false
  }
})

// Debounced search
let searchTimeout = null
watch(() => filters.value.search, () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    refreshTable()
  }, 300)
})

const columns = computed(() => [
  { key: 'receipt_number', label: t('fiscal_receipts.receipt_number') },
  { key: 'invoice', label: t('fiscal_receipts.invoice'), sortable: false },
  { key: 'amount', label: t('fiscal_receipts.amount') },
  { key: 'payment_type', label: t('fiscal_receipts.payment_type'), sortable: false },
  { key: 'source', label: t('fiscal_receipts.source'), thClass: 'extra', sortable: false },
  { key: 'operator', label: t('fiscal_receipts.operator'), thClass: 'extra', sortable: false },
  { key: 'created_at', label: t('fiscal_receipts.date') },
  { key: 'actions', label: '', tdClass: 'text-right', sortable: false },
])

function buildFilterParams() {
  const params = {}
  if (filters.value.source) params.source = filters.value.source
  if (filters.value.deviceId) params.fiscal_device_id = filters.value.deviceId
  if (filters.value.fromDate) params.from_date = filters.value.fromDate
  if (filters.value.toDate) params.to_date = filters.value.toDate
  if (filters.value.paymentType) params.payment_type = filters.value.paymentType
  if (filters.value.stornoOnly) params.is_storno = 1
  if (filters.value.search) params.search = filters.value.search
  return params
}

async function fetchData({ page, filter, sort }) {
  const params = {
    orderByField: sort.fieldName || 'created_at',
    orderBy: sort.order || 'desc',
    page,
    limit: 25,
    ...buildFilterParams(),
  }

  const { data } = await axios.get('/fiscal-receipts', { params })

  // Update empty screen based on actual data
  if (!filters.value.search && !filters.value.source && !filters.value.deviceId && !filters.value.paymentType && !filters.value.stornoOnly && !filters.value.fromDate && !filters.value.toDate) {
    showEmptyScreen.value = (data.total || 0) === 0 && !hasDevices.value
  }

  return {
    data: data.data || [],
    pagination: {
      totalPages: data.last_page || 1,
      currentPage: data.current_page || page,
      totalCount: data.total || 0,
      limit: 25,
    },
  }
}

function refreshTable() {
  tableComponent.value && tableComponent.value.refresh()
  if (showDailySummary.value) fetchSummary()
}

function toggleFilter() {
  showFilters.value = !showFilters.value
}

function clearFilter() {
  filters.value = {
    search: '',
    source: '',
    deviceId: '',
    fromDate: '',
    toDate: '',
    paymentType: '',
    stornoOnly: false,
  }
  refreshTable()
}

function toggleSummary() {
  showDailySummary.value = !showDailySummary.value
  if (showDailySummary.value) fetchSummary()
}

async function fetchSummary() {
  try {
    const { data } = await axios.get('/fiscal-receipts/summary', { params: buildFilterParams() })
    summaryData.value = data.data
  } catch (_e) {
    // Silently fail — summary is optional
  }
}

function openDetail(receipt) {
  selectedReceipt.value = receipt
  showRawResponse.value = false
}

function confirmStorno(receipt) {
  if (window.confirm(t('fiscal_receipts.confirm_storno'))) {
    performStorno(receipt)
  }
}

async function performStorno(receipt) {
  try {
    await axios.post(`/fiscal-devices/${receipt.fiscal_device_id}/receipts/${receipt.id}/storno`, {
      operator_name: window.__auth_user?.name || 'Operator',
    })
    notificationStore.showNotification({
      type: 'success',
      message: t('fiscal_receipts.storno_success'),
    })
    refreshTable()
  } catch (e) {
    notificationStore.showNotification({
      type: 'error',
      message: e.response?.data?.error || e.message,
    })
  }
}

async function exportCsv() {
  const params = { format: 'csv', ...buildFilterParams() }
  try {
    const response = await axios.get('/fiscal-receipts/export', { params, responseType: 'blob' })
    const url = URL.createObjectURL(response.data)
    const a = document.createElement('a')
    a.href = url
    a.download = `fiscal-receipts-${new Date().toISOString().split('T')[0]}.csv`
    a.click()
    URL.revokeObjectURL(url)
  } catch (_e) {
    notificationStore.showNotification({ type: 'error', message: 'Export failed' })
  }
}

function formatMoney(cents) {
  if (!cents && cents !== 0) return '—'
  return (cents / 100).toLocaleString('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' МКД'
}

function formatDate(dateStr) {
  if (!dateStr) return '—'
  const d = new Date(dateStr)
  return d.toLocaleDateString('mk-MK', { day: '2-digit', month: '2-digit', year: 'numeric' })
    + ' ' + d.toLocaleTimeString('mk-MK', { hour: '2-digit', minute: '2-digit' })
}

function sourceLabel(source) {
  const labels = {
    webserial: 'USB (WebSerial)',
    'erpnet-fp': 'ErpNet.FP',
    manual: t('fiscal_receipts.manual'),
    server: 'Server',
  }
  return labels[source] || source || 'Server'
}

function sourceBadgeClass(source) {
  const classes = {
    webserial: 'bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-700/10',
    'erpnet-fp': 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-700/10',
    manual: 'bg-gray-50 text-gray-600 ring-1 ring-inset ring-gray-500/10',
    server: 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-700/10',
  }
  return classes[source] || 'bg-gray-50 text-gray-600'
}

function paymentLabel(type) {
  const labels = {
    cash: t('fiscal_receipts.cash'),
    card: t('fiscal_receipts.card'),
    check: t('fiscal_receipts.check'),
    bank_transfer: t('fiscal_receipts.bank_transfer'),
  }
  return labels[type] || type || '—'
}

function paymentBadgeClass(type) {
  const classes = {
    cash: 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/10',
    card: 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/10',
    check: 'bg-yellow-50 text-yellow-700 ring-1 ring-inset ring-yellow-600/10',
    bank_transfer: 'bg-purple-50 text-purple-700 ring-1 ring-inset ring-purple-600/10',
  }
  return classes[type] || 'bg-gray-50 text-gray-600'
}

function taxGroupLabel(key) {
  const labels = {
    A: t('fiscal_receipts.tax_group_a'),
    a: t('fiscal_receipts.tax_group_a'),
    B: t('fiscal_receipts.tax_group_b'),
    b: t('fiscal_receipts.tax_group_b'),
    V: t('fiscal_receipts.tax_group_v'),
    v: t('fiscal_receipts.tax_group_v'),
    G: t('fiscal_receipts.tax_group_g'),
    g: t('fiscal_receipts.tax_group_g'),
  }
  return labels[key] || key
}
</script>

<!-- CLAUDE-CHECKPOINT -->
