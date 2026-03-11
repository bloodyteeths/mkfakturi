<template>
  <BasePage>
    <BasePageHeader :title="t('batch_operations.title')">
      <template #actions>
        <BaseButton
          v-if="selectedOperation && selectedCompanyIds.length > 0"
          variant="primary"
          :loading="isSubmitting"
          :disabled="isSubmitting || !isFormValid"
          @click="confirmStartBatch"
        >
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="PlayIcon" />
          </template>
          {{ t('batch_operations.start_batch') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Step 1: Operation Selector -->
    <div class="mb-6">
      <h3 class="text-sm font-medium text-gray-700 mb-3">
        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-primary-100 text-primary-700 text-xs font-bold mr-1.5">1</span>
        {{ t('batch_operations.select_operation') }}
      </h3>
      <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        <div
          v-for="op in operations"
          :key="op.key"
          class="relative cursor-pointer rounded-lg border-2 p-4 transition-all hover:shadow-md"
          :class="[
            selectedOperation === op.key
              ? 'border-primary-500 bg-primary-50 ring-2 ring-primary-200'
              : 'border-gray-200 bg-white hover:border-gray-300'
          ]"
          @click="selectOperation(op.key)"
        >
          <div class="flex items-center gap-3">
            <div
              class="flex h-10 w-10 items-center justify-center rounded-lg"
              :class="selectedOperation === op.key ? 'bg-primary-100 text-primary-600' : 'bg-gray-100 text-gray-500'"
            >
              <BaseIcon :name="op.icon" class="h-5 w-5" />
            </div>
            <div>
              <p class="text-sm font-semibold text-gray-900">
                {{ t('batch_operations.' + op.key) }}
              </p>
              <p class="text-xs text-gray-500 mt-0.5">
                {{ t('batch_operations.' + op.key + '_desc') }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Step 2: Company Multi-Selector -->
    <div v-if="selectedOperation" class="mb-6">
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-medium text-gray-700">
          <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-primary-100 text-primary-700 text-xs font-bold mr-1.5">2</span>
          {{ t('batch_operations.select_companies') }}
        </h3>
        <div class="flex gap-2">
          <BaseButton variant="gray" size="sm" @click="selectAllCompanies">
            {{ t('batch_operations.select_all') }}
          </BaseButton>
          <BaseButton variant="gray" size="sm" @click="deselectAllCompanies">
            {{ t('batch_operations.deselect_all') }}
          </BaseButton>
        </div>
      </div>

      <!-- Search filter for companies -->
      <div v-if="companies.length > 10" class="mb-3">
        <input
          v-model="companySearch"
          type="text"
          :placeholder="t('batch_operations.search_companies')"
          class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50"
        />
      </div>

      <div class="rounded-lg border border-gray-200 bg-white p-4 max-h-64 overflow-y-auto">
        <div v-if="companies.length === 0" class="text-sm text-gray-500 text-center py-4">
          {{ t('batch_operations.no_companies_available') }}
        </div>
        <div v-else class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
          <label
            v-for="company in filteredCompanies"
            :key="company.id"
            class="flex items-center gap-2 rounded p-2 hover:bg-gray-50 cursor-pointer"
          >
            <input
              type="checkbox"
              :value="company.id"
              v-model="selectedCompanyIds"
              class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
            />
            <span class="text-sm text-gray-700">{{ company.name }}</span>
          </label>
        </div>
      </div>

      <p v-if="selectedCompanyIds.length > 0" class="mt-2 text-xs text-gray-500">
        {{ t('batch_operations.companies_selected', { count: selectedCompanyIds.length }) }}
      </p>
    </div>

    <!-- Step 3: Parameters Panel -->
    <div
      v-if="selectedOperation && selectedCompanyIds.length > 0"
      class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-4"
    >
      <h3 class="text-sm font-medium text-gray-700 mb-3">
        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-primary-100 text-primary-700 text-xs font-bold mr-1.5">3</span>
        {{ t('batch_operations.parameters') }}
      </h3>

      <!-- Daily Close params -->
      <div v-if="selectedOperation === 'daily_close'" class="grid gap-4 md:grid-cols-2">
        <BaseInputGroup :label="t('batch_operations.date')" :error="validationErrors.date">
          <BaseDatePicker
            v-model="params.date"
            :calendar-button="true"
            calendar-button-icon="CalendarDaysIcon"
          />
        </BaseInputGroup>
      </div>

      <!-- VAT Return params -->
      <div v-if="selectedOperation === 'vat_return'" class="grid gap-4 md:grid-cols-2">
        <BaseInputGroup :label="t('batch_operations.year')">
          <BaseMultiselect
            v-model="params.year"
            :options="yearOptions"
            :searchable="false"
            label="name"
            value-prop="id"
          />
        </BaseInputGroup>
        <BaseInputGroup :label="t('batch_operations.month')">
          <BaseMultiselect
            v-model="params.month"
            :options="monthOptions"
            :searchable="false"
            label="name"
            value-prop="id"
          />
        </BaseInputGroup>
      </div>

      <!-- Trial Balance Export / Journal Export params -->
      <div v-if="selectedOperation === 'trial_balance_export' || selectedOperation === 'journal_export'" class="grid gap-4 md:grid-cols-3">
        <BaseInputGroup v-if="selectedOperation === 'trial_balance_export'" :label="t('batch_operations.report_type')">
          <BaseMultiselect
            v-model="params.report_type"
            :options="reportTypeOptions"
            :searchable="false"
            label="name"
            value-prop="id"
          />
        </BaseInputGroup>
        <BaseInputGroup :label="t('batch_operations.date_from')" :error="validationErrors.date_from">
          <BaseDatePicker
            v-model="params.date_from"
            :calendar-button="true"
            calendar-button-icon="CalendarDaysIcon"
          />
        </BaseInputGroup>
        <BaseInputGroup :label="t('batch_operations.date_to')" :error="validationErrors.date_to">
          <BaseDatePicker
            v-model="params.date_to"
            :calendar-button="true"
            calendar-button-icon="CalendarDaysIcon"
          />
        </BaseInputGroup>
        <BaseInputGroup :label="t('batch_operations.format')">
          <BaseMultiselect
            v-model="params.format"
            :options="formatOptions"
            :searchable="false"
            label="name"
            value-prop="id"
          />
        </BaseInputGroup>
      </div>

      <!-- Period Lock params -->
      <div v-if="selectedOperation === 'period_lock'" class="grid gap-4 md:grid-cols-2">
        <BaseInputGroup :label="t('batch_operations.period_start')" :error="validationErrors.period_start">
          <BaseDatePicker
            v-model="params.period_start"
            :calendar-button="true"
            calendar-button-icon="CalendarDaysIcon"
          />
        </BaseInputGroup>
        <BaseInputGroup :label="t('batch_operations.period_end')" :error="validationErrors.period_end">
          <BaseDatePicker
            v-model="params.period_end"
            :calendar-button="true"
            calendar-button-icon="CalendarDaysIcon"
          />
        </BaseInputGroup>
      </div>

      <!-- Balance Sheet / Income Statement Export params -->
      <div v-if="selectedOperation === 'balance_sheet_export' || selectedOperation === 'income_statement_export'" class="grid gap-4 md:grid-cols-2">
        <BaseInputGroup :label="t('batch_operations.as_of_date')" :error="validationErrors.as_of_date">
          <BaseDatePicker
            v-model="params.as_of_date"
            :calendar-button="true"
            calendar-button-icon="CalendarDaysIcon"
          />
        </BaseInputGroup>
        <BaseInputGroup :label="t('batch_operations.format')">
          <BaseMultiselect
            v-model="params.format"
            :options="formatOptions"
            :searchable="false"
            label="name"
            value-prop="id"
          />
        </BaseInputGroup>
      </div>

      <!-- Validation summary -->
      <p v-if="!isFormValid" class="mt-3 text-xs text-red-500">
        {{ t('batch_operations.fill_required_params') }}
      </p>
    </div>

    <!-- Active/Recent Jobs Table -->
    <div class="mt-8">
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-medium text-gray-700">
          {{ t('batch_operations.recent_jobs') }}
        </h3>
        <!-- Status filter -->
        <div class="flex items-center gap-2">
          <select
            v-model="jobStatusFilter"
            class="rounded-md border-gray-300 text-xs shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200"
          >
            <option value="">{{ t('batch_operations.all_statuses') }}</option>
            <option value="queued">{{ t('batch_operations.status_queued') }}</option>
            <option value="running">{{ t('batch_operations.status_running') }}</option>
            <option value="completed">{{ t('batch_operations.status_completed') }}</option>
            <option value="failed">{{ t('batch_operations.status_failed') }}</option>
            <option value="partially_failed">{{ t('batch_operations.status_partially_failed') }}</option>
          </select>
          <BaseButton variant="gray" size="sm" @click="fetchJobs">
            <BaseIcon name="ArrowPathIcon" class="h-4 w-4" />
          </BaseButton>
        </div>
      </div>

      <div v-if="isLoadingJobs" class="flex items-center justify-center py-12">
        <div class="h-8 w-8 animate-spin rounded-full border-4 border-primary-200 border-t-primary-600"></div>
      </div>

      <div v-else-if="filteredJobs.length === 0" class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-12">
        <BaseIcon name="QueueListIcon" class="h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">
          {{ t('batch_operations.no_jobs') }}
        </h3>
        <p class="mt-1 text-sm text-gray-500">
          {{ t('batch_operations.no_jobs_description') }}
        </p>
      </div>

      <div v-else class="space-y-3">
        <div
          v-for="job in filteredJobs"
          :key="job.id"
          class="rounded-lg border border-gray-200 bg-white overflow-hidden"
        >
          <!-- Job header row -->
          <div
            class="flex items-center justify-between p-4 cursor-pointer hover:bg-gray-50"
            @click="toggleJobExpanded(job.id)"
          >
            <div class="flex items-center gap-4">
              <!-- Status badge -->
              <span
                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                :class="statusBadgeClass(job.status)"
              >
                <span
                  v-if="job.status === 'running'"
                  class="mr-1.5 h-2 w-2 animate-pulse rounded-full bg-blue-400"
                ></span>
                {{ t('batch_operations.status_' + job.status) }}
              </span>

              <!-- Operation type -->
              <span class="text-sm font-medium text-gray-900">
                {{ t('batch_operations.' + job.operation_type) }}
              </span>

              <!-- Company count -->
              <span class="text-xs text-gray-500">
                {{ job.total_items }} {{ t('batch_operations.companies_count') }}
              </span>
            </div>

            <div class="flex items-center gap-4">
              <!-- Progress bar -->
              <div v-if="job.status === 'running' || job.status === 'completed' || job.status === 'partially_failed'" class="w-32">
                <div class="flex items-center gap-2">
                  <div class="flex-1 h-2 rounded-full bg-gray-200 overflow-hidden">
                    <div
                      class="h-full rounded-full transition-all duration-300"
                      :class="progressBarClass(job)"
                      :style="{ width: getProgressPercentage(job) + '%' }"
                    ></div>
                  </div>
                  <span class="text-xs text-gray-500 w-10 text-right">
                    {{ getProgressPercentage(job) }}%
                  </span>
                </div>
              </div>

              <!-- Duration / Created -->
              <span class="text-xs text-gray-400">
                {{ formatDateTime(job.created_at) }}
              </span>

              <!-- Cancel button for queued jobs -->
              <BaseButton
                v-if="job.status === 'queued'"
                variant="danger-outline"
                size="sm"
                @click.stop="confirmCancelJob(job)"
              >
                {{ t('batch_operations.cancel') }}
              </BaseButton>

              <!-- Expand icon -->
              <BaseIcon
                :name="expandedJobIds.includes(job.id) ? 'ChevronUpIcon' : 'ChevronDownIcon'"
                class="h-4 w-4 text-gray-400"
              />
            </div>
          </div>

          <!-- Expanded: per-company results -->
          <div
            v-if="expandedJobIds.includes(job.id) && job.results && job.results.length > 0"
            class="border-t border-gray-200 bg-gray-50"
          >
            <table class="min-w-full divide-y divide-gray-200">
              <thead>
                <tr>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                    {{ t('batch_operations.company') }}
                  </th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                    {{ t('batch_operations.result_status') }}
                  </th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                    {{ t('batch_operations.result_message') }}
                  </th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="(result, idx) in job.results" :key="idx">
                  <td class="px-4 py-2 text-sm text-gray-700">
                    {{ getCompanyName(result.company_id) }}
                  </td>
                  <td class="px-4 py-2">
                    <span
                      class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                      :class="resultStatusClass(result.status)"
                    >
                      {{ t('batch_operations.status_' + result.status) || result.status }}
                    </span>
                  </td>
                  <td class="px-4 py-2 text-sm text-gray-500">
                    {{ result.message }}
                  </td>
                  <td class="px-4 py-2">
                    <button
                      v-if="result.file_path && result.status === 'success'"
                      class="inline-flex items-center gap-1 text-xs font-medium text-primary-600 hover:text-primary-800"
                      @click.stop="downloadFile(job.id, result.company_id)"
                    >
                      <BaseIcon name="ArrowDownTrayIcon" class="h-3.5 w-3.5" />
                      {{ t('batch_operations.download') }}
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useNotificationStore } from '@/scripts/stores/notification'
import batchMessages from '@/scripts/admin/i18n/batch-operations.js'

const axios = window.axios

const consoleStore = useConsoleStore()
const dialogStore = useDialogStore()
const notificationStore = useNotificationStore()

const locale = document.documentElement.lang || 'mk'
const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }
const fmtLocale = localeMap[locale] || 'mk-MK'

function t(key, params = {}) {
  const keys = key.split('.')
  const ns = keys[0]
  const k = keys.slice(1).join('.')

  let value = null
  if (ns === 'batch_operations') {
    value = batchMessages[locale]?.batch_operations?.[k]
      || batchMessages['en']?.batch_operations?.[k]
      || key
  } else {
    return key
  }

  if (typeof value === 'string' && params) {
    Object.keys(params).forEach(p => {
      value = value.replace(`{${p}}`, params[p])
    })
  }

  return value
}

// State
const selectedOperation = ref(null)
const selectedCompanyIds = ref([])
const isSubmitting = ref(false)
const isLoadingJobs = ref(false)
const jobs = ref([])
const expandedJobIds = ref([])
const companySearch = ref('')
const jobStatusFilter = ref('')
let pollingInterval = null

function formatDateToLocalYMD(date) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const now = new Date()

const params = reactive({
  date: formatDateToLocalYMD(now),
  year: now.getFullYear(),
  month: now.getMonth() + 1,
  date_from: formatDateToLocalYMD(new Date(now.getFullYear(), 0, 1)),
  date_to: formatDateToLocalYMD(now),
  as_of_date: formatDateToLocalYMD(now),
  period_start: formatDateToLocalYMD(new Date(now.getFullYear(), now.getMonth(), 1)),
  period_end: formatDateToLocalYMD(new Date(now.getFullYear(), now.getMonth() + 1, 0)),
  report_type: 'trial_balance',
  format: 'csv',
})

// Operation definitions
const operations = [
  { key: 'daily_close', icon: 'CalendarIcon' },
  { key: 'vat_return', icon: 'DocumentTextIcon' },
  { key: 'trial_balance_export', icon: 'ArrowDownTrayIcon' },
  { key: 'journal_export', icon: 'DocumentArrowDownIcon' },
  { key: 'balance_sheet_export', icon: 'ScaleIcon' },
  { key: 'income_statement_export', icon: 'ChartBarIcon' },
  { key: 'period_lock', icon: 'LockClosedIcon' },
]

// Dropdown options
const yearOptions = computed(() => {
  const currentYear = new Date().getFullYear()
  const years = []
  for (let y = currentYear; y >= currentYear - 5; y--) {
    years.push({ id: y, name: String(y) })
  }
  return years
})

const MK_MONTHS = [
  'Јануари', 'Февруари', 'Март', 'Април', 'Мај', 'Јуни',
  'Јули', 'Август', 'Септември', 'Октомври', 'Ноември', 'Декември'
]
const EN_MONTHS = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December'
]

const monthOptions = computed(() => {
  const names = locale === 'mk' ? MK_MONTHS : EN_MONTHS
  return Array.from({ length: 12 }, (_, i) => ({
    id: i + 1,
    name: `${String(i + 1).padStart(2, '0')} - ${names[i]}`,
  }))
})

const reportTypeOptions = computed(() => [
  { id: 'trial_balance', name: t('batch_operations.report_type_trial_balance') },
  { id: 'general_ledger', name: t('batch_operations.report_type_general_ledger') },
  { id: 'journal_entries', name: t('batch_operations.report_type_journal_entries') },
])

const formatOptions = [
  { id: 'csv', name: 'CSV' },
  { id: 'json', name: 'JSON' },
]

// Computed
const companies = computed(() => {
  return consoleStore.managedCompanies || []
})

const filteredCompanies = computed(() => {
  if (!companySearch.value) return companies.value
  const q = companySearch.value.toLowerCase()
  return companies.value.filter(c => c.name.toLowerCase().includes(q))
})

const filteredJobs = computed(() => {
  if (!jobStatusFilter.value) return jobs.value
  return jobs.value.filter(j => j.status === jobStatusFilter.value)
})

// Client-side validation
const validationErrors = computed(() => {
  const errors = {}
  const op = selectedOperation.value

  if (op === 'daily_close' && !params.date) {
    errors.date = t('batch_operations.field_required')
  }
  if ((op === 'trial_balance_export' || op === 'journal_export')) {
    if (!params.date_from) errors.date_from = t('batch_operations.field_required')
    if (!params.date_to) errors.date_to = t('batch_operations.field_required')
    if (params.date_from && params.date_to && params.date_from > params.date_to) {
      errors.date_to = t('batch_operations.date_to_after_from')
    }
  }
  if (op === 'period_lock') {
    if (!params.period_start) errors.period_start = t('batch_operations.field_required')
    if (!params.period_end) errors.period_end = t('batch_operations.field_required')
    if (params.period_start && params.period_end && params.period_start > params.period_end) {
      errors.period_end = t('batch_operations.date_to_after_from')
    }
  }
  if ((op === 'balance_sheet_export' || op === 'income_statement_export') && !params.as_of_date) {
    errors.as_of_date = t('batch_operations.field_required')
  }
  return errors
})

const isFormValid = computed(() => {
  return Object.keys(validationErrors.value).length === 0
})

// Lifecycle
onMounted(async () => {
  try {
    await consoleStore.fetchCompanies()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: t('batch_operations.error_loading_companies'),
    })
  }

  await fetchJobs()

  pollingInterval = setInterval(() => {
    pollRunningJobs()
  }, 3000)
})

onUnmounted(() => {
  if (pollingInterval) {
    clearInterval(pollingInterval)
    pollingInterval = null
  }
})

// Methods
function selectOperation(opKey) {
  selectedOperation.value = opKey
}

function selectAllCompanies() {
  selectedCompanyIds.value = companies.value.map(c => c.id)
}

function deselectAllCompanies() {
  selectedCompanyIds.value = []
}

function toggleJobExpanded(jobId) {
  const idx = expandedJobIds.value.indexOf(jobId)
  if (idx >= 0) {
    expandedJobIds.value.splice(idx, 1)
  } else {
    expandedJobIds.value.push(jobId)
  }
}

function getCompanyName(companyId) {
  const company = companies.value.find(c => c.id === companyId)
  return company ? company.name : `#${companyId}`
}

function getProgressPercentage(job) {
  if (!job.total_items || job.total_items === 0) return 0
  return Math.round(((job.completed_items + job.failed_items) / job.total_items) * 100)
}

function statusBadgeClass(status) {
  switch (status) {
    case 'queued': return 'bg-gray-100 text-gray-800'
    case 'running': return 'bg-blue-100 text-blue-800'
    case 'completed': return 'bg-green-100 text-green-800'
    case 'failed': return 'bg-red-100 text-red-800'
    case 'partially_failed': return 'bg-amber-100 text-amber-800'
    default: return 'bg-gray-100 text-gray-800'
  }
}

function progressBarClass(job) {
  if (job.status === 'completed') return 'bg-green-500'
  if (job.status === 'failed') return 'bg-red-500'
  if (job.status === 'partially_failed') return 'bg-amber-500'
  return 'bg-blue-500'
}

function resultStatusClass(status) {
  switch (status) {
    case 'success': return 'bg-green-100 text-green-800'
    case 'failed': return 'bg-red-100 text-red-800'
    case 'skipped': return 'bg-yellow-100 text-yellow-800'
    default: return 'bg-gray-100 text-gray-800'
  }
}

function formatDateTime(dateTimeStr) {
  if (!dateTimeStr) return '-'
  try {
    const date = new Date(dateTimeStr)
    if (isNaN(date.getTime())) return '-'
    return date.toLocaleString(fmtLocale, {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  } catch {
    return '-'
  }
}

function buildParams() {
  const op = selectedOperation.value
  const p = {}

  if (op === 'daily_close') {
    p.date = params.date
  } else if (op === 'vat_return') {
    p.year = params.year
    p.month = params.month
  } else if (op === 'trial_balance_export') {
    p.report_type = params.report_type
    p.date_from = params.date_from
    p.date_to = params.date_to
    p.format = params.format
  } else if (op === 'journal_export') {
    p.report_type = 'journal_entries'
    p.date_from = params.date_from
    p.date_to = params.date_to
    p.format = params.format
  } else if (op === 'period_lock') {
    p.period_start = params.period_start
    p.period_end = params.period_end
  } else if (op === 'balance_sheet_export' || op === 'income_statement_export') {
    p.as_of_date = params.as_of_date
    p.format = params.format
  }

  return p
}

async function fetchJobs() {
  isLoadingJobs.value = true
  try {
    const response = await axios.get('/partner/batch-operations')
    jobs.value = response.data?.data || []
  } catch (error) {
    // Silently fail - the table will just be empty
  } finally {
    isLoadingJobs.value = false
  }
}

async function pollRunningJobs() {
  const runningJobs = jobs.value.filter(j => j.status === 'running' || j.status === 'queued')
  if (runningJobs.length === 0) return

  for (const job of runningJobs) {
    try {
      const response = await axios.get(`/partner/batch-operations/${job.id}/progress`)
      const updated = response.data?.data
      if (updated) {
        const idx = jobs.value.findIndex(j => j.id === job.id)
        if (idx >= 0) {
          jobs.value[idx] = { ...jobs.value[idx], ...updated }

          if (updated.status !== 'running' && updated.status !== 'queued') {
            const fullResponse = await axios.get(`/partner/batch-operations/${job.id}`)
            if (fullResponse.data?.data) {
              jobs.value[idx] = fullResponse.data.data
            }
          }
        }
      }
    } catch {
      // Silently fail for individual poll
    }
  }
}

async function confirmStartBatch() {
  if (!isFormValid.value || isSubmitting.value) return

  const opLabel = t('batch_operations.' + selectedOperation.value)
  const msg = t('batch_operations.confirm_start_detail', {
    operation: opLabel,
    count: selectedCompanyIds.value.length,
  })

  const confirmed = await dialogStore.openDialog({
    title: t('batch_operations.confirm_start_title'),
    message: msg,
    yesLabel: t('batch_operations.start_batch'),
    noLabel: t('batch_operations.cancel'),
    variant: 'primary',
    hideNoButton: false,
    size: 'lg',
  })

  if (!confirmed) return

  isSubmitting.value = true
  try {
    await axios.post('/partner/batch-operations', {
      operation_type: selectedOperation.value,
      company_ids: selectedCompanyIds.value,
      parameters: buildParams(),
    })

    notificationStore.showNotification({
      type: 'success',
      message: t('batch_operations.job_created'),
    })

    selectedOperation.value = null
    selectedCompanyIds.value = []

    await fetchJobs()
  } catch (error) {
    const data = error.response?.data
    let errorMessage = data?.message || t('batch_operations.error_generic')
    // Show field-level validation errors from backend
    if (data?.errors) {
      const fieldErrors = Object.values(data.errors).flat().join(', ')
      errorMessage = fieldErrors
    }
    notificationStore.showNotification({
      type: 'error',
      message: errorMessage,
    })
  } finally {
    isSubmitting.value = false
  }
}

async function confirmCancelJob(job) {
  const confirmed = await dialogStore.openDialog({
    title: t('batch_operations.confirm_cancel_title'),
    message: t('batch_operations.confirm_cancel'),
    yesLabel: t('batch_operations.confirm_yes'),
    noLabel: t('batch_operations.confirm_no'),
    variant: 'danger',
    hideNoButton: false,
    size: 'lg',
  })

  if (!confirmed) return

  try {
    await axios.post(`/partner/batch-operations/${job.id}/cancel`)

    notificationStore.showNotification({
      type: 'success',
      message: t('batch_operations.job_cancelled'),
    })

    await fetchJobs()
  } catch (error) {
    const errorMessage = error.response?.data?.message || t('batch_operations.error_generic')
    notificationStore.showNotification({
      type: 'error',
      message: errorMessage,
    })
  }
}

async function downloadFile(jobId, companyId) {
  try {
    const response = await axios.get(`/partner/batch-operations/${jobId}/download/${companyId}`, {
      responseType: 'blob',
    })

    // Check if the response is actually an error (JSON returned as blob)
    if (response.data?.type === 'application/json') {
      const text = await response.data.text()
      const json = JSON.parse(text)
      throw new Error(json.message || t('batch_operations.error_generic'))
    }

    const contentDisposition = response.headers['content-disposition'] || ''
    const filenameMatch = contentDisposition.match(/filename="?([^";\n]+)"?/)
    const filename = filenameMatch ? filenameMatch[1] : `export_${jobId}_${companyId}`

    const url = URL.createObjectURL(response.data)
    const a = document.createElement('a')
    a.href = url
    a.download = filename
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(url)
  } catch (error) {
    // Extract error message from blob response (axios wraps JSON errors as blobs)
    let errorMessage = t('batch_operations.error_generic')
    if (error.response?.data instanceof Blob) {
      try {
        const text = await error.response.data.text()
        const json = JSON.parse(text)
        errorMessage = json.message || errorMessage
      } catch (_) {}
    } else if (error.message) {
      errorMessage = error.message
    }
    notificationStore.showNotification({
      type: 'error',
      message: errorMessage,
    })
  }
}
</script>
