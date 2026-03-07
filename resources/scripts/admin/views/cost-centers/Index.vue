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
              <BaseIcon name="Squares2X2Icon" class="h-4 w-4" />
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

    <!-- Tree View -->
    <div v-else-if="costCenters.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
      <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-sm font-medium text-gray-700">
          {{ costCenters.length }} {{ t('title').toLowerCase() }}
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
      <table v-else class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              {{ t('color') }}
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              {{ t('code') }}
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              {{ t('name') }}
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              {{ t('parent') }}
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              {{ $t('general.status') }}
            </th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
              {{ $t('general.actions') }}
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
          <tr
            v-for="cc in costCenters"
            :key="cc.id"
            class="hover:bg-gray-50 cursor-pointer"
            @click="openEditForm(cc)"
          >
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

    <!-- Delete Confirmation -->
    <BaseConfirmDialog
      v-if="showDeleteConfirm"
      :title="$t('general.are_you_sure')"
      :message="deleteConfirmMessage"
      @confirm="deleteCostCenter"
      @cancel="showDeleteConfirm = false"
    />
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useNotificationStore } from '@/scripts/stores/notification'
import CostCenterForm from './Form.vue'
import CostCenterTreeNode from './TreeNode.vue'
import ccMessages from '@/scripts/admin/i18n/cost-centers.js'

const notificationStore = useNotificationStore()

const locale = document.documentElement.lang || 'mk'
const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }
const formattedLocale = localeMap[locale] || 'mk-MK'

function t(key) {
  return ccMessages[locale]?.cost_centers?.[key]
    || ccMessages['en']?.cost_centers?.[key]
    || key
}

// State
const costCenters = ref([])
const treeData = ref([])
const isLoading = ref(false)
const isSaving = ref(false)
const viewMode = ref('tree')
const showForm = ref(false)
const editingCostCenter = ref(null)
const defaultParentId = ref(null)
const showDeleteConfirm = ref(false)
const deletingCostCenter = ref(null)

// Computed
const deleteConfirmMessage = computed(() => {
  if (!deletingCostCenter.value) return ''
  return `Delete "${deletingCostCenter.value.name}"?`
})

// Lifecycle
onMounted(() => {
  loadCostCenters()
})

// Methods
async function loadCostCenters() {
  isLoading.value = true
  try {
    // Load flat list
    const flatResponse = await window.axios.get('/cost-centers')
    costCenters.value = flatResponse.data?.data || []

    // Load tree
    const treeResponse = await window.axios.get('/cost-centers', { params: { tree: 1 } })
    treeData.value = treeResponse.data?.data || []
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
  showDeleteConfirm.value = true
}

async function deleteCostCenter() {
  if (!deletingCostCenter.value) return

  try {
    await window.axios.delete(`/cost-centers/${deletingCostCenter.value.id}`)
    notificationStore.showNotification({
      type: 'success',
      message: t('deleted_success') || 'Deleted successfully',
    })
    showDeleteConfirm.value = false
    deletingCostCenter.value = null
    await loadCostCenters()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('error_deleting') || 'Failed to delete',
    })
  }
}
</script>

<!-- CLAUDE-CHECKPOINT -->
