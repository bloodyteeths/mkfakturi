<template>
  <BasePage>
    <BasePageHeader :title="$t('partner.accounting.vat_returns', 'Tax Returns')">
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

    <!-- Select company message -->
    <div
      v-if="!selectedCompanyId"
      class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-12"
    >
      <BaseIcon name="BuildingOfficeIcon" class="h-12 w-12 text-gray-400" />
      <p class="mt-2 text-sm text-gray-500">
        {{ $t('partner.accounting.select_company_to_view') }}
      </p>
    </div>

    <template v-if="selectedCompanyId">
      <!-- Tab Navigation -->
      <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
            :class="activeTab === tab.key
              ? 'border-primary-500 text-primary-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
            @click="activeTab = tab.key"
          >
            {{ tab.label }}
          </button>
        </nav>
      </div>

      <!-- VAT Tab -->
      <div v-if="activeTab === 'vat'">
        <!-- VAT Status -->
        <div v-if="vatStatus" class="mb-6 rounded-lg bg-white shadow p-6">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-medium text-gray-900">
                {{ $t('banking.vat_compliance', 'VAT Compliance Status') }}
              </h3>
              <p class="mt-1 text-sm text-gray-500">
                {{ $t('banking.vat_number', 'VAT Number') }}: {{ vatStatus.vat_number || 'Not set' }}
              </p>
            </div>
            <span
              class="inline-flex items-center rounded-full px-3 py-0.5 text-sm font-medium"
              :class="vatStatusClass"
            >
              {{ vatStatus.current_status }}
            </span>
          </div>
        </div>

        <!-- Period Selector -->
        <div class="mb-6 rounded-lg bg-white shadow p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">
            {{ $t('banking.generate_return', 'Generate VAT Return') }}
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <BaseInputGroup :label="$t('banking.period_type', 'Period Type')">
              <BaseMultiselect
                v-model="vatForm.period_type"
                :options="periodTypes"
                label="label"
                value-prop="value"
              />
            </BaseInputGroup>
            <BaseInputGroup :label="$t('general.from')">
              <BaseDatePicker
                v-model="vatForm.period_start"
                :calendar-button="true"
                calendar-button-icon="calendar"
              />
            </BaseInputGroup>
            <BaseInputGroup :label="$t('general.to')">
              <BaseDatePicker
                v-model="vatForm.period_end"
                :calendar-button="true"
                calendar-button-icon="calendar"
              />
            </BaseInputGroup>
            <div class="flex items-end space-x-2">
              <BaseButton
                variant="primary-outline"
                :loading="isPreviewingVat"
                @click="previewVat"
              >
                {{ $t('general.preview', 'Preview') }}
              </BaseButton>
              <BaseButton
                variant="primary"
                :loading="isGeneratingVat"
                @click="generateVatXml"
              >
                {{ $t('general.generate', 'Generate XML') }}
              </BaseButton>
            </div>
          </div>
        </div>

        <!-- VAT Preview Data -->
        <div v-if="vatPreviewData" class="mb-6 rounded-lg bg-white shadow overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-medium text-gray-900">
              {{ $t('banking.vat_preview', 'VAT Calculation Preview') }}
            </h3>
          </div>
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  {{ $t('banking.rate', 'Rate') }}
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                  {{ $t('banking.taxable_base', 'Taxable Base') }}
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                  {{ $t('banking.vat_amount', 'VAT Amount') }}
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                  {{ $t('banking.transactions', 'Transactions') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr>
                <td class="px-6 py-4 text-sm text-gray-900">18% ({{ $t('banking.standard', 'Standard') }})</td>
                <td class="px-6 py-4 text-sm text-right text-gray-900">{{ formatMoney(vatPreviewData.standard.taxable_base) }}</td>
                <td class="px-6 py-4 text-sm text-right text-gray-900">{{ formatMoney(vatPreviewData.standard.vat_amount) }}</td>
                <td class="px-6 py-4 text-sm text-right text-gray-500">{{ vatPreviewData.standard.transaction_count }}</td>
              </tr>
              <tr>
                <td class="px-6 py-4 text-sm text-gray-900">5% ({{ $t('banking.reduced', 'Reduced') }})</td>
                <td class="px-6 py-4 text-sm text-right text-gray-900">{{ formatMoney(vatPreviewData.reduced.taxable_base) }}</td>
                <td class="px-6 py-4 text-sm text-right text-gray-900">{{ formatMoney(vatPreviewData.reduced.vat_amount) }}</td>
                <td class="px-6 py-4 text-sm text-right text-gray-500">{{ vatPreviewData.reduced.transaction_count }}</td>
              </tr>
              <tr>
                <td class="px-6 py-4 text-sm text-gray-900">0% ({{ $t('banking.zero_rate', 'Zero Rate') }})</td>
                <td class="px-6 py-4 text-sm text-right text-gray-900">{{ formatMoney(vatPreviewData.zero.taxable_base) }}</td>
                <td class="px-6 py-4 text-sm text-right text-gray-900">{{ formatMoney(vatPreviewData.zero.vat_amount) }}</td>
                <td class="px-6 py-4 text-sm text-right text-gray-500">{{ vatPreviewData.zero.transaction_count }}</td>
              </tr>
            </tbody>
            <tfoot class="bg-gray-50">
              <tr>
                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $t('general.total', 'Total') }}</td>
                <td class="px-6 py-4 text-sm text-right font-medium text-gray-900">-</td>
                <td class="px-6 py-4 text-sm text-right font-medium text-gray-900">{{ formatMoney(vatPreviewData.total_output_vat) }}</td>
                <td class="px-6 py-4 text-sm text-right font-medium text-gray-500">{{ vatPreviewData.total_transactions }}</td>
              </tr>
            </tfoot>
          </table>
          <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
            <BaseButton
              variant="primary"
              :loading="isFilingVat"
              @click="fileVatReturn"
            >
              {{ $t('banking.file_return', 'File Return') }}
            </BaseButton>
          </div>
        </div>

        <!-- Filed Periods -->
        <div class="rounded-lg bg-white shadow overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-medium text-gray-900">
              {{ $t('banking.filed_periods', 'Filed Periods') }}
            </h3>
          </div>
          <div v-if="isLoadingPeriods" class="p-6 text-center">
            <BaseContentPlaceholders>
              <BaseContentPlaceholdersBox :rounded="true" />
            </BaseContentPlaceholders>
          </div>
          <div v-else-if="vatPeriods.length === 0" class="p-12 text-center">
            <BaseIcon name="DocumentTextIcon" class="mx-auto h-12 w-12 text-gray-400" />
            <p class="mt-2 text-sm text-gray-500">
              {{ $t('banking.no_periods', 'No tax return periods found.') }}
            </p>
          </div>
          <table v-else class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('general.period', 'Period') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('general.status', 'Status') }}</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('banking.returns_filed', 'Returns') }}</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="period in vatPeriods" :key="period.id">
                <td class="px-6 py-4 text-sm text-gray-900">{{ period.period_name }}</td>
                <td class="px-6 py-4">
                  <span
                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                    :class="periodStatusClass(period.status)"
                  >
                    {{ period.status }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm text-right text-gray-500">{{ period.filed_returns_count }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- CIT Tab -->
      <div v-if="activeTab === 'cit'">
        <div class="mb-6 rounded-lg bg-white shadow p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">
            {{ $t('tax.cit.title', 'Corporate Income Tax (DB-VP)') }}
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <BaseInputGroup :label="$t('tax.cit.fiscal_year', 'Fiscal Year')">
              <BaseMultiselect
                v-model="citForm.year"
                :options="yearOptions"
                label="label"
                value-prop="value"
              />
            </BaseInputGroup>
            <div class="flex items-end space-x-2">
              <BaseButton
                variant="primary-outline"
                :loading="isPreviewingCit"
                @click="previewCit"
              >
                {{ $t('general.preview', 'Preview') }}
              </BaseButton>
              <BaseButton
                variant="primary"
                :loading="isGeneratingCit"
                @click="generateCitXml"
              >
                {{ $t('general.generate', 'Generate XML') }}
              </BaseButton>
            </div>
          </div>

          <!-- Non-deductible adjustments -->
          <div class="mt-6">
            <h4 class="text-sm font-medium text-gray-700 mb-2">
              {{ $t('tax.cit.adjustments', 'Non-Deductible Expense Adjustments (Неданочно признаени расходи)') }}
            </h4>
            <p class="text-xs text-gray-500 mb-3">
              {{ $t('tax.cit.adjustments_help', 'Select expense categories that are not tax-deductible under Macedonian tax law.') }}
            </p>
            <div
              v-for="(adj, index) in citForm.adjustments"
              :key="index"
              class="flex items-center gap-3 mb-2"
            >
              <BaseMultiselect
                v-model="adj.category"
                :options="adjustmentCategories"
                label="label"
                value-prop="value"
                :searchable="true"
                :placeholder="$t('tax.cit.select_category', 'Select category')"
                class="flex-1"
                @update:model-value="(val) => onCitCategoryChange(adj, val)"
              />
              <BaseInput
                v-model="adj.amount"
                type="number"
                step="0.01"
                min="0"
                :placeholder="$t('general.amount')"
                class="w-40"
              />
              <button
                type="button"
                class="text-red-500 hover:text-red-700"
                @click="citForm.adjustments.splice(index, 1)"
              >
                <BaseIcon name="XMarkIcon" class="h-5 w-5" />
              </button>
            </div>
            <BaseButton
              variant="primary-outline"
              size="sm"
              @click="citForm.adjustments.push({ category: '', description: '', amount: 0 })"
            >
              <template #left="slotProps">
                <BaseIcon name="PlusIcon" :class="slotProps.class" />
              </template>
              {{ $t('tax.cit.add_adjustment', 'Add Adjustment') }}
            </BaseButton>
          </div>

          <!-- Loss carryforward -->
          <div class="mt-4">
            <BaseInputGroup :label="$t('tax.cit.loss_carryforward', 'Loss Carryforward from Previous Years')">
              <BaseInput
                v-model="citForm.loss_carryforward"
                type="number"
                step="0.01"
                min="0"
                placeholder="0.00"
              />
            </BaseInputGroup>
          </div>
        </div>

        <!-- CIT Preview Data -->
        <div v-if="citPreviewData" class="mb-6 rounded-lg bg-white shadow overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-medium text-gray-900">
              {{ $t('tax.cit.calculation_preview', 'CIT Calculation Preview') }}
            </h3>
            <p class="text-sm text-gray-500">
              {{ $t('tax.cit.deadline', 'Filing deadline') }}: {{ citPreviewData.deadline }}
            </p>
          </div>
          <div class="p-6">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
              <div>
                <dt class="text-sm font-medium text-gray-500">{{ $t('tax.cit.accounting_profit', 'Accounting Profit') }}</dt>
                <dd class="mt-1 text-lg font-semibold text-gray-900">{{ formatMoney(citPreviewData.accounting_profit) }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">{{ $t('tax.cit.total_adjustments', 'Non-Deductible Adjustments') }}</dt>
                <dd class="mt-1 text-lg font-semibold text-red-600">+ {{ formatMoney(citPreviewData.total_adjustments) }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">{{ $t('tax.cit.loss_carryforward', 'Loss Carryforward') }}</dt>
                <dd class="mt-1 text-lg font-semibold text-green-600">- {{ formatMoney(citPreviewData.loss_carryforward) }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">{{ $t('tax.cit.taxable_base', 'Taxable Base') }}</dt>
                <dd class="mt-1 text-lg font-bold text-gray-900">{{ formatMoney(citPreviewData.taxable_base) }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">{{ $t('tax.cit.cit_rate', 'CIT Rate') }}</dt>
                <dd class="mt-1 text-lg font-semibold text-gray-900">10%</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">{{ $t('tax.cit.cit_amount', 'CIT Amount') }}</dt>
                <dd class="mt-1 text-lg font-bold text-primary-600">{{ formatMoney(citPreviewData.cit_amount) }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">{{ $t('tax.cit.advance_payments', 'Advance Payments') }}</dt>
                <dd class="mt-1 text-lg font-semibold text-gray-900">{{ formatMoney(citPreviewData.advance_payments) }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">{{ $t('tax.cit.balance_due', 'Balance Due') }}</dt>
                <dd
                  class="mt-1 text-lg font-bold"
                  :class="citPreviewData.balance_due > 0 ? 'text-red-600' : 'text-green-600'"
                >
                  {{ formatMoney(Math.abs(citPreviewData.balance_due)) }}
                  {{ citPreviewData.balance_due < 0 ? $t('tax.cit.refund', '(Refund)') : '' }}
                </dd>
              </div>
            </dl>
          </div>
          <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
            <BaseButton
              variant="primary"
              :loading="isFilingCit"
              :disabled="citFiled"
              @click="fileCitReturn"
            >
              {{ citFiled ? $t('tax.cit.already_filed', 'Filed') : $t('banking.file_return', 'File Return') }}
            </BaseButton>
          </div>
        </div>
      </div>
    </template>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import { useNotificationStore } from '@/scripts/stores/notification'
// Use window.axios (configured with baseURL + company header interceptor)

const { t } = useI18n()
const consoleStore = useConsoleStore()
const notificationStore = useNotificationStore()

// State
const selectedCompanyId = ref(null)
const activeTab = ref('vat')

// VAT state
const vatStatus = ref(null)
const vatPreviewData = ref(null)
const vatPeriods = ref([])
const isPreviewingVat = ref(false)
const isGeneratingVat = ref(false)
const isFilingVat = ref(false)
const isLoadingPeriods = ref(false)

// CIT state
const citPreviewData = ref(null)
const isPreviewingCit = ref(false)
const isGeneratingCit = ref(false)
const isFilingCit = ref(false)
const citFiled = ref(false)

// Forms
function getLocalDateString(date = new Date()) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

function getMonthStart() {
  const now = new Date()
  return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-01`
}

function getMonthEnd() {
  const now = new Date()
  const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate()
  return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${lastDay}`
}

const vatForm = ref({
  period_type: 'MONTHLY',
  period_start: getMonthStart(),
  period_end: getMonthEnd(),
})

const citForm = ref({
  year: new Date().getFullYear() - 1,
  adjustments: [],
  loss_carryforward: 0,
})

// Computed
const companies = computed(() => consoleStore.managedCompanies || [])

const tabs = computed(() => [
  { key: 'vat', label: t('partner.accounting.vat_returns_tab', 'VAT Returns (DDV-04)') },
  { key: 'cit', label: t('tax.cit.tab', 'CIT Annual Return (DB)') },
])

const periodTypes = [
  { label: 'Monthly', value: 'MONTHLY' },
  { label: 'Quarterly', value: 'QUARTERLY' },
]

const yearOptions = computed(() => {
  const currentYear = new Date().getFullYear()
  const options = []
  for (let y = currentYear; y >= 2020; y--) {
    options.push({ label: String(y), value: y })
  }
  return options
})

const adjustmentCategories = [
  { label: 'Казни и пенали (Fines & penalties)', value: 'fines', description: 'Казни и пенали' },
  { label: 'Репрезентација > 1% (Representation excess)', value: 'representation', description: 'Репрезентација над дозволен лимит' },
  { label: 'Недокументирани расходи (Undocumented expenses)', value: 'undocumented', description: 'Недокументирани расходи' },
  { label: 'Расходи за лично возило (Personal vehicle)', value: 'personal_vehicle', description: 'Расходи за лично возило' },
  { label: 'Донации > лимит (Donations over limit)', value: 'donations_excess', description: 'Донации над дозволен лимит' },
  { label: 'Спонзорства > лимит (Sponsorships over limit)', value: 'sponsorships_excess', description: 'Спонзорства над дозволен лимит' },
  { label: 'Камати на поврзани лица (Related party interest)', value: 'related_party_interest', description: 'Камати на поврзани лица' },
  { label: 'Отпис на побарувања (Bad debt write-off)', value: 'bad_debt_writeoff', description: 'Отпис на ненаплатени побарувања' },
  { label: 'Амортизација > даночна (Depreciation excess)', value: 'depreciation_excess', description: 'Амортизација над даночно призната' },
  { label: 'Останати корекции (Other adjustments)', value: 'other', description: 'Останати неданочно признаени расходи' },
]

function onCitCategoryChange(adj, val) {
  const cat = adjustmentCategories.find(c => c.value === val)
  if (cat) {
    adj.description = cat.description
  }
}

const vatStatusClass = computed(() => {
  if (!vatStatus.value) return 'bg-gray-100 text-gray-800'
  switch (vatStatus.value.current_status) {
    case 'compliant': return 'bg-green-100 text-green-800'
    case 'overdue': return 'bg-red-100 text-red-800'
    default: return 'bg-gray-100 text-gray-800'
  }
})

// Methods
function onCompanyChange() {
  vatPreviewData.value = null
  citPreviewData.value = null
  vatPeriods.value = []
  vatStatus.value = null

  if (selectedCompanyId.value) {
    loadVatStatus()
    loadVatPeriods()
  }
}

async function loadVatStatus() {
  try {
    const response = await window.axios.get(`/partner/companies/${selectedCompanyId.value}/tax/vat-status`)
    vatStatus.value = response.data
  } catch (error) {
    console.error('Failed to load VAT status:', error)
  }
}

async function loadVatPeriods() {
  isLoadingPeriods.value = true
  try {
    const response = await window.axios.get(`/partner/companies/${selectedCompanyId.value}/tax/vat-return/periods`)
    vatPeriods.value = response.data.data?.data || response.data.data || []
  } catch (error) {
    console.error('Failed to load VAT periods:', error)
  } finally {
    isLoadingPeriods.value = false
  }
}

async function previewVat() {
  isPreviewingVat.value = true
  try {
    const response = await window.axios.post(
      `/partner/companies/${selectedCompanyId.value}/tax/vat-return/preview`,
      vatForm.value
    )
    vatPreviewData.value = response.data.data
  } catch (error) {
    const msg = error.response?.data?.message || error.response?.data?.error || 'Failed to preview VAT'
    notificationStore.showNotification({ type: 'error', message: msg })
  } finally {
    isPreviewingVat.value = false
  }
}

async function generateVatXml() {
  isGeneratingVat.value = true
  try {
    const response = await window.axios.post(
      `/partner/companies/${selectedCompanyId.value}/tax/vat-return`,
      vatForm.value,
      { responseType: 'blob' }
    )
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `DDV04_${selectedCompanyId.value}.xml`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: 'Failed to generate VAT XML' })
  } finally {
    isGeneratingVat.value = false
  }
}

async function fileVatReturn() {
  if (!vatPreviewData.value) return
  isFilingVat.value = true
  try {
    // First generate the XML content
    const xmlResponse = await window.axios.post(
      `/partner/companies/${selectedCompanyId.value}/tax/vat-return`,
      vatForm.value,
      { responseType: 'text' }
    )

    await window.axios.post(
      `/partner/companies/${selectedCompanyId.value}/tax/vat-return/file`,
      {
        ...vatForm.value,
        xml_content: xmlResponse.data,
      }
    )
    notificationStore.showNotification({ type: 'success', message: t('banking.return_filed', 'Tax return filed successfully') })
    vatPreviewData.value = null
    loadVatPeriods()
  } catch (error) {
    const msg = error.response?.data?.message || 'Failed to file VAT return'
    notificationStore.showNotification({ type: 'error', message: msg })
  } finally {
    isFilingVat.value = false
  }
}

async function previewCit() {
  isPreviewingCit.value = true
  citFiled.value = false
  try {
    const response = await window.axios.post(
      `/partner/companies/${selectedCompanyId.value}/tax/cit-return/preview`,
      {
        year: citForm.value.year,
        adjustments: citForm.value.adjustments.filter(a => a.category && a.amount > 0),
        loss_carryforward: citForm.value.loss_carryforward || 0,
      }
    )
    citPreviewData.value = response.data.data
  } catch (error) {
    const msg = error.response?.data?.message || 'Failed to preview CIT'
    notificationStore.showNotification({ type: 'error', message: msg })
  } finally {
    isPreviewingCit.value = false
  }
}

async function generateCitXml() {
  isGeneratingCit.value = true
  try {
    const response = await window.axios.post(
      `/partner/companies/${selectedCompanyId.value}/tax/cit-return`,
      {
        year: citForm.value.year,
        adjustments: citForm.value.adjustments.filter(a => a.category && a.amount > 0),
        loss_carryforward: citForm.value.loss_carryforward || 0,
      },
      { responseType: 'blob' }
    )
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `DB_VP_${citForm.value.year}.xml`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: 'Failed to generate CIT XML' })
  } finally {
    isGeneratingCit.value = false
  }
}

async function fileCitReturn() {
  if (!citPreviewData.value) return
  isFilingCit.value = true
  try {
    const xmlResponse = await window.axios.post(
      `/partner/companies/${selectedCompanyId.value}/tax/cit-return`,
      {
        year: citForm.value.year,
        adjustments: citForm.value.adjustments.filter(a => a.category && a.amount > 0),
        loss_carryforward: citForm.value.loss_carryforward || 0,
      },
      { responseType: 'text' }
    )

    await window.axios.post(
      `/partner/companies/${selectedCompanyId.value}/tax/cit-return/file`,
      {
        year: citForm.value.year,
        return_data: citPreviewData.value,
        xml_content: xmlResponse.data,
      }
    )
    notificationStore.showNotification({ type: 'success', message: t('tax.cit.filed', 'CIT return filed successfully') })
    citFiled.value = true
  } catch (error) {
    const msg = error.response?.data?.message || 'Failed to file CIT return'
    notificationStore.showNotification({ type: 'error', message: msg })
  } finally {
    isFilingCit.value = false
  }
}

function periodStatusClass(status) {
  switch ((status || '').toLowerCase()) {
    case 'filed': return 'bg-green-100 text-green-800'
    case 'closed': return 'bg-blue-100 text-blue-800'
    case 'open': return 'bg-yellow-100 text-yellow-800'
    default: return 'bg-gray-100 text-gray-800'
  }
}

function formatMoney(amount) {
  if (!amount && amount !== 0) return '0.00'
  return new Intl.NumberFormat('mk-MK', {
    style: 'currency',
    currency: 'MKD',
    minimumFractionDigits: 2,
  }).format(amount)
}

// Lifecycle
onMounted(() => {
  if (companies.value.length === 1) {
    selectedCompanyId.value = companies.value[0].id
    onCompanyChange()
  }
})
</script>

<!-- CLAUDE-CHECKPOINT -->
