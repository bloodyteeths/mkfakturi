<template>
  <BasePage>
    <BasePageHeader :title="$t('tax.cit.title', 'Corporate Income Tax (DB-VP)')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('tax.cit.title', 'Corporate Income Tax')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <!-- Year Selector and Actions -->
    <div class="mb-6 rounded-lg bg-white shadow p-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <BaseInputGroup :label="$t('tax.cit.fiscal_year', 'Fiscal Year')">
          <BaseMultiselect
            v-model="selectedYear"
            :options="yearOptions"
            label="label"
            value-prop="value"
          />
        </BaseInputGroup>
        <div class="flex items-end space-x-2">
          <BaseButton
            variant="primary-outline"
            :loading="isPreviewing"
            @click="previewCit"
          >
            <template #left="slotProps">
              <BaseIcon name="EyeIcon" :class="slotProps.class" />
            </template>
            {{ $t('general.preview', 'Preview') }}
          </BaseButton>
          <BaseButton
            variant="primary-outline"
            :loading="isGenerating"
            @click="generateXml"
          >
            <template #left="slotProps">
              <BaseIcon name="ArrowDownTrayIcon" :class="slotProps.class" />
            </template>
            {{ $t('general.generate', 'Generate XML') }}
          </BaseButton>
          <BaseButton
            variant="primary"
            :loading="isFiling"
            :disabled="!previewData || citFiled"
            @click="fileReturn"
          >
            <template #left="slotProps">
              <BaseIcon :name="citFiled ? 'CheckCircleIcon' : 'PaperAirplaneIcon'" :class="slotProps.class" />
            </template>
            {{ citFiled ? $t('tax.cit.already_filed', 'Filed') : $t('banking.file_return', 'File Return') }}
          </BaseButton>
        </div>
      </div>

      <!-- Non-deductible adjustments -->
      <div class="mt-6 border-t border-gray-200 pt-6">
        <h4 class="text-sm font-medium text-gray-700 mb-3">
          {{ $t('tax.cit.adjustments', 'Non-Deductible Expense Adjustments (Неданочно признаени расходи)') }}
        </h4>
        <p class="text-xs text-gray-500 mb-3">
          {{ $t('tax.cit.adjustments_help', 'Select expense categories that are not tax-deductible under Macedonian tax law. These are added back to the accounting profit.') }}
        </p>
        <div
          v-for="(adj, index) in adjustments"
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
            @update:model-value="(val) => onCategoryChange(adj, val)"
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
            @click="adjustments.splice(index, 1)"
          >
            <BaseIcon name="XMarkIcon" class="h-5 w-5" />
          </button>
        </div>
        <BaseButton
          variant="primary-outline"
          size="sm"
          @click="addAdjustment"
        >
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ $t('tax.cit.add_adjustment', 'Add Adjustment') }}
        </BaseButton>
      </div>

      <!-- Loss carryforward -->
      <div class="mt-4">
        <BaseInputGroup :label="$t('tax.cit.loss_carryforward', 'Loss Carryforward from Previous Years (Пренесена загуба)')">
          <BaseInput
            v-model="lossCarryforward"
            type="number"
            step="0.01"
            min="0"
            placeholder="0.00"
          />
        </BaseInputGroup>
      </div>
    </div>

    <!-- Deadline Warning -->
    <div
      v-if="previewData && previewData.deadline"
      class="mb-6 rounded-lg border border-yellow-200 bg-yellow-50 p-4"
    >
      <div class="flex items-center">
        <BaseIcon name="ExclamationTriangleIcon" class="h-5 w-5 text-yellow-600 mr-2" />
        <p class="text-sm text-yellow-700">
          {{ $t('tax.cit.deadline_notice', 'Filing deadline') }}: <strong>{{ previewData.deadline }}</strong>
        </p>
      </div>
    </div>

    <!-- CIT Preview / Calculation -->
    <div v-if="previewData" class="mb-6 rounded-lg bg-white shadow overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <h3 class="text-lg font-medium text-gray-900">
          {{ $t('tax.cit.calculation', 'CIT Calculation') }} - {{ selectedYear }}
        </h3>
      </div>
      <div class="p-6">
        <!-- Main calculation -->
        <div class="space-y-4">
          <div class="flex justify-between items-center py-3 border-b border-gray-100">
            <span class="text-sm text-gray-600">{{ $t('tax.cit.accounting_profit', 'Accounting Profit (Revenue - Expenses)') }}</span>
            <span class="text-lg font-semibold" :class="previewData.accounting_profit >= 0 ? 'text-green-600' : 'text-red-600'">
              {{ formatMoney(previewData.accounting_profit) }}
            </span>
          </div>

          <div v-if="previewData.adjustments && previewData.adjustments.length > 0">
            <div
              v-for="(adj, i) in previewData.adjustments"
              :key="i"
              class="flex justify-between items-center py-2 pl-6"
            >
              <span class="text-sm text-gray-500">+ {{ adj.description }}</span>
              <span class="text-sm text-red-600">+ {{ formatMoney(adj.amount) }}</span>
            </div>
          </div>

          <div class="flex justify-between items-center py-3 border-b border-gray-100">
            <span class="text-sm text-gray-600">{{ $t('tax.cit.total_adjustments', 'Total Non-Deductible Adjustments') }}</span>
            <span class="text-lg font-semibold text-red-600">+ {{ formatMoney(previewData.total_adjustments) }}</span>
          </div>

          <div class="flex justify-between items-center py-3 border-b border-gray-100">
            <span class="text-sm text-gray-600">{{ $t('tax.cit.loss_carryforward', 'Loss Carryforward') }}</span>
            <span class="text-lg font-semibold text-green-600">- {{ formatMoney(previewData.loss_carryforward) }}</span>
          </div>

          <div class="flex justify-between items-center py-3 border-b-2 border-gray-300 bg-gray-50 px-4 rounded">
            <span class="text-base font-bold text-gray-900">{{ $t('tax.cit.taxable_base', 'Taxable Base (Даночна основа)') }}</span>
            <span class="text-xl font-bold text-gray-900">{{ formatMoney(previewData.taxable_base) }}</span>
          </div>

          <div class="flex justify-between items-center py-3 border-b border-gray-100">
            <span class="text-sm text-gray-600">{{ $t('tax.cit.cit_rate', 'CIT Rate') }}</span>
            <span class="text-lg font-semibold text-gray-900">10%</span>
          </div>

          <div class="flex justify-between items-center py-3 border-b-2 border-primary-300 bg-primary-50 px-4 rounded">
            <span class="text-base font-bold text-primary-900">{{ $t('tax.cit.cit_amount', 'CIT Amount Due (Данок на добивка)') }}</span>
            <span class="text-xl font-bold text-primary-600">{{ formatMoney(previewData.cit_amount) }}</span>
          </div>

          <div class="flex justify-between items-center py-3 border-b border-gray-100">
            <span class="text-sm text-gray-600">{{ $t('tax.cit.advance_payments', 'Monthly Advance Payments Paid') }}</span>
            <span class="text-lg font-semibold text-gray-900">- {{ formatMoney(previewData.advance_payments) }}</span>
          </div>

          <div
            class="flex justify-between items-center py-3 px-4 rounded"
            :class="previewData.balance_due > 0 ? 'bg-red-50 border-2 border-red-300' : 'bg-green-50 border-2 border-green-300'"
          >
            <span class="text-base font-bold" :class="previewData.balance_due > 0 ? 'text-red-900' : 'text-green-900'">
              {{ previewData.balance_due > 0 ? $t('tax.cit.balance_due', 'Balance Due') : $t('tax.cit.balance_refund', 'Refund Due') }}
            </span>
            <span class="text-xl font-bold" :class="previewData.balance_due > 0 ? 'text-red-600' : 'text-green-600'">
              {{ formatMoney(Math.abs(previewData.balance_due)) }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Filed Returns -->
    <div class="rounded-lg bg-white shadow overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
        <h3 class="text-lg font-medium text-gray-900">
          {{ $t('tax.cit.filed_returns', 'Filed CIT Returns') }}
        </h3>
        <BaseButton
          variant="primary-outline"
          size="sm"
          :loading="isLoadingPeriods"
          @click="loadPeriods"
        >
          <template #left="slotProps">
            <BaseIcon name="ArrowPathIcon" :class="slotProps.class" />
          </template>
          {{ $t('general.refresh', 'Refresh') }}
        </BaseButton>
      </div>
      <div v-if="isLoadingPeriods" class="p-6 text-center">
        <BaseContentPlaceholders>
          <BaseContentPlaceholdersBox :rounded="true" />
        </BaseContentPlaceholders>
      </div>
      <div v-else-if="periods.length === 0" class="p-12 text-center">
        <BaseIcon name="DocumentTextIcon" class="mx-auto h-12 w-12 text-gray-400" />
        <p class="mt-2 text-sm text-gray-500">
          {{ $t('tax.cit.no_returns', 'No CIT returns have been filed yet.') }}
        </p>
      </div>
      <table v-else class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('general.year', 'Year') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('general.status', 'Status') }}</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('banking.returns_filed', 'Returns') }}</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="period in periods" :key="period.id">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">FY {{ period.year }}</td>
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
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useNotificationStore } from '@/scripts/stores/notification'

const { t } = useI18n()
const companyStore = useCompanyStore()
const notificationStore = useNotificationStore()

// State
const selectedYear = ref(new Date().getFullYear() - 1)
const adjustments = ref([])
const lossCarryforward = ref(0)
const previewData = ref(null)
const periods = ref([])

const isPreviewing = ref(false)
const isGenerating = ref(false)
const isFiling = ref(false)
const citFiled = ref(false)
const isLoadingPeriods = ref(false)

// Predefined non-deductible expense categories per Macedonian tax law
const adjustmentCategories = [
  { label: t('tax.cit.adj_fines', 'Казни и пенали (Fines & penalties)'), value: 'fines', description: 'Казни и пенали' },
  { label: t('tax.cit.adj_representation', 'Репрезентација > 1% (Representation excess)'), value: 'representation', description: 'Репрезентација над дозволен лимит' },
  { label: t('tax.cit.adj_undocumented', 'Недокументирани расходи (Undocumented expenses)'), value: 'undocumented', description: 'Недокументирани расходи' },
  { label: t('tax.cit.adj_personal', 'Приватни трошоци (Owner personal expenses)'), value: 'personal', description: 'Приватни трошоци на сопственикот' },
  { label: t('tax.cit.adj_interest', 'Камати > пазарна стапка (Interest above market rate)'), value: 'interest', description: 'Камати над пазарна стапка' },
  { label: t('tax.cit.adj_donations', 'Донации > 5% (Donations excess)'), value: 'donations', description: 'Донации над дозволен лимит' },
  { label: t('tax.cit.adj_depreciation', 'Амортизација > стапка (Depreciation above rate)'), value: 'depreciation_excess', description: 'Амортизација над дозволена стапка' },
  { label: t('tax.cit.adj_provisions', 'Резервирања (Provisions)'), value: 'provisions', description: 'Даночно непризнаени резервирања' },
  { label: t('tax.cit.adj_related_party', 'Трансферни цени (Transfer pricing adj.)'), value: 'transfer_pricing', description: 'Корекции по трансферни цени' },
  { label: t('tax.cit.adj_other', 'Останати неданочни трошоци (Other)'), value: 'other', description: 'Останати неданочно признаени расходи' },
]

// Computed
const yearOptions = computed(() => {
  const currentYear = new Date().getFullYear()
  const options = []
  for (let y = currentYear; y >= 2020; y--) {
    options.push({ label: String(y), value: y })
  }
  return options
})

const companyId = computed(() => {
  return companyStore.selectedCompany?.id
})

// Methods
function getRequestPayload() {
  return {
    company_id: companyId.value,
    year: selectedYear.value,
    adjustments: adjustments.value
      .filter(a => a.category && a.amount > 0)
      .map(a => ({
        category: a.category,
        description: a.description,
        amount: parseFloat(a.amount) || 0,
      })),
    loss_carryforward: parseFloat(lossCarryforward.value) || 0,
  }
}

function addAdjustment() {
  adjustments.value.push({ category: '', description: '', amount: 0 })
}

function onCategoryChange(adj, val) {
  const found = adjustmentCategories.find(c => c.value === val)
  if (found) {
    adj.description = found.description
  }
}

async function previewCit() {
  if (!companyId.value) {
    notificationStore.showNotification({ type: 'error', message: 'No company selected' })
    return
  }
  isPreviewing.value = true
  citFiled.value = false
  try {
    const response = await window.axios.post('/tax/cit-return/preview', getRequestPayload())
    previewData.value = response.data.data
  } catch (error) {
    const msg = error.response?.data?.message || error.response?.data?.error || 'Failed to preview CIT'
    notificationStore.showNotification({ type: 'error', message: msg })
  } finally {
    isPreviewing.value = false
  }
}

async function generateXml() {
  if (!companyId.value) {
    notificationStore.showNotification({ type: 'error', message: 'No company selected' })
    return
  }
  isGenerating.value = true
  try {
    const response = await window.axios.post(
      '/tax/cit-return',
      getRequestPayload(),
      { responseType: 'blob' }
    )
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `DB_VP_${selectedYear.value}.xml`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    // When responseType is blob, we need to parse the error differently
    let msg = 'Failed to generate CIT XML'
    if (error.response?.data instanceof Blob) {
      try {
        const text = await error.response.data.text()
        const json = JSON.parse(text)
        msg = json.message || json.error || msg
      } catch { /* ignore parse error */ }
    } else {
      msg = error.response?.data?.message || error.response?.data?.error || msg
    }
    notificationStore.showNotification({ type: 'error', message: msg })
  } finally {
    isGenerating.value = false
  }
}

async function fileReturn() {
  if (!previewData.value || !companyId.value) return
  isFiling.value = true
  try {
    // Generate XML first
    const xmlResponse = await window.axios.post(
      '/tax/cit-return',
      getRequestPayload(),
      { responseType: 'text' }
    )

    const payload = getRequestPayload()
    await window.axios.post('/tax/cit-return/file', {
      company_id: payload.company_id,
      year: payload.year,
      return_data: previewData.value,
      xml_content: xmlResponse.data,
    })

    notificationStore.showNotification({
      type: 'success',
      message: t('tax.cit.filed', 'CIT return filed successfully'),
    })
    citFiled.value = true
    loadPeriods()
  } catch (error) {
    const msg = error.response?.data?.message || 'Failed to file CIT return'
    notificationStore.showNotification({ type: 'error', message: msg })
  } finally {
    isFiling.value = false
  }
}

async function loadPeriods() {
  if (!companyId.value) return
  isLoadingPeriods.value = true
  try {
    const response = await window.axios.get('/tax/cit-return/periods', {
      params: { company_id: companyId.value },
    })
    periods.value = response.data.data?.data || response.data.data || []
  } catch (error) {
    console.error('Failed to load CIT periods:', error)
  } finally {
    isLoadingPeriods.value = false
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
  loadPeriods()
})
</script>

<!-- CLAUDE-CHECKPOINT -->
