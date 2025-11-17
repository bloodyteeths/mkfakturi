<template>
  <BaseSettingCard
    :title="$t('settings.feature_flags.title')"
    :description="$t('settings.feature_flags.description')"
  >
    <div v-if="isLoading" class="flex justify-center py-8">
      <BaseLoader />
    </div>

    <div v-else class="mt-6 space-y-4">
      <!-- Feature Flag Cards -->
      <div
        v-for="flag in featureFlags"
        :key="flag.flag"
        class="relative flex items-start justify-between p-6 border border-gray-200 rounded-lg hover:border-gray-300 transition-colors"
        :class="{
          'bg-green-50 border-green-200': flag.enabled,
          'bg-gray-50': !flag.enabled,
          'border-yellow-300': flag.critical && flag.enabled,
        }"
      >
        <div class="flex-1">
          <div class="flex items-center gap-3 mb-2">
            <h3 class="text-base font-semibold text-gray-900">
              {{ flag.name }}
            </h3>
            <span
              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
              :class="{
                'bg-green-100 text-green-800': flag.enabled,
                'bg-gray-200 text-gray-700': !flag.enabled,
              }"
            >
              {{
                flag.enabled
                  ? $t('settings.feature_flags.enabled')
                  : $t('settings.feature_flags.disabled')
              }}
            </span>
            <span
              v-if="flag.critical"
              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"
            >
              {{ $t('settings.feature_flags.critical') }}
            </span>
          </div>
          <p class="text-sm text-gray-600">
            {{ flag.description }}
          </p>

          <!-- Warning for partner-mocked-data -->
          <div
            v-if="flag.flag === 'partner-mocked-data' && flag.enabled"
            class="mt-3 flex items-start gap-2 p-3 bg-yellow-50 border border-yellow-200 rounded-md"
          >
            <BaseIcon
              name="ExclamationTriangleIcon"
              class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5"
            />
            <p class="text-sm text-yellow-800">
              {{ $t('settings.feature_flags.partner_mocked_data_warning') }}
            </p>
          </div>
        </div>

        <!-- Toggle Switch -->
        <div class="flex-shrink-0 ml-6">
          <button
            type="button"
            :disabled="flag.flag === 'partner-mocked-data' && !confirmCriticalToggle"
            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
            :class="{
              'bg-primary-600': flag.enabled,
              'bg-gray-200': !flag.enabled,
              'opacity-50 cursor-not-allowed':
                flag.flag === 'partner-mocked-data' && !confirmCriticalToggle,
            }"
            @click="handleToggle(flag)"
          >
            <span
              class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
              :class="{
                'translate-x-5': flag.enabled,
                'translate-x-0': !flag.enabled,
              }"
            ></span>
          </button>
        </div>
      </div>
    </div>

    <!-- No flags message -->
    <div
      v-if="!isLoading && featureFlags.length === 0"
      class="text-center py-8 text-gray-500"
    >
      {{ $t('settings.feature_flags.no_flags') }}
    </div>
  </BaseSettingCard>

  <!-- Confirmation Modal for Critical Flags -->
  <BaseModal
    v-if="showConfirmModal"
    :title="$t('settings.feature_flags.confirm_toggle_title')"
  >
    <div class="space-y-4">
      <div class="flex items-start gap-3 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
        <BaseIcon
          name="ExclamationTriangleIcon"
          class="w-6 h-6 text-yellow-600 flex-shrink-0 mt-0.5"
        />
        <div>
          <p class="text-sm font-medium text-yellow-900">
            {{ $t('settings.feature_flags.confirm_toggle_message', { flag: selectedFlag?.name }) }}
          </p>
          <p class="mt-2 text-sm text-yellow-800">
            {{ selectedFlag?.description }}
          </p>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-end gap-3">
        <BaseButton variant="secondary" @click="closeConfirmModal">
          {{ $t('general.cancel') }}
        </BaseButton>
        <BaseButton
          variant="primary"
          @click="confirmToggle"
        >
          {{ $t('general.confirm') }}
        </BaseButton>
      </div>
    </template>
  </BaseModal>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { useNotificationStore } from '@/scripts/stores/notification'
import { handleError } from '@/scripts/helpers/error-handling'

const { t } = useI18n()
const notificationStore = useNotificationStore()

const isLoading = ref(false)
const featureFlags = ref([])
const showConfirmModal = ref(false)
const selectedFlag = ref(null)
const confirmCriticalToggle = ref(true)

onMounted(async () => {
  await fetchFeatureFlags()
})

/**
 * Fetch all feature flags from the API
 */
async function fetchFeatureFlags() {
  isLoading.value = true

  try {
    const response = await axios.get('/settings/feature-flags')
    featureFlags.value = response.data.flags || []
  } catch (error) {
    handleError(error)
  } finally {
    isLoading.value = false
  }
}

/**
 * Handle toggle click - show confirmation for critical flags
 */
function handleToggle(flag) {
  if (flag.critical && flag.enabled) {
    // Show confirmation modal for disabling critical flags
    selectedFlag.value = flag
    showConfirmModal.value = true
  } else {
    // Toggle immediately for non-critical flags
    toggleFeatureFlag(flag)
  }
}

/**
 * Close confirmation modal
 */
function closeConfirmModal() {
  showConfirmModal.value = false
  selectedFlag.value = null
}

/**
 * Confirm toggle from modal
 */
async function confirmToggle() {
  if (selectedFlag.value) {
    await toggleFeatureFlag(selectedFlag.value)
  }
  closeConfirmModal()
}

/**
 * Toggle a feature flag
 */
async function toggleFeatureFlag(flag) {
  try {
    const response = await axios.post(
      `/api/v1/settings/feature-flags/${flag.flag}/toggle`
    )

    if (response.data.success) {
      // Update the local flag state
      flag.enabled = response.data.enabled

      // Show success notification
      notificationStore.showNotification({
        type: 'success',
        message: response.data.message || t('settings.feature_flags.toggle_success'),
      })
    }
  } catch (error) {
    handleError(error)
  }
}
</script>

<style scoped>
/* Additional styles if needed */
</style>
