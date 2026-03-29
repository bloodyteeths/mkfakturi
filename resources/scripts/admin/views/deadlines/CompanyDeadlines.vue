<template>
  <BasePage>
    <BasePageHeader :title="$t('deadlines.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('deadlines.title')" to="#" active />
      </BaseBreadcrumb>
      <template #actions>
        <BaseButton variant="primary" @click="showCreateModal = true">
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ $t('deadlines.add_deadline') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- KPI Cards — clickable to filter -->
    <div class="grid grid-cols-2 gap-4 mt-6 md:grid-cols-4">
      <button
        class="relative flex items-center p-4 bg-white border-l-4 rounded shadow text-left transition-all"
        :class="[
          activeFilter === 'overdue' ? 'border-red-600 ring-2 ring-red-200' : 'border-red-400 hover:border-red-600',
        ]"
        @click="toggleFilter('overdue')"
      >
        <BaseIcon name="ExclamationCircleIcon" class="h-7 w-7 text-red-500 flex-shrink-0" />
        <div class="ml-3">
          <p class="text-xs text-gray-500">{{ $t('deadlines.overdue') }}</p>
          <p class="text-xl font-bold text-red-600">{{ summary.overdue_count }}</p>
        </div>
      </button>

      <button
        class="relative flex items-center p-4 bg-white border-l-4 rounded shadow text-left transition-all"
        :class="[
          activeFilter === 'due_this_week' ? 'border-yellow-600 ring-2 ring-yellow-200' : 'border-yellow-400 hover:border-yellow-600',
        ]"
        @click="toggleFilter('due_this_week')"
      >
        <BaseIcon name="ClockIcon" class="h-7 w-7 text-yellow-500 flex-shrink-0" />
        <div class="ml-3">
          <p class="text-xs text-gray-500">{{ $t('deadlines.due_this_week') }}</p>
          <p class="text-xl font-bold text-yellow-600">{{ summary.due_this_week }}</p>
        </div>
      </button>

      <button
        class="relative flex items-center p-4 bg-white border-l-4 rounded shadow text-left transition-all"
        :class="[
          activeFilter === 'due_this_month' ? 'border-blue-600 ring-2 ring-blue-200' : 'border-blue-400 hover:border-blue-600',
        ]"
        @click="toggleFilter('due_this_month')"
      >
        <BaseIcon name="CalendarIcon" class="h-7 w-7 text-blue-500 flex-shrink-0" />
        <div class="ml-3">
          <p class="text-xs text-gray-500">{{ $t('deadlines.due_this_month') }}</p>
          <p class="text-xl font-bold text-blue-600">{{ summary.due_this_month }}</p>
        </div>
      </button>

      <button
        class="relative flex items-center p-4 bg-white border-l-4 rounded shadow text-left transition-all"
        :class="[
          activeFilter === 'completed' ? 'border-green-600 ring-2 ring-green-200' : 'border-green-400 hover:border-green-600',
        ]"
        @click="toggleFilter('completed')"
      >
        <BaseIcon name="CheckCircleIcon" class="h-7 w-7 text-green-500 flex-shrink-0" />
        <div class="ml-3">
          <p class="text-xs text-gray-500">{{ $t('deadlines.completed_this_month') }}</p>
          <p class="text-xl font-bold text-green-600">{{ summary.completed_this_month }}</p>
        </div>
      </button>
    </div>

    <!-- Filter bar — compact, only shows when actively filtering -->
    <div
      v-if="activeFilter || filters.type"
      class="mt-4 flex items-center gap-3 bg-blue-50 border border-blue-200 rounded-lg px-4 py-2"
    >
      <BaseIcon name="FunnelIcon" class="h-4 w-4 text-blue-500" />
      <span class="text-sm text-blue-700">
        {{ activeFilterLabel }}
      </span>
      <div v-if="!activeFilter" class="flex items-center gap-2">
        <select
          v-model="filters.type"
          class="text-sm px-2 py-1 border border-blue-300 rounded bg-white"
          @change="loadDeadlines(1)"
        >
          <option value="">{{ $t('general.all') }}</option>
          <option value="vat_return">{{ $t('deadlines.type_vat_return') }}</option>
          <option value="mpin">{{ $t('deadlines.type_mpin') }}</option>
          <option value="cit_advance">{{ $t('deadlines.type_cit_advance') }}</option>
          <option value="annual_fs">{{ $t('deadlines.type_annual_fs') }}</option>
          <option value="custom">{{ $t('deadlines.type_custom') }}</option>
        </select>
      </div>
      <button class="ml-auto text-sm text-blue-600 hover:text-blue-800 underline" @click="clearAllFilters">
        {{ $t('general.clear_all') }}
      </button>
    </div>

    <!-- Type filter toggle (shown when no KPI filter active) -->
    <div
      v-if="!activeFilter && !filters.type"
      class="mt-4 flex items-center gap-2"
    >
      <span class="text-xs text-gray-400 mr-1">{{ $t('general.type') }}:</span>
      <button
        v-for="typeOpt in typeOptions"
        :key="typeOpt.value"
        class="px-2.5 py-1 text-xs rounded-full border transition-all"
        :class="filters.type === typeOpt.value
          ? 'bg-primary-100 border-primary-300 text-primary-700'
          : 'bg-white border-gray-200 text-gray-500 hover:border-gray-400'"
        @click="setTypeFilter(typeOpt.value)"
      >
        {{ typeOpt.label }}
      </button>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <BaseContentPlaceholders>
        <BaseContentPlaceholdersBox :rounded="true" class="w-full" style="height: 300px" />
      </BaseContentPlaceholders>
    </div>

    <!-- Empty State -->
    <BaseEmptyPlaceholder
      v-show="!isLoading && deadlines.length === 0"
      :title="$t('deadlines.no_deadlines')"
      :description="activeFilter ? $t('deadlines.no_deadlines_filter') : $t('deadlines.no_deadlines')"
    >
      <template #actions>
        <BaseButton v-if="activeFilter || filters.type" variant="primary-outline" @click="clearAllFilters">
          {{ $t('general.clear_all') }}
        </BaseButton>
        <BaseButton v-else variant="primary-outline" @click="loadDeadlines(1)">
          <template #left="slotProps">
            <BaseIcon name="ArrowPathIcon" :class="slotProps.class" />
          </template>
          {{ $t('general.refresh') }}
        </BaseButton>
      </template>
    </BaseEmptyPlaceholder>

    <!-- Deadline Cards — grouped, clean layout -->
    <div v-if="!isLoading && deadlines.length > 0" class="mt-4 space-y-2">
      <div
        v-for="deadline in deadlines"
        :key="deadline.id"
        class="bg-white rounded-lg shadow-sm border px-5 py-4 flex items-center gap-4 transition-all hover:shadow-md"
        :class="getRowBorderClass(deadline)"
      >
        <!-- Status indicator dot -->
        <div class="flex-shrink-0">
          <div
            class="w-3 h-3 rounded-full"
            :class="getStatusDotClass(deadline.status)"
          ></div>
        </div>

        <!-- Main content -->
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2">
            <h3 class="text-sm font-semibold text-gray-900 truncate">
              {{ locale === 'mk' ? (deadline.title_mk || deadline.title) : deadline.title }}
            </h3>
            <span
              :class="getTypeBadgeClass(deadline.deadline_type)"
              class="px-2 py-0.5 text-[11px] font-medium rounded-full flex-shrink-0"
            >
              {{ getTypeLabel(deadline.deadline_type) }}
            </span>
          </div>
          <p v-if="deadline.description" class="text-xs text-gray-400 truncate mt-0.5">
            {{ locale === 'mk' ? getDescriptionMk(deadline) : deadline.description }}
          </p>
        </div>

        <!-- Due date + days -->
        <div class="flex-shrink-0 text-right w-32">
          <p class="text-sm font-medium text-gray-800">{{ formatDate(deadline.due_date) }}</p>
          <p
            :class="getDaysClass(deadline.days_remaining, deadline.status)"
            class="text-xs mt-0.5"
          >
            {{ getDaysLabel(deadline.days_remaining, deadline.status) }}
          </p>
        </div>

        <!-- Actions -->
        <div class="flex-shrink-0 flex items-center gap-1.5">
          <BaseButton
            v-if="deadline.status !== 'completed'"
            variant="primary"
            size="sm"
            :disabled="completingId === deadline.id"
            @click="completeDeadline(deadline)"
          >
            <template #left="slotProps">
              <BaseIcon name="CheckIcon" :class="slotProps.class" />
            </template>
            {{ completingId === deadline.id ? '...' : $t('deadlines.mark_complete') }}
          </BaseButton>
          <span v-else class="text-xs text-green-600 font-medium px-2">
            <BaseIcon name="CheckCircleIcon" class="h-4 w-4 inline -mt-0.5" />
            {{ $t('deadlines.completed') }}
          </span>

          <BaseButton
            v-if="!deadline.is_recurring && deadline.status !== 'completed'"
            variant="primary-outline"
            size="sm"
            @click="openEditModal(deadline)"
          >
            <BaseIcon name="PencilIcon" class="h-3.5 w-3.5" />
          </BaseButton>
          <BaseButton
            v-if="!deadline.is_recurring"
            variant="danger"
            size="sm"
            @click="confirmDelete(deadline)"
          >
            <BaseIcon name="TrashIcon" class="h-3.5 w-3.5" />
          </BaseButton>
        </div>
      </div>

      <!-- Pagination -->
      <div
        v-if="pagination.lastPage > 1"
        class="flex items-center justify-between pt-3 px-1"
      >
        <p class="text-xs text-gray-400">
          {{ pagination.from }}–{{ pagination.to }} / {{ pagination.total }}
        </p>
        <div class="flex items-center gap-2">
          <BaseButton
            variant="primary-outline"
            size="sm"
            :disabled="pagination.currentPage <= 1"
            @click="loadDeadlines(pagination.currentPage - 1)"
          >
            {{ $t('general.previous') }}
          </BaseButton>
          <span class="text-xs text-gray-500">{{ pagination.currentPage }}/{{ pagination.lastPage }}</span>
          <BaseButton
            variant="primary-outline"
            size="sm"
            :disabled="pagination.currentPage >= pagination.lastPage"
            @click="loadDeadlines(pagination.currentPage + 1)"
          >
            {{ $t('general.next') }}
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Create Modal — simplified, single title field -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
      <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeCreateModal"></div>
        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full">
          <div class="px-6 pt-5 pb-4">
            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('deadlines.add_deadline') }}</h3>
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('deadlines.deadline_name') }} *</label>
                <input
                  v-model="createForm.title"
                  type="text"
                  maxlength="255"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"
                  :placeholder="locale === 'mk' ? 'пр. Квартален извештај за ДЗС' : 'e.g. Quarterly statistical report'"
                />
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('general.type') }}</label>
                  <select
                    v-model="createForm.deadline_type"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"
                  >
                    <option value="custom">{{ $t('deadlines.type_custom') }}</option>
                    <option value="vat_return">{{ $t('deadlines.type_vat_return') }}</option>
                    <option value="mpin">{{ $t('deadlines.type_mpin') }}</option>
                    <option value="cit_advance">{{ $t('deadlines.type_cit_advance') }}</option>
                    <option value="annual_fs">{{ $t('deadlines.type_annual_fs') }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('deadlines.due_date') }} *</label>
                  <input
                    v-model="createForm.due_date"
                    type="date"
                    :min="todayString"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"
                  />
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  {{ $t('general.description') }}
                  <span class="text-gray-400 font-normal">({{ $t('general.optional') }})</span>
                </label>
                <textarea
                  v-model="createForm.description"
                  rows="2"
                  maxlength="1000"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"
                ></textarea>
              </div>

              <div v-if="createError" class="bg-red-50 border border-red-200 rounded-md p-3">
                <p class="text-sm text-red-700">{{ createError }}</p>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 px-6 py-3 flex justify-end space-x-3 rounded-b-lg">
            <BaseButton variant="primary-outline" :disabled="isCreating" @click="closeCreateModal">
              {{ $t('general.cancel') }}
            </BaseButton>
            <BaseButton
              variant="primary"
              :disabled="!createForm.title || !createForm.due_date || isCreating"
              @click="submitCreate"
            >
              {{ isCreating ? '...' : $t('general.save') }}
            </BaseButton>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <div v-if="showEditModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
      <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeEditModal"></div>
        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full">
          <div class="px-6 pt-5 pb-4">
            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('deadlines.edit_deadline') }}</h3>
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('deadlines.deadline_name') }} *</label>
                <input
                  v-model="editForm.title"
                  type="text"
                  maxlength="255"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('deadlines.due_date') }} *</label>
                <input
                  v-model="editForm.due_date"
                  type="date"
                  :min="todayString"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  {{ $t('general.description') }}
                  <span class="text-gray-400 font-normal">({{ $t('general.optional') }})</span>
                </label>
                <textarea
                  v-model="editForm.description"
                  rows="2"
                  maxlength="1000"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"
                ></textarea>
              </div>
              <div v-if="editError" class="bg-red-50 border border-red-200 rounded-md p-3">
                <p class="text-sm text-red-700">{{ editError }}</p>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 px-6 py-3 flex justify-end space-x-3 rounded-b-lg">
            <BaseButton variant="primary-outline" :disabled="isEditing" @click="closeEditModal">
              {{ $t('general.cancel') }}
            </BaseButton>
            <BaseButton variant="primary" :disabled="!editForm.title || !editForm.due_date || isEditing" @click="submitEdit">
              {{ isEditing ? '...' : $t('general.save') }}
            </BaseButton>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
      <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showDeleteModal = false"></div>
        <div class="relative bg-white rounded-lg shadow-xl max-w-sm w-full">
          <div class="px-6 pt-5 pb-4">
            <div class="flex items-center gap-3 mb-3">
              <div class="flex-shrink-0 flex items-center justify-center h-10 w-10 rounded-full bg-red-100">
                <BaseIcon name="ExclamationTriangleIcon" class="h-5 w-5 text-red-600" />
              </div>
              <h3 class="text-lg font-medium text-gray-900">{{ $t('deadlines.confirm_delete') }}</h3>
            </div>
            <p class="text-sm text-gray-500">{{ deleteTarget?.title_mk || deleteTarget?.title }}</p>
          </div>
          <div class="bg-gray-50 px-6 py-3 flex justify-end space-x-3 rounded-b-lg">
            <BaseButton variant="primary-outline" @click="showDeleteModal = false">{{ $t('general.cancel') }}</BaseButton>
            <BaseButton variant="danger" :disabled="isDeleting" @click="executeDelete">
              {{ isDeleting ? '...' : $t('general.delete') }}
            </BaseButton>
          </div>
        </div>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'

const { t, locale } = useI18n()
const notificationStore = useNotificationStore()

// State
const deadlines = ref([])
const isLoading = ref(true)
const completingId = ref(null)

// Active KPI filter (mutually exclusive with dropdown filters)
const activeFilter = ref('')

// Dropdown filters
const filters = reactive({ type: '', status: '' })

// Pagination
const pagination = reactive({ currentPage: 1, lastPage: 1, total: 0, from: 0, to: 0 })

// Create modal
const showCreateModal = ref(false)
const isCreating = ref(false)
const createError = ref(null)
const createForm = reactive({ title: '', deadline_type: 'custom', due_date: '', description: '' })

// Edit modal
const showEditModal = ref(false)
const isEditing = ref(false)
const editError = ref(null)
const editingId = ref(null)
const editForm = reactive({ title: '', due_date: '', description: '' })

// Delete modal
const showDeleteModal = ref(false)
const isDeleting = ref(false)
const deleteTarget = ref(null)

const todayString = computed(() => {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
})

// KPI Summary
const summary = reactive({ overdue_count: 0, due_this_week: 0, due_this_month: 0, completed_this_month: 0 })

// Type filter options as pill buttons
const typeOptions = computed(() => [
  { value: 'vat_return', label: t('deadlines.type_vat_return') },
  { value: 'mpin', label: t('deadlines.type_mpin') },
  { value: 'cit_advance', label: t('deadlines.type_cit_advance') },
  { value: 'annual_fs', label: t('deadlines.type_annual_fs') },
  { value: 'custom', label: t('deadlines.type_custom') },
])

const activeFilterLabel = computed(() => {
  if (activeFilter.value === 'overdue') return t('deadlines.overdue')
  if (activeFilter.value === 'due_this_week') return t('deadlines.due_this_week')
  if (activeFilter.value === 'due_this_month') return t('deadlines.due_this_month')
  if (activeFilter.value === 'completed') return t('deadlines.completed')
  if (filters.type) return t(`deadlines.type_${filters.type}`)
  return ''
})

// ── Data Loading ──

const loadSummary = async () => {
  try {
    const { data } = await window.axios.get('/deadlines/summary')
    Object.assign(summary, data)
  } catch {
    // Non-critical
  }
}

const loadDeadlines = async (page = 1) => {
  isLoading.value = true
  try {
    const params = { page, per_page: 25 }

    // KPI filters map to status param (except due_this_week/month which we handle client-side)
    if (activeFilter.value === 'overdue') {
      params.status = 'overdue'
    } else if (activeFilter.value === 'completed') {
      params.status = 'completed'
    } else if (!activeFilter.value) {
      // No KPI filter — use dropdown filters
      if (filters.type) params.type = filters.type
      if (filters.status) params.status = filters.status
    }
    // For due_this_week / due_this_month we load all non-completed and filter client-side

    const { data } = await window.axios.get('/deadlines', { params })
    let items = data.data || []

    // Client-side date filtering for week/month KPI cards
    if (activeFilter.value === 'due_this_week' || activeFilter.value === 'due_this_month') {
      const now = new Date()
      const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
      const endDate = activeFilter.value === 'due_this_week'
        ? new Date(today.getTime() + (7 - today.getDay()) * 86400000)
        : new Date(today.getFullYear(), today.getMonth() + 1, 0)

      items = items.filter((d) => {
        if (d.status === 'completed') return false
        const due = new Date(d.due_date)
        return due >= today && due <= endDate
      })
    }

    deadlines.value = items
    pagination.currentPage = data.current_page ?? 1
    pagination.lastPage = data.last_page ?? 1
    pagination.total = data.total ?? items.length
    pagination.from = data.from ?? 1
    pagination.to = data.to ?? items.length
  } catch (err) {
    notificationStore.showNotification({
      type: 'error',
      message: err?.response?.data?.error || t('deadlines.no_deadlines'),
    })
  } finally {
    isLoading.value = false
  }
}

// ── Filter Actions ──

const toggleFilter = (filter) => {
  if (activeFilter.value === filter) {
    activeFilter.value = ''
  } else {
    activeFilter.value = filter
    filters.type = ''
    filters.status = ''
  }
  loadDeadlines(1)
}

const setTypeFilter = (type) => {
  filters.type = filters.type === type ? '' : type
  activeFilter.value = ''
  loadDeadlines(1)
}

const clearAllFilters = () => {
  activeFilter.value = ''
  filters.type = ''
  filters.status = ''
  loadDeadlines(1)
}

// ── Actions ──

const completeDeadline = async (deadline) => {
  if (completingId.value) return
  completingId.value = deadline.id
  try {
    const { data } = await window.axios.post(`/deadlines/${deadline.id}/complete`)
    const index = deadlines.value.findIndex((d) => d.id === deadline.id)
    if (index !== -1) deadlines.value[index] = data.data
    notificationStore.showNotification({ type: 'success', message: t('deadlines.deadline_completed') })
    loadSummary()
  } catch (err) {
    notificationStore.showNotification({ type: 'error', message: err?.response?.data?.error || 'Error' })
  } finally {
    completingId.value = null
  }
}

const submitCreate = async () => {
  isCreating.value = true
  createError.value = null
  try {
    // Send title as both EN and MK — user writes in their language
    await window.axios.post('/deadlines', {
      title: createForm.title,
      title_mk: createForm.title,
      deadline_type: createForm.deadline_type,
      due_date: createForm.due_date,
      description: createForm.description || null,
    })
    notificationStore.showNotification({ type: 'success', message: t('deadlines.deadline_created') })
    closeCreateModal()
    loadDeadlines(1)
    loadSummary()
  } catch (err) {
    const errors = err?.response?.data?.errors
    if (errors) {
      const firstError = Object.values(errors)[0]
      createError.value = Array.isArray(firstError) ? firstError[0] : firstError
    } else {
      createError.value = err?.response?.data?.error || 'Failed to create deadline.'
    }
  } finally {
    isCreating.value = false
  }
}

const closeCreateModal = () => {
  showCreateModal.value = false
  Object.assign(createForm, { title: '', deadline_type: 'custom', due_date: '', description: '' })
  createError.value = null
}

const openEditModal = (deadline) => {
  editingId.value = deadline.id
  editForm.title = deadline.title_mk || deadline.title
  editForm.due_date = deadline.due_date?.split('T')[0] || ''
  editForm.description = deadline.description || ''
  editError.value = null
  showEditModal.value = true
}

const closeEditModal = () => {
  showEditModal.value = false
  editingId.value = null
  editError.value = null
}

const submitEdit = async () => {
  if (!editingId.value) return
  isEditing.value = true
  editError.value = null
  try {
    const { data } = await window.axios.patch(`/deadlines/${editingId.value}`, {
      title: editForm.title,
      title_mk: editForm.title,
      due_date: editForm.due_date,
      description: editForm.description || null,
    })
    const index = deadlines.value.findIndex((d) => d.id === editingId.value)
    if (index !== -1) deadlines.value[index] = data.data
    notificationStore.showNotification({ type: 'success', message: t('deadlines.deadline_updated') })
    closeEditModal()
    loadSummary()
  } catch (err) {
    const errors = err?.response?.data?.errors
    if (errors) {
      const firstError = Object.values(errors)[0]
      editError.value = Array.isArray(firstError) ? firstError[0] : firstError
    } else {
      editError.value = err?.response?.data?.error || 'Failed to update deadline.'
    }
  } finally {
    isEditing.value = false
  }
}

const confirmDelete = (deadline) => {
  deleteTarget.value = deadline
  showDeleteModal.value = true
}

const executeDelete = async () => {
  if (!deleteTarget.value) return
  isDeleting.value = true
  try {
    await window.axios.delete(`/deadlines/${deleteTarget.value.id}`)
    deadlines.value = deadlines.value.filter((d) => d.id !== deleteTarget.value.id)
    notificationStore.showNotification({ type: 'success', message: t('deadlines.deadline_deleted') })
    showDeleteModal.value = false
    deleteTarget.value = null
    loadSummary()
  } catch (err) {
    notificationStore.showNotification({ type: 'error', message: err?.response?.data?.error || 'Failed to delete.' })
  } finally {
    isDeleting.value = false
  }
}

// ── Display Helpers ──

const getTypeLabel = (type) => t(`deadlines.type_${type}`) || type

const getTypeBadgeClass = (type) => ({
  vat_return: 'bg-purple-100 text-purple-700',
  mpin: 'bg-indigo-100 text-indigo-700',
  cit_advance: 'bg-teal-100 text-teal-700',
  annual_fs: 'bg-amber-100 text-amber-700',
  custom: 'bg-gray-100 text-gray-600',
})[type] || 'bg-gray-100 text-gray-600'

const getStatusDotClass = (status) => ({
  upcoming: 'bg-green-400',
  due_today: 'bg-yellow-400 animate-pulse',
  overdue: 'bg-red-500 animate-pulse',
  completed: 'bg-gray-300',
})[status] || 'bg-gray-300'

const getRowBorderClass = (deadline) => {
  if (deadline.status === 'overdue') return 'border-l-4 border-l-red-400'
  if (deadline.status === 'due_today') return 'border-l-4 border-l-yellow-400'
  if (deadline.status === 'completed') return 'border-l-4 border-l-gray-200 opacity-60'
  return 'border-l-4 border-l-green-300'
}

const getDaysClass = (daysRemaining, status) => {
  if (status === 'completed') return 'text-gray-400'
  if (daysRemaining < 0) return 'text-red-600 font-semibold'
  if (daysRemaining === 0) return 'text-yellow-600 font-semibold'
  if (daysRemaining <= 3) return 'text-orange-500 font-medium'
  return 'text-gray-500'
}

const getDaysLabel = (daysRemaining, status) => {
  if (status === 'completed') return t('deadlines.completed')
  if (daysRemaining < 0) return `${Math.abs(daysRemaining)} ${t('deadlines.days_overdue')}`
  if (daysRemaining === 0) return t('deadlines.due_today')
  return `${daysRemaining} ${t('deadlines.days_left')}`
}

const getDescriptionMk = (deadline) => {
  // Return MK description if available, otherwise keep original
  return deadline.description
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  try {
    return new Date(dateString).toLocaleDateString(locale.value === 'mk' ? 'mk-MK' : undefined, {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
    })
  } catch {
    return dateString
  }
}

// ── Lifecycle ──
// Default: show upcoming deadlines, not the full history
onMounted(() => {
  loadSummary()
  // Start with no filter — shows all sorted by due_date ASC
  loadDeadlines(1)
})
</script>
