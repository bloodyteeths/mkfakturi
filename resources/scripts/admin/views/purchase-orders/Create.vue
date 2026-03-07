<template>
  <BasePage>
    <BasePageHeader :title="t('new_po')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="t('title')" to="../purchase-orders" />
        <BaseBreadcrumbItem :title="t('new_po')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <div class="space-y-6">
      <!-- Header Fields -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <!-- Supplier -->
          <BaseInputGroup :label="t('supplier')">
            <BaseMultiselect
              v-model="form.supplier_id"
              :options="suppliers"
              :searchable="true"
              label="name"
              value-prop="id"
              :placeholder="t('select_supplier')"
              :loading="isLoadingSuppliers"
            />
          </BaseInputGroup>

          <!-- PO Date -->
          <BaseInputGroup :label="t('date')" required>
            <BaseDatePicker
              v-model="form.po_date"
              :calendar-button="true"
              calendar-button-icon="CalendarDaysIcon"
            />
          </BaseInputGroup>

          <!-- Expected Delivery -->
          <BaseInputGroup :label="t('expected_delivery')">
            <BaseDatePicker
              v-model="form.expected_delivery_date"
              :calendar-button="true"
              calendar-button-icon="CalendarDaysIcon"
            />
          </BaseInputGroup>

          <!-- Warehouse -->
          <BaseInputGroup :label="t('warehouse')">
            <BaseMultiselect
              v-model="form.warehouse_id"
              :options="warehouses"
              :searchable="true"
              label="name"
              value-prop="id"
              :placeholder="t('select_warehouse')"
            />
          </BaseInputGroup>

          <!-- Notes -->
          <div class="md:col-span-2">
            <BaseInputGroup :label="t('notes')">
              <textarea
                v-model="form.notes"
                rows="2"
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm"
                :placeholder="t('notes_placeholder')"
              />
            </BaseInputGroup>
          </div>
        </div>
      </div>

      <!-- Items Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
          <h3 class="text-sm font-semibold text-gray-700">{{ t('items') }}</h3>
          <BaseButton variant="primary-outline" size="sm" @click="addItem">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ t('add_item') }}
          </BaseButton>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-12">#</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('item_name') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-28">{{ t('quantity') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-32">{{ t('price') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-28">{{ t('item_tax') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-32">{{ t('item_total') }}</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-16"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="(item, index) in form.items" :key="index" class="hover:bg-gray-50">
                <td class="px-4 py-3 text-sm text-gray-500">{{ index + 1 }}</td>
                <td class="px-4 py-3">
                  <div class="flex flex-col space-y-1">
                    <BaseMultiselect
                      v-model="item.item_id"
                      :options="inventoryItems"
                      :searchable="true"
                      label="name"
                      value-prop="id"
                      :placeholder="t('select_item')"
                      @update:model-value="onItemSelect(index, $event)"
                    />
                    <input
                      v-model="item.name"
                      type="text"
                      class="w-full text-sm border-gray-300 rounded focus:ring-primary-500 focus:border-primary-500"
                      :placeholder="t('item_name')"
                    />
                  </div>
                </td>
                <td class="px-4 py-3">
                  <input
                    v-model.number="item.quantity"
                    type="number"
                    min="0.0001"
                    step="0.01"
                    class="w-full text-right text-sm border-gray-300 rounded focus:ring-primary-500 focus:border-primary-500"
                  />
                </td>
                <td class="px-4 py-3">
                  <input
                    :value="item.price / 100"
                    type="number"
                    min="0"
                    step="0.01"
                    class="w-full text-right text-sm border-gray-300 rounded focus:ring-primary-500 focus:border-primary-500"
                    @input="item.price = Math.round(parseFloat($event.target.value || 0) * 100)"
                  />
                </td>
                <td class="px-4 py-3">
                  <input
                    :value="item.tax / 100"
                    type="number"
                    min="0"
                    step="0.01"
                    class="w-full text-right text-sm border-gray-300 rounded focus:ring-primary-500 focus:border-primary-500"
                    @input="item.tax = Math.round(parseFloat($event.target.value || 0) * 100)"
                  />
                </td>
                <td class="px-4 py-3 text-right text-sm font-medium text-gray-900">
                  {{ formatMoney(itemTotal(item)) }}
                </td>
                <td class="px-4 py-3 text-center">
                  <button
                    v-if="form.items.length > 1"
                    class="text-red-400 hover:text-red-600"
                    @click="removeItem(index)"
                  >
                    <BaseIcon name="TrashIcon" class="h-4 w-4" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Totals -->
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
          <div class="flex justify-end space-x-8">
            <div class="text-right">
              <p class="text-xs text-gray-500 uppercase">{{ t('sub_total') }}</p>
              <p class="text-sm font-medium text-gray-900">{{ formatMoney(computedSubTotal) }}</p>
            </div>
            <div class="text-right">
              <p class="text-xs text-gray-500 uppercase">{{ t('tax_amount') }}</p>
              <p class="text-sm font-medium text-gray-900">{{ formatMoney(computedTax) }}</p>
            </div>
            <div class="text-right">
              <p class="text-xs text-gray-500 uppercase font-bold">{{ t('total') }}</p>
              <p class="text-lg font-bold text-primary-600">{{ formatMoney(computedTotal) }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex justify-between">
        <router-link to="../purchase-orders">
          <BaseButton variant="primary-outline">
            <template #left="slotProps">
              <BaseIcon name="ArrowLeftIcon" :class="slotProps.class" />
            </template>
            {{ t('back') }}
          </BaseButton>
        </router-link>

        <BaseButton
          variant="primary"
          :loading="isSaving"
          :disabled="!canSave"
          @click="savePurchaseOrder"
        >
          <template #left="slotProps">
            <BaseIcon name="CheckIcon" :class="slotProps.class" />
          </template>
          {{ t('save_draft') }}
        </BaseButton>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/scripts/stores/notification'
import poMessages from '@/scripts/admin/i18n/purchase-orders.js'

const router = useRouter()
const notificationStore = useNotificationStore()

const locale = document.documentElement.lang || 'mk'
function t(key) {
  return poMessages[locale]?.purchaseOrders?.[key]
    || poMessages['en']?.purchaseOrders?.[key]
    || key
}

// State
const isSaving = ref(false)
const isLoadingSuppliers = ref(false)
const suppliers = ref([])
const warehouses = ref([])
const inventoryItems = ref([])

function getLocalDateString(date = new Date()) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const form = reactive({
  supplier_id: null,
  po_date: getLocalDateString(),
  expected_delivery_date: null,
  warehouse_id: null,
  notes: '',
  items: [
    { item_id: null, name: '', quantity: 1, price: 0, tax: 0 },
  ],
})

// Computed
const computedSubTotal = computed(() => {
  return form.items.reduce((sum, item) => {
    return sum + (item.price * item.quantity)
  }, 0)
})

const computedTax = computed(() => {
  return form.items.reduce((sum, item) => {
    return sum + (item.tax || 0)
  }, 0)
})

const computedTotal = computed(() => {
  return computedSubTotal.value + computedTax.value
})

const canSave = computed(() => {
  return form.po_date
    && form.items.length > 0
    && form.items.every(item => item.name && item.quantity > 0)
})

// Methods
const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }
const fmtLocale = localeMap[locale] || 'mk-MK'

function formatMoney(cents) {
  if (cents === null || cents === undefined) return '-'
  return new Intl.NumberFormat(fmtLocale, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(cents / 100)
}

function itemTotal(item) {
  return (item.price * item.quantity) + (item.tax || 0)
}

function addItem() {
  form.items.push({ item_id: null, name: '', quantity: 1, price: 0, tax: 0 })
}

function removeItem(index) {
  form.items.splice(index, 1)
}

function onItemSelect(index, itemId) {
  if (!itemId) return
  const selectedItem = inventoryItems.value.find(i => i.id === itemId)
  if (selectedItem) {
    form.items[index].name = selectedItem.name
    // Price comes from Item model as cents (cost field for purchase price)
    form.items[index].price = selectedItem.cost || selectedItem.price || 0
  }
}

async function fetchSuppliers() {
  isLoadingSuppliers.value = true
  try {
    const response = await window.axios.get('/suppliers', { params: { limit: 'all' } })
    suppliers.value = response.data?.suppliers?.data || response.data?.data || []
  } catch {
    suppliers.value = []
  } finally {
    isLoadingSuppliers.value = false
  }
}

async function fetchWarehouses() {
  try {
    const response = await window.axios.get('/stock/warehouses', { params: { limit: 'all' } })
    warehouses.value = response.data?.data || response.data?.warehouses || []
  } catch {
    warehouses.value = []
  }
}

async function fetchItems() {
  try {
    const response = await window.axios.get('/items', { params: { limit: 'all' } })
    inventoryItems.value = response.data?.items?.data || response.data?.data || []
  } catch {
    inventoryItems.value = []
  }
}

async function savePurchaseOrder() {
  isSaving.value = true
  try {
    const payload = {
      supplier_id: form.supplier_id,
      po_date: form.po_date,
      expected_delivery_date: form.expected_delivery_date,
      warehouse_id: form.warehouse_id,
      notes: form.notes,
      items: form.items.map(item => ({
        item_id: item.item_id,
        name: item.name,
        quantity: item.quantity,
        price: item.price,
        tax: item.tax || 0,
      })),
    }

    const response = await window.axios.post('/purchase-orders', payload)

    notificationStore.showNotification({
      type: 'success',
      message: response.data?.message || t('created_success') || 'Purchase order created',
    })

    const poId = response.data?.data?.id
    if (poId) {
      router.push({ path: `/admin/purchase-orders/${poId}` })
    } else {
      router.push({ path: '/admin/purchase-orders' })
    }
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_creating') || 'Failed to create purchase order',
    })
  } finally {
    isSaving.value = false
  }
}

// Lifecycle
onMounted(() => {
  fetchSuppliers()
  fetchWarehouses()
  fetchItems()
})
</script>

<!-- CLAUDE-CHECKPOINT -->
