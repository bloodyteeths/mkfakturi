<template>
  <BasePage>
    <BasePageHeader :title="t('title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="t('title')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <!-- Opportunities badge -->
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

        <BaseButton
          v-show="showFilters || compensations.length > 0"
          variant="primary-outline"
          class="ml-2"
          @click="showFilters = !showFilters"
        >
          {{ $t('general.filter') }}
          <template #right="slotProps">
            <BaseIcon
              v-if="!showFilters"
              name="FunnelIcon"
              :class="slotProps.class"
            />
            <BaseIcon v-else name="XMarkIcon" :class="slotProps.class" />
          </template>
        </BaseButton>

        <router-link to="compensations/create">
          <BaseButton variant="primary" class="ml-2">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ t('create') }}
          </BaseButton>
        </router-link>
      </template>
    </BasePageHeader>

    <!-- Opportunities Panel -->
    <div v-if="showOpportunities && opportunities.length > 0" class="mb-6 bg-green-50 rounded-lg border border-green-200 p-4">
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
          <div class="flex items-center space-x-3">
            <span class="text-sm font-bold text-green-700">
              {{ t('suggested_amount') }}: {{ formatMoney(opp.suggested_offset) }}
            </span>
            <router-link
              :to="{
                path: 'compensations/create',
                query: { customer_id: opp.customer_id, supplier_id: opp.supplier_id }
              }"
            >
              <BaseButton variant="primary" size="sm">
                {{ t('use_opportunity') }}
              </BaseButton>
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <BaseFilterWrapper
      v-show="showFilters"
      :row-on-xl="true"
      @clear="clearFilters"
    >
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

      <BaseInputGroup :label="$t('general.search')">
        <BaseInput
          v-model="filters.search"
          type="text"
          :placeholder="t('search_placeholder')"
          @input="debouncedFetch"
        />
      </BaseInputGroup>
    </BaseFilterWrapper>

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
            @click="goToPage(page)"
          >
            {{ page }}
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-16">
      <BaseIcon name="ScaleIcon" class="h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">
        {{ t('no_compensations') }}
      </h3>
      <p class="mt-1 text-sm text-gray-500">
        {{ t('no_compensations_description') }}
      </p>
      <div class="mt-6">
        <router-link to="compensations/create">
          <BaseButton variant="primary">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ t('create') }}
          </BaseButton>
        </router-link>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/scripts/stores/notification'
import { debounce } from 'lodash'
import compensationMessages from '@/scripts/admin/i18n/compensations.js'

const router = useRouter()
const notificationStore = useNotificationStore()

// Inline translation helper — reads from our dedicated translations object
// Falls back to the key if not found, so views degrade gracefully
const locale = document.documentElement.lang || 'mk'
function t(key) {
  return compensationMessages[locale]?.compensations?.[key]
    || compensationMessages['en']?.compensations?.[key]
    || key
}

// State
const compensations = ref([])
const meta = ref(null)
const opportunities = ref([])
const opportunityCount = ref(0)
const isLoading = ref(false)
const showFilters = ref(false)
const showOpportunities = ref(false)
const currentPage = ref(1)

const filters = reactive({
  status: null,
  date_from: null,
  date_to: null,
  search: '',
})

const statusOptions = [
  { value: 'draft', label: t('status_draft') },
  { value: 'confirmed', label: t('status_confirmed') },
  { value: 'cancelled', label: t('status_cancelled') },
]

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

async function fetchCompensations(page = 1) {
  isLoading.value = true
  try {
    const params = { page, limit: 15 }
    if (filters.status) params.status = filters.status
    if (filters.date_from) params.date_from = filters.date_from
    if (filters.date_to) params.date_to = filters.date_to
    if (filters.search) params.search = filters.search

    const response = await window.axios.get('/compensations', { params })
    compensations.value = response.data.data || []
    meta.value = response.data.meta || null
    currentPage.value = page
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
  try {
    const response = await window.axios.get('/compensations/opportunities')
    opportunities.value = response.data.data || []
    opportunityCount.value = response.data.count || 0
  } catch {
    // Silently fail — opportunities are optional
  }
}

function goToPage(page) {
  fetchCompensations(page)
}

function viewCompensation(id) {
  router.push({ path: `compensations/${id}` })
}

async function downloadPdf(id) {
  try {
    const response = await window.axios.get(`/compensations/${id}/pdf`, {
      responseType: 'blob',
    })
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

function clearFilters() {
  filters.status = null
  filters.date_from = null
  filters.date_to = null
  filters.search = ''
  fetchCompensations(1)
}

const debouncedFetch = debounce(() => {
  fetchCompensations(1)
}, 400)

watch([() => filters.status, () => filters.date_from, () => filters.date_to], () => {
  fetchCompensations(1)
})

// Lifecycle
onMounted(() => {
  fetchCompensations()
  fetchOpportunities()
})
</script>

<!-- CLAUDE-CHECKPOINT -->
