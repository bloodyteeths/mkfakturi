<template>
  <div class="relative">
    <BaseSettingCard
      :title="$t('settings.two_factor.two_factor_authentication')"
      :description="$t('settings.two_factor.section_description')"
    >
      <!-- 2FA Status -->
      <div class="mb-6">
        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
          <div>
            <h4 class="text-sm font-medium text-gray-900">
              {{ $t('settings.two_factor.status') }}
            </h4>
            <p class="mt-1 text-sm text-gray-500">
              {{ twoFactorStatus.two_factor_enabled
                ? $t('settings.two_factor.enabled_description')
                : $t('settings.two_factor.disabled_description') }}
            </p>
          </div>
          <div>
            <span
              v-if="twoFactorStatus.two_factor_enabled"
              class="inline-flex items-center px-3 py-1 text-sm font-medium text-green-800 bg-green-100 rounded-full"
            >
              {{ $t('settings.two_factor.enabled') }}
            </span>
            <span
              v-else
              class="inline-flex items-center px-3 py-1 text-sm font-medium text-gray-800 bg-gray-200 rounded-full"
            >
              {{ $t('settings.two_factor.disabled') }}
            </span>
          </div>
        </div>
      </div>

      <!-- Enable 2FA Section -->
      <div v-if="!twoFactorStatus.two_factor_enabled" class="space-y-6">
        <p class="text-sm text-gray-600">
          {{ $t('settings.two_factor.setup_intro') }}
        </p>

        <BaseButton @click="enableTwoFactor" :loading="isEnabling" :disabled="isEnabling">
          <template #left="slotProps">
            <BaseIcon
              v-if="!isEnabling"
              name="LockClosedIcon"
              :class="slotProps.class"
            ></BaseIcon>
          </template>
          {{ $t('settings.two_factor.enable_button') }}
        </BaseButton>
      </div>

      <!-- Setup QR Code (when enabled but not confirmed) -->
      <div
        v-if="twoFactorStatus.two_factor_enabled && !twoFactorStatus.two_factor_confirmed && qrCodeData"
        class="space-y-6"
      >
        <div class="p-6 bg-white border border-gray-200 rounded-lg">
          <h4 class="mb-4 text-lg font-medium text-gray-900">
            {{ $t('settings.two_factor.scan_qr_code') }}
          </h4>

          <div class="flex flex-col items-center space-y-4">
            <!-- QR Code -->
            <div class="p-4 bg-white border border-gray-300 rounded-lg" v-html="qrCodeData.qr_code"></div>

            <!-- Secret Key (for manual entry) -->
            <div class="w-full">
              <p class="mb-2 text-sm font-medium text-gray-700">
                {{ $t('settings.two_factor.secret_key_label') }}
              </p>
              <div class="flex items-center space-x-2">
                <code class="flex-1 px-3 py-2 text-sm bg-gray-100 rounded">
                  {{ qrCodeData.secret_key }}
                </code>
                <BaseButton
                  variant="secondary"
                  size="sm"
                  @click="copySecretKey"
                >
                  {{ $t('settings.two_factor.copy') }}
                </BaseButton>
              </div>
            </div>

            <!-- Instructions -->
            <div class="w-full mt-4 text-sm text-gray-600">
              <ol class="pl-5 space-y-2 list-decimal">
                <li>{{ $t('settings.two_factor.instruction_1') }}</li>
                <li>{{ $t('settings.two_factor.instruction_2') }}</li>
                <li>{{ $t('settings.two_factor.instruction_3') }}</li>
              </ol>
            </div>
          </div>
        </div>

        <!-- Confirmation Form -->
        <div class="p-6 bg-gray-50 rounded-lg">
          <h4 class="mb-4 text-lg font-medium text-gray-900">
            {{ $t('settings.two_factor.confirm_setup') }}
          </h4>

          <form @submit.prevent="confirmTwoFactor" class="space-y-4">
            <BaseInputGroup
              :label="$t('settings.two_factor.verification_code')"
              :error="confirmError"
              required
            >
              <BaseInput
                v-model="verificationCode"
                type="text"
                maxlength="6"
                :placeholder="$t('settings.two_factor.code_placeholder')"
                :invalid="!!confirmError"
              />
            </BaseInputGroup>

            <div class="flex space-x-3">
              <BaseButton
                type="submit"
                :loading="isConfirming"
                :disabled="isConfirming || verificationCode.length !== 6"
              >
                {{ $t('settings.two_factor.confirm_button') }}
              </BaseButton>
              <BaseButton
                variant="secondary"
                @click="cancelSetup"
                :disabled="isConfirming"
              >
                {{ $t('general.cancel') }}
              </BaseButton>
            </div>
          </form>
        </div>

        <!-- Recovery Codes -->
        <div v-if="recoveryCodes.length > 0" class="p-6 bg-yellow-50 border border-yellow-200 rounded-lg">
          <h4 class="mb-2 text-lg font-medium text-yellow-900">
            {{ $t('settings.two_factor.recovery_codes_title') }}
          </h4>
          <p class="mb-4 text-sm text-yellow-700">
            {{ $t('settings.two_factor.recovery_codes_warning') }}
          </p>

          <div class="grid grid-cols-2 gap-2 mb-4">
            <code
              v-for="code in recoveryCodes"
              :key="code"
              class="px-3 py-2 text-sm bg-white rounded"
            >
              {{ code }}
            </code>
          </div>

          <BaseButton variant="secondary" size="sm" @click="downloadRecoveryCodes">
            {{ $t('settings.two_factor.download_codes') }}
          </BaseButton>
        </div>
      </div>

      <!-- Manage 2FA (when confirmed) -->
      <div v-if="twoFactorStatus.two_factor_confirmed" class="space-y-6">
        <div class="p-6 bg-green-50 border border-green-200 rounded-lg">
          <div class="flex items-start">
            <BaseIcon name="ShieldCheckIcon" class="w-6 h-6 text-green-600 mr-3 mt-0.5" />
            <div class="flex-1">
              <h4 class="text-lg font-medium text-green-900">
                {{ $t('settings.two_factor.protected') }}
              </h4>
              <p class="mt-1 text-sm text-green-700">
                {{ $t('settings.two_factor.protected_description') }}
              </p>
            </div>
          </div>
        </div>

        <!-- Recovery Codes Management -->
        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
          <div>
            <h4 class="text-sm font-medium text-gray-900">
              {{ $t('settings.two_factor.recovery_codes') }}
            </h4>
            <p class="mt-1 text-sm text-gray-500">
              {{ $t('settings.two_factor.recovery_codes_info') }}
            </p>
          </div>
          <div class="flex space-x-2">
            <BaseButton
              variant="secondary"
              size="sm"
              @click="showRecoveryCodes"
              :loading="isLoadingCodes"
            >
              {{ $t('settings.two_factor.view_codes') }}
            </BaseButton>
            <BaseButton
              variant="secondary"
              size="sm"
              @click="regenerateRecoveryCodes"
              :loading="isRegenerating"
            >
              {{ $t('settings.two_factor.regenerate_codes') }}
            </BaseButton>
          </div>
        </div>

        <!-- Disable 2FA -->
        <div class="p-6 bg-red-50 border border-red-200 rounded-lg">
          <h4 class="mb-2 text-lg font-medium text-red-900">
            {{ $t('settings.two_factor.disable_2fa') }}
          </h4>
          <p class="mb-4 text-sm text-red-700">
            {{ $t('settings.two_factor.disable_warning') }}
          </p>

          <BaseButton
            variant="danger"
            @click="disableTwoFactor"
            :loading="isDisabling"
          >
            {{ $t('settings.two_factor.disable_button') }}
          </BaseButton>
        </div>
      </div>
    </BaseSettingCard>

    <!-- Recovery Codes Modal -->
    <BaseModal :show="showCodesModal" @close="showCodesModal = false">
      <template #header>
        <div class="text-lg font-medium">
          {{ $t('settings.two_factor.recovery_codes_title') }}
        </div>
      </template>

      <div class="space-y-4">
        <p class="text-sm text-gray-600">
          {{ $t('settings.two_factor.recovery_codes_modal_desc') }}
        </p>

        <div v-if="displayedRecoveryCodes.length > 0" class="grid grid-cols-2 gap-2">
          <code
            v-for="code in displayedRecoveryCodes"
            :key="code"
            class="px-3 py-2 text-sm bg-gray-100 rounded"
          >
            {{ code }}
          </code>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end space-x-2">
          <BaseButton
            variant="secondary"
            @click="downloadRecoveryCodes"
          >
            {{ $t('settings.two_factor.download_codes') }}
          </BaseButton>
          <BaseButton @click="showCodesModal = false">
            {{ $t('general.close') }}
          </BaseButton>
        </div>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useNotificationStore } from '@/scripts/admin/stores/notification'
import axios from 'axios'

const notificationStore = useNotificationStore()

const twoFactorStatus = reactive({
  two_factor_enabled: false,
  two_factor_confirmed: false,
})

const qrCodeData = ref(null)
const recoveryCodes = ref([])
const displayedRecoveryCodes = ref([])
const verificationCode = ref('')
const confirmError = ref('')
const showCodesModal = ref(false)

const isEnabling = ref(false)
const isConfirming = ref(false)
const isDisabling = ref(false)
const isLoadingCodes = ref(false)
const isRegenerating = ref(false)

onMounted(async () => {
  await fetchTwoFactorStatus()
})

async function fetchTwoFactorStatus() {
  try {
    const response = await axios.get('/api/v1/two-factor/status')
    twoFactorStatus.two_factor_enabled = response.data.data.two_factor_enabled
    twoFactorStatus.two_factor_confirmed = response.data.data.two_factor_confirmed
  } catch (error) {
    console.error('Failed to fetch 2FA status:', error)
  }
}

async function enableTwoFactor() {
  isEnabling.value = true
  try {
    const response = await axios.post('/api/v1/two-factor/enable')
    qrCodeData.value = response.data.data
    recoveryCodes.value = response.data.data.recovery_codes || []
    twoFactorStatus.two_factor_enabled = true

    notificationStore.showNotification({
      type: 'success',
      message: response.data.message,
    })
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || 'Failed to enable 2FA',
    })
  } finally {
    isEnabling.value = false
  }
}

async function confirmTwoFactor() {
  isConfirming.value = true
  confirmError.value = ''

  try {
    const response = await axios.post('/api/v1/two-factor/confirm', {
      code: verificationCode.value,
    })

    twoFactorStatus.two_factor_confirmed = true
    verificationCode.value = ''
    qrCodeData.value = null

    notificationStore.showNotification({
      type: 'success',
      message: response.data.message,
    })
  } catch (error) {
    confirmError.value = error.response?.data?.message || 'Invalid verification code'
  } finally {
    isConfirming.value = false
  }
}

async function disableTwoFactor() {
  if (!confirm('Are you sure you want to disable two-factor authentication?')) {
    return
  }

  isDisabling.value = true
  try {
    const response = await axios.delete('/api/v1/two-factor/disable')
    twoFactorStatus.two_factor_enabled = false
    twoFactorStatus.two_factor_confirmed = false
    qrCodeData.value = null
    recoveryCodes.value = []

    notificationStore.showNotification({
      type: 'success',
      message: response.data.message,
    })
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || 'Failed to disable 2FA',
    })
  } finally {
    isDisabling.value = false
  }
}

async function showRecoveryCodes() {
  isLoadingCodes.value = true
  try {
    const response = await axios.get('/api/v1/two-factor/recovery-codes')
    displayedRecoveryCodes.value = response.data.data.recovery_codes || []
    showCodesModal.value = true
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: 'Failed to load recovery codes',
    })
  } finally {
    isLoadingCodes.value = false
  }
}

async function regenerateRecoveryCodes() {
  if (!confirm('Are you sure you want to regenerate recovery codes? Old codes will no longer work.')) {
    return
  }

  isRegenerating.value = true
  try {
    const response = await axios.post('/api/v1/two-factor/recovery-codes')
    displayedRecoveryCodes.value = response.data.data.recovery_codes || []
    showCodesModal.value = true

    notificationStore.showNotification({
      type: 'success',
      message: response.data.message,
    })
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: 'Failed to regenerate recovery codes',
    })
  } finally {
    isRegenerating.value = false
  }
}

function cancelSetup() {
  disableTwoFactor()
}

function copySecretKey() {
  if (qrCodeData.value?.secret_key) {
    navigator.clipboard.writeText(qrCodeData.value.secret_key)
    notificationStore.showNotification({
      type: 'success',
      message: 'Secret key copied to clipboard',
    })
  }
}

function downloadRecoveryCodes() {
  const codes = displayedRecoveryCodes.value.length > 0
    ? displayedRecoveryCodes.value
    : recoveryCodes.value

  if (codes.length === 0) return

  const content = `Two-Factor Authentication Recovery Codes\n\nThese codes can be used to access your account if you lose your authentication device.\n\n${codes.join('\n')}\n\nKeep these codes in a safe place.`

  const blob = new Blob([content], { type: 'text/plain' })
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = 'recovery-codes.txt'
  a.click()
  window.URL.revokeObjectURL(url)
}
</script>

// CLAUDE-CHECKPOINT
