<template>
  <BasePage>
    <BasePageHeader :title="$t('partner.accounting.journal_entries')">
      <template #actions>
        <BaseButton
          variant="primary"
          @click="confirmAllPending"
          :disabled="!hasPendingEntries"
        >
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="CheckCircleIcon" />
          </template>
          {{ $t('partner.accounting.confirm_all_pending') }}
        </BaseButton>
      </template>
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

    <!-- Filters -->
    <div v-if="selectedCompanyId" class="mb-6">
      <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <!-- Date Range -->
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

        <BaseInputGroup :label="$t('general.search')">
          <BaseInput
            v-model="filters.search"
            :placeholder="$t('partner.accounting.search_entries')"
            @input="onFilterChange"
          >
            <template #left="slotProps">
              <BaseIcon name="MagnifyingGlassIcon" :class="slotProps.class" />
            </template>
          </BaseInput>
        </BaseInputGroup>
      </div>
    </div>

    <!-- Status Tabs -->
    <BaseTabGroup v-if="selectedCompanyId">
      <template #default>
        <BaseTab
          :title="$t('general.all')"
          :active="statusFilter === null"
          @click="statusFilter = null; loadEntries()"
        />
        <BaseTab
          :title="$t('partner.accounting.pending')"
          :active="statusFilter === 'pending'"
          @click="statusFilter = 'pending'; loadEntries()"
        />
        <BaseTab
          :title="$t('partner.accounting.confirmed')"
          :active="statusFilter === 'confirmed'"
          @click="statusFilter = 'confirmed'; loadEntries()"
        />
      </template>
    </BaseTabGroup>

    <!-- Loading state -->
    <div v-if="partnerAccountingStore.isLoading" class="flex justify-center py-12">
      <BaseSpinner />
    </div>

    <!-- Entries Table -->
    <div v-else-if="selectedCompanyId" class="mt-6">
      <BaseTable
        ref="tableRef"
        :data="entriesData"
        :columns="entryColumns"
        :show-filter="false"
        :loading-type="'placeholder'"
      >
        <template #cell-date="{ row }">
          <span class="text-sm text-gray-900">
            {{ formatDate(row.data.date) }}
          </span>
        </template>

        <template #cell-document="{ row }">
          <div class="flex flex-col">
            <span class="font-medium text-gray-900">
              {{ row.data.document_type }}
            </span>
            <span class="text-xs text-gray-500">
              {{ row.data.document_number }}
            </span>
          </div>
        </template>

        <template #cell-description="{ row }">
          <span class="text-sm text-gray-700">
            {{ row.data.description }}
          </span>
        </template>

        <template #cell-debit_account="{ row }">
          <div v-if="row.data.debit_account" class="flex items-center space-x-2">
            <span class="font-mono text-sm text-gray-700">
              {{ row.data.debit_account.code }}
            </span>
            <span class="text-sm text-gray-600">
              {{ row.data.debit_account.name }}
            </span>
          </div>
          <span v-else class="text-sm text-gray-400">-</span>
        </template>

        <template #cell-credit_account="{ row }">
          <div v-if="row.data.credit_account" class="flex items-center space-x-2">
            <span class="font-mono text-sm text-gray-700">
              {{ row.data.credit_account.code }}
            </span>
            <span class="text-sm text-gray-600">
              {{ row.data.credit_account.name }}
            </span>
          </div>
          <span v-else class="text-sm text-gray-400">-</span>
        </template>

        <template #cell-amount="{ row }">
          <span class="font-medium text-gray-900">
            {{ formatMoney(row.data.amount, row.data.currency) }}
          </span>
        </template>

        <template #cell-status="{ row }">
          <BaseBadge
            :bg-color="getStatusBadgeColor(row.data.status)"
            :text-color="getStatusTextColor(row.data.status)"
          >
            {{ $t(`partner.accounting.${row.data.status}`) }}
          </BaseBadge>
        </template>

        <template #cell-actions="{ row }">
          <div class="flex items-center justify-end space-x-2">
            <BaseButton
              variant="gray"
              size="sm"
              @click="toggleRowExpand(row.data.id)"
            >
              <BaseIcon
                :name="expandedRows.has(row.data.id) ? 'ChevronUpIcon' : 'ChevronDownIcon'"
                class="h-4 w-4"
              />
            </BaseButton>
            <BaseButton
              v-if="row.data.status === 'pending'"
              variant="primary-outline"
              size="sm"
              @click="openConfirmModal(row.data)"
            >
              {{ $t('partner.accounting.confirm') }}
            </BaseButton>
            <BaseButton
              v-if="row.data.status === 'pending'"
              variant="gray"
              size="sm"
              @click="skipEntry(row.data)"
            >
              {{ $t('partner.accounting.skip') }}
            </BaseButton>
          </div>
        </template>

        <!-- Expanded Row Details -->
        <template #expanded-row="{ row }">
          <div
            v-if="expandedRows.has(row.data.id)"
            class="bg-gray-50 p-4"
          >
            <div class="grid grid-cols-2 gap-4">
              <div>
                <h4 class="mb-2 text-sm font-medium text-gray-700">
                  {{ $t('partner.accounting.entry_details') }}
                </h4>
                <dl class="space-y-2 text-sm">
                  <div class="flex justify-between">
                    <dt class="text-gray-500">{{ $t('partner.accounting.entry_id') }}:</dt>
                    <dd class="font-mono text-gray-900">{{ row.data.id }}</dd>
                  </div>
                  <div class="flex justify-between">
                    <dt class="text-gray-500">{{ $t('partner.accounting.created_at') }}:</dt>
                    <dd class="text-gray-900">{{ formatDateTime(row.data.created_at) }}</dd>
                  </div>
                  <div v-if="row.data.confirmed_at" class="flex justify-between">
                    <dt class="text-gray-500">{{ $t('partner.accounting.confirmed_at') }}:</dt>
                    <dd class="text-gray-900">{{ formatDateTime(row.data.confirmed_at) }}</dd>
                  </div>
                </dl>
              </div>
              <div v-if="row.data.metadata">
                <h4 class="mb-2 text-sm font-medium text-gray-700">
                  {{ $t('partner.accounting.metadata') }}
                </h4>
                <pre class="text-xs text-gray-600">{{ JSON.stringify(row.data.metadata, null, 2) }}</pre>
              </div>
            </div>
          </div>
        </template>
      </BaseTable>
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

    <!-- Confirm Entry Modal -->
    <BaseModal
      :show="showConfirmModal"
      :title="$t('partner.accounting.confirm_entry')"
      @close="closeConfirmModal"
    >
      <form @submit.prevent="submitConfirm">
        <div class="grid gap-4">
          <!-- Entry Info -->
          <div class="rounded-md bg-gray-50 p-4">
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="font-medium text-gray-700">{{ $t('partner.accounting.document') }}:</span>
                <span class="text-gray-900">{{ confirmForm.document_type }} {{ confirmForm.document_number }}</span>
              </div>
              <div class="flex justify-between">
                <span class="font-medium text-gray-700">{{ $t('partner.accounting.description') }}:</span>
                <span class="text-gray-900">{{ confirmForm.description }}</span>
              </div>
              <div class="flex justify-between">
                <span class="font-medium text-gray-700">{{ $t('partner.accounting.amount') }}:</span>
                <span class="text-gray-900">{{ formatMoney(confirmForm.amount, confirmForm.currency) }}</span>
              </div>
            </div>
          </div>

          <!-- Account Selection -->
          <BaseInputGroup :label="$t('partner.accounting.confirm_account')" required>
            <BaseMultiselect
              v-model="confirmForm.account_id"
              :options="accountOptions"
              :searchable="true"
              track-by="id"
              label="display_name"
              value-prop="id"
              :placeholder="$t('partner.accounting.select_account_placeholder')"
            />
          </BaseInputGroup>

          <div class="rounded-md bg-blue-50 p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <BaseIcon name="InformationCircleIcon" class="h-5 w-5 text-blue-400" />
              </div>
              <div class="ml-3 text-sm text-blue-700">
                {{ $t('partner.accounting.confirm_entry_help') }}
              </div>
            </div>
          </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
          <BaseButton variant="gray" type="button" @click="closeConfirmModal">
            {{ $t('general.cancel') }}
          </BaseButton>
          <BaseButton
            variant="primary"
            type="submit"
            :loading="partnerAccountingStore.isSaving"
            :disabled="!confirmForm.account_id"
          >
            {{ $t('partner.accounting.confirm') }}
          </BaseButton>
        </div>
      </form>
    </BaseModal>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import { usePartnerAccountingStore } from '@/scripts/admin/stores/partner-accounting'
import { useDialogStore } from '@/scripts/stores/dialog'

const { t } = useI18n()
const consoleStore = useConsoleStore()
const partnerAccountingStore = usePartnerAccountingStore()
const dialogStore = useDialogStore()

// State
const selectedCompanyId = ref(null)
const statusFilter = ref(null)
const showConfirmModal = ref(false)
const tableRef = ref(null)
const expandedRows = ref(new Set())

const filters = reactive({
  start_date: null,
  end_date: null,
  search: '',
})

const confirmForm = reactive({
  id: null,
  document_type: '',
  document_number: '',
  description: '',
  amount: 0,
  currency: 'MKD',
  account_id: null,
})

// Computed
const companies = computed(() => {
  return consoleStore.managedCompanies || []
})

const entryColumns = computed(() => [
  {
    key: 'date',
    label: t('partner.accounting.date'),
    thClass: 'w-32',
    tdClass: 'text-gray-700',
  },
  {
    key: 'document',
    label: t('partner.accounting.document'),
    thClass: 'w-48',
    tdClass: 'text-gray-700',
  },
  {
    key: 'description',
    label: t('partner.accounting.description'),
    thClass: 'extra',
    tdClass: 'text-gray-700',
  },
  {
    key: 'debit_account',
    label: t('partner.accounting.debit_account'),
    thClass: 'w-64',
    tdClass: 'text-gray-700',
  },
  {
    key: 'credit_account',
    label: t('partner.accounting.credit_account'),
    thClass: 'w-64',
    tdClass: 'text-gray-700',
  },
  {
    key: 'amount',
    label: t('partner.accounting.amount'),
    thClass: 'w-32',
    tdClass: 'text-right text-gray-700',
  },
  {
    key: 'status',
    label: t('partner.accounting.status'),
    thClass: 'w-32',
    tdClass: 'text-gray-700',
  },
  {
    key: 'actions',
    label: '',
    thClass: 'w-48',
    tdClass: 'text-right',
    sortable: false,
  },
])

const accountOptions = computed(() => {
  return partnerAccountingStore.activeAccounts.map((a) => ({
    ...a,
    display_name: `${a.code} - ${a.name}`,
  }))
})

const entriesData = computed(() => {
  return {
    data: partnerAccountingStore.journalEntries,
    pagination: {
      totalPages: partnerAccountingStore.journalPagination.totalPages,
      currentPage: partnerAccountingStore.journalPagination.currentPage,
    },
  }
})

const hasPendingEntries = computed(() => {
  return partnerAccountingStore.hasPendingEntries
})

// Lifecycle
onMounted(async () => {
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

    // Load journal entries
    await loadEntries()
  } catch (error) {
    console.error('Failed to load initial data:', error)
  }
}

async function loadEntries() {
  if (!selectedCompanyId.value) return

  const params = {
    ...filters,
    status: statusFilter.value,
  }

  try {
    await partnerAccountingStore.fetchJournalEntries(selectedCompanyId.value, params)
  } catch (error) {
    console.error('Failed to load journal entries:', error)
  }
}

function onCompanyChange() {
  expandedRows.value.clear()
  statusFilter.value = null
  filters.start_date = null
  filters.end_date = null
  filters.search = ''

  loadInitialData()
}

function onFilterChange() {
  loadEntries()
}

function toggleRowExpand(rowId) {
  if (expandedRows.value.has(rowId)) {
    expandedRows.value.delete(rowId)
  } else {
    expandedRows.value.add(rowId)
  }
}

function resetConfirmForm() {
  confirmForm.id = null
  confirmForm.document_type = ''
  confirmForm.document_number = ''
  confirmForm.description = ''
  confirmForm.amount = 0
  confirmForm.currency = 'MKD'
  confirmForm.account_id = null
}

function openConfirmModal(entry) {
  confirmForm.id = entry.id
  confirmForm.document_type = entry.document_type
  confirmForm.document_number = entry.document_number
  confirmForm.description = entry.description
  confirmForm.amount = entry.amount
  confirmForm.currency = entry.currency || 'MKD'
  confirmForm.account_id = null
  showConfirmModal.value = true
}

function closeConfirmModal() {
  showConfirmModal.value = false
  resetConfirmForm()
}

async function submitConfirm() {
  try {
    await partnerAccountingStore.confirmEntry(
      selectedCompanyId.value,
      confirmForm.id,
      confirmForm.account_id
    )

    closeConfirmModal()
    await loadEntries()
  } catch (error) {
    console.error('Failed to confirm entry:', error)
  }
}

async function skipEntry(entry) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('partner.accounting.skip_entry_confirm'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'lg',
    })
    .then(async (confirmed) => {
      if (confirmed) {
        // In a real implementation, this would call an API to skip the entry
        console.log('Skip entry:', entry.id)
      }
    })
}

async function confirmAllPending() {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('partner.accounting.confirm_all_pending_confirm'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'lg',
    })
    .then(async (confirmed) => {
      if (confirmed) {
        // In a real implementation, this would call an API to confirm all pending entries
        console.log('Confirm all pending entries')
      }
    })
}

function formatDate(date) {
  if (!date) return '-'
  return new Date(date).toLocaleDateString()
}

function formatDateTime(dateTime) {
  if (!dateTime) return '-'
  return new Date(dateTime).toLocaleString()
}

function formatMoney(amount, currency = 'MKD') {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency,
  }).format(amount)
}

function getStatusBadgeColor(status) {
  switch (status) {
    case 'confirmed':
      return 'bg-green-100'
    case 'pending':
      return 'bg-yellow-100'
    default:
      return 'bg-gray-100'
  }
}

function getStatusTextColor(status) {
  switch (status) {
    case 'confirmed':
      return 'text-green-800'
    case 'pending':
      return 'text-yellow-800'
    default:
      return 'text-gray-800'
  }
}
</script>

// CLAUDE-CHECKPOINT
