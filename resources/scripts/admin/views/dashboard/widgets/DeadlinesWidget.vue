<template>
  <BaseCard>
    <template #header>
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900">
          {{ $t('dashboard.upcoming_deadlines') || 'Upcoming Deadlines' }}
        </h3>
        <BaseIcon name="ClockIcon" class="h-6 w-6 text-blue-500" />
      </div>
    </template>

    <!-- Loading State -->
    <LoadingSkeleton v-if="isLoading" variant="list" :rows="3" />

    <!-- No Deadlines -->
    <div v-else-if="!deadlines.length" class="text-center py-6">
      <BaseIcon name="CheckCircleIcon" class="h-16 w-16 mx-auto text-green-500 mb-2" />
      <p class="text-sm text-gray-600">
        {{ $t('dashboard.no_upcoming_deadlines') || 'No upcoming deadlines' }}
      </p>
    </div>

    <!-- Deadlines List -->
    <div v-else class="space-y-3">
      <!-- Summary Alert -->
      <div
        :class="[
          'border rounded-lg p-3 mb-4',
          overdueCount > 0
            ? 'bg-red-50 border-red-200'
            : 'bg-yellow-50 border-yellow-200',
        ]"
      >
        <div class="flex items-center justify-between">
          <div>
            <p
              :class="[
                'text-sm font-medium',
                overdueCount > 0 ? 'text-red-800' : 'text-yellow-800',
              ]"
            >
              {{ $t('deadlines.title') || 'Deadlines' }}
            </p>
            <p
              :class="[
                'text-xs mt-1',
                overdueCount > 0 ? 'text-red-600' : 'text-yellow-600',
              ]"
            >
              {{ overdueCount }} {{ $t('deadlines.overdue') || 'Overdue' }},
              {{ dueThisWeekCount }} {{ $t('deadlines.due_this_week') || 'Due This Week' }}
            </p>
          </div>
          <div class="text-right">
            <div
              :class="[
                'text-lg font-bold',
                overdueCount > 0 ? 'text-red-700' : 'text-yellow-700',
              ]"
            >
              {{ deadlines.length }}
            </div>
            <div class="text-xs text-gray-500">
              {{ $t('deadlines.upcoming') || 'Upcoming' }}
            </div>
          </div>
        </div>
      </div>

      <!-- Deadline Items (limited to 5) -->
      <div
        v-for="deadline in displayedDeadlines"
        :key="deadline.id"
        class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
      >
        <div class="flex-1 min-w-0">
          <router-link
            :to="getDeadlineRoute(deadline)"
            class="text-sm font-medium text-primary-500 hover:text-primary-600 truncate block"
          >
            {{ getLocalizedTitle(deadline) }}
          </router-link>
          <div class="flex items-center gap-2 mt-1">
            <span
              :class="getTypeBadgeClass(deadline.deadline_type)"
              class="px-1.5 py-0.5 text-xs font-medium rounded"
            >
              {{ getTypeLabel(deadline) }}
            </span>
            <span :class="getDaysClass(deadline)" class="text-xs">
              {{ formatDueDate(deadline) }}
            </span>
          </div>
        </div>
        <button
          v-if="deadline.status !== 'completed'"
          @click="markComplete(deadline)"
          :disabled="completingId === deadline.id"
          class="ml-3 flex-shrink-0 text-xs font-medium text-green-600 hover:text-green-800 disabled:opacity-50"
        >
          {{ completingId === deadline.id ? '...' : ($t('deadlines.mark_complete') || 'Complete') }}
        </button>
      </div>

      <!-- View All Link -->
      <div v-if="deadlines.length > 5" class="pt-3 border-t border-gray-200">
        <router-link
          to="/admin/deadlines"
          class="text-sm text-primary-500 hover:text-primary-600 font-medium flex items-center justify-center"
        >
          {{ $t('dashboard.view_all_deadlines') || 'View all deadlines' }}
          <BaseIcon name="ChevronRightIcon" class="h-4 w-4 ml-1" />
        </router-link>
      </div>
    </div>
  </BaseCard>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'
import LoadingSkeleton from '@/scripts/admin/components/LoadingSkeleton.vue'

const { t, locale } = useI18n()
const notificationStore = useNotificationStore()

const isLoading = ref(true)
const deadlines = ref([])
const completingId = ref(null)

const displayedDeadlines = computed(() => {
  return deadlines.value.slice(0, 5)
})

const overdueCount = computed(() => {
  return deadlines.value.filter((d) => d.status === 'overdue').length
})

const dueThisWeekCount = computed(() => {
  const today = new Date()
  const weekFromNow = new Date()
  weekFromNow.setDate(today.getDate() + 7)

  return deadlines.value.filter((d) => {
    if (d.status === 'completed') return false
    const due = new Date(d.due_date)
    return due >= today && due <= weekFromNow
  }).length
})

function getTypeBadgeClass(type) {
  const classes = {
    vat_return: 'bg-blue-100 text-blue-700',
    mpin: 'bg-purple-100 text-purple-700',
    cit_advance: 'bg-green-100 text-green-700',
    annual_fs: 'bg-orange-100 text-orange-700',
    custom: 'bg-gray-100 text-gray-700',
  }
  return classes[type] || 'bg-gray-100 text-gray-700'
}

function getLocalizedTitle(deadline) {
  if (locale.value === 'mk' && deadline.title_mk) return deadline.title_mk
  return deadline.title
}

function getTypeLabel(deadline) {
  const key = `deadlines.type_${deadline.deadline_type}`
  const translated = t(key)
  if (translated !== key) return translated
  return deadline.type_label_en || deadline.type_label || deadline.deadline_type
}

function getDaysClass(deadline) {
  if (deadline.status === 'overdue') return 'text-red-600 font-medium'
  if (deadline.status === 'due_today') return 'text-orange-600 font-medium'
  return 'text-gray-500'
}

function formatDueDate(deadline) {
  const days = deadline.days_remaining
  if (days === undefined || days === null) {
    return deadline.due_date
  }
  if (days < 0) return `${Math.abs(days)}d ${t('deadlines.overdue').toLowerCase()}`
  if (days === 0) return t('deadlines.due_today')
  return `${days}d ${t('deadlines.upcoming').toLowerCase()}`
}

function getDeadlineRoute(deadline) {
  const routes = {
    'vat_return': '/admin/accounting/ujp-forms',
    'mpin': '/admin/payroll',
    'cit_advance': '/admin/accounting/ujp-forms',
    'annual_fs': '/admin/reports',
    'payroll': '/admin/payroll',
  }
  return routes[deadline.deadline_type] || '/admin/deadlines'
}

async function fetchDeadlines() {
  isLoading.value = true
  try {
    const { data } = await window.axios.get('/deadlines', {
      params: { per_page: 10 },
    })
    // API returns paginated data
    deadlines.value = (data.data || []).filter(
      (d) => d.status !== 'completed'
    )
  } catch (error) {
    console.error('Error fetching deadlines:', error)
    deadlines.value = []
  } finally {
    isLoading.value = false
  }
}

async function markComplete(deadline) {
  completingId.value = deadline.id
  try {
    await window.axios.post(`/deadlines/${deadline.id}/complete`)
    deadlines.value = deadlines.value.filter((d) => d.id !== deadline.id)
    notificationStore.showNotification({
      type: 'success',
      message: t('deadlines.marked_complete') || 'Deadline marked as complete.',
    })
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: t('deadlines.complete_failed') || 'Failed to complete deadline.',
    })
  } finally {
    completingId.value = null
  }
}

onMounted(() => {
  fetchDeadlines()
})
</script>

