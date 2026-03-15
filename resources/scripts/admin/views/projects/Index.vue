<template>
  <BasePage>
    <BasePageHeader :title="$t('projects.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('projects.title')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton
          v-show="projectStore.totalProjects"
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
          v-if="userStore.hasAbilities(abilities.CREATE_PROJECT)"
          variant="primary"
          @click="$router.push('/admin/projects/create')"
        >
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ $t('projects.new_project') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <BaseFilterWrapper v-show="showFilters" @clear="clearFilter">
      <BaseInputGroup :label="$t('projects.name')">
        <BaseInput v-model="filters.search">
          <template #left="slotProps">
            <BaseIcon name="MagnifyingGlassIcon" :class="slotProps.class" />
          </template>
        </BaseInput>
      </BaseInputGroup>

      <BaseInputGroup :label="$t('projects.status')">
        <BaseMultiselect
          v-model="filters.status"
          :options="statusOptions"
          label="label"
          value-prop="value"
          :placeholder="$t('projects.select_status')"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('projects.customer')">
        <BaseCustomerSelectInput
          v-model="filters.customer_id"
          :placeholder="$t('customers.type_or_click')"
          value-prop="id"
          label="name"
        />
      </BaseInputGroup>
    </BaseFilterWrapper>

    <BaseEmptyPlaceholder
      v-show="showEmptyScreen"
      :title="$t('projects.no_projects')"
      :description="$t('projects.empty_description')"
    >
      <template
        v-if="userStore.hasAbilities(abilities.CREATE_PROJECT)"
        #actions
      >
        <BaseButton
          variant="primary-outline"
          @click="$router.push('/admin/projects/create')"
        >
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ $t('projects.add_new_project') }}
        </BaseButton>
      </template>
    </BaseEmptyPlaceholder>

    <div v-show="!showEmptyScreen" class="relative table-container">
      <div class="relative flex items-center justify-end h-5">
        <BaseDropdown v-if="selectedProjectIds.length">
          <template #activator>
            <span
              class="
                flex
                text-sm
                font-medium
                cursor-pointer
                select-none
                text-primary-400
              "
            >
              {{ $t('general.actions') }}
              <BaseIcon name="ChevronDownIcon" />
            </span>
          </template>
          <BaseDropdownItem @click="removeMultipleProjects">
            <BaseIcon name="TrashIcon" class="mr-3 text-gray-600" />
            {{ $t('general.delete') }}
          </BaseDropdownItem>
        </BaseDropdown>
      </div>

      <BaseTable
        ref="tableComponent"
        class="mt-3"
        :data="fetchData"
        :columns="columns"
      >
        <template #header>
          <div class="absolute z-10 items-center left-6 top-2.5 select-none">
            <BaseCheckbox
              v-model="selectAllField"
              variant="primary"
              @change="toggleSelectAll"
            />
          </div>
        </template>

        <template #cell-checkbox="{ row }">
          <div class="relative block">
            <BaseCheckbox
              :id="row.data.id"
              v-model="selectField"
              :value="row.data.id"
              variant="primary"
            />
          </div>
        </template>

        <template #cell-name="{ row }">
          <router-link
            :to="{ path: `/admin/projects/${row.data.id}/view` }"
            class="font-medium text-primary-500"
          >
            {{ row.data.name }}
          </router-link>
          <p v-if="row.data.code" class="text-xs text-gray-500">{{ row.data.code }}</p>
        </template>

        <template #cell-customer="{ row }">
          {{ row.data.customer?.name || '-' }}
        </template>

        <template #cell-status="{ row }">
          <BaseBadge
            :bg-color="getStatusColor(row.data.status)"
            :content-loading="false"
          >
            {{ $t(`projects.statuses.${row.data.status}`) }}
          </BaseBadge>
        </template>

        <template #cell-budget_amount="{ row }">
          <BaseFormatMoney
            v-if="row.data.budget_amount"
            :amount="row.data.budget_amount"
            :currency="row.data.currency"
          />
          <span v-else>-</span>
        </template>

        <template #cell-dates="{ row }">
          <div v-if="row.data.start_date || row.data.end_date" class="text-sm">
            <span v-if="row.data.formatted_start_date">{{ row.data.formatted_start_date }}</span>
            <span v-if="row.data.formatted_start_date && row.data.formatted_end_date"> - </span>
            <span v-if="row.data.formatted_end_date">{{ row.data.formatted_end_date }}</span>
          </div>
          <span v-else>-</span>
        </template>

        <template v-if="hasAtLeastOneAbility()" #cell-actions="{ row }">
          <ProjectDropdown
            :row="row.data"
            :table="tableComponent"
            :load-data="refreshTable"
          />
        </template>
      </BaseTable>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, onUnmounted } from 'vue'
import { debouncedWatch } from '@vueuse/core'
import { useI18n } from 'vue-i18n'
import abilities from '@/scripts/admin/stub/abilities'
import { useProjectStore } from '@/scripts/admin/stores/project'
import { useUserStore } from '@/scripts/admin/stores/user'
import { useDialogStore } from '@/scripts/stores/dialog'
import ProjectDropdown from '@/scripts/admin/components/dropdowns/ProjectIndexDropdown.vue'

const { t } = useI18n()
const projectStore = useProjectStore()
const userStore = useUserStore()
const dialogStore = useDialogStore()

const showFilters = ref(false)
const tableComponent = ref(null)
const isFetchingInitialData = ref(true)
const selectedProjectIds = ref([])
const selectAllField = ref(false)

const filters = reactive({
  search: '',
  status: null,
  customer_id: null,
})

const showEmptyScreen = computed(
  () => !projectStore.totalProjects && !isFetchingInitialData.value
)

const selectField = computed({
  get: () => selectedProjectIds.value,
  set: (value) => {
    selectedProjectIds.value = value
    selectAllField.value = value.length === projectStore.projects.length && value.length > 0
  },
})

const statusOptions = computed(() => [
  { value: 'open', label: t('projects.statuses.open') },
  { value: 'in_progress', label: t('projects.statuses.in_progress') },
  { value: 'completed', label: t('projects.statuses.completed') },
  { value: 'on_hold', label: t('projects.statuses.on_hold') },
  { value: 'cancelled', label: t('projects.statuses.cancelled') },
])

const columns = computed(() => {
  return [
    {
      key: 'checkbox',
      thClass: 'extra w-10 pr-0',
      sortable: false,
      tdClass: 'font-medium text-gray-900 pr-0',
    },
    { key: 'name', label: t('projects.name'), thClass: 'extra' },
    { key: 'customer', label: t('projects.customer'), sortable: false },
    { key: 'status', label: t('projects.status') },
    { key: 'budget_amount', label: t('projects.budget') },
    { key: 'dates', label: t('projects.dates'), sortable: false },
    {
      key: 'actions',
      label: '',
      sortable: false,
      tdClass: 'text-right text-sm font-medium pl-0',
      thClass: 'pl-0',
    },
  ]
})

function getStatusColor(status) {
  const colors = {
    open: '#3B82F6',
    in_progress: '#F59E0B',
    completed: '#10B981',
    on_hold: '#6B7280',
    cancelled: '#EF4444',
  }
  return colors[status] || '#6B7280'
}

function toggleSelectAll() {
  if (selectedProjectIds.value.length === projectStore.projects.length) {
    selectedProjectIds.value = []
    selectAllField.value = false
  } else {
    selectedProjectIds.value = projectStore.projects.map(p => p.id)
    selectAllField.value = true
  }
}

debouncedWatch(
  filters,
  () => {
    selectedProjectIds.value = []
    selectAllField.value = false
    refreshTable()
  },
  { debounce: 500 }
)

onUnmounted(() => {
  selectedProjectIds.value = []
  selectAllField.value = false
})

async function fetchData({ page, filter, sort }) {
  let data = {
    search: filters.search,
    status: filters.status,
    customer_id: filters.customer_id,
    orderByField: sort.fieldName || 'created_at',
    orderBy: sort.order || 'desc',
    page,
  }

  isFetchingInitialData.value = true
  let response = await projectStore.fetchProjects(data)
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
}

function refreshTable() {
  tableComponent.value && tableComponent.value.refresh()
}

function clearFilter() {
  filters.search = ''
  filters.status = null
  filters.customer_id = null
}

function toggleFilter() {
  if (showFilters.value) {
    clearFilter()
  }

  showFilters.value = !showFilters.value
}

function hasAtLeastOneAbility() {
  return userStore.hasAbilities([
    abilities.DELETE_PROJECT,
    abilities.EDIT_PROJECT,
    abilities.VIEW_PROJECT,
  ])
}

function removeMultipleProjects() {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('projects.confirm_delete'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then((res) => {
      if (res) {
        projectStore.deleteProjects(selectedProjectIds.value).then(() => {
          selectedProjectIds.value = []
          selectAllField.value = false
          refreshTable()
        })
      }
    })
}
</script>
