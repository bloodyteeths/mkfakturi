<template>
  <BasePage>
    <BasePageHeader :title="$t('trade.plt_title', 'ПЛТ — Приемен лист (мало)')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('stock.title')" to="/admin/stock" />
        <BaseBreadcrumbItem :title="$t('trade.tab_plt', 'ПЛТ')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <div class="flex items-center space-x-3">
          <BaseButton
            v-if="pltData"
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
            v-if="pltData"
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
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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

          <div class="flex items-end">
            <BaseButton
              variant="primary"
              class="w-full"
              :loading="isCalculating"
              :disabled="!selectedBillId"
              @click="calculatePlt"
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
    <div v-if="pltData" class="mb-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-400">
        <p class="text-xs text-gray-500 uppercase">Фактура</p>
        <p class="text-lg font-bold text-blue-700">{{ pltData.bill_number }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-gray-400">
        <p class="text-xs text-gray-500 uppercase">Добавувач</p>
        <p class="text-lg font-bold text-gray-700">{{ pltData.supplier_name || '-' }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-400">
        <p class="text-xs text-gray-500 uppercase">Датум</p>
        <p class="text-lg font-bold text-green-700">{{ pltData.bill_date }}</p>
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
    <div v-else-if="!pltData" class="text-center py-12">
      <BaseIcon name="DocumentTextIcon" class="h-12 w-12 text-gray-400 mx-auto mb-4" />
      <h3 class="text-lg font-medium text-gray-900">{{ $t('trade.plt_title', 'ПЛТ — Приемен лист (мало)') }}</h3>
      <p class="text-gray-500 mt-2">Изберете фактура за да ја видите калкулацијата на малопродажна цена.</p>
    </div>

    <!-- PLT Table -->
    <BaseCard v-if="pltData && pltData.items">
      <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
        <div>
          <h3 class="text-lg font-medium text-gray-900">{{ $t('trade.plt_title', 'Образец ПЛТ') }}</h3>
          <p class="text-sm text-gray-500">{{ pltData.bill_number }} - {{ pltData.bill_date }}</p>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-800 text-white">
            <tr>
              <th class="px-3 py-2 text-center text-xs font-medium uppercase">#</th>
              <th class="px-3 py-2 text-left text-xs font-medium uppercase">Артикл</th>
              <th class="px-3 py-2 text-right text-xs font-medium uppercase">Количина</th>
              <th class="px-3 py-2 text-right text-xs font-medium uppercase">{{ $t('trade.nabavna_unit', 'Набавна ед.') }}</th>
              <th class="px-3 py-2 text-right text-xs font-medium uppercase">{{ $t('trade.nabavna_bez_ddv', 'Набавна без ДДВ') }}</th>
              <th class="px-3 py-2 text-center text-xs font-medium uppercase">{{ $t('trade.markup_percent', 'Маржа %') }}</th>
              <th class="px-3 py-2 text-right text-xs font-medium uppercase">{{ $t('trade.marzha_iznos', 'Маржа изн.') }}</th>
              <th class="px-3 py-2 text-right text-xs font-medium uppercase">ДДВ %</th>
              <th class="px-3 py-2 text-right text-xs font-medium uppercase">ДДВ изн.</th>
              <th class="px-3 py-2 text-right text-xs font-medium uppercase">{{ $t('trade.mp_unit', 'МП ед.') }}</th>
              <th class="px-3 py-2 text-right text-xs font-medium uppercase">{{ $t('trade.mp_iznos', 'МП вкупно') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="(item, index) in pltData.items" :key="index" class="hover:bg-gray-50">
              <td class="px-3 py-2 text-sm text-gray-500 text-center">{{ index + 1 }}</td>
              <td class="px-3 py-2 text-sm text-gray-900 font-medium">
                {{ item.item_name || item.name }}
                <span v-if="item.sku" class="block text-xs text-gray-400">{{ item.sku }}</span>
              </td>
              <td class="px-3 py-2 text-sm text-right text-gray-900">{{ item.quantity }}</td>
              <td class="px-3 py-2 text-sm text-right font-mono text-gray-900">{{ fmtAmt(item.unit_price_nabavna) }}</td>
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
              <td class="px-3 py-2 text-sm text-right text-gray-600">{{ item.vat_rate || 18 }}%</td>
              <td class="px-3 py-2 text-sm text-right font-mono text-gray-600">{{ fmtAmt(item.ddv_iznos) }}</td>
              <td class="px-3 py-2 text-sm text-right font-mono text-blue-700 font-semibold">{{ fmtAmt(item.unit_price_prodazhna) }}</td>
              <td class="px-3 py-2 text-sm text-right font-mono text-blue-700 font-semibold">{{ fmtAmt(item.total_prodazhna) }}</td>
            </tr>
          </tbody>
          <tfoot class="bg-gray-800 text-white font-semibold">
            <tr v-if="pltData.totals">
              <td colspan="4" class="px-3 py-3 text-sm">ВКУПНО</td>
              <td class="px-3 py-3 text-sm text-right font-mono">{{ fmtAmt(pltData.totals.total_nabavna_bez_ddv) }}</td>
              <td></td>
              <td class="px-3 py-3 text-sm text-right font-mono">{{ fmtAmt(pltData.totals.total_marzha) }}</td>
              <td></td>
              <td class="px-3 py-3 text-sm text-right font-mono">{{ fmtAmt(pltData.totals.total_ddv) }}</td>
              <td></td>
              <td class="px-3 py-3 text-sm text-right font-mono">{{ fmtAmt(pltData.totals.total_prodazhna) }}</td>
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
const pltData = ref(null)
const marginWarnings = ref([])
const markupOverrides = reactive({})
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
  pltData.value = null
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

async function calculatePlt() {
  if (!selectedBillId.value || !companyId.value) return
  isCalculating.value = true
  pltData.value = null
  marginWarnings.value = []

  try {
    const params = {}
    if (Object.keys(markupOverrides).length > 0) {
      params.markup_overrides = markupOverrides
    }

    const response = await window.axios.get(`${apiBase()}/plt/${selectedBillId.value}`, { params })
    pltData.value = response.data.data
    marginWarnings.value = response.data.margin_check?.warnings || []
  } catch (error) {
    console.error('Failed to calculate PLT:', error)
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
      `${apiBase()}/plt/${selectedBillId.value}/export`,
      { params, responseType: 'blob' }
    )
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `plt_${pltData.value?.bill_number || selectedBillId.value}.pdf`
    link.click()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Failed to export PLT PDF:', error)
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
      price_type: 'retail',
      markup_overrides: Object.keys(markupOverrides).length > 0 ? markupOverrides : null,
      create_nivelacija: true,
    }

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
