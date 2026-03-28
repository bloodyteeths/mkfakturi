<template>
  <BasePage>
    <BasePageHeader :title="order ? order.order_number : t('manufacturing.view_order')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="t('manufacturing.title')" to="/admin/manufacturing" />
        <BaseBreadcrumbItem :title="t('manufacturing.orders')" to="/admin/manufacturing/orders" />
        <BaseBreadcrumbItem :title="order?.order_number || '...'" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton
          v-if="order && order.status === 'draft'"
          variant="primary"
          @click="startProduction"
          :loading="isActing"
        >
          {{ t('manufacturing.start_production') }}
        </BaseButton>
        <BaseButton
          v-if="order && order.status === 'in_progress'"
          variant="primary"
          @click="showCompleteModal = true"
        >
          {{ t('manufacturing.complete_production') }}
        </BaseButton>
        <BaseButton
          v-if="order && order.status !== 'completed'"
          variant="danger"
          @click="cancelOrder"
        >
          {{ t('manufacturing.cancel_production') }}
        </BaseButton>
        <BaseButton
          v-if="order"
          variant="primary-outline"
          @click="downloadPdf('order')"
        >
          {{ t('manufacturing.print_order') }}
        </BaseButton>
        <BaseButton
          v-if="order && order.status === 'completed'"
          variant="primary-outline"
          @click="downloadPdf('costing')"
        >
          {{ t('manufacturing.print_costing') }}
        </BaseButton>
        <BaseButton
          v-if="order"
          variant="primary-outline"
          @click="downloadPdf('trebovnica')"
        >
          {{ t('manufacturing.print_trebovnica') }}
        </BaseButton>
        <BaseButton
          v-if="order && order.status !== 'draft'"
          variant="primary-outline"
          @click="downloadPdf('izdatnica')"
        >
          {{ t('manufacturing.print_izdatnica') }}
        </BaseButton>
        <BaseButton
          v-if="order && order.status === 'completed'"
          variant="primary-outline"
          @click="downloadPdf('priemnica')"
        >
          {{ t('manufacturing.print_priemnica') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Loading -->
    <div v-if="isLoading" class="space-y-4 rounded-lg bg-white p-6 shadow">
      <div v-for="i in 8" :key="i" class="h-4 animate-pulse rounded bg-gray-200"></div>
    </div>

    <template v-else-if="order">
      <!-- Header Info -->
      <div class="rounded-lg bg-white p-6 shadow">
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.order_number') }}</p>
            <p class="mt-1 text-sm font-semibold text-gray-900">{{ order.order_number }}</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.status') }}</p>
            <span :class="statusClass(order.status)" class="mt-1 inline-flex rounded-full px-2 text-xs font-semibold leading-5">
              {{ t('manufacturing.status_' + order.status) }}
            </span>
          </div>
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.output_item') }}</p>
            <p class="mt-1 text-sm text-gray-900">{{ order.output_item?.name || '-' }}</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.bom_code') }}</p>
            <p class="mt-1 text-sm text-gray-900">{{ order.bom?.code || '-' }}</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.order_date') }}</p>
            <p class="mt-1 text-sm text-gray-900">{{ formatDate(order.order_date) }}</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.planned_quantity') }}</p>
            <p class="mt-1 text-sm text-gray-900">{{ order.planned_quantity }}</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.actual_quantity') }}</p>
            <p class="mt-1 text-sm text-gray-900">{{ order.actual_quantity || '-' }}</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.output_warehouse') }}</p>
            <p class="mt-1 text-sm text-gray-900">{{ order.output_warehouse?.name || '-' }}</p>
          </div>
        </div>
      </div>

      <!-- Cost Summary -->
      <div class="mt-6 rounded-lg bg-white p-6 shadow">
        <h3 class="mb-4 text-lg font-medium text-gray-900">{{ t('manufacturing.cost_summary') }}</h3>
        <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.total_material_cost') }}</p>
            <p class="mt-1 text-sm font-semibold text-gray-900">{{ formatMoney(order.total_material_cost) }}</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.total_labor_cost') }}</p>
            <p class="mt-1 text-sm font-semibold text-gray-900">{{ formatMoney(order.total_labor_cost) }}</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.total_overhead_cost') }}</p>
            <p class="mt-1 text-sm font-semibold text-gray-900">{{ formatMoney(order.total_overhead_cost) }}</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.total_wastage_cost') }}</p>
            <p class="mt-1 text-sm font-semibold text-gray-900">{{ formatMoney(order.total_wastage_cost) }}</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.total_production_cost') }}</p>
            <p class="mt-1 text-lg font-bold text-primary-600">{{ formatMoney(order.total_production_cost) }}</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.cost_per_unit') }}</p>
            <p class="mt-1 text-sm font-semibold text-gray-900">{{ formatMoney(order.cost_per_unit) }}</p>
          </div>
        </div>

        <!-- Variance -->
        <div v-if="order.status === 'completed' && order.total_variance !== 0" class="mt-4 border-t border-gray-200 pt-4">
          <h4 class="mb-2 text-sm font-medium text-gray-700">{{ t('manufacturing.variance_analysis') }}</h4>
          <div class="grid grid-cols-3 gap-4">
            <div>
              <p class="text-xs text-gray-500">{{ t('manufacturing.material_variance') }}</p>
              <p :class="order.material_variance > 0 ? 'text-red-600' : 'text-green-600'" class="text-sm font-semibold">
                {{ formatMoney(order.material_variance) }}
                <span class="text-xs">{{ order.material_variance > 0 ? t('manufacturing.unfavorable') : t('manufacturing.favorable') }}</span>
              </p>
            </div>
            <div>
              <p class="text-xs text-gray-500">{{ t('manufacturing.labor_variance') }}</p>
              <p :class="order.labor_variance > 0 ? 'text-red-600' : 'text-green-600'" class="text-sm font-semibold">
                {{ formatMoney(order.labor_variance) }}
              </p>
            </div>
            <div>
              <p class="text-xs text-gray-500">{{ t('manufacturing.total_variance') }}</p>
              <p :class="order.total_variance > 0 ? 'text-red-600' : 'text-green-600'" class="text-sm font-bold">
                {{ formatMoney(order.total_variance) }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Materials Table -->
      <div class="mt-6 rounded-lg bg-white p-6 shadow">
        <h3 class="mb-4 text-lg font-medium text-gray-900">{{ t('manufacturing.lines') }}</h3>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.material') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.planned_qty') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.actual_qty') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.wastage_qty') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.unit_cost') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.actual_cost') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="mat in order.materials" :key="mat.id">
                <td class="px-4 py-3 text-sm text-gray-900">{{ mat.item?.name || '-' }}</td>
                <td class="px-4 py-3 text-right text-sm text-gray-600">{{ mat.planned_quantity }}</td>
                <td class="px-4 py-3 text-right text-sm text-gray-900">{{ mat.actual_quantity || '-' }}</td>
                <td class="px-4 py-3 text-right text-sm text-orange-600">{{ mat.wastage_quantity || '-' }}</td>
                <td class="px-4 py-3 text-right text-sm text-gray-600">{{ formatMoney(mat.actual_unit_cost) }}</td>
                <td class="px-4 py-3 text-right text-sm font-medium text-gray-900">{{ formatMoney(mat.actual_total_cost) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Labor Entries -->
      <div v-if="order.labor_entries && order.labor_entries.length > 0" class="mt-6 rounded-lg bg-white p-6 shadow">
        <h3 class="mb-4 text-lg font-medium text-gray-900">{{ t('manufacturing.labor') }}</h3>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.description') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.hours') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.rate_per_hour') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.actual_cost') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="labor in order.labor_entries" :key="labor.id">
                <td class="px-4 py-3 text-sm text-gray-900">{{ labor.description }}</td>
                <td class="px-4 py-3 text-right text-sm text-gray-600">{{ labor.hours }}</td>
                <td class="px-4 py-3 text-right text-sm text-gray-600">{{ formatMoney(labor.rate_per_hour) }}</td>
                <td class="px-4 py-3 text-right text-sm font-medium text-gray-900">{{ formatMoney(labor.total_cost) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Overhead Entries -->
      <div v-if="order.overhead_entries && order.overhead_entries.length > 0" class="mt-6 rounded-lg bg-white p-6 shadow">
        <h3 class="mb-4 text-lg font-medium text-gray-900">{{ t('manufacturing.overhead') }}</h3>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.description') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.allocation_method') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ $t('general.amount') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="oh in order.overhead_entries" :key="oh.id">
                <td class="px-4 py-3 text-sm text-gray-900">{{ oh.description }}</td>
                <td class="px-4 py-3 text-right text-sm text-gray-600">{{ t('manufacturing.' + oh.allocation_method) }}</td>
                <td class="px-4 py-3 text-right text-sm font-medium text-gray-900">{{ formatMoney(oh.amount) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Quality Control Checks -->
      <div class="mt-6 rounded-lg bg-white p-6 shadow">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900">{{ t('manufacturing.qc_title') }}</h3>
          <BaseButton
            v-if="order.status === 'in_progress' || order.status === 'draft'"
            variant="primary-outline"
            size="sm"
            @click="showQcModal = true"
          >
            {{ t('manufacturing.qc_add_check') }}
          </BaseButton>
        </div>

        <div v-if="qcChecks.length > 0" class="space-y-3">
          <div
            v-for="check in qcChecks"
            :key="check.id"
            class="rounded-lg border p-4"
            :class="{
              'border-green-200 bg-green-50': check.result === 'pass',
              'border-red-200 bg-red-50': check.result === 'fail',
              'border-yellow-200 bg-yellow-50': check.result === 'conditional',
            }"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <span
                  class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold"
                  :class="{
                    'bg-green-100 text-green-800': check.result === 'pass',
                    'bg-red-100 text-red-800': check.result === 'fail',
                    'bg-yellow-100 text-yellow-800': check.result === 'conditional',
                  }"
                >
                  {{ t('manufacturing.qc_result_' + check.result) }}
                </span>
                <span class="text-sm text-gray-600">{{ formatDate(check.check_date) }}</span>
                <span v-if="check.inspector" class="text-xs text-gray-500">{{ check.inspector.name }}</span>
              </div>
              <div class="text-right text-sm">
                <span class="text-green-700">{{ check.quantity_passed }} {{ t('manufacturing.qc_passed') }}</span>
                <span v-if="check.quantity_rejected > 0" class="ml-2 text-red-700">{{ check.quantity_rejected }} {{ t('manufacturing.qc_rejected') }}</span>
              </div>
            </div>

            <!-- Checklist items -->
            <div v-if="check.checklist && check.checklist.length > 0" class="mt-2 flex flex-wrap gap-1.5">
              <span
                v-for="(item, ci) in check.checklist"
                :key="ci"
                class="inline-flex items-center rounded-md px-2 py-0.5 text-xs"
                :class="{
                  'bg-green-100 text-green-700': item.result === 'pass',
                  'bg-red-100 text-red-700': item.result === 'fail',
                  'bg-gray-100 text-gray-500': item.result === 'na',
                }"
              >
                {{ item.criterion }}
              </span>
            </div>

            <!-- Defects -->
            <div v-if="check.defects && check.defects.length > 0" class="mt-2">
              <p class="text-xs font-medium text-red-700">{{ t('manufacturing.qc_defects') }}:</p>
              <ul class="mt-1 space-y-0.5">
                <li v-for="(d, di) in check.defects" :key="di" class="text-xs text-red-600">
                  {{ d.type }} ({{ d.quantity }}) — {{ d.severity || 'minor' }}
                  <span v-if="d.notes" class="text-gray-500">{{ d.notes }}</span>
                </li>
              </ul>
            </div>

            <p v-if="check.notes" class="mt-2 text-xs text-gray-600">{{ check.notes }}</p>
          </div>
        </div>
        <div v-else class="py-4 text-center text-sm text-gray-500">
          {{ t('manufacturing.qc_no_checks') }}
        </div>
      </div>

      <!-- Notes -->
      <div v-if="order.notes" class="mt-6 rounded-lg bg-white p-6 shadow">
        <h3 class="mb-2 text-lg font-medium text-gray-900">{{ t('manufacturing.notes') }}</h3>
        <p class="whitespace-pre-wrap text-sm text-gray-700">{{ order.notes }}</p>
      </div>
    </template>

    <!-- Complete Modal -->
    <teleport to="body">
      <div v-if="showCompleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="mx-4 w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
          <h3 class="text-lg font-medium text-gray-900">{{ t('manufacturing.complete_production') }}</h3>
          <div class="mt-4">
            <BaseInputGroup :label="t('manufacturing.actual_quantity')" required>
              <BaseInput v-model="completeQty" type="number" step="0.0001" min="0" />
            </BaseInputGroup>
          </div>
          <div class="mt-6 flex justify-end space-x-3">
            <BaseButton variant="primary-outline" @click="showCompleteModal = false">
              {{ $t('general.cancel') }}
            </BaseButton>
            <BaseButton variant="primary" :loading="isActing" @click="completeProduction">
              {{ t('manufacturing.complete_production') }}
            </BaseButton>
          </div>
        </div>
      </div>
    </teleport>

    <!-- QC Check Modal -->
    <teleport to="body">
      <div v-if="showQcModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="mx-4 w-full max-w-lg rounded-lg bg-white p-6 shadow-xl max-h-[90vh] overflow-y-auto">
          <h3 class="text-lg font-medium text-gray-900">{{ t('manufacturing.qc_add_check') }}</h3>

          <div class="mt-4 space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <BaseInputGroup :label="t('manufacturing.qc_check_date')" required>
                <BaseInput v-model="qcForm.check_date" type="date" />
              </BaseInputGroup>
              <BaseInputGroup :label="t('manufacturing.qc_result')" required>
                <select v-model="qcForm.result" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                  <option value="pass">{{ t('manufacturing.qc_result_pass') }}</option>
                  <option value="fail">{{ t('manufacturing.qc_result_fail') }}</option>
                  <option value="conditional">{{ t('manufacturing.qc_result_conditional') }}</option>
                </select>
              </BaseInputGroup>
            </div>

            <div class="grid grid-cols-3 gap-4">
              <BaseInputGroup :label="t('manufacturing.qc_qty_inspected')">
                <BaseInput v-model="qcForm.quantity_inspected" type="number" step="0.01" min="0" />
              </BaseInputGroup>
              <BaseInputGroup :label="t('manufacturing.qc_passed')">
                <BaseInput v-model="qcForm.quantity_passed" type="number" step="0.01" min="0" />
              </BaseInputGroup>
              <BaseInputGroup :label="t('manufacturing.qc_rejected')">
                <BaseInput v-model="qcForm.quantity_rejected" type="number" step="0.01" min="0" />
              </BaseInputGroup>
            </div>

            <!-- Checklist -->
            <div>
              <div class="flex items-center justify-between mb-2">
                <label class="text-sm font-medium text-gray-700">{{ t('manufacturing.qc_checklist') }}</label>
                <button @click="addChecklistItem" class="text-xs text-primary-600 hover:text-primary-700">+ {{ t('manufacturing.qc_add_criterion') }}</button>
              </div>
              <div v-for="(item, idx) in qcForm.checklist" :key="idx" class="flex items-center gap-2 mb-2">
                <input
                  v-model="item.criterion"
                  type="text"
                  :placeholder="t('manufacturing.qc_criterion_placeholder')"
                  class="flex-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                />
                <select v-model="item.result" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                  <option value="pass">{{ t('manufacturing.qc_result_pass') }}</option>
                  <option value="fail">{{ t('manufacturing.qc_result_fail') }}</option>
                  <option value="na">N/A</option>
                </select>
                <button @click="qcForm.checklist.splice(idx, 1)" class="text-red-400 hover:text-red-600 text-sm">X</button>
              </div>
            </div>

            <!-- Defects -->
            <div v-if="qcForm.result !== 'pass'">
              <div class="flex items-center justify-between mb-2">
                <label class="text-sm font-medium text-gray-700">{{ t('manufacturing.qc_defects') }}</label>
                <button @click="addDefect" class="text-xs text-red-600 hover:text-red-700">+ {{ t('manufacturing.qc_add_defect') }}</button>
              </div>
              <div v-for="(d, idx) in qcForm.defects" :key="idx" class="flex items-center gap-2 mb-2">
                <input
                  v-model="d.type"
                  type="text"
                  :placeholder="t('manufacturing.qc_defect_type')"
                  class="flex-1 rounded-md border-gray-300 text-sm shadow-sm"
                />
                <input v-model="d.quantity" type="number" min="0" step="1" class="w-16 rounded-md border-gray-300 text-sm shadow-sm" />
                <select v-model="d.severity" class="rounded-md border-gray-300 text-sm shadow-sm">
                  <option value="minor">Minor</option>
                  <option value="major">Major</option>
                  <option value="critical">Critical</option>
                </select>
                <button @click="qcForm.defects.splice(idx, 1)" class="text-red-400 hover:text-red-600 text-sm">X</button>
              </div>
            </div>

            <BaseInputGroup :label="t('manufacturing.notes')">
              <textarea v-model="qcForm.notes" rows="2" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
            </BaseInputGroup>
          </div>

          <div class="mt-6 flex justify-end space-x-3">
            <BaseButton variant="primary-outline" @click="showQcModal = false">
              {{ $t('general.cancel') }}
            </BaseButton>
            <BaseButton variant="primary" :loading="isActing" @click="submitQcCheck">
              {{ t('manufacturing.qc_submit') }}
            </BaseButton>
          </div>
        </div>
      </div>
    </teleport>
  </BasePage>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'

const route = useRoute()
const router = useRouter()
const notificationStore = useNotificationStore()
const { t, locale } = useI18n()

const order = ref(null)
const isLoading = ref(true)
const isActing = ref(false)
const showCompleteModal = ref(false)
const completeQty = ref('')

// QC state
const showQcModal = ref(false)
const qcChecks = ref([])
const qcForm = ref({
  check_date: new Date().toISOString().slice(0, 10),
  result: 'pass',
  quantity_inspected: 0,
  quantity_passed: 0,
  quantity_rejected: 0,
  notes: '',
  checklist: [],
  defects: [],
})

const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }

function formatMoney(cents) {
  if (cents === null || cents === undefined) return '-'
  const fmtLocale = localeMap[locale.value] || 'mk-MK'
  return new Intl.NumberFormat(fmtLocale, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(cents / 100)
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  const fmtLocale = localeMap[locale.value] || 'mk-MK'
  return d.toLocaleDateString(fmtLocale, { year: 'numeric', month: '2-digit', day: '2-digit' })
}

function statusClass(status) {
  return {
    draft: 'bg-gray-100 text-gray-800',
    in_progress: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
  }[status] || 'bg-gray-100 text-gray-800'
}

async function fetchOrder() {
  isLoading.value = true
  try {
    const response = await window.axios.get(`/manufacturing/orders/${route.params.id}`)
    order.value = response.data.data
    completeQty.value = order.value.planned_quantity
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('manufacturing.error_loading'),
    })
  } finally {
    isLoading.value = false
  }
}

async function startProduction() {
  isActing.value = true
  try {
    await window.axios.post(`/manufacturing/orders/${route.params.id}/start`)
    notificationStore.showNotification({ type: 'success', message: t('manufacturing.started_success') })
    fetchOrder()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('manufacturing.error_loading'),
    })
  } finally {
    isActing.value = false
  }
}

async function completeProduction() {
  isActing.value = true
  try {
    await window.axios.post(`/manufacturing/orders/${route.params.id}/complete`, {
      actual_quantity: parseFloat(completeQty.value),
    })
    showCompleteModal.value = false
    notificationStore.showNotification({ type: 'success', message: t('manufacturing.completed_success') })
    fetchOrder()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('manufacturing.error_loading'),
    })
  } finally {
    isActing.value = false
  }
}

async function cancelOrder() {
  if (!confirm(t('manufacturing.confirm_cancel'))) return

  isActing.value = true
  try {
    await window.axios.post(`/manufacturing/orders/${route.params.id}/cancel`)
    notificationStore.showNotification({ type: 'success', message: t('manufacturing.cancelled_success') })
    fetchOrder()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('manufacturing.error_loading'),
    })
  } finally {
    isActing.value = false
  }
}

function downloadPdf(type) {
  const url = `/api/v1/manufacturing/orders/${route.params.id}/pdf/${type}?preview=1`
  window.open(url, '_blank')
}

// ===== QC Functions =====
async function fetchQcChecks() {
  try {
    const res = await window.axios.get(`/manufacturing/orders/${route.params.id}/qc-checks`)
    qcChecks.value = res.data?.data || []
  } catch {
    // silently fail — QC section will show empty
  }
}

function addChecklistItem() {
  qcForm.value.checklist.push({ criterion: '', result: 'pass', notes: '' })
}

function addDefect() {
  qcForm.value.defects.push({ type: '', quantity: 1, severity: 'minor', notes: '' })
}

function resetQcForm() {
  qcForm.value = {
    check_date: new Date().toISOString().slice(0, 10),
    result: 'pass',
    quantity_inspected: order.value?.planned_quantity || 0,
    quantity_passed: order.value?.planned_quantity || 0,
    quantity_rejected: 0,
    notes: '',
    checklist: [],
    defects: [],
  }
}

async function submitQcCheck() {
  isActing.value = true
  try {
    const payload = {
      ...qcForm.value,
      quantity_inspected: parseFloat(qcForm.value.quantity_inspected) || 0,
      quantity_passed: parseFloat(qcForm.value.quantity_passed) || 0,
      quantity_rejected: parseFloat(qcForm.value.quantity_rejected) || 0,
      checklist: qcForm.value.checklist.filter(c => c.criterion.trim()),
      defects: qcForm.value.defects.filter(d => d.type.trim()),
    }
    await window.axios.post(`/manufacturing/orders/${route.params.id}/qc-checks`, payload)
    notificationStore.showNotification({ type: 'success', message: t('manufacturing.qc_saved') })
    showQcModal.value = false
    resetQcForm()
    fetchQcChecks()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('manufacturing.error_loading'),
    })
  } finally {
    isActing.value = false
  }
}

onMounted(async () => {
  await fetchOrder()
  fetchQcChecks()
})
</script>
