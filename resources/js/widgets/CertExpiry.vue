<template>
  <BaseCard class="cert-expiry-widget">
    <template #header>
      <div class="flex items-center">
        <ShieldCheckIcon 
          class="h-5 w-5 mr-2"
          :class="certificateStatusClass"
        />
        <span class="text-lg font-medium">{{ $t('widgets.cert_expiry.title') }}</span>
      </div>
    </template>

    <!-- Loading State -->
    <div v-if="isLoading" class="flex items-center justify-center py-8">
      <BaseSpinner class="h-8 w-8 text-primary-500" />
      <span class="ml-2 text-gray-600">{{ $t('widgets.cert_expiry.loading') }}</span>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="text-center py-8">
      <ExclamationTriangleIcon class="mx-auto h-12 w-12 text-red-400" />
      <p class="mt-2 text-red-600">{{ error }}</p>
      <BaseButton 
        variant="primary-outline" 
        size="sm" 
        class="mt-3"
        @click="fetchCertificate"
      >
        {{ $t('widgets.cert_expiry.retry') }}
      </BaseButton>
    </div>

    <!-- No Certificate State -->
    <div v-else-if="!certificate" class="text-center py-8">
      <ShieldExclamationIcon class="mx-auto h-12 w-12 text-gray-300" />
      <p class="mt-2 text-gray-500">{{ $t('widgets.cert_expiry.no_certificate') }}</p>
      <p class="text-sm text-gray-400 mt-1">
        {{ $t('widgets.cert_expiry.upload_help') }}
      </p>
      <BaseButton 
        variant="primary" 
        size="sm" 
        class="mt-3"
        @click="navigateToUpload"
      >
        {{ $t('widgets.cert_expiry.upload_certificate') }}
      </BaseButton>
    </div>

    <!-- Certificate Information -->
    <div v-else class="space-y-4">
      <!-- Status Badge -->
      <div class="flex items-center justify-between">
        <span class="text-sm text-gray-600">{{ $t('widgets.cert_expiry.status') }}</span>
        <BaseBadge
          :variant="statusVariant"
          size="sm"
        >
          <div class="flex items-center">
            <div 
              v-if="isExpiringSoon" 
              class="w-2 h-2 bg-red-400 rounded-full mr-2 animate-pulse"
            ></div>
            {{ statusText }}
          </div>
        </BaseBadge>
      </div>

      <!-- Certificate Subject -->
      <div>
        <span class="text-sm text-gray-600">{{ $t('widgets.cert_expiry.subject') }}</span>
        <p class="text-sm font-medium truncate">
          {{ certificate.subject?.CN || $t('widgets.cert_expiry.unknown') }}
        </p>
      </div>

      <!-- Expiry Information -->
      <div>
        <span class="text-sm text-gray-600">{{ $t('widgets.cert_expiry.expires') }}</span>
        <p class="text-sm font-medium" :class="expiryTextClass">
          {{ formattedExpiryDate }}
        </p>
        <p v-if="daysUntilExpiry !== null" class="text-xs" :class="daysTextClass">
          {{ daysUntilExpiryText }}
        </p>
      </div>

      <!-- QES Certificate Info (if applicable) -->
      <div v-if="certificate.qes_enabled" class="bg-blue-50 border border-blue-200 rounded-md p-3">
        <div class="flex items-center">
          <BadgeCheckIcon class="h-4 w-4 text-blue-500 mr-2" />
          <span class="text-sm font-medium text-blue-700">
            {{ $t('widgets.cert_expiry.qes_enabled') }}
          </span>
        </div>
        <p class="text-xs text-blue-600 mt-1">
          {{ $t('widgets.cert_expiry.qes_description') }}
        </p>
      </div>

      <!-- Critical Alert (30 days or less) -->
      <div v-if="isExpiringSoon" class="bg-red-50 border border-red-200 rounded-md p-3">
        <div class="flex items-center">
          <ExclamationTriangleIcon class="h-4 w-4 text-red-500 mr-2" />
          <span class="text-sm font-medium text-red-700">
            {{ $t('widgets.cert_expiry.expiry_alert') }}
          </span>
        </div>
        <p class="text-xs text-red-600 mt-1">
          {{ $t('widgets.cert_expiry.renew_message') }}
        </p>
      </div>

      <!-- Actions -->
      <div class="flex space-x-2 pt-2 border-t">
        <BaseButton 
          variant="primary-outline" 
          size="sm"
          @click="viewCertificateDetails"
        >
          <template #left>
            <InfoIcon class="h-4 w-4" />
          </template>
          {{ $t('widgets.cert_expiry.view_details') }}
        </BaseButton>
        <BaseButton 
          v-if="isExpiringSoon" 
          variant="primary" 
          size="sm"
          @click="navigateToUpload"
        >
          <template #left>
            <ArrowPathIcon class="h-4 w-4" />
          </template>
          {{ $t('widgets.cert_expiry.renew_now') }}
        </BaseButton>
      </div>
    </div>

    <!-- Last Update Info -->
    <div class="mt-4 pt-3 border-t text-center">
      <p class="text-xs text-gray-400">
        {{ $t('widgets.cert_expiry.last_checked') }}: {{ formattedLastUpdate }}
      </p>
    </div>
  </BaseCard>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useUserStore } from '@/scripts/admin/stores/user'
import axios from 'axios'

// Icons
import {
  ShieldCheckIcon,
  ShieldExclamationIcon,
  ExclamationTriangleIcon,
  InfoIcon,
  ArrowPathIcon,
  BadgeCheckIcon
} from '@heroicons/vue/24/outline'

// Composables
const { t } = useI18n()
const router = useRouter()
const notificationStore = useNotificationStore()
const userStore = useUserStore()

// Reactive state
const certificate = ref(null)
const isLoading = ref(true)
const error = ref('')
const lastUpdate = ref(new Date())
const refreshInterval = ref(null)

// Computed properties
const certificateStatusClass = computed(() => {
  if (!certificate.value) return 'text-gray-400'
  if (isExpiringSoon.value) return 'text-red-500'
  if (certificate.value.is_valid) return 'text-green-500'
  return 'text-red-500'
})

const daysUntilExpiry = computed(() => {
  if (!certificate.value?.valid_to) return null
  
  const now = new Date()
  const expiryDate = new Date(certificate.value.valid_to)
  const diffTime = expiryDate - now
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  return diffDays
})

const isExpiringSoon = computed(() => {
  return daysUntilExpiry.value !== null && daysUntilExpiry.value <= 30
})

const statusVariant = computed(() => {
  if (!certificate.value) return 'gray'
  if (isExpiringSoon.value) return 'red'
  if (certificate.value.is_valid) return 'green'
  return 'red'
})

const statusText = computed(() => {
  if (!certificate.value) return t('widgets.cert_expiry.no_cert')
  if (daysUntilExpiry.value !== null && daysUntilExpiry.value < 0) return t('widgets.cert_expiry.expired')
  if (isExpiringSoon.value) return t('widgets.cert_expiry.expiring_soon')
  if (certificate.value.is_valid) return t('widgets.cert_expiry.valid')
  return t('widgets.cert_expiry.invalid')
})

const expiryTextClass = computed(() => {
  if (isExpiringSoon.value) return 'text-red-600'
  if (certificate.value?.is_valid) return 'text-green-600'
  return 'text-gray-600'
})

const daysTextClass = computed(() => {
  if (isExpiringSoon.value) return 'text-red-500'
  return 'text-gray-500'
})

const daysUntilExpiryText = computed(() => {
  if (daysUntilExpiry.value === null) return ''
  
  if (daysUntilExpiry.value < 0) {
    const expiredDays = Math.abs(daysUntilExpiry.value)
    return t('widgets.cert_expiry.expired_days_ago', { days: expiredDays })
  }
  
  if (daysUntilExpiry.value === 0) {
    return t('widgets.cert_expiry.expires_today')
  }
  
  return t('widgets.cert_expiry.expires_in_days', { days: daysUntilExpiry.value })
})

const formattedExpiryDate = computed(() => {
  if (!certificate.value?.valid_to) return t('widgets.cert_expiry.unknown')
  
  try {
    return new Date(certificate.value.valid_to).toLocaleDateString()
  } catch {
    return t('widgets.cert_expiry.unknown')
  }
})

const formattedLastUpdate = computed(() => {
  return lastUpdate.value.toLocaleTimeString()
})

// Methods
const fetchCertificate = async () => {
  isLoading.value = true
  error.value = ''

  try {
    const response = await axios.get('/api/v1/certificates/current')
    // Handle both 200 with null data and actual certificate data
    certificate.value = response.data.data || null
    lastUpdate.value = new Date()
  } catch (err) {
    if (err.response?.status === 404) {
      certificate.value = null
    } else if (err.response?.status === 401) {
      // Not authenticated - don't show error, just no certificate
      certificate.value = null
    } else {
      console.error('Failed to fetch certificate:', err)
      error.value = t('widgets.cert_expiry.fetch_error')
    }
  } finally {
    isLoading.value = false
  }
}

const viewCertificateDetails = () => {
  // Navigate to certificate details view
  router.push('/admin/settings/certificates')
}

const navigateToUpload = () => {
  // Navigate to certificate upload page
  router.push('/admin/settings/certificates')
}

const startAutoRefresh = () => {
  // Refresh certificate data every 5 minutes
  refreshInterval.value = setInterval(() => {
    fetchCertificate()
  }, 5 * 60 * 1000)
}

const stopAutoRefresh = () => {
  if (refreshInterval.value) {
    clearInterval(refreshInterval.value)
    refreshInterval.value = null
  }
}

// Lifecycle
onMounted(() => {
  if (userStore.currentUser) {
    fetchCertificate()
    startAutoRefresh()
  }
})

// Watch for user authentication
watch(
  () => userStore.currentUser,
  (newUser) => {
    if (newUser && !certificate.value) {
      fetchCertificate()
      startAutoRefresh()
    }
  }
)

onUnmounted(() => {
  stopAutoRefresh()
})
</script>

<style scoped>
.cert-expiry-widget {
  min-width: 320px;
  max-width: 400px;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>

// CLAUDE-CHECKPOINT: Added authentication check before API calls
// - Imported useUserStore and watch from Vue
// - Added userStore to composables
// - Modified onMounted to only call API if user is authenticated
// - Added watcher to trigger fetch once user authentication is ready