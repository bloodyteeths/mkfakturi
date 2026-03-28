<template>
  <BasePage>
    <BasePageHeader :title="t('title')">
      <template #actions>
        <div class="flex items-center space-x-2">
          <!-- View toggle -->
          <div class="flex border border-gray-300 rounded-md overflow-hidden">
            <button
              class="px-3 py-1.5 text-sm"
              :class="viewMode === 'tree' ? 'bg-primary-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
              @click="viewMode = 'tree'"
            >
              <BaseIcon name="Bars3BottomLeftIcon" class="h-4 w-4" />
            </button>
            <button
              class="px-3 py-1.5 text-sm border-l border-gray-300"
              :class="viewMode === 'flat' ? 'bg-primary-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
              @click="viewMode = 'flat'"
            >
              <BaseIcon name="ListBulletIcon" class="h-4 w-4" />
            </button>
          </div>

          <BaseButton variant="primary" @click="openCreateForm">
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="PlusIcon" />
            </template>
            {{ t('title') }}
          </BaseButton>
        </div>
      </template>
    </BasePageHeader>

    <!-- Sub-navigation tabs -->
    <div class="flex border-b border-gray-200 mb-6">
      <router-link
        v-for="tab in tabs"
        :key="tab.name"
        :to="tab.to"
        class="px-4 py-2 text-sm font-medium border-b-2 -mb-px"
        :class="currentRouteName === tab.name
          ? 'border-primary-500 text-primary-600'
          : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
      >
        {{ tab.label }}
      </router-link>
    </div>

    <!-- Search (flat mode) -->
    <div v-if="viewMode === 'flat' && costCenters.length > 0" class="mb-4">
      <div class="relative">
        <BaseIcon name="MagnifyingGlassIcon" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
        <input
          v-model="searchQuery"
          type="text"
          :placeholder="t('search_placeholder')"
          class="block w-full rounded-md border-gray-300 pl-10 pr-4 py-2 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
        />
      </div>
    </div>

    <!-- Bulk actions bar -->
    <div
      v-if="selectedIds.length > 0"
      class="mb-4 flex items-center space-x-3 bg-primary-50 border border-primary-200 rounded-lg px-4 py-3"
    >
      <span class="text-sm font-medium text-primary-700">
        {{ selectedIds.length }} {{ t('selected_count') }}
      </span>
      <BaseButton size="sm" variant="danger" @click="bulkDelete">
        <template #left="slotProps">
          <BaseIcon :class="slotProps.class" name="TrashIcon" />
        </template>
        {{ t('bulk_delete') }}
      </BaseButton>
      <BaseButton size="sm" variant="primary-outline" @click="bulkSetActive(true)">
        {{ t('bulk_activate') }}
      </BaseButton>
      <BaseButton size="sm" variant="primary-outline" @click="bulkSetActive(false)">
        {{ t('bulk_deactivate') }}
      </BaseButton>
    </div>

    <!-- Loading state -->
    <div v-if="isLoading" class="bg-white rounded-lg shadow p-6">
      <div class="space-y-4 animate-pulse">
        <div v-for="i in 5" :key="i" class="flex items-center space-x-4">
          <div class="h-4 w-4 bg-gray-200 rounded"></div>
          <div class="h-4 bg-gray-200 rounded w-16"></div>
          <div class="h-4 bg-gray-200 rounded flex-1"></div>
          <div class="h-4 bg-gray-200 rounded w-48"></div>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div v-else-if="costCenters.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
      <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-sm font-medium text-gray-700">
          {{ filteredCostCenters.length }} {{ t('title').toLowerCase() }}
        </h3>
      </div>

      <!-- Tree mode -->
      <div v-if="viewMode === 'tree'" class="divide-y divide-gray-100">
        <CostCenterTreeNode
          v-for="node in treeData"
          :key="node.id"
          :node="node"
          :depth="0"
          @edit="openEditForm"
          @add-child="openCreateChildForm"
          @delete="confirmDelete"
        />
      </div>

      <!-- Flat list mode -->
      <template v-else>
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 w-10">
                <input
                  type="checkbox"
                  class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                  :checked="isAllSelected"
                  :indeterminate="isIndeterminate"
                  @change="toggleSelectAll"
                />
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-12">
                {{ t('color') }}
              </th>
              <th
                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:text-gray-700 select-none"
                @click="toggleSort('code')"
              >
                {{ t('code') }}
                <span v-if="sortField === 'code'" class="ml-1">{{ sortDir === 'asc' ? '↑' : '↓' }}</span>
              </th>
              <th
                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:text-gray-700 select-none"
                @click="toggleSort('name')"
              >
                {{ t('name') }}
                <span v-if="sortField === 'name'" class="ml-1">{{ sortDir === 'asc' ? '↑' : '↓' }}</span>
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                {{ t('parent') }}
              </th>
              <th
                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:text-gray-700 select-none"
                @click="toggleSort('is_active')"
              >
                {{ $t('general.status') }}
                <span v-if="sortField === 'is_active'" class="ml-1">{{ sortDir === 'asc' ? '↑' : '↓' }}</span>
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                {{ $t('general.actions') }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr
              v-for="cc in paginatedCostCenters"
              :key="cc.id"
              class="hover:bg-gray-50 cursor-pointer"
              :class="{ 'bg-primary-50': selectedIds.includes(cc.id) }"
              @click="openEditForm(cc)"
            >
              <td class="whitespace-nowrap px-4 py-3" @click.stop>
                <input
                  type="checkbox"
                  class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                  :checked="selectedIds.includes(cc.id)"
                  @change="toggleSelect(cc.id)"
                />
              </td>
              <td class="whitespace-nowrap px-4 py-3">
                <span
                  class="inline-block h-4 w-4 rounded-full"
                  :style="{ backgroundColor: cc.color || '#6366f1' }"
                ></span>
              </td>
              <td class="whitespace-nowrap px-4 py-3 text-sm font-mono text-gray-600">
                {{ cc.code || '-' }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-900">
                {{ cc.name }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-500">
                {{ cc.full_path && cc.full_path !== cc.name ? cc.full_path : '-' }}
              </td>
              <td class="whitespace-nowrap px-4 py-3">
                <span
                  class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                  :class="cc.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'"
                >
                  {{ cc.is_active ? $t('general.active') : $t('general.inactive') }}
                </span>
              </td>
              <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                <button
                  class="text-red-600 hover:text-red-800 ml-2"
                  @click.stop="confirmDelete(cc)"
                >
                  <BaseIcon name="TrashIcon" class="h-4 w-4" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div
          v-if="filteredCostCenters.length > perPage"
          class="px-4 py-3 bg-gray-50 border-t border-gray-200 flex items-center justify-between text-sm"
        >
          <span class="text-gray-600">
            {{ t('showing') }} {{ paginationStart }}–{{ paginationEnd }} {{ t('of_total') }} {{ filteredCostCenters.length }}
          </span>
          <div class="flex items-center space-x-2">
            <select
              v-model="perPage"
              class="rounded border-gray-300 text-sm py-1 pr-8"
            >
              <option :value="10">10</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
            </select>
            <span class="text-gray-500">{{ t('per_page') }}</span>
            <button
              class="px-2 py-1 rounded border border-gray-300 text-gray-600 disabled:opacity-50"
              :disabled="currentPage <= 1"
              @click="currentPage--"
            >
              ←
            </button>
            <button
              class="px-2 py-1 rounded border border-gray-300 text-gray-600 disabled:opacity-50"
              :disabled="currentPage >= totalPages"
              @click="currentPage++"
            >
              →
            </button>
          </div>
        </div>

        <!-- No search results -->
        <div
          v-if="filteredCostCenters.length === 0 && searchQuery"
          class="px-6 py-8 text-center text-gray-500 text-sm"
        >
          <BaseIcon name="MagnifyingGlassIcon" class="mx-auto h-8 w-8 text-gray-300 mb-2" />
          {{ t('no_results') }}
        </div>
      </template>
    </div>

    <!-- Empty state -->
    <div
      v-else
      class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-white py-16"
    >
      <BaseIcon name="RectangleGroupIcon" class="h-12 w-12 text-gray-400" />
      <h3 class="mt-4 text-sm font-medium text-gray-900">
        {{ t('title') }}
      </h3>
      <p class="mt-1 text-sm text-gray-500 text-center max-w-sm">
        {{ t('empty_description') }}
      </p>
      <BaseButton variant="primary" class="mt-4" @click="openCreateForm">
        <template #left="slotProps">
          <BaseIcon :class="slotProps.class" name="PlusIcon" />
        </template>
        {{ t('title') }}
      </BaseButton>
    </div>

    <!-- Side Panel / Modal for Create/Edit -->
    <CostCenterForm
      v-if="showForm"
      :cost-center="editingCostCenter"
      :parent-id="defaultParentId"
      :cost-centers="costCenters"
      :is-saving="isSaving"
      @save="saveCostCenter"
      @close="closeForm"
    />

  </BasePage>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useI18n } from 'vue-i18n'
import CostCenterForm from './Form.vue'
import CostCenterTreeNode from './TreeNode.vue'
import ccMessages from '@/scripts/admin/i18n/cost-centers.js'

const route = useRoute()
const notificationStore = useNotificationStore()
const dialogStore = useDialogStore()
const { t: $t } = useI18n()

const currentRouteName = computed(() => route.name)

const locale = document.documentElement.lang || 'mk'

function t(key) {
  return ccMessages[locale]?.cost_centers?.[key]
    || ccMessages['en']?.cost_centers?.[key]
    || key
}

const tabs = computed(() => [
  { name: 'cost-centers.index', to: '/admin/cost-centers', label: t('title') },
  { name: 'cost-centers.rules', to: '/admin/cost-centers/rules', label: t('rules') },
  { name: 'cost-centers.summary', to: '/admin/cost-centers/summary', label: t('summary') },
])

// State
const costCenters = ref([])
const treeData = ref([])
const isLoading = ref(false)
const isSaving = ref(false)
const viewMode = ref('tree')
const showForm = ref(false)
const editingCostCenter = ref(null)
const defaultParentId = ref(null)
const deletingCostCenter = ref(null)

// Search, sort, pagination
const searchQuery = ref('')
const sortField = ref('')
const sortDir = ref('asc')
const currentPage = ref(1)
const perPage = ref(25)

// Bulk selection
const selectedIds = ref([])

watch(searchQuery, () => { currentPage.value = 1; selectedIds.value = [] })
watch(perPage, () => { currentPage.value = 1 })

// Build tree from flat list
function buildTree(items) {
  const map = {}
  const roots = []
  items.forEach(item => { map[item.id] = { ...item, children: [] } })
  items.forEach(item => {
    if (item.parent_id && map[item.parent_id]) {
      map[item.parent_id].children.push(map[item.id])
    } else {
      roots.push(map[item.id])
    }
  })
  return roots
}

// Computed: filter → sort → paginate
const filteredCostCenters = computed(() => {
  let list = costCenters.value
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(cc =>
      (cc.name || '').toLowerCase().includes(q) ||
      (cc.code || '').toLowerCase().includes(q)
    )
  }
  if (sortField.value) {
    const field = sortField.value
    const dir = sortDir.value === 'asc' ? 1 : -1
    list = [...list].sort((a, b) => {
      const va = a[field] ?? ''
      const vb = b[field] ?? ''
      if (typeof va === 'string') return va.localeCompare(vb) * dir
      if (typeof va === 'boolean') return ((va ? 1 : 0) - (vb ? 1 : 0)) * dir
      return (va - vb) * dir
    })
  }
  return list
})

const totalPages = computed(() => Math.max(1, Math.ceil(filteredCostCenters.value.length / perPage.value)))
const paginationStart = computed(() => (currentPage.value - 1) * perPage.value + 1)
const paginationEnd = computed(() => Math.min(currentPage.value * perPage.value, filteredCostCenters.value.length))

const paginatedCostCenters = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return filteredCostCenters.value.slice(start, start + perPage.value)
})

// Select all / indeterminate
const isAllSelected = computed(() =>
  paginatedCostCenters.value.length > 0 &&
  paginatedCostCenters.value.every(cc => selectedIds.value.includes(cc.id))
)
const isIndeterminate = computed(() =>
  !isAllSelected.value &&
  paginatedCostCenters.value.some(cc => selectedIds.value.includes(cc.id))
)

function toggleSelectAll() {
  if (isAllSelected.value) {
    const pageIds = paginatedCostCenters.value.map(cc => cc.id)
    selectedIds.value = selectedIds.value.filter(id => !pageIds.includes(id))
  } else {
    const pageIds = paginatedCostCenters.value.map(cc => cc.id)
    selectedIds.value = [...new Set([...selectedIds.value, ...pageIds])]
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

function toggleSort(field) {
  if (sortField.value === field) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortField.value = field
    sortDir.value = 'asc'
  }
}

// Lifecycle
onMounted(() => {
  loadCostCenters()
})

// Methods
async function loadCostCenters() {
  isLoading.value = true
  try {
    const response = await window.axios.get('/cost-centers')
    costCenters.value = response.data?.data || []
    treeData.value = buildTree(costCenters.value)
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('error_loading') || 'Failed to load data',
    })
  } finally {
    isLoading.value = false
  }
}

function openCreateForm() {
  editingCostCenter.value = null
  defaultParentId.value = null
  showForm.value = true
}

function openCreateChildForm(parent) {
  editingCostCenter.value = null
  defaultParentId.value = parent.id
  showForm.value = true
}

function openEditForm(cc) {
  editingCostCenter.value = { ...cc }
  defaultParentId.value = null
  showForm.value = true
}

function closeForm() {
  showForm.value = false
  editingCostCenter.value = null
  defaultParentId.value = null
}

async function saveCostCenter(formData) {
  isSaving.value = true
  try {
    if (editingCostCenter.value?.id) {
      await window.axios.put(`/cost-centers/${editingCostCenter.value.id}`, formData)
      notificationStore.showNotification({
        type: 'success',
        message: t('updated_success') || 'Updated successfully',
      })
    } else {
      await window.axios.post('/cost-centers', formData)
      notificationStore.showNotification({
        type: 'success',
        message: t('created_success') || 'Created successfully',
      })
    }
    closeForm()
    await loadCostCenters()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('error_saving') || 'Failed to save',
    })
  } finally {
    isSaving.value = false
  }
}

function confirmDelete(cc) {
  deletingCostCenter.value = cc
  dialogStore
    .openDialog({
      title: $t('general.are_you_sure'),
      message: `${$t('general.delete')} "${cc.name}"?`,
      yesLabel: $t('general.ok'),
      noLabel: $t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then(async (res) => {
      if (res) {
        try {
          await window.axios.delete(`/cost-centers/${cc.id}`)
          notificationStore.showNotification({
            type: 'success',
            message: t('deleted_success') || 'Deleted successfully',
          })
          deletingCostCenter.value = null
          await loadCostCenters()
        } catch (error) {
          notificationStore.showNotification({
            type: 'error',
            message: error.response?.data?.error || t('error_deleting') || 'Failed to delete',
          })
        }
      }
    })
}

async function bulkDelete() {
  const ids = [...selectedIds.value]
  if (!ids.length) return
  dialogStore
    .openDialog({
      title: $t('general.are_you_sure'),
      message: `${$t('general.delete')} ${ids.length} ${t('title').toLowerCase()}?`,
      yesLabel: $t('general.ok'),
      noLabel: $t('general.cancel'),
      variant: 'danger',
    })
    .then(async (res) => {
      if (res) {
        try {
          await Promise.all(ids.map(id => window.axios.delete(`/cost-centers/${id}`)))
          selectedIds.value = []
          notificationStore.showNotification({ type: 'success', message: t('deleted_success') })
          await loadCostCenters()
        } catch (error) {
          notificationStore.showNotification({
            type: 'error',
            message: error.response?.data?.error || t('error_deleting'),
          })
        }
      }
    })
}

async function bulkSetActive(active) {
  const ids = [...selectedIds.value]
  if (!ids.length) return
  try {
    await Promise.all(ids.map(id =>
      window.axios.put(`/cost-centers/${id}`, { is_active: active })
    ))
    selectedIds.value = []
    notificationStore.showNotification({
      type: 'success',
      message: active ? t('bulk_activate') : t('bulk_deactivate'),
    })
    await loadCostCenters()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('error_saving'),
    })
  }
}
</script>

<!-- CLAUDE-CHECKPOINT -->
