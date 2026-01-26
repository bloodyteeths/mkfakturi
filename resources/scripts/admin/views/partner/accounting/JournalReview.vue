<template>
  <BasePage>
    <BasePageHeader :title="$t('partner.accounting.journal_review')">
      <template #actions>
        <BaseButton
          variant="primary"
          @click="goToExport"
          :disabled="!selectedCompanyId"
        >
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="ArrowDownTrayIcon" />
          </template>
          {{ $t('partner.accounting.export') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Company Selector and Date Range -->
    <div class="mb-6">
      <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
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

        <BaseInputGroup :label="$t('general.from')">
          <BaseDatePicker
            v-model="filters.start_date"
            :calendar-button="true"
            calendar-button-icon="calendar"
            @update:model-value="onFilterChange"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('general.to')">
          <BaseDatePicker
            v-model="filters.end_date"
            :calendar-button="true"
            calendar-button-icon="calendar"
            @update:model-value="onFilterChange"
          />
        </BaseInputGroup>
      </div>
    </div>

    <!-- Help text -->
    <div v-if="selectedCompanyId && entries.length > 0" class="mb-4 rounded-lg bg-blue-50 p-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3 text-sm text-blue-700">
          <p><strong>{{ $t('partner.accounting.journal_review_help_title') }}:</strong> {{ $t('partner.accounting.journal_review_help_text') }}</p>
        </div>
      </div>
    </div>

    <!-- Bulk Actions -->
    <div v-if="selectedCompanyId && entries.length > 0" class="mb-4 flex gap-3">
      <BaseButton
        variant="primary-outline"
        @click="acceptAllHighConfidence"
        :disabled="!hasHighConfidenceEntries"
        :loading="partnerAccountingStore.isSaving"
      >
        <template #left="slotProps">
          <BaseIcon :class="slotProps.class" name="CheckCircleIcon" />
        </template>
        {{ $t('partner.accounting.accept_all_above', { threshold: 80 }) }}
      </BaseButton>

      <BaseButton
        variant="gray"
        @click="refreshSuggestions"
        :loading="isRefreshing"
      >
        <template #left="slotProps">
          <BaseIcon :class="slotProps.class" name="ArrowPathIcon" />
        </template>
        {{ $t('partner.accounting.refresh_suggestions') }}
      </BaseButton>
    </div>

    <!-- Loading state -->
    <div v-if="partnerAccountingStore.isLoading" class="flex justify-center py-12">
      <BaseSpinner />
    </div>

    <!-- Entries Table -->
    <div v-else-if="selectedCompanyId && entries.length > 0" class="mt-6">
      <div class="relative overflow-hidden rounded-lg border border-gray-200 bg-white shadow">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ $t('partner.accounting.journal.date') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ $t('partner.accounting.journal.document') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ $t('partner.accounting.journal.description') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ $t('partner.accounting.mappings.select_account') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ $t('partner.accounting.mappings.confidence') }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr
              v-for="entry in entries"
              :key="entry.id"
              :class="[
                'hover:bg-gray-50',
                entry.confirmed ? 'bg-green-50' : ''
              ]"
            >
              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                <div class="flex items-center gap-2">
                  {{ formatDate(entry.date) }}
                  <span
                    v-if="entry.confirmed"
                    class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700"
                  >
                    <BaseIcon name="CheckIcon" class="h-3 w-3" />
                    {{ $t('partner.accounting.confirmed') }}
                  </span>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex flex-col">
                  <span class="text-sm font-medium text-gray-900">
                    {{ entry.document_number }}
                  </span>
                  <span class="text-xs text-gray-500">
                    {{ entry.document_type }}
                  </span>
                </div>
              </td>
              <td class="px-6 py-4 text-sm text-gray-700">
                {{ entry.description }}
              </td>
              <td class="px-6 py-4">
                <AccountDropdown
                  :model-value="entry.account_id"
                  :accounts="accounts"
                  :confidence="entry.confidence"
                  :reason="entry.suggestion_reason"
                  @update:model-value="(value) => handleAccountUpdate(entry, value)"
                />
              </td>
              <td class="whitespace-nowrap px-6 py-4">
                <ConfidenceBadge
                  :confidence="entry.confidence"
                  :reason="entry.suggestion_reason"
                />
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination Loading Overlay -->
        <div
          v-if="isPaginating"
          class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-75"
        >
          <BaseSpinner />
        </div>

        <!-- Pagination -->
        <BaseTablePagination
          :pagination="pagination"
          @pageChange="onPageChange"
        />
      </div>
    </div>

    <!-- Empty state -->
    <div
      v-else-if="selectedCompanyId && entries.length === 0 && !partnerAccountingStore.isLoading"
      class="mt-6 flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-12"
    >
      <BaseIcon name="DocumentTextIcon" class="h-12 w-12 text-gray-400" />
      <p class="mt-2 text-sm text-gray-500">
        {{ $t('partner.accounting.no_entries_found') }}
      </p>
    </div>

    <!-- Select company message -->
    <div
      v-else
      class="mt-6 flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-12"
    >
      <BaseIcon name="BuildingOfficeIcon" class="h-12 w-12 text-gray-400" />
      <p class="mt-2 text-sm text-gray-500">
        {{ $t('partner.accounting.select_company_to_view') }}
      </p>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter, useRoute } from 'vue-router'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import { usePartnerAccountingStore } from '@/scripts/admin/stores/partner-accounting'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useDialogStore } from '@/scripts/stores/dialog'
import AccountDropdown from '@/scripts/admin/components/accounting/AccountDropdown.vue'
import ConfidenceBadge from '@/scripts/admin/components/accounting/ConfidenceBadge.vue'
import BaseTablePagination from '@/scripts/components/base/base-table/BaseTablePagination.vue'

const { t } = useI18n()
const router = useRouter()
const route = useRoute()
const consoleStore = useConsoleStore()
const partnerAccountingStore = usePartnerAccountingStore()
const notificationStore = useNotificationStore()
const dialogStore = useDialogStore()

// State
const selectedCompanyId = ref(null)
const isRefreshing = ref(false)
const currentPage = ref(1)
const isPaginating = ref(false)

// AbortController for cancelling pending requests
let abortController = null
// Debounce timer for filter changes
let filterDebounceTimer = null

const filters = reactive({
  start_date: null,
  end_date: null,
})

// Computed
const companies = computed(() => {
  return consoleStore.managedCompanies || []
})

const accounts = computed(() => {
  return partnerAccountingStore.activeAccounts || []
})

const entries = computed(() => {
  return partnerAccountingStore.journalEntries || []
})

const hasHighConfidenceEntries = computed(() => {
  return entries.value.some((e) => e.confidence >= 0.8 && !e.confirmed)
})

// Pagination computed - transform store format to BaseTablePagination format
const pagination = computed(() => {
  const p = partnerAccountingStore.journalPagination
  return {
    currentPage: p.currentPage,
    totalPages: p.totalPages,
    limit: p.perPage,
    totalCount: p.total,
    count: entries.value.length,
  }
})

// Lifecycle
onMounted(async () => {
  // Check for query params (coming from Partner Clients page)
  const query = route.query
  const hasQueryParams = query.company_id || query.start_date || query.end_date

  if (hasQueryParams) {
    // Use query params if provided
    if (query.start_date) {
      filters.start_date = query.start_date
    }
    if (query.end_date) {
      filters.end_date = query.end_date
    }
  } else {
    // Set default date range to last month using local date formatting
    const today = new Date()
    const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1)
    filters.start_date = formatDateToLocal(lastMonth)
    filters.end_date = formatDateToLocal(today)
  }

  await consoleStore.fetchCompanies()

  // Pre-select company from query param or auto-select first
  if (query.company_id) {
    selectedCompanyId.value = parseInt(query.company_id)
    await loadInitialData()
  } else if (companies.value.length > 0) {
    selectedCompanyId.value = companies.value[0].id
    await loadInitialData()
  }
})

// Cleanup on component unmount
onBeforeUnmount(() => {
  // Cancel any pending requests
  if (abortController) {
    abortController.abort()
  }
  // Clear any pending debounce timer
  if (filterDebounceTimer) {
    clearTimeout(filterDebounceTimer)
  }
})

// Helper function to format date in local timezone (YYYY-MM-DD)
function formatDateToLocal(date) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

// Methods
async function loadInitialData() {
  if (!selectedCompanyId.value) return

  try {
    // Load accounts first (needed for dropdowns)
    await partnerAccountingStore.fetchAccounts(selectedCompanyId.value)

    // Load journal entries with suggestions
    await loadEntriesWithSuggestions()
  } catch (error) {
    console.error('Failed to load initial data:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('partner.accounting.errors.load_initial_data') || 'Failed to load initial data',
    })
  }
}

async function loadEntriesWithSuggestions(page = 1) {
  if (!selectedCompanyId.value) return
  if (!filters.start_date || !filters.end_date) return

  // Cancel any pending request before starting a new one
  if (abortController) {
    abortController.abort()
  }
  abortController = new AbortController()

  currentPage.value = page

  const params = {
    start_date: filters.start_date,
    end_date: filters.end_date,
    page: page,
    per_page: 20,
  }

  try {
    await partnerAccountingStore.fetchJournalWithSuggestions(
      selectedCompanyId.value,
      params,
      abortController.signal
    )
  } catch (error) {
    // Ignore abort errors - they are expected when cancelling requests
    if (error.name === 'AbortError' || error.message === 'canceled') {
      return
    }
    console.error('Failed to load journal entries with suggestions:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('partner.accounting.errors.load_journal_entries') || 'Failed to load journal entries',
    })
  }
}

function onCompanyChange() {
  // Cancel any pending requests when company changes
  if (abortController) {
    abortController.abort()
  }
  // Clear any pending debounce timer
  if (filterDebounceTimer) {
    clearTimeout(filterDebounceTimer)
    filterDebounceTimer = null
  }
  // Preserve user's date selection when company changes
  // Dates are only set to defaults on initial load (in onMounted)
  loadInitialData()
}

function onFilterChange() {
  // Debounce filter changes to avoid rapid API calls
  if (filterDebounceTimer) {
    clearTimeout(filterDebounceTimer)
  }
  filterDebounceTimer = setTimeout(() => {
    currentPage.value = 1
    loadEntriesWithSuggestions(1)
  }, 300)
}

async function onPageChange(page) {
  isPaginating.value = true
  try {
    await loadEntriesWithSuggestions(page)
  } finally {
    isPaginating.value = false
  }
}

function onAccountChange(entry) {
  // Save immediately after change
  saveLearning(entry)
}

async function saveLearning(entry) {
  if (!selectedCompanyId.value) return

  // Skip saving if we don't have entity info for learning
  if (!entry.entity_type || !entry.entity_id) {
    console.log('Skipping learning - no entity info for entry:', entry.id)
    return
  }

  try {
    const mapping = {
      entity_type: entry.entity_type,
      entity_id: entry.entity_id,
      account_id: entry.account_id,
      accepted: true, // User explicitly selected this account
    }

    await partnerAccountingStore.learnMapping(selectedCompanyId.value, [mapping])

    notificationStore.showNotification({
      type: 'success',
      message: t('partner.accounting.mapping_saved'),
    })
  } catch (error) {
    console.error('Failed to save mapping:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('partner.accounting.errors.save_mapping') || 'Failed to save mapping',
    })
  }
}

async function acceptAllHighConfidence() {
  const count = entries.value.filter((e) => e.confidence >= 0.8 && !e.confirmed).length

  const confirmed = await dialogStore.openDialog({
    title: t('general.are_you_sure'),
    message: t('partner.accounting.accept_all_confirm', { count }),
    yesLabel: t('general.ok'),
    noLabel: t('general.cancel'),
    variant: 'primary',
    hideNoButton: false,
    size: 'lg',
  })

  if (!confirmed) return

  try {
    await partnerAccountingStore.acceptAllSuggestions(
      selectedCompanyId.value,
      0.8,
      filters.start_date,
      filters.end_date
    )

    notificationStore.showNotification({
      type: 'success',
      message: t('partner.accounting.all_accepted'),
    })

    // Reload entries
    await loadEntriesWithSuggestions()
  } catch (error) {
    console.error('Failed to accept all suggestions:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('partner.accounting.errors.accept_suggestions') || 'Failed to accept suggestions',
    })
  }
}

async function refreshSuggestions() {
  if (!selectedCompanyId.value) return

  isRefreshing.value = true

  try {
    // Simply reload entries with fresh AI suggestions
    await loadEntriesWithSuggestions(currentPage.value)

    notificationStore.showNotification({
      type: 'success',
      message: t('partner.accounting.suggestions_refreshed'),
    })
  } catch (error) {
    console.error('Failed to refresh suggestions:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('partner.accounting.errors.refresh_suggestions') || 'Failed to refresh suggestions',
    })
  } finally {
    isRefreshing.value = false
  }
}

function goToExport() {
  router.push({
    name: 'partner.accounting.export',
    query: {
      company_id: selectedCompanyId.value,
      start_date: filters.start_date,
      end_date: filters.end_date,
      from_review: 'true', // Skip to format selection step
    },
  })
}

function formatDate(date) {
  if (!date) return '-'
  const d = new Date(date)
  const day = String(d.getDate()).padStart(2, '0')
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const year = d.getFullYear()
  return `${day}.${month}.${year}`
}

function handleAccountUpdate(entry, newAccountId) {
  // Validate that the account exists in the accounts list before updating
  if (newAccountId !== null && newAccountId !== undefined) {
    const accountExists = accounts.value.some(acc => acc.id === newAccountId)
    if (!accountExists) {
      notificationStore.showNotification({
        type: 'error',
        message: t('partner.accounting.errors.invalid_account') || 'Selected account is not valid',
      })
      return
    }
  }

  // Update store via action instead of direct mutation
  partnerAccountingStore.updateEntryAccount(entry.id, newAccountId)
  // Save the learning
  onAccountChange({ ...entry, account_id: newAccountId })
}
</script>

// CLAUDE-CHECKPOINT
