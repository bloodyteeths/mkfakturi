<template>
  <BasePage>
    <BasePageHeader :title="document ? document.document_number : 'Документ'">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('stock.title')" to="/admin/stock" />
        <BaseBreadcrumbItem title="Документи" to="/admin/stock/documents" />
        <BaseBreadcrumbItem :title="document ? document.document_number : '...'" to="#" active />
      </BaseBreadcrumb>

      <template v-if="document" #actions>
        <div class="flex items-center space-x-3">
          <!-- Draft actions -->
          <template v-if="document.status === 'draft'">
            <router-link :to="{ name: 'stock.documents.edit', params: { id: document.id } }">
              <BaseButton variant="primary-outline">
                <template #left="slotProps">
                  <BaseIcon name="PencilIcon" :class="slotProps.class" />
                </template>
                Уреди
              </BaseButton>
            </router-link>
            <BaseButton
              variant="primary"
              :loading="isApproving"
              @click="approveDocument"
            >
              <template #left="slotProps">
                <BaseIcon name="CheckIcon" :class="slotProps.class" />
              </template>
              Одобри
            </BaseButton>
            <BaseButton
              variant="danger-outline"
              :loading="isDeleting"
              @click="deleteDocument"
            >
              <template #left="slotProps">
                <BaseIcon name="TrashIcon" :class="slotProps.class" />
              </template>
              Избриши
            </BaseButton>
          </template>

          <!-- Approved actions -->
          <template v-if="document.status === 'approved'">
            <BaseButton
              variant="primary-outline"
              @click="downloadPdf"
            >
              <template #left="slotProps">
                <BaseIcon name="DocumentArrowDownIcon" :class="slotProps.class" />
              </template>
              PDF
            </BaseButton>
            <BaseButton
              variant="danger"
              :loading="isVoiding"
              @click="voidDocument"
            >
              <template #left="slotProps">
                <BaseIcon name="XMarkIcon" :class="slotProps.class" />
              </template>
              Поништи
            </BaseButton>
          </template>
        </div>
      </template>
    </BasePageHeader>

    <!-- Stock Sub-Navigation Tabs -->
    <StockTabNavigation />

    <div v-if="isLoading" class="flex justify-center py-12">
      <BaseContentPlaceholders>
        <BaseContentPlaceholdersBox class="w-full h-96" />
      </BaseContentPlaceholders>
    </div>

    <div v-else-if="!document" class="text-center py-12">
      <BaseIcon name="ExclamationTriangleIcon" class="h-12 w-12 text-gray-400 mx-auto mb-4" />
      <h3 class="text-lg font-medium text-gray-900">Документот не е пронајден</h3>
    </div>

    <div v-else>
      <!-- Document Header -->
      <BaseCard class="mb-6">
        <div class="flex items-start justify-between mb-4">
          <div>
            <h2 class="text-2xl font-bold text-gray-900">{{ document.document_number }}</h2>
            <div class="flex items-center mt-2 space-x-3">
              <span
                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                :class="typeBadgeClass(document.document_type)"
              >
                {{ document.document_type_label }}
              </span>
              <span
                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                :class="statusBadgeClass(document.status)"
              >
                {{ document.status_label }}
              </span>
            </div>
          </div>
        </div>

        <!-- Info Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
          <div>
            <dt class="text-sm font-medium text-gray-500">Магацин</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ document.warehouse_name }}</dd>
          </div>
          <div v-if="document.destination_warehouse_name">
            <dt class="text-sm font-medium text-gray-500">Одредишен магацин</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ document.destination_warehouse_name }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Датум</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ document.document_date }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Креирано од</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ document.created_by_name || '-' }}</dd>
          </div>
          <div v-if="document.approved_by_name">
            <dt class="text-sm font-medium text-gray-500">Одобрено од</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ document.approved_by_name }}</dd>
          </div>
          <div v-if="document.approved_at">
            <dt class="text-sm font-medium text-gray-500">Одобрено на</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ document.approved_at }}</dd>
          </div>
        </div>

        <!-- Notes -->
        <div v-if="document.notes" class="mt-6 border-t pt-4">
          <dt class="text-sm font-medium text-gray-500">Белешки</dt>
          <dd class="mt-1 text-sm text-gray-900 whitespace-pre-line">{{ document.notes }}</dd>
        </div>
      </BaseCard>

      <!-- Items Table -->
      <BaseCard>
        <template #header>
          <h3 class="text-lg font-medium text-gray-900">
            Ставки ({{ document.items ? document.items.length : 0 }})
          </h3>
        </template>

        <div v-if="!document.items || document.items.length === 0" class="text-center py-8">
          <p class="text-gray-500">Нема ставки во овој документ.</p>
        </div>

        <table v-else class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                #
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Артикл
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Шифра
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                Количина
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                Единечна цена
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                Вкупно
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="(item, index) in document.items" :key="item.id">
              <td class="px-4 py-3 text-sm text-gray-500">
                {{ index + 1 }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-900 font-medium">
                {{ item.item_name }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-500">
                {{ item.item_sku || '-' }}
              </td>
              <td class="px-4 py-3 text-sm text-right text-gray-900">
                {{ formatQuantity(item.quantity) }}
              </td>
              <td class="px-4 py-3 text-sm text-right text-gray-900">
                <BaseFormatMoney v-if="item.unit_cost" :amount="item.unit_cost" />
                <span v-else>-</span>
              </td>
              <td class="px-4 py-3 text-sm text-right text-gray-900 font-medium">
                <BaseFormatMoney v-if="item.total_cost" :amount="item.total_cost" />
                <span v-else>-</span>
              </td>
            </tr>
          </tbody>
          <tfoot class="bg-gray-50">
            <tr>
              <td colspan="5" class="px-4 py-3 text-sm font-medium text-gray-900 text-right">
                Вкупна вредност:
              </td>
              <td class="px-4 py-3 text-sm font-bold text-gray-900 text-right">
                <BaseFormatMoney v-if="document.total_value" :amount="document.total_value" />
                <span v-else>-</span>
              </td>
            </tr>
          </tfoot>
        </table>
      </BaseCard>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useNotificationStore } from '@/scripts/stores/notification'
import StockTabNavigation from '@/scripts/admin/components/StockTabNavigation.vue'
import axios from 'axios'

const route = useRoute()
const router = useRouter()
const dialogStore = useDialogStore()
const notificationStore = useNotificationStore()

const document = ref(null)
const isLoading = ref(false)
const isApproving = ref(false)
const isVoiding = ref(false)
const isDeleting = ref(false)

/**
 * Get CSS class for document type badge.
 */
function typeBadgeClass(type) {
  const classes = {
    receipt: 'bg-green-100 text-green-800',
    issue: 'bg-red-100 text-red-800',
    transfer: 'bg-blue-100 text-blue-800',
    return: 'bg-cyan-100 text-cyan-800',
    write_off: 'bg-amber-100 text-amber-800',
  }
  return classes[type] || 'bg-gray-100 text-gray-800'
}

/**
 * Get CSS class for status badge.
 */
function statusBadgeClass(status) {
  const classes = {
    draft: 'bg-gray-100 text-gray-800',
    approved: 'bg-green-100 text-green-800',
    voided: 'bg-red-100 text-red-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

/**
 * Format quantity, removing unnecessary trailing zeros.
 */
function formatQuantity(qty) {
  if (qty === null || qty === undefined) return '-'
  const num = Number(qty)
  return Number.isInteger(num) ? num.toString() : num.toFixed(4).replace(/\.?0+$/, '')
}

/**
 * Download the document as PDF in a new tab.
 */
function downloadPdf() {
  if (!document.value) return
  window.open(`/api/v1/stock/documents/${document.value.id}/pdf`, '_blank')
}

/**
 * Load document from API.
 */
async function loadDocument() {
  isLoading.value = true
  try {
    const response = await axios.get(`/stock/documents/${route.params.id}`)
    document.value = response.data.data
  } catch (error) {
    console.error('Failed to load document:', error)
    notificationStore.showNotification({
      type: 'error',
      message: 'Грешка при вчитување на документот.',
    })
  } finally {
    isLoading.value = false
  }
}

/**
 * Approve the document after confirmation.
 */
async function approveDocument() {
  const confirmed = await dialogStore.openDialog({
    title: 'Одобрување на документ',
    message: `Дали сте сигурни дека сакате да го одобрите документот ${document.value.document_number}? Ова ќе создаде магацински движења.`,
    yesLabel: 'Одобри',
    noLabel: 'Откажи',
    variant: 'primary',
  })

  if (!confirmed) return

  isApproving.value = true
  try {
    await axios.post(`/stock/documents/${document.value.id}/approve`)
    notificationStore.showNotification({
      type: 'success',
      message: 'Документот е успешно одобрен.',
    })
    await loadDocument()
  } catch (error) {
    console.error('Failed to approve document:', error)
    const errorMsg = error.response?.data?.message || 'Грешка при одобрување.'
    notificationStore.showNotification({
      type: 'error',
      message: errorMsg,
    })
  } finally {
    isApproving.value = false
  }
}

/**
 * Void the document after confirmation.
 */
async function voidDocument() {
  const confirmed = await dialogStore.openDialog({
    title: 'Поништување на документ',
    message: `Дали сте сигурни дека сакате да го поништите документот ${document.value.document_number}? Ова ќе ги поврати сите магацински движења.`,
    yesLabel: 'Поништи',
    noLabel: 'Откажи',
    variant: 'danger',
  })

  if (!confirmed) return

  isVoiding.value = true
  try {
    await axios.post(`/stock/documents/${document.value.id}/void`)
    notificationStore.showNotification({
      type: 'success',
      message: 'Документот е успешно поништен.',
    })
    await loadDocument()
  } catch (error) {
    console.error('Failed to void document:', error)
    const errorMsg = error.response?.data?.message || 'Грешка при поништување.'
    notificationStore.showNotification({
      type: 'error',
      message: errorMsg,
    })
  } finally {
    isVoiding.value = false
  }
}

/**
 * Delete the document after confirmation.
 */
async function deleteDocument() {
  const confirmed = await dialogStore.openDialog({
    title: 'Бришење на документ',
    message: `Дали сте сигурни дека сакате да го избришете документот ${document.value.document_number}?`,
    yesLabel: 'Избриши',
    noLabel: 'Откажи',
    variant: 'danger',
  })

  if (!confirmed) return

  isDeleting.value = true
  try {
    await axios.delete(`/stock/documents/${document.value.id}`)
    notificationStore.showNotification({
      type: 'success',
      message: 'Документот е успешно избришан.',
    })
    router.push({ name: 'stock.documents' })
  } catch (error) {
    console.error('Failed to delete document:', error)
    const errorMsg = error.response?.data?.message || 'Грешка при бришење.'
    notificationStore.showNotification({
      type: 'error',
      message: errorMsg,
    })
  } finally {
    isDeleting.value = false
  }
}

onMounted(() => {
  loadDocument()
})
// CLAUDE-CHECKPOINT
</script>
