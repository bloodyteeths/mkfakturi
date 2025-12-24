<template>
  <BasePage>
    <!-- Page Header -->
    <BasePageHeader :title="$t('payroll.payroll_runs')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('payroll.payroll')" to="/admin/payroll" />
        <BaseBreadcrumbItem :title="$t('payroll.payroll_runs')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton
          v-show="totalRuns"
          variant="primary-outline"
          @click="toggleFilter"
        >
          {{ $t('general.filter') }}
          <template #right="slotProps">
            <BaseIcon
              v-if="!showFilters"
              name="FunnelIcon"
              :class="slotProps.class"
            />
            <BaseIcon v-else name="XMarkIcon" :class="slotProps.class" />
          </template>
        </BaseButton>

        <BaseButton
          class="ml-4"
          variant="primary"
          @click="$router.push('/admin/payroll/runs/create')"
        >
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ $t('payroll.new_payroll_run') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <BaseFilterWrapper :show="showFilters" class="mt-5" @clear="clearFilter">
      <BaseInputGroup :label="$t('payroll.year')">
        <BaseInput
          v-model="filters.period_year"
          type="number"
          :placeholder="$t('payroll.select_year')"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('payroll.month')">
        <BaseMultiselect
          v-model="filters.period_month"
          :options="monthOptions"
          value-prop="value"
          label="label"
          :placeholder="$t('payroll.select_month')"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('payroll.status')">
        <BaseMultiselect
          v-model="filters.status"
          :options="statusOptions"
          value-prop="value"
          label="label"
          :placeholder="$t('general.select_a_status')"
        />
      </BaseInputGroup>
    </BaseFilterWrapper>

    <!-- Empty Table Placeholder -->
    <BaseEmptyPlaceholder
      v-show="showEmptyScreen"
      :title="$t('payroll.no_runs')"
      :description="$t('payroll.no_runs_description')"
    >
      <template #actions>
        <BaseButton
          variant="primary-outline"
          @click="$router.push('/admin/payroll/runs/create')"
        >
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ $t('payroll.create_first_run') }}
        </BaseButton>
      </template>
    </BaseEmptyPlaceholder>

    <div v-show="!showEmptyScreen" class="relative table-container">
      <BaseTable
        ref="tableComponent"
        :data="fetchData"
        :columns="runsColumns"
        class="mt-3"
      >
        <template #cell-period="{ row }">
          <router-link
            :to="{ path: `/admin/payroll/runs/${row.data.id}` }"
            class="font-medium text-primary-500"
          >
            {{ formatPeriod(row.data.period_year, row.data.period_month) }}
          </router-link>
        </template>

        <template #cell-status="{ row }">
          <span
            :class="statusClass(row.data.status)"
            class="px-2 py-1 text-xs font-semibold rounded-full"
          >
            {{ $t(`payroll.status.${row.data.status}`) }}
          </span>
        </template>

        <template #cell-total_gross="{ row }">
          <BaseFormatMoney
            :amount="row.data.total_gross"
            :currency="companyStore.selectedCompanyCurrency"
          />
        </template>

        <template #cell-total_net="{ row }">
          <BaseFormatMoney
            :amount="row.data.total_net"
            :currency="companyStore.selectedCompanyCurrency"
          />
        </template>

        <template #cell-actions="{ row }">
          <BaseDropdown>
            <template #activator>
              <BaseIcon name="DotsHorizontalIcon" class="h-5 text-gray-500" />
            </template>

            <BaseDropdownItem @click="viewRun(row.data.id)">
              <BaseIcon name="EyeIcon" class="h-5 mr-3 text-gray-600" />
              {{ $t('general.view') }}
            </BaseDropdownItem>

            <BaseDropdownItem
              v-if="row.data.status === 'draft'"
              @click="calculateRun(row.data.id)"
            >
              <BaseIcon name="CalculatorIcon" class="h-5 mr-3 text-gray-600" />
              {{ $t('payroll.calculate') }}
            </BaseDropdownItem>

            <BaseDropdownItem
              v-if="row.data.status === 'calculated'"
              @click="approveRun(row.data.id)"
            >
              <BaseIcon name="CheckCircleIcon" class="h-5 mr-3 text-gray-600" />
              {{ $t('payroll.approve') }}
            </BaseDropdownItem>

            <BaseDropdownItem
              v-if="row.data.status === 'approved'"
              @click="postRun(row.data.id)"
            >
              <BaseIcon name="DocumentCheckIcon" class="h-5 mr-3 text-gray-600" />
              {{ $t('payroll.post_to_gl') }}
            </BaseDropdownItem>

            <BaseDropdownItem
              v-if="row.data.status === 'posted'"
              @click="markAsPaid(row.data.id)"
            >
              <BaseIcon name="BanknotesIcon" class="h-5 mr-3 text-gray-600" />
              {{ $t('payroll.mark_as_paid') }}
            </BaseDropdownItem>

            <BaseDropdownItem
              v-if="row.data.status === 'posted' || row.data.status === 'paid'"
              @click="generateBankFile(row.data.id)"
            >
              <BaseIcon name="DocumentArrowDownIcon" class="h-5 mr-3 text-gray-600" />
              {{ $t('payroll.generate_bank_file') }}
            </BaseDropdownItem>

            <BaseDropdownItem
              v-if="row.data.status === 'draft'"
              @click="deleteRun(row.data.id)"
            >
              <BaseIcon name="TrashIcon" class="h-5 mr-3 text-gray-600" />
              {{ $t('general.delete') }}
            </BaseDropdownItem>
          </BaseDropdown>
        </template>
      </BaseTable>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useNotificationStore } from '@/scripts/stores/notification'
import { debouncedWatch } from '@vueuse/core'
import axios from 'axios'

const router = useRouter()
const { t } = useI18n()
const companyStore = useCompanyStore()
const dialogStore = useDialogStore()
const notificationStore = useNotificationStore()

let isFetchingInitialData = ref(true)
let showFilters = ref(false)
let totalRuns = ref(0)
let tableComponent = ref(null)

const filters = reactive({
  period_year: '',
  period_month: '',
  status: '',
})

const monthOptions = [
  { value: 1, label: t('months.january') },
  { value: 2, label: t('months.february') },
  { value: 3, label: t('months.march') },
  { value: 4, label: t('months.april') },
  { value: 5, label: t('months.may') },
  { value: 6, label: t('months.june') },
  { value: 7, label: t('months.july') },
  { value: 8, label: t('months.august') },
  { value: 9, label: t('months.september') },
  { value: 10, label: t('months.october') },
  { value: 11, label: t('months.november') },
  { value: 12, label: t('months.december') },
]

const statusOptions = [
  { value: 'draft', label: t('payroll.status.draft') },
  { value: 'calculated', label: t('payroll.status.calculated') },
  { value: 'approved', label: t('payroll.status.approved') },
  { value: 'posted', label: t('payroll.status.posted') },
  { value: 'paid', label: t('payroll.status.paid') },
]

const showEmptyScreen = computed(() => {
  return !totalRuns.value && !isFetchingInitialData.value
})

const runsColumns = computed(() => {
  return [
    {
      key: 'period',
      label: t('payroll.period'),
      thClass: 'extra',
      tdClass: 'font-medium text-primary-500 cursor-pointer',
    },
    {
      key: 'status',
      label: t('payroll.status'),
      thClass: 'extra',
    },
    {
      key: 'total_gross',
      label: t('payroll.total_gross'),
    },
    {
      key: 'total_net',
      label: t('payroll.total_net'),
    },
    {
      key: 'actions',
      label: '',
      sortable: false,
      tdClass: 'text-right text-sm font-medium',
    },
  ]
})

debouncedWatch(
  filters,
  () => {
    setFilters()
  },
  { debounce: 500 }
)

async function fetchData({ page, filter, sort }) {
  let data = {
    ...filters,
    orderByField: sort.fieldName || 'created_at',
    orderBy: sort.order || 'desc',
    page,
  }

  isFetchingInitialData.value = true

  try {
    const response = await axios.get('/api/v1/admin/payroll/runs', { params: data })
    totalRuns.value = response.data.meta.total || 0
    isFetchingInitialData.value = false

    return {
      data: response.data.data,
      pagination: {
        totalPages: response.data.meta.last_page,
        currentPage: page,
        totalCount: response.data.meta.total,
        limit: 10,
      },
    }
  } catch (error) {
    console.error('Error fetching payroll runs:', error)
    isFetchingInitialData.value = false
    return {
      data: [],
      pagination: {
        totalPages: 1,
        currentPage: 1,
        totalCount: 0,
        limit: 10,
      },
    }
  }
}

function refreshTable() {
  tableComponent.value && tableComponent.value.refresh()
}

function setFilters() {
  refreshTable()
}

function clearFilter() {
  filters.period_year = ''
  filters.period_month = ''
  filters.status = ''
}

function toggleFilter() {
  if (showFilters.value) {
    clearFilter()
  }
  showFilters.value = !showFilters.value
}

function formatPeriod(year, month) {
  const monthName = new Date(year, month - 1).toLocaleString('default', { month: 'long' })
  return `${monthName} ${year}`
}

function statusClass(status) {
  const classes = {
    draft: 'bg-gray-100 text-gray-800',
    calculated: 'bg-blue-100 text-blue-800',
    approved: 'bg-green-100 text-green-800',
    posted: 'bg-purple-100 text-purple-800',
    paid: 'bg-primary-100 text-primary-800',
  }
  return classes[status] || classes.draft
}

function viewRun(id) {
  router.push(`/admin/payroll/runs/${id}`)
}

async function calculateRun(id) {
  try {
    await axios.post(`/api/v1/admin/payroll/runs/${id}/calculate`)
    notificationStore.showNotification({
      type: 'success',
      message: t('payroll.run_calculated'),
    })
    refreshTable()
  } catch (error) {
    console.error('Error calculating run:', error)
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('general.something_went_wrong'),
    })
  }
}

async function approveRun(id) {
  dialogStore
    .openDialog({
      title: t('payroll.approve_run'),
      message: t('payroll.approve_run_confirm'),
      yesLabel: t('general.yes'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
    })
    .then(async (res) => {
      if (res) {
        try {
          await axios.post(`/api/v1/admin/payroll/runs/${id}/approve`)
          notificationStore.showNotification({
            type: 'success',
            message: t('payroll.run_approved'),
          })
          refreshTable()
        } catch (error) {
          console.error('Error approving run:', error)
          notificationStore.showNotification({
            type: 'error',
            message: error.response?.data?.message || t('general.something_went_wrong'),
          })
        }
      }
    })
}

async function postRun(id) {
  dialogStore
    .openDialog({
      title: t('payroll.post_to_gl'),
      message: t('payroll.post_to_gl_confirm'),
      yesLabel: t('general.yes'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
    })
    .then(async (res) => {
      if (res) {
        try {
          await axios.post(`/api/v1/admin/payroll/runs/${id}/post`)
          notificationStore.showNotification({
            type: 'success',
            message: t('payroll.run_posted'),
          })
          refreshTable()
        } catch (error) {
          console.error('Error posting run:', error)
          notificationStore.showNotification({
            type: 'error',
            message: error.response?.data?.message || t('general.something_went_wrong'),
          })
        }
      }
    })
}

async function markAsPaid(id) {
  dialogStore
    .openDialog({
      title: t('payroll.mark_as_paid'),
      message: t('payroll.mark_as_paid_confirm'),
      yesLabel: t('general.yes'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
    })
    .then(async (res) => {
      if (res) {
        try {
          await axios.post(`/api/v1/admin/payroll/runs/${id}/mark-as-paid`)
          notificationStore.showNotification({
            type: 'success',
            message: t('payroll.run_marked_as_paid'),
          })
          refreshTable()
        } catch (error) {
          console.error('Error marking run as paid:', error)
          notificationStore.showNotification({
            type: 'error',
            message: error.response?.data?.message || t('general.something_went_wrong'),
          })
        }
      }
    })
}

async function generateBankFile(id) {
  try {
    const response = await axios.post(
      `/api/v1/admin/payroll/runs/${id}/generate-bank-file`,
      {},
      { responseType: 'blob' }
    )

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `payroll_payment_${id}.xml`)
    document.body.appendChild(link)
    link.click()
    link.remove()

    notificationStore.showNotification({
      type: 'success',
      message: t('payroll.bank_file_generated'),
    })
    refreshTable()
  } catch (error) {
    console.error('Error generating bank file:', error)
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('general.something_went_wrong'),
    })
  }
}

function deleteRun(id) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('payroll.confirm_delete_run'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
    })
    .then(async (res) => {
      if (res) {
        try {
          await axios.delete(`/api/v1/admin/payroll/runs/${id}`)
          notificationStore.showNotification({
            type: 'success',
            message: t('payroll.run_deleted'),
          })
          refreshTable()
        } catch (error) {
          console.error('Error deleting run:', error)
          notificationStore.showNotification({
            type: 'error',
            message: error.response?.data?.message || t('general.something_went_wrong'),
          })
        }
      }
    })
}
</script>

// LLM-CHECKPOINT
