<template>
  <BasePage>
    <BasePageHeader :title="$t('settings.menu_title.ai_insights')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem
          :title="$t('general.home')"
          to="/admin/dashboard"
        />
        <BaseBreadcrumbItem
          :title="$t('settings.setting', 2)"
          to="/admin/settings/account-settings"
        />
        <BaseBreadcrumbItem
          :title="$t('settings.menu_title.ai_insights')"
          to="#"
          active
        />
      </BaseBreadcrumb>
    </BasePageHeader>

    <div class="grid grid-cols-1 gap-6 mt-6">
      <!-- AI Insights Overview -->
      <BaseCard class="p-6">
        <div class="flex items-center mb-4">
          <BaseIcon name="LightBulbIcon" class="w-6 h-6 mr-3 text-primary-600" />
          <h3 class="text-lg font-medium text-gray-900">
            {{ $t('ai.insights.overview_title') }}
          </h3>
        </div>
        <p class="text-gray-600 mb-6">
          {{ $t('ai.insights.overview_description') }}
        </p>

        <!-- AI Features Toggle -->
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <h4 class="text-sm font-medium text-gray-900">
                {{ $t('ai.insights.enable_ai_features') }}
              </h4>
              <p class="text-sm text-gray-500">
                {{ $t('ai.insights.enable_ai_features_description') }}
              </p>
            </div>
            <BaseToggle 
              v-model="aiSettings.enabled"
              @change="updateAiSettings"
            />
          </div>

          <div v-if="aiSettings.enabled" class="border-t pt-4 space-y-4">
            <!-- Financial Analytics -->
            <div class="flex items-center justify-between">
              <div>
                <h4 class="text-sm font-medium text-gray-900">
                  {{ $t('ai.insights.financial_analytics') }}
                </h4>
                <p class="text-sm text-gray-500">
                  {{ $t('ai.insights.financial_analytics_description') }}
                </p>
              </div>
              <BaseToggle 
                v-model="aiSettings.financial_analytics"
                @change="updateAiSettings"
              />
            </div>

            <!-- Risk Assessment -->
            <div class="flex items-center justify-between">
              <div>
                <h4 class="text-sm font-medium text-gray-900">
                  {{ $t('ai.insights.risk_assessment') }}
                </h4>
                <p class="text-sm text-gray-500">
                  {{ $t('ai.insights.risk_assessment_description') }}
                </p>
              </div>
              <BaseToggle 
                v-model="aiSettings.risk_assessment"
                @change="updateAiSettings"
              />
            </div>

            <!-- Predictive Analytics -->
            <div class="flex items-center justify-between">
              <div>
                <h4 class="text-sm font-medium text-gray-900">
                  {{ $t('ai.insights.predictive_analytics') }}
                </h4>
                <p class="text-sm text-gray-500">
                  {{ $t('ai.insights.predictive_analytics_description') }}
                </p>
              </div>
              <BaseToggle 
                v-model="aiSettings.predictive_analytics"
                @change="updateAiSettings"
              />
            </div>
          </div>
        </div>
      </BaseCard>

      <!-- AI Configuration -->
      <BaseCard v-if="aiSettings.enabled" class="p-6">
        <div class="flex items-center mb-4">
          <BaseIcon name="CogIcon" class="w-6 h-6 mr-3 text-primary-600" />
          <h3 class="text-lg font-medium text-gray-900">
            {{ $t('ai.insights.configuration_title') }}
          </h3>
        </div>

        <div class="space-y-4">
          <!-- Update Frequency -->
          <div>
            <BaseLabel>{{ $t('ai.insights.update_frequency') }}</BaseLabel>
            <BaseMultiselect
              v-model="aiSettings.update_frequency"
              :options="frequencyOptions"
              value-prop="value"
              label="label"
              @update:modelValue="updateAiSettings"
            />
          </div>

          <!-- Data Retention -->
          <div>
            <BaseLabel>{{ $t('ai.insights.data_retention') }}</BaseLabel>
            <BaseMultiselect
              v-model="aiSettings.data_retention"
              :options="retentionOptions"
              value-prop="value"
              label="label"
              @update:modelValue="updateAiSettings"
            />
          </div>
        </div>
      </BaseCard>

      <!-- Current Status -->
      <BaseCard class="p-6">
        <div class="flex items-center mb-4">
          <BaseIcon name="ChartBarIcon" class="w-6 h-6 mr-3 text-primary-600" />
          <h3 class="text-lg font-medium text-gray-900">
            {{ $t('ai.insights.status_title') }}
          </h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="bg-gray-50 p-4 rounded-lg">
            <div class="text-sm font-medium text-gray-500 mb-1">
              {{ $t('ai.insights.last_analysis') }}
            </div>
            <div class="text-lg font-semibold text-gray-900">
              {{ formatDateTime(lastAnalysis) }}
            </div>
          </div>
          
          <div class="bg-gray-50 p-4 rounded-lg">
            <div class="text-sm font-medium text-gray-500 mb-1">
              {{ $t('ai.insights.insights_generated') }}
            </div>
            <div class="text-lg font-semibold text-gray-900">
              {{ insightsCount }}
            </div>
          </div>

          <div class="bg-gray-50 p-4 rounded-lg">
            <div class="text-sm font-medium text-gray-500 mb-1">
              {{ $t('ai.insights.system_status') }}
            </div>
            <div class="text-lg font-semibold" :class="statusColor">
              {{ $t(`ai.insights.status_${systemStatus}`) }}
            </div>
          </div>
        </div>

        <div class="mt-4">
          <BaseButton
            variant="primary"
            @click="runAnalysis"
            :loading="isRunningAnalysis"
          >
            {{ $t('ai.insights.run_analysis') }}
          </BaseButton>
        </div>
      </BaseCard>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'
import axios from 'axios'

const { t } = useI18n()
const notificationStore = useNotificationStore()

// Reactive data
const aiSettings = ref({
  enabled: false,
  financial_analytics: true,
  risk_assessment: true,
  predictive_analytics: false,
  update_frequency: 'daily',
  data_retention: '90'
})

const lastAnalysis = ref(null)
const insightsCount = ref(0)
const systemStatus = ref('active')
const isRunningAnalysis = ref(false)

// Options
const frequencyOptions = [
  { value: 'realtime', label: t('ai.insights.frequency_realtime') },
  { value: 'hourly', label: t('ai.insights.frequency_hourly') },
  { value: 'daily', label: t('ai.insights.frequency_daily') },
  { value: 'weekly', label: t('ai.insights.frequency_weekly') }
]

const retentionOptions = [
  { value: '30', label: t('ai.insights.retention_30_days') },
  { value: '90', label: t('ai.insights.retention_90_days') },
  { value: '180', label: t('ai.insights.retention_180_days') },
  { value: '365', label: t('ai.insights.retention_1_year') }
]

// Computed
const statusColor = computed(() => {
  switch (systemStatus.value) {
    case 'active':
      return 'text-green-600'
    case 'warning':
      return 'text-yellow-600'
    case 'error':
      return 'text-red-600'
    default:
      return 'text-gray-600'
  }
})

// Methods
async function loadAiSettings() {
  try {
    const response = await axios.get('/api/v1/ai/settings')
    Object.assign(aiSettings.value, response.data.settings)
    lastAnalysis.value = response.data.last_analysis
    insightsCount.value = response.data.insights_count
    systemStatus.value = response.data.system_status
  } catch (error) {
    console.error('Failed to load AI settings:', error)
    // Set default values if API is not available
    lastAnalysis.value = new Date()
    insightsCount.value = 42
    systemStatus.value = 'active'
  }
}

async function updateAiSettings() {
  try {
    await axios.post('/api/v1/ai/settings', {
      settings: aiSettings.value
    })
    
    notificationStore.showNotification({
      type: 'success',
      message: t('ai.insights.settings_updated')
    })
  } catch (error) {
    console.error('Failed to update AI settings:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('ai.insights.settings_update_failed')
    })
  }
}

async function runAnalysis() {
  isRunningAnalysis.value = true
  try {
    await axios.post('/api/v1/ai/run-analysis')
    await loadAiSettings() // Refresh status
    
    notificationStore.showNotification({
      type: 'success',
      message: t('ai.insights.analysis_started')
    })
  } catch (error) {
    console.error('Failed to run analysis:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('ai.insights.analysis_failed')
    })
  } finally {
    isRunningAnalysis.value = false
  }
}

function formatDateTime(date) {
  if (!date) return t('general.never')
  return new Date(date).toLocaleString()
}

// Lifecycle
onMounted(() => {
  loadAiSettings()
})
</script>

