<template>
  <BaseModal
    :show="modelValue"
    @close="closeModal"
    @update:show="$emit('update:modelValue', $event)"
  >
    <template #header>
      <div class="flex justify-between items-center">
        <h3 class="text-lg font-semibold text-gray-900">
          {{ mode === 'manual' ? $t('banking.add_manual_account') : $t('banking.connect_new_bank') }}
        </h3>
      </div>
    </template>

    <div class="p-6">
      <!-- Mode Selection -->
      <template v-if="mode === 'select'">
        <p class="text-sm text-gray-600 mb-6">
          {{ $t('banking.select_bank_to_connect') }}
        </p>

        <!-- Bank Selection Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Manual Account (first option) -->
          <div
            class="border-2 border-gray-200 rounded-lg p-6 hover:border-primary-500 hover:shadow-md transition-all cursor-pointer"
            :class="{ 'border-primary-500 bg-primary-50': selectedBank === 'manual' }"
            @click="selectBank('manual')"
          >
            <div class="flex flex-col items-center">
              <div class="h-16 w-16 mb-4 flex items-center justify-center bg-gray-100 rounded-full">
                <PlusCircleIcon class="h-10 w-10 text-gray-500" />
              </div>
              <h4 class="text-md font-semibold text-gray-900 mb-2">
                {{ $t('banking.manual_account') }}
              </h4>
              <p class="text-sm text-gray-500 text-center">
                {{ $t('banking.manual_account_description') }}
              </p>
            </div>
          </div>

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

          <!-- Komercijalna Banka -->
          <div
            class="border-2 border-gray-200 rounded-lg p-6 hover:border-primary-500 hover:shadow-md transition-all cursor-pointer"
            :class="{ 'border-primary-500 bg-primary-50': selectedBank === 'komercijalna' }"
            @click="selectBank('komercijalna')"
          >
            <div class="flex flex-col items-center">
              <img
                src="/images/banks/komercijalna-logo.png"
                alt="Komercijalna Banka"
                class="h-16 w-auto mb-4"
                @error="handleImageError"
              />
              <h4 class="text-md font-semibold text-gray-900 mb-2">
                {{ $t('banking.komercijalna') }}
              </h4>
              <p class="text-sm text-gray-500 text-center">
                {{ $t('banking.komercijalna_description') }}
              </p>
            </div>
          </div>
        </div>

        <!-- OAuth Flow Information (only for PSD2 banks) -->
        <div v-if="selectedBank && selectedBank !== 'manual'" class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
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
      </template>

      <!-- Manual Account Form -->
      <template v-if="mode === 'manual'">
        <p class="text-sm text-gray-600 mb-6">
          {{ $t('banking.manual_account_hint') }}
        </p>

        <div class="space-y-4">
          <BaseInputGroup :label="$t('banking.bank_name')" required :error="formErrors.bank_name">
            <BaseInput
              v-model="manualForm.bank_name"
              :placeholder="$t('banking.bank_name_placeholder')"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('banking.account_number')" required :error="formErrors.account_number">
            <BaseInput
              v-model="manualForm.account_number"
              :placeholder="$t('banking.account_number_placeholder')"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('banking.iban_label')" :error="formErrors.iban">
            <BaseInput
              v-model="manualForm.iban"
              :placeholder="$t('banking.iban_placeholder')"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('banking.opening_balance')">
            <BaseInput
              v-model="manualForm.opening_balance"
              type="number"
              step="0.01"
              placeholder="0.00"
            />
          </BaseInputGroup>
        </div>

        <!-- Error Message -->
        <div v-if="errorMessage" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
          <div class="flex items-start">
            <BaseIcon name="ExclamationTriangleIcon" class="h-5 w-5 text-red-600 mt-0.5 mr-3" />
            <p class="text-sm text-red-800">{{ errorMessage }}</p>
          </div>
        </div>
      </template>
    </div>

    <template #footer>
      <div class="flex justify-end space-x-3">
        <BaseButton
          v-if="mode === 'manual'"
          variant="secondary"
          @click="mode = 'select'; errorMessage = null"
        >
          {{ $t('general.back') }}
        </BaseButton>
        <BaseButton
          variant="secondary"
          @click="closeModal"
        >
          {{ $t('general.cancel') }}
        </BaseButton>
        <BaseButton
          variant="primary"
          :disabled="!canProceed || isConnecting"
          :loading="isConnecting"
          @click="handleProceed"
        >
          {{ mode === 'manual' ? $t('banking.add_account') : $t('banking.connect') }}
        </BaseButton>
      </div>
    </template>
  </BaseModal>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'
import axios from 'axios'
import { PlusCircleIcon } from '@heroicons/vue/24/outline'

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
const mode = ref('select') // 'select' or 'manual'
const selectedBank = ref(null)
const isConnecting = ref(false)
const errorMessage = ref(null)
const formErrors = ref({})

const manualForm = ref({
  bank_name: '',
  account_number: '',
  iban: '',
  opening_balance: 0,
})

// Computed
const canProceed = computed(() => {
  if (mode.value === 'manual') {
    return manualForm.value.bank_name && manualForm.value.account_number
  }
  return selectedBank.value
})

// Methods
const selectBank = (bankCode) => {
  selectedBank.value = bankCode
  errorMessage.value = null

  if (bankCode === 'manual') {
    mode.value = 'manual'
  }
}

const handleProceed = () => {
  if (mode.value === 'manual') {
    createManualAccount()
  } else {
    startOAuthFlow()
  }
}

const createManualAccount = async () => {
  isConnecting.value = true
  errorMessage.value = null
  formErrors.value = {}

  try {
    await axios.post('/banking/accounts', {
      bank_name: manualForm.value.bank_name,
      account_number: manualForm.value.account_number,
      iban: manualForm.value.iban || null,
      currency: 'MKD',
      opening_balance: manualForm.value.opening_balance || 0,
    })

    emit('connected')
    resetState()
  } catch (error) {
    if (error.response?.status === 422 && error.response?.data?.errors) {
      formErrors.value = {}
      for (const [field, messages] of Object.entries(error.response.data.errors)) {
        formErrors.value[field] = messages[0]
      }
    } else {
      errorMessage.value = error.response?.data?.message || t('banking.failed_to_create_account')
    }
  } finally {
    isConnecting.value = false
  }
}

const startOAuthFlow = async () => {
  if (!selectedBank.value) return

  isConnecting.value = true
  errorMessage.value = null

  try {
    const response = await axios.get('/banking/oauth/start', {
      params: {
        provider: selectedBank.value
      }
    })

    if (response.data.authorization_url) {
      window.location.href = response.data.authorization_url
    } else {
      throw new Error('No authorization URL returned')
    }
  } catch (error) {
    errorMessage.value = error.response?.data?.message || t('banking.oauth_failed')
    isConnecting.value = false
  }
}

const resetState = () => {
  mode.value = 'select'
  selectedBank.value = null
  errorMessage.value = null
  formErrors.value = {}
  isConnecting.value = false
  manualForm.value = {
    bank_name: '',
    account_number: '',
    iban: '',
    opening_balance: 0,
  }
}

const closeModal = () => {
  emit('update:modelValue', false)
  resetState()
}

const handleImageError = (event) => {
  event.target.style.display = 'none'
}
</script>

<!-- CLAUDE-CHECKPOINT -->
