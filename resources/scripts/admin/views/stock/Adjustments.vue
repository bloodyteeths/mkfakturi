<template>
  <BasePage>
    <BasePageHeader :title="$t('stock.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('stock.title')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <div class="flex items-center space-x-3">
          <BaseButton
            variant="primary-outline"
            @click="showTransferModal = true"
          >
            <template #left="slotProps">
              <BaseIcon name="ArrowsRightLeftIcon" :class="slotProps.class" />
            </template>
            {{ $t('stock.new_transfer') }}
          </BaseButton>
          <BaseButton
            variant="primary"
            @click="showAdjustmentModal = true"
          >
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ $t('stock.new_adjustment') }}
          </BaseButton>
        </div>
      </template>
    </BasePageHeader>

    <!-- Stock Sub-Navigation Tabs -->
    <StockTabNavigation />

    <!-- Stock Module Disabled Warning -->
    <BaseCard v-if="!stockStore.stockEnabled" class="mb-6">
      <div class="text-center py-8">
        <BaseIcon name="ExclamationTriangleIcon" class="h-12 w-12 text-yellow-500 mx-auto mb-4" />
        <h3 class="text-lg font-medium text-gray-900">{{ $t('stock.module_disabled') }}</h3>
        <p class="text-gray-500 mt-2">{{ $t('stock.module_disabled_message') }}</p>
      </div>
    </BaseCard>

    <template v-else>
      <!-- Tabs for Adjustments / Transfers -->
      <div class="mb-6">
        <nav class="flex space-x-4" aria-label="Tabs">
          <button
            @click="activeTab = 'adjustments'"
            :class="[
              activeTab === 'adjustments'
                ? 'bg-primary-100 text-primary-700'
                : 'text-gray-500 hover:text-gray-700',
              'px-3 py-2 font-medium text-sm rounded-md'
            ]"
          >
            {{ $t('stock.adjustments') }}
          </button>
          <button
            @click="activeTab = 'transfers'"
            :class="[
              activeTab === 'transfers'
                ? 'bg-primary-100 text-primary-700'
                : 'text-gray-500 hover:text-gray-700',
              'px-3 py-2 font-medium text-sm rounded-md'
            ]"
          >
            {{ $t('stock.transfers') }}
          </button>
        </nav>
      </div>

      <!-- Adjustments Tab -->
      <BaseCard v-if="activeTab === 'adjustments'">
        <template #header>
          <h3 class="text-lg font-medium text-gray-900">{{ $t('stock.adjustments_list') }}</h3>
        </template>

        <div v-if="isLoadingAdjustments" class="flex justify-center py-8">
          <BaseContentPlaceholders>
            <BaseContentPlaceholdersBox class="w-full h-64" />
          </BaseContentPlaceholders>
        </div>

        <div v-else-if="adjustments.length === 0" class="text-center py-8">
          <BaseIcon name="AdjustmentsHorizontalIcon" class="h-12 w-12 text-gray-400 mx-auto mb-4" />
          <h3 class="text-lg font-medium text-gray-900">{{ $t('stock.no_adjustments') }}</h3>
          <p class="text-gray-500 mt-2">{{ $t('stock.no_adjustments_message') }}</p>
          <BaseButton variant="primary" class="mt-4" @click="showAdjustmentModal = true">
            {{ $t('stock.create_first_adjustment') }}
          </BaseButton>
        </div>

        <table v-else class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                {{ $t('stock.date') }}
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                {{ $t('stock.item') }}
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                {{ $t('stock.warehouse') }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                {{ $t('stock.quantity') }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                {{ $t('stock.unit_cost') }}
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                {{ $t('stock.reason') }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                {{ $t('general.actions') }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="adj in adjustments" :key="adj.id">
              <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
                {{ adj.movement_date }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-900">
                {{ adj.item_name }}
                <span v-if="adj.item_sku" class="text-gray-500 text-xs ml-1">({{ adj.item_sku }})</span>
              </td>
              <td class="px-4 py-3 text-sm text-gray-500">
                {{ adj.warehouse_name }}
              </td>
              <td class="px-4 py-3 text-sm text-right font-medium" :class="adj.quantity > 0 ? 'text-green-600' : 'text-red-600'">
                {{ adj.quantity > 0 ? '+' : '' }}{{ adj.quantity }}
              </td>
              <td class="px-4 py-3 text-sm text-right text-gray-900">
                <BaseFormatMoney v-if="adj.unit_cost" :amount="adj.unit_cost" />
                <span v-else>-</span>
              </td>
              <td class="px-4 py-3 text-sm text-gray-500 max-w-xs truncate">
                {{ adj.meta?.reason || adj.notes || '-' }}
              </td>
              <td class="px-4 py-3 text-sm text-right">
                <BaseButton variant="danger-outline" size="sm" @click="reverseAdjustment(adj)">
                  <BaseIcon name="ArrowUturnLeftIcon" class="h-4 w-4" />
                </BaseButton>
              </td>
            </tr>
          </tbody>
        </table>
      </BaseCard>

      <!-- Transfers Tab -->
      <BaseCard v-if="activeTab === 'transfers'">
        <template #header>
          <h3 class="text-lg font-medium text-gray-900">{{ $t('stock.transfers_list') }}</h3>
        </template>

        <div v-if="isLoadingTransfers" class="flex justify-center py-8">
          <BaseContentPlaceholders>
            <BaseContentPlaceholdersBox class="w-full h-64" />
          </BaseContentPlaceholders>
        </div>

        <div v-else-if="transfers.length === 0" class="text-center py-8">
          <BaseIcon name="ArrowsRightLeftIcon" class="h-12 w-12 text-gray-400 mx-auto mb-4" />
          <h3 class="text-lg font-medium text-gray-900">{{ $t('stock.no_transfers') }}</h3>
          <p class="text-gray-500 mt-2">{{ $t('stock.no_transfers_message') }}</p>
          <BaseButton variant="primary" class="mt-4" @click="showTransferModal = true">
            {{ $t('stock.create_first_transfer') }}
          </BaseButton>
        </div>

        <table v-else class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                {{ $t('stock.date') }}
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                {{ $t('stock.item') }}
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                {{ $t('stock.from_warehouse') }}
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                {{ $t('stock.to_warehouse') }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                {{ $t('stock.quantity') }}
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                {{ $t('stock.notes') }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="transfer in transfers" :key="transfer.id">
              <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
                {{ transfer.movement_date }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-900">
                {{ transfer.item_name }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-500">
                {{ transfer.source_type === 'transfer_out' ? transfer.warehouse_name : '-' }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-500">
                {{ transfer.source_type === 'transfer_in' ? transfer.warehouse_name : '-' }}
              </td>
              <td class="px-4 py-3 text-sm text-right font-medium" :class="transfer.is_stock_in ? 'text-green-600' : 'text-red-600'">
                {{ Math.abs(transfer.quantity) }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-500 max-w-xs truncate">
                {{ transfer.notes || '-' }}
              </td>
            </tr>
          </tbody>
        </table>
      </BaseCard>
    </template>

    <!-- Adjustment Modal -->
    <BaseModal :show="showAdjustmentModal" @close="closeAdjustmentModal">
      <template #header>
        <h3 class="text-lg font-medium text-gray-900">{{ $t('stock.new_adjustment') }}</h3>
      </template>

      <form @submit.prevent="createAdjustment">
        <div class="space-y-4">
          <BaseInputGroup :label="$t('stock.item')" required>
            <BaseMultiselect
              v-model="adjustmentForm.item"
              :content-loading="isLoadingItems"
              value-prop="id"
              track-by="name"
              label="name"
              :filterResults="false"
              resolve-on-load
              :delay="500"
              searchable
              :options="searchItems"
              object
              :placeholder="$t('stock.select_item')"
            >
              <template #singlelabel="{ value }">
                <div class="multiselect-single-label">
                  <span>{{ value.name }}</span>
                  <span v-if="value.sku" class="text-gray-500 text-xs ml-2">({{ value.sku }})</span>
                </div>
              </template>
              <template #option="{ option }">
                <div class="flex justify-between items-center w-full">
                  <span>{{ option.name }}</span>
                  <span v-if="option.sku" class="text-gray-500 text-xs">({{ option.sku }})</span>
                </div>
              </template>
            </BaseMultiselect>
          </BaseInputGroup>

          <BaseInputGroup :label="$t('stock.warehouse')" required>
            <BaseMultiselect
              v-model="adjustmentForm.warehouse_id"
              :content-loading="stockStore.isLoadingWarehouses"
              value-prop="id"
              track-by="name"
              label="name"
              :options="stockStore.warehouses"
              :placeholder="$t('stock.select_warehouse')"
            />
          </BaseInputGroup>

          <!-- Current Stock Display -->
          <div v-if="currentItemStock !== null" class="bg-blue-50 p-3 rounded-lg">
            <span class="text-sm text-blue-700">
              {{ $t('stock.current_stock') }}: <strong>{{ currentItemStock }}</strong>
            </span>
          </div>

          <BaseInputGroup :label="$t('stock.quantity')" required>
            <BaseInput
              v-model="adjustmentForm.quantity"
              type="number"
              step="0.01"
              :placeholder="$t('stock.quantity_placeholder')"
            />
            <p class="mt-1 text-xs text-gray-500">{{ $t('stock.quantity_hint') }}</p>
          </BaseInputGroup>

          <BaseInputGroup :label="$t('stock.unit_cost')">
            <BaseMoney v-model="adjustmentForm.unit_cost" />
            <p class="mt-1 text-xs text-gray-500">{{ $t('stock.unit_cost_adjustment_hint') }}</p>
          </BaseInputGroup>

          <BaseInputGroup :label="$t('stock.reason')" required>
            <BaseMultiselect
              v-model="adjustmentForm.reason"
              :options="adjustmentReasons"
              :placeholder="$t('stock.select_reason')"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('stock.notes')">
            <BaseTextarea
              v-model="adjustmentForm.notes"
              rows="2"
              :placeholder="$t('stock.notes_placeholder')"
            />
          </BaseInputGroup>
        </div>

        <div class="mt-6 flex justify-end space-x-3">
          <BaseButton variant="secondary" @click="closeAdjustmentModal">
            {{ $t('general.cancel') }}
          </BaseButton>
          <BaseButton type="submit" :loading="isSaving">
            {{ $t('stock.create_adjustment') }}
          </BaseButton>
        </div>
      </form>
    </BaseModal>

    <!-- Transfer Modal -->
    <BaseModal :show="showTransferModal" @close="closeTransferModal">
      <template #header>
        <h3 class="text-lg font-medium text-gray-900">{{ $t('stock.new_transfer') }}</h3>
      </template>

      <form @submit.prevent="createTransfer">
        <div class="space-y-4">
          <BaseInputGroup :label="$t('stock.item')" required>
            <BaseMultiselect
              v-model="transferForm.item"
              :content-loading="isLoadingItems"
              value-prop="id"
              track-by="name"
              label="name"
              :filterResults="false"
              resolve-on-load
              :delay="500"
              searchable
              :options="searchItems"
              object
              :placeholder="$t('stock.select_item')"
            >
              <template #singlelabel="{ value }">
                <div class="multiselect-single-label">
                  <span>{{ value.name }}</span>
                  <span v-if="value.sku" class="text-gray-500 text-xs ml-2">({{ value.sku }})</span>
                </div>
              </template>
            </BaseMultiselect>
          </BaseInputGroup>

          <BaseInputGroup :label="$t('stock.from_warehouse')" required>
            <BaseMultiselect
              v-model="transferForm.from_warehouse_id"
              :content-loading="stockStore.isLoadingWarehouses"
              value-prop="id"
              track-by="name"
              label="name"
              :options="stockStore.warehouses"
              :placeholder="$t('stock.select_warehouse')"
            />
          </BaseInputGroup>

          <!-- Source Warehouse Stock Display -->
          <div v-if="sourceWarehouseStock !== null" class="bg-blue-50 p-3 rounded-lg">
            <span class="text-sm text-blue-700">
              {{ $t('stock.available_in_source') }}: <strong>{{ sourceWarehouseStock }}</strong>
            </span>
          </div>

          <BaseInputGroup :label="$t('stock.to_warehouse')" required>
            <BaseMultiselect
              v-model="transferForm.to_warehouse_id"
              :content-loading="stockStore.isLoadingWarehouses"
              value-prop="id"
              track-by="name"
              label="name"
              :options="availableDestinationWarehouses"
              :placeholder="$t('stock.select_warehouse')"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('stock.quantity')" required>
            <BaseInput
              v-model="transferForm.quantity"
              type="number"
              step="0.01"
              min="0.01"
              :max="sourceWarehouseStock || undefined"
              :placeholder="$t('stock.transfer_quantity_placeholder')"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('stock.notes')">
            <BaseTextarea
              v-model="transferForm.notes"
              rows="2"
              :placeholder="$t('stock.transfer_notes_placeholder')"
            />
          </BaseInputGroup>
        </div>

        <div class="mt-6 flex justify-end space-x-3">
          <BaseButton variant="secondary" @click="closeTransferModal">
            {{ $t('general.cancel') }}
          </BaseButton>
          <BaseButton type="submit" :loading="isSaving">
            {{ $t('stock.create_transfer') }}
          </BaseButton>
        </div>
      </form>
    </BaseModal>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useStockStore } from '@/scripts/admin/stores/stock'
import { useItemStore } from '@/scripts/admin/stores/item'
import { useGlobalStore } from '@/scripts/admin/stores/global'
import { useDialogStore } from '@/scripts/stores/dialog'
import StockTabNavigation from '@/scripts/admin/components/StockTabNavigation.vue'

const { t } = useI18n()
const stockStore = useStockStore()
const itemStore = useItemStore()
const globalStore = useGlobalStore()
const dialogStore = useDialogStore()

const activeTab = ref('adjustments')
const adjustments = ref([])
const transfers = ref([])
const isLoadingAdjustments = ref(false)
const isLoadingTransfers = ref(false)
const isLoadingItems = ref(false)
const isSaving = ref(false)

// Modals
const showAdjustmentModal = ref(false)
const showTransferModal = ref(false)

// Adjustment form
const adjustmentForm = reactive({
  item: null,
  warehouse_id: null,
  quantity: null,
  unit_cost: null,
  reason: null,
  notes: '',
})

// Transfer form
const transferForm = reactive({
  item: null,
  from_warehouse_id: null,
  to_warehouse_id: null,
  quantity: null,
  notes: '',
})

// Current stock for selected item/warehouse
const currentItemStock = ref(null)
const sourceWarehouseStock = ref(null)

// Adjustment reasons
const adjustmentReasons = [
  'Inventory count correction',
  'Damaged goods',
  'Expired goods',
  'Lost/stolen',
  'Found during audit',
  'Opening balance',
  'Other',
]

// Filter destination warehouses to exclude source
const availableDestinationWarehouses = computed(() => {
  if (!transferForm.from_warehouse_id) {
    return stockStore.warehouses
  }
  return stockStore.warehouses.filter(w => w.id !== transferForm.from_warehouse_id)
})

// Watch for item/warehouse changes to fetch current stock
watch(
  () => [adjustmentForm.item, adjustmentForm.warehouse_id],
  async ([item, warehouseId]) => {
    if (item && warehouseId) {
      try {
        const data = await stockStore.getItemStock(item.id, warehouseId)
        currentItemStock.value = data.quantity
      } catch {
        currentItemStock.value = null
      }
    } else {
      currentItemStock.value = null
    }
  }
)

watch(
  () => [transferForm.item, transferForm.from_warehouse_id],
  async ([item, warehouseId]) => {
    if (item && warehouseId) {
      try {
        const data = await stockStore.getItemStock(item.id, warehouseId)
        sourceWarehouseStock.value = data.quantity
      } catch {
        sourceWarehouseStock.value = null
      }
    } else {
      sourceWarehouseStock.value = null
    }
  }
)

async function searchItems(search) {
  isLoadingItems.value = true
  try {
    const res = await itemStore.fetchItems({ search, track_quantity: true })
    return res.data.data
  } finally {
    isLoadingItems.value = false
  }
}

async function loadAdjustments() {
  isLoadingAdjustments.value = true
  try {
    const res = await stockStore.fetchAdjustments({ limit: 50 })
    adjustments.value = res.data.data || []
  } catch (error) {
    console.error('Failed to load adjustments:', error)
  } finally {
    isLoadingAdjustments.value = false
  }
}

async function loadTransfers() {
  isLoadingTransfers.value = true
  try {
    const res = await stockStore.fetchTransfers({ limit: 50 })
    transfers.value = res.data.data || []
  } catch (error) {
    console.error('Failed to load transfers:', error)
  } finally {
    isLoadingTransfers.value = false
  }
}

function closeAdjustmentModal() {
  showAdjustmentModal.value = false
  resetAdjustmentForm()
}

function closeTransferModal() {
  showTransferModal.value = false
  resetTransferForm()
}

function resetAdjustmentForm() {
  adjustmentForm.item = null
  adjustmentForm.warehouse_id = null
  adjustmentForm.quantity = null
  adjustmentForm.unit_cost = null
  adjustmentForm.reason = null
  adjustmentForm.notes = ''
  currentItemStock.value = null
}

function resetTransferForm() {
  transferForm.item = null
  transferForm.from_warehouse_id = null
  transferForm.to_warehouse_id = null
  transferForm.quantity = null
  transferForm.notes = ''
  sourceWarehouseStock.value = null
}

async function createAdjustment() {
  if (!adjustmentForm.item || !adjustmentForm.warehouse_id || !adjustmentForm.quantity || !adjustmentForm.reason) {
    return
  }

  isSaving.value = true
  try {
    await stockStore.createAdjustment({
      item_id: adjustmentForm.item.id,
      warehouse_id: adjustmentForm.warehouse_id,
      quantity: parseFloat(adjustmentForm.quantity),
      unit_cost: adjustmentForm.unit_cost || 0,
      reason: adjustmentForm.reason,
      notes: adjustmentForm.notes,
    })

    closeAdjustmentModal()
    await loadAdjustments()
  } catch (error) {
    console.error('Failed to create adjustment:', error)
  } finally {
    isSaving.value = false
  }
}

async function createTransfer() {
  if (!transferForm.item || !transferForm.from_warehouse_id || !transferForm.to_warehouse_id || !transferForm.quantity) {
    return
  }

  isSaving.value = true
  try {
    await stockStore.createTransfer({
      item_id: transferForm.item.id,
      from_warehouse_id: transferForm.from_warehouse_id,
      to_warehouse_id: transferForm.to_warehouse_id,
      quantity: parseFloat(transferForm.quantity),
      notes: transferForm.notes,
    })

    closeTransferModal()
    await loadTransfers()
  } catch (error) {
    console.error('Failed to create transfer:', error)
  } finally {
    isSaving.value = false
  }
}

async function reverseAdjustment(adjustment) {
  const confirmed = await dialogStore.openDialog({
    title: t('general.are_you_sure'),
    message: t('stock.reverse_adjustment_confirm'),
    yesLabel: t('general.yes'),
    noLabel: t('general.no'),
    variant: 'danger',
  })

  if (confirmed) {
    try {
      await stockStore.deleteAdjustment(adjustment.id)
      await loadAdjustments()
    } catch (error) {
      console.error('Failed to reverse adjustment:', error)
    }
  }
}

onMounted(async () => {
  // Check if stock is enabled
  const bootstrap = globalStore.bootstrap
  stockStore.setStockEnabled(bootstrap?.stock_enabled || false)

  if (stockStore.stockEnabled) {
    await stockStore.fetchWarehouses()
    await Promise.all([loadAdjustments(), loadTransfers()])
  }
})
</script>
// CLAUDE-CHECKPOINT
