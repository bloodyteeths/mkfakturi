<template>
  <BasePage>
    <BasePageHeader :title="$t('stock.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('stock.title')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton
          v-show="warehouseStore.totalWarehouses"
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
          v-if="userStore.hasAbilities(abilities.CREATE_WAREHOUSE)"
          variant="primary"
          class="ml-4"
          @click="$router.push('/admin/stock/warehouses/create')"
        >
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ $t('warehouses.new_warehouse') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Stock Sub-Navigation Tabs -->
    <StockTabNavigation />

    <BaseFilterWrapper v-show="showFilters" @clear="clearFilter">
      <BaseInputGroup :label="$t('warehouses.name')">
        <BaseInput v-model="filters.search">
          <template #left="slotProps">
            <BaseIcon name="MagnifyingGlassIcon" :class="slotProps.class" />
          </template>
        </BaseInput>
      </BaseInputGroup>

      <BaseInputGroup :label="$t('warehouses.status')">
        <BaseMultiselect
          v-model="filters.is_active"
          :options="statusOptions"
          label="label"
          value-prop="value"
          :placeholder="$t('warehouses.select_status')"
          @update:modelValue="fetchData"
        />
      </BaseInputGroup>
    </BaseFilterWrapper>

    <BaseEmptyPlaceholder
      v-if="!warehouseStore.totalWarehouses && !warehouseStore.isLoading"
      :title="$t('warehouses.no_warehouses')"
      :description="$t('warehouses.empty_description')"
    >
      <template
        v-if="userStore.hasAbilities(abilities.CREATE_WAREHOUSE)"
        #actions
      >
        <BaseButton
          variant="primary-outline"
          @click="$router.push('/admin/stock/warehouses/create')"
        >
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ $t('warehouses.add_new_warehouse') }}
        </BaseButton>
      </template>
    </BaseEmptyPlaceholder>

    <div v-else class="relative table-container">
      <BaseTable
        ref="tableComponent"
        :data="warehouseStore.warehouses"
        :columns="columns"
        :meta="{ total: warehouseStore.totalWarehouses }"
        :loading="warehouseStore.isLoading"
        @get-data="fetchData"
      >
        <template #cell-name="{ row }">
          <router-link
            :to="{ path: `/admin/stock/warehouses/${row.data.id}/edit` }"
            class="font-medium text-primary-500"
          >
            {{ row.data.name }}
          </router-link>
          <p v-if="row.data.code" class="text-xs text-gray-500">{{ row.data.code }}</p>
        </template>

        <template #cell-is_default="{ row }">
          <BaseBadge
            v-if="row.data.is_default"
            bg-color="#10B981"
            :content-loading="false"
          >
            {{ $t('warehouses.default') }}
          </BaseBadge>
          <span v-else class="text-gray-400">-</span>
        </template>

        <template #cell-is_active="{ row }">
          <BaseBadge
            :bg-color="row.data.is_active ? '#10B981' : '#EF4444'"
            :content-loading="false"
          >
            {{ row.data.is_active ? $t('general.active') : $t('general.inactive') }}
          </BaseBadge>
        </template>

        <template v-if="hasAtleastOneAbility()" #cell-actions="{ row }">
          <WarehouseIndexDropdown
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
import { useWarehouseStore } from '@/scripts/admin/stores/warehouse'
import { useUserStore } from '@/scripts/admin/stores/user'
import WarehouseIndexDropdown from '@/scripts/admin/components/dropdowns/WarehouseIndexDropdown.vue'
import StockTabNavigation from '@/scripts/admin/components/StockTabNavigation.vue'

const { t } = useI18n()
const warehouseStore = useWarehouseStore()
const userStore = useUserStore()

const showFilters = ref(false)
const tableComponent = ref(null)

const filters = reactive({
  search: '',
  is_active: null,
  page: 1,
  limit: 10,
  orderByField: undefined,
  orderBy: undefined,
})

const statusOptions = computed(() => [
  { value: true, label: t('general.active') },
  { value: false, label: t('general.inactive') },
])

const columns = computed(() => {
  return [
    { key: 'name', label: t('warehouses.name'), sortable: true },
    { key: 'code', label: t('warehouses.code'), sortable: true },
    { key: 'is_default', label: t('warehouses.is_default'), sortable: true },
    { key: 'is_active', label: t('warehouses.is_active'), sortable: true },
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
    is_active: filters.is_active?.value ?? filters.is_active,
    page: filters.page,
    limit: filters.limit,
    orderByField: filters.orderByField,
    orderBy: filters.orderBy,
  }
  warehouseStore.fetchWarehouses(query)
}

function clearFilter() {
  filters.search = ''
  filters.is_active = null
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
    abilities.DELETE_WAREHOUSE,
    abilities.EDIT_WAREHOUSE,
  ])
}

onMounted(() => {
  fetchData()
})
</script>
// CLAUDE-CHECKPOINT
