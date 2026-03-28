<template>
  <BasePage>
    <BasePageHeader :title="t('manufacturing.shop_floor_title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="t('manufacturing.title')" to="/admin/manufacturing" />
        <BaseBreadcrumbItem :title="t('manufacturing.shop_floor_title')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="h-10 w-10 animate-spin rounded-full border-b-2 border-primary-600"></div>
    </div>

    <!-- Mobile-first card grid -->
    <div v-else class="mt-4 space-y-3">

      <!-- Barcode Scanner Panel -->
      <div class="rounded-lg bg-gradient-to-r from-indigo-50 to-purple-50 p-4 shadow">
        <div class="flex items-center gap-3 mb-3">
          <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-100">
            <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
            </svg>
          </div>
          <h3 class="text-sm font-semibold text-gray-900">{{ t('manufacturing.scan_barcode') }}</h3>
        </div>

        <!-- Order selector -->
        <div class="mb-3">
          <select
            v-model="scanOrderId"
            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          >
            <option :value="null">{{ t('manufacturing.scan_select_order') }}</option>
            <option v-for="o in inProgressOrders" :key="o.id" :value="o.id">
              {{ o.order_number }} — {{ o.item_name }}
            </option>
          </select>
        </div>

        <!-- Barcode input + quantity -->
        <div class="flex gap-2">
          <input
            ref="barcodeInput"
            v-model="barcodeValue"
            @keydown.enter.prevent="onBarcodeScan"
            :placeholder="t('manufacturing.scan_placeholder')"
            :disabled="!scanOrderId"
            class="flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100"
          />
          <input
            v-model.number="scanQty"
            type="number"
            min="0.01"
            step="0.01"
            :disabled="!scanOrderId"
            class="w-20 rounded-lg border-gray-300 text-center text-sm shadow-sm"
            :placeholder="t('manufacturing.scan_qty')"
          />
          <button
            @click="onBarcodeScan"
            :disabled="!scanOrderId || !barcodeValue || scanning"
            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700 disabled:opacity-50"
          >
            <span v-if="scanning" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent inline-block"></span>
            <span v-else>OK</span>
          </button>
        </div>

        <!-- Last scan result -->
        <div v-if="lastScan" class="mt-2 rounded-lg px-3 py-2 text-sm" :class="lastScan.success ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
          {{ lastScan.message }}
        </div>
      </div>

      <!-- Filter bar -->
      <div class="flex items-center gap-2 rounded-lg bg-white p-3 shadow">
        <button
          v-for="f in filters"
          :key="f.key"
          @click="activeFilter = f.key"
          class="flex-1 rounded-lg px-3 py-2 text-center text-sm font-medium transition"
          :class="activeFilter === f.key
            ? 'bg-primary-100 text-primary-700'
            : 'text-gray-600 hover:bg-gray-100'"
        >
          {{ f.label }}
          <span v-if="f.count > 0" class="ml-1 inline-flex h-5 min-w-[20px] items-center justify-center rounded-full text-xs font-bold"
            :class="activeFilter === f.key ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-700'">
            {{ f.count }}
          </span>
        </button>
      </div>

      <!-- Empty state -->
      <div v-if="filteredOrders.length === 0" class="rounded-lg bg-white p-8 text-center shadow">
        <p class="text-sm text-gray-500">{{ t('manufacturing.shop_floor_empty') }}</p>
      </div>

      <!-- Order cards -->
      <div
        v-for="order in filteredOrders"
        :key="order.id"
        class="rounded-lg bg-white shadow"
      >
        <!-- Card header -->
        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
          <div>
            <p class="text-sm font-bold text-gray-900">{{ order.item_name }}</p>
            <p class="text-xs text-gray-500">{{ order.order_number }} · {{ order.bom_code }}</p>
          </div>
          <span
            class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
            :class="statusClass(order.status)"
          >
            {{ t('manufacturing.status_' + order.status) }}
          </span>
        </div>

        <!-- Progress bar -->
        <div class="px-4 py-3">
          <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
            <span>{{ t('manufacturing.shop_floor_progress') }}</span>
            <span class="font-medium text-gray-900">{{ progressPercent(order) }}%</span>
          </div>
          <div class="h-3 w-full overflow-hidden rounded-full bg-gray-100">
            <div
              class="h-full rounded-full transition-all duration-500"
              :class="order.is_overdue ? 'bg-red-500' : 'bg-primary-500'"
              :style="{ width: progressPercent(order) + '%' }"
            ></div>
          </div>
          <div class="mt-1.5 flex items-center justify-between text-xs text-gray-500">
            <span>{{ order.actual_quantity || 0 }} / {{ order.planned_quantity }} {{ t('manufacturing.unit') }}</span>
            <span v-if="order.is_overdue" class="text-red-600 font-medium">{{ t('manufacturing.gantt_overdue') }}</span>
            <span v-else-if="order.expected_completion">{{ t('manufacturing.shop_floor_due') }} {{ order.expected_completion }}</span>
          </div>
        </div>

        <!-- Work center -->
        <div v-if="order.work_center" class="flex items-center gap-2 border-t border-gray-50 px-4 py-2 text-xs text-gray-500">
          <span>{{ t('manufacturing.work_centers') }}: {{ order.work_center }}</span>
        </div>

        <!-- Action buttons -->
        <div class="flex border-t border-gray-100">
          <!-- Start production -->
          <button
            v-if="order.status === 'draft'"
            @click="startOrder(order)"
            :disabled="acting === order.id"
            class="flex flex-1 items-center justify-center gap-1.5 py-3 text-sm font-medium text-blue-700 transition hover:bg-blue-50 disabled:opacity-50"
          >
            <span v-if="acting === order.id" class="h-4 w-4 animate-spin rounded-full border-2 border-blue-300 border-t-blue-700"></span>
            <span v-else>{{ t('manufacturing.start_production') }}</span>
          </button>

          <!-- Record output (in progress) -->
          <button
            v-if="order.status === 'in_progress'"
            @click="openRecordOutput(order)"
            class="flex flex-1 items-center justify-center gap-1.5 border-r border-gray-100 py-3 text-sm font-medium text-primary-700 transition hover:bg-primary-50"
          >
            {{ t('manufacturing.shop_floor_record_output') }}
          </button>

          <!-- QC Check -->
          <button
            v-if="order.status === 'in_progress'"
            @click="openQcQuick(order)"
            class="flex flex-1 items-center justify-center gap-1.5 border-r border-gray-100 py-3 text-sm font-medium text-purple-700 transition hover:bg-purple-50"
          >
            {{ t('manufacturing.qc_add_check') }}
          </button>

          <!-- Complete -->
          <button
            v-if="order.status === 'in_progress'"
            @click="completeOrder(order)"
            :disabled="acting === order.id"
            class="flex flex-1 items-center justify-center gap-1.5 py-3 text-sm font-medium text-green-700 transition hover:bg-green-50 disabled:opacity-50"
          >
            <span v-if="acting === order.id" class="h-4 w-4 animate-spin rounded-full border-2 border-green-300 border-t-green-700"></span>
            <span v-else>{{ t('manufacturing.complete_production') }}</span>
          </button>

          <!-- View details (completed) -->
          <router-link
            v-if="order.status === 'completed'"
            :to="`/admin/manufacturing/orders/${order.id}`"
            class="flex flex-1 items-center justify-center gap-1.5 py-3 text-sm font-medium text-gray-600 transition hover:bg-gray-50"
          >
            {{ t('manufacturing.view_order') }}
          </router-link>
        </div>
      </div>
    </div>

    <!-- Record Output Modal -->
    <teleport to="body">
      <div v-if="showOutputModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black bg-opacity-50">
        <div class="w-full max-w-md rounded-t-2xl sm:rounded-lg bg-white p-6 shadow-xl">
          <h3 class="text-lg font-medium text-gray-900">{{ t('manufacturing.shop_floor_record_output') }}</h3>
          <p class="mt-1 text-sm text-gray-500">{{ selectedOrder?.item_name }} — {{ selectedOrder?.order_number }}</p>

          <div class="mt-4 space-y-4">
            <BaseInputGroup :label="t('manufacturing.actual_quantity')">
              <BaseInput v-model="outputQty" type="number" step="0.01" min="0" class="text-2xl" />
            </BaseInputGroup>
          </div>

          <div class="mt-6 flex gap-3">
            <BaseButton variant="primary-outline" class="flex-1" @click="showOutputModal = false">
              {{ $t('general.cancel') }}
            </BaseButton>
            <BaseButton variant="primary" class="flex-1" :loading="acting === selectedOrder?.id" @click="submitComplete">
              {{ t('manufacturing.complete_production') }}
            </BaseButton>
          </div>
        </div>
      </div>
    </teleport>

    <!-- Quick QC Modal with Disposition -->
    <teleport to="body">
      <div v-if="showQcQuick" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black bg-opacity-50">
        <div class="w-full max-w-md rounded-t-2xl sm:rounded-lg bg-white p-6 shadow-xl">
          <h3 class="text-lg font-medium text-gray-900">{{ t('manufacturing.qc_add_check') }}</h3>
          <p class="mt-1 text-sm text-gray-500">{{ selectedOrder?.item_name }}</p>

          <div class="mt-4 space-y-4">
            <div class="grid grid-cols-3 gap-2">
              <button
                v-for="r in ['pass', 'fail', 'conditional']"
                :key="r"
                @click="quickQc.result = r"
                class="rounded-lg border-2 px-3 py-3 text-center text-sm font-medium transition"
                :class="quickQc.result === r
                  ? (r === 'pass' ? 'border-green-500 bg-green-50 text-green-700' : r === 'fail' ? 'border-red-500 bg-red-50 text-red-700' : 'border-yellow-500 bg-yellow-50 text-yellow-700')
                  : 'border-gray-200 text-gray-600'"
              >
                {{ t('manufacturing.qc_result_' + r) }}
              </button>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <BaseInputGroup :label="t('manufacturing.qc_passed')">
                <BaseInput v-model="quickQc.quantity_passed" type="number" step="1" min="0" />
              </BaseInputGroup>
              <BaseInputGroup :label="t('manufacturing.qc_rejected')">
                <BaseInput v-model="quickQc.quantity_rejected" type="number" step="1" min="0" />
              </BaseInputGroup>
            </div>

            <!-- Disposition (only for fail/conditional) -->
            <div v-if="quickQc.result !== 'pass' && parseFloat(quickQc.quantity_rejected) > 0" class="rounded-lg border border-orange-200 bg-orange-50 p-3">
              <p class="text-xs font-medium text-orange-800 mb-2">{{ t('manufacturing.qc_disposition') }}</p>
              <div class="grid grid-cols-2 gap-2">
                <button
                  @click="quickQc.disposition = 'rework'"
                  class="rounded-lg border-2 px-3 py-2.5 text-center text-sm font-medium transition"
                  :class="quickQc.disposition === 'rework' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-200 text-gray-600'"
                >
                  {{ t('manufacturing.qc_disposition_rework') }}
                </button>
                <button
                  @click="quickQc.disposition = 'scrap'"
                  class="rounded-lg border-2 px-3 py-2.5 text-center text-sm font-medium transition"
                  :class="quickQc.disposition === 'scrap' ? 'border-red-500 bg-red-50 text-red-700' : 'border-gray-200 text-gray-600'"
                >
                  {{ t('manufacturing.qc_disposition_scrap') }}
                </button>
              </div>
            </div>

            <BaseInputGroup :label="t('manufacturing.notes')">
              <textarea v-model="quickQc.notes" rows="2" class="w-full rounded-md border-gray-300 text-sm shadow-sm"></textarea>
            </BaseInputGroup>
          </div>

          <div class="mt-6 flex gap-3">
            <BaseButton variant="primary-outline" class="flex-1" @click="showQcQuick = false">
              {{ $t('general.cancel') }}
            </BaseButton>
            <BaseButton variant="primary" class="flex-1" :loading="acting === selectedOrder?.id" @click="submitQuickQc">
              {{ t('manufacturing.qc_submit') }}
            </BaseButton>
          </div>
        </div>
      </div>
    </teleport>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'

const { t } = useI18n()
const notificationStore = useNotificationStore()

const loading = ref(true)
const orders = ref([])
const acting = ref(null)
const activeFilter = ref('in_progress')

// Barcode scanning
const barcodeInput = ref(null)
const barcodeValue = ref('')
const scanOrderId = ref(null)
const scanQty = ref(1)
const scanning = ref(false)
const lastScan = ref(null)

// Modals
const showOutputModal = ref(false)
const showQcQuick = ref(false)
const selectedOrder = ref(null)
const outputQty = ref(0)
const quickQc = ref({ result: 'pass', quantity_passed: 0, quantity_rejected: 0, notes: '', disposition: null })

const inProgressOrders = computed(() => orders.value.filter(o => o.status === 'in_progress'))

const filters = computed(() => [
  { key: 'in_progress', label: t('manufacturing.status_in_progress'), count: orders.value.filter(o => o.status === 'in_progress').length },
  { key: 'draft', label: t('manufacturing.status_draft'), count: orders.value.filter(o => o.status === 'draft').length },
  { key: 'completed', label: t('manufacturing.status_completed'), count: orders.value.filter(o => o.status === 'completed').length },
])

const filteredOrders = computed(() => {
  return orders.value.filter(o => o.status === activeFilter.value)
})

function statusClass(status) {
  return {
    draft: 'bg-gray-100 text-gray-800',
    in_progress: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
  }[status] || 'bg-gray-100 text-gray-800'
}

function progressPercent(order) {
  if (!order.planned_quantity || order.planned_quantity <= 0) return 0
  const actual = parseFloat(order.actual_quantity) || 0
  return Math.min(100, Math.round((actual / parseFloat(order.planned_quantity)) * 100))
}

// ===== Barcode Scanning =====
async function onBarcodeScan() {
  if (!scanOrderId.value || !barcodeValue.value || scanning.value) return
  scanning.value = true
  lastScan.value = null

  try {
    const res = await window.axios.post(`/manufacturing/orders/${scanOrderId.value}/scan`, {
      barcode: barcodeValue.value.trim(),
      quantity: scanQty.value || 1,
    })
    lastScan.value = {
      success: true,
      message: t('manufacturing.scan_success', { qty: res.data?.data?.quantity, item: res.data?.data?.item?.name }),
    }
    barcodeValue.value = ''
  } catch (error) {
    lastScan.value = {
      success: false,
      message: error.response?.data?.message || t('manufacturing.scan_not_found'),
    }
  } finally {
    scanning.value = false
    await nextTick()
    barcodeInput.value?.focus()
  }
}

// ===== Actions =====
async function fetchOrders() {
  loading.value = true
  try {
    const res = await window.axios.get('/manufacturing/orders', {
      params: { limit: 50, orderByField: 'order_date', orderBy: 'desc' },
    })
    orders.value = (res.data?.data || []).map(o => ({
      id: o.id,
      order_number: o.order_number,
      item_name: o.output_item?.name || '-',
      bom_code: o.bom?.code || '-',
      status: o.status,
      planned_quantity: o.planned_quantity,
      actual_quantity: o.actual_quantity,
      expected_completion: o.expected_completion_date,
      work_center: o.work_center?.name,
      is_overdue: o.expected_completion_date && new Date(o.expected_completion_date) < new Date() && o.status !== 'completed',
    }))
  } catch (error) {
    console.error('Failed to fetch orders:', error)
  } finally {
    loading.value = false
  }
}

async function startOrder(order) {
  acting.value = order.id
  try {
    await window.axios.post(`/manufacturing/orders/${order.id}/start`)
    notificationStore.showNotification({ type: 'success', message: t('manufacturing.started_success') })
    order.status = 'in_progress'
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: error.response?.data?.message || t('manufacturing.error_loading') })
  } finally {
    acting.value = null
  }
}

function openRecordOutput(order) {
  selectedOrder.value = order
  outputQty.value = order.planned_quantity
  showOutputModal.value = true
}

function completeOrder(order) {
  selectedOrder.value = order
  outputQty.value = order.planned_quantity
  showOutputModal.value = true
}

async function submitComplete() {
  if (!selectedOrder.value) return
  acting.value = selectedOrder.value.id
  try {
    await window.axios.post(`/manufacturing/orders/${selectedOrder.value.id}/complete`, {
      actual_quantity: parseFloat(outputQty.value),
    })
    notificationStore.showNotification({ type: 'success', message: t('manufacturing.completed_success') })
    showOutputModal.value = false
    selectedOrder.value.status = 'completed'
    selectedOrder.value.actual_quantity = outputQty.value
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: error.response?.data?.message || t('manufacturing.error_loading') })
  } finally {
    acting.value = null
  }
}

function openQcQuick(order) {
  selectedOrder.value = order
  quickQc.value = {
    result: 'pass',
    quantity_passed: order.planned_quantity,
    quantity_rejected: 0,
    notes: '',
    disposition: null,
  }
  showQcQuick.value = true
}

async function submitQuickQc() {
  if (!selectedOrder.value) return
  acting.value = selectedOrder.value.id
  try {
    const today = new Date()
    const dateStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`

    const qcRes = await window.axios.post(`/manufacturing/orders/${selectedOrder.value.id}/qc-checks`, {
      check_date: dateStr,
      result: quickQc.value.result,
      quantity_inspected: parseFloat(quickQc.value.quantity_passed) + parseFloat(quickQc.value.quantity_rejected),
      quantity_passed: parseFloat(quickQc.value.quantity_passed),
      quantity_rejected: parseFloat(quickQc.value.quantity_rejected),
      notes: quickQc.value.notes,
      checklist: [],
      defects: [],
    })

    // Process disposition if set
    if (quickQc.value.disposition && qcRes.data?.data?.id) {
      const checkId = qcRes.data.data.id
      try {
        await window.axios.post(`/manufacturing/orders/${selectedOrder.value.id}/qc-checks/${checkId}/dispose`, {
          disposition: quickQc.value.disposition,
        })
        const dispMsg = quickQc.value.disposition === 'rework'
          ? t('manufacturing.qc_rework_created')
          : t('manufacturing.qc_scrap_recorded')
        notificationStore.showNotification({ type: 'success', message: dispMsg })
      } catch (dispError) {
        notificationStore.showNotification({ type: 'warning', message: dispError.response?.data?.message || 'Disposition failed' })
      }
    }

    notificationStore.showNotification({ type: 'success', message: t('manufacturing.qc_saved') })
    showQcQuick.value = false
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: error.response?.data?.message || t('manufacturing.error_loading') })
  } finally {
    acting.value = null
  }
}

onMounted(() => fetchOrders())
</script>
