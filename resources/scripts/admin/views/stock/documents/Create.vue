<template>
  <BasePage>
    <BasePageHeader :title="isEdit ? 'Уреди документ' : 'Нов магацински документ'">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('stock.title')" to="/admin/stock" />
        <BaseBreadcrumbItem title="Документи" to="/admin/stock/documents" />
        <BaseBreadcrumbItem :title="isEdit ? 'Уреди' : 'Нов'" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <!-- Stock Sub-Navigation Tabs -->
    <StockTabNavigation />

    <div v-if="isLoadingDocument" class="flex justify-center py-12">
      <BaseContentPlaceholders>
        <BaseContentPlaceholdersBox class="w-full h-96" />
      </BaseContentPlaceholders>
    </div>

    <form v-else @submit.prevent="saveDocument">
      <!-- Document Type Selection -->
      <BaseCard class="mb-6">
        <template #header>
          <h3 class="text-lg font-medium text-gray-900">Тип на документ</h3>
        </template>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <label
            v-for="typeOpt in documentTypes"
            :key="typeOpt.value"
            class="relative flex items-center p-4 border-2 rounded-lg cursor-pointer transition-colors"
            :class="form.document_type === typeOpt.value
              ? 'border-primary-500 bg-primary-50'
              : 'border-gray-200 hover:border-gray-300'"
          >
            <input
              type="radio"
              :value="typeOpt.value"
              v-model="form.document_type"
              class="sr-only"
            />
            <div class="flex items-center">
              <div
                class="flex-shrink-0 h-10 w-10 rounded-full flex items-center justify-center"
                :class="typeOpt.iconBg"
              >
                <BaseIcon :name="typeOpt.icon" class="h-5 w-5" :class="typeOpt.iconColor" />
              </div>
              <div class="ml-3">
                <span class="block text-sm font-medium text-gray-900">
                  {{ typeOpt.label }}
                </span>
                <span class="block text-xs text-gray-500">
                  {{ typeOpt.description }}
                </span>
              </div>
            </div>
          </label>
        </div>
      </BaseCard>

      <!-- Document Details -->
      <BaseCard class="mb-6">
        <template #header>
          <h3 class="text-lg font-medium text-gray-900">Детали на документ</h3>
        </template>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <BaseInputGroup label="Магацин (извор)" required>
            <BaseMultiselect
              v-model="form.warehouse_id"
              :content-loading="isLoadingWarehouses"
              value-prop="id"
              track-by="name"
              label="name"
              :options="warehouses"
              placeholder="Изберете магацин"
            />
          </BaseInputGroup>

          <BaseInputGroup
            v-if="form.document_type === 'transfer'"
            label="Одредишен магацин"
            required
          >
            <BaseMultiselect
              v-model="form.destination_warehouse_id"
              :content-loading="isLoadingWarehouses"
              value-prop="id"
              track-by="name"
              label="name"
              :options="destinationWarehouses"
              placeholder="Изберете одредишен магацин"
            />
          </BaseInputGroup>

          <BaseInputGroup label="Датум" required>
            <BaseInput
              v-model="form.document_date"
              type="date"
            />
          </BaseInputGroup>

          <BaseInputGroup label="Белешки" class="md:col-span-2">
            <BaseTextarea
              v-model="form.notes"
              rows="2"
              placeholder="Опционални белешки за документот..."
            />
          </BaseInputGroup>
        </div>
      </BaseCard>

      <!-- Line Items -->
      <BaseCard class="mb-6">
        <template #header>
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900">Ставки</h3>
            <BaseButton variant="primary-outline" size="sm" type="button" @click="addItem">
              <template #left="slotProps">
                <BaseIcon name="PlusIcon" :class="slotProps.class" />
              </template>
              Додај ставка
            </BaseButton>
          </div>
        </template>

        <div v-if="form.items.length === 0" class="text-center py-8">
          <BaseIcon name="CubeIcon" class="h-8 w-8 text-gray-400 mx-auto mb-2" />
          <p class="text-gray-500">Нема додадени ставки. Кликнете "Додај ставка" за да започнете.</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-1/3">
                  Артикл
                </th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-32">
                  Количина
                </th>
                <th
                  v-if="form.document_type === 'receipt'"
                  class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-40"
                >
                  Единечна цена
                </th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-40">
                  Вкупно
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-48">
                  Белешка
                </th>
                <th class="px-4 py-3 w-12"></th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="(item, index) in form.items" :key="index">
                <td class="px-4 py-2">
                  <BaseMultiselect
                    v-model="item.selectedItem"
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
                    placeholder="Пребарај артикл..."
                    @change="onItemSelected(index, $event)"
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
                </td>
                <td class="px-4 py-2">
                  <BaseInput
                    v-model="item.quantity"
                    type="number"
                    step="0.0001"
                    min="0.0001"
                    class="text-right"
                    placeholder="0"
                  />
                </td>
                <td v-if="form.document_type === 'receipt'" class="px-4 py-2">
                  <BaseInput
                    v-model="item.unit_cost"
                    type="number"
                    step="0.01"
                    min="0"
                    class="text-right"
                    placeholder="0.00"
                  />
                </td>
                <td class="px-4 py-2 text-right text-sm text-gray-900 font-medium whitespace-nowrap">
                  <BaseFormatMoney v-if="getItemTotal(item)" :amount="getItemTotal(item)" />
                  <span v-else>-</span>
                </td>
                <td class="px-4 py-2">
                  <BaseInput
                    v-model="item.notes"
                    type="text"
                    placeholder="Белешка..."
                  />
                </td>
                <td class="px-4 py-2 text-center">
                  <BaseButton
                    variant="danger-outline"
                    size="sm"
                    type="button"
                    @click="removeItem(index)"
                  >
                    <BaseIcon name="TrashIcon" class="h-4 w-4" />
                  </BaseButton>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Summary -->
        <div v-if="form.items.length > 0" class="mt-4 border-t pt-4 flex justify-between items-center px-4">
          <span class="text-sm text-gray-600">
            Вкупно ставки: <strong>{{ form.items.length }}</strong>
          </span>
          <span class="text-sm text-gray-900">
            Вкупна вредност:
            <strong>
              <BaseFormatMoney v-if="totalValue" :amount="totalValue" />
              <span v-else>-</span>
            </strong>
          </span>
        </div>
      </BaseCard>

      <!-- Actions -->
      <div class="flex justify-end space-x-3 mb-8">
        <router-link :to="{ name: 'stock.documents' }">
          <BaseButton variant="secondary" type="button">
            Откажи
          </BaseButton>
        </router-link>
        <BaseButton
          variant="primary-outline"
          type="submit"
          :loading="isSaving && saveAction === 'draft'"
          :disabled="isSaving"
          @click="saveAction = 'draft'"
        >
          Зачувај нацрт
        </BaseButton>
        <BaseButton
          variant="primary"
          type="submit"
          :loading="isSaving && saveAction === 'approve'"
          :disabled="isSaving"
          @click="saveAction = 'approve'"
        >
          Зачувај и одобри
        </BaseButton>
      </div>
    </form>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useStockStore } from '@/scripts/admin/stores/stock'
import { useItemStore } from '@/scripts/admin/stores/item'
import { useNotificationStore } from '@/scripts/stores/notification'
import StockTabNavigation from '@/scripts/admin/components/StockTabNavigation.vue'
import axios from 'axios'

const route = useRoute()
const router = useRouter()
const stockStore = useStockStore()
const itemStore = useItemStore()
const notificationStore = useNotificationStore()

const isEdit = computed(() => !!route.params.id)
const isLoadingDocument = ref(false)
const isLoadingWarehouses = ref(false)
const isLoadingItems = ref(false)
const isSaving = ref(false)
const saveAction = ref('draft')
const warehouses = ref([])

const documentTypes = [
  {
    value: 'receipt',
    label: 'Приемница',
    description: 'Прием на стока во магацин',
    icon: 'ArrowDownTrayIcon',
    iconBg: 'bg-green-100',
    iconColor: 'text-green-600',
  },
  {
    value: 'issue',
    label: 'Издатница',
    description: 'Издавање на стока од магацин',
    icon: 'ArrowUpTrayIcon',
    iconBg: 'bg-red-100',
    iconColor: 'text-red-600',
  },
  {
    value: 'transfer',
    label: 'Преносница',
    description: 'Пренос меѓу магацини',
    icon: 'ArrowsRightLeftIcon',
    iconBg: 'bg-blue-100',
    iconColor: 'text-blue-600',
  },
]

const form = reactive({
  document_type: 'receipt',
  warehouse_id: null,
  destination_warehouse_id: null,
  document_date: new Date().toISOString().split('T')[0],
  notes: '',
  items: [],
})

/**
 * Computed list of destination warehouses excluding the source warehouse.
 */
const destinationWarehouses = computed(() => {
  if (!form.warehouse_id) return warehouses.value
  return warehouses.value.filter(w => w.id !== form.warehouse_id)
})

/**
 * Computed total value from all items.
 */
const totalValue = computed(() => {
  return form.items.reduce((sum, item) => sum + (getItemTotal(item) || 0), 0)
})

/**
 * Calculate total cost for a line item (in cents).
 */
function getItemTotal(item) {
  if (form.document_type === 'receipt' && item.quantity && item.unit_cost) {
    return Math.round(parseFloat(item.quantity) * parseFloat(item.unit_cost) * 100)
  }
  // For issue/transfer, show WAC-based total if available
  if (item.wac && item.quantity) {
    return Math.round(parseFloat(item.quantity) * item.wac)
  }
  return null
}

/**
 * Add a new empty line item row.
 */
function addItem() {
  form.items.push({
    selectedItem: null,
    item_id: null,
    quantity: null,
    unit_cost: null,
    notes: '',
    wac: null,
  })
}

/**
 * Remove a line item by index.
 */
function removeItem(index) {
  form.items.splice(index, 1)
}

/**
 * Handle item selection from the multiselect dropdown.
 * Fetches WAC for non-receipt documents.
 */
async function onItemSelected(index, selectedItem) {
  if (selectedItem) {
    form.items[index].item_id = selectedItem.id

    // Fetch WAC for issue/transfer types
    if (form.document_type !== 'receipt' && form.warehouse_id) {
      try {
        const data = await stockStore.getItemStock(selectedItem.id, form.warehouse_id)
        form.items[index].wac = data.unit_cost || 0
      } catch {
        form.items[index].wac = null
      }
    }
  } else {
    form.items[index].item_id = null
    form.items[index].wac = null
  }
}

/**
 * Search items by name, SKU, or barcode for the multiselect dropdown.
 */
async function searchItems(search) {
  isLoadingItems.value = true
  try {
    const res = await itemStore.fetchItems({ search, track_quantity: true })
    return res.data.data
  } finally {
    isLoadingItems.value = false
  }
}

/**
 * Load existing document data when editing.
 */
async function loadDocument() {
  if (!isEdit.value) return

  isLoadingDocument.value = true
  try {
    const response = await axios.get(`/stock/documents/${route.params.id}`)
    const doc = response.data.data

    if (doc.status !== 'draft') {
      notificationStore.showNotification({
        type: 'error',
        message: 'Само нацрт-документи можат да се уредуваат.',
      })
      router.push({ name: 'stock.documents.view', params: { id: doc.id } })
      return
    }

    form.document_type = doc.document_type
    form.warehouse_id = doc.warehouse_id
    form.destination_warehouse_id = doc.destination_warehouse_id
    form.document_date = doc.document_date
    form.notes = doc.notes || ''
    form.items = (doc.items || []).map(item => ({
      selectedItem: {
        id: item.item_id,
        name: item.item_name,
        sku: item.item_sku,
      },
      item_id: item.item_id,
      quantity: parseFloat(item.quantity),
      unit_cost: item.unit_cost ? item.unit_cost / 100 : null,
      notes: item.notes || '',
      wac: null,
    }))
  } catch (error) {
    console.error('Failed to load document:', error)
    notificationStore.showNotification({
      type: 'error',
      message: 'Грешка при вчитување на документот.',
    })
  } finally {
    isLoadingDocument.value = false
  }
}

/**
 * Load warehouses from the stock store.
 */
async function loadWarehouses() {
  isLoadingWarehouses.value = true
  try {
    const response = await axios.get('/stock/warehouses', { params: { limit: 100 } })
    warehouses.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load warehouses:', error)
  } finally {
    isLoadingWarehouses.value = false
  }
}

/**
 * Save the document (create or update).
 * Optionally approve immediately after saving.
 */
async function saveDocument() {
  // Basic client-side validation
  if (!form.document_type || !form.warehouse_id || !form.document_date) {
    notificationStore.showNotification({
      type: 'error',
      message: 'Пополнете ги сите задолжителни полиња.',
    })
    return
  }

  if (form.items.length === 0) {
    notificationStore.showNotification({
      type: 'error',
      message: 'Мора да додадете барем една ставка.',
    })
    return
  }

  // Validate all items have required fields
  for (let i = 0; i < form.items.length; i++) {
    const item = form.items[i]
    if (!item.item_id || !item.quantity || item.quantity <= 0) {
      notificationStore.showNotification({
        type: 'error',
        message: `Ставка ${i + 1}: Изберете артикл и внесете количина.`,
      })
      return
    }
    if (form.document_type === 'receipt' && (!item.unit_cost || item.unit_cost < 0)) {
      notificationStore.showNotification({
        type: 'error',
        message: `Ставка ${i + 1}: Единечната цена е задолжителна за приемница.`,
      })
      return
    }
  }

  if (form.document_type === 'transfer' && !form.destination_warehouse_id) {
    notificationStore.showNotification({
      type: 'error',
      message: 'Одредишниот магацин е задолжителен за преносница.',
    })
    return
  }

  isSaving.value = true
  try {
    const payload = {
      document_type: form.document_type,
      warehouse_id: form.warehouse_id,
      destination_warehouse_id: form.document_type === 'transfer' ? form.destination_warehouse_id : null,
      document_date: form.document_date,
      notes: form.notes || null,
      items: form.items.map(item => ({
        item_id: item.item_id,
        quantity: parseFloat(item.quantity),
        unit_cost: item.unit_cost ? parseFloat(item.unit_cost) : null,
        notes: item.notes || null,
      })),
    }

    let response
    if (isEdit.value) {
      response = await axios.put(`/stock/documents/${route.params.id}`, payload)
    } else {
      response = await axios.post('/stock/documents', payload)
    }

    const docId = response.data.data.id

    // If "Save & Approve" was clicked, also approve the document
    if (saveAction.value === 'approve') {
      try {
        await axios.post(`/stock/documents/${docId}/approve`)
        notificationStore.showNotification({
          type: 'success',
          message: 'Документот е успешно креиран и одобрен.',
        })
      } catch (approveError) {
        notificationStore.showNotification({
          type: 'warning',
          message: 'Документот е зачуван, но одобрувањето не успеа: ' + (approveError.response?.data?.message || approveError.message),
        })
      }
    } else {
      notificationStore.showNotification({
        type: 'success',
        message: isEdit.value ? 'Документот е успешно ажуриран.' : 'Документот е успешно креиран.',
      })
    }

    router.push({ name: 'stock.documents.view', params: { id: docId } })
  } catch (error) {
    console.error('Failed to save document:', error)
    const errorMsg = error.response?.data?.message || error.response?.data?.error || 'Грешка при зачувување.'
    notificationStore.showNotification({
      type: 'error',
      message: errorMsg,
    })
  } finally {
    isSaving.value = false
  }
}

// Reset destination warehouse when switching away from transfer type
watch(() => form.document_type, (newType) => {
  if (newType !== 'transfer') {
    form.destination_warehouse_id = null
  }
})

onMounted(async () => {
  await loadWarehouses()
  if (isEdit.value) {
    await loadDocument()
  }
})
</script>
// CLAUDE-CHECKPOINT
