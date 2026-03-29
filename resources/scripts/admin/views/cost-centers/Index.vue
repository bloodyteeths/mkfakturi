<template>
  <BasePage>
    <BasePageHeader :title="t('title')">
      <template #actions>
        <BaseButton variant="primary" @click="openCreateForm">
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="PlusIcon" />
          </template>
          {{ t('title') }}
        </BaseButton>
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

    <!-- Search -->
    <div v-if="costCenters.length > 0" class="mb-4">
      <div class="relative max-w-sm">
        <BaseIcon name="MagnifyingGlassIcon" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
        <input
          v-model="searchQuery"
          type="text"
          :placeholder="t('search_placeholder') || 'Пребарај...'"
          class="block w-full rounded-md border-gray-300 pl-10 pr-4 py-2 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
        />
      </div>
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

    <!-- Tree view -->
    <div v-else-if="filteredTree.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
      <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
        <h3 class="text-sm font-medium text-gray-700">
          {{ costCenters.length }} {{ t('title').toLowerCase() }}
        </h3>
      </div>
      <div class="divide-y divide-gray-100">
        <CostCenterTreeNode
          v-for="node in filteredTree"
          :key="node.id"
          :node="node"
          :depth="0"
          :search-query="searchQuery"
          @edit="openEditForm"
          @add-child="openCreateChildForm"
          @delete="confirmDelete"
        />
      </div>
    </div>

    <!-- No search results -->
    <div
      v-else-if="costCenters.length > 0 && searchQuery"
      class="bg-white rounded-lg shadow px-6 py-12 text-center"
    >
      <BaseIcon name="MagnifyingGlassIcon" class="mx-auto h-10 w-10 text-gray-300 mb-3" />
      <p class="text-sm text-gray-500">{{ t('no_results') || 'Нема резултати' }}</p>
    </div>

    <!-- Empty state -->
    <div
      v-else-if="!isLoading && costCenters.length === 0"
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

    <!-- Side Panel for Create/Edit -->
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
import { ref, computed, onMounted } from 'vue'
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
const isLoading = ref(false)
const isSaving = ref(false)
const showForm = ref(false)
const editingCostCenter = ref(null)
const defaultParentId = ref(null)
const searchQuery = ref('')

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

// Filter tree: keep nodes that match + their ancestors
function filterTree(nodes, query) {
  if (!query) return nodes
  const q = query.toLowerCase()

  function nodeMatches(node) {
    return (node.name || '').toLowerCase().includes(q) ||
      (node.code || '').toLowerCase().includes(q)
  }

  function filterNode(node) {
    const childResults = (node.children || []).map(filterNode).filter(Boolean)
    if (nodeMatches(node) || childResults.length > 0) {
      return { ...node, children: childResults }
    }
    return null
  }

  return nodes.map(filterNode).filter(Boolean)
}

const fullTree = computed(() => buildTree(costCenters.value))
const filteredTree = computed(() => filterTree(fullTree.value, searchQuery.value))

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
</script>

<!-- CLAUDE-CHECKPOINT -->
