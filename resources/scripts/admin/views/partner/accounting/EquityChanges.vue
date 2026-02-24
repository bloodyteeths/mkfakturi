<template>
  <BasePage>
    <BasePageHeader :title="$t('partner.accounting.equity_changes', 'Equity Changes')">
    </BasePageHeader>

    <!-- Company Selector -->
    <div class="mb-6">
      <BaseInputGroup :label="$t('partner.select_company')">
        <BaseMultiselect
          v-model="selectedCompanyId"
          :options="companies"
          :searchable="true"
          track-by="id"
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
        <BaseInputGroup :label="$t('general.year', 'Year')" required>
          <BaseMultiselect
            v-model="selectedYear"
            :options="yearOptions"
            label="label"
            value-prop="value"
          />
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

    <!-- Equity Changes Report -->
    <div v-if="data" class="bg-white rounded-lg shadow overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <h3 class="text-lg font-medium text-gray-900">
          {{ $t('reports.equity.title', 'Statement of Changes in Equity (Извештај за промени во капиталот)') }}
        </h3>
        <p class="text-sm text-gray-500">{{ $t('reports.equity.fiscal_year', 'Fiscal Year') }}: {{ selectedYear }}</p>
      </div>

      <div class="p-6 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-48"></th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                {{ $t('reports.equity.share_capital', 'Основен капитал') }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                {{ $t('reports.equity.reserves', 'Резерви') }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                {{ $t('reports.equity.retained_earnings', 'Задржана добивка') }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                {{ $t('reports.equity.revaluation', 'Ревалор. резерви') }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                {{ $t('reports.equity.other', 'Останато') }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase bg-gray-100">
                {{ $t('general.total', 'Вкупно') }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <!-- Opening Balance -->
            <tr class="bg-gray-50 font-semibold">
              <td class="px-4 py-3 text-sm text-gray-900">{{ $t('reports.equity.opening', 'Почетно салдо') }} (01.01.{{ selectedYear }})</td>
              <td class="px-4 py-3 text-sm text-right">{{ formatMoney(data.opening.share_capital) }}</td>
              <td class="px-4 py-3 text-sm text-right">{{ formatMoney(data.opening.reserves) }}</td>
              <td class="px-4 py-3 text-sm text-right">{{ formatMoney(data.opening.retained_earnings) }}</td>
              <td class="px-4 py-3 text-sm text-right">{{ formatMoney(data.opening.revaluation) }}</td>
              <td class="px-4 py-3 text-sm text-right">{{ formatMoney(data.opening.other) }}</td>
              <td class="px-4 py-3 text-sm text-right font-bold bg-gray-100">{{ formatMoney(data.opening.total) }}</td>
            </tr>

            <!-- Net Income -->
            <tr>
              <td class="px-4 py-3 text-sm text-gray-700">{{ $t('reports.equity.net_income', 'Нето добивка/загуба') }}</td>
              <td class="px-4 py-3 text-sm text-right text-gray-400">-</td>
              <td class="px-4 py-3 text-sm text-right text-gray-400">-</td>
              <td class="px-4 py-3 text-sm text-right" :class="amountClass(data.net_income)">{{ formatMoney(data.net_income) }}</td>
              <td class="px-4 py-3 text-sm text-right text-gray-400">-</td>
              <td class="px-4 py-3 text-sm text-right text-gray-400">-</td>
              <td class="px-4 py-3 text-sm text-right font-medium bg-gray-50" :class="amountClass(data.net_income)">{{ formatMoney(data.net_income) }}</td>
            </tr>

            <!-- Changes -->
            <tr v-if="data.changes.share_capital !== 0">
              <td class="px-4 py-3 text-sm text-gray-600 pl-8">{{ $t('reports.equity.capital_change', 'Промена во капитал') }}</td>
              <td class="px-4 py-3 text-sm text-right" :class="amountClass(data.changes.share_capital)">{{ formatMoney(data.changes.share_capital) }}</td>
              <td class="px-4 py-3 text-sm text-right text-gray-400">-</td>
              <td class="px-4 py-3 text-sm text-right text-gray-400">-</td>
              <td class="px-4 py-3 text-sm text-right text-gray-400">-</td>
              <td class="px-4 py-3 text-sm text-right text-gray-400">-</td>
              <td class="px-4 py-3 text-sm text-right font-medium bg-gray-50" :class="amountClass(data.changes.share_capital)">{{ formatMoney(data.changes.share_capital) }}</td>
            </tr>

            <tr v-if="data.changes.reserves !== 0">
              <td class="px-4 py-3 text-sm text-gray-600 pl-8">{{ $t('reports.equity.reserves_change', 'Промена во резерви') }}</td>
              <td class="px-4 py-3 text-sm text-right text-gray-400">-</td>
              <td class="px-4 py-3 text-sm text-right" :class="amountClass(data.changes.reserves)">{{ formatMoney(data.changes.reserves) }}</td>
              <td class="px-4 py-3 text-sm text-right text-gray-400">-</td>
              <td class="px-4 py-3 text-sm text-right text-gray-400">-</td>
              <td class="px-4 py-3 text-sm text-right text-gray-400">-</td>
              <td class="px-4 py-3 text-sm text-right font-medium bg-gray-50" :class="amountClass(data.changes.reserves)">{{ formatMoney(data.changes.reserves) }}</td>
            </tr>

            <!-- Total Changes -->
            <tr class="border-b-2 border-gray-300">
              <td class="px-4 py-3 text-sm font-semibold text-gray-900">{{ $t('reports.equity.total_changes', 'Вкупно промени') }}</td>
              <td class="px-4 py-3 text-sm text-right font-medium" :class="amountClass(data.changes.share_capital)">{{ formatMoney(data.changes.share_capital) }}</td>
              <td class="px-4 py-3 text-sm text-right font-medium" :class="amountClass(data.changes.reserves)">{{ formatMoney(data.changes.reserves) }}</td>
              <td class="px-4 py-3 text-sm text-right font-medium" :class="amountClass(data.changes.retained_earnings)">{{ formatMoney(data.changes.retained_earnings) }}</td>
              <td class="px-4 py-3 text-sm text-right font-medium" :class="amountClass(data.changes.revaluation)">{{ formatMoney(data.changes.revaluation) }}</td>
              <td class="px-4 py-3 text-sm text-right font-medium" :class="amountClass(data.changes.other)">{{ formatMoney(data.changes.other) }}</td>
              <td class="px-4 py-3 text-sm text-right font-bold bg-gray-100" :class="amountClass(data.changes.total)">{{ formatMoney(data.changes.total) }}</td>
            </tr>

            <!-- Closing Balance -->
            <tr class="bg-primary-50 font-bold">
              <td class="px-4 py-3 text-sm text-primary-900">{{ $t('reports.equity.closing', 'Крајно салдо') }} (31.12.{{ selectedYear }})</td>
              <td class="px-4 py-3 text-sm text-right text-primary-800">{{ formatMoney(data.closing.share_capital) }}</td>
              <td class="px-4 py-3 text-sm text-right text-primary-800">{{ formatMoney(data.closing.reserves) }}</td>
              <td class="px-4 py-3 text-sm text-right text-primary-800">{{ formatMoney(data.closing.retained_earnings) }}</td>
              <td class="px-4 py-3 text-sm text-right text-primary-800">{{ formatMoney(data.closing.revaluation) }}</td>
              <td class="px-4 py-3 text-sm text-right text-primary-800">{{ formatMoney(data.closing.other) }}</td>
              <td class="px-4 py-3 text-sm text-right text-primary-600 bg-primary-100">{{ formatMoney(data.closing.total) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="hasSearched && !data" class="bg-white rounded-lg shadow p-12 text-center">
      <BaseIcon name="ScaleIcon" class="mx-auto h-12 w-12 text-gray-400" />
      <p class="mt-2 text-sm text-gray-500">{{ $t('reports.equity.no_data', 'No equity data for this year.') }}</p>
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

const { t } = useI18n()
const consoleStore = useConsoleStore()
const notificationStore = useNotificationStore()

const selectedCompanyId = ref(null)
const data = ref(null)
const isLoading = ref(false)
const isExporting = ref(false)
const hasSearched = ref(false)
const selectedYear = ref(new Date().getFullYear() - 1)

const companies = computed(() => consoleStore.managedCompanies || [])

const selectedCompanyCurrency = computed(() => {
  if (!selectedCompanyId.value) return 'MKD'
  const company = companies.value.find(c => c.id === selectedCompanyId.value)
  return company?.currency?.code || 'MKD'
})

const yearOptions = computed(() => {
  const current = new Date().getFullYear()
  const options = []
  for (let y = current; y >= 2020; y--) {
    options.push({ label: String(y), value: y })
  }
  return options
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
    const response = await window.axios.get(`/partner/companies/${selectedCompanyId.value}/accounting/equity-changes`, {
      params: { year: selectedYear.value },
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
      `/partner/companies/${selectedCompanyId.value}/accounting/equity-changes/export`,
      {
        params: { year: selectedYear.value },
        responseType: 'blob',
      }
    )
    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `equity_changes_${selectedYear.value}.pdf`)
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
  if (amount === null || amount === undefined || amount === 0) return '-'
  return new Intl.NumberFormat('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount) + ' ' + selectedCompanyCurrency.value
}

function amountClass(amount) {
  if (!amount) return 'text-gray-500'
  return amount >= 0 ? 'text-green-700' : 'text-red-600'
}
</script>

<!-- CLAUDE-CHECKPOINT -->
