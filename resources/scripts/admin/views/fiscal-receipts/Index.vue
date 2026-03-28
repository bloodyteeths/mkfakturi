<template>
  <BasePage>
    <BasePageHeader :title="$t('fiscal_receipts.title')">
      <template #actions>
        <div class="flex flex-wrap items-center gap-3">
          <!-- Date from -->
          <div class="flex items-center gap-1">
            <label class="text-xs text-gray-500">{{ $t('fiscal_receipts.date_from') }}</label>
            <input
              v-model="filters.fromDate"
              type="date"
              class="rounded-md border-gray-300 text-sm w-36"
              @change="refreshTable"
            />
          </div>

          <!-- Date to -->
          <div class="flex items-center gap-1">
            <label class="text-xs text-gray-500">{{ $t('fiscal_receipts.date_to') }}</label>
            <input
              v-model="filters.toDate"
              type="date"
              class="rounded-md border-gray-300 text-sm w-36"
              @change="refreshTable"
            />
          </div>

          <!-- Source filter -->
          <select
            v-model="filters.source"
            class="rounded-md border-gray-300 text-sm"
            @change="refreshTable"
          >
            <option value="">{{ $t('fiscal_receipts.all_sources') }}</option>
            <option value="webserial">WebSerial</option>
            <option value="erpnet-fp">ErpNet.FP</option>
            <option value="manual">{{ $t('fiscal_receipts.manual') }}</option>
          </select>

          <!-- Device filter -->
          <select
            v-if="devices.length > 0"
            v-model="filters.deviceId"
            class="rounded-md border-gray-300 text-sm"
            @change="refreshTable"
          >
            <option value="">{{ $t('fiscal_receipts.all_devices') }}</option>
            <option v-for="d in devices" :key="d.id" :value="d.id">
              {{ d.name || d.device_type }}
            </option>
          </select>

          <!-- Payment type filter -->
          <select
            v-model="filters.paymentType"
            class="rounded-md border-gray-300 text-sm"
            @change="refreshTable"
          >
            <option value="">{{ $t('fiscal_receipts.all_payments') }}</option>
            <option value="cash">{{ $t('fiscal_receipts.cash') }}</option>
            <option value="card">{{ $t('fiscal_receipts.card') }}</option>
            <option value="check">{{ $t('fiscal_receipts.check') }}</option>
            <option value="bank_transfer">{{ $t('fiscal_receipts.bank_transfer') }}</option>
          </select>

          <!-- Storno toggle -->
          <label class="flex items-center gap-1.5 text-sm text-gray-600 cursor-pointer">
            <input
              v-model="filters.stornoOnly"
              type="checkbox"
              class="rounded border-gray-300 text-red-600 focus:ring-red-500"
              @change="refreshTable"
            />
            {{ $t('fiscal_receipts.storno') }}
          </label>

          <!-- Daily Summary toggle -->
          <button
            type="button"
            class="inline-flex items-center rounded-md px-3 py-1.5 text-sm font-medium"
            :class="showDailySummary
              ? 'bg-primary-600 text-white'
              : 'bg-white text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50'"
            @click="showDailySummary = !showDailySummary"
          >
            {{ $t('fiscal_receipts.daily_summary') }}
          </button>

          <!-- Export CSV -->
          <button
            type="button"
            class="inline-flex items-center rounded-md bg-white px-3 py-1.5 text-sm font-medium text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
            @click="exportCsv"
          >
            {{ $t('fiscal_receipts.export_csv') }}
          </button>
        </div>
      </template>
    </BasePageHeader>

    <!-- Daily Summary Card -->
    <div
      v-if="showDailySummary && summaryData.count > 0"
      class="mb-6 rounded-lg bg-gray-50 border border-gray-200 p-5"
    >
      <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ $t('fiscal_receipts.daily_summary') }}</h3>
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <div>
          <p class="text-xs text-gray-500">{{ $t('fiscal_receipts.total_receipts') }}</p>
          <p class="text-lg font-semibold text-gray-900">{{ summaryData.count }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">{{ $t('fiscal_receipts.total_amount') }}</p>
          <p class="text-lg font-semibold text-gray-900">{{ formatMoney(summaryData.totalAmount) }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">{{ $t('fiscal_receipts.total_vat') }}</p>
          <p class="text-lg font-semibold text-gray-900">{{ formatMoney(summaryData.totalVat) }}</p>
        </div>
        <div v-if="summaryData.taxA > 0">
          <p class="text-xs text-gray-500">{{ $t('fiscal_receipts.tax_group_a') }}</p>
          <p class="text-sm font-medium text-gray-800">{{ formatMoney(summaryData.taxA) }}</p>
        </div>
        <div v-if="summaryData.taxB > 0">
          <p class="text-xs text-gray-500">{{ $t('fiscal_receipts.tax_group_b') }}</p>
          <p class="text-sm font-medium text-gray-800">{{ formatMoney(summaryData.taxB) }}</p>
        </div>
        <div v-if="summaryData.taxV > 0">
          <p class="text-xs text-gray-500">{{ $t('fiscal_receipts.tax_group_v') }}</p>
          <p class="text-sm font-medium text-gray-800">{{ formatMoney(summaryData.taxV) }}</p>
        </div>
      </div>
    </div>

    <!-- Empty state: no fiscal devices configured -->
    <div v-if="!hasDevices && !isLoading" class="text-center py-16">
      <svg class="h-16 w-16 mx-auto text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18.75 9H5.25" />
      </svg>
      <p class="text-gray-500 mb-4">{{ $t('fiscal_receipts.no_devices_configured') }}</p>
      <router-link
        to="/admin/settings/fiscal-devices"
        class="inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700"
      >
        {{ $t('fiscal_receipts.go_to_settings') }}
      </router-link>
    </div>

    <!-- Receipts table -->
    <BaseTable
      v-else
      ref="table"
      :data="fetchData"
      :columns="columns"
    >
      <template #cell-receipt_number="{ row }">
        <span
          class="font-mono text-sm font-medium text-gray-900 cursor-pointer hover:text-primary-600"
          @click="openDetail(row.data)"
        >
          {{ row.data.receipt_number }}
        </span>
      </template>

      <template #cell-fiscal_id="{ row }">
        <span class="font-mono text-xs text-gray-600">
          {{ row.data.fiscal_id }}
        </span>
      </template>

      <template #cell-device="{ row }">
        <span class="text-sm text-gray-700">
          {{ row.data.fiscal_device?.name || row.data.fiscal_device?.device_type || '—' }}
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
          <span class="ml-1 text-xs text-gray-500">
            (ДДВ {{ formatMoney(row.data.vat_amount) }})
          </span>
        </div>
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

      <template #cell-unique_sale_number="{ row }">
        <span v-if="row.data.unique_sale_number" class="font-mono text-xs text-gray-600">
          {{ row.data.unique_sale_number }}
        </span>
        <span v-else class="text-xs text-gray-400">—</span>
      </template>

      <template #cell-created_at="{ row }">
        <span class="text-sm text-gray-600">
          {{ formatDate(row.data.created_at) }}
        </span>
      </template>

      <!-- Row click for detail -->
      <template #row="{ row, columns: cols }">
        <tr
          class="cursor-pointer hover:bg-gray-50"
          @click="openDetail(row.data)"
        >
          <td
            v-for="col in cols"
            :key="col.key"
            class="px-3 py-3 text-sm"
          >
            <slot :name="`cell-${col.key}`" :row="row">
              {{ row.data[col.key] }}
            </slot>
          </td>
        </tr>
      </template>
    </BaseTable>

    <!-- Detail Slide-over Panel -->
    <div
      v-if="selectedReceipt"
      class="fixed inset-0 z-50 flex justify-end"
    >
      <!-- Backdrop -->
      <div
        class="fixed inset-0 bg-black/30"
        @click="selectedReceipt = null"
      />

      <!-- Panel -->
      <div class="relative w-full max-w-[480px] h-full bg-white shadow-xl overflow-y-auto">
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
                  <th class="text-left py-1.5 text-xs font-medium text-gray-500">{{ $t('fiscal_receipts.tax_breakdown') }}</th>
                  <th class="text-right py-1.5 text-xs font-medium text-gray-500">{{ $t('fiscal_receipts.amount') }}</th>
                  <th class="text-right py-1.5 text-xs font-medium text-gray-500">ДДВ</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(group, key) in selectedReceipt.tax_breakdown" :key="key" class="border-b border-gray-100">
                  <td class="py-1.5 text-gray-700">{{ taxGroupLabel(key) }}</td>
                  <td class="py-1.5 text-right text-gray-700">{{ formatMoney(group.amount || group.turnover) }}</td>
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
                  <th class="text-right py-1.5 text-xs font-medium text-gray-500">Qty</th>
                  <th class="text-right py-1.5 text-xs font-medium text-gray-500">{{ $t('fiscal_receipts.amount') }}</th>
                  <th class="text-right py-1.5 text-xs font-medium text-gray-500">{{ $t('fiscal_receipts.tax_breakdown') }}</th>
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
          <div v-if="selectedReceipt.storno_receipt_id || selectedReceipt.original_receipt_id">
            <h4 class="text-sm font-semibold text-gray-700 mb-2">{{ $t('fiscal_receipts.storno') }}</h4>
            <div v-if="selectedReceipt.original_receipt_id" class="text-sm text-gray-600">
              Original: <span class="font-mono text-primary-600">#{{ selectedReceipt.original_receipt_id }}</span>
            </div>
            <div v-if="selectedReceipt.storno_receipt_id" class="text-sm text-gray-600">
              Storno: <span class="font-mono text-red-600">#{{ selectedReceipt.storno_receipt_id }}</span>
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
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'

const { t } = useI18n()
const table = ref(null)
const devices = ref([])
const hasDevices = ref(true)
const isLoading = ref(true)
const selectedReceipt = ref(null)
const showRawResponse = ref(false)
const showDailySummary = ref(false)
const currentPageData = ref([])

const filters = ref({
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
  } catch (_e) {
    hasDevices.value = false
  } finally {
    isLoading.value = false
  }
})

const summaryData = computed(() => {
  const rows = currentPageData.value
  if (!rows || rows.length === 0) {
    return { count: 0, totalAmount: 0, totalVat: 0, taxA: 0, taxB: 0, taxV: 0 }
  }
  let totalAmount = 0
  let totalVat = 0
  let taxA = 0
  let taxB = 0
  let taxV = 0

  rows.forEach((r) => {
    if (!r.is_storno) {
      totalAmount += r.amount || 0
      totalVat += r.vat_amount || 0
    }
    if (r.tax_breakdown) {
      const tb = r.tax_breakdown
      if (tb.A || tb.a) {
        const g = tb.A || tb.a
        taxA += g.amount || g.turnover || 0
      }
      if (tb.B || tb.b) {
        const g = tb.B || tb.b
        taxB += g.amount || g.turnover || 0
      }
      if (tb.V || tb.v) {
        const g = tb.V || tb.v
        taxV += g.amount || g.turnover || 0
      }
    }
  })

  return { count: rows.length, totalAmount, totalVat, taxA, taxB, taxV }
})

const columns = computed(() => [
  {
    key: 'receipt_number',
    label: t('fiscal_receipts.receipt_number'),
    thClass: 'extra',
    tdClass: '',
  },
  {
    key: 'fiscal_id',
    label: t('fiscal_receipts.fiscal_id'),
    thClass: 'extra',
    tdClass: '',
  },
  {
    key: 'device',
    label: t('fiscal_receipts.device'),
    thClass: 'extra',
    tdClass: '',
    sortable: false,
  },
  {
    key: 'invoice',
    label: t('fiscal_receipts.invoice'),
    thClass: 'extra',
    tdClass: '',
    sortable: false,
  },
  {
    key: 'amount',
    label: t('fiscal_receipts.amount'),
    thClass: 'extra',
    tdClass: '',
  },
  {
    key: 'source',
    label: t('fiscal_receipts.source'),
    thClass: 'extra',
    tdClass: '',
  },
  {
    key: 'operator',
    label: t('fiscal_receipts.operator'),
    thClass: 'extra',
    tdClass: '',
    sortable: false,
  },
  {
    key: 'payment_type',
    label: t('fiscal_receipts.payment_type'),
    thClass: 'extra',
    tdClass: '',
    sortable: false,
  },
  {
    key: 'unique_sale_number',
    label: t('fiscal_receipts.unique_sale_number'),
    thClass: 'extra',
    tdClass: '',
    sortable: false,
  },
  {
    key: 'created_at',
    label: t('fiscal_receipts.date'),
    thClass: 'extra',
    tdClass: '',
  },
])

async function fetchData({ page, filter, sort }) {
  const params = {
    orderByField: sort.fieldName || 'created_at',
    orderBy: sort.order || 'desc',
    page,
    limit: 25,
  }

  if (filters.value.source) params.source = filters.value.source
  if (filters.value.deviceId) params.fiscal_device_id = filters.value.deviceId
  if (filters.value.fromDate) params.from_date = filters.value.fromDate
  if (filters.value.toDate) params.to_date = filters.value.toDate
  if (filters.value.paymentType) params.payment_type = filters.value.paymentType
  if (filters.value.stornoOnly) params.is_storno = 1

  const { data } = await axios.get('/fiscal-receipts', { params })

  currentPageData.value = data.data || []

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
  table.value && table.value.refresh()
}

function openDetail(receipt) {
  selectedReceipt.value = receipt
  showRawResponse.value = false
}

function exportCsv() {
  const params = new URLSearchParams()
  params.set('format', 'csv')
  if (filters.value.fromDate) params.set('from_date', filters.value.fromDate)
  if (filters.value.toDate) params.set('to_date', filters.value.toDate)
  if (filters.value.source) params.set('source', filters.value.source)
  if (filters.value.deviceId) params.set('fiscal_device_id', filters.value.deviceId)
  if (filters.value.paymentType) params.set('payment_type', filters.value.paymentType)
  if (filters.value.stornoOnly) params.set('is_storno', '1')

  window.open(`/api/v1/fiscal-receipts/export?${params.toString()}`, '_blank')
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
