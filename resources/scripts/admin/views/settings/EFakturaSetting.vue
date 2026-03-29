<template>
  <BaseSettingCard
    :title="$t('settings.efaktura.title')"
    :description="$t('settings.efaktura.description')"
  >
    <div class="grid-cols-2 col-span-1 mt-14">
      <!-- Submission Mode Toggle -->
      <BaseInputGroup
        :label="$t('settings.efaktura.submission_mode')"
        class="my-2"
      >
        <BaseMultiselect
          v-model="settingsForm.efaktura_mode"
          :options="modeOptions"
          value-prop="value"
          label="label"
          :can-deselect="false"
        />
        <p class="mt-1 text-xs text-gray-500" v-html="$t('settings.efaktura.mode_help')" />
      </BaseInputGroup>

      <BaseDivider class="mt-4 mb-4" />

      <!-- Environment -->
      <BaseInputGroup
        :label="$t('settings.efaktura.environment')"
        class="my-2"
      >
        <BaseMultiselect
          v-model="settingsForm.efaktura_environment"
          :options="environmentOptions"
          value-prop="value"
          label="label"
          :can-deselect="false"
        />
        <p class="mt-1 text-xs text-gray-500" v-html="$t('settings.efaktura.environment_help')" />
      </BaseInputGroup>

      <BaseDivider class="mt-4 mb-4" />

      <!-- Portal URL -->
      <BaseInputGroup
        :label="$t('settings.efaktura.base_url')"
        class="my-2"
      >
        <BaseInput
          v-model="settingsForm.efaktura_portal_url"
          type="text"
          placeholder="https://e-ujp.ujp.gov.mk"
        />
      </BaseInputGroup>

      <!-- Portal Credentials Section (shown in portal mode or always for fallback) -->
      <h6 class="text-sm font-semibold text-gray-700 mt-6 mb-3">{{ $t('settings.efaktura.portal_credentials') }}</h6>
      <p class="text-xs text-gray-500 mb-4">
        {{ $t('settings.efaktura.portal_credentials_help') }}
      </p>

      <BaseInputGroup
        :label="$t('settings.efaktura.username')"
        class="my-2"
      >
        <BaseInput
          v-model="settingsForm.efaktura_username"
          type="text"
          :placeholder="$t('settings.efaktura.username_placeholder')"
        />
      </BaseInputGroup>

      <BaseInputGroup
        :label="$t('settings.efaktura.password')"
        class="my-2"
      >
        <BaseInput
          v-model="settingsForm.efaktura_password"
          :type="showPassword ? 'text' : 'password'"
          :placeholder="$t('settings.efaktura.password_placeholder')"
        />
        <button
          type="button"
          class="mt-1 text-sm text-indigo-600 hover:text-indigo-800"
          @click="showPassword = !showPassword"
        >
          {{ showPassword ? $t('settings.efaktura.hide_password') : $t('settings.efaktura.show_password') }}
        </button>
      </BaseInputGroup>

      <!-- API Credentials Section (shown when API mode selected) -->
      <template v-if="settingsForm.efaktura_mode === 'api'">
        <BaseDivider class="mt-4 mb-4" />

        <h6 class="text-sm font-semibold text-gray-700 mb-3">{{ $t('settings.efaktura.api_credentials') }}</h6>
        <p class="text-xs text-gray-500 mb-4">
          {{ $t('settings.efaktura.api_credentials_help') }}
        </p>

        <BaseInputGroup
          :label="$t('settings.efaktura.api_key')"
          class="my-2"
        >
          <BaseInput
            v-model="settingsForm.efaktura_api_key"
            :type="showApiKey ? 'text' : 'password'"
            :placeholder="$t('settings.efaktura.api_key_placeholder')"
          />
          <button
            type="button"
            class="mt-1 text-sm text-indigo-600 hover:text-indigo-800"
            @click="showApiKey = !showApiKey"
          >
            {{ showApiKey ? $t('settings.efaktura.hide_api_key') : $t('settings.efaktura.show_api_key') }}
          </button>
        </BaseInputGroup>

        <BaseInputGroup
          :label="$t('settings.efaktura.api_secret')"
          class="my-2"
        >
          <BaseInput
            v-model="settingsForm.efaktura_api_secret"
            :type="showApiSecret ? 'text' : 'password'"
            :placeholder="$t('settings.efaktura.api_secret_placeholder')"
          />
          <button
            type="button"
            class="mt-1 text-sm text-indigo-600 hover:text-indigo-800"
            @click="showApiSecret = !showApiSecret"
          >
            {{ showApiSecret ? $t('settings.efaktura.hide_api_secret') : $t('settings.efaktura.show_api_secret') }}
          </button>
        </BaseInputGroup>
      </template>

      <BaseDivider class="mt-4 mb-4" />

      <!-- Timeout -->
      <BaseInputGroup
        :label="$t('settings.efaktura.timeout')"
        class="my-2 max-w-xs"
      >
        <BaseInput
          v-model="settingsForm.efaktura_timeout"
          type="number"
          placeholder="30"
          min="10"
          max="120"
        />
      </BaseInputGroup>

      <!-- Max Retries -->
      <BaseInputGroup
        :label="$t('settings.efaktura.max_retries')"
        class="my-2 max-w-xs"
      >
        <BaseInput
          v-model="settingsForm.efaktura_max_retries"
          type="number"
          placeholder="3"
          min="0"
          max="10"
        />
      </BaseInputGroup>

      <!-- Current mode status indicator -->
      <div
        class="mt-4 p-3 rounded-lg text-sm"
        :class="settingsForm.efaktura_mode === 'api' ? 'bg-blue-50 text-blue-700' : 'bg-amber-50 text-amber-700'"
      >
        <strong>{{ $t('settings.efaktura.current_mode') }}</strong>
        {{ settingsForm.efaktura_mode === 'api' ? $t('settings.efaktura.mode_api_label') : $t('settings.efaktura.mode_portal_label') }}
        &middot;
        <strong>{{ $t('settings.efaktura.environment_label') }}</strong>
        {{ settingsForm.efaktura_environment === 'sandbox' ? $t('settings.efaktura.env_sandbox') : $t('settings.efaktura.env_production') }}
      </div>

      <BaseButton
        :disabled="isSaving"
        :loading="isSaving"
        variant="primary"
        class="mt-6"
        @click="saveSettings"
      >
        <template #left="slotProps">
          <BaseIcon
            v-if="!isSaving"
            :class="slotProps.class"
            name="ArrowDownOnSquareIcon"
          />
        </template>
        {{ $t('settings.efaktura.save') }}
      </BaseButton>
    </div>
  </BaseSettingCard>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'
import axios from 'axios'

const { t } = useI18n()
const notificationStore = useNotificationStore()

const isSaving = ref(false)
const isLoading = ref(true)
const showPassword = ref(false)
const showApiKey = ref(false)
const showApiSecret = ref(false)

const modeOptions = computed(() => [
  { label: t('settings.efaktura.mode_portal'), value: 'portal' },
  { label: t('settings.efaktura.mode_api'), value: 'api' },
])

const environmentOptions = computed(() => [
  { label: t('settings.efaktura.env_production_option'), value: 'production' },
  { label: t('settings.efaktura.env_sandbox_option'), value: 'sandbox' },
])

const settingsForm = reactive({
  efaktura_mode: 'portal',
  efaktura_environment: 'production',
  efaktura_portal_url: 'https://e-ujp.ujp.gov.mk',
  efaktura_username: '',
  efaktura_password: '',
  efaktura_api_key: '',
  efaktura_api_secret: '',
  efaktura_timeout: '30',
  efaktura_max_retries: '3',
})

// Load settings on mount
loadSettings()

async function loadSettings() {
  try {
    const { data } = await axios.get('/api/v1/company/settings', {
      params: {
        settings: [
          'efaktura_mode',
          'efaktura_environment',
          'efaktura_portal_url',
          'efaktura_username',
          'efaktura_password',
          'efaktura_api_key',
          'efaktura_api_secret',
          'efaktura_timeout',
          'efaktura_max_retries',
        ],
      },
    })
    if (data.data) {
      Object.keys(settingsForm).forEach((key) => {
        if (data.data[key] !== undefined && data.data[key] !== null) {
          settingsForm[key] = data.data[key]
        }
      })
    }
  } catch (e) {
    // First time — defaults are fine
  } finally {
    isLoading.value = false
  }
}

async function saveSettings() {
  isSaving.value = true

  try {
    await axios.post('/api/v1/company/settings', {
      settings: { ...settingsForm },
    })
    notificationStore.showNotification({
      type: 'success',
      message: t('settings.efaktura.saved'),
    })
  } catch (e) {
    notificationStore.showNotification({
      type: 'error',
      message: e.response?.data?.message || t('settings.efaktura.save_failed'),
    })
  } finally {
    isSaving.value = false
  }
}
</script>
<!-- CLAUDE-CHECKPOINT -->
