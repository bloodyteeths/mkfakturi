<template>
  <BaseSettingCard
    :title="$t('settings.privacy_data.title')"
    :description="$t('settings.privacy_data.description')"
  >
    <div class="space-y-6">
      <!-- GDPR Data Export Section -->
      <div class="border-b border-gray-200 pb-6">
        <h3 class="text-lg font-medium text-gray-900 mb-2">
          {{ $t('settings.privacy_data.export_title') }}
        </h3>
        <p class="text-sm text-gray-600 mb-4">
          {{ $t('settings.privacy_data.export_description') }}
        </p>

        <!-- Export Status Card -->
        <div
          v-if="latestExport"
          class="bg-gray-50 rounded-lg p-4 mb-4 border border-gray-200"
        >
          <div class="flex items-center justify-between">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-2">
                <span class="text-sm font-medium text-gray-700">
                  {{ $t('settings.privacy_data.latest_export') }}
                </span>
                <span
                  :class="getStatusBadgeClass(latestExport.status)"
                  class="px-2 py-1 text-xs font-medium rounded-full"
                >
                  {{ getStatusLabel(latestExport.status) }}
                </span>
              </div>

              <div class="text-xs text-gray-500 space-y-1">
                <p>
                  {{ $t('settings.privacy_data.requested_at') }}:
                  {{ formatDateTime(latestExport.created_at) }}
                </p>
                <p v-if="latestExport.status === 'completed'">
                  {{ $t('settings.privacy_data.expires_at') }}:
                  {{ formatDateTime(latestExport.expires_at) }}
                </p>
                <p v-if="latestExport.status === 'completed' && latestExport.file_size">
                  {{ $t('settings.privacy_data.file_size') }}:
                  {{ formatFileSize(latestExport.file_size) }}
                </p>
                <p
                  v-if="latestExport.status === 'failed'"
                  class="text-red-600"
                >
                  {{ $t('settings.privacy_data.error') }}:
                  {{ latestExport.error_message }}
                </p>
              </div>
            </div>

            <div class="flex gap-2">
              <BaseButton
                v-if="latestExport.status === 'completed'"
                :outline="true"
                variant="primary"
                @click="downloadExport"
              >
                <template #left="slotProps">
                  <BaseIcon
                    name="ArrowDownTrayIcon"
                    :class="slotProps.class"
                  />
                </template>
                {{ $t('settings.privacy_data.download') }}
              </BaseButton>

              <BaseButton
                v-if="
                  latestExport.status === 'completed' ||
                  latestExport.status === 'failed'
                "
                :outline="true"
                variant="danger"
                @click="deleteExport"
              >
                <template #left="slotProps">
                  <BaseIcon name="TrashIcon" :class="slotProps.class" />
                </template>
                {{ $t('general.delete') }}
              </BaseButton>
            </div>
          </div>

          <!-- Processing indicator -->
          <div
            v-if="latestExport.status === 'processing' || latestExport.status === 'pending'"
            class="mt-3"
          >
            <div class="flex items-center gap-2">
              <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary-500"></div>
              <span class="text-xs text-gray-600">
                {{ $t('settings.privacy_data.processing_message') }}
              </span>
            </div>
          </div>
        </div>

        <!-- Request Export Button -->
        <BaseButton
          :loading="isRequesting"
          :disabled="isRequesting || isExportInProgress"
          @click="requestExport"
        >
          <template #left="slotProps">
            <BaseIcon
              v-if="!isRequesting"
              name="DocumentArrowDownIcon"
              :class="slotProps.class"
            />
          </template>
          {{ $t('settings.privacy_data.request_export') }}
        </BaseButton>

        <p class="text-xs text-gray-500 mt-2">
          {{ $t('settings.privacy_data.export_note') }}
        </p>
      </div>

      <!-- GDPR Information Section -->
      <div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">
          {{ $t('settings.privacy_data.your_rights_title') }}
        </h3>
        <div class="text-sm text-gray-600 space-y-2">
          <p>{{ $t('settings.privacy_data.rights_description') }}</p>
          <ul class="list-disc list-inside space-y-1 ml-2">
            <li>{{ $t('settings.privacy_data.right_access') }}</li>
            <li>{{ $t('settings.privacy_data.right_rectification') }}</li>
            <li>{{ $t('settings.privacy_data.right_erasure') }}</li>
            <li>{{ $t('settings.privacy_data.right_portability') }}</li>
          </ul>
        </div>
      </div>
    </div>
  </BaseSettingCard>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'
import axios from 'axios'

const { t } = useI18n()
const notificationStore = useNotificationStore()

const latestExport = ref(null)
const isRequesting = ref(false)
const isPolling = ref(false)
let pollingInterval = null

const isExportInProgress = computed(() => {
  return (
    latestExport.value &&
    (latestExport.value.status === 'pending' ||
      latestExport.value.status === 'processing')
  )
})

onMounted(() => {
  fetchLatestExport()
})

async function fetchLatestExport() {
  try {
    const response = await axios.get('/api/v1/user-data-exports/latest')
    latestExport.value = response.data.export

    // Start polling if export is in progress
    if (isExportInProgress.value && !isPolling.value) {
      startPolling()
    }
  } catch (error) {
    console.error('Error fetching latest export:', error)
  }
}

function startPolling() {
  if (isPolling.value) return

  isPolling.value = true
  pollingInterval = setInterval(async () => {
    await fetchLatestExport()

    // Stop polling when export is completed or failed
    if (!isExportInProgress.value) {
      stopPolling()
    }
  }, 5000) // Poll every 5 seconds
}

function stopPolling() {
  if (pollingInterval) {
    clearInterval(pollingInterval)
    pollingInterval = null
    isPolling.value = false
  }
}

async function requestExport() {
  if (isRequesting.value || isExportInProgress.value) {
    return
  }

  isRequesting.value = true

  try {
    const response = await axios.post('/api/v1/user-data-exports')
    latestExport.value = response.data.export

    notificationStore.showNotification({
      type: 'success',
      message: t('settings.privacy_data.export_requested_success'),
    })

    // Start polling for status updates
    startPolling()
  } catch (error) {
    if (error.response?.status === 422) {
      notificationStore.showNotification({
        type: 'error',
        message: error.response.data.message || t('settings.privacy_data.export_in_progress'),
      })
    } else {
      notificationStore.showNotification({
        type: 'error',
        message: t('settings.privacy_data.export_request_failed'),
      })
    }
  } finally {
    isRequesting.value = false
  }
}

async function downloadExport() {
  if (!latestExport.value || latestExport.value.status !== 'completed') {
    return
  }

  try {
    const response = await axios.get(
      `/api/v1/user-data-exports/${latestExport.value.id}/download`,
      {
        responseType: 'blob',
      }
    )

    // Create download link
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute(
      'download',
      `my-data-export-${new Date().toISOString().split('T')[0]}.zip`
    )
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)

    notificationStore.showNotification({
      type: 'success',
      message: t('settings.privacy_data.download_success'),
    })
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: t('settings.privacy_data.download_failed'),
    })
  }
}

async function deleteExport() {
  if (!latestExport.value) {
    return
  }

  if (!confirm(t('settings.privacy_data.delete_confirm'))) {
    return
  }

  try {
    await axios.delete(`/api/v1/user-data-exports/${latestExport.value.id}`)

    notificationStore.showNotification({
      type: 'success',
      message: t('settings.privacy_data.delete_success'),
    })

    latestExport.value = null
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: t('settings.privacy_data.delete_failed'),
    })
  }
}

function getStatusLabel(status) {
  const labels = {
    pending: t('settings.privacy_data.status_pending'),
    processing: t('settings.privacy_data.status_processing'),
    completed: t('settings.privacy_data.status_completed'),
    failed: t('settings.privacy_data.status_failed'),
    unknown: t('settings.privacy_data.status_unknown'),
  }
  return labels[status] || t('settings.privacy_data.status_unknown')
}

function getStatusBadgeClass(status) {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    processing: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800',
    unknown: 'bg-gray-100 text-gray-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

function formatDateTime(dateString) {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleString()
}

function formatFileSize(bytes) {
  if (!bytes) return 'N/A'
  const units = ['B', 'KB', 'MB', 'GB']
  let i = 0
  let size = bytes

  while (size >= 1024 && i < units.length - 1) {
    size /= 1024
    i++
  }

  return `${size.toFixed(2)} ${units[i]}`
}

// Clean up polling on component unmount - handled via onBeforeUnmount imported above
onBeforeUnmount(() => {
  stopPolling()
})
</script>
