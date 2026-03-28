<template>
  <BasePage>
    <BasePageHeader :title="$t('items.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('items.item', 2)" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <div class="flex items-center justify-end space-x-5">
          <BaseButton
            v-show="itemStore.totalItems"
            variant="primary-outline"
            @click="toggleFilter"
          >
            {{ $t('general.filter') }}
            <template #right="slotProps">
              <BaseIcon
                v-if="!showFilters"
                :class="slotProps.class"
                name="FunnelIcon"
              />
              <BaseIcon v-else name="XMarkIcon" :class="slotProps.class" />
            </template>
          </BaseButton>

          <ExportButton
            v-show="itemStore.totalItems"
            type="items"
            :filters="filters"
          />

          <BaseButton
            v-if="userStore.hasAbilities(abilities.CREATE_ITEM)"
            @click="$router.push('/admin/items/create')"
          >
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ $t('items.add_item') }}
          </BaseButton>
        </div>
      </template>
    </BasePageHeader>

    <BaseFilterWrapper :show="showFilters" class="mt-5" @clear="clearFilter">
      <BaseInputGroup :label="$t('items.name')" class="text-left">
        <BaseInput
          v-model="filters.name"
          type="text"
          name="name"
          autocomplete="off"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('items.unit')" class="text-left">
        <BaseMultiselect
          v-model="filters.unit_id"
          :placeholder="$t('items.select_a_unit')"
          value-prop="id"
          track-by="name"
          :filter-results="false"
          label="name"
          resolve-on-load
          :delay="500"
          searchable
          class="w-full"
          :options="searchUnits"
        />
      </BaseInputGroup>

      <BaseInputGroup class="text-left" :label="$t('items.price_from', 'Price From')">
        <BaseMoney v-model="filters.price_from" />
      </BaseInputGroup>

      <BaseInputGroup class="text-left" :label="$t('items.price_to', 'Price To')">
        <BaseMoney v-model="filters.price_to" />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('items.category')" class="text-left">
        <BaseMultiselect
          v-model="filters.category_id"
          :placeholder="$t('items.category_placeholder', 'All categories')"
          value-prop="id"
          track-by="name"
          label="name"
          searchable
          :can-deselect="true"
          class="w-full"
          :options="itemStore.itemCategories"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('items.track_quantity')" class="text-left">
        <BaseMultiselect
          v-model="filters.track_quantity"
          :placeholder="$t('general.all', 'All')"
          :can-deselect="true"
          class="w-full"
          :options="[
            { id: '1', name: $t('items.track_quantity_enabled') },
            { id: '0', name: $t('items.track_quantity_disabled') },
          ]"
          value-prop="id"
          label="name"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('stock.low_stock', 'Low Stock')" class="text-left">
        <div class="flex items-center h-10">
          <BaseCheckbox
            v-model="filters.low_stock"
            :label="$t('items.show_low_stock', 'Show only low stock items')"
          />
        </div>
      </BaseInputGroup>
    </BaseFilterWrapper>

    <BaseEmptyPlaceholder
      v-show="showEmptyScreen"
      :title="$t('items.no_items')"
      :description="$t('items.list_of_items')"
    >
      <SatelliteIcon class="mt-5 mb-4" />

      <template #actions>
        <BaseButton
          v-if="userStore.hasAbilities(abilities.CREATE_ITEM)"
          variant="primary-outline"
          @click="$router.push('/admin/items/create')"
        >
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ $t('items.add_new_item') }}
        </BaseButton>
      </template>
    </BaseEmptyPlaceholder>

    <div v-show="!showEmptyScreen" class="relative table-container">
      <div
        class="
          relative
          flex
          items-center
          justify-end
          h-5
          border-gray-200 border-solid
        "
      >
        <BaseDropdown v-if="itemStore.selectedItems.length">
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

          <BaseDropdownItem @click="showBulkCategoryModal = true">
            <BaseIcon name="TagIcon" class="mr-3 text-gray-600" />
            {{ $t('items.bulk_assign_category', 'Assign Category') }}
          </BaseDropdownItem>

          <BaseDropdownItem @click="bulkEnableStockTracking">
            <BaseIcon name="ArchiveBoxIcon" class="mr-3 text-gray-600" />
            {{ $t('items.bulk_enable_stock', 'Enable Stock Tracking') }}
          </BaseDropdownItem>

          <BaseDropdownItem @click="bulkDisableStockTracking">
            <BaseIcon name="ArchiveBoxXMarkIcon" class="mr-3 text-gray-600" />
            {{ $t('items.bulk_disable_stock', 'Disable Stock Tracking') }}
          </BaseDropdownItem>

          <BaseDropdownItem @click="removeMultipleItems">
            <BaseIcon name="TrashIcon" class="mr-3 text-gray-600" />
            {{ $t('general.delete') }}
          </BaseDropdownItem>
        </BaseDropdown>
      </div>

      <BaseTable
        ref="table"
        :data="fetchData"
        :columns="itemColumns"
        :placeholder-count="itemStore.totalItems >= 20 ? 10 : 5"
        class="mt-3"
      >
        <template #header>
          <div class="absolute items-center left-6 top-2.5 select-none">
            <BaseCheckbox
              v-model="itemStore.selectAllField"
              variant="primary"
              @change="itemStore.selectAllItems"
            />
          </div>
        </template>

        <template #cell-status="{ row }">
          <div class="relative block">
            <BaseCheckbox
              :id="row.id"
              v-model="selectField"
              :value="row.data.id"
            />
          </div>
        </template>

        <template #cell-name="{ row }">
          <router-link
            :to="{ path: `items/${row.data.id}/edit` }"
            class="font-medium text-primary-500"
          >
            <BaseText :text="row.data.name" />
          </router-link>
        </template>

        <template #cell-sku="{ row }">
          <span class="text-gray-600">
            {{ row.data.sku || '-' }}
          </span>
        </template>

        <template #cell-unit_name="{ row }">
          <span>
            {{ row.data.unit ? row.data.unit.name : '-' }}
          </span>
        </template>

        <template #cell-price="{ row }">
          <BaseFormatMoney
            :amount="row.data.price"
            :currency="companyStore.selectedCompanyCurrency"
          />
        </template>

        <template #cell-quantity="{ row }">
          <span v-if="row.data.track_quantity" class="text-gray-900">
            {{ row.data.quantity ?? 0 }}
            <span v-if="row.data.minimum_quantity && row.data.quantity <= row.data.minimum_quantity" class="ml-1 bg-red-100 bg-opacity-75 px-2 py-0.5 text-xs font-medium text-red-700 uppercase rounded-full">
              {{ $t('general.low') || 'Low' }}
            </span>
          </span>
          <span v-else class="text-gray-400">-</span>
        </template>

        <template #cell-created_at="{ row }">
          <span>{{ row.data.formatted_created_at }}</span>
        </template>

        <template v-if="hasAbilities()" #cell-actions="{ row }">
          <ItemDropdown
            :row="row.data"
            :table="table"
            :load-data="refreshTable"
          />
        </template>
      </BaseTable>
    </div>
    <!-- Bulk Category Assignment Modal -->
    <BaseModal :show="showBulkCategoryModal" @close="showBulkCategoryModal = false">
      <template #header>
        {{ $t('items.bulk_assign_category', 'Assign Category') }}
      </template>
      <div class="p-6">
        <BaseInputGroup :label="$t('items.category')">
          <BaseMultiselect
            v-model="bulkCategoryId"
            label="name"
            :options="itemStore.itemCategories"
            value-prop="id"
            :placeholder="$t('items.category_placeholder', 'Select category')"
            searchable
            :can-deselect="true"
            track-by="name"
          />
        </BaseInputGroup>
        <div class="flex justify-end mt-4 space-x-3">
          <BaseButton variant="primary-outline" @click="showBulkCategoryModal = false">
            {{ $t('general.cancel') }}
          </BaseButton>
          <BaseButton @click="bulkAssignCategory">
            {{ $t('general.save') }}
          </BaseButton>
        </div>
      </div>
    </BaseModal>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted, reactive, onUnmounted } from 'vue' // Fixed: Removed unused inject import
import { debouncedWatch } from '@vueuse/core'
import { useI18n } from 'vue-i18n'
import { useItemStore } from '@/scripts/admin/stores/item'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useUserStore } from '@/scripts/admin/stores/user'
import ItemDropdown from '@/scripts/admin/components/dropdowns/ItemIndexDropdown.vue'
import SatelliteIcon from '@/scripts/components/icons/empty/SatelliteIcon.vue'
import abilities from '@/scripts/admin/stub/abilities'
import ExportButton from '@/scripts/admin/components/ExportButton.vue'

const itemStore = useItemStore()
const companyStore = useCompanyStore()
const notificationStore = useNotificationStore()
const dialogStore = useDialogStore()
const userStore = useUserStore()

const { t } = useI18n()
let showFilters = ref(false)
let isFetchingInitialData = ref(true)

const filters = reactive({
  name: '',
  unit_id: '',
  price_from: '',
  price_to: '',
  category_id: '',
  track_quantity: '',
  low_stock: false,
})

const table = ref(null)

const showEmptyScreen = computed(
  () => !itemStore.totalItems && !isFetchingInitialData.value
)

const selectField = computed({
  get: () => itemStore.selectedItems,
  set: (value) => {
    return itemStore.selectItem(value)
  },
})

const itemColumns = computed(() => {
  return [
    {
      key: 'status',
      thClass: 'extra w-10',
      tdClass: 'font-medium text-gray-900',
      placeholderClass: 'w-10',
      sortable: false,
    },
    {
      key: 'name',
      label: t('items.name'),
      thClass: 'extra',
      tdClass: 'font-medium text-gray-900',
    },
    { key: 'sku', label: t('items.sku') },
    { key: 'unit_name', label: t('items.unit') },
    { key: 'price', label: t('items.price') },
    { key: 'quantity', label: t('stock.quantity') },
    { key: 'created_at', label: t('items.added_on') },

    {
      key: 'actions',
      thClass: 'text-right',
      tdClass: 'text-right text-sm font-medium',
      sortable: false,
    },
  ]
})
// CLAUDE-CHECKPOINT: Added SKU column to items table

debouncedWatch(
  filters,
  () => {
    setFilters()
  },
  { debounce: 500 }
)

itemStore.fetchItemUnits({ limit: 'all' })
itemStore.fetchItemCategories({ limit: 'all' })

onUnmounted(() => {
  if (itemStore.selectAllField) {
    itemStore.selectAllItems()
  }
})

function clearFilter() {
  filters.name = ''
  filters.unit_id = ''
  filters.price = ''
  filters.category_id = ''
  filters.track_quantity = ''
  filters.low_stock = false
}

function hasAbilities() {
  return userStore.hasAbilities([abilities.DELETE_ITEM, abilities.EDIT_ITEM])
}

function toggleFilter() {
  if (showFilters.value) {
    clearFilter()
  }

  showFilters.value = !showFilters.value
}

function refreshTable() {
  table.value && table.value.refresh()
}

function setFilters() {
  refreshTable()
}

async function searchUnits(search) {
  let res = await itemStore.fetchItemUnits({ search })

  return res.data.data
}

async function fetchData({ page, filter, sort }) {
  let data = {
    search: filters.name,
    unit_id: filters.unit_id !== null ? filters.unit_id : '',
    price: Math.round(filters.price * 100),
    category_id: filters.category_id !== null ? filters.category_id : '',
    track_quantity: filters.track_quantity !== null ? filters.track_quantity : '',
    low_stock: filters.low_stock ? 1 : '',
    orderByField: sort.fieldName || 'created_at',
    orderBy: sort.order || 'desc',
    page,
  }

  isFetchingInitialData.value = true

  let response = await itemStore.fetchItems(data)

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

const showBulkCategoryModal = ref(false)
const bulkCategoryId = ref(null)

async function bulkAssignCategory() {
  try {
    await itemStore.bulkUpdate('assign_category', {
      category_id: bulkCategoryId.value,
    })
    showBulkCategoryModal.value = false
    bulkCategoryId.value = null
    refreshTable()
  } catch {
    // error handled by store
  }
}

async function bulkEnableStockTracking() {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('items.bulk_enable_stock_confirm', 'Enable stock tracking for {count} selected items?', { count: itemStore.selectedItems.length }),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'lg',
    })
    .then(async (res) => {
      if (res) {
        await itemStore.bulkUpdate('toggle_track_quantity', { track_quantity: true })
        refreshTable()
      }
    })
}

async function bulkDisableStockTracking() {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('items.bulk_disable_stock_confirm', 'Disable stock tracking for {count} selected items?', { count: itemStore.selectedItems.length }),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then(async (res) => {
      if (res) {
        await itemStore.bulkUpdate('toggle_track_quantity', { track_quantity: false })
        refreshTable()
      }
    })
}

function removeMultipleItems() {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('items.confirm_delete', 2),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then((res) => {
      if (res) {
        itemStore.deleteMultipleItems().then((response) => {
          // Fixed: Added fallback if response.data.success is undefined - still refresh table
          if (response.data.success || response.data.success === undefined) {
            table.value && table.value.refresh()
          }
        })
      }
    })
}
</script>
// CLAUDE-CHECKPOINT: Added export button to items index view
