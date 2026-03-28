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

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-1 gap-6 mt-6 md:grid-cols-4">
      <!-- Overdue -->
      <div class="relative flex items-center p-4 bg-white border-l-4 border-red-500 rounded shadow">
        <div class="flex-shrink-0">
          <BaseIcon name="ExclamationCircleIcon" class="h-8 w-8 text-red-500" />
        </div>
        <div class="ml-4">
          <p class="text-sm font-medium text-gray-500">{{ $t('deadlines.overdue') }}</p>
          <p class="text-2xl font-bold text-red-600">{{ summary.overdue_count }}</p>
        </div>
      </div>

      <!-- Due This Week -->
      <div class="relative flex items-center p-4 bg-white border-l-4 border-yellow-500 rounded shadow">
        <div class="flex-shrink-0">
          <BaseIcon name="ClockIcon" class="h-8 w-8 text-yellow-500" />
        </div>
        <div class="ml-4">
          <p class="text-sm font-medium text-gray-500">{{ $t('deadlines.due_this_week') }}</p>
          <p class="text-2xl font-bold text-yellow-600">{{ summary.due_this_week }}</p>
        </div>
      </div>

      <!-- Due This Month -->
      <div class="relative flex items-center p-4 bg-white border-l-4 border-blue-500 rounded shadow">
        <div class="flex-shrink-0">
          <BaseIcon name="CalendarIcon" class="h-8 w-8 text-blue-500" />
        </div>
        <div class="ml-4">
          <p class="text-sm font-medium text-gray-500">{{ $t('deadlines.due_this_month') }}</p>
          <p class="text-2xl font-bold text-blue-600">{{ summary.due_this_month }}</p>
        </div>
      </div>

      <!-- Completed This Month -->
      <div class="relative flex items-center p-4 bg-white border-l-4 border-green-500 rounded shadow">
        <div class="flex-shrink-0">
          <BaseIcon name="CheckCircleIcon" class="h-8 w-8 text-green-500" />
        </div>
        <div class="ml-4">
          <p class="text-sm font-medium text-gray-500">{{ $t('deadlines.completed_this_month') }}</p>
          <p class="text-2xl font-bold text-green-600">{{ summary.completed_this_month }}</p>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="mt-6 flex flex-wrap items-end gap-4 bg-white p-4 rounded shadow">
      <div class="w-48">
        <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('general.type') }}</label>
        <select
          v-model="filters.type"
          class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"
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
      <div class="w-44">
        <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('deadlines.status') }}</label>
        <select
          v-model="filters.status"
          class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"
          @change="loadDeadlines(1)"
        >
          <option value="">{{ $t('general.all') }}</option>
          <option value="upcoming">{{ $t('deadlines.upcoming') }}</option>
          <option value="due_today">{{ $t('deadlines.due_today') }}</option>
          <option value="overdue">{{ $t('deadlines.overdue') }}</option>
          <option value="completed">{{ $t('deadlines.completed') }}</option>
        </select>
      </div>
      <BaseButton
        v-if="filters.type || filters.status"
        variant="primary-outline"
        size="sm"
        @click="clearFilters"
      >
        {{ $t('general.clear_all') }}
      </BaseButton>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <BaseContentPlaceholders>
        <BaseContentPlaceholdersBox
          :rounded="true"
          class="w-full"
          style="height: 300px"
        />
      </BaseContentPlaceholders>
    </div>

    <!-- Empty State -->
    <BaseEmptyPlaceholder
      v-show="!isLoading && deadlines.length === 0"
      :title="$t('deadlines.no_deadlines')"
      :description="$t('deadlines.no_deadlines')"
    >
      <template #actions>
        <BaseButton variant="primary-outline" @click="loadDeadlines(1)">
          <template #left="slotProps">
            <BaseIcon name="ArrowPathIcon" :class="slotProps.class" />
          </template>
          {{ $t('general.refresh') }}
        </BaseButton>
      </template>
    </BaseEmptyPlaceholder>

    <!-- Deadlines Table -->
    <div v-if="!isLoading && deadlines.length > 0" class="mt-6">
      <BaseTable
        ref="table"
        :data="deadlines"
        :columns="deadlineColumns"
        class="mt-3"
      >
        <template #cell-title="{ row }">
          <div class="flex items-center gap-2">
            <div class="font-medium text-gray-900">
              {{ locale === 'mk' ? (row.data.title_mk || row.data.title) : row.data.title }}
            </div>
            <span
              v-if="row.data.is_recurring"
              class="px-1.5 py-0.5 text-[10px] font-medium rounded bg-blue-50 text-blue-600"
              :title="$t('deadlines.system_recurring')"
            >
              {{ $t('deadlines.recurring') }}
            </span>
            <span
              v-else
              class="px-1.5 py-0.5 text-[10px] font-medium rounded bg-gray-50 text-gray-500"
            >
              {{ $t('deadlines.type_custom') }}
            </span>
          </div>
          <div v-if="row.data.description" class="text-xs text-gray-500 truncate max-w-xs">
            {{ row.data.description }}
          </div>
        </template>

        <template #cell-deadline_type="{ row }">
          <span
            :class="getTypeBadgeClass(row.data.deadline_type)"
            class="px-2 py-1 text-xs font-medium rounded-full"
          >
            {{ getTypeLabel(row.data.deadline_type) }}
          </span>
        </template>

        <template #cell-due_date="{ row }">
          <div class="text-sm text-gray-900">
            {{ formatDate(row.data.due_date) }}
          </div>
          <div
            :class="getDaysClass(row.data.days_remaining, row.data.status)"
            class="text-xs mt-0.5"
          >
            {{ getDaysLabel(row.data.days_remaining, row.data.status) }}
          </div>
        </template>

        <template #cell-status="{ row }">
          <span
            :class="getStatusBadgeClass(row.data.status)"
            class="px-2 py-1 text-xs font-medium rounded-full"
          >
            {{ getStatusLabel(row.data.status) }}
          </span>
        </template>

        <template #cell-completed_by="{ row }">
          <span v-if="row.data.completed_by" class="text-sm text-gray-600">
            {{ row.data.completed_by.name }}
          </span>
          <span v-else class="text-sm text-gray-400">—</span>
        </template>

        <template #cell-actions="{ row }">
          <div class="flex items-center gap-2">
            <BaseButton
              v-if="row.data.status !== 'completed'"
              variant="primary-outline"
              size="sm"
              :disabled="completingId === row.data.id"
              @click="completeDeadline(row.data)"
            >
              <template #left="slotProps">
                <BaseIcon name="CheckIcon" :class="slotProps.class" />
              </template>
              {{ completingId === row.data.id ? '...' : $t('deadlines.mark_complete') }}
            </BaseButton>
            <span
              v-else
              class="text-sm text-gray-400 italic"
            >
              {{ $t('deadlines.completed') }}
            </span>
            <!-- Edit button for custom deadlines -->
            <BaseButton
              v-if="!row.data.is_recurring && row.data.status !== 'completed'"
              variant="primary-outline"
              size="sm"
              @click="openEditModal(row.data)"
            >
              <template #left="slotProps">
                <BaseIcon name="PencilIcon" :class="slotProps.class" />
              </template>
            </BaseButton>
            <BaseButton
              v-if="!row.data.is_recurring"
              variant="danger"
              size="sm"
              @click="confirmDelete(row.data)"
            >
              <template #left="slotProps">
                <BaseIcon name="TrashIcon" :class="slotProps.class" />
              </template>
            </BaseButton>
          </div>
        </template>
      </BaseTable>

      <!-- Pagination -->
      <div
        v-if="pagination.lastPage > 1"
        class="flex items-center justify-between mt-4 px-2"
      >
        <p class="text-sm text-gray-600">
          {{ $t('general.showing') }} {{ pagination.from }}–{{ pagination.to }}
          {{ $t('general.of') }} {{ pagination.total }}
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
          <span class="text-sm text-gray-600">
            {{ pagination.currentPage }} / {{ pagination.lastPage }}
          </span>
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

    <!-- Create Deadline Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
      <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeCreateModal"></div>
        <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full">
          <div class="px-6 pt-5 pb-4">
            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('deadlines.add_deadline') }}</h3>

            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('deadlines.title') }} (EN) *</label>
                <input
                  v-model="createForm.title"
                  type="text"
                  maxlength="255"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"
                  :placeholder="$t('deadlines.title')"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('deadlines.title') }} (MK)</label>
                <input
                  v-model="createForm.title_mk"
                  type="text"
                  maxlength="255"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"
                  placeholder="Наслов на македонски"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('general.type') }}</label>
                <select
                  v-model="createForm.deadline_type"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"
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

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('general.description') }}</label>
                <textarea
                  v-model="createForm.description"
                  rows="3"
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

    <!-- Edit Deadline Modal -->
    <div v-if="showEditModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
      <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeEditModal"></div>
        <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full">
          <div class="px-6 pt-5 pb-4">
            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('deadlines.edit_deadline') }}</h3>

            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('deadlines.title') }} (EN) *</label>
                <input
                  v-model="editForm.title"
                  type="text"
                  maxlength="255"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('deadlines.title') }} (MK)</label>
                <input
                  v-model="editForm.title_mk"
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
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('general.description') }}</label>
                <textarea
                  v-model="editForm.description"
                  rows="3"
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
            <BaseButton
              variant="primary"
              :disabled="!editForm.title || !editForm.due_date || isEditing"
              @click="submitEdit"
            >
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
            <p class="text-sm text-gray-500">
              {{ deleteTarget?.title_mk || deleteTarget?.title }}
            </p>
          </div>
          <div class="bg-gray-50 px-6 py-3 flex justify-end space-x-3 rounded-b-lg">
            <BaseButton variant="primary-outline" @click="showDeleteModal = false">
              {{ $t('general.cancel') }}
            </BaseButton>
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

// Filters
const filters = reactive({
  type: '',
  status: '',
})

// Pagination
const pagination = reactive({
  currentPage: 1,
  lastPage: 1,
  total: 0,
  from: 0,
  to: 0,
})

// Create modal state
const showCreateModal = ref(false)
const isCreating = ref(false)
const createError = ref(null)
const createForm = reactive({
  title: '',
  title_mk: '',
  deadline_type: 'custom',
  due_date: '',
  description: '',
})

// Edit modal state
const showEditModal = ref(false)
const isEditing = ref(false)
const editError = ref(null)
const editingId = ref(null)
const editForm = reactive({
  title: '',
  title_mk: '',
  due_date: '',
  description: '',
})

// Delete modal state
const showDeleteModal = ref(false)
const isDeleting = ref(false)
const deleteTarget = ref(null)

// Fix: use local date parts instead of toISOString() which is UTC
const todayString = computed(() => {
  const d = new Date()
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
})

// KPI Summary — loaded from dedicated API
const summary = reactive({
  overdue_count: 0,
  due_this_week: 0,
  due_this_month: 0,
  completed_this_month: 0,
})

// Table column definitions
const deadlineColumns = computed(() => [
  {
    key: 'title',
    label: t('deadlines.title'),
    thClass: 'min-w-[250px]',
  },
  {
    key: 'deadline_type',
    label: t('general.type'),
    thClass: 'w-36',
  },
  {
    key: 'due_date',
    label: t('deadlines.due_date'),
    thClass: 'w-44',
  },
  {
    key: 'status',
    label: t('deadlines.status'),
    thClass: 'w-32',
  },
  {
    key: 'completed_by',
    label: t('deadlines.completed_by'),
    thClass: 'w-36',
  },
  {
    key: 'actions',
    label: '',
    thClass: 'w-52',
  },
])

/**
 * Load KPI summary from dedicated server-side endpoint.
 */
const loadSummary = async () => {
  try {
    const { data } = await window.axios.get('/deadlines/summary')
    summary.overdue_count = data.overdue_count ?? 0
    summary.due_this_week = data.due_this_week ?? 0
    summary.due_this_month = data.due_this_month ?? 0
    summary.completed_this_month = data.completed_this_month ?? 0
  } catch {
    // Silently fail — KPI is non-critical
  }
}

/**
 * Load deadlines from the company API endpoint.
 */
const loadDeadlines = async (page = 1) => {
  isLoading.value = true
  try {
    const params = { page, per_page: 25 }
    if (filters.type) params.type = filters.type
    if (filters.status) params.status = filters.status

    const { data } = await window.axios.get('/deadlines', { params })
    const items = data.data || []
    deadlines.value = items

    // Pagination
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

const clearFilters = () => {
  filters.type = ''
  filters.status = ''
  loadDeadlines(1)
}

/**
 * Mark a deadline as completed via the API.
 */
const completeDeadline = async (deadline) => {
  if (completingId.value) return

  completingId.value = deadline.id

  try {
    const { data } = await window.axios.post(`/deadlines/${deadline.id}/complete`)

    // Update the deadline in the list
    const index = deadlines.value.findIndex((d) => d.id === deadline.id)
    if (index !== -1) {
      deadlines.value[index] = data.data
    }

    notificationStore.showNotification({
      type: 'success',
      message: t('deadlines.deadline_completed'),
    })

    // Refresh summary counts
    loadSummary()
  } catch (err) {
    notificationStore.showNotification({
      type: 'error',
      message: err?.response?.data?.error || 'Error completing deadline.',
    })
  } finally {
    completingId.value = null
  }
}

/**
 * Submit the create deadline form.
 */
const submitCreate = async () => {
  isCreating.value = true
  createError.value = null

  try {
    await window.axios.post('/deadlines', {
      title: createForm.title,
      title_mk: createForm.title_mk || null,
      deadline_type: createForm.deadline_type,
      due_date: createForm.due_date,
      description: createForm.description || null,
    })

    notificationStore.showNotification({
      type: 'success',
      message: t('deadlines.deadline_created'),
    })

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
  createForm.title = ''
  createForm.title_mk = ''
  createForm.deadline_type = 'custom'
  createForm.due_date = ''
  createForm.description = ''
  createError.value = null
}

/**
 * Open edit modal for a custom deadline.
 */
const openEditModal = (deadline) => {
  editingId.value = deadline.id
  editForm.title = deadline.title
  editForm.title_mk = deadline.title_mk || ''
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
      title_mk: editForm.title_mk || null,
      due_date: editForm.due_date,
      description: editForm.description || null,
    })

    const index = deadlines.value.findIndex((d) => d.id === editingId.value)
    if (index !== -1) {
      deadlines.value[index] = data.data
    }

    notificationStore.showNotification({
      type: 'success',
      message: t('deadlines.deadline_updated'),
    })

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

/**
 * Open delete confirmation modal.
 */
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
    notificationStore.showNotification({
      type: 'success',
      message: t('deadlines.deadline_deleted'),
    })
    showDeleteModal.value = false
    deleteTarget.value = null
    loadSummary()
  } catch (err) {
    notificationStore.showNotification({
      type: 'error',
      message: err?.response?.data?.error || 'Failed to delete deadline.',
    })
  } finally {
    isDeleting.value = false
  }
}

// Display helpers

const getTypeLabel = (type) => {
  return t(`deadlines.type_${type}`) || type
}

const getTypeBadgeClass = (type) => {
  const classes = {
    vat_return: 'bg-purple-100 text-purple-800',
    mpin: 'bg-indigo-100 text-indigo-800',
    cit_advance: 'bg-teal-100 text-teal-800',
    annual_fs: 'bg-amber-100 text-amber-800',
    custom: 'bg-gray-100 text-gray-800',
  }
  return classes[type] || 'bg-gray-100 text-gray-800'
}

const getStatusLabel = (status) => {
  const labels = {
    upcoming: t('deadlines.upcoming'),
    due_today: t('deadlines.due_today'),
    overdue: t('deadlines.overdue'),
    completed: t('deadlines.completed'),
  }
  return labels[status] || status
}

const getStatusBadgeClass = (status) => {
  const classes = {
    upcoming: 'bg-green-100 text-green-800',
    due_today: 'bg-yellow-100 text-yellow-800',
    overdue: 'bg-red-100 text-red-800',
    completed: 'bg-gray-100 text-gray-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getDaysClass = (daysRemaining, status) => {
  if (status === 'completed') return 'text-gray-400'
  if (daysRemaining < 0) return 'text-red-600 font-medium'
  if (daysRemaining === 0) return 'text-yellow-600 font-medium'
  if (daysRemaining <= 3) return 'text-orange-500'
  return 'text-gray-500'
}

const getDaysLabel = (daysRemaining, status) => {
  if (status === 'completed') return t('deadlines.completed')
  if (daysRemaining < 0) return `${Math.abs(daysRemaining)} ${t('deadlines.days_overdue')}`
  if (daysRemaining === 0) return t('deadlines.due_today')
  return `${daysRemaining} ${t('deadlines.days_left')}`
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  try {
    return new Date(dateString).toLocaleDateString(undefined, {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    })
  } catch {
    return dateString
  }
}

// Lifecycle
onMounted(() => {
  loadDeadlines(1)
  loadSummary()
})
</script>
