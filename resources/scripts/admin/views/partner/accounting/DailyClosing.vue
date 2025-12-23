<template>
  <BasePage>
    <BasePageHeader :title="$t('partner.accounting.daily_closing')">
      <template #actions>
        <BaseButton
          variant="primary-outline"
          @click="showCloseForm = !showCloseForm"
          :disabled="!selectedCompanyId"
        >
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="LockClosedIcon" />
          </template>
          {{ $t('settings.period_lock.close_day') }}
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

    <!-- Close Day Form -->
    <div
      v-if="showCloseForm && selectedCompanyId"
      class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200"
    >
      <h3 class="text-lg font-medium text-gray-900 mb-4">
        {{ $t('settings.period_lock.close_day') }}
      </h3>

      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <BaseInputGroup :label="$t('settings.period_lock.date')">
          <BaseDatePicker
            v-model="closeForm.date"
            :calendar-button="true"
            calendar-button-icon="CalendarDaysIcon"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('settings.period_lock.type')">
          <BaseMultiselect
            v-model="closeForm.type"
            :options="closingTypes"
            :searchable="false"
            track-by="value"
            label="label"
            value-prop="value"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('settings.period_lock.notes')">
          <BaseInput
            v-model="closeForm.notes"
            :placeholder="$t('settings.period_lock.notes_placeholder')"
          />
        </BaseInputGroup>
      </div>

      <div class="mt-4 flex justify-end gap-3">
        <BaseButton variant="gray" @click="cancelCloseForm">
          {{ $t('general.cancel') }}
        </BaseButton>
        <BaseButton
          variant="primary"
          :loading="isSubmitting"
          @click="submitCloseDay"
        >
          {{ $t('settings.period_lock.confirm_close') }}
        </BaseButton>
      </div>
    </div>

    <!-- Loading state -->
    <div v-if="isLoading" class="flex justify-center py-12">
      <BaseSpinner />
    </div>

    <!-- Daily Closings Table -->
    <BaseTable
      v-else-if="selectedCompanyId"
      ref="table"
      class="mt-6"
      :show-filter="false"
      :data="fetchData"
      :columns="closingColumns"
    >
      <template #cell-date="{ row }">
        <span class="font-medium text-gray-900">
          {{ formatDate(row.data.date) }}
        </span>
      </template>

      <template #cell-type="{ row }">
        <BaseBadge
          :bg-color="getTypeBadgeColor(row.data.type)"
          :text-color="getTypeTextColor(row.data.type)"
        >
          {{ $t(`settings.period_lock.type_${row.data.type}`) }}
        </BaseBadge>
      </template>

      <template #cell-closed_by="{ row }">
        <span v-if="row.data.closed_by">
          {{ row.data.closed_by.name }}
        </span>
        <span v-else class="text-gray-400">-</span>
      </template>

      <template #cell-closed_at="{ row }">
        {{ formatDateTime(row.data.closed_at) }}
      </template>

      <template #cell-actions="{ row }">
        <BaseDropdown>
          <template #activator>
            <div class="inline-block cursor-pointer">
              <BaseIcon name="EllipsisHorizontalIcon" class="text-gray-500" />
            </div>
          </template>

          <BaseDropdownItem @click="onUnlockDay(row.data)">
            <BaseIcon name="LockOpenIcon" class="mr-3 text-gray-600" />
            {{ $t('settings.period_lock.unlock_day') }}
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
import { useDialogStore } from '@/scripts/stores/dialog'
import axios from 'axios'

const { t } = useI18n()
const consoleStore = useConsoleStore()
const dialogStore = useDialogStore()

// State
const selectedCompanyId = ref(null)
const table = ref(null)
const showCloseForm = ref(false)
const isSubmitting = ref(false)
const isLoading = ref(false)

const closeForm = reactive({
  date: new Date().toISOString().split('T')[0],
  type: 'all',
  notes: '',
})

// Computed
const companies = computed(() => {
  return consoleStore.managedCompanies || []
})

const closingTypes = computed(() => [
  { value: 'all', label: t('settings.period_lock.type_all') },
  { value: 'invoices', label: t('settings.period_lock.type_invoices') },
  { value: 'cash', label: t('settings.period_lock.type_cash') },
])

const closingColumns = computed(() => [
  {
    key: 'date',
    label: t('settings.period_lock.date'),
    thClass: 'extra',
    tdClass: 'font-medium text-gray-900',
  },
  {
    key: 'type',
    label: t('settings.period_lock.type'),
    tdClass: 'font-medium text-gray-900',
  },
  {
    key: 'closed_by',
    label: t('settings.period_lock.closed_by'),
    tdClass: 'font-medium text-gray-900',
  },
  {
    key: 'closed_at',
    label: t('settings.period_lock.closed_at'),
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

function getTypeBadgeColor(type) {
  switch (type) {
    case 'all':
      return 'bg-red-100'
    case 'invoices':
      return 'bg-blue-100'
    case 'cash':
      return 'bg-green-100'
    default:
      return 'bg-gray-100'
  }
}

function getTypeTextColor(type) {
  switch (type) {
    case 'all':
      return 'text-red-800'
    case 'invoices':
      return 'text-blue-800'
    case 'cash':
      return 'text-green-800'
    default:
      return 'text-gray-800'
  }
}

async function fetchData({ page, filter, sort }) {
  if (!selectedCompanyId.value) {
    return { data: [], pagination: { totalPages: 1, currentPage: 1 } }
  }

  isLoading.value = true
  try {
    const response = await axios.get(`/partner/companies/${selectedCompanyId.value}/daily-closings`, {
      params: {
        orderByField: sort.fieldName || 'date',
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
    console.error('Failed to fetch daily closings:', error)
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

function cancelCloseForm() {
  showCloseForm.value = false
  closeForm.date = new Date().toISOString().split('T')[0]
  closeForm.type = 'all'
  closeForm.notes = ''
}

async function submitCloseDay() {
  if (!selectedCompanyId.value) return

  isSubmitting.value = true

  try {
    await axios.post(`/partner/companies/${selectedCompanyId.value}/daily-closings`, {
      date: closeForm.date,
      type: closeForm.type,
      notes: closeForm.notes,
    })

    cancelCloseForm()
    table.value && table.value.refresh()
  } catch (error) {
    console.error('Failed to create daily closing:', error)
  } finally {
    isSubmitting.value = false
  }
}

function onUnlockDay(closing) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('settings.period_lock.unlock_day_confirm', {
        date: formatDate(closing.date),
      }),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then(async (res) => {
      if (res) {
        try {
          await axios.delete(`/partner/companies/${selectedCompanyId.value}/daily-closings/${closing.id}`)
          table.value && table.value.refresh()
        } catch (error) {
          console.error('Failed to delete daily closing:', error)
        }
      }
    })
}
</script>

// CLAUDE-CHECKPOINT
