<template>
  <BasePage>
    <BasePageHeader :title="nivelacija ? nivelacija.document_number : $t('trade.nivelacija')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('stock.title')" to="/admin/stock" />
        <BaseBreadcrumbItem :title="$t('trade.nivelacii_title')" to="/admin/stock/trade/nivelacii" />
        <BaseBreadcrumbItem :title="nivelacija ? nivelacija.document_number : '...'" to="#" active />
      </BaseBreadcrumb>

      <template v-if="nivelacija" #actions>
        <div class="flex items-center space-x-3">
          <!-- Draft actions -->
          <template v-if="nivelacija.status === 'draft'">
            <router-link :to="{ name: 'stock.trade.nivelacija.edit', params: { id: nivelacija.id } }">
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
              @click="approveNivelacija"
            >
              <template #left="slotProps">
                <BaseIcon name="CheckIcon" :class="slotProps.class" />
              </template>
              {{ $t('trade.approve_nivelacija') }}
            </BaseButton>
          </template>

          <!-- Approved actions -->
          <template v-if="nivelacija.status === 'approved'">
            <BaseButton
              variant="danger"
              :loading="isVoiding"
              @click="voidNivelacija"
            >
              <template #left="slotProps">
                <BaseIcon name="XMarkIcon" :class="slotProps.class" />
              </template>
              {{ $t('trade.void_nivelacija') }}
            </BaseButton>
          </template>

          <!-- PDF Export (always visible) -->
          <BaseButton
            variant="primary-outline"
            :loading="isExporting"
            @click="exportPdf"
          >
            <template #left="slotProps">
              <BaseIcon name="ArrowDownTrayIcon" :class="slotProps.class" />
            </template>
            PDF
          </BaseButton>
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

    <div v-else-if="!nivelacija" class="text-center py-12">
      <BaseIcon name="ExclamationTriangleIcon" class="h-12 w-12 text-gray-400 mx-auto mb-4" />
      <h3 class="text-lg font-medium text-gray-900">Нивелацијата не е пронајдена</h3>
    </div>

    <div v-else>
      <!-- Header Card -->
      <BaseCard class="mb-6">
        <div class="flex items-start justify-between mb-4">
          <div>
            <h2 class="text-2xl font-bold text-gray-900">{{ nivelacija.document_number }}</h2>
            <div class="flex items-center mt-2 space-x-3">
              <span
                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                :class="typeBadgeClass(nivelacija.type)"
              >
                {{ typeLabel(nivelacija.type) }}
              </span>
              <span
                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                :class="statusBadgeClass(nivelacija.status)"
              >
                {{ statusLabel(nivelacija.status) }}
              </span>
            </div>
          </div>
          <div class="text-right">
            <p class="text-sm text-gray-500">{{ $t('trade.total_difference') }}</p>
            <p class="text-2xl font-bold font-mono"
              :class="nivelacija.total_difference > 0 ? 'text-green-700' : nivelacija.total_difference < 0 ? 'text-red-700' : 'text-gray-900'"
            >
              {{ formatMoney(nivelacija.total_difference) }} МКД
            </p>
          </div>
        </div>

        <!-- Info Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-6">
          <div>
            <dt class="text-sm font-medium text-gray-500">{{ $t('trade.doc_date') }}</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ formatDate(nivelacija.document_date) }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">{{ $t('trade.warehouse') }}</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ nivelacija.warehouse?.name || $t('trade.all_warehouses') }}</dd>
          </div>
          <div v-if="nivelacija.source_bill">
            <dt class="text-sm font-medium text-gray-500">{{ $t('trade.source_bill') }}</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ nivelacija.source_bill.bill_number }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Креирано од</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ nivelacija.creator?.name || '-' }}</dd>
          </div>
          <div v-if="nivelacija.approver">
            <dt class="text-sm font-medium text-gray-500">Одобрено од</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ nivelacija.approver.name }}</dd>
          </div>
          <div v-if="nivelacija.approved_at">
            <dt class="text-sm font-medium text-gray-500">Одобрено на</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ formatDate(nivelacija.approved_at) }}</dd>
          </div>
        </div>

        <!-- Reason -->
        <div class="mt-6 border-t pt-4">
          <dt class="text-sm font-medium text-gray-500">{{ $t('trade.reason') }}</dt>
          <dd class="mt-1 text-sm text-gray-900">{{ nivelacija.reason }}</dd>
        </div>
      </BaseCard>

      <!-- Items Table -->
      <BaseCard>
        <template #header>
          <h3 class="text-lg font-medium text-gray-900">
            Ставки ({{ nivelacija.items ? nivelacija.items.length : 0 }})
          </h3>
        </template>

        <div v-if="!nivelacija.items || nivelacija.items.length === 0" class="text-center py-8">
          <p class="text-gray-500">Нема ставки.</p>
        </div>

        <table v-else class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Артикл</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Единица</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('trade.qty_on_hand') }}</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('trade.old_price') }}</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('trade.new_price') }}</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('trade.price_difference') }}</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Вкупна разлика</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Белешка</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="(item, index) in nivelacija.items" :key="item.id">
              <td class="px-4 py-3 text-sm text-gray-500">{{ index + 1 }}</td>
              <td class="px-4 py-3 text-sm text-gray-900 font-medium">
                {{ item.item?.name || `Артикл #${item.item_id}` }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-500">
                {{ item.item?.unit?.name || '-' }}
              </td>
              <td class="px-4 py-3 text-sm text-right text-gray-900">
                {{ item.quantity_on_hand }}
              </td>
              <td class="px-4 py-3 text-sm text-right text-gray-900 font-mono">
                {{ formatMoney(item.old_retail_price) }}
              </td>
              <td class="px-4 py-3 text-sm text-right text-gray-900 font-mono">
                {{ formatMoney(item.new_retail_price) }}
              </td>
              <td class="px-4 py-3 text-sm text-right font-mono"
                :class="item.price_difference > 0 ? 'text-green-700' : item.price_difference < 0 ? 'text-red-700' : 'text-gray-500'"
              >
                {{ formatMoney(item.price_difference) }}
              </td>
              <td class="px-4 py-3 text-sm text-right font-mono font-semibold"
                :class="item.total_difference > 0 ? 'text-green-700' : item.total_difference < 0 ? 'text-red-700' : 'text-gray-500'"
              >
                {{ formatMoney(item.total_difference) }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-500">
                {{ item.notes || '-' }}
              </td>
            </tr>
          </tbody>
          <tfoot class="bg-gray-50">
            <tr>
              <td colspan="7" class="px-4 py-3 text-sm font-medium text-gray-900 text-right">
                {{ $t('trade.total_difference') }}:
              </td>
              <td class="px-4 py-3 text-sm font-bold text-right font-mono"
                :class="nivelacija.total_difference > 0 ? 'text-green-700' : nivelacija.total_difference < 0 ? 'text-red-700' : 'text-gray-900'"
              >
                {{ formatMoney(nivelacija.total_difference) }}
              </td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </BaseCard>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useNotificationStore } from '@/scripts/stores/notification'
import StockTabNavigation from '@/scripts/admin/components/StockTabNavigation.vue'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const companyStore = useCompanyStore()
const dialogStore = useDialogStore()
const notificationStore = useNotificationStore()

const nivelacija = ref(null)
const isLoading = ref(false)
const isApproving = ref(false)
const isVoiding = ref(false)
const isExporting = ref(false)

const companyId = computed(() => companyStore.selectedCompany?.id)

function apiBase() {
  return `/partner/companies/${companyId.value}/accounting`
}

function statusBadgeClass(status) {
  const classes = {
    draft: 'bg-amber-100 text-amber-800',
    approved: 'bg-green-100 text-green-800',
    voided: 'bg-gray-100 text-gray-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

function statusLabel(status) {
  const labels = { draft: t('trade.status_draft'), approved: t('trade.status_approved'), voided: t('trade.status_voided') }
  return labels[status] || status
}

function typeBadgeClass(type) {
  const classes = {
    price_change: 'bg-blue-100 text-blue-800',
    discount: 'bg-purple-100 text-purple-800',
    supplier_change: 'bg-orange-100 text-orange-800',
  }
  return classes[type] || 'bg-gray-100 text-gray-800'
}

function typeLabel(type) {
  const labels = {
    price_change: t('trade.type_price_change'),
    discount: t('trade.type_discount'),
    supplier_change: t('trade.type_supplier_change'),
  }
  return labels[type] || type
}

function formatDate(date) {
  if (!date) return '-'
  return String(date).substring(0, 10)
}

function formatMoney(amount) {
  if (amount === null || amount === undefined) return '-'
  const num = Number(amount) / 100
  return num.toLocaleString('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

async function loadNivelacija() {
  if (!companyId.value) return
  isLoading.value = true
  try {
    const response = await window.axios.get(`${apiBase()}/nivelacii/${route.params.id}`)
    nivelacija.value = response.data.data
  } catch (error) {
    console.error('Failed to load nivelacija:', error)
    notificationStore.showNotification({
      type: 'error',
      message: 'Грешка при вчитување на нивелацијата.',
    })
  } finally {
    isLoading.value = false
  }
}

async function approveNivelacija() {
  const confirmed = await dialogStore.openDialog({
    title: t('trade.approve_nivelacija'),
    message: t('trade.approve_confirm'),
    yesLabel: t('trade.approve_nivelacija'),
    noLabel: 'Откажи',
    variant: 'primary',
  })

  if (!confirmed) return

  isApproving.value = true
  try {
    await window.axios.post(`${apiBase()}/nivelacii/${nivelacija.value.id}/approve`)
    notificationStore.showNotification({
      type: 'success',
      message: 'Нивелацијата е одобрена. Цените се ажурирани.',
    })
    await loadNivelacija()
  } catch (error) {
    console.error('Failed to approve nivelacija:', error)
    const msg = error.response?.data?.error || 'Грешка при одобрување.'
    notificationStore.showNotification({ type: 'error', message: msg })
  } finally {
    isApproving.value = false
  }
}

async function voidNivelacija() {
  const confirmed = await dialogStore.openDialog({
    title: t('trade.void_nivelacija'),
    message: t('trade.void_confirm'),
    yesLabel: t('trade.void_nivelacija'),
    noLabel: 'Откажи',
    variant: 'danger',
  })

  if (!confirmed) return

  isVoiding.value = true
  try {
    await window.axios.post(`${apiBase()}/nivelacii/${nivelacija.value.id}/void`)
    notificationStore.showNotification({
      type: 'success',
      message: 'Нивелацијата е поништена. Цените се вратени.',
    })
    await loadNivelacija()
  } catch (error) {
    console.error('Failed to void nivelacija:', error)
    const msg = error.response?.data?.error || 'Грешка при поништување.'
    notificationStore.showNotification({ type: 'error', message: msg })
  } finally {
    isVoiding.value = false
  }
}

async function exportPdf() {
  if (!companyId.value || !nivelacija.value) return
  isExporting.value = true
  try {
    const response = await window.axios.get(
      `${apiBase()}/nivelacii/${nivelacija.value.id}/export`,
      { responseType: 'blob' }
    )
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `nivelacija_${nivelacija.value.document_number}.pdf`
    link.click()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Failed to export PDF:', error)
    notificationStore.showNotification({
      type: 'error',
      message: 'Грешка при преземање на PDF.',
    })
  } finally {
    isExporting.value = false
  }
}

onMounted(() => {
  loadNivelacija()
})
</script>

// CLAUDE-CHECKPOINT
