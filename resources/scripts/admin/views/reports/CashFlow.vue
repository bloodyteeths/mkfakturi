<template>
  <div class="grid gap-8 pt-10">
    <!-- Filters -->
    <div class="p-6 bg-white rounded-lg shadow">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <BaseInputGroup :label="$t('general.from_date')" required>
          <BaseDatePicker v-model="filters.start_date" :calendar-button="true" calendar-button-icon="CalendarDaysIcon" />
        </BaseInputGroup>
        <BaseInputGroup :label="$t('general.to_date')" required>
          <BaseDatePicker v-model="filters.end_date" :calendar-button="true" calendar-button-icon="CalendarDaysIcon" />
        </BaseInputGroup>
        <div class="flex items-end">
          <BaseButton variant="primary" class="w-full" :loading="isLoading" @click="loadReport">
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="MagnifyingGlassIcon" />
            </template>
            {{ $t('general.load') }}
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
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import moment from 'moment'

const { t } = useI18n()
const companyStore = useCompanyStore()
const data = ref(null)
const isLoading = ref(false)
const hasSearched = ref(false)

const filters = ref({
  start_date: moment().startOf('year').format('YYYY-MM-DD'),
  end_date: moment().endOf('year').format('YYYY-MM-DD'),
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

async function loadReport() {
  isLoading.value = true
  hasSearched.value = true
  try {
    const response = await window.axios.get('/accounting/cash-flow', {
      params: { start_date: filters.value.start_date, end_date: filters.value.end_date },
    })
    data.value = response.data.data
  } catch (error) {
    console.error('Failed to load cash flow:', error)
    data.value = null
  } finally {
    isLoading.value = false
  }
}

function formatMoney(amount) {
  if (amount === null || amount === undefined) return '-'
  const currency = companyStore.selectedCompanyCurrency
  return new Intl.NumberFormat('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount) + ' ' + (currency?.code || 'MKD')
}

function amountClass(amount) {
  if (!amount) return 'text-gray-900'
  return amount >= 0 ? 'text-green-700' : 'text-red-600'
}
</script>

<!-- CLAUDE-CHECKPOINT -->
