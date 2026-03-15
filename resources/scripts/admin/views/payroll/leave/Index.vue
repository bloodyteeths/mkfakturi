<template>
  <BasePage>
    <!-- Page Header -->
    <BasePageHeader :title="$t('payroll.leave_requests')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('payroll.payroll')" to="/admin/payroll" />
        <BaseBreadcrumbItem :title="$t('payroll.leave_requests')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton
          v-show="totalRequests"
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
         
          variant="primary"
          @click="$router.push('/admin/payroll/leave/create')"
        >
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ $t('payroll.new_leave_request') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <BaseFilterWrapper :show="showFilters" class="mt-5" @clear="clearFilter">
      <BaseInputGroup :label="$t('payroll.employee')">
        <BaseMultiselect
          v-model="filters.employee_id"
          :options="employees"
          value-prop="id"
          label="full_name"
          searchable
          :placeholder="$t('payroll.select_employee')"
        >
          <template #option="{ option }">
            {{ option.first_name }} {{ option.last_name }}
          </template>
          <template #singlelabel="{ value }">
            {{ value.first_name }} {{ value.last_name }}
          </template>
        </BaseMultiselect>
      </BaseInputGroup>

      <BaseInputGroup :label="$t('payroll.status_label')">
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
      :title="$t('payroll.no_leave_requests')"
      :description="$t('payroll.no_leave_requests_description')"
    >
      <template #actions>
        <BaseButton
          variant="primary-outline"
          @click="$router.push('/admin/payroll/leave/create')"
        >
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ $t('payroll.create_first_leave_request') }}
        </BaseButton>
      </template>
    </BaseEmptyPlaceholder>

    <div v-show="!showEmptyScreen" class="relative table-container">
      <BaseTable
        ref="tableComponent"
        :data="fetchData"
        :columns="requestColumns"
        class="mt-3"
      >
        <template #cell-employee="{ row }">
          <span class="font-medium text-gray-900">
            {{ row.data.employee?.first_name }} {{ row.data.employee?.last_name }}
          </span>
        </template>

        <template #cell-leave_type="{ row }">
          {{ row.data.leave_type?.name || '-' }}
        </template>

        <template #cell-start_date="{ row }">
          {{ formatDate(row.data.start_date) }}
        </template>

        <template #cell-end_date="{ row }">
          {{ formatDate(row.data.end_date) }}
        </template>

        <template #cell-business_days="{ row }">
          {{ row.data.business_days }}
        </template>

        <template #cell-status="{ row }">
          <span
            :class="statusClass(row.data.status)"
            class="px-2 py-1 text-xs font-semibold rounded-full"
          >
            {{ statusLabel(row.data.status) }}
          </span>
        </template>

        <template #cell-actions="{ row }">
          <BaseDropdown>
            <template #activator>
              <BaseIcon name="DotsHorizontalIcon" class="h-5 text-gray-500" />
            </template>

            <BaseDropdownItem @click="viewRequest(row.data.id)">
              <BaseIcon name="EyeIcon" class="h-5 mr-3 text-gray-600" />
              {{ $t('general.view') }}
            </BaseDropdownItem>

            <BaseDropdownItem
              v-if="row.data.status === 'pending'"
              @click="approveRequest(row.data.id)"
            >
              <BaseIcon name="CheckCircleIcon" class="h-5 mr-3 text-green-600" />
              {{ $t('payroll.approve') }}
            </BaseDropdownItem>

            <BaseDropdownItem
              v-if="row.data.status === 'pending'"
              @click="openRejectDialog(row.data.id)"
            >
              <BaseIcon name="XCircleIcon" class="h-5 mr-3 text-red-600" />
              {{ $t('payroll.reject') }}
            </BaseDropdownItem>

            <BaseDropdownItem
              v-if="row.data.status === 'pending'"
              @click="cancelRequest(row.data.id)"
            >
              <BaseIcon name="TrashIcon" class="h-5 mr-3 text-gray-600" />
              {{ $t('general.cancel') }}
            </BaseDropdownItem>
          </BaseDropdown>
        </template>
      </BaseTable>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, computed, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useNotificationStore } from '@/scripts/stores/notification'
import { debouncedWatch } from '@vueuse/core'
import axios from 'axios'

const router = useRouter()
const { t } = useI18n()
const dialogStore = useDialogStore()
const notificationStore = useNotificationStore()

let isFetchingInitialData = ref(true)
let showFilters = ref(false)
let totalRequests = ref(0)
let employees = ref([])
let tableComponent = ref(null)

const filters = reactive({
  employee_id: '',
  status: '',
})

const statusOptions = [
  { value: 'pending', label: t('payroll.leave_status.pending') },
  { value: 'approved', label: t('payroll.leave_status.approved') },
  { value: 'rejected', label: t('payroll.leave_status.rejected') },
  { value: 'cancelled', label: t('payroll.leave_status.cancelled') },
]

const showEmptyScreen = computed(() => {
  return !totalRequests.value && !isFetchingInitialData.value
})

const requestColumns = computed(() => {
  return [
    {
      key: 'employee',
      label: t('payroll.employee'),
      thClass: 'extra',
      tdClass: 'font-medium text-gray-900',
    },
    {
      key: 'leave_type',
      label: t('payroll.leave_type'),
    },
    {
      key: 'start_date',
      label: t('payroll.start_date'),
    },
    {
      key: 'end_date',
      label: t('payroll.end_date'),
    },
    {
      key: 'business_days',
      label: t('payroll.days'),
    },
    {
      key: 'status',
      label: t('payroll.status_label'),
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

onMounted(async () => {
  await loadEmployees()
  setTimeout(() => {
    if (tableComponent.value) {
      tableComponent.value.refresh()
    }
  }, 100)
})

async function loadEmployees() {
  try {
    const response = await axios.get('payroll-employees', { params: { limit: 1000 } })
    if (response.data && response.data.data) {
      employees.value = response.data.data.map(emp => ({
        ...emp,
        full_name: `${emp.first_name} ${emp.last_name}`,
      }))
    }
  } catch (error) {
    console.error('Error loading employees:', error)
  }
}

async function fetchData({ page, filter, sort }) {
  let data = {
    orderByField: sort.fieldName || 'created_at',
    orderBy: sort.order || 'desc',
    page,
  }

  if (filters.employee_id) data.employee_id = filters.employee_id
  if (filters.status) data.status = filters.status

  isFetchingInitialData.value = true

  try {
    const response = await axios.get('leave-requests', { params: data })
    totalRequests.value = response.data.meta.total || 0
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
    console.error('Error fetching leave requests:', error)
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
  filters.employee_id = ''
  filters.status = ''
}

function toggleFilter() {
  if (showFilters.value) {
    clearFilter()
  }
  showFilters.value = !showFilters.value
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  return date.toLocaleDateString('default', { year: 'numeric', month: 'short', day: 'numeric' })
}

function statusClass(status) {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
    cancelled: 'bg-gray-100 text-gray-800',
  }
  return classes[status] || classes.pending
}

function statusLabel(status) {
  const labels = {
    pending: t('payroll.leave_status.pending'),
    approved: t('payroll.leave_status.approved'),
    rejected: t('payroll.leave_status.rejected'),
    cancelled: t('payroll.leave_status.cancelled'),
  }
  return labels[status] || status
}

function viewRequest(id) {
  // Show request details in a dialog
  dialogStore.openDialog({
    title: t('payroll.leave_request_details'),
    message: t('payroll.loading_details'),
    yesLabel: t('general.close'),
    noLabel: '',
    variant: 'primary',
    hideNoButton: true,
  })
}

async function approveRequest(id) {
  dialogStore
    .openDialog({
      title: t('payroll.approve_leave_request'),
      message: t('payroll.approve_leave_confirm'),
      yesLabel: t('general.yes'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
    })
    .then(async (res) => {
      if (res) {
        try {
          await axios.post(`leave-requests/${id}/approve`)
          notificationStore.showNotification({
            type: 'success',
            message: t('payroll.leave_request_approved'),
          })
          refreshTable()
        } catch (error) {
          console.error('Error approving leave request:', error)
          notificationStore.showNotification({
            type: 'error',
            message: error.response?.data?.message || t('general.something_went_wrong'),
          })
        }
      }
    })
}

function openRejectDialog(id) {
  dialogStore
    .openDialog({
      title: t('payroll.reject_leave_request'),
      message: t('payroll.reject_leave_confirm'),
      yesLabel: t('payroll.reject'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
    })
    .then(async (res) => {
      if (res) {
        try {
          await axios.post(`leave-requests/${id}/reject`, {
            rejection_reason: 'Rejected by manager',
          })
          notificationStore.showNotification({
            type: 'success',
            message: t('payroll.leave_request_rejected'),
          })
          refreshTable()
        } catch (error) {
          console.error('Error rejecting leave request:', error)
          notificationStore.showNotification({
            type: 'error',
            message: error.response?.data?.message || t('general.something_went_wrong'),
          })
        }
      }
    })
}

function cancelRequest(id) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('payroll.cancel_leave_confirm'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
    })
    .then(async (res) => {
      if (res) {
        try {
          await axios.delete(`leave-requests/${id}`)
          notificationStore.showNotification({
            type: 'success',
            message: t('payroll.leave_request_cancelled'),
          })
          refreshTable()
        } catch (error) {
          console.error('Error cancelling leave request:', error)
          notificationStore.showNotification({
            type: 'error',
            message: error.response?.data?.message || t('general.something_went_wrong'),
          })
        }
      }
    })
}
</script>

