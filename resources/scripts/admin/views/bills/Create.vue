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
        <!-- Section 1: Basic Info -->
        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">
          {{ $t('bills.bill_information', 'Bill Information') }}
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <BaseInputGroup
            :label="$t('bills.bill_number')"
            :error="validationErrors.bill_number"
          >
            <BaseInput v-model="bill.bill_number" required />
          </BaseInputGroup>

          <BaseInputGroup
            :label="$t('bills.supplier')"
            :error="validationErrors.supplier_id"
          >
            <BaseSupplierSelectInput
              v-model="bill.supplier_id"
              fetch-all
              show-action
            />
          </BaseInputGroup>

          <BaseInputGroup
            :label="$t('bills.bill_date')"
            :error="validationErrors.bill_date"
          >
            <BaseDatePicker
              v-model="bill.bill_date"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('bills.supply_date')">
            <BaseDatePicker
              v-model="bill.supply_date"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('bills.due_date')">
            <BaseDatePicker
              v-model="bill.due_date"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('bills.payment_terms')">
            <BaseMultiselect
              v-model="bill.payment_terms_days"
              :options="paymentTermsOptions"
              label="label"
              value-prop="value"
              track-by="value"
              :placeholder="$t('bills.payment_terms')"
              :can-deselect="true"
            />
          </BaseInputGroup>
        </div>

        <!-- Section 2: Currency & Project -->
        <div class="border-t border-gray-100 mt-6 pt-4">
          <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">
            {{ $t('bills.currency', 'Currency') }} & {{ $t('projects.project', 'Project') }}
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
        </div>

        <!-- Section 3: Compliance -->
        <div class="border-t border-gray-100 mt-6 pt-4">
          <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">
            {{ $t('bills.compliance_fields', 'Compliance') }}
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <BaseInputGroup :label="$t('bills.place_of_issue')">
              <BaseInput
                v-model="bill.place_of_issue"
                :placeholder="$t('bills.place_of_issue_placeholder')"
              />
            </BaseInputGroup>

            <BaseInputGroup :label="$t('invoices.reverse_charge')">
              <div class="flex items-center gap-2 mt-1">
                <BaseSwitch
                  v-model="bill.is_reverse_charge"
                />
                <span v-if="bill.is_reverse_charge" class="text-xs text-red-600 font-medium">
                  {{ $t('invoices.reverse_charge_notice') }}
                </span>
              </div>
            </BaseInputGroup>
          </div>
        </div>

        <div class="mt-6">
          <!-- Barcode Scanner Mode Toggle -->
          <div class="mb-4">
            <ScannerModeToggle
              :is-enabled="scannerEnabled"
              :is-processing="scannerProcessing"
              :last-scanned-item="lastScannedItem"
              :error="scannerError"
              :scan-count="scanCount"
              @toggle="toggleScanner"
            />
          </div>

          <h3 class="text-sm font-medium text-gray-900">
            {{ $t('bills.items') }}
          </h3>

          <div class="mt-4 space-y-4">
            <div
              v-for="(line, index) in items"
              :key="index"
              class="relative grid grid-cols-2 md:grid-cols-6 gap-3 md:gap-4 items-end border border-gray-100 rounded-md p-3"
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
                <template v-else>
                  <div v-if="!line.manualEntry">
                    <BaseMultiselect
                      v-model="line.selectedItem"
                      value-prop="id"
                      track-by="name"
                      label="name"
                      :filter-results="false"
                      :delay="300"
                      searchable
                      object
                      :options="searchItems"
                      :placeholder="$t('items.search_item')"
                      @update:model-value="(val) => selectItem(index, val)"
                    >
                      <template #option="{ option }">
                        <div class="flex justify-between items-center w-full">
                          <span>{{ option.name }}</span>
                          <span v-if="option.sku" class="text-gray-500 text-xs ml-2">({{ option.sku }})</span>
                        </div>
                      </template>
                    </BaseMultiselect>
                    <button
                      type="button"
                      class="text-xs text-primary-500 hover:text-primary-700 mt-1"
                      @click="line.manualEntry = true"
                    >
                      {{ $t('bills.manual_entry', 'Enter manually') }}
                    </button>
                  </div>
                  <div v-else>
                    <BaseInput
                      v-model="line.name"
                      :placeholder="$t('bills.item_name')"
                    />
                    <button
                      type="button"
                      class="text-xs text-primary-500 hover:text-primary-700 mt-1"
                      @click="line.manualEntry = false"
                    >
                      {{ $t('bills.search_catalog', 'Search catalog') }}
                    </button>
                  </div>
                </template>
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
                  min="0"
                  step="any"
                />
                <span v-if="line.unit_name" class="text-xs text-gray-500 mt-1">
                  {{ line.unit_name }}
                </span>
              </BaseInputGroup>

              <BaseInputGroup :label="$t('bills.item_price')">
                <BaseMoney
                  v-model="line.price"
                  :currency="selectedCurrency"
                />
              </BaseInputGroup>

              <BaseInputGroup :label="$t('bills.item_tax_rate')">
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

              <!-- Remove item row button -->
              <div
                v-if="items.length > 1"
                class="absolute top-1 right-1"
              >
                <button
                  type="button"
                  class="p-1 text-gray-400 hover:text-red-500 transition-colors"
                  @click="removeItemRow(index)"
                >
                  <BaseIcon name="TrashIcon" class="w-4 h-4" />
                </button>
              </div>
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

        <!-- Notes -->
        <div class="mt-6">
          <BaseInputGroup :label="$t('general.notes')">
            <textarea
              v-model="bill.notes"
              rows="3"
              :placeholder="$t('general.notes')"
              class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm"
            />
          </BaseInputGroup>
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
          <BaseButton variant="secondary" type="button" @click="$router.push('/admin/bills')">
            {{ $t('general.cancel') }}
          </BaseButton>
          <BaseButton
            variant="primary"
            type="submit"
            :loading="isSaving"
            :disabled="isSaving"
          >
            {{ isEdit ? $t('general.update') : $t('general.create') }}
          </BaseButton>
        </div>
      </form>
    </BaseCard>

    <DuplicateWarningModal
      :show="showDuplicateWarning"
      :duplicates="duplicateRecords"
      entity-type="bills"
      @close="closeDuplicateWarning"
      @confirm="saveWithDuplicate"
    />
  </BasePage>
</template>

<script setup>
import { reactive, ref, computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useBillsStore } from '@/scripts/admin/stores/bills'
import { useGlobalStore } from '@/scripts/admin/stores/global'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useTaxTypeStore } from '@/scripts/admin/stores/tax-type'
import { useStockStore } from '@/scripts/admin/stores/stock'
import { useItemStore } from '@/scripts/admin/stores/item'
import { useReceiptScannerStore } from '@/scripts/admin/stores/receipt-scanner'
import { useNotificationStore } from '@/scripts/stores/notification'
import ScannerModeToggle from '@/scripts/admin/components/ScannerModeToggle.vue'
import DuplicateWarningModal from '@/scripts/admin/components/modal-components/DuplicateWarningModal.vue'
import { useBarcodeScanner } from '@/scripts/admin/composables/useBarcodeScanner'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const billsStore = useBillsStore()
const globalStore = useGlobalStore()
const companyStore = useCompanyStore()
const taxTypeStore = useTaxTypeStore()
const stockStore = useStockStore()
const itemStore = useItemStore()
const receiptScannerStore = useReceiptScannerStore()
const notificationStore = useNotificationStore()

const isSaving = ref(false)
const showDuplicateWarning = ref(false)
const duplicateRecords = ref([])
const validationErrors = reactive({
  bill_number: '',
  bill_date: '',
  supplier_id: '',
})

// Stock module integration
const stockEnabled = computed(() => {
  const featureFlags = globalStore.featureFlags || {}
  return featureFlags?.stock?.enabled || featureFlags?.stock || false
})

// Barcode Scanner Integration
function handleScannedItem(item) {
  // Find an empty row or add a new one
  let targetIndex = items.findIndex(line => !line.item_id && !line.name)

  if (targetIndex === -1) {
    // No empty row, add a new one
    addItemRow()
    targetIndex = items.length - 1
  }

  // Populate the item row
  selectItem(targetIndex, item)
}

const {
  isEnabled: scannerEnabled,
  isProcessing: scannerProcessing,
  lastScannedItem,
  error: scannerError,
  scanCount,
  toggleScanner
} = useBarcodeScanner({
  onItemFound: handleScannedItem,
  playSound: true
})

const currencies = ref([])

// Today's date in YYYY-MM-DD format
const today = new Date().toISOString().split('T')[0]

const bill = reactive({
  id: null,
  bill_number: '',
  bill_date: today,
  supply_date: null,
  due_date: '',
  payment_terms_days: null,
  supplier_id: null,
  currency_id: null,
  exchange_rate: 1,
  discount: 0,
  notes: '',
  scanned_receipt_path: null,
  project_id: null,
  is_reverse_charge: false,
  place_of_issue: '',
})

const paymentTermsOptions = computed(() => [
  { label: `15 ${t('bills.days', 'дена')}`, value: 15 },
  { label: `30 ${t('bills.days', 'дена')}`, value: 30 },
  { label: `45 ${t('bills.days', 'дена')}`, value: 45 },
  { label: `60 ${t('bills.days', 'дена')}`, value: 60 },
  { label: `90 ${t('bills.days', 'дена')}`, value: 90 },
])

// When bill_date changes, default supply_date if not set
watch(() => bill.bill_date, (newDate) => {
  if (newDate && !bill.supply_date) {
    bill.supply_date = newDate
  }
})

// When payment_terms_days changes, auto-calculate due_date
watch(() => bill.payment_terms_days, (days) => {
  if (days && bill.bill_date) {
    const parts = bill.bill_date.split('-')
    const base = new Date(
      parseInt(parts[0], 10),
      parseInt(parts[1], 10) - 1,
      parseInt(parts[2], 10)
    )
    base.setDate(base.getDate() + days)
    const y = base.getFullYear()
    const m = String(base.getMonth() + 1).padStart(2, '0')
    const d = String(base.getDate()).padStart(2, '0')
    bill.due_date = `${y}-${m}-${d}`
  }
})

const items = reactive([
  createEmptyItem(),
])

function createEmptyItem() {
  return {
    item_id: null,
    name: '',
    description: '',
    quantity: 1,
    price: 0,
    taxes: [],
    warehouse_id: null,
    track_quantity: false,
    selectedItem: null,
    unit_name: '',
    manualEntry: false,
  }
}

// Search items for autocomplete
async function searchItems(query) {
  try {
    if (!query || query.length < 1) {
      await itemStore.fetchItems({ limit: 50 })
      return itemStore.items || []
    }
    await itemStore.fetchItems({ search: query, limit: 20 })
    return itemStore.items || []
  } catch (error) {
    console.error('Error searching items:', error)
    return itemStore.items || []
  }
}

// Select item from dropdown
function selectItem(index, item) {
  if (!item) return

  items[index].item_id = item.id
  items[index].name = item.name
  items[index].description = item.description || ''
  // Item prices are stored in cents in the DB.
  // BaseMoney v-model works in display format (dollars/denars), so divide by 100.
  items[index].price = item.price / 100
  items[index].track_quantity = item.track_quantity || false
  items[index].unit_name = item.unit?.name || item.unit_name || ''
  items[index].selectedItem = item

  // Auto-fill taxes from item (extract tax_type_id for multiselect)
  if (item.taxes && item.taxes.length > 0) {
    const taxIds = item.taxes.map(t => t.tax_type_id)
    items[index].taxes = taxIds
  }
}

// Clear selected item
function clearItem(index) {
  items[index].item_id = null
  items[index].name = ''
  items[index].description = ''
  items[index].price = 0
  items[index].track_quantity = false
  items[index].unit_name = ''
  items[index].selectedItem = null
  items[index].taxes = []
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
  bill.notes = data.notes || ''
  bill.scanned_receipt_path = data.scanned_receipt_path || null
  bill.project_id = data.project_id || null
  bill.supply_date = data.supply_date || null
  bill.place_of_issue = data.place_of_issue || ''
  bill.payment_terms_days = data.payment_terms_days || null
  bill.is_reverse_charge = data.is_reverse_charge || false

  if (data.items && data.items.length) {
    items.splice(0, items.length, ...data.items.map((i) => {
      // Extract tax IDs from the item's taxes relationship
      const taxIds = (i.taxes || []).map(t => t.tax_type_id).filter(Boolean)

      return {
        item_id: i.item_id || null,
        name: i.name || '',
        description: i.description || '',
        quantity: i.quantity || 1,
        // API returns price in cents — convert to display format for BaseMoney
        price: (i.price || 0) / 100,
        taxes: taxIds,
        warehouse_id: i.warehouse_id || null,
        track_quantity: i.track_quantity || false,
        unit_name: i.unit?.name || i.unit_name || '',
        selectedItem: i.item_id ? { id: i.item_id, name: i.name } : null,
      }
    }))
  }
}

// Helper: convert display price to cents
function priceToCents(displayPrice) {
  return Math.round((Number(displayPrice) || 0) * 100)
}

// Helper: compute line-level tax in cents with compound tax support
function computeLineTax(line) {
  const qty = Number(line.quantity) || 0
  const priceCents = priceToCents(line.price)
  const lineSubtotalCents = Math.round(qty * priceCents)
  const taxIds = line.taxes || []

  // First pass: simple (non-compound) taxes
  let simpleTaxCents = 0
  taxIds.forEach(taxId => {
    const taxType = taxTypeStore.taxTypes.find(t => t.id === taxId)
    if (taxType && !taxType.compound_tax) {
      simpleTaxCents += Math.round(lineSubtotalCents * Number(taxType.percent) / 100)
    }
  })

  // Second pass: compound taxes (applied on subtotal + simple taxes)
  let compoundTaxCents = 0
  taxIds.forEach(taxId => {
    const taxType = taxTypeStore.taxTypes.find(t => t.id === taxId)
    if (taxType && taxType.compound_tax) {
      compoundTaxCents += Math.round((lineSubtotalCents + simpleTaxCents) * Number(taxType.percent) / 100)
    }
  })

  return simpleTaxCents + compoundTaxCents
}

// All computed values return CENTS (BaseFormatMoney divides by 100 for display)
const calculatedSubTotal = computed(() =>
  items.reduce((sum, line) => {
    const qty = Number(line.quantity) || 0
    const priceCents = priceToCents(line.price)
    return sum + Math.round(qty * priceCents)
  }, 0)
)

const calculatedTax = computed(() =>
  items.reduce((sum, line) => sum + computeLineTax(line), 0)
)

const calculatedDiscountVal = computed(() => {
  const discountRate = Number(bill.discount) || 0
  return Math.round(calculatedSubTotal.value * discountRate / 100)
})

const calculatedTotal = computed(() =>
  calculatedSubTotal.value - calculatedDiscountVal.value + calculatedTax.value
)

function addItemRow() {
  items.push(createEmptyItem())
}

function removeItemRow(index) {
  if (items.length > 1) {
    items.splice(index, 1)
  }
}

function validateForm() {
  let isValid = true
  validationErrors.bill_number = ''
  validationErrors.bill_date = ''
  validationErrors.supplier_id = ''

  if (!bill.bill_number || !bill.bill_number.trim()) {
    validationErrors.bill_number = window.i18n.global.t('validation.required')
    isValid = false
  }

  if (!bill.bill_date) {
    validationErrors.bill_date = window.i18n.global.t('validation.required')
    isValid = false
  }

  if (!bill.supplier_id) {
    validationErrors.supplier_id = window.i18n.global.t('validation.required')
    isValid = false
  }

  // Check at least one item has a name
  const hasValidItem = items.some(line => (line.name && line.name.trim()) || line.item_id)
  if (!hasValidItem) {
    notificationStore.showNotification({
      type: 'error',
      message: window.i18n.global.t('bills.item_name') + ' ' + window.i18n.global.t('validation.required'),
    })
    isValid = false
  }

  return isValid
}

function buildPayload() {
  return {
    id: bill.id,
    bill_number: bill.bill_number,
    bill_date: bill.bill_date,
    due_date: bill.due_date,
    supplier_id: bill.supplier_id,
    currency_id: bill.currency_id,
    exchange_rate: bill.exchange_rate,
    discount: bill.discount || 0,
    notes: bill.notes || '',
    project_id: bill.project_id,
    scanned_receipt_path: bill.scanned_receipt_path || null,
    is_reverse_charge: bill.is_reverse_charge || false,
    supply_date: bill.supply_date || null,
    place_of_issue: bill.place_of_issue || '',
    payment_terms_days: bill.payment_terms_days || null,
    // All amounts in cents
    discount_val: calculatedDiscountVal.value,
    sub_total: calculatedSubTotal.value,
    tax: calculatedTax.value,
    total: calculatedTotal.value,
    items: items
      .filter(line => (line.name && line.name.trim()) || line.item_id)
      .map((line) => {
        const qty = Number(line.quantity) || 0
        const priceCents = priceToCents(line.price)
        const lineSubtotalCents = Math.round(qty * priceCents)
        const lineTaxCents = computeLineTax(line)
        const lineTotalCents = lineSubtotalCents + lineTaxCents

        // Build taxes array with per-tax amounts in cents
        const itemTaxes = (line.taxes || []).map(taxId => {
          const taxType = taxTypeStore.taxTypes.find(t => t.id === taxId)
          if (!taxType) return null

          let taxAmountCents
          if (taxType.compound_tax) {
            // Compound: calculate simple taxes first, then apply on subtotal + simple
            let simpleTax = 0
            ;(line.taxes || []).forEach(tid => {
              const tt = taxTypeStore.taxTypes.find(t => t.id === tid)
              if (tt && !tt.compound_tax) {
                simpleTax += Math.round(lineSubtotalCents * Number(tt.percent) / 100)
              }
            })
            taxAmountCents = Math.round((lineSubtotalCents + simpleTax) * Number(taxType.percent) / 100)
          } else {
            taxAmountCents = Math.round(lineSubtotalCents * Number(taxType.percent) / 100)
          }

          return {
            tax_type_id: taxType.id,
            name: taxType.name,
            percent: Number(taxType.percent),
            amount: taxAmountCents,
            compound_tax: taxType.compound_tax || 0,
          }
        }).filter(Boolean)

        return {
          item_id: line.item_id || null,
          name: line.name || line.description || 'Item',
          description: line.description || '',
          quantity: qty,
          price: priceCents,
          discount: 0,
          discount_val: 0,
          tax: lineTaxCents,
          total: lineTotalCents,
          taxes: itemTaxes,
          warehouse_id: line.warehouse_id || null,
        }
      }),
  }
}

async function handleSubmit(allowDuplicate = false) {
  if (typeof allowDuplicate !== 'boolean') allowDuplicate = false
  if (!validateForm()) return

  isSaving.value = true

  try {
    const payload = buildPayload()
    if (isEdit.value) {
      await billsStore.updateBill(payload)
    } else {
      const response = await billsStore.createBill(payload, allowDuplicate)

      if (response?.data?.is_duplicate_warning) {
        isSaving.value = false
        duplicateRecords.value = response.data.duplicates || []
        showDuplicateWarning.value = true
        return
      }
    }
    router.push('/admin/bills')
  } catch (err) {
    // Error notification handled by store's handleError
    console.error('Bill save error:', err)
  } finally {
    isSaving.value = false
  }
}

function closeDuplicateWarning() {
  showDuplicateWarning.value = false
  duplicateRecords.value = []
}

function saveWithDuplicate() {
  closeDuplicateWarning()
  handleSubmit(true)
}

onMounted(async () => {
  globalStore.fetchCurrencies().then((res) => {
    // Handle both cached (array) and fresh (response object) results
    currencies.value = res?.data?.data || globalStore.currencies || []

    if (!bill.currency_id && companyStore.selectedCompanyCurrency) {
      bill.currency_id = companyStore.selectedCompanyCurrency.id
    }
  })

  // Fetch all tax types (must complete before form hydration)
  await taxTypeStore.fetchTaxTypes({ limit: 'all' })

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
    // Pre-fill from receipt scanner store (full scanned data with line items)
    const scannedData = receiptScannerStore.consumeScannedBillData()
    if (scannedData) {
      const sb = scannedData.bill
      bill.bill_number = sb.bill_number || ''
      bill.bill_date = sb.bill_date || ''
      bill.due_date = sb.due_date || ''
      bill.scanned_receipt_path = sb.scanned_receipt_path || null

      // Pre-fill line items from scan
      if (scannedData.items && scannedData.items.length > 0) {
        items.splice(0, items.length, ...scannedData.items.map((si) => ({
          item_id: null,
          name: si.name || '',
          description: si.description || '',
          quantity: si.quantity || 1,
          price: si.price || 0,
          taxes: [],
          warehouse_id: null,
          track_quantity: false,
          selectedItem: null,
        })))
      }
    }

    // Legacy: pre-fill from query params
    if (route.query.scanned_receipt_path && !scannedData) {
      bill.scanned_receipt_path = route.query.scanned_receipt_path
    }

    // Pre-fill item from low stock reorder action
    if (route.query.prefill_item_id) {
      const itemId = parseInt(route.query.prefill_item_id)
      const itemName = route.query.prefill_item_name || ''
      const quantity = parseInt(route.query.prefill_quantity) || 1

      // Fetch the item to get full details
      itemStore.fetchItem(itemId).then(() => {
        const item = itemStore.currentItem
        if (item && item.id) {
          selectItem(0, item)
          items[0].quantity = quantity
        } else {
          // Fallback: just set name and quantity if item fetch fails
          items[0].item_id = itemId
          items[0].name = itemName
          items[0].quantity = quantity
        }
      }).catch(() => {
        // Fallback on error
        items[0].item_id = itemId
        items[0].name = itemName
        items[0].quantity = quantity
      })
    }

    // Pre-fill from AI draft
    if (route.query.draft_id && !scannedData) {
      loadAiDraft(route.query.draft_id)
    }
  }
})

/**
 * Load AI-generated draft data to pre-fill the bill form.
 */
async function loadAiDraft(draftId) {
  try {
    const axios = (await import('axios')).default
    const { data } = await axios.get(`/ai/drafts/${draftId}`)
    const d = data.entity_data || {}

    // Pre-fill supplier (search by name)
    if (d.supplier_id) {
      bill.supplier_id = d.supplier_id
    }

    // Pre-fill dates
    if (d.date) bill.bill_date = d.date
    if (d.due_date) bill.due_date = d.due_date
    if (d.notes) bill.notes = d.notes

    // Pre-fill items
    if (d.items?.length > 0) {
      items.splice(0, items.length, ...d.items.map((item) => ({
        item_id: item.item_id || null,
        name: item.name || '',
        description: item.description || '',
        quantity: item.quantity || 1,
        price: (item.unit_price || 0) / 100, // Convert cents to display
        taxes: [],
        warehouse_id: null,
        track_quantity: false,
        selectedItem: null,
      })))
    }

    // Mark draft as used
    try { await axios.post(`/ai/drafts/${draftId}/use`) } catch (e) { /* non-critical */ }
  } catch (err) {
    console.error('[AI Draft] Failed to load bill draft:', err)
  }
}
</script>
