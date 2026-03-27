<template>
  <BasePage>
    <BasePageHeader :title="$t('partner.accounting.vat_books', 'Книга на ДДВ')">
      <template #actions>
        <div v-if="currentEntries.length > 0" class="flex space-x-2">
          <BaseButton variant="primary-outline" @click="exportCsv">
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="ArrowDownTrayIcon" />
            </template>
            CSV
          </BaseButton>
          <BaseButton variant="primary" :loading="isExportingPdf" @click="previewPdf">
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="EyeIcon" />
            </template>
            PDF
          </BaseButton>
          <BaseButton variant="primary-outline" :loading="isExportingUjp" @click="exportUjpPdf">
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="DocumentTextIcon" />
            </template>
            {{ $t('partner.accounting.ujp_format', 'УЈП формат') }}
          </BaseButton>
        </div>
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
          <div class="flex flex-col items-end">
            <BaseButton variant="primary" class="w-full" :loading="isLoading" :disabled="!canLoad" @click="loadVatBooks">
              <template #left="slotProps">
                <BaseIcon :class="slotProps.class" name="MagnifyingGlassIcon" />
              </template>
              {{ $t('general.load') }}
            </BaseButton>
            <p v-if="dateError" class="mt-1 text-xs text-red-500">{{ dateError }}</p>
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

      <!-- Rate Summary -->
      <div v-if="hasSearched && !isLoading && currentRateSummary.length > 0" class="mb-6 bg-white rounded-lg shadow p-4">
        <h4 class="text-sm font-semibold text-gray-700 mb-3">
          {{ $t('partner.accounting.vat_summary_by_rate', 'Рекапитулација по стапка') }}
        </h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
          <div
            v-for="summary in currentRateSummary"
            :key="summary.rate"
            class="flex items-center justify-between rounded-lg border p-3"
            :class="summary.rate === 18 ? 'border-blue-200 bg-blue-50' : summary.rate === 5 ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-gray-50'"
          >
            <div>
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold" :class="rateClass(summary.rate)">
                {{ summary.rate }}%
              </span>
              <span class="ml-2 text-xs text-gray-500">{{ summary.count }} {{ summary.count === 1 ? 'факт.' : 'факт.' }}</span>
            </div>
            <div class="text-right">
              <div class="text-xs text-gray-500">{{ $t('partner.accounting.taxable_base', 'Основица') }}: {{ formatMoney(summary.taxable_base) }}</div>
              <div class="text-sm font-semibold" :class="summary.rate === 18 ? 'text-blue-700' : summary.rate === 5 ? 'text-green-700' : 'text-gray-700'">
                {{ $t('partner.accounting.vat_amount', 'ДДВ') }}: {{ formatMoney(summary.vat_amount) }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Output Book (Sales Invoices) -->
      <template v-if="activeTab === 'output'">
        <div v-if="outputEntries.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
            <div>
              <h3 class="text-lg font-medium text-gray-900">{{ $t('partner.accounting.vat_output_book', 'Книга на излезни фактури') }}</h3>
              <p class="text-sm text-gray-500">{{ filters.start_date }} &mdash; {{ filters.end_date }}</p>
            </div>
            <div v-if="hasOverrides" class="flex items-center space-x-2">
              <span class="text-xs text-amber-600 font-medium">{{ overrideCount }} {{ $t('partner.accounting.manual_corrections', 'рачни корекции') }}</span>
              <BaseButton variant="danger-outline" size="sm" @click="clearAllOverrides">
                {{ $t('partner.accounting.reset_all', 'Ресетирај') }}
              </BaseButton>
            </div>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                  <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('general.date') }}</th>
                  <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('invoices.invoice_number', 'Број') }}</th>
                  <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('partner.accounting.customer_name', 'Купувач') }}</th>
                  <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('partner.accounting.edb', 'ЕДБ') }}</th>
                  <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('partner.accounting.total_with_vat', 'Вкупно со ДДВ') }}</th>
                  <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('partner.accounting.taxable_base', 'Основица') }}</th>
                  <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('partner.accounting.vat_amount', 'ДДВ') }}</th>
                  <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ $t('partner.accounting.vat_rate', 'Стапка') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr
                  v-for="(entry, i) in outputEntries"
                  :key="entry.id"
                  class="hover:bg-gray-50"
                  :class="{
                    'bg-amber-50': isOverridden('output', entry.id),
                    'bg-red-50': entry.doc_type === 'credit_note',
                    'bg-yellow-50': entry.vat_amount === 0 && entry.total > 0 && entry.doc_type !== 'credit_note',
                  }"
                >
                  <td class="px-3 py-3 text-sm text-gray-500">{{ i + 1 }}</td>
                  <td class="px-3 py-3 text-sm text-gray-900 whitespace-nowrap">{{ entry.date }}</td>
                  <td class="px-3 py-3 text-sm font-medium">
                    <span :class="entry.doc_type === 'credit_note' ? 'text-red-600' : 'text-primary-600'">
                      {{ entry.number }}
                    </span>
                    <span v-if="entry.doc_type === 'credit_note'" class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-red-100 text-red-700">КН</span>
                  </td>
                  <td class="px-3 py-3 text-sm text-gray-900 max-w-xs truncate" :title="entry.party_name">{{ entry.party_name }}</td>
                  <td class="px-3 py-3 text-sm text-gray-600 font-mono text-xs">{{ entry.party_tax_id || '-' }}</td>
                  <td class="px-3 py-3 text-sm text-right font-medium" :class="entry.total < 0 ? 'text-red-600' : 'text-gray-900'">{{ formatMoney(entry.total) }}</td>
                  <td class="px-3 py-3 text-sm text-right text-gray-900">
                    <EditableAmount
                      :value="getEffective('output', entry.id, 'taxable_base', entry.taxable_base)"
                      :original="entry.taxable_base"
                      :is-overridden="isFieldOverridden('output', entry.id, 'taxable_base')"
                      @update="(val) => setOverride('output', entry.id, 'taxable_base', val, entry)"
                    />
                  </td>
                  <td class="px-3 py-3 text-sm text-right text-gray-900">
                    <EditableAmount
                      :value="getEffective('output', entry.id, 'vat_amount', entry.vat_amount)"
                      :original="entry.vat_amount"
                      :is-overridden="isFieldOverridden('output', entry.id, 'vat_amount')"
                      @update="(val) => setOverride('output', entry.id, 'vat_amount', val, entry)"
                    />
                  </td>
                  <td class="px-3 py-3 text-sm text-center">
                    <EditableRate
                      :value="getEffective('output', entry.id, 'vat_rate', entry.vat_rate)"
                      :original="entry.vat_rate"
                      :is-overridden="isFieldOverridden('output', entry.id, 'vat_rate')"
                      @update="(val) => setOverride('output', entry.id, 'vat_rate', val, entry)"
                    />
                  </td>
                </tr>
              </tbody>
              <tfoot class="bg-gray-100 font-semibold">
                <tr>
                  <td colspan="5" class="px-3 py-3 text-sm">{{ $t('general.total') }} ({{ outputEntries.length }})</td>
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
          <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
            <div>
              <h3 class="text-lg font-medium text-gray-900">{{ $t('partner.accounting.vat_input_book', 'Книга на влезни фактури') }}</h3>
              <p class="text-sm text-gray-500">{{ filters.start_date }} &mdash; {{ filters.end_date }}</p>
            </div>
            <div v-if="hasOverrides" class="flex items-center space-x-2">
              <span class="text-xs text-amber-600 font-medium">{{ overrideCount }} {{ $t('partner.accounting.manual_corrections', 'рачни корекции') }}</span>
              <BaseButton variant="danger-outline" size="sm" @click="clearAllOverrides">
                {{ $t('partner.accounting.reset_all', 'Ресетирај') }}
              </BaseButton>
            </div>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                  <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('general.date') }}</th>
                  <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('partner.accounting.bill_number', 'Број') }}</th>
                  <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('partner.accounting.supplier_name', 'Добавувач') }}</th>
                  <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('partner.accounting.edb', 'ЕДБ') }}</th>
                  <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('partner.accounting.total_with_vat', 'Вкупно со ДДВ') }}</th>
                  <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('partner.accounting.taxable_base', 'Основица') }}</th>
                  <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('partner.accounting.vat_amount', 'ДДВ') }}</th>
                  <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ $t('partner.accounting.vat_rate', 'Стапка') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr
                  v-for="(entry, i) in inputEntries"
                  :key="entry.id"
                  class="hover:bg-gray-50"
                  :class="{
                    'bg-amber-50': isOverridden('input', entry.id),
                    'bg-yellow-50': entry.vat_amount === 0 && entry.total > 0,
                  }"
                >
                  <td class="px-3 py-3 text-sm text-gray-500">{{ i + 1 }}</td>
                  <td class="px-3 py-3 text-sm text-gray-900 whitespace-nowrap">{{ entry.date }}</td>
                  <td class="px-3 py-3 text-sm text-primary-600 font-medium">{{ entry.number }}</td>
                  <td class="px-3 py-3 text-sm text-gray-900 max-w-xs truncate" :title="entry.party_name">{{ entry.party_name }}</td>
                  <td class="px-3 py-3 text-sm text-gray-600 font-mono text-xs">{{ entry.party_tax_id || '-' }}</td>
                  <td class="px-3 py-3 text-sm text-right font-medium text-gray-900">{{ formatMoney(entry.total) }}</td>
                  <td class="px-3 py-3 text-sm text-right text-gray-900">
                    <EditableAmount
                      :value="getEffective('input', entry.id, 'taxable_base', entry.taxable_base)"
                      :original="entry.taxable_base"
                      :is-overridden="isFieldOverridden('input', entry.id, 'taxable_base')"
                      @update="(val) => setOverride('input', entry.id, 'taxable_base', val, entry)"
                    />
                  </td>
                  <td class="px-3 py-3 text-sm text-right text-gray-900">
                    <EditableAmount
                      :value="getEffective('input', entry.id, 'vat_amount', entry.vat_amount)"
                      :original="entry.vat_amount"
                      :is-overridden="isFieldOverridden('input', entry.id, 'vat_amount')"
                      @update="(val) => setOverride('input', entry.id, 'vat_amount', val, entry)"
                    />
                  </td>
                  <td class="px-3 py-3 text-sm text-center">
                    <EditableRate
                      :value="getEffective('input', entry.id, 'vat_rate', entry.vat_rate)"
                      :original="entry.vat_rate"
                      :is-overridden="isFieldOverridden('input', entry.id, 'vat_rate')"
                      @update="(val) => setOverride('input', entry.id, 'vat_rate', val, entry)"
                    />
                  </td>
                </tr>
              </tbody>
              <tfoot class="bg-gray-100 font-semibold">
                <tr>
                  <td colspan="5" class="px-3 py-3 text-sm">{{ $t('general.total') }} ({{ inputEntries.length }})</td>
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
          <div class="h-4 bg-gray-200 rounded w-20"></div>
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
    <PdfPreviewModal
      :show="showPdfPreview"
      :pdf-url="previewPdfUrl"
      :title="activeTab === 'output'
        ? $t('partner.accounting.vat_output_book', 'Книга на излезни фактури')
        : $t('partner.accounting.vat_input_book', 'Книга на влезни фактури')"
      @close="closePdfPreview"
      @download="downloadPdf"
    />
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import { useNotificationStore } from '@/scripts/stores/notification'
import PdfPreviewModal from './components/PdfPreviewModal.vue'

const { t } = useI18n()
const consoleStore = useConsoleStore()
const notificationStore = useNotificationStore()

const selectedCompanyId = ref(null)
const activeTab = ref('output')
const isLoading = ref(false)
const isExportingPdf = ref(false)
const isExportingUjp = ref(false)
const showPdfPreview = ref(false)
const previewPdfUrl = ref(null)
const pdfBlob = ref(null)
const hasSearched = ref(false)
const outputEntries = ref([])
const inputEntries = ref([])
const outputByRate = ref([])
const inputByRate = ref([])

// Overrides: { 'output_123_vat_amount': 5000, 'input_45_vat_rate': 5 }
const overrides = ref({})

const filters = ref({
  start_date: `${new Date().getFullYear()}-01-01`,
  end_date: new Date().toISOString().slice(0, 10),
})

const companies = computed(() => consoleStore.managedCompanies || [])

const dateError = computed(() => {
  if (!filters.value.start_date || !filters.value.end_date) {
    return t('partner.accounting.dates_required', 'Изберете почетен и краен датум')
  }
  if (filters.value.start_date > filters.value.end_date) {
    return t('partner.accounting.date_order_error', 'Почетниот датум мора да биде пред крајниот')
  }
  return null
})

const canLoad = computed(() => !dateError.value && selectedCompanyId.value)

const tabs = computed(() => [
  { key: 'output', label: t('partner.accounting.output_book', 'Излезни фактури'), count: outputEntries.value.length },
  { key: 'input', label: t('partner.accounting.input_book', 'Влезни фактури'), count: inputEntries.value.length },
])

const currentEntries = computed(() => activeTab.value === 'output' ? outputEntries.value : inputEntries.value)
const currentRateSummary = computed(() => {
  const entries = currentEntries.value
  if (!entries.length) return []
  // Recalculate using effective values (with overrides applied)
  const byRate = {}
  entries.forEach(entry => {
    const book = activeTab.value
    const rate = getEffective(book, entry.id, 'vat_rate', entry.vat_rate)
    const key = String(rate)
    if (!byRate[key]) {
      byRate[key] = { rate, count: 0, taxable_base: 0, vat_amount: 0, total: 0 }
    }
    byRate[key].count++
    byRate[key].taxable_base += getEffective(book, entry.id, 'taxable_base', entry.taxable_base)
    byRate[key].vat_amount += getEffective(book, entry.id, 'vat_amount', entry.vat_amount)
    byRate[key].total += entry.total
  })
  return Object.values(byRate).sort((a, b) => b.rate - a.rate)
})

function calcTotals(entries, book) {
  return entries.reduce((acc, e) => ({
    total: acc.total + (e.total || 0),
    taxable_base: acc.taxable_base + getEffective(book, e.id, 'taxable_base', e.taxable_base),
    vat_amount: acc.vat_amount + getEffective(book, e.id, 'vat_amount', e.vat_amount),
  }), { total: 0, taxable_base: 0, vat_amount: 0 })
}

const outputTotals = computed(() => calcTotals(outputEntries.value, 'output'))
const inputTotals = computed(() => calcTotals(inputEntries.value, 'input'))

// Override helpers
function overrideKey(book, id, field) {
  return `${book}_${id}_${field}`
}

function getEffective(book, id, field, original) {
  const key = overrideKey(book, id, field)
  return overrides.value[key] !== undefined ? overrides.value[key] : original
}

function isFieldOverridden(book, id, field) {
  return overrides.value[overrideKey(book, id, field)] !== undefined
}

function isOverridden(book, id) {
  return isFieldOverridden(book, id, 'taxable_base') ||
    isFieldOverridden(book, id, 'vat_amount') ||
    isFieldOverridden(book, id, 'vat_rate')
}

const hasOverrides = computed(() => Object.keys(overrides.value).length > 0)
const overrideCount = computed(() => {
  const ids = new Set()
  Object.keys(overrides.value).forEach(k => {
    const parts = k.split('_')
    ids.add(`${parts[0]}_${parts[1]}`)
  })
  return ids.size
})

function setOverride(book, id, field, value, entry) {
  const key = overrideKey(book, id, field)
  // If setting back to original, remove override
  if (value === entry[field]) {
    delete overrides.value[key]
  } else {
    overrides.value[key] = value
  }
  // Trigger reactivity
  overrides.value = { ...overrides.value }
}

function clearAllOverrides() {
  overrides.value = {}
}

onMounted(async () => {
  await consoleStore.fetchCompanies()
  if (companies.value.length === 1) {
    selectedCompanyId.value = companies.value[0].id
  }
})

function onCompanyChange() {
  outputEntries.value = []
  inputEntries.value = []
  outputByRate.value = []
  inputByRate.value = []
  hasSearched.value = false
  overrides.value = {}
}

async function loadVatBooks() {
  if (!canLoad.value) return
  isLoading.value = true
  hasSearched.value = true
  outputEntries.value = []
  inputEntries.value = []
  overrides.value = {}

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
    outputByRate.value = data.output_by_rate || []
    inputByRate.value = data.input_by_rate || []
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('partner.accounting.vat_books_load_error', 'Грешка при вчитување на ДДВ книгите'),
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
  const book = activeTab.value
  const bookType = book === 'output' ? 'излезни' : 'влезни'
  const partyLabel = book === 'output' ? 'Купувач' : 'Добавувач'
  const headers = ['#', 'Датум', 'Тип', 'Број', partyLabel, 'ЕДБ', 'Вкупно со ДДВ', 'Основица', 'ДДВ', 'Стапка %']
  const docTypeLabel = (type) => type === 'credit_note' ? 'Книжно одоб.' : type === 'bill' ? 'Фактура' : 'Фактура'
  const rows = entries.map((e, i) => [
    i + 1, e.date, docTypeLabel(e.doc_type), e.number, e.party_name, e.party_tax_id || '',
    (e.total / 100).toFixed(2),
    (getEffective(book, e.id, 'taxable_base', e.taxable_base) / 100).toFixed(2),
    (getEffective(book, e.id, 'vat_amount', e.vat_amount) / 100).toFixed(2),
    getEffective(book, e.id, 'vat_rate', e.vat_rate),
  ])
  const csvContent = [headers.join(','), ...rows.map(r => r.map(c => `"${String(c).replace(/"/g, '""')}"`).join(','))].join('\n')
  const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = `ddv_kniga_${bookType}_${filters.value.start_date}_${filters.value.end_date}.csv`
  link.click()
  URL.revokeObjectURL(link.href)
}

async function previewPdf() {
  if (!selectedCompanyId.value) return
  isExportingPdf.value = true
  try {
    const response = await window.axios.get(`/partner/companies/${selectedCompanyId.value}/accounting/vat-books/export`, {
      params: {
        type: activeTab.value,
        from_date: filters.value.start_date,
        to_date: filters.value.end_date,
      },
      responseType: 'blob',
    })
    // Check if response is actually a PDF
    if (response.data.type && !response.data.type.includes('pdf')) {
      const text = await response.data.text()
      try {
        const err = JSON.parse(text)
        throw new Error(err.message || 'Server returned non-PDF response')
      } catch (parseErr) {
        if (parseErr.message !== 'Server returned non-PDF response') {
          throw new Error('Unexpected server response')
        }
        throw parseErr
      }
    }
    pdfBlob.value = new Blob([response.data], { type: 'application/pdf' })
    previewPdfUrl.value = window.URL.createObjectURL(pdfBlob.value)
    showPdfPreview.value = true
  } catch (error) {
    let message = t('partner.accounting.pdf_export_error', 'Грешка при генерирање на PDF')
    if (error.response?.data instanceof Blob) {
      try {
        const text = await error.response.data.text()
        const parsed = JSON.parse(text)
        message = parsed.message || message
      } catch (_) { /* ignore parse errors */ }
    } else if (error.message) {
      message = error.message
    }
    notificationStore.showNotification({ type: 'error', message })
  } finally {
    isExportingPdf.value = false
  }
}

function downloadPdf() {
  if (!pdfBlob.value) return
  const bookType = activeTab.value === 'output' ? 'izlezni' : 'vlezni'
  const url = window.URL.createObjectURL(pdfBlob.value)
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', `kniga_ddv_${bookType}_${filters.value.start_date}_${filters.value.end_date}.pdf`)
  document.body.appendChild(link)
  link.click()
  link.remove()
  window.URL.revokeObjectURL(url)
}

function closePdfPreview() {
  showPdfPreview.value = false
  if (previewPdfUrl.value) {
    window.URL.revokeObjectURL(previewPdfUrl.value)
    previewPdfUrl.value = null
  }
  pdfBlob.value = null
}

async function exportUjpPdf() {
  if (!selectedCompanyId.value) return
  isExportingUjp.value = true
  try {
    const response = await window.axios.get(`/partner/companies/${selectedCompanyId.value}/accounting/invoice-register/export`, {
      params: {
        type: activeTab.value,
        from_date: filters.value.start_date,
        to_date: filters.value.end_date,
      },
      responseType: 'blob',
    })
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const bookType = activeTab.value === 'output' ? 'izlezni' : 'vlezni'
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `kniga_${bookType}_fakturi_${filters.value.start_date}_${filters.value.end_date}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: t('partner.accounting.pdf_export_error', 'Грешка при генерирање на PDF'),
    })
  } finally {
    isExportingUjp.value = false
  }
}
</script>

<!-- Inline sub-components for editable cells -->
<script>
import { defineComponent, ref as vRef, computed as vComputed, nextTick } from 'vue'

const EditableAmount = defineComponent({
  name: 'EditableAmount',
  props: {
    value: { type: Number, required: true },
    original: { type: Number, required: true },
    isOverridden: { type: Boolean, default: false },
  },
  emits: ['update'],
  setup(props, { emit }) {
    const editing = vRef(false)
    const inputRef = vRef(null)
    const editValue = vRef('')

    const displayValue = vComputed(() => {
      const amount = props.value
      if (amount === null || amount === undefined) return '-'
      const v = Math.abs(amount) / 100
      const sign = amount < 0 ? '-' : ''
      return sign + new Intl.NumberFormat('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(v)
    })

    function startEdit() {
      editValue.value = (props.value / 100).toFixed(2)
      editing.value = true
      nextTick(() => inputRef.value?.select())
    }

    function finishEdit() {
      editing.value = false
      const parsed = parseFloat(editValue.value.replace(/,/g, '.'))
      if (!isNaN(parsed)) {
        emit('update', Math.round(parsed * 100))
      }
    }

    function cancelEdit() {
      editing.value = false
    }

    return { editing, inputRef, editValue, displayValue, startEdit, finishEdit, cancelEdit }
  },
  template: `
    <div class="inline-flex items-center group">
      <input
        v-if="editing"
        ref="inputRef"
        v-model="editValue"
        type="text"
        class="w-24 text-right text-sm border border-primary-300 rounded px-1 py-0.5 focus:ring-1 focus:ring-primary-500 focus:outline-none"
        @blur="finishEdit"
        @keyup.enter="finishEdit"
        @keyup.escape="cancelEdit"
      />
      <span
        v-else
        class="cursor-pointer hover:text-primary-600 hover:underline decoration-dashed"
        :class="{ 'text-amber-700 font-semibold': isOverridden }"
        :title="isOverridden ? 'Рачно коригирано (кликни за промена)' : 'Кликни за корекција'"
        @click="startEdit"
      >
        {{ displayValue }}
      </span>
      <span v-if="isOverridden" class="ml-1 text-amber-500 text-xs" title="Рачна корекција">*</span>
    </div>
  `,
})

const EditableRate = defineComponent({
  name: 'EditableRate',
  props: {
    value: { type: Number, required: true },
    original: { type: Number, required: true },
    isOverridden: { type: Boolean, default: false },
  },
  emits: ['update'],
  setup(props, { emit }) {
    const editing = vRef(false)

    function rateClass(rate) {
      if (rate === 18) return 'bg-blue-100 text-blue-700'
      if (rate === 10) return 'bg-purple-100 text-purple-700'
      if (rate === 5) return 'bg-green-100 text-green-700'
      if (rate === 0) return 'bg-gray-100 text-gray-700'
      return 'bg-amber-100 text-amber-700'
    }

    function selectRate(rate) {
      editing.value = false
      emit('update', rate)
    }

    return { editing, rateClass, selectRate }
  },
  template: `
    <div class="relative inline-block">
      <div v-if="editing" class="flex space-x-1">
        <button
          v-for="rate in [18, 10, 5, 0]"
          :key="rate"
          class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium cursor-pointer border"
          :class="[rateClass(rate), value === rate ? 'ring-2 ring-primary-400' : 'opacity-70 hover:opacity-100']"
          @click="selectRate(rate)"
        >
          {{ rate }}%
        </button>
        <button class="text-xs text-gray-400 hover:text-gray-600 px-1" @click="editing = false">x</button>
      </div>
      <span
        v-else
        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium cursor-pointer hover:ring-2 hover:ring-primary-300"
        :class="[rateClass(value), isOverridden ? 'ring-2 ring-amber-400' : '']"
        :title="isOverridden ? 'Рачно коригирано (кликни за промена)' : 'Кликни за промена на стапка'"
        @click="editing = true"
      >
        {{ value }}%
        <span v-if="isOverridden" class="ml-0.5 text-amber-500">*</span>
      </span>
    </div>
  `,
})

export default {
  components: { EditableAmount, EditableRate },
}
</script>

<!-- CLAUDE-CHECKPOINT -->
