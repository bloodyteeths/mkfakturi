<template>
  <form action="" class="relative" @submit.prevent="updateDashboardSettings">
    <BaseSettingCard
      :title="$t('settings.dashboard.title')"
      :description="$t('settings.dashboard.description')"
    >
      <BaseInputGrid class="mt-5">
        <BaseInputGroup
          :content-loading="isFetchingInitialData"
          :label="$t('ai.financial_insights')"
          :help-text="$t('settings.dashboard.ai_insights_help')"
        >
          <BaseSwitch
            v-model="settingsForm.show_ai_insights"
            :content-loading="isFetchingInitialData"
            class="text-left"
          >
            <template #label>
              <span class="text-sm text-gray-700 dark:text-gray-300">
                {{ $t('settings.dashboard.enable_ai_insights') }}
              </span>
            </template>
          </BaseSwitch>
        </BaseInputGroup>
      </BaseInputGrid>

      <BaseDivider class="mb-5 md:mb-8" />

      <BaseButton
        :loading="isUpdating"
        :disabled="isUpdating"
        size="lg"
        variant="primary"
        type="submit"
        class="mt-4"
      >
        <template #left="slotProps">
          <BaseIcon :class="slotProps.class" name="SaveIcon" />
        </template>
        {{ $t('general.save') }}
      </BaseButton>
    </BaseSettingCard>
  </form>
</template>

<script setup>
import { reactive, ref, inject } from 'vue'
import { useUserStore } from '@/scripts/admin/stores/user'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useI18n } from 'vue-i18n'

// Store instances
const userStore = useUserStore()
const notificationStore = useNotificationStore()
const { t } = useI18n()

// Loading states
const isFetchingInitialData = ref(true)
const isUpdating = ref(false)

// Form data
const settingsForm = reactive({
  show_ai_insights: true, // Default to enabled
})

// Initialize component
const initialize = async () => {
  try {
    isFetchingInitialData.value = true
    
    // Fetch current user settings
    const response = await userStore.fetchUserSettings(['show_ai_insights'])
    
    // Set form values from response
    if (response.data?.settings?.show_ai_insights !== undefined) {
      settingsForm.show_ai_insights = response.data.settings.show_ai_insights
    }
    
  } catch (error) {
    console.error('Failed to fetch dashboard settings:', error)
  } finally {
    isFetchingInitialData.value = false
  }
}

// Update settings
const updateDashboardSettings = async () => {
  try {
    isUpdating.value = true
    
    const data = {
      settings: {
        show_ai_insights: settingsForm.show_ai_insights,
      }
    }
    
    await userStore.updateUserSettings(data)
    
    notificationStore.showNotification({
      type: 'success',
      message: t('settings.dashboard.updated_message'),
    })
    
  } catch (error) {
    console.error('Failed to update dashboard settings:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('validation.something_went_wrong'),
    })
  } finally {
    isUpdating.value = false
  }
}

// Initialize on mount
initialize()
</script>

