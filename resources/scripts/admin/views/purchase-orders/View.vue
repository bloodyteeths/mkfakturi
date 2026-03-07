<template>
  <BasePage>
    <BasePageHeader :title="po ? po.po_number : t('title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="t('title')" to="../purchase-orders" />
        <BaseBreadcrumbItem
          :title="po ? po.po_number : '...'"
          to="#"
          active
        />
      </BaseBreadcrumb>

      <template #actions>
        <div v-if="po" class="flex items-center space-x-2">
          <!-- Edit (draft only) -->
          <router-link
            v-if="po.status === 'draft'"
            :to="`/admin/purchase-orders/${po.id}/edit`"
          >
            <BaseButton variant="primary-outline">
              <template #left="slotProps">
                <BaseIcon name="PencilSquareIcon" :class="slotProps.class" />
              </template>
              {{ t('edit_draft') }}
            </BaseButton>
          </router-link>

          <!-- Send (draft only) -->
          <BaseButton
            v-if="po.status === 'draft'"
            variant="primary"
            @click="showSendDialog = true"
          >
            <template #left="slotProps">
              <BaseIcon name="PaperAirplaneIcon" :class="slotProps.class" />
            </template>
            {{ t('send_to_supplier') }}
          </BaseButton>

          <!-- Receive Goods (sent/acknowledged/partially_received) -->
          <BaseButton
            v-if="['sent', 'acknowledged', 'partially_received'].includes(po.status)"
            variant="primary"
            @click="showReceiveModal = true"
          >
            <template #left="slotProps">
              <BaseIcon name="TruckIcon" :class="slotProps.class" />
            </template>
            {{ t('receive_goods') }}
          </BaseButton>

          <!-- Convert to Bill (fully_received/partially_received, not yet billed) -->
          <BaseButton
            v-if="['fully_received', 'partially_received'].includes(po.status) && !po.converted_bill_id"
            variant="primary-outline"
            @click="showConvertDialog = true"
          >
            <template #left="slotProps">
              <BaseIcon name="DocumentDuplicateIcon" :class="slotProps.class" />
            </template>
            {{ t('convert_to_bill') }}
          </BaseButton>

          <!-- 3-Way Match -->
          <BaseButton
            v-if="po.converted_bill_id"
            variant="primary-outline"
            :loading="isMatching"
            @click="runThreeWayMatch"
          >
            <template #left="slotProps">
              <BaseIcon name="CheckBadgeIcon" :class="slotProps.class" />
            </template>
            {{ t('three_way_match') }}
          </BaseButton>

          <!-- Cancel (draft/sent only) -->
          <BaseButton
            v-if="['draft', 'sent'].includes(po.status)"
            variant="danger"
            @click="showCancelDialog = true"
          >
            <template #left="slotProps">
              <BaseIcon name="XMarkIcon" :class="slotProps.class" />
            </template>
            {{ t('cancel_po') }}
          </BaseButton>

          <!-- Delete (draft only) -->
          <BaseButton
            v-if="po.status === 'draft'"
            variant="danger"
            @click="showDeleteDialog = true"
          >
            <template #left="slotProps">
              <BaseIcon name="TrashIcon" :class="slotProps.class" />
            </template>
            {{ t('delete_po') }}
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
    <div v-else-if="po" class="space-y-6">
      <!-- Header Card -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
          <div>
            <h3 class="text-lg font-medium text-gray-900">{{ po.po_number }}</h3>
            <p class="text-sm text-gray-500">{{ formatDate(po.po_date) }}</p>
          </div>
          <span
            :class="statusBadgeClass(po.status)"
            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
          >
            {{ statusLabel(po.status) }}
          </span>
        </div>

        <div class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div>
              <p class="text-xs text-gray-500 uppercase font-medium">{{ t('supplier') }}</p>
              <p class="text-sm font-medium text-gray-900 mt-1">
                {{ po.supplier?.name || '-' }}
              </p>
              <p v-if="po.supplier?.email" class="text-xs text-gray-500 mt-0.5">
                {{ po.supplier.email }}
              </p>
            </div>
            <div>
              <p class="text-xs text-gray-500 uppercase font-medium">{{ t('expected_delivery') }}</p>
              <p class="text-sm font-medium text-gray-900 mt-1">
                {{ po.expected_delivery_date ? formatDate(po.expected_delivery_date) : '-' }}
              </p>
            </div>
            <div>
              <p class="text-xs text-gray-500 uppercase font-medium">{{ t('warehouse') }}</p>
              <p class="text-sm font-medium text-gray-900 mt-1">
                {{ po.warehouse?.name || '-' }}
              </p>
            </div>
            <div>
              <p class="text-xs text-gray-500 uppercase font-medium">{{ t('created_by') }}</p>
              <p class="text-sm font-medium text-gray-900 mt-1">
                {{ po.created_by_user?.name || po.created_by?.name || '-' }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Total Summary -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
          <p class="text-xs text-blue-600 uppercase font-medium">{{ t('sub_total') }}</p>
          <p class="text-2xl font-bold text-blue-800">{{ formatMoney(po.sub_total) }}</p>
        </div>
        <div class="bg-amber-50 rounded-lg p-4 border border-amber-200">
          <p class="text-xs text-amber-600 uppercase font-medium">{{ t('tax_amount') }}</p>
          <p class="text-2xl font-bold text-amber-800">{{ formatMoney(po.tax) }}</p>
        </div>
        <div class="bg-green-50 rounded-lg p-4 border border-green-200">
          <p class="text-xs text-green-600 uppercase font-medium">{{ t('total') }}</p>
          <p class="text-2xl font-bold text-green-800">{{ formatMoney(po.total) }}</p>
        </div>
      </div>

      <!-- Items Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-3 bg-gray-50 border-b border-gray-200">
          <h3 class="text-sm font-semibold text-gray-700">{{ t('items') }}</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('item_name') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('quantity_ordered') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('quantity_received') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('quantity_remaining') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('price') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('item_tax') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('item_total') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="item in po.items" :key="item.id" class="hover:bg-gray-50">
                <td class="px-4 py-3 text-sm text-gray-900">
                  {{ item.name }}
                  <span v-if="item.item" class="text-xs text-gray-500 ml-1">({{ item.item.sku || '' }})</span>
                </td>
                <td class="px-4 py-3 text-sm text-right text-gray-900">{{ item.quantity }}</td>
                <td class="px-4 py-3 text-sm text-right">
                  <span :class="item.received_quantity >= item.quantity ? 'text-green-600 font-medium' : 'text-amber-600'">
                    {{ item.received_quantity }}
                  </span>
                </td>
                <td class="px-4 py-3 text-sm text-right">
                  <span :class="item.remaining_quantity > 0 ? 'text-red-600' : 'text-green-600'">
                    {{ item.remaining_quantity }}
                  </span>
                </td>
                <td class="px-4 py-3 text-sm text-right text-gray-500">{{ formatMoney(item.price) }}</td>
                <td class="px-4 py-3 text-sm text-right text-gray-500">{{ formatMoney(item.tax) }}</td>
                <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">{{ formatMoney(item.total) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Goods Receipts -->
      <div v-if="po.goods_receipts && po.goods_receipts.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-3 bg-gray-50 border-b border-gray-200">
          <h3 class="text-sm font-semibold text-gray-700">{{ t('goods_receipts') }} ({{ po.goods_receipts.length }})</h3>
        </div>
        <div class="divide-y divide-gray-100">
          <div
            v-for="receipt in po.goods_receipts"
            :key="receipt.id"
            class="px-6 py-3"
          >
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-gray-900">{{ receipt.receipt_number }}</p>
                <p class="text-xs text-gray-500">{{ formatDate(receipt.receipt_date) }}</p>
              </div>
              <div class="text-right">
                <p class="text-xs text-gray-500">
                  {{ receipt.items?.length || 0 }} {{ t('items').toLowerCase() }}
                </p>
              </div>
            </div>
            <!-- Receipt items detail -->
            <div v-if="receipt.items && receipt.items.length > 0" class="mt-2 pl-4 border-l-2 border-gray-200">
              <div
                v-for="ri in receipt.items"
                :key="ri.id"
                class="text-xs text-gray-500 py-0.5"
              >
                {{ t('quantity_received') }}: {{ ri.quantity_received }}
                <span v-if="ri.quantity_accepted !== null"> | {{ t('quantity_accepted') }}: {{ ri.quantity_accepted }}</span>
                <span v-if="ri.quantity_rejected > 0" class="text-red-500"> | {{ t('quantity_rejected') }}: {{ ri.quantity_rejected }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Linked Bill -->
      <div v-if="po.converted_bill" class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-2">{{ t('convert_to_bill') }}</h3>
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-primary-500">{{ po.converted_bill.bill_number }}</p>
            <p class="text-xs text-gray-500">{{ formatMoney(po.converted_bill.total) }}</p>
          </div>
          <span class="text-xs text-gray-500">{{ po.converted_bill.status }}</span>
        </div>
      </div>

      <!-- Three-Way Match Results -->
      <div v-if="matchResult" class="bg-white rounded-lg shadow overflow-hidden">
        <div
          :class="[
            'px-6 py-3 border-b flex items-center justify-between',
            matchResult.matched ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'
          ]"
        >
          <div class="flex items-center">
            <BaseIcon
              :name="matchResult.matched ? 'CheckCircleIcon' : 'ExclamationTriangleIcon'"
              :class="matchResult.matched ? 'text-green-600' : 'text-red-600'"
              class="h-5 w-5 mr-2"
            />
            <h3 :class="matchResult.matched ? 'text-green-800' : 'text-red-800'" class="text-sm font-semibold">
              {{ t('match_result') }}: {{ matchResult.matched ? t('all_matched') : t('has_discrepancies') }}
            </h3>
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('item_name') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('po_qty') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('received_qty') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('billed_qty') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('price') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('billed_price') }}</th>
                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">{{ t('status') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="(d, idx) in matchResult.discrepancies" :key="idx">
                <td class="px-4 py-2 text-gray-900">{{ d.item_name }}</td>
                <td class="px-4 py-2 text-right">{{ d.po_quantity }}</td>
                <td class="px-4 py-2 text-right">{{ d.received_quantity }}</td>
                <td class="px-4 py-2 text-right">{{ d.billed_quantity }}</td>
                <td class="px-4 py-2 text-right">{{ formatMoney(d.po_price) }}</td>
                <td class="px-4 py-2 text-right">{{ formatMoney(d.billed_price) }}</td>
                <td class="px-4 py-2 text-center">
                  <span
                    v-if="d.quantity_match && d.price_match"
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                  >
                    {{ t('matched') }}
                  </span>
                  <span
                    v-else
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"
                  >
                    {{ t('discrepancy') }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Notes -->
      <div v-if="po.notes" class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-2">{{ t('notes') }}</h3>
        <p class="text-sm text-gray-600 whitespace-pre-line">{{ po.notes }}</p>
      </div>
    </div>

    <!-- Not Found -->
    <div v-else class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-16">
      <BaseIcon name="ExclamationCircleIcon" class="h-12 w-12 text-gray-400" />
      <p class="mt-2 text-sm text-gray-500">{{ t('not_found') }}</p>
    </div>

    <!-- Receive Goods Modal -->
    <ReceiveGoods
      v-if="showReceiveModal && po"
      :po="po"
      @close="showReceiveModal = false"
      @received="onGoodsReceived"
    />

    <!-- Send Dialog -->
    <div v-if="showSendDialog" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="showSendDialog = false" />
      <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ t('send_title') }}</h3>

        <!-- Supplier email info -->
        <div v-if="supplierEmail" class="mb-4">
          <p class="text-sm text-gray-500 mb-2">{{ t('send_email_message') }}</p>
          <div class="flex items-center bg-blue-50 border border-blue-200 rounded-lg px-4 py-3">
            <BaseIcon name="EnvelopeIcon" class="h-5 w-5 text-blue-500 mr-2 flex-shrink-0" />
            <div>
              <p class="text-xs text-blue-600 font-medium">{{ t('supplier_email') }}</p>
              <p class="text-sm font-medium text-blue-800">{{ supplierEmail }}</p>
            </div>
          </div>
        </div>
        <div v-else class="mb-4">
          <div class="flex items-start bg-amber-50 border border-amber-200 rounded-lg px-4 py-3">
            <BaseIcon name="ExclamationTriangleIcon" class="h-5 w-5 text-amber-500 mr-2 flex-shrink-0 mt-0.5" />
            <p class="text-sm text-amber-700">{{ t('no_supplier_email') }}</p>
          </div>
        </div>

        <div class="flex justify-end space-x-3">
          <BaseButton variant="primary-outline" @click="showSendDialog = false">
            {{ t('back') }}
          </BaseButton>
          <BaseButton variant="primary" :loading="isSending" @click="sendPo">
            <template #left="slotProps">
              <BaseIcon name="PaperAirplaneIcon" :class="slotProps.class" />
            </template>
            {{ t('send_to_supplier') }}
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Cancel Dialog -->
    <div v-if="showCancelDialog" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="showCancelDialog = false" />
      <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ t('cancel_title') }}</h3>
        <p class="text-sm text-gray-500 mb-6">{{ t('cancel_message') }}</p>
        <div class="flex justify-end space-x-3">
          <BaseButton variant="primary-outline" @click="showCancelDialog = false">
            {{ t('back') }}
          </BaseButton>
          <BaseButton variant="danger" :loading="isCancelling" @click="cancelPo">
            {{ t('cancel_po') }}
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Delete Dialog -->
    <div v-if="showDeleteDialog" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="showDeleteDialog = false" />
      <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ t('delete_title') }}</h3>
        <p class="text-sm text-gray-500 mb-6">{{ t('delete_message') }}</p>
        <div class="flex justify-end space-x-3">
          <BaseButton variant="primary-outline" @click="showDeleteDialog = false">
            {{ t('back') }}
          </BaseButton>
          <BaseButton variant="danger" :loading="isDeleting" @click="deletePo">
            {{ t('delete_po') }}
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Convert to Bill Dialog -->
    <div v-if="showConvertDialog" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="showConvertDialog = false" />
      <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ t('convert_title') }}</h3>
        <p class="text-sm text-gray-500 mb-6">{{ t('convert_message') }}</p>
        <div class="flex justify-end space-x-3">
          <BaseButton variant="primary-outline" @click="showConvertDialog = false">
            {{ t('back') }}
          </BaseButton>
          <BaseButton variant="primary" :loading="isConverting" @click="convertToBill">
            {{ t('convert_to_bill') }}
          </BaseButton>
        </div>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useNotificationStore } from '@/scripts/stores/notification'
import poMessages from '@/scripts/admin/i18n/purchase-orders.js'
import ReceiveGoods from './ReceiveGoods.vue'

const route = useRoute()
const router = useRouter()
const notificationStore = useNotificationStore()

const locale = document.documentElement.lang || 'mk'
function t(key) {
  return poMessages[locale]?.purchaseOrders?.[key]
    || poMessages['en']?.purchaseOrders?.[key]
    || key
}

// Computed
const supplierEmail = computed(() => po.value?.supplier?.email || null)

// State
const po = ref(null)
const matchResult = ref(null)
const isLoading = ref(false)
const isSending = ref(false)
const isCancelling = ref(false)
const isDeleting = ref(false)
const isConverting = ref(false)
const isMatching = ref(false)
const showReceiveModal = ref(false)
const showSendDialog = ref(false)
const showCancelDialog = ref(false)
const showDeleteDialog = ref(false)
const showConvertDialog = ref(false)

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

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString(fmtLocale, { year: 'numeric', month: '2-digit', day: '2-digit' })
}

function statusBadgeClass(status) {
  switch (status) {
    case 'draft': return 'bg-gray-100 text-gray-700'
    case 'sent': return 'bg-blue-100 text-blue-800'
    case 'acknowledged': return 'bg-indigo-100 text-indigo-800'
    case 'partially_received': return 'bg-yellow-100 text-yellow-800'
    case 'fully_received': return 'bg-green-100 text-green-800'
    case 'billed': return 'bg-purple-100 text-purple-800'
    case 'closed': return 'bg-gray-200 text-gray-900'
    case 'cancelled': return 'bg-red-100 text-red-800'
    default: return 'bg-gray-100 text-gray-700'
  }
}

function statusLabel(status) {
  const key = 'status_' + status
  return t(key)
}

async function fetchPo() {
  const id = route.params.id
  if (!id) return

  isLoading.value = true
  try {
    const response = await window.axios.get(`/purchase-orders/${id}`)
    po.value = response.data?.data || null
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_loading') || 'Failed to load purchase order',
    })
  } finally {
    isLoading.value = false
  }
}

async function sendPo() {
  isSending.value = true
  try {
    const response = await window.axios.post(`/purchase-orders/${po.value.id}/send`)
    po.value = response.data?.data || po.value
    showSendDialog.value = false

    const emailTo = response.data?.email_sent_to
    const msg = emailTo
      ? `${t('sent_success')} — ${t('email_sent_to')}: ${emailTo}`
      : (response.data?.message || t('sent_success'))

    notificationStore.showNotification({
      type: 'success',
      message: msg,
    })
    fetchPo()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_sending'),
    })
  } finally {
    isSending.value = false
  }
}

async function cancelPo() {
  isCancelling.value = true
  try {
    const response = await window.axios.post(`/purchase-orders/${po.value.id}/cancel`)
    po.value = response.data?.data || po.value
    showCancelDialog.value = false
    notificationStore.showNotification({
      type: 'success',
      message: response.data?.message || t('cancelled_success') || 'Purchase order cancelled',
    })
    fetchPo()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_cancelling') || 'Failed to cancel',
    })
  } finally {
    isCancelling.value = false
  }
}

async function deletePo() {
  isDeleting.value = true
  try {
    await window.axios.delete(`/purchase-orders/${po.value.id}`)
    showDeleteDialog.value = false
    notificationStore.showNotification({
      type: 'success',
      message: t('deleted_success') || 'Purchase order deleted',
    })
    router.push({ path: '/admin/purchase-orders' })
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_deleting') || 'Failed to delete',
    })
  } finally {
    isDeleting.value = false
  }
}

async function convertToBill() {
  isConverting.value = true
  try {
    const response = await window.axios.post(`/purchase-orders/${po.value.id}/convert-to-bill`)
    showConvertDialog.value = false
    notificationStore.showNotification({
      type: 'success',
      message: response.data?.message || t('converted_success') || 'Bill created from purchase order',
    })
    fetchPo()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_converting') || 'Failed to convert',
    })
  } finally {
    isConverting.value = false
  }
}

async function runThreeWayMatch() {
  isMatching.value = true
  matchResult.value = null
  try {
    const response = await window.axios.get(`/purchase-orders/${po.value.id}/three-way-match`)
    matchResult.value = response.data?.data || null
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_matching') || 'Failed to run match',
    })
  } finally {
    isMatching.value = false
  }
}

function onGoodsReceived() {
  showReceiveModal.value = false
  fetchPo()
}

// Lifecycle
onMounted(() => {
  fetchPo()
})
</script>

<!-- CLAUDE-CHECKPOINT -->
