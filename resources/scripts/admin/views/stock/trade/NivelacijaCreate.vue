<template>
  <BasePage>
    <BasePageHeader :title="isEditing ? $t('trade.edit_nivelacija') : $t('trade.new_nivelacija')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('stock.title')" to="/admin/stock" />
        <BaseBreadcrumbItem :title="$t('trade.nivelacii_title')" to="/admin/stock/trade/nivelacii" />
        <BaseBreadcrumbItem :title="isEditing ? $t('trade.edit_nivelacija') : $t('trade.new_nivelacija')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <!-- Stock Sub-Navigation Tabs -->
    <StockTabNavigation />

    <div v-if="isLoadingEdit" class="flex justify-center py-12">
      <BaseContentPlaceholders>
        <BaseContentPlaceholdersBox class="w-full h-96" />
      </BaseContentPlaceholders>
    </div>

    <form v-else @submit.prevent="submitForm">
      <!-- Header Fields -->
      <BaseCard class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 p-6">
          <BaseInputGroup :label="$t('trade.type')" required>
            <BaseMultiselect
              v-model="form.type"
              :options="typeOptions"
              value-prop="value"
              label="label"
              :placeholder="$t('trade.type')"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('trade.doc_date')" required>
            <BaseInput
              v-model="form.document_date"
              type="date"
              required
            />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('trade.warehouse')">
            <BaseMultiselect
              v-model="form.warehouse_id"
              :options="warehouses"
              value-prop="id"
              label="name"
              :placeholder="$t('trade.all_warehouses')"
              :canClear="true"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('trade.source_bill')">
            <BaseMultiselect
              v-model="form.source_bill_id"
              :options="bills"
              value-prop="id"
              label="bill_number"
              track-by="bill_number"
              :placeholder="$t('trade.select_bill_placeholder')"
              :searchable="true"
              :canClear="true"
            />
          </BaseInputGroup>
        </div>

        <div class="px-6 pb-6">
          <BaseInputGroup :label="$t('trade.reason')" required>
            <BaseInput
              v-model="form.reason"
              type="text"
              :placeholder="$t('trade.reason')"
              required
            />
          </BaseInputGroup>
        </div>
      </BaseCard>

      <!-- Items -->
      <BaseCard class="mb-6">
        <template #header>
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900">Ставки</h3>
            <BaseButton variant="primary-outline" size="sm" type="button" @click="addItem">
              <template #left="slotProps">
                <BaseIcon name="PlusIcon" :class="slotProps.class" />
              </template>
              {{ $t('trade.add_item') }}
            </BaseButton>
          </div>
        </template>

        <div v-if="form.items.length === 0" class="text-center py-8">
          <BaseIcon name="CubeIcon" class="h-12 w-12 text-gray-400 mx-auto mb-4" />
          <p class="text-gray-500">Додадете артикли за нивелација.</p>
          <BaseButton variant="primary" size="sm" class="mt-4" type="button" @click="addItem">
            {{ $t('trade.add_item') }}
          </BaseButton>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase" style="min-width: 200px">
                  Артикл
                </th>
                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase" style="min-width: 100px">
                  {{ $t('trade.qty_on_hand') }}
                </th>
                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase" style="min-width: 120px">
                  {{ $t('trade.old_price') }}
                </th>
                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase" style="min-width: 120px">
                  {{ $t('trade.new_price') }}
                </th>
                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase" style="min-width: 120px">
                  {{ $t('trade.price_difference') }}
                </th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase" style="min-width: 150px">
                  Белешка
                </th>
                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase" style="width: 60px">
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="(item, index) in form.items" :key="index">
                <td class="px-3 py-2">
                  <BaseMultiselect
                    v-model="item.item_id"
                    :options="availableItems"
                    value-prop="id"
                    label="name"
                    track-by="name"
                    :searchable="true"
                    placeholder="Избери артикл..."
                    @update:model-value="onItemSelected(index)"
                  />
                </td>
                <td class="px-3 py-2">
                  <BaseInput
                    v-model.number="item.quantity_on_hand"
                    type="number"
                    step="0.01"
                    min="0"
                    class="text-right"
                  />
                </td>
                <td class="px-3 py-2">
                  <BaseInput
                    v-model.number="item.old_retail_price_display"
                    type="number"
                    step="0.01"
                    min="0"
                    class="text-right"
                    @update:model-value="recalcDifference(index)"
                  />
                </td>
                <td class="px-3 py-2">
                  <BaseInput
                    v-model.number="item.new_retail_price_display"
                    type="number"
                    step="0.01"
                    min="0"
                    class="text-right"
                    @update:model-value="recalcDifference(index)"
                  />
                </td>
                <td class="px-3 py-2 text-right font-mono text-sm"
                  :class="item.difference > 0 ? 'text-green-700' : item.difference < 0 ? 'text-red-700' : 'text-gray-500'"
                >
                  {{ item.difference !== undefined ? item.difference.toFixed(2) : '0.00' }}
                </td>
                <td class="px-3 py-2">
                  <BaseInput
                    v-model="item.notes"
                    type="text"
                    placeholder="..."
                  />
                </td>
                <td class="px-3 py-2 text-right">
                  <BaseButton variant="danger-outline" size="sm" type="button" @click="removeItem(index)">
                    <BaseIcon name="TrashIcon" class="h-4 w-4" />
                  </BaseButton>
                </td>
              </tr>
            </tbody>
            <tfoot class="bg-gray-50">
              <tr>
                <td colspan="4" class="px-3 py-3 text-sm font-medium text-gray-900 text-right">
                  {{ $t('trade.total_difference') }}:
                </td>
                <td class="px-3 py-3 text-sm font-bold text-right font-mono"
                  :class="totalDifference > 0 ? 'text-green-700' : totalDifference < 0 ? 'text-red-700' : 'text-gray-900'"
                >
                  {{ totalDifference.toFixed(2) }}
                </td>
                <td colspan="2"></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </BaseCard>

      <!-- Actions -->
      <div class="flex items-center justify-end space-x-3">
        <router-link :to="{ name: 'stock.trade.nivelacii' }">
          <BaseButton variant="secondary" type="button">
            Откажи
          </BaseButton>
        </router-link>
        <BaseButton
          variant="primary"
          type="submit"
          :loading="isSaving"
          :disabled="!canSubmit"
        >
          <template #left="slotProps">
            <BaseIcon name="CheckIcon" :class="slotProps.class" />
          </template>
          {{ isEditing ? 'Зачувај измени' : 'Креирај нивелација' }}
        </BaseButton>
      </div>
    </form>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useStockStore } from '@/scripts/admin/stores/stock'
import StockTabNavigation from '@/scripts/admin/components/StockTabNavigation.vue'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const companyStore = useCompanyStore()
const notificationStore = useNotificationStore()
const stockStore = useStockStore()

const isEditing = computed(() => !!route.params.id)
const isLoadingEdit = ref(false)
const isSaving = ref(false)
const availableItems = ref([])
const bills = ref([])
const warehouses = ref([])

const companyId = computed(() => companyStore.selectedCompany?.id)

function apiBase() {
  return `/partner/companies/${companyId.value}/accounting`
}

const typeOptions = [
  { label: t('trade.type_price_change'), value: 'price_change' },
  { label: t('trade.type_discount'), value: 'discount' },
  { label: t('trade.type_supplier_change'), value: 'supplier_change' },
]

const today = new Date()
const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`

const form = reactive({
  type: 'price_change',
  document_date: todayStr,
  reason: '',
  source_bill_id: null,
  warehouse_id: null,
  items: [],
})

const totalDifference = computed(() => {
  return form.items.reduce((sum, item) => {
    const diff = (item.new_retail_price_display || 0) - (item.old_retail_price_display || 0)
    return sum + (diff * (item.quantity_on_hand || 0))
  }, 0)
})

const canSubmit = computed(() => {
  return form.type && form.document_date && form.reason && form.items.length > 0
    && form.items.every(item => item.item_id && item.quantity_on_hand >= 0
      && item.old_retail_price_display >= 0 && item.new_retail_price_display >= 0)
})

function addItem() {
  form.items.push({
    item_id: null,
    quantity_on_hand: 0,
    old_retail_price_display: 0,
    new_retail_price_display: 0,
    difference: 0,
    notes: '',
  })
}

function removeItem(index) {
  form.items.splice(index, 1)
}

function onItemSelected(index) {
  const item = form.items[index]
  if (!item.item_id) return

  const found = availableItems.value.find(i => i.id === item.item_id)
  if (found) {
    // Set old price from item's current retail price (in cents -> display in MKD)
    item.old_retail_price_display = (found.retail_price || 0) / 100
    item.new_retail_price_display = (found.retail_price || 0) / 100
    item.quantity_on_hand = found.stock_on_hand || 0
    recalcDifference(index)
  }
}

function recalcDifference(index) {
  const item = form.items[index]
  item.difference = (item.new_retail_price_display || 0) - (item.old_retail_price_display || 0)
}

async function loadItems() {
  try {
    const response = await window.axios.get('/items', {
      params: { limit: 'all' },
    })
    availableItems.value = response.data.data || response.data || []
  } catch (error) {
    console.error('Failed to load items:', error)
  }
}

async function loadBills() {
  try {
    const response = await window.axios.get('/bills', {
      params: { limit: 50, status: 'COMPLETED' },
    })
    bills.value = response.data.data || response.data?.bills?.data || []
  } catch (error) {
    console.error('Failed to load bills:', error)
  }
}

async function loadWarehouses() {
  try {
    await stockStore.fetchWarehouses()
    warehouses.value = stockStore.warehouses || []
  } catch (error) {
    console.error('Failed to load warehouses:', error)
  }
}

async function loadExisting() {
  if (!isEditing.value || !companyId.value) return
  isLoadingEdit.value = true
  try {
    const response = await window.axios.get(`${apiBase()}/nivelacii/${route.params.id}`)
    const data = response.data.data
    form.type = data.type
    form.document_date = String(data.document_date).substring(0, 10)
    form.reason = data.reason
    form.source_bill_id = data.source_bill_id
    form.warehouse_id = data.warehouse_id
    form.items = (data.items || []).map(item => ({
      item_id: item.item_id,
      quantity_on_hand: item.quantity_on_hand || 0,
      old_retail_price_display: (item.old_retail_price || 0) / 100,
      new_retail_price_display: (item.new_retail_price || 0) / 100,
      difference: ((item.new_retail_price || 0) - (item.old_retail_price || 0)) / 100,
      notes: item.notes || '',
    }))
  } catch (error) {
    console.error('Failed to load nivelacija:', error)
    notificationStore.showNotification({
      type: 'error',
      message: 'Грешка при вчитување.',
    })
  } finally {
    isLoadingEdit.value = false
  }
}

async function submitForm() {
  if (!companyId.value || !canSubmit.value) return
  isSaving.value = true

  const payload = {
    type: form.type,
    document_date: form.document_date,
    reason: form.reason,
    source_bill_id: form.source_bill_id || null,
    warehouse_id: form.warehouse_id || null,
    items: form.items.map(item => ({
      item_id: item.item_id,
      quantity_on_hand: item.quantity_on_hand,
      old_retail_price: Math.round((item.old_retail_price_display || 0) * 100),
      new_retail_price: Math.round((item.new_retail_price_display || 0) * 100),
      notes: item.notes || null,
    })),
  }

  try {
    if (isEditing.value) {
      await window.axios.put(`${apiBase()}/nivelacii/${route.params.id}`, payload)
      notificationStore.showNotification({
        type: 'success',
        message: 'Нивелацијата е ажурирана.',
      })
    } else {
      const response = await window.axios.post(`${apiBase()}/nivelacii`, payload)
      const newId = response.data.data?.id
      notificationStore.showNotification({
        type: 'success',
        message: 'Нивелацијата е креирана.',
      })
      if (newId) {
        router.push({ name: 'stock.trade.nivelacija.view', params: { id: newId } })
        return
      }
    }
    router.push({ name: 'stock.trade.nivelacii' })
  } catch (error) {
    console.error('Failed to save nivelacija:', error)
    const msg = error.response?.data?.error || error.response?.data?.message || 'Грешка при зачувување.'
    notificationStore.showNotification({
      type: 'error',
      message: msg,
    })
  } finally {
    isSaving.value = false
  }
}

onMounted(async () => {
  await Promise.all([loadItems(), loadBills(), loadWarehouses()])
  if (isEditing.value) {
    await loadExisting()
  }
})
</script>

// CLAUDE-CHECKPOINT
