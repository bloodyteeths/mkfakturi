<template>
  <BaseSettingCard
    title="WooCommerce Integration"
    description="Sync orders from your WooCommerce store into Facturino invoices."
  >
    <div class="grid-cols-2 col-span-1 mt-14">
      <!-- Connection -->
      <h6 class="text-sm font-semibold text-gray-700 mb-3">Connection</h6>

      <BaseInputGroup label="Store URL" class="my-2" required>
        <BaseInput
          v-model="settingsForm.store_url"
          type="url"
          placeholder="https://myshop.mk"
        />
      </BaseInputGroup>

      <BaseInputGroup label="Consumer Key" class="my-2" required>
        <BaseInput
          v-model="settingsForm.consumer_key"
          type="text"
          placeholder="ck_..."
        />
      </BaseInputGroup>

      <BaseInputGroup label="Consumer Secret" class="my-2" required>
        <BaseInput
          v-model="settingsForm.consumer_secret"
          :type="showSecret ? 'text' : 'password'"
          placeholder="cs_..."
        />
        <button
          type="button"
          class="mt-1 text-sm text-indigo-600 hover:text-indigo-800"
          @click="showSecret = !showSecret"
        >
          {{ showSecret ? 'Hide' : 'Show' }}
        </button>
      </BaseInputGroup>

      <div class="flex items-center gap-3 mt-4 mb-6">
        <BaseButton
          :disabled="isTesting"
          :loading="isTesting"
          variant="primary-outline"
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
          class="p-2 rounded-lg text-sm"
          :class="connectionStatus.success ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'"
        >
          {{ connectionStatus.success ? 'Connected: ' + (connectionStatus.store_name || 'OK') : 'Error: ' + connectionStatus.error }}
        </div>
      </div>

      <BaseDivider class="mt-2 mb-4" />

      <!-- Sync Settings -->
      <h6 class="text-sm font-semibold text-gray-700 mb-3">Sync Settings</h6>

      <ul class="divide-y divide-gray-200 mb-4">
        <BaseSwitchSection
          v-model="autoSyncField"
          title="Auto-sync"
          description="Automatically sync new orders on a schedule."
        />
      </ul>

      <BaseInputGroup
        v-if="autoSyncField"
        label="Sync Frequency"
        class="my-2 max-w-xs"
      >
        <BaseMultiselect
          v-model="settingsForm.sync_frequency"
          :options="[
            { label: 'Every 15 minutes', value: '15' },
            { label: 'Every hour', value: '60' },
            { label: 'Every 4 hours', value: '240' },
          ]"
          value-prop="value"
          label="label"
          :can-deselect="false"
        />
      </BaseInputGroup>

      <BaseDivider class="mt-4 mb-4" />

      <!-- Tax Mapping -->
      <h6 class="text-sm font-semibold text-gray-700 mb-3">Tax Mapping</h6>

      <div class="grid grid-cols-2 gap-4 mb-4">
        <BaseInputGroup label="WC 'Standard' tax">
          <BaseMultiselect
            v-model="settingsForm.tax_mapping_standard"
            :options="taxOptions"
            value-prop="value"
            label="label"
            placeholder="Select tax type"
          />
        </BaseInputGroup>

        <BaseInputGroup label="WC 'Reduced' tax">
          <BaseMultiselect
            v-model="settingsForm.tax_mapping_reduced"
            :options="taxOptions"
            value-prop="value"
            label="label"
            placeholder="Select tax type"
          />
        </BaseInputGroup>
      </div>

      <div class="flex items-center gap-3 mt-6">
        <BaseButton
          :disabled="isSaving"
          :loading="isSaving"
          variant="primary"
          @click="saveSettings"
        >
          <template #left="slotProps">
            <BaseIcon
              v-if="!isSaving"
              :class="slotProps.class"
              name="ArrowDownOnSquareIcon"
            />
          </template>
          Save Settings
        </BaseButton>

        <BaseButton
          :disabled="isSyncing"
          :loading="isSyncing"
          variant="primary-outline"
          @click="syncNow"
        >
          <template #left="slotProps">
            <BaseIcon
              v-if="!isSyncing"
              :class="slotProps.class"
              name="ArrowPathIcon"
            />
          </template>
          Sync Now
        </BaseButton>
      </div>

      <!-- Sync History -->
      <BaseDivider class="mt-6 mb-4" />
      <h6 class="text-sm font-semibold text-gray-700 mb-3">Sync History</h6>

      <div v-if="syncHistory.length === 0" class="text-sm text-gray-500 py-4">
        No sync history yet.
      </div>

      <table v-else class="w-full text-sm">
        <thead>
          <tr class="text-left text-gray-500 border-b">
            <th class="py-2">Time</th>
            <th class="py-2">Orders</th>
            <th class="py-2">Status</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(entry, i) in syncHistory"
            :key="i"
            class="border-b border-gray-100"
          >
            <td class="py-2 text-gray-600">{{ formatDate(entry.created_at) }}</td>
            <td class="py-2 text-gray-700">{{ entry.synced_count }}</td>
            <td class="py-2">
              <span
                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                :class="entry.status === 'success' ? 'bg-green-50 text-green-700' : 'bg-yellow-50 text-yellow-700'"
              >
                {{ entry.status === 'success' ? 'Success' : entry.error_count + ' errors' }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </BaseSettingCard>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useNotificationStore } from '@/scripts/stores/notification'
import axios from 'axios'

const notificationStore = useNotificationStore()

const isSaving = ref(false)
const isTesting = ref(false)
const isSyncing = ref(false)
const showSecret = ref(false)
const connectionStatus = ref(null)
const syncHistory = ref([])

const settingsForm = reactive({
  store_url: '',
  consumer_key: '',
  consumer_secret: '',
  auto_sync: false,
  sync_frequency: '60',
  tax_mapping_standard: null,
  tax_mapping_reduced: null,
})

const autoSyncField = computed({
  get: () => settingsForm.auto_sync,
  set: (val) => { settingsForm.auto_sync = val },
})

const taxOptions = [
  { label: 'DDV 18%', value: '18' },
  { label: 'DDV 5%', value: '5' },
  { label: 'DDV 10%', value: '10' },
  { label: 'No tax (0%)', value: '0' },
]

onMounted(async () => {
  await loadSettings()
  await loadSyncHistory()
})

async function loadSettings() {
  try {
    const { data } = await axios.get('/api/v1/woocommerce/settings')
    const s = data.data || {}
    settingsForm.store_url = s.woocommerce_store_url || ''
    settingsForm.consumer_key = s.woocommerce_consumer_key || ''
    settingsForm.consumer_secret = ''
    settingsForm.auto_sync = s.woocommerce_auto_sync === '1'
    settingsForm.sync_frequency = s.woocommerce_sync_frequency || '60'
  } catch (e) {
    // Settings not yet configured
  }
}

async function loadSyncHistory() {
  try {
    const { data } = await axios.get('/api/v1/woocommerce/sync-history')
    syncHistory.value = data.data || []
  } catch (e) {
    // No history yet
  }
}

async function testConnection() {
  isTesting.value = true
  connectionStatus.value = null

  try {
    const { data } = await axios.post('/api/v1/woocommerce/test-connection', {
      store_url: settingsForm.store_url,
      consumer_key: settingsForm.consumer_key,
      consumer_secret: settingsForm.consumer_secret,
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
    const taxMapping = {}
    if (settingsForm.tax_mapping_standard) taxMapping['standard'] = settingsForm.tax_mapping_standard
    if (settingsForm.tax_mapping_reduced) taxMapping['reduced'] = settingsForm.tax_mapping_reduced

    await axios.post('/api/v1/woocommerce/settings', {
      store_url: settingsForm.store_url,
      consumer_key: settingsForm.consumer_key,
      consumer_secret: settingsForm.consumer_secret,
      auto_sync: settingsForm.auto_sync,
      sync_frequency: settingsForm.sync_frequency,
      tax_mapping: taxMapping,
    })

    notificationStore.showNotification({
      type: 'success',
      message: 'WooCommerce settings saved',
    })
  } catch (e) {
    notificationStore.showNotification({
      type: 'error',
      message: e.response?.data?.message || 'Failed to save settings',
    })
  } finally {
    isSaving.value = false
  }
}

async function syncNow() {
  isSyncing.value = true

  try {
    await axios.post('/api/v1/woocommerce/sync')
    notificationStore.showNotification({
      type: 'success',
      message: 'Sync started. Check back in a moment.',
    })

    // Refresh history after a short delay
    setTimeout(() => loadSyncHistory(), 3000)
  } catch (e) {
    notificationStore.showNotification({
      type: 'error',
      message: e.response?.data?.message || 'Failed to start sync',
    })
  } finally {
    isSyncing.value = false
  }
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleString()
}
</script>
