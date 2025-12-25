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
          class="ml-4"
        />

        <BaseButton
          v-if="userStore.hasAbilities(abilities.CREATE_SUPPLIER)"
          variant="primary"
          class="ml-4"
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
      <BaseInputGroup :label="$t('suppliers.name')">
        <BaseInput v-model="filters.search">
          <template #left="slotProps">
            <BaseIcon name="MagnifyingGlassIcon" :class="slotProps.class" />
          </template>
        </BaseInput>
      </BaseInputGroup>
    </BaseFilterWrapper>

    <BaseEmptyPlaceholder
      v-if="!suppliersStore.supplierTotalCount && !suppliersStore.isFetchingList"
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

    <div v-else class="relative table-container">
      <BaseTable
        ref="tableComponent"
        :data="suppliersStore.suppliers"
        :columns="columns"
        :meta="{ total: suppliersStore.supplierTotalCount }"
        :loading="suppliersStore.isFetchingList"
        @get-data="fetchData"
      >
        <template #cell-name="{ row }">
          <router-link
            :to="{ path: `/admin/suppliers/${row.data.id}/view` }"
            class="font-medium text-primary-500"
          >
            {{ row.data.name }}
          </router-link>
        </template>

        <template #cell-email="{ row }">
          {{ row.data.email || '-' }}
        </template>

        <template #cell-tax_id="{ row }">
          {{ row.data.tax_id || '-' }}
        </template>

        <template v-if="hasAtleastOneAbility()" #cell-actions="{ row }">
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
import { ref, reactive, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import abilities from '@/scripts/admin/stub/abilities'
import { useSuppliersStore } from '@/scripts/admin/stores/suppliers'
import { useUserStore } from '@/scripts/admin/stores/user'
import SupplierDropdown from '@/scripts/admin/components/dropdowns/SupplierIndexDropdown.vue'
import ExportButton from '@/scripts/admin/components/ExportButton.vue'

const { t } = useI18n()
const suppliersStore = useSuppliersStore()
const userStore = useUserStore()

const showFilters = ref(false)
const tableComponent = ref(null)

const filters = reactive({
  search: '',
  page: 1,
  limit: 10,
  orderByField: undefined,
  orderBy: undefined,
})

const columns = computed(() => {
  return [
    { key: 'name', label: t('suppliers.name') },
    { key: 'email', label: t('suppliers.email') },
    { key: 'tax_id', label: t('suppliers.tax_id') },
    {
      key: 'actions',
      label: '',
      sortable: false,
      tdClass: 'text-right text-sm font-medium pl-0',
      thClass: 'pl-0',
    },
  ]
})

function fetchData(params) {
  filters.page = params?.page ?? filters.page
  filters.limit = params?.limit ?? filters.limit

  if (params?.orderByField) {
    filters.orderByField = params.orderByField
    filters.orderBy = params.orderBy
  }

  const query = {
    search: filters.search,
    page: filters.page,
    limit: filters.limit,
    orderByField: filters.orderByField,
    orderBy: filters.orderBy,
  }
  suppliersStore.fetchSuppliers(query)
}

function clearFilter() {
  filters.search = ''
  fetchData()
}

function toggleFilter() {
  if (showFilters.value) {
    clearFilter()
  }

  showFilters.value = !showFilters.value
}

function refreshTable() {
  fetchData()
}

function hasAtleastOneAbility() {
  return userStore.hasAbilities([
    abilities.DELETE_SUPPLIER,
    abilities.EDIT_SUPPLIER,
    abilities.VIEW_SUPPLIER,
  ])
}

onMounted(() => {
  fetchData()
})
</script>
// CLAUDE-CHECKPOINT: Added export button to suppliers index view
