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
            v-if="previewData"
            variant="primary"
            :loading="isFiling"
            @click="fileReturn"
          >
            <template #left="slotProps">
              <BaseIcon name="PaperAirplaneIcon" :class="slotProps.class" />
            </template>
            {{ $t('banking.file_return', 'File Return') }}
          </BaseButton>
        </div>
      </div>

      <!-- Non-deductible adjustments -->
      <div class="mt-6 border-t border-gray-200 pt-6">
        <h4 class="text-sm font-medium text-gray-700 mb-3">
          {{ $t('tax.cit.adjustments', 'Non-Deductible Expense Adjustments') }}
        </h4>
        <p class="text-xs text-gray-500 mb-3">
          {{ $t('tax.cit.adjustments_help', 'Add expenses that are not tax-deductible: fines (kazni), representation excess (>1%), undocumented expenses, etc.') }}
        </p>
        <div
          v-for="(adj, index) in adjustments"
          :key="index"
          class="flex items-center gap-3 mb-2"
        >
          <BaseInput
            v-model="adj.description"
            :placeholder="$t('tax.cit.adjustment_description', 'Description')"
            class="flex-1"
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
          @click="adjustments.push({ description: '', amount: 0 })"
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
            <span class="text-base font-bold text-gray-900">{{ $t('tax.cit.taxable_base', 'Taxable Base') }}</span>
            <span class="text-xl font-bold text-gray-900">{{ formatMoney(previewData.taxable_base) }}</span>
          </div>

          <div class="flex justify-between items-center py-3 border-b border-gray-100">
            <span class="text-sm text-gray-600">{{ $t('tax.cit.cit_rate', 'CIT Rate') }}</span>
            <span class="text-lg font-semibold text-gray-900">10%</span>
          </div>

          <div class="flex justify-between items-center py-3 border-b-2 border-primary-300 bg-primary-50 px-4 rounded">
            <span class="text-base font-bold text-primary-900">{{ $t('tax.cit.cit_amount', 'CIT Amount Due') }}</span>
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
import { useNotificationStore } from '@/scripts/stores/notification'
import axios from 'axios'

const { t } = useI18n()
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
const isLoadingPeriods = ref(false)

// Computed
const yearOptions = computed(() => {
  const currentYear = new Date().getFullYear()
  const options = []
  for (let y = currentYear; y >= 2020; y--) {
    options.push({ label: String(y), value: y })
  }
  return options
})

// Methods
function getRequestPayload() {
  return {
    company_id: parseInt(window.Ls?.store?.companyId || document.querySelector('meta[name="company-id"]')?.content || 0),
    year: selectedYear.value,
    adjustments: adjustments.value.filter(a => a.description && a.amount > 0),
    loss_carryforward: lossCarryforward.value || 0,
  }
}

async function previewCit() {
  isPreviewing.value = true
  try {
    const response = await axios.post('/v1/admin/tax/cit-return/preview', getRequestPayload())
    previewData.value = response.data.data
  } catch (error) {
    const msg = error.response?.data?.message || error.response?.data?.error || 'Failed to preview CIT'
    notificationStore.showNotification({ type: 'error', message: msg })
  } finally {
    isPreviewing.value = false
  }
}

async function generateXml() {
  isGenerating.value = true
  try {
    const response = await axios.post(
      '/v1/admin/tax/cit-return',
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
    notificationStore.showNotification({ type: 'error', message: 'Failed to generate CIT XML' })
  } finally {
    isGenerating.value = false
  }
}

async function fileReturn() {
  if (!previewData.value) return
  isFiling.value = true
  try {
    // Generate XML first
    const xmlResponse = await axios.post(
      '/v1/admin/tax/cit-return',
      getRequestPayload(),
      { responseType: 'text' }
    )

    const payload = getRequestPayload()
    await axios.post('/v1/admin/tax/cit-return/file', {
      company_id: payload.company_id,
      year: payload.year,
      return_data: previewData.value,
      xml_content: xmlResponse.data,
    })

    notificationStore.showNotification({
      type: 'success',
      message: t('tax.cit.filed', 'CIT return filed successfully'),
    })
    previewData.value = null
    loadPeriods()
  } catch (error) {
    const msg = error.response?.data?.message || 'Failed to file CIT return'
    notificationStore.showNotification({ type: 'error', message: msg })
  } finally {
    isFiling.value = false
  }
}

async function loadPeriods() {
  isLoadingPeriods.value = true
  try {
    const payload = getRequestPayload()
    const response = await axios.get('/v1/admin/tax/cit-return/periods', {
      params: { company_id: payload.company_id },
    })
    periods.value = response.data.data?.data || response.data.data || []
  } catch (error) {
    console.error('Failed to load CIT periods:', error)
  } finally {
    isLoadingPeriods.value = false
  }
}

function periodStatusClass(status) {
  switch (status) {
    case 'FILED': return 'bg-green-100 text-green-800'
    case 'CLOSED': return 'bg-blue-100 text-blue-800'
    case 'OPEN': return 'bg-yellow-100 text-yellow-800'
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
