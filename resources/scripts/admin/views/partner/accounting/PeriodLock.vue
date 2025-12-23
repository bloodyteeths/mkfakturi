<template>
  <BasePage>
    <BasePageHeader :title="$t('partner.accounting.period_lock')">
      <template #actions>
        <BaseButton
          variant="primary-outline"
          @click="showLockForm = !showLockForm"
          :disabled="!selectedCompanyId"
        >
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="LockClosedIcon" />
          </template>
          {{ $t('settings.period_lock.lock_period') }}
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

    <!-- Lock Period Form -->
    <div
      v-if="showLockForm && selectedCompanyId"
      class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200"
    >
      <h3 class="text-lg font-medium text-gray-900 mb-4">
        {{ $t('settings.period_lock.lock_period') }}
      </h3>

      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <BaseInputGroup :label="$t('settings.period_lock.period_start')">
          <BaseDatePicker
            v-model="lockForm.period_start"
            :calendar-button="true"
            calendar-button-icon="CalendarDaysIcon"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('settings.period_lock.period_end')">
          <BaseDatePicker
            v-model="lockForm.period_end"
            :calendar-button="true"
            calendar-button-icon="CalendarDaysIcon"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('settings.period_lock.notes')">
          <BaseInput
            v-model="lockForm.notes"
            :placeholder="$t('settings.period_lock.notes_placeholder')"
          />
        </BaseInputGroup>
      </div>

      <div class="mt-4 flex justify-end gap-3">
        <BaseButton variant="gray" @click="cancelLockForm">
          {{ $t('general.cancel') }}
        </BaseButton>
        <BaseButton
          variant="primary"
          :loading="isSubmitting"
          @click="submitLockPeriod"
        >
          {{ $t('settings.period_lock.confirm_lock') }}
        </BaseButton>
      </div>
    </div>

    <!-- Loading state -->
    <div v-if="isLoading" class="flex justify-center py-12">
      <BaseSpinner />
    </div>

    <!-- Period Locks Table -->
    <BaseTable
      v-else-if="selectedCompanyId"
      ref="table"
      class="mt-6"
      :show-filter="false"
      :data="fetchData"
      :columns="lockColumns"
    >
      <template #cell-period_start="{ row }">
        <span class="font-medium text-gray-900">
          {{ formatDate(row.data.period_start) }}
        </span>
      </template>

      <template #cell-period_end="{ row }">
        <span class="font-medium text-gray-900">
          {{ formatDate(row.data.period_end) }}
        </span>
      </template>

      <template #cell-locked_by="{ row }">
        <span v-if="row.data.locked_by">
          {{ row.data.locked_by.name }}
        </span>
        <span v-else class="text-gray-400">-</span>
      </template>

      <template #cell-locked_at="{ row }">
        {{ formatDateTime(row.data.locked_at) }}
      </template>

      <template #cell-actions="{ row }">
        <BaseDropdown>
          <template #activator>
            <div class="inline-block cursor-pointer">
              <BaseIcon name="EllipsisHorizontalIcon" class="text-gray-500" />
            </div>
          </template>

          <BaseDropdownItem @click="onUnlockPeriod(row.data)">
            <BaseIcon name="LockOpenIcon" class="mr-3 text-gray-600" />
            {{ $t('settings.period_lock.unlock_period') }}
          </BaseDropdownItem>
        </BaseDropdown>
      </template>
    </BaseTable>

    <!-- Select company message -->
    <div
      v-else
      class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-12"
    >
      <BaseIcon name="BuildingOfficeIcon" class="h-12 w-12 text-gray-400" />
      <p class="mt-2 text-sm text-gray-500">
        {{ $t('partner.accounting.select_company_to_view') }}
      </p>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import { usePartnerAccountingStore } from '@/scripts/admin/stores/partner-accounting'
import { useDialogStore } from '@/scripts/stores/dialog'
import axios from 'axios'

const { t } = useI18n()
const consoleStore = useConsoleStore()
const partnerAccountingStore = usePartnerAccountingStore()
const dialogStore = useDialogStore()

// State
const selectedCompanyId = ref(null)
const table = ref(null)
const showLockForm = ref(false)
const isSubmitting = ref(false)
const isLoading = ref(false)
const periodLocks = ref([])

// Default to first and last day of previous month
const now = new Date()
const firstDayLastMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1)
const lastDayLastMonth = new Date(now.getFullYear(), now.getMonth(), 0)

const lockForm = reactive({
  period_start: firstDayLastMonth.toISOString().split('T')[0],
  period_end: lastDayLastMonth.toISOString().split('T')[0],
  notes: '',
})

// Computed
const companies = computed(() => {
  return consoleStore.managedCompanies || []
})

const lockColumns = computed(() => [
  {
    key: 'period_start',
    label: t('settings.period_lock.period_start'),
    thClass: 'extra',
    tdClass: 'font-medium text-gray-900',
  },
  {
    key: 'period_end',
    label: t('settings.period_lock.period_end'),
    thClass: 'extra',
    tdClass: 'font-medium text-gray-900',
  },
  {
    key: 'locked_by',
    label: t('settings.period_lock.locked_by'),
    tdClass: 'font-medium text-gray-900',
  },
  {
    key: 'locked_at',
    label: t('settings.period_lock.locked_at'),
    tdClass: 'text-gray-500',
  },
  {
    key: 'notes',
    label: t('settings.period_lock.notes'),
    tdClass: 'text-gray-500',
  },
  {
    key: 'actions',
    label: '',
    tdClass: 'text-right text-sm font-medium',
    sortable: false,
  },
])

// Lifecycle
onMounted(async () => {
  await consoleStore.fetchCompanies()

  // Auto-select first company if available
  if (companies.value.length > 0) {
    selectedCompanyId.value = companies.value[0].id
  }
})

// Watch for company changes
watch(selectedCompanyId, async (newCompanyId) => {
  if (newCompanyId && table.value) {
    table.value.refresh()
  }
})

// Methods
function formatDate(dateStr) {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  return date.toLocaleDateString('mk-MK', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

function formatDateTime(dateTimeStr) {
  if (!dateTimeStr) return '-'
  const date = new Date(dateTimeStr)
  return date.toLocaleString('mk-MK', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

async function fetchData({ page, filter, sort }) {
  if (!selectedCompanyId.value) {
    return { data: [], pagination: { totalPages: 1, currentPage: 1 } }
  }

  isLoading.value = true
  try {
    const response = await axios.get(`/partner/companies/${selectedCompanyId.value}/period-locks`, {
      params: {
        orderByField: sort.fieldName || 'period_start',
        orderBy: sort.order || 'desc',
        page,
      },
    })

    return {
      data: response.data.data || [],
      pagination: {
        totalPages: 1,
        currentPage: 1,
      },
    }
  } catch (error) {
    console.error('Failed to fetch period locks:', error)
    return { data: [], pagination: { totalPages: 1, currentPage: 1 } }
  } finally {
    isLoading.value = false
  }
}

function onCompanyChange() {
  if (table.value) {
    table.value.refresh()
  }
}

function cancelLockForm() {
  showLockForm.value = false
  const now = new Date()
  const firstDayLastMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1)
  const lastDayLastMonth = new Date(now.getFullYear(), now.getMonth(), 0)
  lockForm.period_start = firstDayLastMonth.toISOString().split('T')[0]
  lockForm.period_end = lastDayLastMonth.toISOString().split('T')[0]
  lockForm.notes = ''
}

async function submitLockPeriod() {
  if (!selectedCompanyId.value) return

  isSubmitting.value = true

  try {
    await axios.post(`/partner/companies/${selectedCompanyId.value}/period-locks`, {
      period_start: lockForm.period_start,
      period_end: lockForm.period_end,
      notes: lockForm.notes,
    })

    cancelLockForm()
    table.value && table.value.refresh()
  } catch (error) {
    console.error('Failed to create period lock:', error)
  } finally {
    isSubmitting.value = false
  }
}

function onUnlockPeriod(lock) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('settings.period_lock.unlock_period_confirm'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then(async (res) => {
      if (res) {
        try {
          await axios.delete(`/partner/companies/${selectedCompanyId.value}/period-locks/${lock.id}`)
          table.value && table.value.refresh()
        } catch (error) {
          console.error('Failed to delete period lock:', error)
        }
      }
    })
}
</script>

// CLAUDE-CHECKPOINT
