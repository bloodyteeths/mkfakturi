<template>
  <BaseSettingCard
    title="E-Faktura Settings"
    description="Configure the e-invoice submission mode and UJP portal/API credentials for the Macedonian tax authority."
  >
    <div class="grid-cols-2 col-span-1 mt-14">
      <!-- Submission Mode Toggle -->
      <BaseInputGroup
        label="Submission Mode"
        class="my-2"
      >
        <BaseMultiselect
          v-model="settingsForm.efaktura_mode"
          :options="modeOptions"
          value-prop="value"
          label="label"
          :can-deselect="false"
        />
        <p class="mt-1 text-xs text-gray-500">
          <strong>Portal:</strong> Submit via web portal scraping (legacy).
          <strong>API:</strong> Submit via official UJP REST API (recommended when available).
        </p>
      </BaseInputGroup>

      <BaseDivider class="mt-4 mb-4" />

      <!-- Environment -->
      <BaseInputGroup
        label="Environment"
        class="my-2"
      >
        <BaseMultiselect
          v-model="settingsForm.efaktura_environment"
          :options="environmentOptions"
          value-prop="value"
          label="label"
          :can-deselect="false"
        />
        <p class="mt-1 text-xs text-gray-500">
          Use <strong>Sandbox</strong> for testing. Switch to <strong>Production</strong> for live submissions.
        </p>
      </BaseInputGroup>

      <BaseDivider class="mt-4 mb-4" />

      <!-- Portal URL -->
      <BaseInputGroup
        label="Portal / API Base URL"
        class="my-2"
      >
        <BaseInput
          v-model="settingsForm.efaktura_portal_url"
          type="text"
          placeholder="https://e-ujp.ujp.gov.mk"
        />
      </BaseInputGroup>

      <!-- Portal Credentials Section (shown in portal mode or always for fallback) -->
      <h6 class="text-sm font-semibold text-gray-700 mt-6 mb-3">Portal Credentials</h6>
      <p class="text-xs text-gray-500 mb-4">
        Username and password for the UJP e-Faktura web portal. Required for portal mode.
      </p>

      <BaseInputGroup
        label="Username"
        class="my-2"
      >
        <BaseInput
          v-model="settingsForm.efaktura_username"
          type="text"
          placeholder="Portal username"
        />
      </BaseInputGroup>

      <BaseInputGroup
        label="Password"
        class="my-2"
      >
        <BaseInput
          v-model="settingsForm.efaktura_password"
          :type="showPassword ? 'text' : 'password'"
          placeholder="Portal password"
        />
        <button
          type="button"
          class="mt-1 text-sm text-indigo-600 hover:text-indigo-800"
          @click="showPassword = !showPassword"
        >
          {{ showPassword ? 'Hide' : 'Show' }} password
        </button>
      </BaseInputGroup>

      <!-- API Credentials Section (shown when API mode selected) -->
      <template v-if="settingsForm.efaktura_mode === 'api'">
        <BaseDivider class="mt-4 mb-4" />

        <h6 class="text-sm font-semibold text-gray-700 mb-3">API Credentials</h6>
        <p class="text-xs text-gray-500 mb-4">
          API key and secret for the UJP REST API. Required for API mode.
        </p>

        <BaseInputGroup
          label="API Key"
          class="my-2"
        >
          <BaseInput
            v-model="settingsForm.efaktura_api_key"
            :type="showApiKey ? 'text' : 'password'"
            placeholder="API key"
          />
          <button
            type="button"
            class="mt-1 text-sm text-indigo-600 hover:text-indigo-800"
            @click="showApiKey = !showApiKey"
          >
            {{ showApiKey ? 'Hide' : 'Show' }} API key
          </button>
        </BaseInputGroup>

        <BaseInputGroup
          label="API Secret"
          class="my-2"
        >
          <BaseInput
            v-model="settingsForm.efaktura_api_secret"
            :type="showApiSecret ? 'text' : 'password'"
            placeholder="API secret"
          />
          <button
            type="button"
            class="mt-1 text-sm text-indigo-600 hover:text-indigo-800"
            @click="showApiSecret = !showApiSecret"
          >
            {{ showApiSecret ? 'Hide' : 'Show' }} API secret
          </button>
        </BaseInputGroup>
      </template>

      <BaseDivider class="mt-4 mb-4" />

      <!-- Timeout -->
      <BaseInputGroup
        label="Request Timeout (seconds)"
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
        label="Max Retries"
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
        <strong>Current mode:</strong>
        {{ settingsForm.efaktura_mode === 'api' ? 'API (REST) — uses UjpApiClient' : 'Portal (scraping) — uses UjpPortalClient' }}
        &middot;
        <strong>Environment:</strong>
        {{ settingsForm.efaktura_environment === 'sandbox' ? 'Sandbox (test)' : 'Production (live)' }}
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
        Save E-Faktura Settings
      </BaseButton>
    </div>
  </BaseSettingCard>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useNotificationStore } from '@/scripts/stores/notification'
import axios from 'axios'

const notificationStore = useNotificationStore()

const isSaving = ref(false)
const isLoading = ref(true)
const showPassword = ref(false)
const showApiKey = ref(false)
const showApiSecret = ref(false)

const modeOptions = [
  { label: 'Portal (web scraping)', value: 'portal' },
  { label: 'API (REST — recommended)', value: 'api' },
]

const environmentOptions = [
  { label: 'Production (live submissions)', value: 'production' },
  { label: 'Sandbox (test submissions)', value: 'sandbox' },
]

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
      message: 'E-Faktura settings saved.',
    })
  } catch (e) {
    notificationStore.showNotification({
      type: 'error',
      message: e.response?.data?.message || 'Failed to save E-Faktura settings.',
    })
  } finally {
    isSaving.value = false
  }
}
</script>
<!-- CLAUDE-CHECKPOINT -->
