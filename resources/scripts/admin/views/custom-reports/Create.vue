<template>
  <BasePage>
    <BasePageHeader :title="t('create')">
      <template #actions>
        <BaseButton variant="primary-outline" @click="$router.push({ name: 'custom-reports.index' })">
          {{ $t('general.cancel') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Wizard Steps -->
    <div class="mb-6">
      <nav class="flex items-center justify-center" aria-label="Progress">
        <ol class="flex items-center space-x-5">
          <li v-for="(stepLabel, idx) in steps" :key="idx">
            <button
              class="flex items-center"
              :class="idx <= currentStep ? 'text-primary-600' : 'text-gray-400'"
              @click="idx < currentStep ? currentStep = idx : null"
            >
              <span
                class="flex h-8 w-8 items-center justify-center rounded-full border-2 text-sm font-medium"
                :class="idx === currentStep ? 'border-primary-600 bg-primary-600 text-white' : idx < currentStep ? 'border-primary-600 bg-primary-50 text-primary-600' : 'border-gray-300 text-gray-400'"
              >
                {{ idx + 1 }}
              </span>
              <span class="ml-2 text-sm font-medium hidden sm:block">{{ stepLabel }}</span>
            </button>
          </li>
        </ol>
      </nav>
    </div>

    <!-- Step 1: Name & Description -->
    <div v-if="currentStep === 0" class="bg-white rounded-lg shadow p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-4">{{ t('step_name') }}</h3>
      <div class="max-w-lg">
        <BaseInputGroup :label="t('name')" required>
          <BaseInput v-model="form.name" :placeholder="t('name')" />
        </BaseInputGroup>
      </div>
      <div class="mt-6 flex justify-end">
        <BaseButton variant="primary" @click="goToStep(1)" :disabled="!form.name">
          {{ $t('general.next') }}
        </BaseButton>
      </div>
    </div>

    <!-- Step 2: Account Selection -->
    <div v-if="currentStep === 1" class="bg-white rounded-lg shadow p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-4">{{ t('step_accounts') }}</h3>

      <!-- Filter Type -->
      <div class="mb-4">
        <label class="text-sm font-medium text-gray-700 mb-2 block">{{ t('account_filter') }}</label>
        <div class="flex items-center space-x-4">
          <label v-for="opt in filterTypeOptions" :key="opt.value" class="flex items-center">
            <input
              type="radio"
              v-model="form.account_filter.type"
              :value="opt.value"
              class="h-4 w-4 text-primary-600 border-gray-300 focus:ring-primary-500"
            />
            <span class="ml-2 text-sm text-gray-700">{{ opt.label }}</span>
          </label>
        </div>
      </div>

      <!-- Range Inputs -->
      <div v-if="form.account_filter.type === 'range'" class="grid grid-cols-2 gap-4 max-w-lg">
        <BaseInputGroup :label="t('range_from')">
          <BaseInput v-model="form.account_filter.from" placeholder="1000" />
        </BaseInputGroup>
        <BaseInputGroup :label="t('range_to')">
          <BaseInput v-model="form.account_filter.to" placeholder="1999" />
        </BaseInputGroup>
      </div>

      <!-- Category Selection -->
      <div v-if="form.account_filter.type === 'category'" class="max-w-lg">
        <BaseInputGroup :label="t('select_categories')">
          <div class="grid grid-cols-2 gap-2 mt-2">
            <label
              v-for="cat in accountCategories"
              :key="cat.value"
              class="flex items-center p-2 rounded border cursor-pointer hover:bg-gray-50"
              :class="form.account_filter.categories?.includes(cat.value) ? 'border-primary-500 bg-primary-50' : 'border-gray-200'"
            >
              <input
                type="checkbox"
                :value="cat.value"
                v-model="form.account_filter.categories"
                class="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
              />
              <span class="ml-2 text-sm text-gray-700">{{ cat.label }}</span>
            </label>
          </div>
        </BaseInputGroup>
      </div>

      <!-- Specific Codes -->
      <div v-if="form.account_filter.type === 'specific'" class="max-w-lg">
        <BaseInputGroup :label="t('enter_codes')">
          <BaseInput
            v-model="codesInput"
            :placeholder="t('codes_placeholder')"
          />
          <p class="text-xs text-gray-400 mt-1">{{ t('codes_placeholder') }}</p>
        </BaseInputGroup>
      </div>

      <div class="mt-6 flex justify-between">
        <BaseButton variant="primary-outline" @click="currentStep = 0">
          {{ $t('general.back') }}
        </BaseButton>
        <BaseButton variant="primary" @click="goToStep(2)">
          {{ $t('general.next') }}
        </BaseButton>
      </div>
    </div>

    <!-- Step 3: Column Selection -->
    <div v-if="currentStep === 2" class="bg-white rounded-lg shadow p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-4">{{ t('step_columns') }}</h3>

      <div class="grid grid-cols-2 md:grid-cols-3 gap-3 max-w-2xl">
        <label
          v-for="col in columnOptions"
          :key="col.value"
          class="flex items-center p-3 rounded-lg border cursor-pointer hover:bg-gray-50 transition-colors"
          :class="form.columns.includes(col.value) ? 'border-primary-500 bg-primary-50' : 'border-gray-200'"
        >
          <input
            type="checkbox"
            :value="col.value"
            v-model="form.columns"
            class="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
          />
          <span class="ml-3 text-sm font-medium text-gray-700">{{ col.label }}</span>
        </label>
      </div>

      <div class="mt-6 flex justify-between">
        <BaseButton variant="primary-outline" @click="currentStep = 1">
          {{ $t('general.back') }}
        </BaseButton>
        <BaseButton variant="primary" @click="goToStep(3)" :disabled="form.columns.length === 0">
          {{ $t('general.next') }}
        </BaseButton>
      </div>
    </div>

    <!-- Step 4: Period & Grouping -->
    <div v-if="currentStep === 3" class="bg-white rounded-lg shadow p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-4">{{ t('step_period') }}</h3>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 max-w-2xl">
        <BaseInputGroup :label="t('period')">
          <select v-model="form.period_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
            <option value="month">{{ t('monthly') }}</option>
            <option value="quarter">{{ t('quarterly') }}</option>
            <option value="year">{{ t('yearly') }}</option>
            <option value="custom">{{ t('custom_period') }}</option>
          </select>
        </BaseInputGroup>

        <BaseInputGroup :label="t('group_by')">
          <select v-model="form.group_by" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
            <option value="">{{ t('no_grouping') }}</option>
            <option value="month">{{ t('by_month') }}</option>
            <option value="quarter">{{ t('by_quarter') }}</option>
            <option value="cost_center">{{ t('by_cost_center') }}</option>
          </select>
        </BaseInputGroup>

        <BaseInputGroup :label="t('comparison')">
          <select v-model="form.comparison" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
            <option value="">{{ t('no_comparison') }}</option>
            <option value="previous_year">{{ t('previous_year') }}</option>
            <option value="budget">{{ t('budget_comparison') }}</option>
          </select>
        </BaseInputGroup>
      </div>

      <!-- Custom date range -->
      <div v-if="form.period_type === 'custom'" class="grid grid-cols-2 gap-4 max-w-lg mt-4">
        <BaseInputGroup :label="t('date_from')">
          <BaseInput v-model="form.date_from" type="date" />
        </BaseInputGroup>
        <BaseInputGroup :label="t('date_to')">
          <BaseInput v-model="form.date_to" type="date" />
        </BaseInputGroup>
      </div>

      <div class="mt-6 flex justify-between">
        <BaseButton variant="primary-outline" @click="currentStep = 2">
          {{ $t('general.back') }}
        </BaseButton>
        <BaseButton variant="primary" @click="goToStep(4)">
          {{ $t('general.next') }}
        </BaseButton>
      </div>
    </div>

    <!-- Step 5: Preview & Save -->
    <div v-if="currentStep === 4" class="space-y-6">
      <!-- Config Summary -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ t('step_preview') }}</h3>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
          <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-xs text-gray-500">{{ t('name') }}</p>
            <p class="text-sm font-medium text-gray-900">{{ form.name }}</p>
          </div>
          <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-xs text-gray-500">{{ t('account_filter') }}</p>
            <p class="text-sm font-medium text-gray-900">{{ filterLabel(form.account_filter) }}</p>
          </div>
          <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-xs text-gray-500">{{ t('columns') }}</p>
            <p class="text-sm font-medium text-gray-900">{{ form.columns.join(', ') }}</p>
          </div>
          <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-xs text-gray-500">{{ t('period') }}</p>
            <p class="text-sm font-medium text-gray-900">{{ periodLabel(form.period_type) }}</p>
          </div>
        </div>

        <!-- Preview Button -->
        <BaseButton variant="primary-outline" @click="runPreview" :disabled="isPreviewing">
          {{ isPreviewing ? t('loading') : t('preview') }}
        </BaseButton>
      </div>

      <!-- Preview Table -->
      <div v-if="previewData" class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
          <h3 class="text-sm font-medium text-gray-700">{{ t('preview') }} ({{ previewData.rows?.length || 0 }} rows)</h3>
          <span class="text-xs text-gray-400">
            {{ previewData.period?.from }} - {{ previewData.period?.to }}
          </span>
        </div>

        <div v-if="previewData.rows && previewData.rows.length > 0" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th
                  v-for="col in previewData.columns"
                  :key="col"
                  class="px-4 py-3 text-xs font-medium text-gray-500 uppercase"
                  :class="isNumericColumn(col) ? 'text-right' : 'text-left'"
                >
                  {{ columnLabel(col) }}
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
              <tr v-for="(row, idx) in previewData.rows" :key="idx" class="hover:bg-gray-50">
                <td
                  v-for="col in previewData.columns"
                  :key="col"
                  class="px-4 py-2 text-sm"
                  :class="isNumericColumn(col) ? 'text-right font-mono' : 'text-left'"
                >
                  {{ isNumericColumn(col) ? formatNumber(row[col]) : (row[col] || '-') }}
                </td>
              </tr>
              <!-- Totals row -->
              <tr v-if="previewData.totals" class="bg-primary-50 font-semibold">
                <td
                  v-for="(col, cidx) in previewData.columns"
                  :key="col"
                  class="px-4 py-3 text-sm"
                  :class="isNumericColumn(col) ? 'text-right font-mono text-primary-900' : 'text-left text-primary-900'"
                >
                  {{ cidx === 0 ? t('total') : (isNumericColumn(col) ? formatNumber(previewData.totals[col]) : '') }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="px-6 py-8 text-center text-sm text-gray-500">
          {{ t('no_data') }}
        </div>
      </div>

      <!-- Optional Schedule -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-medium text-gray-700 mb-3">{{ t('schedule_email') }} ({{ $t('general.optional') || 'Optional' }})</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-2xl">
          <BaseInputGroup :label="t('cron_expression')">
            <BaseInput v-model="form.schedule_cron" placeholder="0 8 1 * *" />
            <p class="text-xs text-gray-400 mt-1">{{ t('cron_hint') }}</p>
          </BaseInputGroup>
          <BaseInputGroup :label="t('email_recipients')">
            <BaseInput v-model="emailInput" placeholder="cfo@company.mk, accountant@company.mk" />
            <p class="text-xs text-gray-400 mt-1">{{ t('email_hint') }}</p>
          </BaseInputGroup>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex justify-between">
        <BaseButton variant="primary-outline" @click="currentStep = 3">
          {{ $t('general.back') }}
        </BaseButton>
        <BaseButton variant="primary" @click="saveTemplate" :disabled="isSaving">
          {{ t('save_template') }}
        </BaseButton>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'

import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/scripts/stores/notification'
import crMessages from '@/scripts/admin/i18n/custom-reports.js'

const locale = document.documentElement.lang || 'mk'
const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }
const fmtLocale = localeMap[locale] || 'mk-MK'

function t(key) {
  return crMessages[locale]?.custom_reports?.[key]
    || crMessages['en']?.custom_reports?.[key]
    || key
}

const router = useRouter()
const notificationStore = useNotificationStore()

const currentStep = ref(0)
const isSaving = ref(false)
const isPreviewing = ref(false)
const previewData = ref(null)
const codesInput = ref('')
const emailInput = ref('')

const steps = [
  t('step_name'),
  t('step_accounts'),
  t('step_columns'),
  t('step_period'),
  t('step_preview'),
]

const form = reactive({
  name: '',
  account_filter: {
    type: 'all',
    from: '',
    to: '',
    categories: [],
    codes: [],
  },
  columns: ['code', 'name', 'opening', 'debit', 'credit', 'closing'],
  period_type: 'year',
  group_by: '',
  comparison: '',
  date_from: '',
  date_to: '',
  schedule_cron: '',
})

const filterTypeOptions = [
  { value: 'all', label: t('all_accounts') },
  { value: 'range', label: t('account_range') },
  { value: 'category', label: t('account_category') },
  { value: 'specific', label: t('specific_accounts') },
]

const accountCategories = computed(() => [
  { value: 'OPERATING_REVENUE', label: t('cat_operating_revenue') },
  { value: 'NON_OPERATING_REVENUE', label: t('cat_non_operating_revenue') },
  { value: 'OPERATING_EXPENSE', label: t('cat_operating_expense') },
  { value: 'DIRECT_EXPENSE', label: t('cat_direct_expense') },
  { value: 'OVERHEAD_EXPENSE', label: t('cat_overhead_expense') },
  { value: 'NON_OPERATING_EXPENSE', label: t('cat_non_operating_expense') },
  { value: 'CURRENT_ASSET', label: t('cat_current_asset') },
  { value: 'NON_CURRENT_ASSET', label: t('cat_non_current_asset') },
  { value: 'CURRENT_LIABILITY', label: t('cat_current_liability') },
  { value: 'NON_CURRENT_LIABILITY', label: t('cat_non_current_liability') },
  { value: 'EQUITY', label: t('cat_equity') },
  { value: 'BANK', label: t('cat_bank') },
  { value: 'RECEIVABLE', label: t('cat_receivable') },
  { value: 'PAYABLE', label: t('cat_payable') },
  { value: 'INVENTORY', label: t('cat_inventory') },
])

const columnOptions = [
  { value: 'code', label: t('code') },
  { value: 'name', label: t('account_name') },
  { value: 'opening', label: t('opening') },
  { value: 'debit', label: t('debit') },
  { value: 'credit', label: t('credit') },
  { value: 'closing', label: t('closing') },
  { value: 'budget', label: t('budget') },
  { value: 'variance', label: t('variance') },
  { value: 'variance_pct', label: t('variance_pct') },
]

const numericColumns = ['opening', 'debit', 'credit', 'closing', 'budget', 'variance', 'variance_pct']

function isNumericColumn(col) {
  return numericColumns.includes(col)
}

function goToStep(step) {
  if (step === 1 && !form.name) return
  if (step === 3 && form.columns.length === 0) return
  currentStep.value = step
}

function periodLabel(type) {
  const labels = {
    month: t('monthly'),
    quarter: t('quarterly'),
    year: t('yearly'),
    custom: t('custom_period'),
  }
  return labels[type] || type || t('yearly')
}

function filterLabel(filter) {
  if (!filter || filter.type === 'all') return t('all_accounts')
  if (filter.type === 'range') return `${filter.from || '?'} - ${filter.to || '?'}`
  if (filter.type === 'category') return `${(filter.categories || []).length} ${t('categories_count')}`
  if (filter.type === 'specific') return `${(filter.codes || []).length} ${t('codes_count')}`
  return t('all_accounts')
}

function columnLabel(col) {
  const labels = {
    code: t('code'),
    name: t('account_name'),
    opening: t('opening'),
    debit: t('debit'),
    credit: t('credit'),
    closing: t('closing'),
    budget: t('budget'),
    variance: t('variance'),
    variance_pct: t('variance_pct'),
    period_group: t('period_group'),
    cost_center_name: t('cost_center'),
  }
  return labels[col] || col
}

function formatNumber(val) {
  if (val === null || val === undefined) return '-'
  return Number(val).toLocaleString(fmtLocale, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function buildAccountFilter() {
  const filter = { type: form.account_filter.type }

  if (filter.type === 'range') {
    filter.from = form.account_filter.from || '0'
    filter.to = form.account_filter.to || '9999'
  } else if (filter.type === 'category') {
    filter.categories = form.account_filter.categories || []
  } else if (filter.type === 'specific') {
    filter.codes = codesInput.value
      .split(',')
      .map(c => c.trim())
      .filter(Boolean)
  }

  return filter
}

async function runPreview() {
  isPreviewing.value = true
  previewData.value = null

  try {
    const payload = {
      account_filter: buildAccountFilter(),
      columns: form.columns,
      period_type: form.period_type,
      group_by: form.group_by || null,
      comparison: form.comparison || null,
    }

    if (form.period_type === 'custom') {
      payload.date_from = form.date_from || null
      payload.date_to = form.date_to || null
    }

    const response = await window.axios.post('/custom-reports/preview', payload)
    previewData.value = response.data?.data || null
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('error_preview'),
    })
  } finally {
    isPreviewing.value = false
  }
}

async function saveTemplate() {
  isSaving.value = true

  try {
    const scheduleEmails = emailInput.value
      ? emailInput.value.split(',').map(e => e.trim()).filter(Boolean)
      : null

    const payload = {
      name: form.name,
      account_filter: buildAccountFilter(),
      columns: form.columns,
      period_type: form.period_type || null,
      group_by: form.group_by || null,
      comparison: form.comparison || null,
      schedule_cron: form.schedule_cron || null,
      schedule_emails: scheduleEmails,
    }

    await window.axios.post('/custom-reports', payload)

    notificationStore.showNotification({
      type: 'success',
      message: t('template_saved'),
    })

    router.push({ name: 'custom-reports.index' })
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('error_saving'),
    })
  } finally {
    isSaving.value = false
  }
}
</script>

<!-- CLAUDE-CHECKPOINT -->
