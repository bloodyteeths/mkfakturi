<template>
  <BasePage>
    <BasePageHeader :title="$t('partner.accounting.cash_flow', 'Cash Flow Statement')">
    </BasePageHeader>

    <!-- Company Selector -->
    <div class="mb-6">
      <BaseInputGroup :label="$t('partner.select_company')">
        <BaseMultiselect
          v-model="selectedCompanyId"
          :options="companies"
          :searchable="true"
          track-by="name"
          label="name"
          value-prop="id"
          :placeholder="$t('partner.select_company_placeholder')"
          @update:model-value="onCompanyChange"
        />
      </BaseInputGroup>
    </div>

    <!-- Filters -->
    <div v-if="selectedCompanyId" class="p-6 bg-white rounded-lg shadow mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <BaseInputGroup :label="$t('general.from_date')" required>
          <BaseDatePicker v-model="filters.start_date" :calendar-button="true" calendar-button-icon="CalendarDaysIcon" />
        </BaseInputGroup>
        <BaseInputGroup :label="$t('general.to_date')" required>
          <BaseDatePicker v-model="filters.end_date" :calendar-button="true" calendar-button-icon="CalendarDaysIcon" />
        </BaseInputGroup>
        <div class="flex items-end space-x-2">
          <BaseButton variant="primary" class="flex-1" :loading="isLoading" @click="loadReport">
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="MagnifyingGlassIcon" />
            </template>
            {{ $t('general.load') }}
          </BaseButton>
          <BaseButton
            variant="primary-outline"
            :loading="isExporting"
            :disabled="!data"
            @click="exportPdf"
          >
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="ArrowDownTrayIcon" />
            </template>
            {{ $t('reports.cash_flow.export_pdf', 'Export PDF') }}
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Cash Flow Report -->
    <div v-if="data" class="bg-white rounded-lg shadow overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <h3 class="text-lg font-medium text-gray-900">
          {{ $t('reports.cash_flow.title', 'Cash Flow Statement (Извештај за парични текови)') }}
        </h3>
        <p class="text-sm text-gray-500">{{ filters.start_date }} — {{ filters.end_date }}</p>
      </div>

      <div class="p-6">
        <table class="min-w-full">
          <thead>
            <tr class="border-b-2 border-gray-300">
              <th class="text-left py-2 text-xs font-medium text-gray-500 uppercase">{{ $t('general.description') }}</th>
              <th class="text-right py-2 text-xs font-medium text-gray-500 uppercase">{{ $t('general.amount') }}</th>
            </tr>
          </thead>
          <tbody>
            <!-- Operating Activities -->
            <tr class="border-b border-gray-200 bg-gray-50">
              <td colspan="2" class="py-2 px-2 text-sm font-bold text-gray-900 uppercase">
                {{ $t('reports.cash_flow.operating', 'I. Оперативни активности') }}
              </td>
            </tr>
            <tr class="border-b border-gray-100">
              <td class="py-2 px-2 text-sm font-semibold text-gray-900">{{ $t('reports.cash_flow.net_income', 'Нето добивка') }}</td>
              <td class="py-2 px-2 text-sm text-right font-semibold" :class="amountClass(data.operating.net_income)">{{ formatMoney(data.operating.net_income) }}</td>
            </tr>
            <tr v-for="(item, key) in operatingAdjustments" :key="key" class="border-b border-gray-50">
              <td class="py-1.5 pl-6 pr-2 text-sm text-gray-600">{{ item.label }}</td>
              <td class="py-1.5 px-2 text-sm text-right" :class="amountClass(item.amount)">{{ formatMoney(item.amount) }}</td>
            </tr>
            <tr class="border-b-2 border-gray-300 bg-blue-50">
              <td class="py-2 px-2 text-sm font-bold text-gray-900">{{ $t('reports.cash_flow.operating_total', 'Нето парични средства од оперативни активности') }}</td>
              <td class="py-2 px-2 text-sm text-right font-bold" :class="amountClass(data.operating.total)">{{ formatMoney(data.operating.total) }}</td>
            </tr>

            <!-- Investing Activities -->
            <tr class="border-b border-gray-200 bg-gray-50">
              <td colspan="2" class="py-2 px-2 text-sm font-bold text-gray-900 uppercase">
                {{ $t('reports.cash_flow.investing', 'II. Инвестициски активности') }}
              </td>
            </tr>
            <tr v-for="(item, key) in investingItems" :key="key" class="border-b border-gray-50">
              <td class="py-1.5 pl-6 pr-2 text-sm text-gray-600">{{ item.label }}</td>
              <td class="py-1.5 px-2 text-sm text-right" :class="amountClass(item.amount)">{{ formatMoney(item.amount) }}</td>
            </tr>
            <tr class="border-b-2 border-gray-300 bg-blue-50">
              <td class="py-2 px-2 text-sm font-bold text-gray-900">{{ $t('reports.cash_flow.investing_total', 'Нето парични средства од инвестициски активности') }}</td>
              <td class="py-2 px-2 text-sm text-right font-bold" :class="amountClass(data.investing.total)">{{ formatMoney(data.investing.total) }}</td>
            </tr>

            <!-- Financing Activities -->
            <tr class="border-b border-gray-200 bg-gray-50">
              <td colspan="2" class="py-2 px-2 text-sm font-bold text-gray-900 uppercase">
                {{ $t('reports.cash_flow.financing', 'III. Финансиски активности') }}
              </td>
            </tr>
            <tr v-for="(item, key) in financingItems" :key="key" class="border-b border-gray-50">
              <td class="py-1.5 pl-6 pr-2 text-sm text-gray-600">{{ item.label }}</td>
              <td class="py-1.5 px-2 text-sm text-right" :class="amountClass(item.amount)">{{ formatMoney(item.amount) }}</td>
            </tr>
            <tr class="border-b-2 border-gray-300 bg-blue-50">
              <td class="py-2 px-2 text-sm font-bold text-gray-900">{{ $t('reports.cash_flow.financing_total', 'Нето парични средства од финансиски активности') }}</td>
              <td class="py-2 px-2 text-sm text-right font-bold" :class="amountClass(data.financing.total)">{{ formatMoney(data.financing.total) }}</td>
            </tr>

            <!-- Summary -->
            <tr><td colspan="2" class="py-2"></td></tr>
            <tr class="border-b border-gray-200">
              <td class="py-2 px-2 text-sm text-gray-900">{{ $t('reports.cash_flow.cash_start', 'Парични средства на почеток') }}</td>
              <td class="py-2 px-2 text-sm text-right font-medium">{{ formatMoney(data.summary.cash_start) }}</td>
            </tr>
            <tr class="border-b border-gray-200">
              <td class="py-2 px-2 text-sm font-semibold text-gray-900">{{ $t('reports.cash_flow.net_change', 'Нето промена на парични средства') }}</td>
              <td class="py-2 px-2 text-sm text-right font-semibold" :class="amountClass(data.summary.net_change)">{{ formatMoney(data.summary.net_change) }}</td>
            </tr>
            <tr class="bg-primary-50 border-2 border-primary-200">
              <td class="py-3 px-2 text-base font-bold text-primary-900">{{ $t('reports.cash_flow.cash_end', 'Парични средства на крај') }}</td>
              <td class="py-3 px-2 text-base text-right font-bold text-primary-600">{{ formatMoney(data.summary.cash_end) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="hasSearched && !data" class="bg-white rounded-lg shadow p-12 text-center">
      <BaseIcon name="BanknotesIcon" class="mx-auto h-12 w-12 text-gray-400" />
      <p class="mt-2 text-sm text-gray-500">{{ $t('reports.cash_flow.no_data', 'No cash flow data for this period.') }}</p>
    </div>

    <!-- Select company -->
    <div v-if="!selectedCompanyId" class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-12">
      <BaseIcon name="BuildingOfficeIcon" class="h-12 w-12 text-gray-400" />
      <p class="mt-2 text-sm text-gray-500">{{ $t('partner.accounting.select_company_to_view') }}</p>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import { useNotificationStore } from '@/scripts/stores/notification'
import moment from 'moment'

const { t } = useI18n()
const consoleStore = useConsoleStore()
const notificationStore = useNotificationStore()

const selectedCompanyId = ref(null)
const data = ref(null)
const isLoading = ref(false)
const isExporting = ref(false)
const hasSearched = ref(false)

const filters = ref({
  start_date: moment().startOf('year').format('YYYY-MM-DD'),
  end_date: moment().endOf('year').format('YYYY-MM-DD'),
})

const companies = computed(() => consoleStore.managedCompanies || [])

const selectedCompanyCurrency = computed(() => {
  if (!selectedCompanyId.value) return 'MKD'
  const company = companies.value.find(c => c.id === selectedCompanyId.value)
  return company?.currency?.code || 'MKD'
})

const operatingAdjustments = computed(() => {
  if (!data.value) return []
  const o = data.value.operating
  return [
    { label: t('reports.cash_flow.depreciation', 'Амортизација'), amount: o.depreciation },
    { label: t('reports.cash_flow.receivables', 'Промена на побарувања'), amount: o.receivables_change },
    { label: t('reports.cash_flow.inventory', 'Промена на залихи'), amount: o.inventory_change },
    { label: t('reports.cash_flow.prepaid', 'Промена на аванси'), amount: o.prepaid_change },
    { label: t('reports.cash_flow.payables', 'Промена на обврски'), amount: o.payables_change },
    { label: t('reports.cash_flow.tax_payable', 'Промена на даночни обврски'), amount: o.tax_payable_change },
    { label: t('reports.cash_flow.other_liabilities', 'Промена на останати обврски'), amount: o.other_current_liabilities_change },
  ].filter(item => item.amount !== 0)
})

const investingItems = computed(() => {
  if (!data.value) return []
  const i = data.value.investing
  return [
    { label: t('reports.cash_flow.fixed_assets', 'Основни средства'), amount: i.fixed_assets },
    { label: t('reports.cash_flow.intangible', 'Нематеријални средства'), amount: i.intangible_assets },
    { label: t('reports.cash_flow.investments', 'Инвестиции'), amount: i.investments },
  ].filter(item => item.amount !== 0)
})

const financingItems = computed(() => {
  if (!data.value) return []
  const f = data.value.financing
  return [
    { label: t('reports.cash_flow.long_term_debt', 'Долгорочни обврски'), amount: f.long_term_debt },
    { label: t('reports.cash_flow.short_term_debt', 'Краткорочни обврски'), amount: f.short_term_debt },
    { label: t('reports.cash_flow.equity_changes', 'Промени во капитал'), amount: f.equity_changes },
  ].filter(item => item.amount !== 0)
})

onMounted(async () => {
  try {
    await consoleStore.fetchCompanies()
    if (companies.value.length > 0) {
      selectedCompanyId.value = companies.value[0].id
    }
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: t('errors.failed_to_load_companies') })
  }
})

function onCompanyChange() {
  data.value = null
  hasSearched.value = false
}

async function loadReport() {
  if (!selectedCompanyId.value) return
  isLoading.value = true
  hasSearched.value = true
  try {
    const response = await window.axios.get(`/partner/companies/${selectedCompanyId.value}/accounting/cash-flow`, {
      params: { start_date: filters.value.start_date, end_date: filters.value.end_date },
    })
    data.value = response.data.data
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: error.response?.data?.message || t('errors.failed_to_load_data') })
    data.value = null
  } finally {
    isLoading.value = false
  }
}

async function exportPdf() {
  if (!selectedCompanyId.value || !data.value) return
  isExporting.value = true
  try {
    const response = await window.axios.get(
      `/partner/companies/${selectedCompanyId.value}/accounting/cash-flow/export`,
      {
        params: { start_date: filters.value.start_date, end_date: filters.value.end_date },
        responseType: 'blob',
      }
    )
    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `cash_flow_${filters.value.start_date}_${filters.value.end_date}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: error.response?.data?.message || 'Failed to export PDF' })
  } finally {
    isExporting.value = false
  }
}

function formatMoney(amount) {
  if (amount === null || amount === undefined) return '-'
  return new Intl.NumberFormat('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount) + ' ' + selectedCompanyCurrency.value
}

function amountClass(amount) {
  if (!amount) return 'text-gray-900'
  return amount >= 0 ? 'text-green-700' : 'text-red-600'
}
</script>

<!-- CLAUDE-CHECKPOINT -->
