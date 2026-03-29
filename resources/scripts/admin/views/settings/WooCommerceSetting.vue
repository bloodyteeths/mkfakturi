<template>
  <BaseSettingCard
    :title="$t('settings.woocommerce.title')"
    :description="$t('settings.woocommerce.description')"
  >
    <div class="grid-cols-2 col-span-1 mt-14">
      <!-- Connection -->
      <h6 class="text-sm font-semibold text-gray-700 mb-3">{{ $t('settings.woocommerce.connection') }}</h6>

      <BaseInputGroup :label="$t('settings.woocommerce.store_url')" class="my-2" required>
        <BaseInput
          v-model="settingsForm.store_url"
          type="url"
          placeholder="https://myshop.mk"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('settings.woocommerce.consumer_key')" class="my-2" required>
        <BaseInput
          v-model="settingsForm.consumer_key"
          type="text"
          placeholder="ck_..."
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('settings.woocommerce.consumer_secret')" class="my-2" required>
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
          {{ showSecret ? $t('settings.woocommerce.hide') : $t('settings.woocommerce.show') }}
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
          {{ $t('settings.woocommerce.test_connection') }}
        </BaseButton>

        <div
          v-if="connectionStatus"
          class="p-2 rounded-lg text-sm"
          :class="connectionStatus.success ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'"
        >
          {{ connectionStatus.success ? $t('settings.woocommerce.connected') + (connectionStatus.store_name || 'OK') : $t('settings.woocommerce.error') + connectionStatus.error }}
        </div>
      </div>

      <BaseDivider class="mt-2 mb-4" />

      <!-- Sync Settings -->
      <h6 class="text-sm font-semibold text-gray-700 mb-3">{{ $t('settings.woocommerce.sync_settings') }}</h6>

      <ul class="divide-y divide-gray-200 mb-4">
        <BaseSwitchSection
          v-model="autoSyncField"
          :title="$t('settings.woocommerce.auto_sync')"
          :description="$t('settings.woocommerce.auto_sync_desc')"
        />
      </ul>

      <BaseInputGroup
        v-if="autoSyncField"
        :label="$t('settings.woocommerce.sync_frequency')"
        class="my-2 max-w-xs"
      >
        <BaseMultiselect
          v-model="settingsForm.sync_frequency"
          :options="frequencyOptions"
          value-prop="value"
          label="label"
          :can-deselect="false"
        />
      </BaseInputGroup>

      <BaseDivider class="mt-4 mb-4" />

      <!-- Tax Mapping -->
      <h6 class="text-sm font-semibold text-gray-700 mb-3">{{ $t('settings.woocommerce.tax_mapping') }}</h6>

      <div class="grid grid-cols-2 gap-4 mb-4">
        <BaseInputGroup :label="$t('settings.woocommerce.tax_standard')">
          <BaseMultiselect
            v-model="settingsForm.tax_mapping_standard"
            :options="taxOptions"
            value-prop="value"
            label="label"
            :placeholder="$t('settings.woocommerce.tax_select')"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('settings.woocommerce.tax_reduced')">
          <BaseMultiselect
            v-model="settingsForm.tax_mapping_reduced"
            :options="taxOptions"
            value-prop="value"
            label="label"
            :placeholder="$t('settings.woocommerce.tax_select')"
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
          {{ $t('settings.woocommerce.save') }}
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
          {{ $t('settings.woocommerce.sync_now') }}
        </BaseButton>
      </div>

      <!-- Sync History -->
      <BaseDivider class="mt-6 mb-4" />
      <h6 class="text-sm font-semibold text-gray-700 mb-3">{{ $t('settings.woocommerce.sync_history') }}</h6>

      <div v-if="syncHistory.length === 0" class="text-sm text-gray-500 py-4">
        {{ $t('settings.woocommerce.no_history') }}
      </div>

      <table v-else class="w-full text-sm">
        <thead>
          <tr class="text-left text-gray-500 border-b">
            <th class="py-2">{{ $t('settings.woocommerce.time') }}</th>
            <th class="py-2">{{ $t('settings.woocommerce.orders') }}</th>
            <th class="py-2">{{ $t('settings.woocommerce.status') }}</th>
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
                {{ entry.status === 'success' ? $t('settings.woocommerce.status_success') : $t('settings.woocommerce.status_errors', { count: entry.error_count }) }}
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
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'
import axios from 'axios'

const { t } = useI18n()
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

const frequencyOptions = computed(() => [
  { label: t('settings.woocommerce.freq_15'), value: '15' },
  { label: t('settings.woocommerce.freq_60'), value: '60' },
  { label: t('settings.woocommerce.freq_240'), value: '240' },
])

const taxOptions = computed(() => [
  { label: t('settings.woocommerce.tax_18'), value: '18' },
  { label: t('settings.woocommerce.tax_5'), value: '5' },
  { label: t('settings.woocommerce.tax_10'), value: '10' },
  { label: t('settings.woocommerce.tax_0'), value: '0' },
])

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
      message: t('settings.woocommerce.saved'),
    })
  } catch (e) {
    notificationStore.showNotification({
      type: 'error',
      message: e.response?.data?.message || t('settings.woocommerce.save_failed'),
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
      message: t('settings.woocommerce.sync_started'),
    })

    // Refresh history after a short delay
    setTimeout(() => loadSyncHistory(), 3000)
  } catch (e) {
    notificationStore.showNotification({
      type: 'error',
      message: e.response?.data?.message || t('settings.woocommerce.sync_failed'),
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
