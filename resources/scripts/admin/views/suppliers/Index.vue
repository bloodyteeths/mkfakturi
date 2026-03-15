<template>
  <BasePage>
    <BasePageHeader :title="$t('suppliers.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('suppliers.title')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton
          v-show="suppliersStore.supplierTotalCount"
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

        <ExportButton
          v-show="suppliersStore.supplierTotalCount"
          type="suppliers"
          :filters="filters"
        />

        <BaseButton
          v-if="userStore.hasAbilities(abilities.CREATE_SUPPLIER)"
          variant="primary"
          @click="$router.push('/admin/suppliers/create')"
        >
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ $t('suppliers.new_supplier') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <BaseFilterWrapper v-show="showFilters" @clear="clearFilter">
      <BaseInputGroup :label="$t('suppliers.name')" class="text-left">
        <BaseInput
          v-model="filters.name"
          type="text"
          name="name"
          autocomplete="off"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('suppliers.contact_name')" class="text-left">
        <BaseInput
          v-model="filters.contact_name"
          type="text"
          name="contact_name"
          autocomplete="off"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('suppliers.phone')" class="text-left">
        <BaseInput
          v-model="filters.phone"
          type="text"
          name="phone"
          autocomplete="off"
        />
      </BaseInputGroup>
    </BaseFilterWrapper>

    <BaseEmptyPlaceholder
      v-if="showEmptyScreen"
      :title="$t('suppliers.no_suppliers')"
      :description="$t('suppliers.empty_description')"
    >
      <template
        v-if="userStore.hasAbilities(abilities.CREATE_SUPPLIER)"
        #actions
      >
        <BaseButton
          variant="primary-outline"
          @click="$router.push('/admin/suppliers/create')"
        >
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ $t('suppliers.add_new_supplier') }}
        </BaseButton>
      </template>
    </BaseEmptyPlaceholder>

    <div v-show="!showEmptyScreen" class="relative table-container">
      <div class="relative flex items-center justify-end h-5">
        <BaseDropdown v-if="suppliersStore.selectedSupplierIds.length">
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
          <BaseDropdownItem @click="removeMultipleSuppliers">
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
              v-model="selectAllFieldStatus"
              variant="primary"
              @change="suppliersStore.selectSuppliers(
                selectAllFieldStatus
                  ? suppliersStore.suppliers.map(s => s.id)
                  : []
              )"
            />
          </div>
        </template>

        <template #cell-status="{ row }">
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
            :to="{ path: `/admin/suppliers/${row.data.id}/view` }"
            class="font-medium text-primary-500"
          >
            {{ row.data.name }}
            <span v-if="row.data.contact_name" class="block text-xs text-gray-400">
              {{ row.data.contact_name }}
            </span>
          </router-link>
        </template>

        <template #cell-phone="{ row }">
          {{ row.data.phone || '-' }}
        </template>

        <template #cell-email="{ row }">
          {{ row.data.email || '-' }}
        </template>

        <template #cell-tax_id="{ row }">
          {{ row.data.tax_id || '-' }}
        </template>

        <template #cell-created_at="{ row }">
          <span>{{ row.data.formatted_created_at }}</span>
        </template>

        <template v-if="hasAtLeastOneAbility()" #cell-actions="{ row }">
          <SupplierDropdown
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
import { debouncedWatch } from '@vueuse/core'
import { ref, reactive, computed, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import abilities from '@/scripts/admin/stub/abilities'
import { useSuppliersStore } from '@/scripts/admin/stores/suppliers'
import { useUserStore } from '@/scripts/admin/stores/user'
import { useDialogStore } from '@/scripts/stores/dialog'
import SupplierDropdown from '@/scripts/admin/components/dropdowns/SupplierIndexDropdown.vue'
import ExportButton from '@/scripts/admin/components/ExportButton.vue'

const { t } = useI18n()
const suppliersStore = useSuppliersStore()
const userStore = useUserStore()
const dialogStore = useDialogStore()

const showFilters = ref(false)
const tableComponent = ref(null)
let isFetchingInitialData = ref(true)

const filters = reactive({
  name: '',
  contact_name: '',
  phone: '',
})

const showEmptyScreen = computed(
  () => !suppliersStore.supplierTotalCount && !isFetchingInitialData.value
)

const selectField = computed({
  get: () => suppliersStore.selectedSupplierIds,
  set: (value) => {
    return suppliersStore.selectSuppliers(value)
  },
})

const selectAllFieldStatus = computed({
  get: () => suppliersStore.selectAllField,
  set: (value) => {
    return suppliersStore.setSelectAllState(value)
  },
})

const columns = computed(() => {
  return [
    {
      key: 'status',
      thClass: 'extra w-10 pr-0',
      sortable: false,
      tdClass: 'font-medium text-gray-900 pr-0',
    },
    {
      key: 'name',
      label: t('suppliers.name'),
      thClass: 'extra',
      tdClass: 'font-medium text-gray-900',
    },
    { key: 'phone', label: t('suppliers.phone') },
    { key: 'email', label: t('suppliers.email') },
    { key: 'tax_id', label: t('suppliers.tax_id') },
    { key: 'created_at', label: t('suppliers.added_on') },
    {
      key: 'actions',
      label: '',
      sortable: false,
      tdClass: 'text-right text-sm font-medium pl-0',
      thClass: 'pl-0',
    },
  ]
})

async function fetchData({ page, filter, sort }) {
  let data = {
    name: filters.name,
    contact_name: filters.contact_name,
    phone: filters.phone,
    orderByField: sort.fieldName || 'created_at',
    orderBy: sort.order || 'desc',
    page,
  }

  isFetchingInitialData.value = true
  let response = await suppliersStore.fetchSuppliers(data)
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

debouncedWatch(
  filters,
  () => {
    suppliersStore.selectSuppliers([])
    refreshTable()
  },
  { debounce: 500 }
)

onUnmounted(() => {
  if (suppliersStore.selectAllField) {
    suppliersStore.selectSuppliers([])
  }
})

function clearFilter() {
  filters.name = ''
  filters.contact_name = ''
  filters.phone = ''
}

function toggleFilter() {
  if (showFilters.value) {
    clearFilter()
  }

  showFilters.value = !showFilters.value
}

function refreshTable() {
  tableComponent.value && tableComponent.value.refresh()
}

function hasAtLeastOneAbility() {
  return userStore.hasAbilities([
    abilities.DELETE_SUPPLIER,
    abilities.EDIT_SUPPLIER,
    abilities.VIEW_SUPPLIER,
  ])
}

function removeMultipleSuppliers() {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('suppliers.confirm_delete', 2),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then((res) => {
      if (res) {
        suppliersStore.deleteMultipleSuppliers().then((response) => {
          if (response.data) {
            refreshTable()
          }
        })
      }
    })
}
</script>
