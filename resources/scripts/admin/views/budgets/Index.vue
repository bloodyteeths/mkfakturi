<template>
  <BasePage>
    <BasePageHeader :title="t('title')">
      <template #actions>
        <div class="flex items-center gap-3">
          <!-- Search -->
          <input
            v-model="searchQuery"
            type="text"
            :placeholder="t('search')"
            class="rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 w-48"
          />

          <!-- Filter Toggle -->
          <button
            class="inline-flex items-center gap-1.5 px-3 py-2 border rounded-md text-sm font-medium focus:outline-none focus:ring-2 focus:ring-primary-500 transition-colors"
            :class="showFilters
              ? 'border-primary-500 text-primary-700 bg-primary-50'
              : 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50'"
            @click="showFilters = !showFilters"
          >
            <BaseIcon name="FunnelIcon" class="h-4 w-4" />
            {{ t('filters') || 'Филтри' }}
            <span
              v-if="activeFilterCount > 0"
              class="inline-flex items-center justify-center h-5 w-5 rounded-full bg-primary-600 text-white text-xs font-bold"
            >
              {{ activeFilterCount }}
            </span>
          </button>

          <BaseButton variant="primary" @click="$router.push({ name: 'budgets.create' })">
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="PlusIcon" />
            </template>
            {{ t('create') }}
          </BaseButton>
        </div>
      </template>
    </BasePageHeader>

    <!-- Collapsible Filters Panel -->
    <transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0 -translate-y-2"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 -translate-y-2"
    >
      <div v-show="showFilters" class="bg-white rounded-lg shadow p-4 mb-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Status Filter -->
          <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">{{ t('status') }}</label>
            <select
              v-model="filterStatus"
              class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
            >
              <option value="">{{ t('all') }}</option>
              <option value="draft">{{ t('draft') }}</option>
              <option value="approved">{{ t('approved') }}</option>
              <option value="locked">{{ t('locked') }}</option>
              <option value="archived">{{ t('archived') }}</option>
            </select>
          </div>

          <!-- Year Filter -->
          <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">{{ t('period') }}</label>
            <select
              v-model="filterYear"
              class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
            >
              <option value="">{{ t('all') }}</option>
              <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
            </select>
          </div>

          <!-- Scenario Filter -->
          <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">{{ t('scenario') }}</label>
            <select
              v-model="filterScenario"
              class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
            >
              <option value="">{{ t('all') }}</option>
              <option value="expected">{{ t('scenario_expected') }}</option>
              <option value="optimistic">{{ t('scenario_optimistic') }}</option>
              <option value="pessimistic">{{ t('scenario_pessimistic') }}</option>
            </select>
          </div>

          <!-- Cost Center Filter -->
          <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">{{ t('cost_center') }}</label>
            <select
              v-model="filterCostCenter"
              class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
            >
              <option value="">{{ t('all') }}</option>
              <option v-for="cc in costCenterOptions" :key="cc.id" :value="cc.id">{{ cc.name }}</option>
            </select>
          </div>
        </div>

        <!-- Clear Filters -->
        <div v-if="activeFilterCount > 0" class="mt-3 text-right">
          <button
            class="text-sm text-primary-600 hover:text-primary-800 font-medium"
            @click="clearFilters"
          >
            {{ t('clear_filters') || 'Избриши филтри' }}
          </button>
        </div>
      </div>
    </transition>

    <!-- Stats Cards -->
    <div v-if="!isLoading" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm font-medium text-gray-500">{{ t('total_amount') }}</p>
        <p class="mt-1 text-2xl font-semibold text-gray-900">{{ formatNumber(totalBudgetedAmount) }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm font-medium text-gray-500">{{ t('drafts_pending') || 'Нацрти' }}</p>
        <p class="mt-1 text-2xl font-semibold text-yellow-600">{{ draftCount }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm font-medium text-gray-500">{{ t('active_budgets') }}</p>
        <p class="mt-1 text-2xl font-semibold text-green-600">{{ activeBudgetsCount }}</p>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="bg-white rounded-lg shadow p-6">
      <div class="space-y-4 animate-pulse">
        <div v-for="i in 5" :key="i" class="flex items-center space-x-4">
          <div class="h-4 bg-gray-200 rounded flex-1"></div>
          <div class="h-4 bg-gray-200 rounded w-24"></div>
          <div class="h-4 bg-gray-200 rounded w-20"></div>
          <div class="h-4 bg-gray-200 rounded w-16"></div>
        </div>
      </div>
    </div>

    <!-- Budget List -->
    <div v-else-if="budgets.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="w-10 px-4 py-3">
              <input
                type="checkbox"
                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                :checked="allSelected"
                :indeterminate="someSelected && !allSelected"
                @change="toggleSelectAll"
              />
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('name') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('period') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('scenario') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('status') }}</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('total_amount') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('cost_center') }}</th>
            <th class="w-12 px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
          <tr
            v-for="budget in paginatedBudgets"
            :key="budget.id"
            class="hover:bg-gray-50 cursor-pointer"
            :class="{ 'bg-primary-50': selectedIds.includes(budget.id) }"
            @click="$router.push({ name: 'budgets.view', params: { id: budget.id } })"
          >
            <td class="px-4 py-3" @click.stop>
              <input
                type="checkbox"
                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                :checked="selectedIds.includes(budget.id)"
                @change="toggleSelect(budget.id)"
              />
            </td>
            <td class="px-4 py-3">
              <div class="text-sm font-medium text-gray-900">
                <span v-if="budget.number" class="text-gray-500 font-normal">{{ budget.number }} &middot; </span>{{ budget.name }}
              </div>
              <div class="text-xs text-gray-500">
                {{ budget.lines_count }} {{ t('lines').toLowerCase() }}
                <template v-if="budget.created_by_user">
                  <span class="mx-1">&middot;</span>
                  <span class="text-gray-400">{{ t('created_by_label') }}: {{ budget.created_by_user.name }}</span>
                </template>
              </div>
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">
              {{ formatDate(budget.start_date) }} - {{ formatDate(budget.end_date) }}
              <span class="ml-1 text-xs text-gray-400">({{ scenarioLabel(budget.period_type) }})</span>
            </td>
            <td class="whitespace-nowrap px-4 py-3">
              <span
                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                :class="scenarioBadgeClass(budget.scenario)"
              >
                {{ scenarioLabel(budget.scenario) }}
              </span>
            </td>
            <td class="whitespace-nowrap px-4 py-3">
              <span
                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                :class="statusBadgeClass(budget.status)"
              >
                {{ statusLabel(budget.status) }}
              </span>
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-sm text-right font-medium text-gray-900">
              {{ formatNumber(budget.total_amount || 0) }}
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">
              <span v-if="budget.cost_center" class="flex items-center">
                <span
                  class="inline-block h-3 w-3 rounded-full mr-1.5"
                  :style="{ backgroundColor: budget.cost_center.color || '#6366f1' }"
                ></span>
                {{ budget.cost_center.name }}
              </span>
              <span v-else class="text-gray-400">-</span>
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-right text-sm" @click.stop>
              <div class="relative">
                <button
                  class="p-1 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 focus:outline-none"
                  @click="toggleDropdown(budget.id)"
                >
                  <BaseIcon name="EllipsisVerticalIcon" class="h-5 w-5" />
                </button>

                <!-- Row Action Dropdown -->
                <div
                  v-show="openDropdownId === budget.id"
                  class="absolute right-0 top-8 z-30 w-48 rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 py-1"
                >
                  <!-- View -->
                  <button
                    class="w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                    @click="goTo('budgets.view', budget.id)"
                  >
                    <BaseIcon name="EyeIcon" class="h-4 w-4 text-gray-400" />
                    {{ t('view') || 'Прегледај' }}
                  </button>

                  <!-- Edit (draft only) -->
                  <button
                    v-if="budget.status === 'draft'"
                    class="w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                    @click="goTo('budgets.edit', budget.id)"
                  >
                    <BaseIcon name="PencilSquareIcon" class="h-4 w-4 text-gray-400" />
                    {{ t('edit') }}
                  </button>

                  <!-- Clone -->
                  <button
                    class="w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                    @click="cloneBudget(budget.id)"
                  >
                    <BaseIcon name="DocumentDuplicateIcon" class="h-4 w-4 text-gray-400" />
                    {{ t('clone') || 'Клонирај' }}
                  </button>

                  <!-- Archive (approved/locked only) -->
                  <button
                    v-if="budget.status === 'approved' || budget.status === 'locked'"
                    class="w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                    @click="archiveBudget(budget.id)"
                  >
                    <BaseIcon name="ArchiveBoxIcon" class="h-4 w-4 text-gray-400" />
                    {{ t('archive') || 'Архивирај' }}
                  </button>

                  <!-- Export PDF -->
                  <button
                    class="w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                    @click="exportPdf(budget.id)"
                  >
                    <BaseIcon name="ArrowDownTrayIcon" class="h-4 w-4 text-gray-400" />
                    {{ t('export_pdf') || 'Извези PDF' }}
                  </button>

                  <div class="border-t border-gray-100 my-1"></div>

                  <!-- Delete (draft only) -->
                  <button
                    v-if="budget.status === 'draft'"
                    class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50"
                    @click="confirmDelete(budget)"
                  >
                    <BaseIcon name="TrashIcon" class="h-4 w-4 text-red-400" />
                    {{ t('delete') || 'Избриши' }}
                  </button>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="flex items-center justify-between border-t border-gray-200 bg-gray-50 px-4 py-3">
        <div class="text-sm text-gray-600">
          {{ t('page_of').replace('{page}', currentPage).replace('{total}', totalPages) }}
        </div>
        <div class="flex space-x-2">
          <button
            :disabled="currentPage <= 1"
            class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-sm font-medium bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            @click="currentPage--"
          >
            &laquo;
          </button>
          <button
            :disabled="currentPage >= totalPages"
            class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-sm font-medium bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            @click="currentPage++"
          >
            &raquo;
          </button>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="!isLoading"
      class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-white py-16"
    >
      <BaseIcon name="CalculatorIcon" class="h-12 w-12 text-gray-400" />
      <h3 class="mt-4 text-sm font-medium text-gray-900">{{ t('no_budgets') }}</h3>
      <p class="mt-1 text-sm text-gray-500">{{ t('empty_description') }}</p>
      <BaseButton variant="primary" class="mt-4" @click="$router.push({ name: 'budgets.create' })">
        <template #left="slotProps">
          <BaseIcon :class="slotProps.class" name="PlusIcon" />
        </template>
        {{ t('create') }}
      </BaseButton>
    </div>

    <!-- Bulk Action Bar -->
    <transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0 translate-y-4"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-4"
    >
      <div
        v-if="selectedIds.length > 0"
        class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 flex items-center gap-3 rounded-lg bg-gray-900 px-5 py-3 shadow-xl text-white"
      >
        <span class="text-sm font-medium">
          {{ selectedIds.length }} {{ t('selected') || 'избрани' }}
        </span>

        <button
          v-if="selectedDraftIds.length > 0"
          class="inline-flex items-center gap-1.5 rounded-md bg-red-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-red-700 transition-colors"
          @click="bulkDelete"
        >
          <BaseIcon name="TrashIcon" class="h-4 w-4" />
          {{ t('delete') || 'Избриши' }} ({{ selectedDraftIds.length }})
        </button>

        <button
          v-if="selectedArchivableIds.length > 0"
          class="inline-flex items-center gap-1.5 rounded-md bg-blue-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-blue-700 transition-colors"
          @click="bulkArchive"
        >
          <BaseIcon name="ArchiveBoxIcon" class="h-4 w-4" />
          {{ t('archive') || 'Архивирај' }} ({{ selectedArchivableIds.length }})
        </button>

        <button
          class="ml-2 rounded-md p-1 text-gray-400 hover:text-white transition-colors"
          @click="selectedIds = []"
        >
          <BaseIcon name="XMarkIcon" class="h-5 w-5" />
        </button>
      </div>
    </transition>

    <!-- Dropdown Backdrop (click-outside handler) -->
    <div
      v-if="openDropdownId !== null"
      class="fixed inset-0 z-20"
      @click="openDropdownId = null"
    ></div>

  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted, watch, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useI18n } from 'vue-i18n'
import { debounce } from 'lodash'
import budgetMessages from '@/scripts/admin/i18n/budgets.js'

const router = useRouter()
const notificationStore = useNotificationStore()
const dialogStore = useDialogStore()
const { t: $t } = useI18n()

const locale = document.documentElement.lang || 'mk'
const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }
const fmtLocale = localeMap[locale] || 'mk-MK'
function t(key) {
  return budgetMessages[locale]?.budgets?.[key]
    || budgetMessages['en']?.budgets?.[key]
    || key
}

// State
const budgets = ref([])
const isLoading = ref(false)
const filterStatus = ref('')
const filterYear = ref('')
const filterScenario = ref('')
const filterCostCenter = ref('')
const searchQuery = ref('')
const deletingBudget = ref(null)
const currentPage = ref(1)
const perPage = 15

// UX State
const showFilters = ref(false)
const selectedIds = ref([])
const openDropdownId = ref(null)

// Computed
const currentYear = new Date().getFullYear()
const yearOptions = computed(() => {
  const years = []
  for (let y = currentYear + 1; y >= currentYear - 3; y--) {
    years.push(y)
  }
  return years
})

const costCenterOptions = computed(() => {
  const seen = new Map()
  for (const b of budgets.value) {
    if (b.cost_center && !seen.has(b.cost_center.id)) {
      seen.set(b.cost_center.id, b.cost_center)
    }
  }
  return Array.from(seen.values())
})

const activeFilterCount = computed(() => {
  let count = 0
  if (filterStatus.value) count++
  if (filterYear.value) count++
  if (filterScenario.value) count++
  if (filterCostCenter.value) count++
  return count
})

const totalBudgetedAmount = computed(() => {
  return budgets.value.reduce((sum, b) => sum + Number(b.total_amount || 0), 0)
})

const draftCount = computed(() => {
  return budgets.value.filter(b => b.status === 'draft').length
})

const activeBudgetsCount = computed(() => {
  return budgets.value.filter(b => b.status === 'approved' || b.status === 'locked').length
})

const filteredBudgets = computed(() => {
  let result = budgets.value
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    result = result.filter(b => {
      return (b.name && b.name.toLowerCase().includes(q))
        || (b.number && b.number.toLowerCase().includes(q))
    })
  }
  if (filterCostCenter.value) {
    result = result.filter(b => b.cost_center && b.cost_center.id === Number(filterCostCenter.value))
  }
  return result
})

const totalPages = computed(() => {
  return Math.max(1, Math.ceil(filteredBudgets.value.length / perPage))
})

const paginatedBudgets = computed(() => {
  const start = (currentPage.value - 1) * perPage
  return filteredBudgets.value.slice(start, start + perPage)
})

// Bulk selection computed
const allSelected = computed(() => {
  if (paginatedBudgets.value.length === 0) return false
  return paginatedBudgets.value.every(b => selectedIds.value.includes(b.id))
})

const someSelected = computed(() => {
  return paginatedBudgets.value.some(b => selectedIds.value.includes(b.id))
})

const selectedDraftIds = computed(() => {
  return selectedIds.value.filter(id => {
    const b = budgets.value.find(x => x.id === id)
    return b && b.status === 'draft'
  })
})

const selectedArchivableIds = computed(() => {
  return selectedIds.value.filter(id => {
    const b = budgets.value.find(x => x.id === id)
    return b && (b.status === 'approved' || b.status === 'locked')
  })
})

// Reset to page 1 when search changes
watch(searchQuery, () => {
  currentPage.value = 1
})

// Lifecycle
onMounted(() => {
  loadBudgets()
})

const debouncedLoad = debounce(() => {
  loadBudgets()
}, 300)

watch([filterStatus, filterYear, filterScenario, filterCostCenter], () => {
  currentPage.value = 1
  debouncedLoad()
})

// Methods
async function loadBudgets() {
  isLoading.value = true
  try {
    const params = {}
    if (filterStatus.value) params.status = filterStatus.value
    if (filterYear.value) params.year = filterYear.value
    if (filterScenario.value) params.scenario = filterScenario.value

    const response = await window.axios.get('/budgets', { params })
    budgets.value = response.data?.data || []
    selectedIds.value = []
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('error_loading'),
    })
  } finally {
    isLoading.value = false
  }
}

function clearFilters() {
  filterStatus.value = ''
  filterYear.value = ''
  filterScenario.value = ''
  filterCostCenter.value = ''
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString(fmtLocale, { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function formatNumber(val) {
  return Number(val || 0).toLocaleString(fmtLocale, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function statusLabel(status) {
  const labels = {
    draft: t('draft'),
    approved: t('approved'),
    locked: t('locked'),
    archived: t('archived'),
  }
  return labels[status] || status
}

function statusBadgeClass(status) {
  const classes = {
    draft: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    locked: 'bg-blue-100 text-blue-800',
    archived: 'bg-gray-100 text-gray-600',
  }
  return classes[status] || 'bg-gray-100 text-gray-600'
}

function scenarioLabel(scenario) {
  const labels = {
    expected: t('scenario_expected'),
    optimistic: t('scenario_optimistic'),
    pessimistic: t('scenario_pessimistic'),
    monthly: t('period_monthly'),
    quarterly: t('period_quarterly'),
    yearly: t('period_yearly'),
  }
  return labels[scenario] || scenario
}

function scenarioBadgeClass(scenario) {
  const classes = {
    expected: 'bg-blue-100 text-blue-800',
    optimistic: 'bg-green-100 text-green-800',
    pessimistic: 'bg-red-100 text-red-800',
  }
  return classes[scenario] || 'bg-gray-100 text-gray-600'
}

// Selection
function toggleSelectAll() {
  if (allSelected.value) {
    const pageIds = paginatedBudgets.value.map(b => b.id)
    selectedIds.value = selectedIds.value.filter(id => !pageIds.includes(id))
  } else {
    const pageIds = paginatedBudgets.value.map(b => b.id)
    const merged = new Set([...selectedIds.value, ...pageIds])
    selectedIds.value = Array.from(merged)
  }
}

function toggleSelect(id) {
  const idx = selectedIds.value.indexOf(id)
  if (idx >= 0) {
    selectedIds.value.splice(idx, 1)
  } else {
    selectedIds.value.push(id)
  }
}

// Row action dropdown
function toggleDropdown(id) {
  openDropdownId.value = openDropdownId.value === id ? null : id
}

function goTo(routeName, id) {
  openDropdownId.value = null
  router.push({ name: routeName, params: { id } })
}

// Actions
async function cloneBudget(id) {
  openDropdownId.value = null
  try {
    await window.axios.post(`/budgets/${id}/clone`)
    notificationStore.showNotification({
      type: 'success',
      message: t('cloned_success') || 'Budget cloned successfully',
    })
    await loadBudgets()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('error_loading'),
    })
  }
}

async function archiveBudget(id) {
  openDropdownId.value = null
  dialogStore
    .openDialog({
      title: $t('general.are_you_sure'),
      message: t('confirm_archive') || 'Archive this budget?',
      yesLabel: $t('general.ok'),
      noLabel: $t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'lg',
    })
    .then(async (res) => {
      if (res) {
        try {
          await window.axios.post(`/budgets/${id}/archive`)
          notificationStore.showNotification({
            type: 'success',
            message: t('archived_success') || 'Budget archived successfully',
          })
          await loadBudgets()
        } catch (error) {
          notificationStore.showNotification({
            type: 'error',
            message: error.response?.data?.error || t('error_loading'),
          })
        }
      }
    })
}

async function exportPdf(id) {
  openDropdownId.value = null
  try {
    const response = await window.axios.get(`/budgets/${id}/pdf`, { responseType: 'blob' })
    const url = URL.createObjectURL(response.data)
    const link = document.createElement('a')
    link.href = url
    link.download = `budget-${id}.pdf`
    link.click()
    URL.revokeObjectURL(url)
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('error_loading'),
    })
  }
}

function confirmDelete(budget) {
  openDropdownId.value = null
  deletingBudget.value = budget
  dialogStore
    .openDialog({
      title: $t('general.are_you_sure'),
      message: t('confirm_delete') || `Delete "${budget.name}"?`,
      yesLabel: $t('general.ok'),
      noLabel: $t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then(async (res) => {
      if (res) {
        try {
          await window.axios.delete(`/budgets/${deletingBudget.value.id}`)
          notificationStore.showNotification({
            type: 'success',
            message: t('deleted_success') || 'Deleted successfully',
          })
          deletingBudget.value = null
          await loadBudgets()
        } catch (error) {
          notificationStore.showNotification({
            type: 'error',
            message: error.response?.data?.error || t('error_loading'),
          })
        }
      }
    })
}

// Bulk actions
function bulkDelete() {
  const ids = [...selectedDraftIds.value]
  if (!ids.length) return

  dialogStore
    .openDialog({
      title: $t('general.are_you_sure'),
      message: (t('confirm_bulk_delete') || 'Delete {count} draft budgets?').replace('{count}', ids.length),
      yesLabel: $t('general.ok'),
      noLabel: $t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then(async (res) => {
      if (res) {
        try {
          for (const id of ids) {
            await window.axios.delete(`/budgets/${id}`)
          }
          notificationStore.showNotification({
            type: 'success',
            message: (t('bulk_deleted_success') || '{count} budgets deleted').replace('{count}', ids.length),
          })
          selectedIds.value = []
          await loadBudgets()
        } catch (error) {
          notificationStore.showNotification({
            type: 'error',
            message: error.response?.data?.error || t('error_loading'),
          })
        }
      }
    })
}

function bulkArchive() {
  const ids = [...selectedArchivableIds.value]
  if (!ids.length) return

  dialogStore
    .openDialog({
      title: $t('general.are_you_sure'),
      message: (t('confirm_bulk_archive') || 'Archive {count} budgets?').replace('{count}', ids.length),
      yesLabel: $t('general.ok'),
      noLabel: $t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'lg',
    })
    .then(async (res) => {
      if (res) {
        try {
          for (const id of ids) {
            await window.axios.post(`/budgets/${id}/archive`)
          }
          notificationStore.showNotification({
            type: 'success',
            message: (t('bulk_archived_success') || '{count} budgets archived').replace('{count}', ids.length),
          })
          selectedIds.value = []
          await loadBudgets()
        } catch (error) {
          notificationStore.showNotification({
            type: 'error',
            message: error.response?.data?.error || t('error_loading'),
          })
        }
      }
    })
}
</script>

<!-- CLAUDE-CHECKPOINT -->
