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
      <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow">
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
              class="hover:bg-gray-50"
            >
              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                {{ formatDate(entry.date) }}
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
                  v-model="entry.account_id"
                  :accounts="accounts"
                  :confidence="entry.confidence"
                  :reason="entry.suggestion_reason"
                  @change="onAccountChange(entry)"
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
import { ref, reactive, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import { usePartnerAccountingStore } from '@/scripts/admin/stores/partner-accounting'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useDialogStore } from '@/scripts/stores/dialog'
import AccountDropdown from '@/scripts/admin/components/accounting/AccountDropdown.vue'
import ConfidenceBadge from '@/scripts/admin/components/accounting/ConfidenceBadge.vue'

const { t } = useI18n()
const router = useRouter()
const consoleStore = useConsoleStore()
const partnerAccountingStore = usePartnerAccountingStore()
const notificationStore = useNotificationStore()
const dialogStore = useDialogStore()

// State
const selectedCompanyId = ref(null)
const isRefreshing = ref(false)
const changedEntries = ref(new Map())

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

// Lifecycle
onMounted(async () => {
  // Set default date range to last month
  const today = new Date()
  const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1)
  filters.start_date = lastMonth.toISOString().split('T')[0]
  filters.end_date = today.toISOString().split('T')[0]

  await consoleStore.fetchCompanies()

  // Auto-select first company if available
  if (companies.value.length > 0) {
    selectedCompanyId.value = companies.value[0].id
    await loadInitialData()
  }
})

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
  }
}

async function loadEntriesWithSuggestions() {
  if (!selectedCompanyId.value) return
  if (!filters.start_date || !filters.end_date) return

  const params = {
    start_date: filters.start_date,
    end_date: filters.end_date,
  }

  try {
    await partnerAccountingStore.fetchJournalWithSuggestions(selectedCompanyId.value, params)
  } catch (error) {
    console.error('Failed to load journal entries with suggestions:', error)
  }
}

function onCompanyChange() {
  changedEntries.value.clear()
  filters.start_date = null
  filters.end_date = null

  loadInitialData()
}

function onFilterChange() {
  loadEntriesWithSuggestions()
}

function onAccountChange(entry) {
  changedEntries.value.set(entry.id, {
    entry_id: entry.id,
    account_id: entry.account_id,
  })

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
      filters.date_from,
      filters.date_to
    )

    notificationStore.showNotification({
      type: 'success',
      message: t('partner.accounting.all_accepted'),
    })

    // Reload entries
    await loadEntriesWithSuggestions()
  } catch (error) {
    console.error('Failed to accept all suggestions:', error)
  }
}

async function refreshSuggestions() {
  if (!selectedCompanyId.value) return

  isRefreshing.value = true

  try {
    const entryIds = entries.value.map((e) => e.id)
    await partnerAccountingStore.getSuggestions(selectedCompanyId.value, entryIds)

    notificationStore.showNotification({
      type: 'success',
      message: t('partner.accounting.suggestions_refreshed'),
    })

    // Reload entries
    await loadEntriesWithSuggestions()
  } catch (error) {
    console.error('Failed to refresh suggestions:', error)
  } finally {
    isRefreshing.value = false
  }
}

function goToExport() {
  router.push({
    name: 'partner.accounting.export',
    query: {
      date_from: filters.date_from,
      date_to: filters.date_to,
    },
  })
}

function formatDate(date) {
  if (!date) return '-'
  const d = new Date(date)
  const day = String(d.getDate()).padStart(2, '0')
  const month = String(d.getMonth() + 1).padStart(2, '0')
  return `${day}.${month}`
}
</script>

// CLAUDE-CHECKPOINT
