<template>
  <BasePage>
    <BasePageHeader :title="$t('trade.kap_title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('stock.title')" to="/admin/stock" />
        <BaseBreadcrumbItem :title="$t('trade.tab_kap')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <div class="flex items-center space-x-3">
          <BaseButton
            v-if="kapData"
            variant="primary-outline"
            :loading="isExporting"
            @click="exportPdf"
          >
            <template #left="slotProps">
              <BaseIcon name="ArrowDownTrayIcon" :class="slotProps.class" />
            </template>
            {{ $t('trade.download_pdf') }}
          </BaseButton>
          <BaseButton
            v-if="kapData"
            variant="primary"
            :loading="isApplying"
            @click="applyPrices"
          >
            <template #left="slotProps">
              <BaseIcon name="CheckIcon" :class="slotProps.class" />
            </template>
            {{ $t('trade.apply_prices') }}
          </BaseButton>
        </div>
      </template>
    </BasePageHeader>

    <!-- Stock Sub-Navigation Tabs -->
    <StockTabNavigation />

    <!-- Bill Selector -->
    <BaseCard class="mb-6">
      <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <BaseInputGroup :label="$t('trade.select_bill')" required>
            <BaseMultiselect
              v-model="selectedBillId"
              :options="bills"
              value-prop="id"
              label="bill_number"
              track-by="bill_number"
              :placeholder="$t('trade.select_bill_placeholder')"
              :searchable="true"
              @update:model-value="onBillChange"
            />
          </BaseInputGroup>

          <!-- Dependent Costs -->
          <BaseInputGroup :label="$t('trade.dc_transport')">
            <BaseInput
              v-model.number="dependentCosts.transport"
              type="number"
              step="0.01"
              min="0"
              placeholder="0.00"
            />
          </BaseInputGroup>
          <BaseInputGroup :label="$t('trade.dc_customs')">
            <BaseInput
              v-model.number="dependentCosts.customs"
              type="number"
              step="0.01"
              min="0"
              placeholder="0.00"
            />
          </BaseInputGroup>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
          <BaseInputGroup :label="$t('trade.dc_insurance')">
            <BaseInput
              v-model.number="dependentCosts.insurance"
              type="number"
              step="0.01"
              min="0"
              placeholder="0.00"
            />
          </BaseInputGroup>
          <BaseInputGroup :label="$t('trade.dc_other')">
            <BaseInput
              v-model.number="dependentCosts.other"
              type="number"
              step="0.01"
              min="0"
              placeholder="0.00"
            />
          </BaseInputGroup>
          <div class="flex items-end">
            <BaseButton
              variant="primary"
              class="w-full"
              :loading="isCalculating"
              :disabled="!selectedBillId"
              @click="calculateKap"
            >
              <template #left="slotProps">
                <BaseIcon name="CalculatorIcon" :class="slotProps.class" />
              </template>
              {{ $t('trade.calculate') }}
            </BaseButton>
          </div>
        </div>
      </div>
    </BaseCard>

    <!-- Bill Info -->
    <div v-if="kapData" class="mb-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-400">
        <p class="text-xs text-gray-500 uppercase">Фактура</p>
        <p class="text-lg font-bold text-blue-700">{{ kapData.bill_number }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-gray-400">
        <p class="text-xs text-gray-500 uppercase">Добавувач</p>
        <p class="text-lg font-bold text-gray-700">{{ kapData.supplier_name || '-' }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-400">
        <p class="text-xs text-gray-500 uppercase">Датум</p>
        <p class="text-lg font-bold text-green-700">{{ kapData.bill_date }}</p>
      </div>
    </div>

    <!-- Margin Warnings -->
    <div v-if="marginWarnings.length > 0" class="mb-6">
      <div class="rounded-lg bg-amber-50 border border-amber-200 p-4">
        <div class="flex items-start">
          <BaseIcon name="ExclamationTriangleIcon" class="h-5 w-5 text-amber-500 mt-0.5" />
          <div class="ml-3">
            <h3 class="text-sm font-medium text-amber-800">{{ $t('trade.margin_cap_warning') }}</h3>
            <div class="mt-2 text-sm text-amber-700">
              <p v-for="(warn, i) in marginWarnings" :key="i">
                {{ warn.item_name }}: {{ warn.actual_markup }}% ({{ $t('trade.margin_cap_percent', { cap: warn.cap }) }})
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isCalculating" class="flex justify-center py-8">
      <BaseContentPlaceholders>
        <BaseContentPlaceholdersBox class="w-full h-64" />
      </BaseContentPlaceholders>
    </div>

    <!-- No bill selected -->
    <div v-else-if="!kapData" class="text-center py-12">
      <BaseIcon name="DocumentTextIcon" class="h-12 w-12 text-gray-400 mx-auto mb-4" />
      <h3 class="text-lg font-medium text-gray-900">{{ $t('trade.kap_title') }}</h3>
      <p class="text-gray-500 mt-2">Изберете фактура за да ја видите калкулацијата.</p>
    </div>

    <!-- KAP Table -->
    <BaseCard v-if="kapData && kapData.items">
      <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
        <div>
          <h3 class="text-lg font-medium text-gray-900">{{ $t('trade.kap_title') }}</h3>
          <p class="text-sm text-gray-500">{{ kapData.bill_number }} - {{ kapData.bill_date }}</p>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-800 text-white">
            <tr>
              <th class="px-3 py-2 text-center text-xs font-medium uppercase">#</th>
              <th class="px-3 py-2 text-left text-xs font-medium uppercase">Артикл</th>
              <th class="px-3 py-2 text-right text-xs font-medium uppercase">Количина</th>
              <th class="px-3 py-2 text-right text-xs font-medium uppercase">{{ $t('trade.fakturna_unit') }}</th>
              <th class="px-3 py-2 text-right text-xs font-medium uppercase">{{ $t('trade.fakturna_iznos') }}</th>
              <th class="px-3 py-2 text-right text-xs font-medium uppercase">{{ $t('trade.zavisni_troshoci') }}</th>
              <th class="px-3 py-2 text-right text-xs font-medium uppercase">{{ $t('trade.nabavna_bez_ddv') }}</th>
              <th class="px-3 py-2 text-center text-xs font-medium uppercase">{{ $t('trade.markup_percent') }}</th>
              <th class="px-3 py-2 text-right text-xs font-medium uppercase">{{ $t('trade.marzha_iznos') }}</th>
              <th class="px-3 py-2 text-right text-xs font-medium uppercase">{{ $t('trade.gp_unit') }}</th>
              <th class="px-3 py-2 text-right text-xs font-medium uppercase">{{ $t('trade.gp_iznos') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="(item, index) in kapData.items" :key="index" class="hover:bg-gray-50">
              <td class="px-3 py-2 text-sm text-gray-500 text-center">{{ index + 1 }}</td>
              <td class="px-3 py-2 text-sm text-gray-900 font-medium">
                {{ item.item_name || item.name }}
                <span v-if="item.sku" class="block text-xs text-gray-400">{{ item.sku }}</span>
              </td>
              <td class="px-3 py-2 text-sm text-right text-gray-900">{{ item.quantity }}</td>
              <td class="px-3 py-2 text-sm text-right font-mono text-gray-900">{{ fmtAmt(item.unit_price_nabavna) }}</td>
              <td class="px-3 py-2 text-sm text-right font-mono text-gray-900">{{ fmtAmt(item.total_nabavna) }}</td>
              <td class="px-3 py-2 text-sm text-right font-mono text-gray-600">{{ fmtAmt(item.zavisni_troshoci) }}</td>
              <td class="px-3 py-2 text-sm text-right font-mono text-gray-900">{{ fmtAmt(item.nabavna_bez_ddv) }}</td>
              <td class="px-3 py-2 text-sm text-center">
                <input
                  type="number"
                  class="w-16 text-center border border-gray-300 rounded px-1 py-0.5 text-sm"
                  :value="markupOverrides[item.item_id] !== undefined ? markupOverrides[item.item_id] : item.markup_percent"
                  step="0.1"
                  min="0"
                  @change="setMarkup(item.item_id, $event.target.value)"
                />
              </td>
              <td class="px-3 py-2 text-sm text-right font-mono text-green-700">{{ fmtAmt(item.marzha_iznos) }}</td>
              <td class="px-3 py-2 text-sm text-right font-mono text-blue-700 font-semibold">{{ fmtAmt(item.unit_price_prodazhna) }}</td>
              <td class="px-3 py-2 text-sm text-right font-mono text-blue-700 font-semibold">{{ fmtAmt(item.total_prodazhna) }}</td>
            </tr>
          </tbody>
          <tfoot class="bg-gray-800 text-white font-semibold">
            <tr v-if="kapData.totals">
              <td colspan="4" class="px-3 py-3 text-sm">ВКУПНО</td>
              <td class="px-3 py-3 text-sm text-right font-mono">{{ fmtAmt(kapData.totals.total_nabavna) }}</td>
              <td class="px-3 py-3 text-sm text-right font-mono">{{ fmtAmt(kapData.totals.total_zavisni) }}</td>
              <td class="px-3 py-3 text-sm text-right font-mono">{{ fmtAmt(kapData.totals.total_nabavna_bez_ddv) }}</td>
              <td></td>
              <td class="px-3 py-3 text-sm text-right font-mono">{{ fmtAmt(kapData.totals.total_marzha) }}</td>
              <td></td>
              <td class="px-3 py-3 text-sm text-right font-mono">{{ fmtAmt(kapData.totals.total_prodazhna) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </BaseCard>

    <!-- Apply Prices Result -->
    <div v-if="applyResult" class="mt-6">
      <BaseCard>
        <div class="p-6">
          <h3 class="text-lg font-medium text-green-800 mb-4">{{ $t('trade.prices_applied', { count: applyResult.changed.length }) }}</h3>
          <div v-if="applyResult.nivelacija" class="bg-blue-50 rounded-lg p-4 mt-4">
            <p class="text-sm text-blue-800">
              {{ $t('trade.auto_nivelacija_created', { number: applyResult.nivelacija.document_number }) }}
            </p>
            <router-link
              :to="{ name: 'stock.trade.nivelacija.view', params: { id: applyResult.nivelacija.id } }"
              class="text-sm text-blue-600 underline mt-1 inline-block"
            >
              Прегледај нивелација
            </router-link>
          </div>
        </div>
      </BaseCard>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useNotificationStore } from '@/scripts/stores/notification'
import StockTabNavigation from '@/scripts/admin/components/StockTabNavigation.vue'

const { t } = useI18n()
const companyStore = useCompanyStore()
const dialogStore = useDialogStore()
const notificationStore = useNotificationStore()

const selectedBillId = ref(null)
const bills = ref([])
const kapData = ref(null)
const marginWarnings = ref([])
const markupOverrides = reactive({})
const dependentCosts = reactive({
  transport: 0,
  customs: 0,
  insurance: 0,
  other: 0,
})
const applyResult = ref(null)

const isCalculating = ref(false)
const isExporting = ref(false)
const isApplying = ref(false)

const companyId = computed(() => companyStore.selectedCompany?.id)

function apiBase() {
  return `/partner/companies/${companyId.value}/accounting`
}

function fmtAmt(amount) {
  if (amount === null || amount === undefined) return '-'
  const num = Number(amount) / 100
  return num.toLocaleString('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function setMarkup(itemId, value) {
  markupOverrides[itemId] = parseFloat(value)
}

function onBillChange() {
  kapData.value = null
  marginWarnings.value = []
  applyResult.value = null
}

async function loadBills() {
  try {
    const response = await window.axios.get('/bills', {
      params: { limit: 100 },
    })
    bills.value = response.data.data || response.data?.bills?.data || []
  } catch (error) {
    console.error('Failed to load bills:', error)
  }
}

async function calculateKap() {
  if (!selectedBillId.value || !companyId.value) return
  isCalculating.value = true
  kapData.value = null
  marginWarnings.value = []

  try {
    const params = {}
    if (Object.keys(markupOverrides).length > 0) {
      params.markup_overrides = markupOverrides
    }
    const dcArray = []
    if (dependentCosts.transport > 0) dcArray.push({ type: 'transport', amount: Math.round(dependentCosts.transport * 100) })
    if (dependentCosts.customs > 0) dcArray.push({ type: 'customs', amount: Math.round(dependentCosts.customs * 100) })
    if (dependentCosts.insurance > 0) dcArray.push({ type: 'insurance', amount: Math.round(dependentCosts.insurance * 100) })
    if (dependentCosts.other > 0) dcArray.push({ type: 'other', amount: Math.round(dependentCosts.other * 100) })
    if (dcArray.length > 0) params.dependent_costs = dcArray

    const response = await window.axios.get(`${apiBase()}/kap/${selectedBillId.value}`, { params })
    kapData.value = response.data.data
    marginWarnings.value = response.data.margin_check?.warnings || []
  } catch (error) {
    console.error('Failed to calculate KAP:', error)
    notificationStore.showNotification({
      type: 'error',
      message: 'Грешка при калкулација.',
    })
  } finally {
    isCalculating.value = false
  }
}

async function exportPdf() {
  if (!selectedBillId.value || !companyId.value) return
  isExporting.value = true
  try {
    const params = {}
    if (Object.keys(markupOverrides).length > 0) {
      params.markup_overrides = markupOverrides
    }

    const response = await window.axios.get(
      `${apiBase()}/kap/${selectedBillId.value}/export`,
      { params, responseType: 'blob' }
    )
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `kap_${kapData.value?.bill_number || selectedBillId.value}.pdf`
    link.click()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Failed to export KAP PDF:', error)
    notificationStore.showNotification({
      type: 'error',
      message: 'Грешка при преземање на PDF.',
    })
  } finally {
    isExporting.value = false
  }
}

async function applyPrices() {
  if (!selectedBillId.value || !companyId.value) return

  const confirmed = await dialogStore.openDialog({
    title: t('trade.apply_prices'),
    message: t('trade.apply_prices_confirm'),
    yesLabel: t('trade.apply_prices'),
    noLabel: 'Откажи',
    variant: 'primary',
  })

  if (!confirmed) return

  isApplying.value = true
  try {
    const payload = {
      bill_id: selectedBillId.value,
      price_type: 'wholesale',
      markup_overrides: Object.keys(markupOverrides).length > 0 ? markupOverrides : null,
      create_nivelacija: true,
    }
    const dcArray = []
    if (dependentCosts.transport > 0) dcArray.push({ type: 'transport', amount: Math.round(dependentCosts.transport * 100) })
    if (dependentCosts.customs > 0) dcArray.push({ type: 'customs', amount: Math.round(dependentCosts.customs * 100) })
    if (dependentCosts.insurance > 0) dcArray.push({ type: 'insurance', amount: Math.round(dependentCosts.insurance * 100) })
    if (dependentCosts.other > 0) dcArray.push({ type: 'other', amount: Math.round(dependentCosts.other * 100) })
    if (dcArray.length > 0) payload.dependent_costs = dcArray

    const response = await window.axios.post(`${apiBase()}/apply-prices`, payload)
    applyResult.value = response.data.data

    const changedCount = response.data.data?.changed?.length || 0
    notificationStore.showNotification({
      type: 'success',
      message: t('trade.prices_applied', { count: changedCount }),
    })
  } catch (error) {
    console.error('Failed to apply prices:', error)
    const msg = error.response?.data?.error || 'Грешка при примена на цени.'
    notificationStore.showNotification({ type: 'error', message: msg })
  } finally {
    isApplying.value = false
  }
}

onMounted(() => {
  loadBills()
})
</script>

// CLAUDE-CHECKPOINT
