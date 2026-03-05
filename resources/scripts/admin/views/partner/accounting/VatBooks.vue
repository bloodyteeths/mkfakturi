<template>
  <BasePage>
    <BasePageHeader :title="$t('partner.accounting.vat_books', 'Книга на ДДВ')">
      <template #actions>
        <BaseButton v-if="currentEntries.length > 0" variant="primary-outline" @click="exportCsv">
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="ArrowDownTrayIcon" />
          </template>
          {{ $t('general.export') }}
        </BaseButton>
      </template>
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

    <div v-if="!selectedCompanyId" class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-12">
      <BaseIcon name="BuildingOfficeIcon" class="h-12 w-12 text-gray-400" />
      <p class="mt-2 text-sm text-gray-500">{{ $t('partner.accounting.select_company_to_view') }}</p>
    </div>

    <template v-if="selectedCompanyId">
      <!-- Filters -->
      <div class="p-6 bg-white rounded-lg shadow mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <BaseInputGroup :label="$t('general.from_date')" required>
            <BaseDatePicker v-model="filters.start_date" :calendar-button="true" calendar-button-icon="CalendarDaysIcon" />
          </BaseInputGroup>
          <BaseInputGroup :label="$t('general.to_date')" required>
            <BaseDatePicker v-model="filters.end_date" :calendar-button="true" calendar-button-icon="CalendarDaysIcon" />
          </BaseInputGroup>
          <div class="flex items-end">
            <BaseButton variant="primary" class="w-full" :loading="isLoading" @click="loadVatBooks">
              <template #left="slotProps">
                <BaseIcon :class="slotProps.class" name="MagnifyingGlassIcon" />
              </template>
              {{ $t('general.load') }}
            </BaseButton>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium"
            :class="activeTab === tab.key
              ? 'border-primary-500 text-primary-600'
              : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
            @click="activeTab = tab.key"
          >
            {{ tab.label }}
            <span v-if="tab.count > 0" class="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">
              {{ tab.count }}
            </span>
          </button>
        </nav>
      </div>

      <!-- Output Book (Sales Invoices) -->
      <template v-if="activeTab === 'output'">
        <div v-if="outputEntries.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-medium text-gray-900">{{ $t('partner.accounting.vat_output_book', 'Книга на излезни фактури') }}</h3>
            <p class="text-sm text-gray-500">{{ filters.start_date }} &mdash; {{ filters.end_date }}</p>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                  <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('general.date') }}</th>
                  <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('invoices.invoice_number', 'Број') }}</th>
                  <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('partner.accounting.customer_name', 'Купувач') }}</th>
                  <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('partner.accounting.total_with_vat', 'Вкупно со ДДВ') }}</th>
                  <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('partner.accounting.taxable_base', 'Основица') }}</th>
                  <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('partner.accounting.vat_amount', 'ДДВ') }}</th>
                  <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ $t('partner.accounting.vat_rate', 'Стапка') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="(entry, i) in outputEntries" :key="entry.id" class="hover:bg-gray-50">
                  <td class="px-3 py-3 text-sm text-gray-500">{{ i + 1 }}</td>
                  <td class="px-3 py-3 text-sm text-gray-900 whitespace-nowrap">{{ entry.date }}</td>
                  <td class="px-3 py-3 text-sm text-primary-600 font-medium">{{ entry.number }}</td>
                  <td class="px-3 py-3 text-sm text-gray-900 max-w-xs truncate">{{ entry.party_name }}</td>
                  <td class="px-3 py-3 text-sm text-right font-medium text-gray-900">{{ formatMoney(entry.total) }}</td>
                  <td class="px-3 py-3 text-sm text-right text-gray-900">{{ formatMoney(entry.taxable_base) }}</td>
                  <td class="px-3 py-3 text-sm text-right text-gray-900">{{ formatMoney(entry.vat_amount) }}</td>
                  <td class="px-3 py-3 text-sm text-center">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" :class="rateClass(entry.vat_rate)">
                      {{ entry.vat_rate }}%
                    </span>
                  </td>
                </tr>
              </tbody>
              <tfoot class="bg-gray-100 font-semibold">
                <tr>
                  <td colspan="4" class="px-3 py-3 text-sm">{{ $t('general.total') }} ({{ outputEntries.length }})</td>
                  <td class="px-3 py-3 text-sm text-right">{{ formatMoney(outputTotals.total) }}</td>
                  <td class="px-3 py-3 text-sm text-right">{{ formatMoney(outputTotals.taxable_base) }}</td>
                  <td class="px-3 py-3 text-sm text-right">{{ formatMoney(outputTotals.vat_amount) }}</td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
        <div v-else-if="hasSearched && !isLoading" class="bg-white rounded-lg shadow p-12 text-center">
          <BaseIcon name="DocumentTextIcon" class="mx-auto h-12 w-12 text-gray-400" />
          <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('partner.accounting.no_output_invoices', 'Нема излезни фактури') }}</h3>
          <p class="mt-1 text-sm text-gray-500">{{ $t('partner.accounting.no_output_invoices_desc', 'Нема пронајдено фактури за избраниот период.') }}</p>
        </div>
      </template>

      <!-- Input Book (Purchase Bills) -->
      <template v-if="activeTab === 'input'">
        <div v-if="inputEntries.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-medium text-gray-900">{{ $t('partner.accounting.vat_input_book', 'Книга на влезни фактури') }}</h3>
            <p class="text-sm text-gray-500">{{ filters.start_date }} &mdash; {{ filters.end_date }}</p>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                  <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('general.date') }}</th>
                  <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('partner.accounting.bill_number', 'Број') }}</th>
                  <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('partner.accounting.supplier_name', 'Добавувач') }}</th>
                  <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('partner.accounting.total_with_vat', 'Вкупно со ДДВ') }}</th>
                  <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('partner.accounting.taxable_base', 'Основица') }}</th>
                  <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('partner.accounting.vat_amount', 'ДДВ') }}</th>
                  <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ $t('partner.accounting.vat_rate', 'Стапка') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="(entry, i) in inputEntries" :key="entry.id" class="hover:bg-gray-50">
                  <td class="px-3 py-3 text-sm text-gray-500">{{ i + 1 }}</td>
                  <td class="px-3 py-3 text-sm text-gray-900 whitespace-nowrap">{{ entry.date }}</td>
                  <td class="px-3 py-3 text-sm text-primary-600 font-medium">{{ entry.number }}</td>
                  <td class="px-3 py-3 text-sm text-gray-900 max-w-xs truncate">{{ entry.party_name }}</td>
                  <td class="px-3 py-3 text-sm text-right font-medium text-gray-900">{{ formatMoney(entry.total) }}</td>
                  <td class="px-3 py-3 text-sm text-right text-gray-900">{{ formatMoney(entry.taxable_base) }}</td>
                  <td class="px-3 py-3 text-sm text-right text-gray-900">{{ formatMoney(entry.vat_amount) }}</td>
                  <td class="px-3 py-3 text-sm text-center">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" :class="rateClass(entry.vat_rate)">
                      {{ entry.vat_rate }}%
                    </span>
                  </td>
                </tr>
              </tbody>
              <tfoot class="bg-gray-100 font-semibold">
                <tr>
                  <td colspan="4" class="px-3 py-3 text-sm">{{ $t('general.total') }} ({{ inputEntries.length }})</td>
                  <td class="px-3 py-3 text-sm text-right">{{ formatMoney(inputTotals.total) }}</td>
                  <td class="px-3 py-3 text-sm text-right">{{ formatMoney(inputTotals.taxable_base) }}</td>
                  <td class="px-3 py-3 text-sm text-right">{{ formatMoney(inputTotals.vat_amount) }}</td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
        <div v-else-if="hasSearched && !isLoading" class="bg-white rounded-lg shadow p-12 text-center">
          <BaseIcon name="DocumentTextIcon" class="mx-auto h-12 w-12 text-gray-400" />
          <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('partner.accounting.no_input_bills', 'Нема влезни фактури') }}</h3>
          <p class="mt-1 text-sm text-gray-500">{{ $t('partner.accounting.no_input_bills_desc', 'Нема пронајдено набавки/трошоци за избраниот период.') }}</p>
        </div>
      </template>

      <!-- Loading -->
      <div v-if="isLoading" class="bg-white rounded-lg shadow overflow-hidden p-6">
        <div v-for="i in 5" :key="i" class="flex space-x-4 animate-pulse mb-4">
          <div class="h-4 bg-gray-200 rounded w-8"></div>
          <div class="h-4 bg-gray-200 rounded w-24"></div>
          <div class="h-4 bg-gray-200 rounded w-20"></div>
          <div class="h-4 bg-gray-200 rounded w-40"></div>
          <div class="h-4 bg-gray-200 rounded w-24"></div>
          <div class="h-4 bg-gray-200 rounded w-24"></div>
          <div class="h-4 bg-gray-200 rounded w-20"></div>
        </div>
      </div>

      <!-- Initial State -->
      <div v-if="!hasSearched && !isLoading" class="bg-white rounded-lg shadow p-12 text-center">
        <BaseIcon name="BookOpenIcon" class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('partner.accounting.vat_books_prompt', 'Изберете период за ДДВ книгите') }}</h3>
        <p class="mt-1 text-sm text-gray-500">{{ $t('partner.accounting.vat_books_hint', 'Книга на влезни и излезни фактури со ДДВ податоци.') }}</p>
      </div>
    </template>
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
const activeTab = ref('output')
const isLoading = ref(false)
const hasSearched = ref(false)
const outputEntries = ref([])
const inputEntries = ref([])

const filters = ref({
  start_date: `${new Date().getFullYear()}-01-01`,
  end_date: new Date().toISOString().slice(0, 10),
})

const companies = computed(() => consoleStore.managedCompanies || [])

const tabs = computed(() => [
  { key: 'output', label: t('partner.accounting.output_book', 'Излезни фактури'), count: outputEntries.value.length },
  { key: 'input', label: t('partner.accounting.input_book', 'Влезни фактури'), count: inputEntries.value.length },
])

const currentEntries = computed(() => activeTab.value === 'output' ? outputEntries.value : inputEntries.value)

function calcTotals(entries) {
  return entries.reduce((acc, e) => ({
    total: acc.total + (e.total || 0),
    taxable_base: acc.taxable_base + (e.taxable_base || 0),
    vat_amount: acc.vat_amount + (e.vat_amount || 0),
  }), { total: 0, taxable_base: 0, vat_amount: 0 })
}

const outputTotals = computed(() => calcTotals(outputEntries.value))
const inputTotals = computed(() => calcTotals(inputEntries.value))

onMounted(async () => {
  await consoleStore.fetchCompanies()
  if (companies.value.length === 1) {
    selectedCompanyId.value = companies.value[0].id
  }
})

function onCompanyChange() {
  outputEntries.value = []
  inputEntries.value = []
  hasSearched.value = false
}

async function loadVatBooks() {
  if (!selectedCompanyId.value) return
  isLoading.value = true
  hasSearched.value = true
  outputEntries.value = []
  inputEntries.value = []

  try {
    const response = await window.axios.get(`/partner/companies/${selectedCompanyId.value}/accounting/vat-books`, {
      params: {
        from_date: filters.value.start_date,
        to_date: filters.value.end_date,
      },
    })
    const data = response.data?.data || response.data
    outputEntries.value = data.output || []
    inputEntries.value = data.input || []
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || 'Failed to load VAT books',
    })
  } finally {
    isLoading.value = false
  }
}

function formatMoney(amount) {
  if (amount === null || amount === undefined) return '-'
  const value = Math.abs(amount) / 100
  const sign = amount < 0 ? '-' : ''
  return sign + new Intl.NumberFormat('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value) + ' ден.'
}

function rateClass(rate) {
  if (rate === 18) return 'bg-blue-100 text-blue-700'
  if (rate === 5) return 'bg-green-100 text-green-700'
  if (rate === 0) return 'bg-gray-100 text-gray-700'
  return 'bg-amber-100 text-amber-700'
}

function exportCsv() {
  const entries = currentEntries.value
  if (!entries.length) return
  const bookType = activeTab.value === 'output' ? 'излезни' : 'влезни'
  const headers = ['#', 'Датум', 'Број', activeTab.value === 'output' ? 'Купувач' : 'Добавувач', 'Вкупно со ДДВ', 'Основица', 'ДДВ', 'Стапка %']
  const rows = entries.map((e, i) => [
    i + 1, e.date, e.number, e.party_name,
    (e.total / 100).toFixed(2), (e.taxable_base / 100).toFixed(2),
    (e.vat_amount / 100).toFixed(2), e.vat_rate,
  ])
  const csvContent = [headers.join(','), ...rows.map(r => r.map(c => `"${String(c).replace(/"/g, '""')}"`).join(','))].join('\n')
  const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = `ddv_kniga_${bookType}_${filters.value.start_date}_${filters.value.end_date}.csv`
  link.click()
}
</script>

<!-- CLAUDE-CHECKPOINT -->
