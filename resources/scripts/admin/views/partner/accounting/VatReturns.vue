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
          track-by="name"
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
      <!-- VAT Number Warning Banner -->
      <div
        v-if="vatStatus && !vatStatus.vat_number"
        class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4"
      >
        <div class="flex items-center">
          <BaseIcon name="ExclamationTriangleIcon" class="h-5 w-5 text-amber-600 mr-3 flex-shrink-0" />
          <div>
            <p class="text-sm font-medium text-amber-800">
              {{ $t('banking.vat_number_missing', 'VAT number (ЕДБ) is not set for this company.') }}
            </p>
            <p class="mt-1 text-xs text-amber-700">
              {{ $t('banking.vat_number_missing_help', 'VAT returns cannot be generated without a valid ЕДБ number. Please update company settings first.') }}
            </p>
          </div>
        </div>
      </div>

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
                {{ $t('banking.vat_number', 'VAT Number (ЕДБ)') }}: {{ vatStatus.vat_number || $t('general.not_set', 'Not set') }}
              </p>
              <p v-if="vatStatus.next_deadline" class="mt-1 text-xs text-gray-400">
                {{ $t('banking.next_deadline', 'Next deadline') }}: {{ formatDate(vatStatus.next_deadline) }}
              </p>
            </div>
            <span
              class="inline-flex items-center rounded-full px-3 py-0.5 text-sm font-medium"
              :class="vatStatusClass"
            >
              {{ translateStatus(vatStatus.current_status) }}
            </span>
          </div>
        </div>

        <!-- Period Selector -->
        <div class="mb-6 rounded-lg bg-white shadow p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">
            {{ $t('banking.generate_return', 'Generate VAT Return (ДДВ-04)') }}
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <BaseInputGroup :label="$t('banking.period_type', 'Period Type')">
              <BaseMultiselect
                v-model="vatForm.period_type"
                :options="periodTypes"
                label="label"
                value-prop="value"
                @update:model-value="onPeriodTypeChange"
              />
            </BaseInputGroup>
            <BaseInputGroup :label="$t('banking.select_period', 'Select Period')">
              <BaseMultiselect
                v-model="selectedPeriod"
                :options="periodOptions"
                label="label"
                value-prop="value"
                :searchable="true"
                :placeholder="$t('banking.select_month', 'Select month...')"
                @update:model-value="onPeriodSelect"
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
                :disabled="!canGenerateVat"
                @click="previewVat"
              >
                {{ $t('general.preview', 'Preview') }}
              </BaseButton>
              <BaseButton
                variant="primary"
                :loading="isGeneratingVat"
                :disabled="!canGenerateVat"
                @click="generateVatXml"
              >
                XML
              </BaseButton>
            </div>
          </div>
          <p v-if="!canGenerateVat && selectedCompanyId" class="mt-2 text-xs text-red-500">
            {{ vatValidationMessage }}
          </p>
        </div>

        <!-- VAT Preview Data -->
        <div v-if="vatPreviewData" class="mb-6 rounded-lg bg-white shadow overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-medium text-gray-900">
                {{ $t('banking.vat_preview', 'VAT Calculation Preview') }}
              </h3>
              <span class="text-sm text-gray-500">
                {{ vatForm.period_start }} — {{ vatForm.period_end }}
              </span>
            </div>
          </div>

          <!-- Output VAT Table -->
          <div class="px-6 py-3 bg-blue-50 border-b border-gray-200">
            <span class="text-xs font-medium text-blue-700 uppercase">
              {{ $t('banking.output_vat', 'Output VAT (Излезен ДДВ)') }}
            </span>
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
                <td class="px-6 py-3 text-sm text-gray-900">18% ({{ $t('banking.standard', 'Standard') }})</td>
                <td class="px-6 py-3 text-sm text-right text-gray-900">{{ formatMoney(vatPreviewData.standard.taxable_base) }}</td>
                <td class="px-6 py-3 text-sm text-right text-gray-900">{{ formatMoney(vatPreviewData.standard.vat_amount) }}</td>
                <td class="px-6 py-3 text-sm text-right text-gray-500">{{ vatPreviewData.standard.transaction_count }}</td>
              </tr>
              <tr>
                <td class="px-6 py-3 text-sm text-gray-900">5% ({{ $t('banking.reduced', 'Reduced') }})</td>
                <td class="px-6 py-3 text-sm text-right text-gray-900">{{ formatMoney(vatPreviewData.reduced.taxable_base) }}</td>
                <td class="px-6 py-3 text-sm text-right text-gray-900">{{ formatMoney(vatPreviewData.reduced.vat_amount) }}</td>
                <td class="px-6 py-3 text-sm text-right text-gray-500">{{ vatPreviewData.reduced.transaction_count }}</td>
              </tr>
              <tr>
                <td class="px-6 py-3 text-sm text-gray-900">0% ({{ $t('banking.zero_rate', 'Zero Rate') }})</td>
                <td class="px-6 py-3 text-sm text-right text-gray-900">{{ formatMoney(vatPreviewData.zero.taxable_base) }}</td>
                <td class="px-6 py-3 text-sm text-right text-gray-900">{{ formatMoney(vatPreviewData.zero.vat_amount) }}</td>
                <td class="px-6 py-3 text-sm text-right text-gray-500">{{ vatPreviewData.zero.transaction_count }}</td>
              </tr>
              <tr v-if="vatPreviewData.exempt && vatPreviewData.exempt.taxable_base > 0">
                <td class="px-6 py-3 text-sm text-gray-900">{{ $t('banking.exempt', 'Exempt (Ослободено)') }}</td>
                <td class="px-6 py-3 text-sm text-right text-gray-900">{{ formatMoney(vatPreviewData.exempt.taxable_base) }}</td>
                <td class="px-6 py-3 text-sm text-right text-gray-900">{{ formatMoney(vatPreviewData.exempt.vat_amount) }}</td>
                <td class="px-6 py-3 text-sm text-right text-gray-500">{{ vatPreviewData.exempt.transaction_count }}</td>
              </tr>
            </tbody>
          </table>

          <!-- VAT Summary -->
          <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
              <div>
                <span class="text-gray-500">{{ $t('banking.total_output_vat', 'Total Output VAT (Излезен)') }}</span>
                <p class="font-semibold text-gray-900">{{ formatMoney(vatPreviewData.total_output_vat) }}</p>
              </div>
              <div>
                <span class="text-gray-500">{{ $t('banking.total_input_vat', 'Total Input VAT (Влезен)') }}</span>
                <p class="font-semibold text-gray-900">{{ formatMoney(vatPreviewData.total_input_vat || 0) }}</p>
              </div>
              <div>
                <span class="text-gray-500">{{ $t('banking.net_vat', 'Net VAT Due (Нето ДДВ)') }}</span>
                <p class="text-lg font-bold" :class="netVatDue >= 0 ? 'text-red-600' : 'text-green-600'">
                  {{ formatMoney(Math.abs(netVatDue)) }}
                  <span v-if="netVatDue < 0" class="text-xs font-normal">({{ $t('banking.refund', 'Refund') }})</span>
                </p>
              </div>
              <div>
                <span class="text-gray-500">{{ $t('banking.total_transactions', 'Total Transactions') }}</span>
                <p class="font-semibold text-gray-900">{{ vatPreviewData.total_transactions }}</p>
              </div>
            </div>
          </div>

          <!-- File Return Button with Confirmation -->
          <div class="px-6 py-4 border-t border-gray-200">
            <div v-if="!showFileConfirm" class="flex justify-end">
              <BaseButton
                variant="primary"
                @click="showFileConfirm = true"
              >
                {{ $t('banking.file_return', 'File Return') }}
              </BaseButton>
            </div>
            <div v-else class="flex items-center justify-between bg-amber-50 rounded-lg p-4">
              <div>
                <p class="text-sm font-medium text-amber-800">
                  {{ $t('banking.confirm_filing', 'Confirm: File DDV-04 return for this period?') }}
                </p>
                <p class="text-xs text-amber-700 mt-1">
                  {{ $t('banking.confirm_filing_help', 'This will lock the period. You can amend later if needed.') }}
                </p>
              </div>
              <div class="flex space-x-2 ml-4">
                <BaseButton
                  variant="primary-outline"
                  size="sm"
                  @click="showFileConfirm = false"
                >
                  {{ $t('general.cancel', 'Cancel') }}
                </BaseButton>
                <BaseButton
                  variant="primary"
                  size="sm"
                  :loading="isFilingVat"
                  @click="fileVatReturn"
                >
                  {{ $t('banking.confirm_file', 'Yes, File') }}
                </BaseButton>
              </div>
            </div>
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
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('general.actions', 'Actions') }}</th>
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
                    {{ translatePeriodStatus(period.status) }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm text-right text-gray-500">{{ period.filed_returns_count }}</td>
                <td class="px-6 py-4 text-sm text-right">
                  <button
                    v-if="period.filed_returns_count > 0"
                    class="text-primary-600 hover:text-primary-800 text-xs font-medium"
                    @click="viewPeriodReturns(period)"
                  >
                    {{ $t('banking.view_returns', 'View') }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Period Returns Detail (expandable) -->
          <div v-if="selectedPeriodReturns" class="border-t border-gray-200 bg-gray-50 px-6 py-4">
            <div class="flex items-center justify-between mb-3">
              <h4 class="text-sm font-medium text-gray-700">
                {{ $t('banking.returns_for_period', 'Returns for') }} {{ selectedPeriodReturns.period?.period_name }}
              </h4>
              <button class="text-xs text-gray-500 hover:text-gray-700" @click="selectedPeriodReturns = null">
                {{ $t('general.close', 'Close') }}
              </button>
            </div>
            <div v-for="ret in selectedPeriodReturns.returns" :key="ret.id" class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
              <div>
                <span class="text-sm text-gray-900">{{ ret.status_label }}</span>
                <span v-if="ret.submitted_at" class="ml-2 text-xs text-gray-500">{{ formatDate(ret.submitted_at) }}</span>
                <span v-if="ret.submitted_by" class="ml-1 text-xs text-gray-400">{{ $t('general.by', 'by') }} {{ ret.submitted_by.name }}</span>
                <span v-if="ret.is_amendment" class="ml-2 inline-flex items-center rounded-full bg-purple-100 px-2 py-0.5 text-xs text-purple-700">
                  {{ $t('banking.amendment', 'Amendment') }}
                </span>
              </div>
              <button
                class="text-primary-600 hover:text-primary-800 text-xs font-medium"
                @click="downloadReturnXml(ret.id)"
              >
                {{ $t('banking.download_xml', 'Download XML') }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- CIT Tab -->
      <div v-if="activeTab === 'cit'">
        <div class="mb-6 rounded-lg bg-white shadow p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">
            {{ $t('tax.cit.title', 'Corporate Income Tax (Данок на добивка)') }}
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
              {{ citFiled ? $t('tax.cit.already_filed', 'Filed') : $t('tax.cit.file_at_ujp', 'File at UJP') }}
            </BaseButton>
          </div>
        </div>
      </div>
    </template>
  </BasePage>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import { useNotificationStore } from '@/scripts/stores/notification'

const { t } = useI18n()
const consoleStore = useConsoleStore()
const notificationStore = useNotificationStore()

// State
const selectedCompanyId = ref(null)
const activeTab = ref('vat')
const showFileConfirm = ref(false)

// VAT state
const vatStatus = ref(null)
const vatPreviewData = ref(null)
const vatPeriods = ref([])
const isPreviewingVat = ref(false)
const isGeneratingVat = ref(false)
const isFilingVat = ref(false)
const isLoadingPeriods = ref(false)
const selectedPeriodReturns = ref(null)
const selectedPeriod = ref(null)

// CIT state
const citPreviewData = ref(null)
const isPreviewingCit = ref(false)
const isGeneratingCit = ref(false)
const isFilingCit = ref(false)
const citFiled = ref(false)

// Helper: get previous month's start
function getPrevMonthStart() {
  const now = new Date()
  const prev = new Date(now.getFullYear(), now.getMonth() - 1, 1)
  return `${prev.getFullYear()}-${String(prev.getMonth() + 1).padStart(2, '0')}-01`
}

// Helper: get previous month's end
function getPrevMonthEnd() {
  const now = new Date()
  const lastDay = new Date(now.getFullYear(), now.getMonth(), 0).getDate()
  const prev = new Date(now.getFullYear(), now.getMonth() - 1, 1)
  return `${prev.getFullYear()}-${String(prev.getMonth() + 1).padStart(2, '0')}-${lastDay}`
}

// Forms — default to PREVIOUS month (accountants file for last month)
const vatForm = ref({
  period_type: 'MONTHLY',
  period_start: getPrevMonthStart(),
  period_end: getPrevMonthEnd(),
})

const citForm = ref({
  year: new Date().getFullYear() - 1,
  adjustments: [],
  loss_carryforward: 0,
})

// Computed
const companies = computed(() => consoleStore.managedCompanies || [])

const tabs = computed(() => [
  { key: 'vat', label: t('partner.accounting.vat_returns_tab', 'ДДВ-04 (VAT)') },
  { key: 'cit', label: t('tax.cit.tab', 'ДБ (CIT)') },
])

const periodTypes = computed(() => [
  { label: t('vat.monthly', 'Месечно (Monthly)'), value: 'MONTHLY' },
  { label: t('vat.quarterly', 'Квартално (Quarterly)'), value: 'QUARTERLY' },
])

// Period options: generate list of months/quarters for quick selection
const periodOptions = computed(() => {
  const options = []
  const now = new Date()

  if (vatForm.value.period_type === 'MONTHLY') {
    // Last 24 months
    for (let i = 1; i <= 24; i++) {
      const d = new Date(now.getFullYear(), now.getMonth() - i, 1)
      const year = d.getFullYear()
      const month = d.getMonth() + 1
      const monthName = d.toLocaleDateString('mk-MK', { month: 'long', year: 'numeric' })
      const lastDay = new Date(year, month, 0).getDate()
      options.push({
        label: monthName,
        value: `${year}-${String(month).padStart(2, '0')}`,
        start: `${year}-${String(month).padStart(2, '0')}-01`,
        end: `${year}-${String(month).padStart(2, '0')}-${lastDay}`,
      })
    }
  } else {
    // Last 8 quarters
    for (let i = 1; i <= 8; i++) {
      const qDate = new Date(now.getFullYear(), now.getMonth() - (i * 3), 1)
      const q = Math.floor(qDate.getMonth() / 3) + 1
      const year = qDate.getFullYear()
      const startMonth = (q - 1) * 3 + 1
      const endMonth = q * 3
      const lastDay = new Date(year, endMonth, 0).getDate()
      options.push({
        label: `Q${q} ${year}`,
        value: `${year}-Q${q}`,
        start: `${year}-${String(startMonth).padStart(2, '0')}-01`,
        end: `${year}-${String(endMonth).padStart(2, '0')}-${lastDay}`,
      })
    }
  }

  return options
})

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

// Validation
const canGenerateVat = computed(() => {
  if (!vatStatus.value?.vat_number) return false
  if (!vatForm.value.period_start || !vatForm.value.period_end) return false
  if (vatForm.value.period_start > vatForm.value.period_end) return false
  return true
})

const vatValidationMessage = computed(() => {
  if (!vatStatus.value?.vat_number) {
    return t('banking.vat_number_required', 'Company must have a VAT number (ЕДБ) set in settings.')
  }
  if (!vatForm.value.period_start || !vatForm.value.period_end) {
    return t('banking.dates_required', 'Start and end dates are required.')
  }
  if (vatForm.value.period_start > vatForm.value.period_end) {
    return t('banking.invalid_date_range', 'End date must be after start date.')
  }
  return ''
})

const netVatDue = computed(() => {
  if (!vatPreviewData.value) return 0
  return (vatPreviewData.value.total_output_vat || 0) - (vatPreviewData.value.total_input_vat || 0)
})

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
    case 'no_returns': return 'bg-yellow-100 text-yellow-800'
    default: return 'bg-gray-100 text-gray-800'
  }
})

// Period type change → reset dates
function onPeriodTypeChange() {
  selectedPeriod.value = null
  vatPreviewData.value = null
  showFileConfirm.value = false
  // Set default to first option in period list
  if (periodOptions.value.length > 0) {
    const first = periodOptions.value[0]
    selectedPeriod.value = first.value
    vatForm.value.period_start = first.start
    vatForm.value.period_end = first.end
  }
}

// Period select → auto-fill dates
function onPeriodSelect(val) {
  const opt = periodOptions.value.find(o => o.value === val)
  if (opt) {
    vatForm.value.period_start = opt.start
    vatForm.value.period_end = opt.end
  }
  vatPreviewData.value = null
  showFileConfirm.value = false
}

// Translation helpers
function translateStatus(status) {
  const map = {
    compliant: t('banking.status_compliant', 'Compliant'),
    overdue: t('banking.status_overdue', 'Overdue'),
    no_returns: t('banking.status_no_returns', 'No returns'),
    unknown: t('banking.status_unknown', 'Unknown'),
  }
  return map[status] || status
}

function translatePeriodStatus(status) {
  const map = {
    open: t('banking.period_open', 'Open'),
    closed: t('banking.period_closed', 'Closed'),
    filed: t('banking.period_filed', 'Filed'),
    amended: t('banking.period_amended', 'Amended'),
  }
  return map[(status || '').toLowerCase()] || status
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  try {
    return new Date(dateStr).toLocaleDateString('mk-MK', { day: '2-digit', month: '2-digit', year: 'numeric' })
  } catch {
    return dateStr
  }
}

// Methods
function onCompanyChange() {
  vatPreviewData.value = null
  citPreviewData.value = null
  vatPeriods.value = []
  vatStatus.value = null
  showFileConfirm.value = false
  selectedPeriodReturns.value = null

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
  showFileConfirm.value = false
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
    const companyName = companies.value.find(c => c.id === selectedCompanyId.value)?.name || selectedCompanyId.value
    link.setAttribute('download', `DDV04_${companyName}_${vatForm.value.period_start}_${vatForm.value.period_end}.xml`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    notificationStore.showNotification({ type: 'success', message: t('banking.xml_downloaded', 'DDV-04 XML downloaded') })
  } catch (error) {
    let msg = 'Failed to generate VAT XML'
    if (error.response?.data instanceof Blob) {
      try {
        const text = await error.response.data.text()
        const json = JSON.parse(text)
        msg = json.message || json.error || msg
      } catch (e) { /* ignore parse errors */ }
    } else if (error.response?.data?.message) {
      msg = error.response.data.message
    }
    notificationStore.showNotification({ type: 'error', message: msg })
  } finally {
    isGeneratingVat.value = false
  }
}

async function fileVatReturn() {
  if (!vatPreviewData.value) return
  isFilingVat.value = true
  try {
    await window.axios.post(
      `/partner/companies/${selectedCompanyId.value}/tax/vat-return/file`,
      {
        ...vatForm.value,
        generate_xml: true,
      }
    )
    notificationStore.showNotification({ type: 'success', message: t('banking.return_filed', 'Tax return filed successfully') })
    vatPreviewData.value = null
    showFileConfirm.value = false
    loadVatPeriods()
  } catch (error) {
    const msg = error.response?.data?.message || 'Failed to file VAT return'
    notificationStore.showNotification({ type: 'error', message: msg })
  } finally {
    isFilingVat.value = false
  }
}

async function viewPeriodReturns(period) {
  try {
    const response = await window.axios.get(
      `/partner/companies/${selectedCompanyId.value}/tax/vat-return/periods/${period.id}/returns`
    )
    selectedPeriodReturns.value = response.data.data
  } catch (error) {
    console.error('Failed to load returns:', error)
  }
}

async function downloadReturnXml(returnId) {
  try {
    const response = await window.axios.get(
      `/partner/companies/${selectedCompanyId.value}/tax/vat-return/${returnId}/download-xml`,
      { responseType: 'blob' }
    )
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `DDV04_return_${returnId}.xml`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    let msg = 'Failed to download XML'
    if (error.response?.data instanceof Blob) {
      try {
        const text = await error.response.data.text()
        const json = JSON.parse(text)
        msg = json.message || json.error || msg
      } catch (e) { /* ignore */ }
    }
    notificationStore.showNotification({ type: 'error', message: msg })
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
    let msg = 'Failed to generate CIT XML'
    if (error.response?.data instanceof Blob) {
      try {
        const text = await error.response.data.text()
        const json = JSON.parse(text)
        msg = json.message || json.error || msg
      } catch (e) { /* ignore parse errors */ }
    } else if (error.response?.data?.message) {
      msg = error.response.data.message
    }
    notificationStore.showNotification({ type: 'error', message: msg })
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
    notificationStore.showNotification({ type: 'success', message: t('tax.cit.filed', 'CIT return saved. Opening UJP e-Tax portal...') })
    citFiled.value = true

    window.open('https://etax.ujp.gov.mk', '_blank')
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
    case 'amended': return 'bg-purple-100 text-purple-800'
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
onMounted(async () => {
  await consoleStore.fetchCompanies()
  if (companies.value.length === 1) {
    selectedCompanyId.value = companies.value[0].id
    onCompanyChange()
  }
  // Set initial period selection
  if (periodOptions.value.length > 0) {
    selectedPeriod.value = periodOptions.value[0].value
  }
})
</script>

<!-- CLAUDE-CHECKPOINT -->
