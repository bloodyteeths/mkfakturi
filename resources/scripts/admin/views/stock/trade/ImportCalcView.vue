<template>
  <BasePage>
    <BasePageHeader :title="data ? data.document_number : $t('trade.import_calculation')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('stock.title')" to="/admin/stock" />
        <BaseBreadcrumbItem :title="$t('trade.import_calculations_title')" to="/admin/stock/trade/import-calculations" />
        <BaseBreadcrumbItem :title="data ? data.document_number : '...'" to="#" active />
      </BaseBreadcrumb>

      <template v-if="data" #actions>
        <div class="flex items-center space-x-3">
          <!-- Draft actions -->
          <template v-if="data.status === 'draft'">
            <router-link :to="{ name: 'stock.trade.import-calculation.edit', params: { id: data.id } }">
              <BaseButton variant="primary-outline">
                <template #left="slotProps">
                  <BaseIcon name="PencilIcon" :class="slotProps.class" />
                </template>
                {{ $t('trade.edit') }}
              </BaseButton>
            </router-link>
            <BaseButton
              variant="primary"
              :loading="isApproving"
              @click="approveCalc"
            >
              <template #left="slotProps">
                <BaseIcon name="CheckIcon" :class="slotProps.class" />
              </template>
              {{ $t('trade.approve') }}
            </BaseButton>
          </template>

          <!-- Approved actions -->
          <template v-if="data.status === 'approved'">
            <BaseButton
              variant="danger"
              :loading="isVoiding"
              @click="voidCalc"
            >
              <template #left="slotProps">
                <BaseIcon name="XMarkIcon" :class="slotProps.class" />
              </template>
              {{ $t('trade.void') }}
            </BaseButton>
          </template>

          <!-- PDF Export (always visible) -->
          <BaseButton
            variant="primary-outline"
            :loading="isExporting"
            @click="exportPdf"
          >
            <template #left="slotProps">
              <BaseIcon name="ArrowDownTrayIcon" :class="slotProps.class" />
            </template>
            PDF
          </BaseButton>
        </div>
      </template>
    </BasePageHeader>

    <!-- Stock Sub-Navigation Tabs -->
    <StockTabNavigation />

    <div v-if="isLoading" class="flex justify-center py-12">
      <BaseContentPlaceholders>
        <BaseContentPlaceholdersBox class="w-full h-96" />
      </BaseContentPlaceholders>
    </div>

    <div v-else-if="!data" class="text-center py-12">
      <BaseIcon name="ExclamationTriangleIcon" class="h-12 w-12 text-gray-400 mx-auto mb-4" />
      <h3 class="text-lg font-medium text-gray-900">{{ $t('trade.import_calc_not_found') }}</h3>
    </div>

    <div v-else>
      <!-- Document Info Card -->
      <BaseCard class="mb-6">
        <div class="flex items-start justify-between mb-4">
          <div>
            <h2 class="text-2xl font-bold text-gray-900">{{ data.document_number }}</h2>
            <div class="flex items-center mt-2">
              <span
                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                :class="statusBadgeClass(data.status)"
              >
                {{ statusLabel(data.status) }}
              </span>
            </div>
          </div>
          <div class="text-right">
            <p class="text-sm text-gray-500">{{ $t('trade.grand_total_landed_cost') }}</p>
            <p class="text-2xl font-bold font-mono text-gray-900">
              {{ formatMoney(data.total_landed_cost) }} MKD
            </p>
          </div>
        </div>

        <!-- Info Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-6">
          <div>
            <dt class="text-sm font-medium text-gray-500">{{ $t('trade.doc_date') }}</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ formatDate(data.document_date) }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">{{ $t('trade.supplier') }}</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ data.supplier_name || '-' }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">{{ $t('trade.invoice_number') }}</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ data.supplier_invoice_number || '-' }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">{{ $t('trade.currency') }}</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ data.currency_code || '-' }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">{{ $t('trade.exchange_rate') }}</dt>
            <dd class="mt-1 text-sm text-gray-900 font-mono">{{ data.exchange_rate || '-' }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">{{ $t('trade.warehouse') }}</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ data.warehouse?.name || '-' }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">{{ $t('trade.created_by') }}</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ data.creator?.name || '-' }}</dd>
          </div>
          <div v-if="data.approver">
            <dt class="text-sm font-medium text-gray-500">{{ $t('trade.approved_by') }}</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ data.approver.name }}</dd>
          </div>
          <div v-if="data.approved_at">
            <dt class="text-sm font-medium text-gray-500">{{ $t('trade.approved_at') }}</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ formatDate(data.approved_at) }}</dd>
          </div>
        </div>

        <!-- Notes -->
        <div v-if="data.notes" class="mt-6 border-t pt-4">
          <dt class="text-sm font-medium text-gray-500">{{ $t('trade.notes') }}</dt>
          <dd class="mt-1 text-sm text-gray-900">{{ data.notes }}</dd>
        </div>
      </BaseCard>

      <!-- Costs Summary Card -->
      <BaseCard class="mb-6">
        <template #header>
          <h3 class="text-lg font-medium text-gray-900">{{ $t('trade.costs_summary') }}</h3>
        </template>

        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-6">
          <div class="text-center p-4 bg-gray-50 rounded-lg">
            <dt class="text-xs font-medium text-gray-500 uppercase">{{ $t('trade.transport') }}</dt>
            <dd class="mt-2 text-lg font-bold font-mono text-gray-900">{{ formatMoney(data.transport_amount) }}</dd>
          </div>
          <div class="text-center p-4 bg-gray-50 rounded-lg">
            <dt class="text-xs font-medium text-gray-500 uppercase">{{ $t('trade.forwarding') }}</dt>
            <dd class="mt-2 text-lg font-bold font-mono text-gray-900">{{ formatMoney(data.forwarding_amount) }}</dd>
          </div>
          <div class="text-center p-4 bg-gray-50 rounded-lg">
            <dt class="text-xs font-medium text-gray-500 uppercase">{{ $t('trade.other_costs') }}</dt>
            <dd class="mt-2 text-lg font-bold font-mono text-gray-900">{{ formatMoney(data.other_costs_amount) }}</dd>
          </div>
          <div class="text-center p-4 bg-blue-50 rounded-lg">
            <dt class="text-xs font-medium text-blue-600 uppercase">{{ $t('trade.customs_duty_total') }}</dt>
            <dd class="mt-2 text-lg font-bold font-mono text-blue-900">{{ formatMoney(data.customs_duty_total) }}</dd>
          </div>
          <div class="text-center p-4 bg-blue-50 rounded-lg">
            <dt class="text-xs font-medium text-blue-600 uppercase">{{ $t('trade.import_vat_total') }}</dt>
            <dd class="mt-2 text-lg font-bold font-mono text-blue-900">{{ formatMoney(data.import_vat_total) }}</dd>
          </div>
          <div class="text-center p-4 bg-green-50 rounded-lg">
            <dt class="text-xs font-medium text-green-600 uppercase">{{ $t('trade.grand_total_landed_cost') }}</dt>
            <dd class="mt-2 text-lg font-bold font-mono text-green-900">{{ formatMoney(data.total_landed_cost) }}</dd>
          </div>
        </div>
      </BaseCard>

      <!-- Tariff Heading Summary Table -->
      <BaseCard v-if="tariffSummary && tariffSummary.length > 0" class="mb-6">
        <template #header>
          <h3 class="text-lg font-medium text-gray-900">{{ $t('trade.tariff_heading_summary') }}</h3>
        </template>

        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('trade.tariff_number') }}</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('trade.tariff_item_count') }}</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('trade.customs_base') }}</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('trade.tariff_rate_pct') }}</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('trade.customs_duty') }}</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="(row, index) in tariffSummary" :key="index">
              <td class="px-4 py-3 text-sm text-gray-900 font-medium">{{ row.tariff_heading }}</td>
              <td class="px-4 py-3 text-sm text-right text-gray-900">{{ row.items_count }}</td>
              <td class="px-4 py-3 text-sm text-right text-gray-900 font-mono">{{ formatMoney(row.customs_base_total) }}</td>
              <td class="px-4 py-3 text-sm text-right text-gray-900 font-mono">{{ row.duty_rate }}%</td>
              <td class="px-4 py-3 text-sm text-right text-gray-900 font-mono">{{ formatMoney(row.duty_amount) }}</td>
            </tr>
          </tbody>
        </table>
      </BaseCard>

      <!-- Items Detail Table -->
      <BaseCard>
        <template #header>
          <h3 class="text-lg font-medium text-gray-900">
            {{ $t('trade.items') }} ({{ data.items ? data.items.length : 0 }})
          </h3>
        </template>

        <div v-if="!data.items || data.items.length === 0" class="text-center py-8">
          <p class="text-gray-500">{{ $t('trade.no_items') }}</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('trade.item_article') }}</th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('trade.tariff') }}</th>
                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('trade.qty') }}</th>
                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('trade.unit_price_fcy') }}</th>
                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('trade.invoice_mkd') }}</th>
                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('trade.transport') }}</th>
                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('trade.customs_duty') }}</th>
                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('trade.forwarding') }}</th>
                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('trade.other_short') }}</th>
                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('trade.total_before_vat') }}</th>
                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('trade.vat') }}</th>
                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('trade.total') }}</th>
                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('trade.landed_unit_price') }}</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="(item, index) in data.items" :key="item.id">
                <td class="px-3 py-3 text-sm text-gray-500">{{ index + 1 }}</td>
                <td class="px-3 py-3 text-sm text-gray-900 font-medium">
                  {{ item.description || item.item?.name || '-' }}
                </td>
                <td class="px-3 py-3 text-sm text-gray-500">{{ item.tariff_heading || '-' }}</td>
                <td class="px-3 py-3 text-sm text-right text-gray-900">{{ item.quantity }}</td>
                <td class="px-3 py-3 text-sm text-right text-gray-900 font-mono">{{ formatMoney(item.unit_price_fcy) }}</td>
                <td class="px-3 py-3 text-sm text-right text-gray-900 font-mono">{{ formatMoney(item.invoice_value_mkd) }}</td>
                <td class="px-3 py-3 text-sm text-right text-gray-900 font-mono">{{ formatMoney(item.transport_allocated) }}</td>
                <td class="px-3 py-3 text-sm text-right text-gray-900 font-mono">{{ formatMoney(item.customs_duty_amount) }}</td>
                <td class="px-3 py-3 text-sm text-right text-gray-900 font-mono">{{ formatMoney(item.forwarding_allocated) }}</td>
                <td class="px-3 py-3 text-sm text-right text-gray-900 font-mono">{{ formatMoney(item.other_costs_allocated) }}</td>
                <td class="px-3 py-3 text-sm text-right text-gray-900 font-mono">{{ formatMoney(item.landed_cost_before_vat) }}</td>
                <td class="px-3 py-3 text-sm text-right text-gray-900 font-mono">{{ formatMoney(item.import_vat_amount) }}</td>
                <td class="px-3 py-3 text-sm text-right text-gray-900 font-mono font-semibold">{{ formatMoney(item.total_landed_cost) }}</td>
                <td class="px-3 py-3 text-sm text-right text-green-700 font-mono font-semibold">{{ formatMoney(item.unit_landed_cost) }}</td>
              </tr>
            </tbody>
            <tfoot class="bg-gray-50">
              <tr>
                <td colspan="5" class="px-3 py-3 text-sm font-medium text-gray-900 text-right">
                  {{ $t('trade.totals') }}:
                </td>
                <td class="px-3 py-3 text-sm font-bold text-right font-mono text-gray-900">{{ formatMoney(totals.invoice_mkd) }}</td>
                <td class="px-3 py-3 text-sm font-bold text-right font-mono text-gray-900">{{ formatMoney(totals.transport) }}</td>
                <td class="px-3 py-3 text-sm font-bold text-right font-mono text-gray-900">{{ formatMoney(totals.customs_duty) }}</td>
                <td class="px-3 py-3 text-sm font-bold text-right font-mono text-gray-900">{{ formatMoney(totals.forwarding) }}</td>
                <td class="px-3 py-3 text-sm font-bold text-right font-mono text-gray-900">{{ formatMoney(totals.other_costs) }}</td>
                <td class="px-3 py-3 text-sm font-bold text-right font-mono text-gray-900">{{ formatMoney(totals.total_before_vat) }}</td>
                <td class="px-3 py-3 text-sm font-bold text-right font-mono text-gray-900">{{ formatMoney(totals.vat) }}</td>
                <td class="px-3 py-3 text-sm font-bold text-right font-mono text-gray-900">{{ formatMoney(totals.total_with_vat) }}</td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </BaseCard>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useNotificationStore } from '@/scripts/stores/notification'
import StockTabNavigation from '@/scripts/admin/components/StockTabNavigation.vue'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const companyStore = useCompanyStore()
const dialogStore = useDialogStore()
const notificationStore = useNotificationStore()

const data = ref(null)
const tariffSummary = ref([])
const isLoading = ref(false)
const isApproving = ref(false)
const isVoiding = ref(false)
const isExporting = ref(false)

const companyId = computed(() => companyStore.selectedCompany?.id)

function apiBase() {
  return `/partner/companies/${companyId.value}/accounting`
}

const totals = computed(() => {
  if (!data.value?.items?.length) {
    return {
      invoice_mkd: 0,
      transport: 0,
      customs_duty: 0,
      forwarding: 0,
      other_costs: 0,
      total_before_vat: 0,
      vat: 0,
      total_with_vat: 0,
    }
  }
  return data.value.items.reduce(
    (acc, item) => {
      acc.invoice_mkd += Number(item.invoice_value_mkd) || 0
      acc.transport += Number(item.transport_allocated) || 0
      acc.customs_duty += Number(item.customs_duty_amount) || 0
      acc.forwarding += Number(item.forwarding_allocated) || 0
      acc.other_costs += Number(item.other_costs_allocated) || 0
      acc.total_before_vat += Number(item.landed_cost_before_vat) || 0
      acc.vat += Number(item.import_vat_amount) || 0
      acc.total_with_vat += Number(item.total_landed_cost) || 0
      return acc
    },
    {
      invoice_mkd: 0,
      transport: 0,
      customs_duty: 0,
      forwarding: 0,
      other_costs: 0,
      total_before_vat: 0,
      vat: 0,
      total_with_vat: 0,
    }
  )
})

function statusBadgeClass(status) {
  const classes = {
    draft: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    voided: 'bg-red-100 text-red-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

function statusLabel(status) {
  const labels = {
    draft: t('trade.status_draft'),
    approved: t('trade.status_approved'),
    voided: t('trade.status_voided'),
  }
  return labels[status] || status
}

function formatDate(date) {
  if (!date) return '-'
  return String(date).substring(0, 10)
}

function formatMoney(amount) {
  if (amount === null || amount === undefined) return '-'
  const num = Number(amount) / 100
  return num.toLocaleString('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

async function loadData() {
  if (!companyId.value) return
  isLoading.value = true
  try {
    const response = await window.axios.get(
      `${apiBase()}/import-calculations/${route.params.id}`
    )
    data.value = response.data.data
    tariffSummary.value = response.data.tariff_summary || []
  } catch (error) {
    console.error('Failed to load import calculation:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('trade.import_calc_load_error'),
    })
  } finally {
    isLoading.value = false
  }
}

async function approveCalc() {
  const confirmed = await dialogStore.openDialog({
    title: t('trade.approve_import_calc'),
    message: t('trade.approve_import_calc_confirm'),
    yesLabel: t('trade.approve'),
    noLabel: t('trade.cancel'),
    variant: 'primary',
  })

  if (!confirmed) return

  isApproving.value = true
  try {
    await window.axios.post(
      `${apiBase()}/import-calculations/${data.value.id}/approve`
    )
    notificationStore.showNotification({
      type: 'success',
      message: t('trade.import_calc_approved'),
    })
    await loadData()
  } catch (error) {
    console.error('Failed to approve import calculation:', error)
    const msg = error.response?.data?.error || t('trade.import_calc_approve_error')
    notificationStore.showNotification({ type: 'error', message: msg })
  } finally {
    isApproving.value = false
  }
}

async function voidCalc() {
  const confirmed = await dialogStore.openDialog({
    title: t('trade.void_import_calc'),
    message: t('trade.void_import_calc_confirm'),
    yesLabel: t('trade.void'),
    noLabel: t('trade.cancel'),
    variant: 'danger',
  })

  if (!confirmed) return

  isVoiding.value = true
  try {
    await window.axios.post(
      `${apiBase()}/import-calculations/${data.value.id}/void`
    )
    notificationStore.showNotification({
      type: 'success',
      message: t('trade.import_calc_voided'),
    })
    await loadData()
  } catch (error) {
    console.error('Failed to void import calculation:', error)
    const msg = error.response?.data?.error || t('trade.import_calc_void_error')
    notificationStore.showNotification({ type: 'error', message: msg })
  } finally {
    isVoiding.value = false
  }
}

async function exportPdf() {
  if (!companyId.value || !data.value) return
  isExporting.value = true
  try {
    const response = await window.axios.get(
      `${apiBase()}/import-calculations/${data.value.id}/export`,
      { responseType: 'blob' }
    )
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `import_calc_${data.value.document_number}.pdf`
    link.click()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Failed to export PDF:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('trade.import_calc_export_error'),
    })
  } finally {
    isExporting.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>

// CLAUDE-CHECKPOINT
