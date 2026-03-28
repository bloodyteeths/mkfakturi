<template>
  <BasePage>
    <BasePageHeader :title="$t('fiscal_receipts.title')">
      <template #actions>
        <div class="flex items-center gap-3">
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
        </div>
      </template>
    </BasePageHeader>

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
        <span class="font-mono text-sm font-medium text-gray-900">
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
        <span class="text-sm font-medium text-gray-900">
          {{ formatMoney(row.data.amount) }}
        </span>
        <span class="ml-1 text-xs text-gray-500">
          (ДДВ {{ formatMoney(row.data.vat_amount) }})
        </span>
      </template>

      <template #cell-source="{ row }">
        <span
          class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium"
          :class="sourceBadgeClass(row.data.source)"
        >
          {{ sourceLabel(row.data.source) }}
        </span>
      </template>

      <template #cell-created_at="{ row }">
        <span class="text-sm text-gray-600">
          {{ formatDate(row.data.created_at) }}
        </span>
      </template>
    </BaseTable>
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

const filters = ref({
  source: '',
  deviceId: '',
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

  const { data } = await axios.get('/fiscal-receipts', { params })

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
</script>

<!-- CLAUDE-CHECKPOINT -->
