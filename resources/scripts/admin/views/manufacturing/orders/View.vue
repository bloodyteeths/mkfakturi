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

onMounted(() => fetchOrder())
</script>
