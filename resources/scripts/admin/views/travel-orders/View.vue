<template>
  <BasePage>
    <BasePageHeader :title="order ? order.travel_number : t('title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="t('title')" to="../travel-orders" />
        <BaseBreadcrumbItem
          :title="order ? order.travel_number : '...'"
          to="#"
          active
        />
      </BaseBreadcrumb>

      <template #actions>
        <div v-if="order" class="flex items-center space-x-2">
          <!-- Download PDF button (always visible) -->
          <BaseButton
            variant="primary-outline"
            :loading="isDownloading"
            @click="downloadPdf"
          >
            <template #left="slotProps">
              <BaseIcon name="ArrowDownTrayIcon" :class="slotProps.class" />
            </template>
            {{ t('download_pdf') }}
          </BaseButton>

          <!-- Approve button (draft / pending_approval) -->
          <BaseButton
            v-if="order.status === 'draft' || order.status === 'pending_approval'"
            variant="primary"
            :loading="isApproving"
            @click="showApproveDialog = true"
          >
            <template #left="slotProps">
              <BaseIcon name="CheckIcon" :class="slotProps.class" />
            </template>
            {{ t('approve') }}
          </BaseButton>

          <!-- Settle button (approved only) -->
          <BaseButton
            v-if="order.status === 'approved'"
            variant="primary"
            :loading="isSettling"
            @click="showSettleDialog = true"
          >
            <template #left="slotProps">
              <BaseIcon name="CalculatorIcon" :class="slotProps.class" />
            </template>
            {{ t('settle') }}
          </BaseButton>

          <!-- Reject button (draft / pending_approval) -->
          <BaseButton
            v-if="order.status === 'draft' || order.status === 'pending_approval'"
            variant="danger"
            :loading="isRejecting"
            @click="showRejectDialog = true"
          >
            <template #left="slotProps">
              <BaseIcon name="XMarkIcon" :class="slotProps.class" />
            </template>
            {{ t('reject') }}
          </BaseButton>

          <!-- Delete button (draft only) -->
          <BaseButton
            v-if="order.status === 'draft'"
            variant="danger"
            :loading="isDeleting"
            @click="showDeleteDialog = true"
          >
            <template #left="slotProps">
              <BaseIcon name="TrashIcon" :class="slotProps.class" />
            </template>
            {{ t('delete') }}
          </BaseButton>
        </div>
      </template>
    </BasePageHeader>

    <!-- Loading -->
    <div v-if="isLoading" class="bg-white rounded-lg shadow p-6">
      <div class="space-y-4">
        <div v-for="i in 6" :key="i" class="flex space-x-4 animate-pulse">
          <div class="h-4 bg-gray-200 rounded w-32"></div>
          <div class="h-4 bg-gray-200 rounded flex-1"></div>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div v-else-if="order" class="space-y-6">
      <!-- Header Card -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
          <div>
            <h3 class="text-lg font-medium text-gray-900">{{ order.travel_number }}</h3>
            <p class="text-sm text-gray-500">{{ order.purpose }}</p>
          </div>
          <span :class="statusBadgeClass(order.status)" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium">
            {{ statusLabel(order.status) }}
          </span>
        </div>

        <div class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
            <div>
              <p class="text-xs text-gray-500 uppercase font-medium">{{ t('type') }}</p>
              <p class="text-sm font-medium text-gray-900 mt-1">
                {{ order.type === 'domestic' ? t('domestic') : t('foreign') }}
              </p>
            </div>
            <div>
              <p class="text-xs text-gray-500 uppercase font-medium">{{ t('employee') }}</p>
              <p class="text-sm font-medium text-gray-900 mt-1">
                {{ order.employee ? `${order.employee.first_name} ${order.employee.last_name}` : '-' }}
              </p>
            </div>
            <div>
              <p class="text-xs text-gray-500 uppercase font-medium">{{ t('departure') }}</p>
              <p class="text-sm font-medium text-gray-900 mt-1">{{ formatDate(order.departure_date) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 uppercase font-medium">{{ t('return_date') }}</p>
              <p class="text-sm font-medium text-gray-900 mt-1">{{ formatDate(order.return_date) }}</p>
            </div>
            <div v-if="order.approved_by_user">
              <p class="text-xs text-gray-500 uppercase font-medium">{{ t('approved_by') }}</p>
              <p class="text-sm font-medium text-gray-900 mt-1">{{ order.approved_by_user.name }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Totals Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
          <p class="text-xs text-blue-600 uppercase font-medium">{{ t('total_per_diem') }}</p>
          <p class="text-xl font-bold text-blue-800">{{ formatMoney(order.total_per_diem) }}</p>
        </div>
        <div class="bg-amber-50 rounded-lg p-4 border border-amber-200">
          <p class="text-xs text-amber-600 uppercase font-medium">{{ t('total_mileage') }}</p>
          <p class="text-xl font-bold text-amber-800">{{ formatMoney(order.total_mileage_cost) }}</p>
        </div>
        <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
          <p class="text-xs text-purple-600 uppercase font-medium">{{ t('total_expenses') }}</p>
          <p class="text-xl font-bold text-purple-800">{{ formatMoney(order.total_expenses) }}</p>
        </div>
        <div class="bg-green-50 rounded-lg p-4 border border-green-200">
          <p class="text-xs text-green-600 uppercase font-medium">{{ t('grand_total') }}</p>
          <p class="text-xl font-bold text-green-800">{{ formatMoney(order.grand_total) }}</p>
        </div>
        <div :class="order.reimbursement_amount >= 0 ? 'bg-indigo-50 border-indigo-200' : 'bg-red-50 border-red-200'" class="rounded-lg p-4 border">
          <p :class="order.reimbursement_amount >= 0 ? 'text-indigo-600' : 'text-red-600'" class="text-xs uppercase font-medium">{{ t('reimbursement') }}</p>
          <p :class="order.reimbursement_amount >= 0 ? 'text-indigo-800' : 'text-red-800'" class="text-xl font-bold">{{ formatMoney(order.reimbursement_amount) }}</p>
          <p class="text-xs text-gray-500 mt-1">{{ t('advance') }}: {{ formatMoney(order.advance_amount) }}</p>
        </div>
      </div>

      <!-- Segments Table -->
      <div v-if="order.segments && order.segments.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-3 bg-blue-50 border-b border-blue-200">
          <h3 class="text-sm font-semibold text-blue-800">{{ t('segments') }} ({{ order.segments.length }})</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('from_city') }}</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('to_city') }}</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('transport_type') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('distance') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('days') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('per_diem') }}</th>
                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">{{ t('accommodation_provided') }}</th>
                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">{{ t('meals_provided') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="seg in order.segments" :key="seg.id" class="hover:bg-gray-50">
                <td class="px-4 py-3 text-sm text-gray-900">{{ seg.from_city }}</td>
                <td class="px-4 py-3 text-sm text-gray-900">{{ seg.to_city }}</td>
                <td class="px-4 py-3 text-sm text-gray-500">{{ transportLabel(seg.transport_type) }}</td>
                <td class="px-4 py-3 text-sm text-right text-gray-500">{{ seg.distance_km || '-' }} km</td>
                <td class="px-4 py-3 text-sm text-right text-gray-500">{{ seg.per_diem_days || '-' }}</td>
                <td class="px-4 py-3 text-sm text-right font-medium text-blue-700">{{ formatMoney(seg.per_diem_amount) }}</td>
                <td class="px-4 py-3 text-sm text-center">
                  <span v-if="seg.accommodation_provided" class="text-green-600">&#10003;</span>
                  <span v-else class="text-gray-300">-</span>
                </td>
                <td class="px-4 py-3 text-sm text-center">
                  <span v-if="seg.meals_provided" class="text-green-600">&#10003;</span>
                  <span v-else class="text-gray-300">-</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Expenses Table -->
      <div v-if="order.expenses && order.expenses.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-3 bg-purple-50 border-b border-purple-200">
          <h3 class="text-sm font-semibold text-purple-800">{{ t('expenses') }} ({{ order.expenses.length }})</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('category') }}</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('description') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('amount') }}</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('currency') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="exp in order.expenses" :key="exp.id" class="hover:bg-gray-50">
                <td class="px-4 py-3 text-sm text-gray-500">{{ categoryLabel(exp.category) }}</td>
                <td class="px-4 py-3 text-sm text-gray-900">{{ exp.description }}</td>
                <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">{{ formatMoney(exp.amount) }}</td>
                <td class="px-4 py-3 text-sm text-gray-500">{{ exp.currency_code }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Notes -->
      <div v-if="order.notes" class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-2">{{ t('notes') }}</h3>
        <p class="text-sm text-gray-600 whitespace-pre-line">{{ order.notes }}</p>
      </div>
    </div>

    <!-- Not found -->
    <div v-else class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-16">
      <BaseIcon name="ExclamationCircleIcon" class="h-12 w-12 text-gray-400" />
      <p class="mt-2 text-sm text-gray-500">{{ t('not_found') }}</p>
    </div>

    <!-- Approve Dialog -->
    <div v-if="showApproveDialog" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="showApproveDialog = false" />
      <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ t('approve') }}</h3>
        <p class="text-sm text-gray-500 mb-6">{{ t('confirm_approve') }}</p>
        <div class="flex justify-end space-x-3">
          <BaseButton variant="primary-outline" @click="showApproveDialog = false">
            {{ t('back') }}
          </BaseButton>
          <BaseButton variant="primary" :loading="isApproving" @click="approveOrder">
            {{ t('approve') }}
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Settle Dialog -->
    <div v-if="showSettleDialog" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="showSettleDialog = false" />
      <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ t('settle') }}</h3>
        <p class="text-sm text-gray-500 mb-6">{{ t('confirm_settle') }}</p>
        <div class="flex justify-end space-x-3">
          <BaseButton variant="primary-outline" @click="showSettleDialog = false">
            {{ t('back') }}
          </BaseButton>
          <BaseButton variant="primary" :loading="isSettling" @click="settleOrder">
            {{ t('settle') }}
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Reject Dialog -->
    <div v-if="showRejectDialog" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="showRejectDialog = false" />
      <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ t('reject') }}</h3>
        <p class="text-sm text-gray-500 mb-6">{{ t('confirm_reject') }}</p>
        <div class="flex justify-end space-x-3">
          <BaseButton variant="primary-outline" @click="showRejectDialog = false">
            {{ t('back') }}
          </BaseButton>
          <BaseButton variant="danger" :loading="isRejecting" @click="rejectOrder">
            {{ t('reject') }}
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Delete Dialog -->
    <div v-if="showDeleteDialog" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="showDeleteDialog = false" />
      <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ t('delete') }}</h3>
        <p class="text-sm text-gray-500 mb-6">{{ t('confirm_delete') }}</p>
        <div class="flex justify-end space-x-3">
          <BaseButton variant="primary-outline" @click="showDeleteDialog = false">
            {{ t('back') }}
          </BaseButton>
          <BaseButton variant="danger" :loading="isDeleting" @click="deleteOrder">
            {{ t('delete') }}
          </BaseButton>
        </div>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'
import travelMessages from '@/scripts/admin/i18n/travel-orders.js'

const route = useRoute()
const router = useRouter()
const notificationStore = useNotificationStore()
const { locale: i18nLocale } = useI18n()

function t(key) {
  const l = i18nLocale.value || 'mk'
  return travelMessages[l]?.travel_orders?.[key]
    || travelMessages['en']?.travel_orders?.[key]
    || key
}

function transportLabel(type) {
  const labels = { car: t('transport_car'), bus: t('transport_bus'), train: t('transport_train'), plane: t('transport_plane'), other: t('transport_other') }
  return labels[type] || type
}

function categoryLabel(cat) {
  const labels = { transport: t('category_transport'), accommodation: t('category_accommodation'), meals: t('category_meals'), other: t('category_other') }
  return labels[cat] || cat
}

// State
const order = ref(null)
const isLoading = ref(false)
const isApproving = ref(false)
const isSettling = ref(false)
const isRejecting = ref(false)
const isDeleting = ref(false)
const isDownloading = ref(false)
const showApproveDialog = ref(false)
const showSettleDialog = ref(false)
const showRejectDialog = ref(false)
const showDeleteDialog = ref(false)

// Methods
const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }

function formatMoney(cents) {
  if (!cents && cents !== 0) return '-'
  const fmtLocale = localeMap[i18nLocale.value] || 'mk-MK'
  return new Intl.NumberFormat(fmtLocale, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(cents / 100)
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  const fmtLocale = localeMap[i18nLocale.value] || 'mk-MK'
  return d.toLocaleDateString(fmtLocale, { year: 'numeric', month: '2-digit', day: '2-digit' })
}

function statusBadgeClass(status) {
  switch (status) {
    case 'draft': return 'bg-gray-100 text-gray-700'
    case 'pending_approval': return 'bg-yellow-100 text-yellow-800'
    case 'approved': return 'bg-green-100 text-green-800'
    case 'settled': return 'bg-blue-100 text-blue-800'
    case 'rejected': return 'bg-red-100 text-red-800'
    default: return 'bg-gray-100 text-gray-700'
  }
}

function statusLabel(status) {
  const key = `status_${status}`
  return t(key)
}

async function fetchOrder() {
  const id = route.params.id
  if (!id) return

  isLoading.value = true
  try {
    const response = await window.axios.get(`/travel-orders/${id}`)
    order.value = response.data?.data || null
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_loading'),
    })
  } finally {
    isLoading.value = false
  }
}

async function downloadPdf() {
  isDownloading.value = true
  try {
    const response = await window.axios.get(`/travel-orders/${order.value.id}/pdf`, {
      responseType: 'blob',
    })
    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `paten-nalog-${order.value.travel_number}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_downloading'),
    })
  } finally {
    isDownloading.value = false
  }
}

async function approveOrder() {
  isApproving.value = true
  try {
    const response = await window.axios.post(`/travel-orders/${order.value.id}/approve`)
    order.value = response.data?.data || order.value
    showApproveDialog.value = false
    notificationStore.showNotification({
      type: 'success',
      message: response.data?.message || t('approved_success'),
    })
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_approving'),
    })
  } finally {
    isApproving.value = false
  }
}

async function settleOrder() {
  isSettling.value = true
  try {
    const response = await window.axios.post(`/travel-orders/${order.value.id}/settle`)
    order.value = response.data?.data || order.value
    showSettleDialog.value = false
    notificationStore.showNotification({
      type: 'success',
      message: response.data?.message || t('settled_success'),
    })
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_settling'),
    })
  } finally {
    isSettling.value = false
  }
}

async function rejectOrder() {
  isRejecting.value = true
  try {
    const response = await window.axios.post(`/travel-orders/${order.value.id}/reject`)
    order.value = response.data?.data || order.value
    showRejectDialog.value = false
    notificationStore.showNotification({
      type: 'success',
      message: response.data?.message || t('rejected_success'),
    })
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_rejecting'),
    })
  } finally {
    isRejecting.value = false
  }
}

async function deleteOrder() {
  isDeleting.value = true
  try {
    await window.axios.delete(`/travel-orders/${order.value.id}`)
    showDeleteDialog.value = false
    notificationStore.showNotification({
      type: 'success',
      message: t('deleted_success'),
    })
    router.push({ path: '/admin/travel-orders' })
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_deleting'),
    })
  } finally {
    isDeleting.value = false
  }
}

// Lifecycle
onMounted(() => {
  fetchOrder()
})
</script>

<!-- CLAUDE-CHECKPOINT -->
