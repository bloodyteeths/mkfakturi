<template>
  <BasePage>
    <BasePageHeader :title="t('manufacturing.new_bom')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="t('manufacturing.title')" to="/admin/manufacturing" />
        <BaseBreadcrumbItem :title="t('manufacturing.boms')" to="/admin/manufacturing/boms" />
        <BaseBreadcrumbItem :title="t('manufacturing.new_bom')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <form @submit.prevent="submitForm" class="space-y-6">
      <!-- Header Fields -->
      <div class="rounded-lg bg-white p-6 shadow">
        <h3 class="mb-4 text-lg font-medium text-gray-900">{{ t('manufacturing.bom_name') }}</h3>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
          <BaseInputGroup :label="t('manufacturing.bom_name')" required>
            <BaseInput v-model="form.name" type="text" required />
          </BaseInputGroup>

          <BaseInputGroup :label="t('manufacturing.output_item')" required>
            <BaseMultiselect
              v-model="form.output_item_id"
              :options="itemOptions"
              label="name"
              value-prop="id"
              :placeholder="t('manufacturing.select_item')"
              :searchable="true"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="t('manufacturing.output_quantity')" required>
            <BaseInput v-model="form.output_quantity" type="number" step="0.0001" min="0.0001" required />
          </BaseInputGroup>

          <BaseInputGroup :label="t('manufacturing.output_unit')">
            <BaseMultiselect
              v-model="form.output_unit_id"
              :options="unitOptions"
              label="name"
              value-prop="id"
              :placeholder="t('manufacturing.unit')"
              :can-deselect="true"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="t('manufacturing.expected_wastage')">
            <BaseInput v-model="form.expected_wastage_percent" type="number" step="0.01" min="0" max="100" />
          </BaseInputGroup>

          <BaseInputGroup :label="t('manufacturing.labor_cost_per_unit')">
            <BaseInput v-model="laborCostDisplay" type="number" step="0.01" min="0" />
          </BaseInputGroup>

          <BaseInputGroup :label="t('manufacturing.overhead_cost_per_unit')">
            <BaseInput v-model="overheadCostDisplay" type="number" step="0.01" min="0" />
          </BaseInputGroup>

          <BaseInputGroup :label="t('manufacturing.description')" class="md:col-span-2">
            <textarea v-model="form.description" rows="2" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"></textarea>
          </BaseInputGroup>
        </div>
      </div>

      <!-- AI Material Suggestions -->
      <div v-if="form.name && form.name.length >= 3" class="rounded-lg border border-purple-200 bg-purple-50 p-4 shadow">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-2">
            <span class="text-sm font-medium text-purple-900">{{ t('manufacturing.ai_suggest_materials') }}</span>
            <span class="rounded-full bg-purple-100 px-2 py-0.5 text-xs text-purple-700">AI</span>
          </div>
          <BaseButton
            variant="primary-outline"
            size="sm"
            :loading="aiLoading"
            @click="suggestMaterials"
          >
            {{ t('manufacturing.ai_suggest') }}
          </BaseButton>
        </div>
        <div v-if="aiSuggestions.length > 0" class="mt-3 space-y-2">
          <div
            v-for="(suggestion, idx) in aiSuggestions"
            :key="idx"
            class="flex items-center justify-between rounded border border-purple-100 bg-white p-2 text-sm"
          >
            <span>{{ suggestion.name }} — {{ suggestion.quantity }} {{ suggestion.unit }}</span>
            <BaseButton size="sm" variant="primary-outline" @click="applySuggestion(suggestion)">
              + {{ t('manufacturing.add_line') }}
            </BaseButton>
          </div>
        </div>
      </div>

      <!-- Material Lines -->
      <div class="rounded-lg bg-white p-6 shadow">
        <div class="mb-4 flex items-center justify-between">
          <h3 class="text-lg font-medium text-gray-900">{{ t('manufacturing.lines') }}</h3>
          <BaseButton variant="primary-outline" size="sm" @click="addLine">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ t('manufacturing.add_line') }}
          </BaseButton>
        </div>

        <div v-if="form.lines.length === 0" class="rounded border-2 border-dashed border-gray-300 py-8 text-center text-gray-500">
          {{ t('manufacturing.add_line') }}
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="(line, index) in form.lines"
            :key="index"
            class="grid grid-cols-12 items-end gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3"
          >
            <div class="col-span-4">
              <label class="mb-1 block text-xs font-medium text-gray-600">{{ t('manufacturing.material') }}</label>
              <BaseMultiselect
                v-model="line.item_id"
                :options="itemOptions"
                label="name"
                value-prop="id"
                :placeholder="t('manufacturing.select_item')"
                :searchable="true"
              />
            </div>
            <div class="col-span-2">
              <label class="mb-1 block text-xs font-medium text-gray-600">{{ t('manufacturing.quantity') }}</label>
              <BaseInput v-model="line.quantity" type="number" step="0.0001" min="0.0001" />
            </div>
            <div class="col-span-2">
              <label class="mb-1 block text-xs font-medium text-gray-600">{{ t('manufacturing.unit') }}</label>
              <BaseMultiselect
                v-model="line.unit_id"
                :options="unitOptions"
                label="name"
                value-prop="id"
                :can-deselect="true"
              />
            </div>
            <div class="col-span-2">
              <label class="mb-1 block text-xs font-medium text-gray-600">{{ t('manufacturing.wastage_percent') }}</label>
              <BaseInput v-model="line.wastage_percent" type="number" step="0.01" min="0" max="100" />
            </div>
            <div class="col-span-2 text-right">
              <BaseButton variant="danger" size="sm" @click="removeLine(index)">
                <BaseIcon name="TrashIcon" class="h-4 w-4" />
              </BaseButton>
            </div>
          </div>
        </div>
      </div>

      <!-- Live Cost Preview -->
      <div v-if="form.lines.length > 0" class="rounded-lg border border-gray-200 bg-white p-6 shadow">
        <h3 class="mb-3 text-lg font-medium text-gray-900">{{ t('manufacturing.cost_preview') }}</h3>
        <div class="grid grid-cols-2 gap-2 text-sm md:grid-cols-4">
          <div class="rounded bg-blue-50 p-3">
            <p class="text-xs text-gray-500">{{ t('manufacturing.total_material_cost') }}</p>
            <p class="text-lg font-semibold text-blue-800">{{ formatMoney(liveCost.material) }}</p>
          </div>
          <div class="rounded bg-green-50 p-3">
            <p class="text-xs text-gray-500">{{ t('manufacturing.total_labor_cost') }}</p>
            <p class="text-lg font-semibold text-green-800">{{ formatMoney(form.labor_cost_per_unit) }}</p>
          </div>
          <div class="rounded bg-orange-50 p-3">
            <p class="text-xs text-gray-500">{{ t('manufacturing.total_overhead_cost') }}</p>
            <p class="text-lg font-semibold text-orange-800">{{ formatMoney(form.overhead_cost_per_unit) }}</p>
          </div>
          <div class="rounded bg-purple-50 p-3">
            <p class="text-xs text-gray-500">{{ t('manufacturing.cost_per_unit') }}</p>
            <p class="text-lg font-semibold text-purple-800">{{ formatMoney(liveCost.total) }}</p>
          </div>
        </div>
      </div>

      <!-- Submit -->
      <div class="flex justify-end space-x-3">
        <router-link to="/admin/manufacturing/boms">
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
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'

const router = useRouter()
const notificationStore = useNotificationStore()
const { t } = useI18n()

const itemOptions = ref([])
const unitOptions = ref([])
const isSaving = ref(false)
const aiLoading = ref(false)
const aiSuggestions = ref([])
const itemPrices = ref({}) // item_id → WAC (cents)

const form = reactive({
  name: '',
  output_item_id: null,
  output_quantity: '1',
  output_unit_id: null,
  expected_wastage_percent: '',
  labor_cost_per_unit: 0,
  overhead_cost_per_unit: 0,
  description: '',
  lines: [],
})

// Display values in MKD (user sees denars, API expects cents)
const laborCostDisplay = computed({
  get: () => form.labor_cost_per_unit ? (form.labor_cost_per_unit / 100).toFixed(2) : '',
  set: (val) => { form.labor_cost_per_unit = val ? Math.round(parseFloat(val) * 100) : 0 },
})

const overheadCostDisplay = computed({
  get: () => form.overhead_cost_per_unit ? (form.overhead_cost_per_unit / 100).toFixed(2) : '',
  set: (val) => { form.overhead_cost_per_unit = val ? Math.round(parseFloat(val) * 100) : 0 },
})

// Live cost calculation
const liveCost = computed(() => {
  const outputQty = parseFloat(form.output_quantity) || 1
  let materialCost = 0

  for (const line of form.lines) {
    if (!line.item_id || !line.quantity) continue
    const wac = itemPrices.value[line.item_id] || 0
    const qty = parseFloat(line.quantity) || 0
    const wastage = 1 + ((parseFloat(line.wastage_percent) || 0) / 100)
    materialCost += Math.round((qty / outputQty) * wastage * wac)
  }

  const labor = form.labor_cost_per_unit || 0
  const overhead = form.overhead_cost_per_unit || 0

  return {
    material: materialCost,
    total: materialCost + labor + overhead,
  }
})

function formatMoney(cents) {
  if (!cents) return '0.00 ден.'
  return (cents / 100).toLocaleString('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ден.'
}

function addLine() {
  form.lines.push({
    item_id: null,
    quantity: '',
    unit_id: null,
    wastage_percent: '0',
  })
}

function removeLine(index) {
  form.lines.splice(index, 1)
}

async function suggestMaterials() {
  if (!form.name || form.name.length < 3) return
  aiLoading.value = true
  aiSuggestions.value = []
  try {
    const res = await window.axios.post('/manufacturing/ai/suggest-materials', {
      product_name: form.name,
    })
    aiSuggestions.value = res.data?.data?.materials || []
  } catch {
    // AI unavailable — silently ignore
  } finally {
    aiLoading.value = false
  }
}

function applySuggestion(suggestion) {
  form.lines.push({
    item_id: suggestion.item_id || null,
    quantity: String(suggestion.quantity || ''),
    unit_id: null,
    wastage_percent: String(suggestion.wastage_percent || '0'),
  })
}

async function submitForm() {
  if (!form.name || !form.output_item_id || form.lines.length === 0) {
    notificationStore.showNotification({
      type: 'error',
      message: t('manufacturing.error_loading'),
    })
    return
  }

  isSaving.value = true
  try {
    const payload = {
      name: form.name,
      output_item_id: form.output_item_id,
      output_quantity: parseFloat(form.output_quantity),
      output_unit_id: form.output_unit_id,
      expected_wastage_percent: form.expected_wastage_percent ? parseFloat(form.expected_wastage_percent) : null,
      labor_cost_per_unit: form.labor_cost_per_unit || 0,
      overhead_cost_per_unit: form.overhead_cost_per_unit || 0,
      description: form.description || null,
      lines: form.lines.map((line, i) => ({
        item_id: line.item_id,
        quantity: parseFloat(line.quantity),
        unit_id: line.unit_id,
        wastage_percent: line.wastage_percent ? parseFloat(line.wastage_percent) : 0,
        sort_order: i,
      })),
    }

    const response = await window.axios.post('/manufacturing/boms', payload)

    notificationStore.showNotification({
      type: 'success',
      message: t('manufacturing.created_success'),
    })

    router.push(`/admin/manufacturing/boms/${response.data.data.id}`)
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
    const [itemsRes, unitsRes] = await Promise.all([
      window.axios.get('/items', { params: { limit: 500 } }),
      window.axios.get('/units'),
    ])
    const items = itemsRes.data?.data || itemsRes.data || []
    itemOptions.value = items
    unitOptions.value = unitsRes.data?.data || unitsRes.data || []

    // Build price map from item cost (stored in cents)
    for (const item of items) {
      if (item.cost) itemPrices.value[item.id] = item.cost
    }
  } catch {
    // Options will stay empty
  }

  // Start with one empty line
  addLine()
})
</script>
