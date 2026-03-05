<template>
  <BasePage>
    <BasePageHeader :title="$t('partner.accounting.journal_import.title')">
      <template #actions>
        <BaseButton
          v-if="currentStep > 1 && currentStep < 4"
          variant="gray"
          @click="previousStep"
        >
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="ChevronLeftIcon" />
          </template>
          {{ $t('general.back') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Wizard Steps Indicator -->
    <div class="mb-8">
      <nav aria-label="Progress">
        <ol role="list" class="flex items-center justify-center gap-8 sm:gap-16">
          <li
            v-for="(step, index) in steps"
            :key="index"
            class="flex items-center gap-3"
          >
            <div
              v-if="index > 0"
              :class="[
                currentStep > index ? 'bg-primary-600' : 'bg-gray-200',
                'hidden h-0.5 w-8 sm:block sm:w-16',
              ]"
            />
            <div class="flex items-center gap-2">
              <div
                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full"
                :class="[
                  currentStep > index + 1
                    ? 'bg-primary-600'
                    : currentStep === index + 1
                    ? 'border-2 border-primary-600 bg-white'
                    : 'border-2 border-gray-300 bg-white',
                ]"
              >
                <BaseIcon
                  v-if="currentStep > index + 1"
                  name="CheckIcon"
                  class="h-5 w-5 text-white"
                />
                <span
                  v-else
                  :class="[
                    currentStep === index + 1 ? 'text-primary-600' : 'text-gray-500',
                    'text-sm font-medium',
                  ]"
                >
                  {{ index + 1 }}
                </span>
              </div>
              <span
                class="text-xs font-medium"
                :class="currentStep === index + 1 ? 'text-primary-600' : 'text-gray-500'"
              >
                {{ step.name }}
              </span>
            </div>
          </li>
        </ol>
      </nav>
    </div>

    <!-- Step 1: Upload File -->
    <div v-if="currentStep === 1" class="mx-auto max-w-3xl">
      <div class="rounded-lg bg-white p-6 shadow">
        <h3 class="mb-1 text-lg font-medium text-gray-900">
          {{ $t('partner.accounting.journal_import.title') }}
        </h3>
        <p class="mb-6 text-sm text-gray-500">
          {{ $t('partner.accounting.journal_import.description') }}
        </p>

        <div class="grid gap-6">
          <!-- Company Selector -->
          <BaseInputGroup :label="$t('partner.select_company')" required>
            <BaseMultiselect
              v-model="importForm.company_id"
              :options="companies"
              :searchable="true"
              track-by="name"
              label="name"
              value-prop="id"
              :placeholder="$t('partner.select_company_placeholder')"
            />
          </BaseInputGroup>

          <!-- Drag & Drop File Upload -->
          <BaseInputGroup
            :label="$t('partner.accounting.journal_import.select_file')"
            required
          >
            <div
              v-if="!selectedFile"
              class="mt-1 cursor-pointer rounded-lg border-2 border-dashed p-6 text-center transition-colors"
              :class="[
                isDragOver
                  ? 'border-primary-300 bg-primary-50'
                  : 'border-gray-300 hover:border-gray-400',
              ]"
              @dragover.prevent="isDragOver = true"
              @dragleave.prevent="isDragOver = false"
              @drop.prevent="handleDrop"
              @click="$refs.fileInput.click()"
            >
              <BaseIcon name="CloudArrowUpIcon" class="mx-auto h-10 w-10 text-gray-400" />
              <p class="mt-2 text-sm font-medium text-gray-700">
                {{ $t('partner.accounting.journal_import.select_file') }}
              </p>
              <p class="mt-1 text-xs text-gray-500">
                {{ $t('partner.accounting.journal_import.supported_formats') }}
                — {{ $t('partner.accounting.journal_import.file_hint') }}
              </p>
              <div class="mt-3 flex justify-center gap-2">
                <span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">Pantheon .txt</span>
                <span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">CSV</span>
              </div>
              <input
                ref="fileInput"
                type="file"
                accept=".txt,.csv"
                class="hidden"
                @change="onFileSelected"
              />
            </div>

            <!-- Selected File Display -->
            <div
              v-else
              class="mt-1 flex items-center justify-between rounded-lg border border-green-200 bg-green-50 px-4 py-3"
            >
              <div class="flex items-center gap-3">
                <BaseIcon name="DocumentTextIcon" class="h-5 w-5 text-green-600" />
                <div>
                  <p class="text-sm font-medium text-green-900">{{ selectedFile.name }}</p>
                  <p class="text-xs text-green-700">{{ formatFileSize(selectedFile.size) }}</p>
                </div>
              </div>
              <button
                class="text-sm text-green-700 hover:text-green-900"
                @click="removeFile"
              >
                {{ $t('general.remove') }}
              </button>
            </div>
          </BaseInputGroup>

          <!-- Parse Error -->
          <div v-if="parseError" class="rounded-md bg-red-50 p-4">
            <div class="flex">
              <BaseIcon name="ExclamationCircleIcon" class="h-5 w-5 text-red-400" />
              <div class="ml-3">
                <p class="text-sm text-red-700">{{ parseError }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-6 flex justify-end">
          <BaseButton
            variant="primary"
            :disabled="!canParse"
            :loading="isParsing"
            @click="parseFile"
          >
            {{ isParsing
              ? $t('partner.accounting.journal_import.parsing')
              : $t('partner.accounting.journal_import.parse_file')
            }}
            <template #right="slotProps">
              <BaseIcon :class="slotProps.class" name="ChevronRightIcon" />
            </template>
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Step 2: Preview -->
    <div v-if="currentStep === 2" class="mx-auto max-w-6xl">
      <div class="rounded-lg bg-white p-6 shadow">
        <h3 class="mb-4 text-lg font-medium text-gray-900">
          {{ $t('partner.accounting.journal_import.preview_summary') }}
        </h3>

        <!-- Summary Cards -->
        <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-5">
          <div class="rounded-lg bg-gray-50 p-3 text-center">
            <div class="text-2xl font-bold text-gray-900">{{ previewData?.summary?.total_nalozi || 0 }}</div>
            <div class="text-xs text-gray-500">{{ $t('partner.accounting.journal_import.total_nalozi') }}</div>
          </div>
          <div class="rounded-lg bg-gray-50 p-3 text-center">
            <div class="text-2xl font-bold text-gray-900">{{ previewData?.summary?.total_line_items || 0 }}</div>
            <div class="text-xs text-gray-500">{{ $t('partner.accounting.journal_import.total_line_items') }}</div>
          </div>
          <div class="rounded-lg bg-gray-50 p-3 text-center">
            <div class="text-2xl font-bold text-gray-900">{{ previewData?.summary?.total_accounts || 0 }}</div>
            <div class="text-xs text-gray-500">{{ $t('partner.accounting.journal_import.total_accounts') }}</div>
          </div>
          <div class="rounded-lg bg-green-50 p-3 text-center">
            <div class="text-2xl font-bold text-green-700">{{ previewData?.summary?.balanced || 0 }}</div>
            <div class="text-xs text-green-600">{{ $t('partner.accounting.journal_import.balanced_nalozi') }}</div>
          </div>
          <div class="rounded-lg p-3 text-center" :class="(previewData?.summary?.unbalanced || 0) > 0 ? 'bg-red-50' : 'bg-gray-50'">
            <div class="text-2xl font-bold" :class="(previewData?.summary?.unbalanced || 0) > 0 ? 'text-red-700' : 'text-gray-900'">{{ previewData?.summary?.unbalanced || 0 }}</div>
            <div class="text-xs" :class="(previewData?.summary?.unbalanced || 0) > 0 ? 'text-red-600' : 'text-gray-500'">{{ $t('partner.accounting.journal_import.unbalanced_nalozi') }}</div>
          </div>
        </div>

        <!-- Warnings -->
        <div v-if="previewData?.validation?.warnings?.length" class="mb-4 rounded-md bg-yellow-50 p-4">
          <div class="flex">
            <BaseIcon name="ExclamationTriangleIcon" class="h-5 w-5 text-yellow-400" />
            <div class="ml-3">
              <p class="text-sm font-medium text-yellow-800">
                {{ $t('partner.accounting.journal_import.warning_unbalanced') }}
              </p>
            </div>
          </div>
        </div>

        <!-- Missing Accounts -->
        <div v-if="missingAccountCount > 0" class="mb-4 rounded-md bg-blue-50 p-4">
          <div class="flex">
            <BaseIcon name="InformationCircleIcon" class="h-5 w-5 text-blue-400" />
            <div class="ml-3">
              <p class="text-sm text-blue-700">
                {{ $t('partner.accounting.journal_import.missing_accounts') }}: {{ missingAccountCount }}
              </p>
            </div>
          </div>
        </div>

        <!-- Select All / Deselect All -->
        <div class="mb-3 flex items-center gap-3">
          <button
            class="text-sm font-medium text-primary-600 hover:text-primary-800"
            @click="selectAll"
          >
            {{ $t('partner.accounting.journal_import.select_all') }}
          </button>
          <span class="text-gray-300">|</span>
          <button
            class="text-sm font-medium text-gray-500 hover:text-gray-700"
            @click="deselectAll"
          >
            {{ $t('partner.accounting.journal_import.deselect_all') }}
          </button>
          <span class="ml-auto text-sm text-gray-500">
            {{ selectedCount }} / {{ previewData?.nalozi?.length || 0 }}
          </span>
        </div>

        <!-- Nalozi Table -->
        <div class="overflow-x-auto rounded-lg border border-gray-200">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="sticky top-0 bg-gray-50">
              <tr>
                <th class="w-10 px-3 py-3"></th>
                <th class="px-3 py-3 text-left text-xs font-medium uppercase text-gray-500">
                  {{ $t('partner.accounting.journal_import.nalog_id') }}
                </th>
                <th class="px-3 py-3 text-left text-xs font-medium uppercase text-gray-500">
                  {{ $t('partner.accounting.journal_import.nalog_type') }}
                </th>
                <th class="px-3 py-3 text-left text-xs font-medium uppercase text-gray-500">
                  {{ $t('partner.accounting.journal_import.nalog_date') }}
                </th>
                <th class="px-3 py-3 text-right text-xs font-medium uppercase text-gray-500">
                  {{ $t('partner.accounting.journal_import.line_count') }}
                </th>
                <th class="px-3 py-3 text-right text-xs font-medium uppercase text-gray-500">
                  {{ $t('partner.accounting.journal_import.debit') }}
                </th>
                <th class="px-3 py-3 text-right text-xs font-medium uppercase text-gray-500">
                  {{ $t('partner.accounting.journal_import.credit') }}
                </th>
                <th class="px-3 py-3 text-center text-xs font-medium uppercase text-gray-500">
                  {{ $t('partner.accounting.journal_import.status') }}
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
              <template v-for="(nalog, idx) in previewData?.nalozi || []" :key="nalog.nalog_id">
                <tr
                  class="cursor-pointer transition-colors"
                  :class="[
                    !nalog.balanced ? 'bg-red-50 hover:bg-red-100' : idx % 2 === 0 ? 'hover:bg-gray-50' : 'bg-gray-50/50 hover:bg-gray-100',
                  ]"
                  @click="toggleExpand(idx)"
                >
                  <td class="px-3 py-3">
                    <input
                      type="checkbox"
                      :checked="selectedNalozi[idx]"
                      :disabled="!nalog.balanced"
                      class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                      @click.stop="toggleSelect(idx)"
                    />
                  </td>
                  <td class="whitespace-nowrap px-3 py-3 text-sm font-medium text-gray-900">
                    {{ nalog.nalog_id }}
                  </td>
                  <td class="whitespace-nowrap px-3 py-3 text-sm text-gray-500">
                    {{ nalog.type }}
                  </td>
                  <td class="whitespace-nowrap px-3 py-3 text-sm text-gray-500">
                    {{ nalog.date }}
                  </td>
                  <td class="whitespace-nowrap px-3 py-3 text-right text-sm text-gray-500">
                    {{ nalog.line_count }}
                  </td>
                  <td class="whitespace-nowrap px-3 py-3 text-right text-sm font-mono text-gray-900">
                    {{ formatAmount(nalog.total_debit) }}
                  </td>
                  <td class="whitespace-nowrap px-3 py-3 text-right text-sm font-mono text-gray-900">
                    {{ formatAmount(nalog.total_credit) }}
                  </td>
                  <td class="whitespace-nowrap px-3 py-3 text-center">
                    <span
                      class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                      :class="nalog.balanced
                        ? 'bg-green-100 text-green-800'
                        : 'bg-red-100 text-red-800'"
                    >
                      {{ nalog.balanced
                        ? $t('partner.accounting.journal_import.balanced')
                        : $t('partner.accounting.journal_import.unbalanced')
                      }}
                    </span>
                  </td>
                </tr>

                <!-- Expanded Line Items -->
                <tr v-if="expandedRows[idx]">
                  <td colspan="8" class="bg-gray-50 px-6 py-3">
                    <table class="min-w-full">
                      <thead>
                        <tr>
                          <th class="px-2 py-1 text-left text-xs font-medium text-gray-400">
                            {{ $t('partner.accounting.journal_import.account_code') }}
                          </th>
                          <th class="px-2 py-1 text-left text-xs font-medium text-gray-400">
                            {{ $t('partner.accounting.journal_import.account_name') }}
                          </th>
                          <th class="px-2 py-1 text-right text-xs font-medium text-gray-400">
                            {{ $t('partner.accounting.journal_import.debit') }}
                          </th>
                          <th class="px-2 py-1 text-right text-xs font-medium text-gray-400">
                            {{ $t('partner.accounting.journal_import.credit') }}
                          </th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr
                          v-for="(item, li) in nalog.line_items"
                          :key="li"
                          class="border-t border-gray-100"
                        >
                          <td class="px-2 py-1 text-xs font-mono text-gray-700">
                            {{ item.account_code }}
                          </td>
                          <td class="px-2 py-1 text-xs text-gray-600">
                            {{ item.account_name }}
                          </td>
                          <td class="px-2 py-1 text-right text-xs font-mono"
                              :class="!item.credited ? 'text-gray-900 font-medium' : 'text-gray-300'"
                          >
                            {{ !item.credited ? formatAmount(item.amount) : '' }}
                          </td>
                          <td class="px-2 py-1 text-right text-xs font-mono"
                              :class="item.credited ? 'text-gray-900 font-medium' : 'text-gray-300'"
                          >
                            {{ item.credited ? formatAmount(item.amount) : '' }}
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <!-- Auto-create accounts checkbox -->
        <div class="mt-4 flex items-center">
          <input
            v-model="autoCreateAccounts"
            type="checkbox"
            class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
          />
          <label class="ml-2 text-sm text-gray-600">
            {{ $t('partner.accounting.journal_import.auto_create_accounts') }}
          </label>
        </div>

        <div class="mt-6 flex justify-between">
          <BaseButton variant="gray" @click="previousStep">
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="ChevronLeftIcon" />
            </template>
            {{ $t('general.back') }}
          </BaseButton>
          <BaseButton
            variant="primary"
            :disabled="selectedCount === 0"
            :loading="isImporting"
            @click="performImport"
          >
            {{ isImporting
              ? $t('partner.accounting.journal_import.importing')
              : $t('partner.accounting.journal_import.import_selected')
            }}
            ({{ selectedCount }})
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Step 3: Result -->
    <div v-if="currentStep === 3" class="mx-auto max-w-3xl">
      <div class="rounded-lg bg-white p-6 shadow">
        <!-- Success -->
        <div v-if="importResult?.imported > 0" class="text-center">
          <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
            <BaseIcon name="CheckIcon" class="h-8 w-8 text-green-600" />
          </div>
          <h3 class="mt-4 text-lg font-medium text-gray-900">
            {{ $t('partner.accounting.journal_import.import_success', { count: importResult.imported }) }}
          </h3>
          <p class="mt-2 text-sm text-gray-500">
            {{ $t('partner.accounting.journal_import.import_success_detail', {
              transactions: importResult.imported,
              accounts: importResult.accounts_created || 0,
              items: importResult.total_line_items || 0,
            }) }}
          </p>

          <!-- Import Errors (partial) -->
          <div v-if="importResult?.errors?.length" class="mt-4 rounded-md bg-yellow-50 p-4 text-left">
            <p class="text-sm font-medium text-yellow-800">
              {{ importResult.errors.length }} {{ $t('partner.accounting.journal_import.skipped_nalozi') }}:
            </p>
            <ul class="mt-2 list-disc pl-5 text-sm text-yellow-700">
              <li v-for="err in importResult.errors" :key="err.nalog">
                {{ err.nalog }}: {{ err.error }}
              </li>
            </ul>
          </div>

          <div class="mt-6 flex justify-center gap-3">
            <BaseButton
              variant="primary"
              @click="goToGeneralLedger"
            >
              {{ $t('partner.accounting.journal_import.view_general_ledger') }}
            </BaseButton>
            <BaseButton
              variant="gray"
              @click="resetWizard"
            >
              {{ $t('partner.accounting.journal_import.import_another') }}
            </BaseButton>
          </div>
        </div>

        <!-- Failure -->
        <div v-else class="text-center">
          <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-100">
            <BaseIcon name="ExclamationCircleIcon" class="h-8 w-8 text-red-600" />
          </div>
          <h3 class="mt-4 text-lg font-medium text-gray-900">
            {{ $t('partner.accounting.journal_import.import_failed') }}
          </h3>
          <div v-if="importResult?.errors?.length" class="mt-4 rounded-md bg-red-50 p-4 text-left">
            <ul class="list-disc pl-5 text-sm text-red-700">
              <li v-for="err in importResult.errors" :key="err.nalog">
                {{ err.nalog }}: {{ err.error }}
              </li>
            </ul>
          </div>
          <div class="mt-6">
            <BaseButton variant="gray" @click="currentStep = 2">
              {{ $t('general.back') }}
            </BaseButton>
          </div>
        </div>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import { usePartnerAccountingStore } from '@/scripts/admin/stores/partner-accounting'
import { useNotificationStore } from '@/scripts/stores/notification'

const { t } = useI18n()
const router = useRouter()
const consoleStore = useConsoleStore()
const partnerAccountingStore = usePartnerAccountingStore()
const notificationStore = useNotificationStore()

// State
const currentStep = ref(1)
const isParsing = ref(false)
const isImporting = ref(false)
const parseError = ref(null)
const selectedFile = ref(null)
const previewData = ref(null)
const importResult = ref(null)
const selectedNalozi = ref({})
const expandedRows = ref({})
const autoCreateAccounts = ref(true)
const fileInput = ref(null)
const isDragOver = ref(false)

const importForm = reactive({
  company_id: null,
})

const steps = computed(() => [
  { name: t('partner.accounting.journal_import.step_upload'), number: 1 },
  { name: t('partner.accounting.journal_import.step_preview'), number: 2 },
  { name: t('partner.accounting.journal_import.step_import'), number: 3 },
])

const companies = computed(() => consoleStore.managedCompanies || [])

const canParse = computed(() => importForm.company_id && selectedFile.value && !isParsing.value)

const selectedCount = computed(() => {
  return Object.values(selectedNalozi.value).filter(Boolean).length
})

const missingAccountCount = computed(() => {
  const missing = previewData.value?.validation?.missing_accounts
  return missing ? Object.keys(missing).length : 0
})

// Methods
function onFileSelected(event) {
  const file = event.target.files[0]
  if (file) {
    validateAndSetFile(file)
  }
  event.target.value = ''
}

function handleDrop(event) {
  isDragOver.value = false
  const file = event.dataTransfer.files[0]
  if (file) {
    validateAndSetFile(file)
  }
}

function validateAndSetFile(file) {
  const ext = file.name.split('.').pop().toLowerCase()
  if (!['txt', 'csv'].includes(ext)) {
    parseError.value = t('partner.accounting.journal_import.supported_formats')
    return
  }
  if (file.size > 10 * 1024 * 1024) {
    parseError.value = t('partner.accounting.journal_import.file_hint')
    return
  }
  selectedFile.value = file
  parseError.value = null
}

function removeFile() {
  selectedFile.value = null
  parseError.value = null
}

async function parseFile() {
  if (!canParse.value) return
  isParsing.value = true
  parseError.value = null

  try {
    const response = await partnerAccountingStore.previewJournalImport(
      importForm.company_id,
      selectedFile.value
    )

    if (response.success) {
      previewData.value = response.data

      // Auto-select all balanced nalozi
      const nalozi = response.data.nalozi || []
      const selections = {}
      nalozi.forEach((n, idx) => {
        selections[idx] = n.balanced
      })
      selectedNalozi.value = selections

      currentStep.value = 2
    } else {
      parseError.value = response.message || t('partner.accounting.journal_import.import_failed')
    }
  } catch (error) {
    parseError.value = error.response?.data?.message || error.message || t('partner.accounting.journal_import.import_failed')
  } finally {
    isParsing.value = false
  }
}

async function performImport() {
  if (selectedCount.value === 0) return
  isImporting.value = true

  try {
    const nalozi = (previewData.value?.nalozi || []).filter((_, idx) => selectedNalozi.value[idx])

    const response = await partnerAccountingStore.importJournalEntries(
      importForm.company_id,
      {
        nalozi,
        auto_create_accounts: autoCreateAccounts.value,
      }
    )

    importResult.value = response.data
    currentStep.value = 3
  } catch (error) {
    importResult.value = {
      imported: 0,
      errors: [{ nalog: 'all', error: error.response?.data?.message || error.message }],
    }
    currentStep.value = 3
  } finally {
    isImporting.value = false
  }
}

function toggleExpand(idx) {
  expandedRows.value[idx] = !expandedRows.value[idx]
}

function toggleSelect(idx) {
  const nalog = previewData.value?.nalozi?.[idx]
  if (nalog && !nalog.balanced) return
  selectedNalozi.value[idx] = !selectedNalozi.value[idx]
}

function selectAll() {
  const nalozi = previewData.value?.nalozi || []
  const selections = {}
  nalozi.forEach((n, idx) => {
    selections[idx] = n.balanced
  })
  selectedNalozi.value = selections
}

function deselectAll() {
  selectedNalozi.value = {}
}

function previousStep() {
  if (currentStep.value > 1) {
    currentStep.value--
  }
}

function goToGeneralLedger() {
  router.push({
    name: 'partner.accounting.general-ledger',
    query: { company_id: importForm.company_id },
  })
}

function resetWizard() {
  currentStep.value = 1
  previewData.value = null
  importResult.value = null
  selectedNalozi.value = {}
  expandedRows.value = {}
  selectedFile.value = null
  parseError.value = null
}

function formatAmount(val) {
  if (!val && val !== 0) return ''
  return Number(val).toLocaleString('mk-MK', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}

function formatFileSize(bytes) {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
}

// Lifecycle
onMounted(async () => {
  try {
    await consoleStore.fetchCompanies()
    if (companies.value.length > 0) {
      importForm.company_id = companies.value[0].id
    }
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: t('partner.accounting.journal_import.import_failed'),
    })
  }
})
</script>
