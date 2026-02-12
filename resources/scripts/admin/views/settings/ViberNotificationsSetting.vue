<template>
  <BaseSettingCard
    title="Viber Notifications — Platform Config"
    description="Configure Viber Business API credentials. These are global platform settings — tenants opt-in from their own Notifications page."
  >
    <div class="grid-cols-2 col-span-1 mt-14">
      <!-- Master toggle -->
      <BaseSwitchSection
        v-model="viberEnabled"
        title="Enable Viber Platform-Wide"
        description="When enabled, tenants can opt-in to receive Viber notifications from their Notifications settings."
      />

      <BaseDivider class="mt-4 mb-4" />

      <template v-if="viberEnabled">
        <!-- Auth Token -->
        <BaseInputGroup
          label="Viber Auth Token"
          class="my-2"
        >
          <BaseInput
            v-model="settingsForm.viber_auth_token"
            :type="showToken ? 'text' : 'password'"
            placeholder="Paste your Viber Business API auth token"
          />
          <button
            type="button"
            class="mt-1 text-sm text-indigo-600 hover:text-indigo-800"
            @click="showToken = !showToken"
          >
            {{ showToken ? 'Hide' : 'Show' }} token
          </button>
        </BaseInputGroup>

        <!-- Sender Name -->
        <BaseInputGroup
          label="Sender Name"
          class="my-2"
        >
          <BaseInput
            v-model="settingsForm.viber_sender_name"
            type="text"
            placeholder="Facturino"
          />
          <p class="mt-1 text-xs text-gray-500">
            This name appears as the sender for all Viber messages.
          </p>
        </BaseInputGroup>

        <BaseButton
          :disabled="isTesting"
          :loading="isTesting"
          variant="primary-outline"
          class="mt-4 mb-6"
          @click="testConnection"
        >
          <template #left="slotProps">
            <BaseIcon
              v-if="!isTesting"
              :class="slotProps.class"
              name="SignalIcon"
            />
          </template>
          Test Connection
        </BaseButton>

        <div
          v-if="connectionStatus"
          class="mb-4 p-3 rounded-lg text-sm"
          :class="connectionStatus.success ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'"
        >
          {{ connectionStatus.success ? 'Connected: ' + connectionStatus.account_name : 'Error: ' + connectionStatus.error }}
        </div>

        <BaseDivider class="mt-2 mb-4" />

        <h6 class="text-sm font-semibold text-gray-700 mb-3">Available Notification Events</h6>
        <p class="text-xs text-gray-500 mb-4">
          These events will be available for tenants to opt-in. Disabling an event here disables it globally.
        </p>

        <ul class="divide-y divide-gray-200">
          <BaseSwitchSection
            v-model="invoiceSentField"
            title="Invoice Sent"
            description="Notify customer when an invoice is delivered."
          />

          <BaseSwitchSection
            v-model="paymentReceivedField"
            title="Payment Received"
            description="Confirm payment receipt to customer."
          />

          <BaseSwitchSection
            v-model="overdueReminderField"
            title="Overdue Reminder"
            description="Send reminder when invoice is past due."
          />
        </ul>

        <BaseInputGroup
          v-if="overdueReminderField"
          label="Default reminder days"
          class="my-2 max-w-xs"
        >
          <BaseMultiselect
            v-model="settingsForm.viber_overdue_days"
            :options="[
              { label: '7 days', value: '7' },
              { label: '14 days', value: '14' },
              { label: '30 days', value: '30' },
            ]"
            value-prop="value"
            label="label"
            :can-deselect="false"
          />
        </BaseInputGroup>
      </template>

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
        Save Platform Settings
      </BaseButton>
    </div>
  </BaseSettingCard>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { useNotificationStore } from '@/scripts/stores/notification'
import axios from 'axios'

const notificationStore = useNotificationStore()

const isSaving = ref(false)
const isTesting = ref(false)
const showToken = ref(false)
const connectionStatus = ref(null)
const isLoading = ref(true)

const settingsForm = reactive({
  viber_platform_enabled: 'NO',
  viber_auth_token: '',
  viber_sender_name: 'Facturino',
  viber_allow_invoice_sent: 'YES',
  viber_allow_payment_received: 'YES',
  viber_allow_overdue_reminder: 'YES',
  viber_overdue_days: '7',
})

// Load global settings on mount
loadGlobalSettings()

async function loadGlobalSettings() {
  try {
    const { data } = await axios.get('/api/v1/admin/viber/settings')
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

const viberEnabled = computed({
  get: () => settingsForm.viber_platform_enabled === 'YES',
  set: (val) => { settingsForm.viber_platform_enabled = val ? 'YES' : 'NO' },
})

const invoiceSentField = computed({
  get: () => settingsForm.viber_allow_invoice_sent === 'YES',
  set: (val) => { settingsForm.viber_allow_invoice_sent = val ? 'YES' : 'NO' },
})

const paymentReceivedField = computed({
  get: () => settingsForm.viber_allow_payment_received === 'YES',
  set: (val) => { settingsForm.viber_allow_payment_received = val ? 'YES' : 'NO' },
})

const overdueReminderField = computed({
  get: () => settingsForm.viber_allow_overdue_reminder === 'YES',
  set: (val) => { settingsForm.viber_allow_overdue_reminder = val ? 'YES' : 'NO' },
})

async function testConnection() {
  isTesting.value = true
  connectionStatus.value = null

  try {
    const { data } = await axios.post('/api/v1/admin/viber/test-connection', {
      auth_token: settingsForm.viber_auth_token,
    })
    connectionStatus.value = data
  } catch (e) {
    connectionStatus.value = { success: false, error: e.response?.data?.error || e.message }
  } finally {
    isTesting.value = false
  }
}

async function saveSettings() {
  isSaving.value = true

  try {
    await axios.post('/api/v1/admin/viber/settings', settingsForm)
    notificationStore.showNotification({
      type: 'success',
      message: 'Viber platform settings saved.',
    })
  } catch (e) {
    notificationStore.showNotification({
      type: 'error',
      message: e.response?.data?.message || 'Failed to save settings.',
    })
  } finally {
    isSaving.value = false
  }
}
</script>
