<template>
  <div ref="notificationCenter" class="relative">
    <!-- Notification Bell Icon -->
    <div
      class="
        flex
        items-center
        justify-center
        w-8
        h-8
        text-sm text-black
        bg-white
        rounded
        md:h-9 md:w-9
        cursor-pointer
        hover:bg-gray-100
        relative
      "
      @click="togglePanel"
    >
      <BaseIcon name="BellIcon" class="w-5 h-5 text-gray-600" />

      <!-- Unread Badge -->
      <span
        v-if="unreadCount > 0"
        class="
          absolute
          top-0
          right-0
          inline-flex
          items-center
          justify-center
          px-1.5
          py-0.5
          text-xs
          font-bold
          leading-none
          text-white
          bg-red-500
          rounded-full
          transform
          translate-x-1/2
          -translate-y-1/2
        "
      >
        {{ unreadCount > 9 ? '9+' : unreadCount }}
      </span>
    </div>

    <!-- Notification Panel -->
    <transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="translate-y-1 opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="translate-y-0 opacity-100"
      leave-to-class="translate-y-1 opacity-0"
    >
      <div
        v-if="isOpen"
        class="
          absolute
          right-0
          mt-2
          w-80
          bg-white
          rounded-md
          shadow-lg
          z-50
        "
      >
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b border-gray-200">
          <h3 class="text-base font-semibold text-gray-900">
            {{ $t('notifications.title') }}
          </h3>
          <div class="flex items-center space-x-2">
            <button
              v-if="notifications.length > 0"
              class="text-xs text-primary-500 hover:text-primary-600 font-medium"
              @click="markAllAsRead"
            >
              {{ $t('notifications.mark_all_read') }}
            </button>
          </div>
        </div>

        <!-- Notification List -->
        <div
          class="
            overflow-y-auto
            max-h-96
            scrollbar-thin
            scrollbar-thumb-gray-300
            scrollbar-track-gray-100
          "
        >
          <div v-if="loading" class="flex items-center justify-center p-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500"></div>
          </div>

          <div
            v-else-if="notifications.length === 0"
            class="flex flex-col items-center justify-center p-8 text-gray-400"
          >
            <BaseIcon name="BellSlashIcon" class="h-12 w-12 mb-2" />
            <p class="text-sm">{{ $t('notifications.no_notifications') }}</p>
          </div>

          <div v-else>
            <div
              v-for="notification in notifications"
              :key="notification.id"
              class="
                border-b
                border-gray-100
                last:border-b-0
                hover:bg-gray-50
                transition-colors
                cursor-pointer
              "
              :class="{ 'bg-blue-50': !notification.read_at }"
              @click="handleNotificationClick(notification)"
            >
              <div class="p-4">
                <div class="flex items-start">
                  <!-- Icon based on notification type -->
                  <div
                    class="
                      flex-shrink-0
                      w-10
                      h-10
                      rounded-full
                      flex
                      items-center
                      justify-center
                      mr-3
                    "
                    :class="getNotificationIconClass(notification.type)"
                  >
                    <BaseIcon
                      :name="getNotificationIcon(notification.type)"
                      class="h-5 w-5"
                    />
                  </div>

                  <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between">
                      <p
                        class="text-sm font-medium text-gray-900"
                        :class="{ 'font-semibold': !notification.read_at }"
                      >
                        {{ notification.data.title }}
                      </p>
                      <button
                        class="
                          ml-2
                          text-gray-400
                          hover:text-gray-600
                          flex-shrink-0
                        "
                        @click.stop="removeNotification(notification.id)"
                      >
                        <BaseIcon name="XMarkIcon" class="h-4 w-4" />
                      </button>
                    </div>
                    <p class="mt-1 text-sm text-gray-600">
                      {{ notification.data.message }}
                    </p>
                    <p class="mt-1 text-xs text-gray-400">
                      {{ formatTime(notification.created_at) }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div
          v-if="notifications.length > 0"
          class="
            p-3
            border-t border-gray-200
            bg-gray-50
            rounded-b-md
          "
        >
          <button
            class="
              w-full
              text-center
              text-sm
              text-primary-500
              hover:text-primary-600
              font-medium
            "
            @click="clearAll"
          >
            {{ $t('notifications.clear_all') }}
          </button>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { onClickOutside } from '@vueuse/core'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import axios from 'axios'

const router = useRouter()
const { t } = useI18n()

const notificationCenter = ref(null)
const isOpen = ref(false)
const loading = ref(false)
const notifications = ref([])
const pollingInterval = ref(null)

// Computed properties
const unreadCount = computed(() => {
  return notifications.value.filter(n => !n.read_at).length
})

// Click outside to close
onClickOutside(notificationCenter, () => {
  isOpen.value = false
})

// Lifecycle
onMounted(() => {
  fetchNotifications()
  startPolling()
})

// Stop polling when component is unmounted
watch(() => isOpen.value, (newValue) => {
  if (newValue) {
    fetchNotifications()
  }
})

/**
 * Toggle notification panel
 */
function togglePanel() {
  isOpen.value = !isOpen.value
}

/**
 * Fetch notifications from API
 */
async function fetchNotifications() {
  try {
    loading.value = true
    const response = await axios.get('/notifications')
    notifications.value = response.data.data || []
  } catch (error) {
    console.error('Failed to fetch notifications:', error)
    // Silently fail - notifications are not critical
  } finally {
    loading.value = false
  }
}

/**
 * Start polling for new notifications every 30 seconds
 */
function startPolling() {
  // Poll every 30 seconds
  pollingInterval.value = setInterval(() => {
    if (!isOpen.value) {
      // Only fetch in background when panel is closed to avoid disrupting user
      fetchNotifications()
    }
  }, 30000)
}

/**
 * Mark a single notification as read
 */
async function markAsRead(notificationId) {
  try {
    await axios.post(`/notifications/${notificationId}/read`)

    // Update local state
    const notification = notifications.value.find(n => n.id === notificationId)
    if (notification) {
      notification.read_at = new Date().toISOString()
    }
  } catch (error) {
    console.error('Failed to mark notification as read:', error)
  }
}

/**
 * Mark all notifications as read
 */
async function markAllAsRead() {
  try {
    await axios.post('/notifications/mark-all-read')

    // Update local state
    notifications.value.forEach(n => {
      n.read_at = new Date().toISOString()
    })
  } catch (error) {
    console.error('Failed to mark all notifications as read:', error)
  }
}

/**
 * Remove a single notification
 */
async function removeNotification(notificationId) {
  try {
    await axios.delete(`/notifications/${notificationId}`)

    // Remove from local state
    notifications.value = notifications.value.filter(n => n.id !== notificationId)
  } catch (error) {
    console.error('Failed to remove notification:', error)
  }
}

/**
 * Clear all notifications
 */
async function clearAll() {
  try {
    await axios.post('/notifications/clear')
    notifications.value = []
  } catch (error) {
    console.error('Failed to clear notifications:', error)
  }
}

/**
 * Handle notification click
 */
async function handleNotificationClick(notification) {
  // Mark as read
  if (!notification.read_at) {
    await markAsRead(notification.id)
  }

  // Navigate if there's a link
  if (notification.data.link) {
    isOpen.value = false
    router.push(notification.data.link)
  }
}

/**
 * Get notification icon based on type
 */
function getNotificationIcon(type) {
  const iconMap = {
    invoice: 'DocumentTextIcon',
    payment: 'CurrencyDollarIcon',
    estimate: 'DocumentIcon',
    customer: 'UserIcon',
    ticket: 'TicketIcon',
    trial_expiring: 'ExclamationTriangleIcon',
    trial_expired: 'XCircleIcon',
    payout: 'BanknotesIcon',
    kyc: 'ShieldCheckIcon',
    info: 'InformationCircleIcon',
    success: 'CheckCircleIcon',
    warning: 'ExclamationTriangleIcon',
    error: 'XCircleIcon',
  }

  return iconMap[type] || 'BellIcon'
}

/**
 * Get notification icon background class based on type
 */
function getNotificationIconClass(type) {
  const classMap = {
    invoice: 'bg-blue-100 text-blue-600',
    payment: 'bg-green-100 text-green-600',
    estimate: 'bg-purple-100 text-purple-600',
    customer: 'bg-indigo-100 text-indigo-600',
    ticket: 'bg-yellow-100 text-yellow-600',
    trial_expiring: 'bg-orange-100 text-orange-600',
    trial_expired: 'bg-red-100 text-red-600',
    payout: 'bg-emerald-100 text-emerald-600',
    kyc: 'bg-teal-100 text-teal-600',
    info: 'bg-blue-100 text-blue-600',
    success: 'bg-green-100 text-green-600',
    warning: 'bg-yellow-100 text-yellow-600',
    error: 'bg-red-100 text-red-600',
  }

  return classMap[type] || 'bg-gray-100 text-gray-600'
}

/**
 * Format time as relative (e.g., "2 hours ago")
 */
function formatTime(timestamp) {
  const date = new Date(timestamp)
  const now = new Date()
  const diffMs = now - date
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMins / 60)
  const diffDays = Math.floor(diffHours / 24)

  if (diffMins < 1) {
    return t('notifications.just_now')
  } else if (diffMins < 60) {
    return t('notifications.minutes_ago', { count: diffMins })
  } else if (diffHours < 24) {
    return t('notifications.hours_ago', { count: diffHours })
  } else if (diffDays < 7) {
    return t('notifications.days_ago', { count: diffDays })
  } else {
    return date.toLocaleDateString()
  }
}

// Cleanup on unmount
onMounted(() => {
  return () => {
    if (pollingInterval.value) {
      clearInterval(pollingInterval.value)
    }
  }
})
</script>
<!-- CLAUDE-CHECKPOINT -->
