<template>
  <BasePage>
    <BasePageHeader :title="isEdit ? $t('bills.edit_bill') : $t('bills.new_bill')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('bills.title')" to="/admin/bills" />
        <BaseBreadcrumbItem
          :title="isEdit ? $t('bills.edit_bill') : $t('bills.new_bill')"
          to="#"
          active
        />
      </BaseBreadcrumb>
    </BasePageHeader>

    <BaseCard>
      <form @submit.prevent="handleSubmit">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <BaseInputGroup :label="$t('bills.bill_number')">
            <BaseInput v-model="bill.bill_number" required />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('bills.bill_date')">
            <BaseDatePicker
              v-model="bill.bill_date"
              :calendar-button="true"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('bills.due_date')">
            <BaseDatePicker
              v-model="bill.due_date"
              :calendar-button="true"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('bills.supplier')">
            <BaseSupplierSelectInput
              v-model="bill.supplier_id"
              fetch-all
            />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('bills.currency')">
            <BaseMultiselect
              v-model="bill.currency_id"
              :options="currencies"
              label="name"
              value-prop="id"
              track-by="code"
              :placeholder="$t('customers.select_currency')"
              :can-deselect="false"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('bills.exchange_rate')">
            <BaseInput
              v-model.number="bill.exchange_rate"
              type="number"
              min="0"
              step="0.0001"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('projects.project')">
            <BaseProjectSelectInput
              v-model="bill.project_id"
              :show-action="false"
            />
          </BaseInputGroup>
        </div>

        <div class="mt-6">
          <h3 class="text-sm font-medium text-gray-900">
            {{ $t('bills.items') }}
          </h3>

          <div class="mt-4 space-y-4">
            <div
              v-for="(line, index) in items"
              :key="index"
              class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end"
            >
              <BaseInputGroup :label="$t('bills.item_name')" class="col-span-2">
                <!-- Show selected item or search field -->
                <div v-if="line.item_id" class="relative flex items-center h-10 pl-2 bg-gray-200 border border-gray-200 rounded">
                  {{ line.name }}
                  <span
                    class="absolute text-gray-400 cursor-pointer top-2 right-2"
                    @click="clearItem(index)"
                  >
                    <BaseIcon name="XCircleIcon" />
                  </span>
                </div>
                <BaseMultiselect
                  v-else
                  v-model="line.selectedItem"
                  value-prop="id"
                  track-by="name"
                  label="name"
                  :filter-results="false"
                  :delay="300"
                  searchable
                  :options="searchItems"
                  :placeholder="$t('items.select_a_unit')"
                  @update:model-value="(val) => selectItem(index, val)"
                >
                  <template #option="{ option }">
                    <div class="flex justify-between items-center w-full">
                      <span>{{ option.name }}</span>
                      <span v-if="option.sku" class="text-gray-500 text-xs ml-2">({{ option.sku }})</span>
                    </div>
                  </template>
                </BaseMultiselect>
                <!-- Description below item name -->
                <BaseInput
                  v-model="line.description"
                  class="mt-1"
                  :placeholder="$t('bills.item_description')"
                />
              </BaseInputGroup>

              <BaseInputGroup :label="$t('bills.item_quantity')">
                <BaseInput
                  v-model.number="line.quantity"
                  type="number"
                  min="1"
                />
              </BaseInputGroup>

              <BaseInputGroup :label="$t('bills.item_price')">
                <BaseInput
                  v-model.number="line.price"
                  type="number"
                  min="0"
                  step="0.01"
                />
              </BaseInputGroup>

              <BaseInputGroup :label="$t('bills.item_tax')">
                <BaseMultiselect
                  v-model="line.taxes"
                  :options="taxTypeStore.taxTypes"
                  label="name"
                  value-prop="id"
                  track-by="id"
                  mode="tags"
                  :placeholder="$t('invoices.select_a_tax')"
                  :can-clear="true"
                  :close-on-select="false"
                />
              </BaseInputGroup>

              <!-- Warehouse selector for stock module -->
              <BaseInputGroup v-if="stockEnabled" :label="$t('stock.warehouse')">
                <BaseMultiselect
                  v-model="line.warehouse_id"
                  :content-loading="stockStore.isLoadingWarehouses"
                  value-prop="id"
                  track-by="name"
                  label="name"
                  :options="stockStore.warehouses"
                  :placeholder="$t('stock.select_warehouse')"
                  :can-deselect="true"
                />
              </BaseInputGroup>
            </div>

            <BaseButton
              variant="secondary"
              size="sm"
              type="button"
              @click="addItemRow"
            >
              <template #left="slotProps">
                <BaseIcon name="PlusIcon" :class="slotProps.class" />
              </template>
              {{ $t('bills.add_item') }}
            </BaseButton>
          </div>
        </div>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
          <BaseInputGroup :label="$t('bills.sub_total')">
            <BaseFormatMoney
              :amount="calculatedSubTotal"
              :currency="selectedCurrency"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('bills.discount_percentage')">
            <BaseInput
              v-model.number="bill.discount"
              type="number"
              min="0"
              max="100"
              step="0.01"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('bills.discount_amount')">
            <BaseFormatMoney
              :amount="calculatedDiscountVal"
              :currency="selectedCurrency"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('bills.tax')">
            <BaseFormatMoney
              :amount="calculatedTax"
              :currency="selectedCurrency"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('bills.total')">
            <BaseFormatMoney
              :amount="calculatedTotal"
              :currency="selectedCurrency"
            />
          </BaseInputGroup>
        </div>

        <div class="mt-6 flex justify-end space-x-3">
          <BaseButton variant="secondary" @click="$router.push('/admin/bills')">
            {{ $t('general.cancel') }}
          </BaseButton>
          <BaseButton variant="primary" type="submit">
            {{ isEdit ? $t('general.update') : $t('general.create') }}
          </BaseButton>
        </div>
      </form>
    </BaseCard>
  </BasePage>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useBillsStore } from '@/scripts/admin/stores/bills'
import { useGlobalStore } from '@/scripts/admin/stores/global'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useTaxTypeStore } from '@/scripts/admin/stores/tax-type'
import { useStockStore } from '@/scripts/admin/stores/stock'
import { useItemStore } from '@/scripts/admin/stores/item'

const route = useRoute()
const router = useRouter()
const billsStore = useBillsStore()
const globalStore = useGlobalStore()
const companyStore = useCompanyStore()
const taxTypeStore = useTaxTypeStore()
const stockStore = useStockStore()
const itemStore = useItemStore()

// Stock module integration
const stockEnabled = computed(() => {
  const featureFlags = globalStore.featureFlags || {}
  return featureFlags?.stock?.enabled || featureFlags?.stock || false
})

const currencies = ref([])

const bill = reactive({
  id: null,
  bill_number: '',
  bill_date: '',
  due_date: '',
  supplier_id: null,
  currency_id: null,
  exchange_rate: 1,
  discount: 0,
  scanned_receipt_path: null,
  project_id: null,
})

const items = reactive([
  {
    item_id: null,
    name: '',
    description: '',
    quantity: 1,
    price: 0,
    taxes: [],
    warehouse_id: null, // Stock module: warehouse for stock IN
    track_quantity: false,
    selectedItem: null,
  },
])

// Search items for autocomplete
async function searchItems(query) {
  if (!query || query.length < 1) {
    // Return all items if no query
    const response = await itemStore.fetchItems({ limit: 50 })
    return response?.data?.data || itemStore.items || []
  }
  const response = await itemStore.fetchItems({ search: query, limit: 20 })
  return response?.data?.data || itemStore.items || []
}

// Select item from dropdown
function selectItem(index, item) {
  if (!item) return
  items[index].item_id = item.id
  items[index].name = item.name
  items[index].description = item.description || ''
  // Convert price based on currency precision (MKD has precision 0, no conversion needed)
  const precision = parseInt(companyStore.selectedCompanyCurrency?.precision ?? 2)
  items[index].price = precision === 0 ? item.price : item.price / 100
  items[index].track_quantity = item.track_quantity || false
  items[index].selectedItem = item
}

// Clear selected item
function clearItem(index) {
  items[index].item_id = null
  items[index].name = ''
  items[index].description = ''
  items[index].price = 0
  items[index].track_quantity = false
  items[index].selectedItem = null
}

const isEdit = computed(() => !!route.params.id)

const selectedCurrency = computed(() => {
  return (
    currencies.value.find((c) => c.id === bill.currency_id) ??
    companyStore.selectedCompanyCurrency
  )
})

function hydrateForm(data) {
  bill.id = data.id
  bill.bill_number = data.bill_number
  bill.bill_date = data.bill_date
  bill.due_date = data.due_date
  bill.supplier_id = data.supplier_id
  bill.currency_id = data.currency_id
  bill.exchange_rate = data.exchange_rate || 1
  bill.discount = data.discount || 0
  bill.scanned_receipt_path = data.scanned_receipt_path || null
  bill.project_id = data.project_id || null

  if (data.items && data.items.length) {
    items.splice(0, items.length, ...data.items.map((i) => ({
      name: i.name,
      description: i.description,
      quantity: i.quantity,
      price: i.price,
      tax_rate: 0,
    })))
  }
}

const calculatedSubTotal = computed(() =>
  items.reduce(
    (sum, line) => sum + (Number(line.quantity) || 0) * (Number(line.price) || 0),
    0
  )
)

const calculatedTax = computed(() =>
  items.reduce((sum, line) => {
    const qty = Number(line.quantity) || 0
    const price = Number(line.price) || 0
    const lineSubtotal = qty * price

    // Calculate tax based on selected tax types
    const lineTax = (line.taxes || []).reduce((taxSum, taxId) => {
      const taxType = taxTypeStore.taxTypes.find(t => t.id === taxId)
      if (taxType) {
        return taxSum + (lineSubtotal * Number(taxType.percent)) / 100
      }
      return taxSum
    }, 0)

    return sum + lineTax
  }, 0)
)

const calculatedDiscountVal = computed(() => {
  const discountRate = Number(bill.discount) || 0
  return (calculatedSubTotal.value * discountRate) / 100
})

const calculatedTotal = computed(
  () =>
    calculatedSubTotal.value - calculatedDiscountVal.value + calculatedTax.value
)

function addItemRow() {
  items.push({
    item_id: null,
    name: '',
    description: '',
    quantity: 1,
    price: 0,
    taxes: [],
    warehouse_id: null, // Stock module: warehouse for stock IN
    track_quantity: false,
    selectedItem: null,
  })
}

function buildPayload() {
  const subTotal = calculatedSubTotal.value
  const discountVal = calculatedDiscountVal.value
  const taxAmount = calculatedTax.value
  const total = calculatedTotal.value

  return {
    id: bill.id,
    bill_number: bill.bill_number,
    bill_date: bill.bill_date,
    due_date: bill.due_date,
    supplier_id: bill.supplier_id,
    currency_id: bill.currency_id,
    exchange_rate: bill.exchange_rate,
    discount: bill.discount || 0,
    project_id: bill.project_id,
    discount_val: Math.round(discountVal),
    sub_total: Math.round(subTotal),
    tax: Math.round(taxAmount),
    total: Math.round(total),
    items: items.map((line) => {
      const lineSubTotal =
        (Number(line.quantity) || 0) * (Number(line.price) || 0)

      // Calculate total tax for this line item
      const lineTax = (line.taxes || []).reduce((taxSum, taxId) => {
        const taxType = taxTypeStore.taxTypes.find(t => t.id === taxId)
        if (taxType) {
          return taxSum + (lineSubTotal * Number(taxType.percent)) / 100
        }
        return taxSum
      }, 0)

      const lineTotal = lineSubTotal + lineTax

      // Build taxes array with details
      const itemTaxes = (line.taxes || []).map(taxId => {
        const taxType = taxTypeStore.taxTypes.find(t => t.id === taxId)
        if (taxType) {
          const taxAmount = (lineSubTotal * Number(taxType.percent)) / 100
          return {
            tax_type_id: taxType.id,
            name: taxType.name,
            percent: Number(taxType.percent),
            amount: Math.round(taxAmount),
            compound_tax: taxType.compound_tax || 0,
          }
        }
        return null
      }).filter(Boolean)

      return {
        item_id: line.item_id || null,
        name: line.name || line.description || 'Item',
        description: line.description,
        quantity: Number(line.quantity) || 0,
        price: Number(line.price) || 0,
        discount: 0,
        discount_val: 0,
        tax: Math.round(lineTax),
        total: Math.round(lineTotal),
        taxes: itemTaxes,
        warehouse_id: line.warehouse_id || null, // Stock module: warehouse for stock IN
      }
    }),
  }
}

function handleSubmit() {
  const payload = buildPayload()
  if (isEdit.value) {
    billsStore.updateBill(payload).then(() => {
      router.push('/admin/bills')
    })
  } else {
    billsStore.createBill(payload).then(() => {
      router.push('/admin/bills')
    })
  }
}

onMounted(async () => {
  globalStore.fetchCurrencies().then((res) => {
    currencies.value = res.data.data || globalStore.currencies

    if (!bill.currency_id && companyStore.selectedCompanyCurrency) {
      bill.currency_id = companyStore.selectedCompanyCurrency.id
    }
  })

  // Fetch tax types
  taxTypeStore.fetchTaxTypes()

  // Stock module: fetch warehouses if enabled
  if (stockEnabled.value) {
    try {
      await stockStore.fetchWarehouses()
    } catch (err) {
      console.warn('Could not load warehouses for stock selector')
    }
  }

  if (isEdit.value) {
    billsStore.fetchBill(route.params.id).then((response) => {
      hydrateForm(response.data.data)
    })
  } else {
    // Pre-fill from query params (e.g., from receipt scanner)
    if (route.query.scanned_receipt_path) {
      bill.scanned_receipt_path = route.query.scanned_receipt_path
    }
  }
})
</script>
// CLAUDE-CHECKPOINT
