<template>
  <BasePage>
    <BasePageHeader :title="t('manufacturing.complete_production')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="t('manufacturing.title')" to="/admin/manufacturing" />
        <BaseBreadcrumbItem :title="t('manufacturing.orders')" to="/admin/manufacturing/orders" />
        <BaseBreadcrumbItem :title="order?.order_number || '...'" :to="`/admin/manufacturing/orders/${$route.params.id}`" />
        <BaseBreadcrumbItem :title="t('manufacturing.complete_production')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <!-- Loading -->
    <div v-if="isLoading" class="space-y-4 rounded-lg bg-white p-6 shadow">
      <div v-for="i in 6" :key="i" class="h-4 animate-pulse rounded bg-gray-200"></div>
    </div>

    <template v-else-if="order">
      <!-- Step 1: Actual Quantity -->
      <div class="rounded-lg bg-white p-6 shadow">
        <h3 class="mb-4 text-lg font-medium text-gray-900">{{ t('manufacturing.actual_quantity') }}</h3>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
          <div>
            <p class="text-xs text-gray-500">{{ t('manufacturing.output_item') }}</p>
            <p class="text-sm font-medium text-gray-900">{{ order.output_item?.name }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">{{ t('manufacturing.planned_quantity') }}</p>
            <p class="text-sm font-medium text-gray-900">{{ order.planned_quantity }}</p>
          </div>
          <BaseInputGroup :label="t('manufacturing.actual_quantity')" required>
            <BaseInput v-model="form.actual_quantity" type="number" step="0.0001" min="0" />
          </BaseInputGroup>
        </div>
      </div>

      <!-- Step 2: Co-production outputs (optional) -->
      <div class="mt-6 rounded-lg bg-white p-6 shadow">
        <div class="mb-4 flex items-center justify-between">
          <h3 class="text-lg font-medium text-gray-900">{{ t('manufacturing.co_production') }}</h3>
          <div class="flex items-center space-x-2">
            <label class="text-sm text-gray-600">{{ t('manufacturing.co_production') }}</label>
            <input v-model="isCoProduction" type="checkbox" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
          </div>
        </div>

        <div v-if="isCoProduction" class="space-y-4">
          <!-- Allocation method -->
          <BaseInputGroup :label="t('manufacturing.allocation_method')">
            <BaseMultiselect
              v-model="allocationMethod"
              :options="allocationMethods"
              label="label"
              value-prop="value"
            />
          </BaseInputGroup>

          <!-- Output lines -->
          <div class="space-y-3">
            <div
              v-for="(output, index) in form.co_outputs"
              :key="index"
              class="grid grid-cols-12 items-end gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3"
            >
              <div class="col-span-3">
                <label class="mb-1 block text-xs font-medium text-gray-600">{{ t('manufacturing.output_item') }}</label>
                <BaseMultiselect
                  v-model="output.item_id"
                  :options="itemOptions"
                  label="name"
                  value-prop="id"
                  :searchable="true"
                />
              </div>
              <div class="col-span-2">
                <label class="mb-1 block text-xs font-medium text-gray-600">{{ t('manufacturing.quantity') }}</label>
                <BaseInput v-model="output.quantity" type="number" step="0.0001" min="0.0001" />
              </div>
              <div class="col-span-2">
                <label class="mb-1 block text-xs font-medium text-gray-600">{{ t('manufacturing.output_warehouse') }}</label>
                <BaseMultiselect
                  v-model="output.warehouse_id"
                  :options="warehouseOptions"
                  label="name"
                  value-prop="id"
                  :can-deselect="true"
                />
              </div>
              <div class="col-span-1 text-center">
                <label class="mb-1 block text-xs font-medium text-gray-600">{{ t('manufacturing.is_primary') }}</label>
                <input v-model="output.is_primary" type="checkbox" class="rounded border-gray-300 text-primary-600" />
              </div>
              <div v-if="allocationMethod === 'fixed_ratio'" class="col-span-2">
                <label class="mb-1 block text-xs font-medium text-gray-600">{{ t('manufacturing.allocation_percent') }} %</label>
                <BaseInput v-model="output.allocation_percent" type="number" step="0.01" min="0" max="100" />
              </div>
              <div v-else class="col-span-2"></div>
              <div class="col-span-2 text-right">
                <BaseButton v-if="form.co_outputs.length > 2" variant="danger" size="sm" @click="removeOutput(index)">
                  <BaseIcon name="TrashIcon" class="h-4 w-4" />
                </BaseButton>
              </div>
            </div>
          </div>

          <BaseButton variant="primary-outline" size="sm" @click="addOutput">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ t('manufacturing.add_output') }}
          </BaseButton>
        </div>

        <div v-else class="text-sm text-gray-500">
          {{ t('manufacturing.output_item') }}: {{ order.output_item?.name }} — {{ form.actual_quantity || order.planned_quantity }}
        </div>
      </div>

      <!-- Cost Summary Preview -->
      <div class="mt-6 rounded-lg bg-white p-6 shadow">
        <h3 class="mb-4 text-lg font-medium text-gray-900">{{ t('manufacturing.cost_summary') }}</h3>
        <div class="grid grid-cols-2 gap-4 md:grid-cols-5">
          <div>
            <p class="text-xs text-gray-500">{{ t('manufacturing.total_material_cost') }}</p>
            <p class="text-sm font-semibold">{{ formatMoney(order.total_material_cost) }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">{{ t('manufacturing.total_labor_cost') }}</p>
            <p class="text-sm font-semibold">{{ formatMoney(order.total_labor_cost) }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">{{ t('manufacturing.total_overhead_cost') }}</p>
            <p class="text-sm font-semibold">{{ formatMoney(order.total_overhead_cost) }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">{{ t('manufacturing.total_production_cost') }}</p>
            <p class="text-lg font-bold text-primary-600">{{ formatMoney(order.total_production_cost) }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">{{ t('manufacturing.cost_per_unit') }}</p>
            <p class="text-sm font-semibold">
              {{ form.actual_quantity > 0 ? formatMoney(Math.round(order.total_production_cost / parseFloat(form.actual_quantity))) : '-' }}
            </p>
          </div>
        </div>
      </div>

      <!-- Submit -->
      <div class="mt-6 flex justify-end space-x-3">
        <router-link :to="`/admin/manufacturing/orders/${order.id}`">
          <BaseButton variant="primary-outline">{{ $t('general.cancel') }}</BaseButton>
        </router-link>
        <BaseButton variant="primary" :loading="isSaving" @click="submitCompletion">
          {{ t('manufacturing.complete_production') }}
        </BaseButton>
      </div>
    </template>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'

const route = useRoute()
const router = useRouter()
const notificationStore = useNotificationStore()
const { t, locale } = useI18n()

const order = ref(null)
const isLoading = ref(true)
const isSaving = ref(false)
const isCoProduction = ref(false)
const allocationMethod = ref('weight')
const itemOptions = ref([])
const warehouseOptions = ref([])

const form = reactive({
  actual_quantity: '',
  co_outputs: [],
})

const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }

const allocationMethods = computed(() => [
  { label: t('manufacturing.allocation_by_weight'), value: 'weight' },
  { label: t('manufacturing.allocation_by_market'), value: 'market_value' },
  { label: t('manufacturing.allocation_by_ratio'), value: 'fixed_ratio' },
  { label: t('manufacturing.allocation_manual'), value: 'manual' },
])

function formatMoney(cents) {
  if (cents === null || cents === undefined) return '-'
  const fmtLocale = localeMap[locale.value] || 'mk-MK'
  return new Intl.NumberFormat(fmtLocale, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(cents / 100)
}

function addOutput() {
  form.co_outputs.push({
    item_id: null,
    quantity: '',
    warehouse_id: order.value?.output_warehouse_id || null,
    is_primary: form.co_outputs.length === 0,
    allocation_method: allocationMethod.value,
    allocation_percent: '',
  })
}

function removeOutput(index) {
  form.co_outputs.splice(index, 1)
}

async function submitCompletion() {
  isSaving.value = true
  try {
    const payload = {
      actual_quantity: parseFloat(form.actual_quantity),
    }

    if (isCoProduction.value && form.co_outputs.length >= 2) {
      payload.co_outputs = form.co_outputs.map((o) => ({
        item_id: o.item_id,
        quantity: parseFloat(o.quantity),
        warehouse_id: o.warehouse_id,
        is_primary: o.is_primary,
        allocation_method: allocationMethod.value,
        allocation_percent: o.allocation_percent ? parseFloat(o.allocation_percent) : 0,
      }))
    }

    await window.axios.post(`/manufacturing/orders/${route.params.id}/complete`, payload)

    notificationStore.showNotification({
      type: 'success',
      message: t('manufacturing.completed_success'),
    })

    router.push(`/admin/manufacturing/orders/${route.params.id}`)
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('manufacturing.error_loading'),
    })
  } finally {
    isSaving.value = false
  }
}

onMounted(async () => {
  try {
    const [orderRes, itemsRes, whRes] = await Promise.all([
      window.axios.get(`/manufacturing/orders/${route.params.id}`),
      window.axios.get('/items', { params: { limit: 500 } }),
      window.axios.get('/stock/warehouses'),
    ])

    order.value = orderRes.data.data
    form.actual_quantity = order.value.planned_quantity
    itemOptions.value = itemsRes.data?.data || itemsRes.data || []
    warehouseOptions.value = whRes.data?.data || whRes.data || []

    // Pre-fill first output with the primary product
    addOutput()
    form.co_outputs[0].item_id = order.value.output_item_id
    form.co_outputs[0].quantity = form.actual_quantity
    form.co_outputs[0].is_primary = true
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('manufacturing.error_loading'),
    })
  } finally {
    isLoading.value = false
  }
})
</script>
