<template>
  <BasePage>
    <!-- Page Header -->
    <BasePageHeader :title="$t('payroll.employees')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('payroll.payroll')" to="/admin/payroll" />
        <BaseBreadcrumbItem :title="$t('payroll.employees')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton
          v-show="totalEmployees"
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
          @click="$router.push('/admin/payroll/employees/create')"
        >
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ $t('payroll.add_employee') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <BaseFilterWrapper :show="showFilters" class="mt-5" @clear="clearFilter">
      <BaseInputGroup :label="$t('payroll.department')">
        <BaseMultiselect
          v-model="filters.department"
          :options="departments"
          searchable
          :placeholder="$t('payroll.select_department')"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('payroll.employment_type')">
        <BaseMultiselect
          v-model="filters.employment_type"
          :options="employmentTypes"
          searchable
          :placeholder="$t('payroll.select_employment_type')"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('payroll.status')">
        <BaseMultiselect
          v-model="filters.is_active"
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
      :title="$t('payroll.no_employees')"
      :description="$t('payroll.no_employees_description')"
    >
      <template #actions>
        <BaseButton
          variant="primary-outline"
          @click="$router.push('/admin/payroll/employees/create')"
        >
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ $t('payroll.add_first_employee') }}
        </BaseButton>
      </template>
    </BaseEmptyPlaceholder>

    <div v-show="!showEmptyScreen" class="relative table-container">
      <BaseTable
        ref="tableComponent"
        :data="fetchData"
        :columns="employeeColumns"
        class="mt-3"
      >
        <template #cell-name="{ row }">
          <router-link
            :to="{ path: `/admin/payroll/employees/${row.data.id}/edit` }"
            class="font-medium text-primary-500"
          >
            {{ row.data.first_name }} {{ row.data.last_name }}
          </router-link>
        </template>

        <template #cell-employee_number="{ row }">
          {{ row.data.employee_number }}
        </template>

        <template #cell-department="{ row }">
          {{ row.data.department || '-' }}
        </template>

        <template #cell-position="{ row }">
          {{ row.data.position || '-' }}
        </template>

        <template #cell-employment_type="{ row }">
          <span class="capitalize">
            {{ $t(`payroll.employment_types.${row.data.employment_type}`) }}
          </span>
        </template>

        <template #cell-base_salary="{ row }">
          <BaseFormatMoney
            :amount="row.data.base_salary_amount"
            :currency="row.data.currency"
          />
        </template>

        <template #cell-status="{ row }">
          <span
            :class="row.data.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
            class="px-2 py-1 text-xs font-semibold rounded-full"
          >
            {{ row.data.is_active ? $t('general.active') : $t('general.inactive') }}
          </span>
        </template>

        <template #cell-actions="{ row }">
          <BaseDropdown>
            <template #activator>
              <BaseIcon name="DotsHorizontalIcon" class="h-5 text-gray-500" />
            </template>

            <BaseDropdownItem @click="editEmployee(row.data.id)">
              <BaseIcon name="PencilIcon" class="h-5 mr-3 text-gray-600" />
              {{ $t('general.edit') }}
            </BaseDropdownItem>

            <BaseDropdownItem
              v-if="row.data.is_active"
              @click="terminateEmployee(row.data.id)"
            >
              <BaseIcon name="XCircleIcon" class="h-5 mr-3 text-gray-600" />
              {{ $t('payroll.terminate') }}
            </BaseDropdownItem>

            <BaseDropdownItem
              v-if="!row.data.is_active && !row.data.deleted_at"
              @click="reactivateEmployee(row.data.id)"
            >
              <BaseIcon name="CheckCircleIcon" class="h-5 mr-3 text-gray-600" />
              {{ $t('payroll.reactivate') }}
            </BaseDropdownItem>

            <BaseDropdownItem @click="deleteEmployee(row.data.id)">
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
let totalEmployees = ref(0)
let departments = ref([])
let tableComponent = ref(null)

const filters = reactive({
  department: '',
  employment_type: '',
  is_active: '',
})

const employmentTypes = ['full_time', 'part_time', 'contract']

const statusOptions = [
  { value: '1', label: t('general.active') },
  { value: '0', label: t('general.inactive') },
]

const showEmptyScreen = computed(() => {
  return !totalEmployees.value && !isFetchingInitialData.value
})

const employeeColumns = computed(() => {
  return [
    {
      key: 'employee_number',
      label: t('payroll.employee_number'),
      thClass: 'extra',
      tdClass: 'font-medium text-gray-900',
    },
    {
      key: 'name',
      label: t('payroll.name'),
      thClass: 'extra',
      tdClass: 'font-medium text-primary-500 cursor-pointer',
    },
    {
      key: 'department',
      label: t('payroll.department'),
    },
    {
      key: 'position',
      label: t('payroll.position'),
    },
    {
      key: 'employment_type',
      label: t('payroll.employment_type'),
    },
    {
      key: 'base_salary',
      label: t('payroll.base_salary'),
    },
    {
      key: 'status',
      label: t('payroll.status'),
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
  await loadDepartments()
})

async function loadDepartments() {
  try {
    const response = await axios.get('/api/v1/admin/payroll/employees/departments')
    if (response.data) {
      departments.value = response.data.departments || []
    }
  } catch (error) {
    console.error('Error loading departments:', error)
  }
}

async function fetchData({ page, filter, sort }) {
  let data = {
    ...filters,
    orderByField: sort.fieldName || 'created_at',
    orderBy: sort.order || 'desc',
    page,
  }

  isFetchingInitialData.value = true

  try {
    const response = await axios.get('/api/v1/admin/payroll/employees', { params: data })
    totalEmployees.value = response.data.meta.total || 0
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
    console.error('Error fetching employees:', error)
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
  filters.department = ''
  filters.employment_type = ''
  filters.is_active = ''
}

function toggleFilter() {
  if (showFilters.value) {
    clearFilter()
  }
  showFilters.value = !showFilters.value
}

function editEmployee(id) {
  router.push(`/admin/payroll/employees/${id}/edit`)
}

function terminateEmployee(id) {
  dialogStore
    .openDialog({
      title: t('payroll.terminate_employee'),
      message: t('payroll.terminate_employee_confirm'),
      yesLabel: t('general.yes'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      size: 'sm',
      hideNoButton: false,
    })
    .then(async (res) => {
      if (res) {
        try {
          await axios.post(`/api/v1/admin/payroll/employees/${id}/terminate`)
          notificationStore.showNotification({
            type: 'success',
            message: t('payroll.employee_terminated'),
          })
          refreshTable()
        } catch (error) {
          console.error('Error terminating employee:', error)
          notificationStore.showNotification({
            type: 'error',
            message: t('general.something_went_wrong'),
          })
        }
      }
    })
}

function reactivateEmployee(id) {
  dialogStore
    .openDialog({
      title: t('payroll.reactivate_employee'),
      message: t('payroll.reactivate_employee_confirm'),
      yesLabel: t('general.yes'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      size: 'sm',
      hideNoButton: false,
    })
    .then(async (res) => {
      if (res) {
        try {
          // Reactivate by updating is_active and clearing termination_date
          await axios.put(`/api/v1/admin/payroll/employees/${id}`, {
            is_active: true,
            termination_date: null,
          })
          notificationStore.showNotification({
            type: 'success',
            message: t('payroll.employee_reactivated'),
          })
          refreshTable()
        } catch (error) {
          console.error('Error reactivating employee:', error)
          notificationStore.showNotification({
            type: 'error',
            message: t('general.something_went_wrong'),
          })
        }
      }
    })
}

function deleteEmployee(id) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('payroll.confirm_delete_employee'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      size: 'sm',
      hideNoButton: false,
    })
    .then(async (res) => {
      if (res) {
        try {
          await axios.delete(`/api/v1/admin/payroll/employees/${id}`)
          notificationStore.showNotification({
            type: 'success',
            message: t('payroll.employee_deleted'),
          })
          refreshTable()
        } catch (error) {
          console.error('Error deleting employee:', error)
          notificationStore.showNotification({
            type: 'error',
            message: t('general.something_went_wrong'),
          })
        }
      }
    })
}
</script>

// LLM-CHECKPOINT
