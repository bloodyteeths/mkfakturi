<template>
  <BasePage>
    <BasePageHeader :title="pageTitle">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('items.item', 2)" to="/admin/items" />
        <BaseBreadcrumbItem :title="pageTitle" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <ItemUnitModal />

    <form
      class="grid lg:grid-cols-2 mt-6"
      action="submit"
      @submit.prevent="submitItem"
    >
      <BaseCard class="w-full">
        <BaseInputGrid layout="one-column">
          <BaseInputGroup
            :label="$t('items.name')"
            :content-loading="isFetchingInitialData"
            required
            :error="
              v$.currentItem.name.$error &&
              v$.currentItem.name.$errors[0].$message
            "
          >
            <BaseInput
              v-model="itemStore.currentItem.name"
              :content-loading="isFetchingInitialData"
              :invalid="v$.currentItem.name.$error"
              @input="v$.currentItem.name.$touch()"
            />
          </BaseInputGroup>

          <BaseInputGroup
            :label="$t('items.sku')"
            :content-loading="isFetchingInitialData"
            :error="
              v$.currentItem.sku.$error &&
              v$.currentItem.sku.$errors[0].$message
            "
          >
            <BaseInput
              v-model="itemStore.currentItem.sku"
              :content-loading="isFetchingInitialData"
              :invalid="v$.currentItem.sku.$error"
              @input="v$.currentItem.sku.$touch()"
            />
          </BaseInputGroup>

          <BaseInputGroup
            :label="$t('items.barcode')"
            :content-loading="isFetchingInitialData"
            :error="
              v$.currentItem.barcode.$error &&
              v$.currentItem.barcode.$errors[0].$message
            "
          >
            <BaseInput
              v-model="itemStore.currentItem.barcode"
              :content-loading="isFetchingInitialData"
              :invalid="v$.currentItem.barcode.$error"
              @input="v$.currentItem.barcode.$touch()"
            />
          </BaseInputGroup>

          <BaseInputGroup
            :label="$t('items.price')"
            :content-loading="isFetchingInitialData"
          >
            <BaseMoney
              v-model="price"
              :content-loading="isFetchingInitialData"
            />
          </BaseInputGroup>

          <BaseInputGroup
            :content-loading="isFetchingInitialData"
            :label="$t('items.unit')"
          >
            <BaseMultiselect
              v-model="itemStore.currentItem.unit_id"
              :content-loading="isFetchingInitialData"
              label="name"
              :options="itemStore.itemUnits"
              value-prop="id"
              :placeholder="$t('items.select_a_unit')"
              searchable
              track-by="name"
            >
              <template #action>
                <BaseSelectAction @click="addItemUnit">
                  <BaseIcon
                    name="PlusIcon"
                    class="h-4 mr-2 -ml-2 text-center text-primary-400"
                  />
                  {{ $t('settings.customization.items.add_item_unit') }}
                </BaseSelectAction>
              </template>
            </BaseMultiselect>
          </BaseInputGroup>

          <BaseInputGroup
            v-if="isTaxPerItem"
            :label="$t('items.taxes')"
            :content-loading="isFetchingInitialData"
          >
            <BaseMultiselect
              v-model="taxes"
              :content-loading="isFetchingInitialData"
              :options="getTaxTypes"
              mode="tags"
              label="tax_name"
              class="w-full"
              value-prop="id"
              :can-deselect="false"
              :can-clear="false"
              searchable
              track-by="tax_name"
              object
            />
          </BaseInputGroup>

          <BaseInputGroup
            :label="$t('items.description')"
            :content-loading="isFetchingInitialData"
            :error="
              v$.currentItem.description.$error &&
              v$.currentItem.description.$errors[0].$message
            "
          >
            <BaseTextarea
              v-model="itemStore.currentItem.description"
              :content-loading="isFetchingInitialData"
              name="description"
              :row="2"
              rows="2"
              @input="v$.currentItem.description.$touch()"
            />
          </BaseInputGroup>

          <!-- Stock Tracking Toggle (only shows when stock module is enabled) -->
          <BaseInputGroup
            v-if="stockEnabled"
            :label="$t('items.track_quantity')"
            :content-loading="isFetchingInitialData"
          >
            <div class="flex items-center space-x-3">
              <BaseSwitch
                v-model="itemStore.currentItem.track_quantity"
                :content-loading="isFetchingInitialData"
              />
              <span class="text-sm text-gray-500">
                {{ itemStore.currentItem.track_quantity ? $t('items.track_quantity_enabled') : $t('items.track_quantity_disabled') }}
              </span>
            </div>
            <p class="mt-1 text-xs text-gray-400">
              {{ $t('items.track_quantity_hint') }}
            </p>
          </BaseInputGroup>

          <!-- Minimum Quantity (only shows when track_quantity is enabled) -->
          <BaseInputGroup
            v-if="stockEnabled && itemStore.currentItem.track_quantity"
            :label="$t('items.minimum_quantity')"
            :content-loading="isFetchingInitialData"
          >
            <BaseInput
              v-model="itemStore.currentItem.minimum_quantity"
              :content-loading="isFetchingInitialData"
              type="number"
              step="1"
              min="0"
              :placeholder="$t('items.minimum_quantity_placeholder')"
            />
            <p class="mt-1 text-xs text-gray-400">
              {{ $t('items.minimum_quantity_hint') }}
            </p>
          </BaseInputGroup>

          <!-- Category -->
          <BaseInputGroup
            v-if="stockEnabled && itemStore.currentItem.track_quantity"
            :label="$t('items.category')"
            :content-loading="isFetchingInitialData"
          >
            <BaseInput
              v-model="itemStore.currentItem.category"
              :content-loading="isFetchingInitialData"
              type="text"
              :placeholder="$t('items.category_placeholder')"
            />
          </BaseInputGroup>

          <!-- Initial Stock Entry (only for new items with track_quantity enabled) -->
          <template v-if="showInitialStock">
            <div class="col-span-1 pt-2 pb-1 border-t border-gray-200 dark:border-gray-700">
              <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $t('items.initial_stock_title') }}
              </h4>
              <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ $t('items.initial_stock_hint') }}
              </p>
            </div>

            <BaseInputGroup
              :label="$t('items.warehouse')"
              :content-loading="isFetchingInitialData"
            >
              <BaseMultiselect
                v-model="initialStock.warehouse_id"
                :content-loading="isFetchingInitialData"
                label="name"
                :options="warehouseStore.activeWarehouses"
                value-prop="id"
                :placeholder="$t('items.select_warehouse')"
                searchable
                track-by="name"
              />
            </BaseInputGroup>

            <BaseInputGroup
              :label="$t('items.initial_quantity')"
              :content-loading="isFetchingInitialData"
            >
              <BaseInput
                v-model="initialStock.quantity"
                :content-loading="isFetchingInitialData"
                type="number"
                step="0.01"
                min="0"
                :placeholder="$t('items.initial_quantity_placeholder')"
              />
            </BaseInputGroup>

            <BaseInputGroup
              :label="$t('items.unit_cost')"
              :content-loading="isFetchingInitialData"
            >
              <BaseMoney
                v-model="initialStock.unit_cost"
                :content-loading="isFetchingInitialData"
              />
              <p class="mt-1 text-xs text-gray-400">
                {{ $t('items.unit_cost_hint') }}
              </p>
            </BaseInputGroup>
          </template>

          <div>
            <BaseButton
              :content-loading="isFetchingInitialData"
              type="submit"
              :loading="isSaving"
            >
              <template #left="slotProps">
                <BaseIcon
                  v-if="!isSaving"
                  name="ArrowDownOnSquareIcon"
                  :class="slotProps.class"
                />
              </template>

              {{ isEdit ? $t('items.update_item') : $t('items.save_item') }}
            </BaseButton>
          </div>
        </BaseInputGrid>
      </BaseCard>
    </form>
  </BasePage>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  required,
  minLength,
  numeric,
  minValue,
  maxLength,
  helpers,
} from '@vuelidate/validators'
import useVuelidate from '@vuelidate/core'
import { useItemStore } from '@/scripts/admin/stores/item'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useTaxTypeStore } from '@/scripts/admin/stores/tax-type'
import { useModalStore } from '@/scripts/stores/modal'
import { useGlobalStore } from '@/scripts/admin/stores/global'
import { useWarehouseStore } from '@/scripts/admin/stores/warehouse'
import ItemUnitModal from '@/scripts/admin/components/modal-components/ItemUnitModal.vue'
import { useUserStore } from '@/scripts/admin/stores/user'
import abilities from '@/scripts/admin/stub/abilities'

const itemStore = useItemStore()
const globalStore = useGlobalStore()
const warehouseStore = useWarehouseStore()
const taxTypeStore = useTaxTypeStore()
const modalStore = useModalStore()
const companyStore = useCompanyStore()
const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const userStore = useUserStore()

const isSaving = ref(false)
const taxPerItem = ref(companyStore.selectedCompanySettings.tax_per_item)
const isFetchingInitialData = ref(false)

// Stock module is always enabled (no feature flag)
const stockEnabled = computed(() => true)

// isEdit must be defined BEFORE showInitialStock and loadData()
const isEdit = computed(() => route.name === 'items.edit')

// Initial stock entry fields (shown when track_quantity is enabled on new item)
const initialStock = ref({
  warehouse_id: null,
  quantity: null,
  unit_cost: null,
})

// Show initial stock fields only for NEW items with track_quantity enabled
const showInitialStock = computed(() => {
  return !isEdit.value &&
    stockEnabled.value &&
    itemStore.currentItem.track_quantity
})

// Load data after all refs and computeds are defined
loadData()

const price = computed({
  get: () => {
    // FIXED: For zero-precision currencies (like MKD), don't divide by 100
    const precision = parseInt(companyStore.selectedCompanyCurrency.precision)
    // Return 0 if price is null/undefined
    const currentPrice = itemStore.currentItem.price ?? 0
    if (precision === 0) {
      return currentPrice
    }
    return currentPrice / 100
  },
  set: (value) => {
    // FIXED: For zero-precision currencies (like MKD), don't multiply by 100
    // CRITICAL: v-money3 with masked=true can emit null when field is empty
    // Convert null/undefined to 0 to prevent validation errors
    const safeValue = value ?? 0
    const precision = parseInt(companyStore.selectedCompanyCurrency.precision)
    if (precision === 0) {
      itemStore.currentItem.price = Math.round(safeValue)
    } else {
      itemStore.currentItem.price = Math.round(safeValue * 100)
    }
  },
})

const taxes = computed({
  get: () =>
    itemStore?.currentItem?.taxes?.map((tax) => {
      if (tax) {
        return {
          ...tax,
          tax_type_id: tax.id,
          tax_name: `${tax.name} (${tax.calculation_type === 'fixed'
            ? new Intl.NumberFormat(undefined, {
                style: 'currency',
                currency: companyStore.selectedCompanyCurrency.code
              }).format(tax.fixed_amount / 100)
            : `${tax.percent}%`})`,
        }
      }
    }),
  set: (value) => {
    itemStore.currentItem.taxes = value
  },
})

const pageTitle = computed(() =>
  isEdit.value ? t('items.edit_item') : t('items.new_item')
)

const getTaxTypes = computed(() => {
  return taxTypeStore.taxTypes.map((tax) => {
    return {
      ...tax,
      tax_type_id: tax.id,
      tax_name: `${tax.name} (${tax.calculation_type === 'fixed'
        ? new Intl.NumberFormat(undefined, {
            style: 'currency',
            currency: companyStore.selectedCompanyCurrency.code
          }).format(tax.fixed_amount / 100)
        : `${tax.percent}%`})`,
    }
  })
})

const isTaxPerItem = computed(() => taxPerItem.value === 'YES')

const rules = computed(() => {
  return {
    currentItem: {
      name: {
        required: helpers.withMessage(t('validation.required'), required),
        minLength: helpers.withMessage(
          t('validation.name_min_length', { count: 2 }),
          minLength(2)
        ),
      },

      sku: {
        maxLength: helpers.withMessage(
          t('validation.sku_maxlength'),
          maxLength(255)
        ),
      },

      barcode: {
        maxLength: helpers.withMessage(
          t('validation.barcode_maxlength'),
          maxLength(255)
        ),
      },

      description: {
        maxLength: helpers.withMessage(
          t('validation.description_maxlength'),
          maxLength(65000)
        ),
      },
    },
  }
})

const v$ = useVuelidate(rules, itemStore)
// CLAUDE-CHECKPOINT: Added SKU and Barcode fields with validation

async function addItemUnit() {
  modalStore.openModal({
    title: t('settings.customization.items.add_item_unit'),
    componentName: 'ItemUnitModal',
    size: 'sm',
  })
}

async function loadData() {
  isFetchingInitialData.value = true

  // Reset store state for new items BEFORE loading
  // For edit mode, we'll populate with fetched data
  if (!isEdit.value) {
    itemStore.resetCurrentItem()
  }

  const loadPromises = [
    itemStore.fetchItemUnits({ limit: 'all' }),
  ]

  if (userStore.hasAbilities(abilities.VIEW_TAX_TYPE)) {
    loadPromises.push(taxTypeStore.fetchTaxTypes({ limit: 'all' }))
  }

  // Load warehouses if stock module is enabled
  if (stockEnabled.value) {
    loadPromises.push(warehouseStore.fetchWarehouses({ limit: 'all' }))
  }

  await Promise.all(loadPromises)

  if (isEdit.value) {
    let id = route.params.id
    await itemStore.fetchItem(id)
    itemStore.currentItem.tax_per_item === 1
      ? (taxPerItem.value = 'YES')
      : (taxPerItem.value = 'NO')
  } else {
    // For new items, set default warehouse if available
    if (warehouseStore.defaultWarehouse) {
      initialStock.value.warehouse_id = warehouseStore.defaultWarehouse.id
    }
  }

  isFetchingInitialData.value = false
}

async function submitItem() {
  v$.value.currentItem.$touch()

  if (v$.value.currentItem.$invalid) {
    return false
  }

  isSaving.value = true

  try {
    let data = {
      id: route.params.id,
      ...itemStore.currentItem,
    }

    if (itemStore.currentItem && itemStore.currentItem.taxes) {
      data.taxes = itemStore.currentItem.taxes.map((tax) => {
        return {
          tax_type_id: tax.tax_type_id,
          calculation_type: tax.calculation_type,
          fixed_amount: tax.fixed_amount,
          amount: tax.calculation_type === 'fixed' ? tax.fixed_amount : Math.round(price.value * tax.percent),
          percent: tax.percent,
          name: tax.name,
          collective_tax: 0,
        }
      })
    }

    // Include initial stock data for new items with track_quantity enabled
    if (showInitialStock.value &&
        initialStock.value.warehouse_id &&
        initialStock.value.quantity > 0) {
      data.initial_stock = {
        warehouse_id: initialStock.value.warehouse_id,
        quantity: initialStock.value.quantity,
        unit_cost: initialStock.value.unit_cost || 0,
      }
    }

    const action = isEdit.value ? itemStore.updateItem : itemStore.addItem

    await action(data)
    isSaving.value = false
    router.push('/admin/items')
    closeItemModal()
  } catch (err) {
    isSaving.value = false
    return
  }
  function closeItemModal() {
    modalStore.closeModal()
    setTimeout(() => {
      itemStore.resetCurrentItem()
      modalStore.$reset()
      v$.value.$reset()
    }, 300)
  }
}
</script>
// CLAUDE-CHECKPOINT: Added track_quantity toggle for stock module
