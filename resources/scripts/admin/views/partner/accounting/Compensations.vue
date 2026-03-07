<template>
  <BasePage>
    <BasePageHeader :title="t('title')">
      <template #actions>
        <BaseButton
          v-if="opportunityCount > 0"
          variant="primary-outline"
          @click="showOpportunities = !showOpportunities"
        >
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="LightBulbIcon" />
          </template>
          {{ t('opportunities') }}
          <span class="ml-1 inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-green-600 rounded-full">
            {{ opportunityCount }}
          </span>
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Company Selector -->
    <div class="mb-6">
      <BaseInputGroup :label="$t('partner.select_company')">
        <BaseMultiselect
          v-model="selectedCompanyId"
          :options="companies"
          :searchable="true"
          label="name"
          value-prop="id"
          :placeholder="$t('partner.select_company_placeholder')"
          @update:model-value="onCompanyChange"
        />
      </BaseInputGroup>
    </div>

    <!-- Opportunities Panel -->
    <div v-if="showOpportunities && opportunities.length > 0 && selectedCompanyId" class="mb-6 bg-green-50 rounded-lg border border-green-200 p-4">
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-semibold text-green-800">
          {{ t('opportunities') }} ({{ opportunities.length }})
        </h3>
        <button class="text-green-600 hover:text-green-800" @click="showOpportunities = false">
          <BaseIcon name="XMarkIcon" class="h-4 w-4" />
        </button>
      </div>
      <div class="space-y-2">
        <div
          v-for="opp in opportunities"
          :key="`${opp.customer_id}-${opp.supplier_id}`"
          class="flex items-center justify-between bg-white rounded p-3 border border-green-100"
        >
          <div>
            <p class="text-sm font-medium text-gray-900">{{ opp.customer_name }} / {{ opp.supplier_name }}</p>
            <p class="text-xs text-gray-500">
              {{ t('open_receivables') }}: {{ formatMoney(opp.open_receivables) }} |
              {{ t('open_payables') }}: {{ formatMoney(opp.open_payables) }}
            </p>
          </div>
          <span class="text-sm font-bold text-green-700">
            {{ t('suggested_amount') }}: {{ formatMoney(opp.suggested_offset) }}
          </span>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div v-if="selectedCompanyId" class="p-4 bg-white rounded-lg shadow mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <BaseInputGroup :label="t('status')">
          <BaseMultiselect
            v-model="filters.status"
            :options="statusOptions"
            :searchable="false"
            label="label"
            value-prop="value"
            :placeholder="$t('general.select_a_status')"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('general.from')">
          <BaseDatePicker
            v-model="filters.date_from"
            :calendar-button="true"
            calendar-button-icon="CalendarDaysIcon"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('general.to')">
          <BaseDatePicker
            v-model="filters.date_to"
            :calendar-button="true"
            calendar-button-icon="CalendarDaysIcon"
          />
        </BaseInputGroup>

        <div class="flex items-end">
          <BaseButton
            variant="primary"
            class="w-full"
            :loading="isLoading"
            @click="fetchCompensations(1)"
          >
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="MagnifyingGlassIcon" />
            </template>
            {{ $t('reports.update_report') }}
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="bg-white rounded-lg shadow overflow-hidden">
      <div class="p-6 space-y-4">
        <div v-for="i in 5" :key="i" class="flex space-x-4 animate-pulse">
          <div class="h-4 bg-gray-200 rounded w-24"></div>
          <div class="h-4 bg-gray-200 rounded w-20"></div>
          <div class="h-4 bg-gray-200 rounded flex-1"></div>
          <div class="h-4 bg-gray-200 rounded w-16"></div>
          <div class="h-4 bg-gray-200 rounded w-20"></div>
          <div class="h-4 bg-gray-200 rounded w-16"></div>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div v-else-if="compensations.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('number') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('date') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('counterparty') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('type') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('amount') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('status') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('actions') }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr
              v-for="comp in compensations"
              :key="comp.id"
              class="hover:bg-gray-50 cursor-pointer"
              @click="viewCompensation(comp.id)"
            >
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-primary-500">
                {{ comp.compensation_number }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(comp.compensation_date) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ comp.customer?.name || comp.supplier?.name || '-' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ comp.type === 'bilateral' ? t('bilateral') : t('unilateral') }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900">
                {{ formatMoney(comp.total_amount) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center">
                <span :class="statusBadgeClass(comp.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                  {{ statusLabel(comp.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                <div class="flex items-center justify-end space-x-2" @click.stop>
                  <BaseButton
                    v-if="comp.status === 'confirmed'"
                    variant="primary-outline"
                    size="sm"
                    @click="downloadPdf(comp.id)"
                  >
                    <BaseIcon name="ArrowDownTrayIcon" class="h-4 w-4" />
                  </BaseButton>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="meta && meta.last_page > 1" class="px-6 py-3 border-t border-gray-200 flex items-center justify-between">
        <p class="text-sm text-gray-500">
          {{ meta.total }} {{ t('title').toLowerCase() }}
        </p>
        <div class="flex space-x-1">
          <BaseButton
            v-for="page in meta.last_page"
            :key="page"
            :variant="page === meta.current_page ? 'primary' : 'primary-outline'"
            size="sm"
            @click="fetchCompensations(page)"
          >
            {{ page }}
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="selectedCompanyId && !isLoading"
      class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-16"
    >
      <BaseIcon name="ScaleIcon" class="h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">
        {{ t('no_compensations') }}
      </h3>
      <p class="mt-1 text-sm text-gray-500">
        {{ t('no_compensations_description') }}
      </p>
    </div>

    <!-- Select company message -->
    <div
      v-else-if="!selectedCompanyId"
      class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-12"
    >
      <BaseIcon name="BuildingOfficeIcon" class="h-12 w-12 text-gray-400" />
      <p class="mt-2 text-sm text-gray-500">
        {{ $t('partner.accounting.select_company_to_view') }}
      </p>
    </div>

    <!-- Detail Modal -->
    <div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="closeDetail" />
      <div class="relative bg-white rounded-lg shadow-xl max-w-3xl w-full mx-4 max-h-[85vh] overflow-y-auto">
        <div class="sticky top-0 bg-white px-6 py-4 border-b border-gray-200 flex items-center justify-between z-10">
          <div>
            <h3 class="text-lg font-medium text-gray-900">{{ selectedComp?.compensation_number }}</h3>
            <span :class="statusBadgeClass(selectedComp?.status)" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium mt-1">
              {{ statusLabel(selectedComp?.status) }}
            </span>
          </div>
          <div class="flex items-center space-x-2">
            <BaseButton
              v-if="selectedComp?.status === 'confirmed'"
              variant="primary-outline"
              size="sm"
              @click="downloadPdf(selectedComp.id)"
            >
              <template #left="slotProps">
                <BaseIcon name="ArrowDownTrayIcon" :class="slotProps.class" />
              </template>
              {{ t('download_pdf') }}
            </BaseButton>
            <button class="text-gray-400 hover:text-gray-600" @click="closeDetail">
              <BaseIcon name="XMarkIcon" class="h-5 w-5" />
            </button>
          </div>
        </div>

        <div v-if="isLoadingDetail" class="p-6 space-y-4">
          <div v-for="i in 4" :key="i" class="flex space-x-4 animate-pulse">
            <div class="h-4 bg-gray-200 rounded flex-1"></div>
          </div>
        </div>

        <div v-else-if="selectedComp" class="p-6 space-y-4">
          <!-- Summary -->
          <div class="grid grid-cols-3 gap-3">
            <div class="bg-blue-50 rounded p-3 text-center">
              <p class="text-xs text-blue-600 uppercase">{{ t('total_receivables') }}</p>
              <p class="text-lg font-bold text-blue-800">{{ formatMoney(selectedComp.receivables_total) }}</p>
            </div>
            <div class="bg-amber-50 rounded p-3 text-center">
              <p class="text-xs text-amber-600 uppercase">{{ t('total_payables') }}</p>
              <p class="text-lg font-bold text-amber-800">{{ formatMoney(selectedComp.payables_total) }}</p>
            </div>
            <div class="bg-green-50 rounded p-3 text-center">
              <p class="text-xs text-green-600 uppercase">{{ t('offset_amount') }}</p>
              <p class="text-lg font-bold text-green-800">{{ formatMoney(selectedComp.total_amount) }}</p>
            </div>
          </div>

          <!-- Items -->
          <div v-if="detailReceivables.length > 0">
            <h4 class="text-xs font-semibold text-blue-800 uppercase mb-1">{{ t('our_receivables') }}</h4>
            <table class="min-w-full divide-y divide-gray-200 border border-gray-200 rounded text-sm">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-3 py-1 text-left text-xs font-medium text-gray-500">{{ t('document_number') }}</th>
                  <th class="px-3 py-1 text-right text-xs font-medium text-gray-500">{{ t('document_total') }}</th>
                  <th class="px-3 py-1 text-right text-xs font-medium text-gray-500">{{ t('amount_to_offset') }}</th>
                  <th class="px-3 py-1 text-right text-xs font-medium text-gray-500">{{ t('remaining_after') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="item in detailReceivables" :key="item.id">
                  <td class="px-3 py-2">{{ item.document_number }}</td>
                  <td class="px-3 py-2 text-right text-gray-500">{{ formatMoney(item.document_total) }}</td>
                  <td class="px-3 py-2 text-right font-medium text-blue-700">{{ formatMoney(item.amount_offset) }}</td>
                  <td class="px-3 py-2 text-right text-gray-500">{{ formatMoney(item.remaining_after) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="detailPayables.length > 0">
            <h4 class="text-xs font-semibold text-amber-800 uppercase mb-1">{{ t('our_payables') }}</h4>
            <table class="min-w-full divide-y divide-gray-200 border border-gray-200 rounded text-sm">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-3 py-1 text-left text-xs font-medium text-gray-500">{{ t('document_number') }}</th>
                  <th class="px-3 py-1 text-right text-xs font-medium text-gray-500">{{ t('document_total') }}</th>
                  <th class="px-3 py-1 text-right text-xs font-medium text-gray-500">{{ t('amount_to_offset') }}</th>
                  <th class="px-3 py-1 text-right text-xs font-medium text-gray-500">{{ t('remaining_after') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="item in detailPayables" :key="item.id">
                  <td class="px-3 py-2">{{ item.document_number }}</td>
                  <td class="px-3 py-2 text-right text-gray-500">{{ formatMoney(item.document_total) }}</td>
                  <td class="px-3 py-2 text-right font-medium text-amber-700">{{ formatMoney(item.amount_offset) }}</td>
                  <td class="px-3 py-2 text-right text-gray-500">{{ formatMoney(item.remaining_after) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="selectedComp.notes" class="text-sm text-gray-600 bg-gray-50 rounded p-3">
            <strong>{{ t('notes') }}:</strong> {{ selectedComp.notes }}
          </div>
        </div>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import { useNotificationStore } from '@/scripts/stores/notification'
import compensationMessages from '@/scripts/admin/i18n/compensations.js'

const consoleStore = useConsoleStore()
const notificationStore = useNotificationStore()

const locale = document.documentElement.lang || 'mk'
function t(key) {
  return compensationMessages[locale]?.compensations?.[key]
    || compensationMessages['en']?.compensations?.[key]
    || key
}

// State
const selectedCompanyId = ref(null)
const compensations = ref([])
const meta = ref(null)
const opportunities = ref([])
const opportunityCount = ref(0)
const isLoading = ref(false)
const showOpportunities = ref(false)

// Detail modal
const showDetailModal = ref(false)
const selectedComp = ref(null)
const isLoadingDetail = ref(false)

const filters = reactive({
  status: null,
  date_from: null,
  date_to: null,
})

const statusOptions = [
  { value: 'draft', label: t('status_draft') },
  { value: 'confirmed', label: t('status_confirmed') },
  { value: 'cancelled', label: t('status_cancelled') },
]

const companies = computed(() => consoleStore.managedCompanies || [])

const detailReceivables = computed(() => {
  return (selectedComp.value?.items || []).filter(i => i.side === 'receivable')
})

const detailPayables = computed(() => {
  return (selectedComp.value?.items || []).filter(i => i.side === 'payable')
})

const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }
const formattedLocale = localeMap[locale] || 'mk-MK'

// Methods
function formatMoney(cents) {
  if (!cents && cents !== 0) return '-'
  return new Intl.NumberFormat(formattedLocale, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(cents / 100)
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString(formattedLocale, { year: 'numeric', month: '2-digit', day: '2-digit' })
}

function statusBadgeClass(status) {
  switch (status) {
    case 'draft': return 'bg-gray-100 text-gray-700'
    case 'confirmed': return 'bg-green-100 text-green-800'
    case 'cancelled': return 'bg-red-100 text-red-800'
    default: return 'bg-gray-100 text-gray-700'
  }
}

function statusLabel(status) {
  switch (status) {
    case 'draft': return t('status_draft')
    case 'confirmed': return t('status_confirmed')
    case 'cancelled': return t('status_cancelled')
    default: return status
  }
}

function onCompanyChange() {
  compensations.value = []
  meta.value = null
  opportunities.value = []
  opportunityCount.value = 0
  if (selectedCompanyId.value) {
    fetchCompensations(1)
    fetchOpportunities()
  }
}

async function fetchCompensations(page = 1) {
  if (!selectedCompanyId.value) return

  isLoading.value = true
  try {
    const params = { page, limit: 15 }
    if (filters.status) params.status = filters.status
    if (filters.date_from) params.date_from = filters.date_from
    if (filters.date_to) params.date_to = filters.date_to

    const response = await window.axios.get(
      `/partner/companies/${selectedCompanyId.value}/accounting/compensations`,
      { params }
    )
    compensations.value = response.data.data || []
    meta.value = response.data.meta || null
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_loading') || 'Failed to load compensations',
    })
  } finally {
    isLoading.value = false
  }
}

async function fetchOpportunities() {
  if (!selectedCompanyId.value) return

  try {
    const response = await window.axios.get(
      `/partner/companies/${selectedCompanyId.value}/accounting/compensations/opportunities`
    )
    opportunities.value = response.data.data || []
    opportunityCount.value = response.data.count || 0
  } catch {
    // Silently fail
  }
}

async function viewCompensation(id) {
  showDetailModal.value = true
  isLoadingDetail.value = true
  selectedComp.value = null

  try {
    const response = await window.axios.get(
      `/partner/companies/${selectedCompanyId.value}/accounting/compensations/${id}`
    )
    selectedComp.value = response.data?.data || null
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: t('error_loading') || 'Failed to load compensation details',
    })
    showDetailModal.value = false
  } finally {
    isLoadingDetail.value = false
  }
}

function closeDetail() {
  showDetailModal.value = false
  selectedComp.value = null
}

async function downloadPdf(id) {
  try {
    const response = await window.axios.get(
      `/partner/companies/${selectedCompanyId.value}/accounting/compensations/${id}/pdf`,
      { responseType: 'blob' }
    )
    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `kompenzacija_${id}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: t('error_download_pdf') || 'Failed to download PDF',
    })
  }
}

// Lifecycle
onMounted(async () => {
  try {
    await consoleStore.fetchCompanies()
    if (companies.value.length > 0) {
      selectedCompanyId.value = companies.value[0].id
      fetchCompensations(1)
      fetchOpportunities()
    }
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: t('error_loading') || 'Failed to load companies',
    })
  }
})
</script>

<!-- CLAUDE-CHECKPOINT -->
