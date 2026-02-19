<template>
  <div class="bg-white rounded-lg shadow p-6">
    <!-- Pre-finalize state -->
    <template v-if="store.fiscalYearStatus !== 'CLOSED'">
      <h3 class="text-lg font-medium text-gray-900 mb-2">
        {{ t('partner.accounting.year_end.step6_title') }}
      </h3>
      <p class="text-sm text-gray-500 mb-6">
        {{ t('partner.accounting.year_end.step6_desc', { year: store.year }) }}
      </p>

      <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
        <div class="flex items-start">
          <BaseIcon name="ExclamationTriangleIcon" class="h-5 w-5 text-yellow-500 mr-2 mt-0.5" />
          <div>
            <p class="text-sm font-medium text-yellow-800">{{ t('partner.accounting.year_end.warning') }}</p>
            <ul class="text-sm text-yellow-700 mt-1 list-disc list-inside space-y-1">
              <li>{{ t('partner.accounting.year_end.warning_invoices', { year: store.year }) }}</li>
              <li>{{ t('partner.accounting.year_end.warning_vat') }}</li>
              <li>{{ t('partner.accounting.year_end.warning_undo_24h') }}</li>
            </ul>
          </div>
        </div>
      </div>

      <BaseButton
        variant="primary"
        :loading="store.isLoading"
        @click="doFinalize"
      >
        <template #left="slotProps">
          <BaseIcon :class="slotProps.class" name="LockClosedIcon" />
        </template>
        {{ t('partner.accounting.year_end.lock_year', { year: store.year }) }}
      </BaseButton>
    </template>

    <!-- Success state -->
    <template v-else>
      <div class="text-center py-8">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
          <BaseIcon name="CheckIcon" class="h-8 w-8 text-green-600" />
        </div>
        <h3 class="text-xl font-medium text-gray-900 mb-2">
          {{ t('partner.accounting.year_end.year_closed_title', { year: store.year }) }}
        </h3>
        <p class="text-sm text-gray-500 mb-6">
          {{ t('partner.accounting.year_end.year_closed_desc') }}
        </p>

        <div class="bg-gray-50 rounded-lg p-4 inline-block text-left mb-6">
          <p class="text-xs text-gray-500">{{ t('partner.accounting.year_end.filing_deadlines') }}</p>
          <ul class="text-sm text-gray-700 mt-2 space-y-1">
            <li>{{ t('partner.accounting.year_end.deadline_paper_crm') }}</li>
            <li>{{ t('partner.accounting.year_end.deadline_electronic_crm') }}</li>
            <li>{{ t('partner.accounting.year_end.deadline_tax_ujp') }}</li>
          </ul>
        </div>

        <div class="flex justify-center space-x-3">
          <BaseButton variant="gray" @click="store.goToStep(5)">
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="ArrowLeftIcon" />
            </template>
            {{ t('partner.accounting.year_end.back_to_reports') }}
          </BaseButton>
          <BaseButton variant="primary-outline" @click="doUndo" :loading="isUndoing">
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="ArrowUturnLeftIcon" />
            </template>
            {{ t('partner.accounting.year_end.undo_24h') }}
          </BaseButton>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useYearEndClosingStore } from '@/scripts/admin/stores/year-end-closing'
import { useNotificationStore } from '@/scripts/stores/notification'

const { t } = useI18n()
const store = useYearEndClosingStore()
const notificationStore = useNotificationStore()
const isUndoing = ref(false)

async function doFinalize() {
  try {
    await store.finalize()
    notificationStore.showNotification({
      type: 'success',
      message: t('partner.accounting.year_end.year_closed_success', { year: store.year }),
    })
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('partner.accounting.year_end.closing_error'),
    })
  }
}

async function doUndo() {
  isUndoing.value = true
  try {
    await store.undoClosing()
    notificationStore.showNotification({
      type: 'success',
      message: t('partner.accounting.year_end.undo_success'),
    })
    store.goToStep(1)
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('partner.accounting.year_end.undo_error'),
    })
  } finally {
    isUndoing.value = false
  }
}
</script>
