<template>
  <BasePage>
    <BasePageHeader :title="$t('deadlines.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('deadlines.title')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-1 gap-6 mt-6 md:grid-cols-3">
      <!-- Overdue -->
      <div class="relative flex items-center p-4 bg-white border-l-4 border-red-500 rounded shadow">
        <div class="flex-shrink-0">
          <BaseIcon name="ExclamationCircleIcon" class="h-8 w-8 text-red-500" />
        </div>
        <div class="ml-4">
          <p class="text-sm font-medium text-gray-500">{{ $t('deadlines.overdue') }}</p>
          <p class="text-2xl font-bold text-red-600">{{ summary.overdue }}</p>
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
        <BaseButton variant="primary-outline" @click="loadDeadlines">
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
          <div class="font-medium text-gray-900">
            {{ row.data.title_mk || row.data.title }}
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
            {{ row.data.type_label || getTypeLabel(row.data.deadline_type) }}
          </span>
        </template>

        <template #cell-due_date="{ row }">
          <span class="text-sm text-gray-900">
            {{ formatDate(row.data.due_date) }}
          </span>
        </template>

        <template #cell-status="{ row }">
          <span
            :class="getStatusBadgeClass(row.data.status)"
            class="px-2 py-1 text-xs font-medium rounded-full"
          >
            {{ getStatusLabel(row.data.status) }}
          </span>
        </template>

        <template #cell-actions="{ row }">
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
        </template>
      </BaseTable>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'

const { t } = useI18n()
const notificationStore = useNotificationStore()

// State
const deadlines = ref([])
const isLoading = ref(true)
const completingId = ref(null)

// KPI Summary
const summary = reactive({
  overdue: 0,
  due_this_week: 0,
  due_this_month: 0,
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
    thClass: 'w-40',
  },
  {
    key: 'status',
    label: t('deadlines.status'),
    thClass: 'w-32',
  },
  {
    key: 'actions',
    label: '',
    thClass: 'w-40',
  },
])

/**
 * Load deadlines from the company API endpoint.
 */
const loadDeadlines = async () => {
  isLoading.value = true
  try {
    const { data } = await window.axios.get('/deadlines')
    const items = data.data || []
    deadlines.value = items

    // Compute KPI summary from the loaded data
    computeSummary(items)
  } catch (err) {
    notificationStore.showNotification({
      type: 'error',
      message: err?.response?.data?.error || t('deadlines.no_deadlines'),
    })
  } finally {
    isLoading.value = false
  }
}

/**
 * Compute KPI summary counts from the deadlines list.
 */
const computeSummary = (items) => {
  const now = new Date()
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())

  // End of this week (Sunday)
  const endOfWeek = new Date(today)
  endOfWeek.setDate(today.getDate() + (7 - today.getDay()))

  // End of this month
  const endOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0)

  let overdue = 0
  let dueThisWeek = 0
  let dueThisMonth = 0

  items.forEach((d) => {
    if (d.status === 'overdue') {
      overdue++
    }
    if (d.status !== 'completed') {
      const dueDate = new Date(d.due_date)
      if (dueDate >= today && dueDate <= endOfWeek) {
        dueThisWeek++
      }
      if (dueDate >= today && dueDate <= endOfMonth) {
        dueThisMonth++
      }
    }
  })

  summary.overdue = overdue
  summary.due_this_week = dueThisWeek
  summary.due_this_month = dueThisMonth
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
      message: t('deadlines.mark_complete'),
    })

    // Recompute summary
    computeSummary(deadlines.value)
  } catch (err) {
    notificationStore.showNotification({
      type: 'error',
      message: err?.response?.data?.error || 'Error completing deadline.',
    })
  } finally {
    completingId.value = null
  }
}

// Display helpers

const getTypeLabel = (type) => {
  const labels = {
    vat_return: 'VAT Return',
    mpin: 'MPIN',
    cit_advance: 'CIT Advance',
    annual_fs: 'Annual FS',
    custom: 'Custom',
  }
  return labels[type] || type
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
  loadDeadlines()
})
</script>
// CLAUDE-CHECKPOINT
