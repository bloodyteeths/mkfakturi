<template>
  <BaseSettingCard
    :title="$t('settings.account_review.title')"
    :description="$t('settings.account_review.description')"
  >
    <template #action>
      <div class="flex gap-3">
        <BaseButton
          v-if="selectedRows.length > 0"
          variant="primary"
          :loading="isBulkConfirming"
          @click="onBulkConfirm"
        >
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="CheckCircleIcon" />
          </template>
          {{ $t('settings.account_review.confirm_all') }} ({{ selectedRows.length }})
        </BaseButton>
        <BaseButton variant="gray" @click="refreshTable">
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="ArrowPathIcon" />
          </template>
          {{ $t('general.refresh') }}
        </BaseButton>
      </div>
    </template>

    <!-- Filters -->
    <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
      <div class="grid gap-4 md:grid-cols-3">
        <BaseInputGroup :label="$t('settings.account_review.filter_type')">
          <BaseMultiselect
            v-model="filters.type"
            :options="transactionTypes"
            :searchable="false"
            track-by="value"
            label="label"
            value-prop="value"
            @change="onFilterChange"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('general.from_date')">
          <BaseDatePicker
            v-model="filters.from_date"
            :calendar-button="true"
            calendar-button-icon="CalendarDaysIcon"
            @change="onFilterChange"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('general.to_date')">
          <BaseDatePicker
            v-model="filters.to_date"
            :calendar-button="true"
            calendar-button-icon="CalendarDaysIcon"
            @change="onFilterChange"
          />
        </BaseInputGroup>
      </div>
    </div>

    <!-- Pending Transactions Table -->
    <BaseTable
      ref="table"
      class="mt-6"
      :show-filter="false"
      :data="fetchData"
      :columns="tableColumns"
      :enable-select="true"
      @select="onRowSelect"
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
          {{ $t(`settings.account_review.type_${row.data.type}`) }}
        </BaseBadge>
      </template>

      <template #cell-reference="{ row }">
        <span class="font-medium text-primary-500">
          {{ row.data.reference }}
        </span>
      </template>

      <template #cell-amount="{ row }">
        <div class="text-right font-medium">
          {{ formatMoney(row.data.amount, row.data.currency) }}
        </div>
      </template>

      <template #cell-suggested_debit="{ row }">
        <BaseMultiselect
          v-model="row.data.selected_debit_account_id"
          :options="activeAccounts"
          :searchable="true"
          :create-option="false"
          track-by="id"
          label="display_name"
          value-prop="id"
          :placeholder="$t('settings.account_review.select_account')"
        />
      </template>

      <template #cell-suggested_credit="{ row }">
        <BaseMultiselect
          v-model="row.data.selected_credit_account_id"
          :options="activeAccounts"
          :searchable="true"
          :create-option="false"
          track-by="id"
          label="display_name"
          value-prop="id"
          :placeholder="$t('settings.account_review.select_account')"
        />
      </template>

      <template #cell-actions="{ row }">
        <BaseButton
          variant="primary-outline"
          size="sm"
          :disabled="!canConfirmRow(row.data)"
          @click="onConfirmSingle(row.data)"
        >
          {{ $t('settings.account_review.confirm') }}
        </BaseButton>
      </template>
    </BaseTable>

    <!-- No pending message -->
    <div
      v-if="!isLoading && pendingTransactions.length === 0"
      class="mt-6 text-center py-12"
    >
      <BaseIcon name="CheckCircleIcon" class="mx-auto h-12 w-12 text-green-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">
        {{ $t('settings.account_review.no_pending') }}
      </h3>
    </div>
  </BaseSettingCard>
</template>

<script setup>
import { useAccountStore } from '@/scripts/admin/stores/account'
import { useDialogStore } from '@/scripts/stores/dialog'
import { computed, ref, reactive, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const accountStore = useAccountStore()
const dialogStore = useDialogStore()

const table = ref(null)
const isLoading = ref(false)
const isBulkConfirming = ref(false)
const pendingTransactions = ref([])
const selectedRows = ref([])

const filters = reactive({
  type: null,
  from_date: null,
  to_date: null,
})

const transactionTypes = computed(() => [
  { value: null, label: t('settings.account_review.all_types') },
  { value: 'invoice', label: t('settings.account_review.type_invoice') },
  { value: 'expense', label: t('settings.account_review.type_expense') },
  { value: 'payment', label: t('settings.account_review.type_payment') },
])

const tableColumns = computed(() => [
  {
    key: 'date',
    label: t('general.date'),
    thClass: 'extra',
    tdClass: 'font-medium text-gray-900',
  },
  {
    key: 'type',
    label: t('general.type'),
  },
  {
    key: 'reference',
    label: t('general.reference'),
  },
  {
    key: 'description',
    label: t('general.description'),
    tdClass: 'text-gray-500',
  },
  {
    key: 'amount',
    label: t('general.amount'),
    thClass: 'text-right',
  },
  {
    key: 'suggested_debit',
    label: t('settings.account_review.suggested_debit'),
  },
  {
    key: 'suggested_credit',
    label: t('settings.account_review.suggested_credit'),
  },
  {
    key: 'actions',
    label: '',
    tdClass: 'text-right text-sm font-medium',
    sortable: false,
  },
])

const activeAccounts = computed(() => {
  return accountStore.activeAccounts || []
})

onMounted(async () => {
  // Load accounts for dropdowns
  await accountStore.fetchAccounts({ active: true })
})

async function fetchData({ page, limit, filter }) {
  isLoading.value = true

  try {
    const params = {
      limit: limit || 50,
      ...filters,
    }

    const response = await accountStore.getPendingReview(params)

    // Initialize selected account IDs from suggestions
    pendingTransactions.value = response.data.map(item => ({
      ...item,
      selected_debit_account_id: item.suggested_debit_account_id,
      selected_credit_account_id: item.suggested_credit_account_id,
    }))

    return {
      data: pendingTransactions.value,
      pagination: {
        totalPages: 1,
        currentPage: 1,
        count: response.count,
      },
    }
  } catch (error) {
    console.error('Error fetching pending transactions:', error)
    return {
      data: [],
      pagination: {
        totalPages: 1,
        currentPage: 1,
        count: 0,
      },
    }
  } finally {
    isLoading.value = false
  }
}

function onFilterChange() {
  refreshTable()
}

function refreshTable() {
  if (table.value) {
    table.value.refresh()
  }
}

function onRowSelect(rows) {
  selectedRows.value = rows
}

function canConfirmRow(row) {
  return row.selected_debit_account_id && row.selected_credit_account_id
}

async function onConfirmSingle(row) {
  if (!canConfirmRow(row)) {
    return
  }

  try {
    await accountStore.confirmSuggestion({
      type: row.type,
      id: row.id,
      debit_account_id: row.selected_debit_account_id,
      credit_account_id: row.selected_credit_account_id,
    })

    // Refresh table
    refreshTable()
  } catch (error) {
    console.error('Error confirming account assignment:', error)
  }
}

async function onBulkConfirm() {
  if (selectedRows.value.length === 0) {
    return
  }

  const confirmed = await dialogStore.openConfirmationDialog({
    title: t('settings.account_review.confirm_bulk_title'),
    message: t('settings.account_review.confirm_bulk_message', {
      count: selectedRows.value.length,
    }),
    okText: t('general.confirm'),
    cancelText: t('general.cancel'),
  })

  if (!confirmed) {
    return
  }

  isBulkConfirming.value = true

  try {
    const items = selectedRows.value
      .filter(row => canConfirmRow(row))
      .map(row => ({
        type: row.type,
        id: row.id,
        debit_account_id: row.selected_debit_account_id,
        credit_account_id: row.selected_credit_account_id,
      }))

    await accountStore.bulkConfirm(items)

    // Clear selection and refresh
    selectedRows.value = []
    refreshTable()
  } catch (error) {
    console.error('Error bulk confirming:', error)
  } finally {
    isBulkConfirming.value = false
  }
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  return date.toLocaleDateString('mk-MK', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

function formatMoney(amount, currency) {
  if (!amount) return '-'
  const formatted = new Intl.NumberFormat('mk-MK', {
    style: 'currency',
    currency: currency || 'MKD',
  }).format(amount / 100) // Assuming amount is in cents
  return formatted
}

function getTypeBadgeColor(type) {
  const colors = {
    invoice: 'bg-blue-100',
    expense: 'bg-red-100',
    payment: 'bg-green-100',
  }
  return colors[type] || 'bg-gray-100'
}

function getTypeTextColor(type) {
  const colors = {
    invoice: 'text-blue-800',
    expense: 'text-red-800',
    payment: 'text-green-800',
  }
  return colors[type] || 'text-gray-800'
}
</script>
// CLAUDE-CHECKPOINT
