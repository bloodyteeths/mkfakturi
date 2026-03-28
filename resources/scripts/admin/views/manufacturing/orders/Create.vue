<template>
  <BasePage>
    <BasePageHeader :title="t('manufacturing.new_order')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="t('manufacturing.title')" to="/admin/manufacturing" />
        <BaseBreadcrumbItem :title="t('manufacturing.orders')" to="/admin/manufacturing/orders" />
        <BaseBreadcrumbItem :title="t('manufacturing.new_order')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <!-- AI Natural Language Input -->
    <div class="rounded-lg border border-purple-200 bg-purple-50 p-4 shadow">
      <div class="flex items-center space-x-2 mb-2">
        <span class="text-sm font-medium text-purple-900">{{ t('manufacturing.ai_parse_order') }}</span>
        <span class="rounded-full bg-purple-100 px-2 py-0.5 text-xs text-purple-700">AI</span>
      </div>
      <div class="flex space-x-2">
        <input
          v-model="nlInput"
          type="text"
          class="block flex-1 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
          :placeholder="t('manufacturing.ai_parse_placeholder')"
          @keydown.enter.prevent="parseNlInput"
        />
        <BaseButton variant="primary-outline" size="sm" :loading="nlLoading" @click="parseNlInput">
          {{ t('manufacturing.ai_parse') }}
        </BaseButton>
      </div>
    </div>

    <form @submit.prevent="submitForm" class="space-y-6">
      <div class="rounded-lg bg-white p-6 shadow">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
          <BaseInputGroup :label="t('manufacturing.select_bom')" required>
            <BaseMultiselect
              v-model="form.bom_id"
              :options="bomOptions"
              label="label"
              value-prop="id"
              :placeholder="t('manufacturing.select_bom')"
              :searchable="true"
              @change="onBomChange"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="t('manufacturing.planned_quantity')" required>
            <BaseInput v-model="form.planned_quantity" type="number" step="0.0001" min="0.0001" required />
          </BaseInputGroup>

          <BaseInputGroup :label="t('manufacturing.order_date')">
            <BaseInput v-model="form.order_date" type="date" />
          </BaseInputGroup>

          <BaseInputGroup :label="t('manufacturing.expected_completion')">
            <BaseInput v-model="form.expected_completion_date" type="date" />
          </BaseInputGroup>

          <BaseInputGroup :label="t('manufacturing.output_warehouse')">
            <BaseMultiselect
              v-model="form.output_warehouse_id"
              :options="warehouseOptions"
              label="name"
              value-prop="id"
              :placeholder="t('manufacturing.select_warehouse')"
              :can-deselect="true"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="t('manufacturing.notes')" class="md:col-span-2 lg:col-span-3">
            <textarea v-model="form.notes" rows="2" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"></textarea>
          </BaseInputGroup>
        </div>

        <!-- Selected BOM info -->
        <div v-if="selectedBom" class="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-4">
          <p class="text-sm font-medium text-blue-900">
            {{ selectedBom.name }} ({{ selectedBom.code }})
          </p>
          <p class="mt-1 text-xs text-blue-700">
            {{ t('manufacturing.output_item') }}: {{ selectedBom.output_item?.name || '-' }}
            &bull; {{ t('manufacturing.output_quantity') }}: {{ selectedBom.output_quantity }}
          </p>
        </div>

        <!-- Stock Availability Warnings -->
        <div v-if="stockCheck && !stockCheck.all_available" class="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-4">
          <p class="mb-2 text-sm font-medium text-amber-900">{{ t('manufacturing.stock_warning') }}</p>
          <div class="space-y-1">
            <div
              v-for="mat in stockCheck.materials.filter((m) => !m.sufficient)"
              :key="mat.item_id"
              class="flex items-center justify-between text-xs"
            >
              <span class="text-amber-800">{{ mat.item_name }}</span>
              <span class="text-red-700">
                {{ t('manufacturing.shortage') }}: {{ mat.shortage }}
                ({{ t('manufacturing.available') }}: {{ mat.available_qty }} / {{ t('manufacturing.required') }}: {{ mat.required_qty }})
              </span>
            </div>
          </div>
        </div>
        <div v-else-if="stockCheck && stockCheck.all_available" class="mt-4 rounded-lg border border-green-200 bg-green-50 p-3">
          <p class="text-sm text-green-800">{{ t('manufacturing.stock_ok') }}</p>
        </div>
      </div>

      <!-- Submit -->
      <div class="flex justify-end space-x-3">
        <router-link to="/admin/manufacturing/orders">
          <BaseButton variant="primary-outline">{{ $t('general.cancel') }}</BaseButton>
        </router-link>
        <BaseButton type="submit" variant="primary" :loading="isSaving">
          {{ $t('general.save') }}
        </BaseButton>
      </div>
    </form>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'

const router = useRouter()
const route = useRoute()
const notificationStore = useNotificationStore()
const { t } = useI18n()

const bomOptions = ref([])
const warehouseOptions = ref([])
const allBoms = ref([])
const isSaving = ref(false)
const nlInput = ref('')
const nlLoading = ref(false)
const stockCheck = ref(null)
let stockCheckTimer = null

const form = reactive({
  bom_id: null,
  planned_quantity: '1',
  order_date: new Date().toISOString().split('T')[0],
  expected_completion_date: '',
  output_warehouse_id: null,
  notes: '',
})

const selectedBom = computed(() => allBoms.value.find((b) => b.id === form.bom_id))

function onBomChange() {
  checkStockAvailability()
}

async function checkStockAvailability() {
  if (!form.bom_id || !form.planned_quantity || parseFloat(form.planned_quantity) <= 0) {
    stockCheck.value = null
    return
  }
  try {
    const res = await window.axios.get(`/manufacturing/boms/${form.bom_id}/stock-availability`, {
      params: {
        quantity: parseFloat(form.planned_quantity),
        warehouse_id: form.output_warehouse_id || undefined,
      },
    })
    stockCheck.value = res.data?.data || null
  } catch {
    stockCheck.value = null
  }
}

// Debounced watch on quantity changes
watch(() => form.planned_quantity, () => {
  clearTimeout(stockCheckTimer)
  stockCheckTimer = setTimeout(checkStockAvailability, 500)
})

watch(() => form.output_warehouse_id, () => {
  checkStockAvailability()
})

async function parseNlInput() {
  if (!nlInput.value || nlInput.value.length < 3) return
  nlLoading.value = true
  try {
    const res = await window.axios.post('/manufacturing/ai/parse-intent', {
      input: nlInput.value,
    })
    const data = res.data?.data
    if (data) {
      if (data.bom_id) form.bom_id = data.bom_id
      if (data.quantity) form.planned_quantity = String(data.quantity)
      if (data.deadline) form.expected_completion_date = data.deadline
      if (data.notes) form.notes = data.notes
    }
  } catch {
    // AI unavailable — silently ignore
  } finally {
    nlLoading.value = false
  }
}

async function submitForm() {
  if (!form.bom_id) return

  isSaving.value = true
  try {
    const payload = {
      bom_id: form.bom_id,
      planned_quantity: parseFloat(form.planned_quantity),
      order_date: form.order_date || null,
      expected_completion_date: form.expected_completion_date || null,
      output_warehouse_id: form.output_warehouse_id || null,
      notes: form.notes || null,
    }

    const response = await window.axios.post('/manufacturing/orders', payload)

    notificationStore.showNotification({
      type: 'success',
      message: t('manufacturing.created_success'),
    })

    router.push(`/admin/manufacturing/orders/${response.data.data.id}`)
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
    const [bomsRes, whRes] = await Promise.all([
      window.axios.get('/manufacturing/boms', { params: { limit: 500, is_active: 1 } }),
      window.axios.get('/stock/warehouses'),
    ])

    allBoms.value = bomsRes.data.data || []
    bomOptions.value = allBoms.value.map((b) => ({
      id: b.id,
      label: `${b.code} — ${b.name}`,
      ...b,
    }))
    warehouseOptions.value = whRes.data?.data || whRes.data || []

    // Auto-fill from query params (AI parse-intent or dashboard link)
    const qBom = route.query.bom
    const qQty = route.query.qty
    const qDeadline = route.query.deadline
    const qNotes = route.query.notes

    if (qBom) {
      const bomId = parseInt(qBom)
      if (allBoms.value.some((b) => b.id === bomId)) {
        form.bom_id = bomId
        onBomChange()
      }
    }
    if (qQty && parseFloat(qQty) > 0) {
      form.planned_quantity = String(qQty)
    }
    if (qDeadline) {
      form.expected_completion_date = qDeadline
    }
    if (qNotes) {
      form.notes = qNotes
    }
  } catch {
    // Options will stay empty
  }
})
</script>
