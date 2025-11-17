<template>
  <BaseModal
    :show="modelValue"
    @close="closeModal"
    @update:show="$emit('update:modelValue', $event)"
  >
    <template #header>
      <div class="flex justify-between items-center">
        <h3 class="text-lg font-semibold text-gray-900">
          {{ $t('banking.connect_new_bank') }}
        </h3>
      </div>
    </template>

    <div class="p-6">
      <p class="text-sm text-gray-600 mb-6">
        {{ $t('banking.select_bank_to_connect') }}
      </p>

      <!-- Bank Selection -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Stopanska Banka -->
        <div
          class="border-2 border-gray-200 rounded-lg p-6 hover:border-primary-500 hover:shadow-md transition-all cursor-pointer"
          :class="{ 'border-primary-500 bg-primary-50': selectedBank === 'stopanska' }"
          @click="selectBank('stopanska')"
        >
          <div class="flex flex-col items-center">
            <img
              src="/images/banks/stopanska-logo.png"
              alt="Stopanska Banka"
              class="h-16 w-auto mb-4"
              @error="handleImageError"
            />
            <h4 class="text-md font-semibold text-gray-900 mb-2">
              {{ $t('banking.stopanska') }}
            </h4>
            <p class="text-sm text-gray-500 text-center">
              {{ $t('banking.stopanska_description') }}
            </p>
          </div>
        </div>

        <!-- NLB Banka -->
        <div
          class="border-2 border-gray-200 rounded-lg p-6 hover:border-primary-500 hover:shadow-md transition-all cursor-pointer"
          :class="{ 'border-primary-500 bg-primary-50': selectedBank === 'nlb' }"
          @click="selectBank('nlb')"
        >
          <div class="flex flex-col items-center">
            <img
              src="/images/banks/nlb-logo.png"
              alt="NLB Banka"
              class="h-16 w-auto mb-4"
              @error="handleImageError"
            />
            <h4 class="text-md font-semibold text-gray-900 mb-2">
              {{ $t('banking.nlb') }}
            </h4>
            <p class="text-sm text-gray-500 text-center">
              {{ $t('banking.nlb_description') }}
            </p>
          </div>
        </div>
      </div>

      <!-- OAuth Flow Information -->
      <div v-if="selectedBank" class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <div class="flex items-start">
          <BaseIcon name="InformationCircleIcon" class="h-5 w-5 text-blue-600 mt-0.5 mr-3" />
          <div class="text-sm text-blue-800">
            <p class="font-semibold mb-1">{{ $t('banking.oauth_info_title') }}</p>
            <ul class="list-disc list-inside space-y-1 ml-2">
              <li>{{ $t('banking.oauth_step_1') }}</li>
              <li>{{ $t('banking.oauth_step_2') }}</li>
              <li>{{ $t('banking.oauth_step_3') }}</li>
              <li>{{ $t('banking.oauth_step_4') }}</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Error Message -->
      <div v-if="errorMessage" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
        <div class="flex items-start">
          <BaseIcon name="ExclamationTriangleIcon" class="h-5 w-5 text-red-600 mt-0.5 mr-3" />
          <p class="text-sm text-red-800">{{ errorMessage }}</p>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-end space-x-3">
        <BaseButton
          variant="secondary"
          @click="closeModal"
        >
          {{ $t('general.cancel') }}
        </BaseButton>
        <BaseButton
          variant="primary"
          :disabled="!selectedBank || isConnecting"
          :loading="isConnecting"
          @click="startOAuthFlow"
        >
          {{ $t('banking.connect') }}
        </BaseButton>
      </div>
    </template>
  </BaseModal>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'
import axios from 'axios'

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true
  }
})

const emit = defineEmits(['update:modelValue', 'connected'])

const { t } = useI18n()
const notificationStore = useNotificationStore()

// State
const selectedBank = ref(null)
const isConnecting = ref(false)
const errorMessage = ref(null)

// Methods
const selectBank = (bankCode) => {
  selectedBank.value = bankCode
  errorMessage.value = null
}

const startOAuthFlow = async () => {
  if (!selectedBank.value) return

  isConnecting.value = true
  errorMessage.value = null

  try {
    // Get the OAuth authorization URL from backend
    const response = await axios.get('/banking/oauth/start', {
      params: {
        provider: selectedBank.value
      }
    })

    if (response.data.authorization_url) {
      // Redirect to bank's OAuth authorization page
      window.location.href = response.data.authorization_url
    } else {
      throw new Error('No authorization URL returned')
    }
  } catch (error) {
    console.error('Failed to start OAuth flow:', error)
    errorMessage.value = error.response?.data?.message || t('banking.oauth_failed')
    isConnecting.value = false
  }
}

const closeModal = () => {
  emit('update:modelValue', false)
  // Reset state
  selectedBank.value = null
  errorMessage.value = null
  isConnecting.value = false
}

const handleImageError = (event) => {
  // Fallback if bank logo image is missing
  event.target.style.display = 'none'
}
</script>

<!-- CLAUDE-CHECKPOINT -->
