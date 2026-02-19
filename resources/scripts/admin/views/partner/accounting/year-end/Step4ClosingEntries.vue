<template>
  <div class="bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-medium text-gray-900 mb-2">
      {{ t('partner.accounting.year_end.step4_title') }}
    </h3>
    <p class="text-sm text-gray-500 mb-6">
      {{ t('partner.accounting.year_end.step4_desc') }}
    </p>

    <!-- Loading -->
    <div v-if="store.isLoading" class="space-y-4 animate-pulse">
      <div v-for="i in 5" :key="i" class="flex space-x-4">
        <div class="h-4 bg-gray-200 rounded flex-1"></div>
        <div class="h-4 bg-gray-200 rounded w-24"></div>
      </div>
    </div>

    <!-- Error state -->
    <div v-else-if="store.lastError" class="space-y-4">
      <div class="bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex items-start">
          <BaseIcon name="ExclamationTriangleIcon" class="h-5 w-5 text-red-500 mr-2 mt-0.5" />
          <div>
            <p class="text-sm font-medium text-red-800">{{ t('partner.accounting.year_end.entries_error') }}</p>
            <p class="text-sm text-red-600 mt-1">{{ store.lastError }}</p>
          </div>
        </div>
      </div>
      <BaseButton variant="primary-outline" :loading="store.isLoading" @click="retryPreview">
        <template #left="slotProps">
          <BaseIcon :class="slotProps.class" name="ArrowPathIcon" />
        </template>
        {{ t('partner.accounting.year_end.recheck') }}
      </BaseButton>
    </div>

    <!-- Preview Table -->
    <div v-else-if="store.closingPreview" class="space-y-6">
      <!-- Result badge -->
      <div :class="[
        'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium',
        store.closingPreview.is_profit ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
      ]">
        {{ store.closingPreview.is_profit ? t('partner.accounting.year_end.profit') : t('partner.accounting.year_end.loss') }}:
        {{ formatMoney(Math.abs(store.closingPreview.summary.net_profit_after_tax)) }}
      </div>

      <!-- Entries table -->
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('partner.accounting.year_end.description') }}</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('partner.accounting.year_end.debit') }}</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('partner.accounting.year_end.credit') }}</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('partner.accounting.year_end.amount') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="(entry, i) in store.closingPreview.entries" :key="i">
              <td class="px-4 py-3 text-sm text-gray-900">{{ entry.description }}</td>
              <td class="px-4 py-3 text-sm text-gray-600">{{ entry.debit_account }} — {{ entry.debit_name }}</td>
              <td class="px-4 py-3 text-sm text-gray-600">{{ entry.credit_account }} — {{ entry.credit_name }}</td>
              <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">{{ formatMoney(entry.amount) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <p class="text-xs text-gray-500">
        {{ t('partner.accounting.year_end.entries_dated_dec31', { year: store.year }) }}
      </p>

      <!-- Commit button -->
      <div v-if="!store.closingResult" class="flex space-x-3">
        <BaseButton
          variant="primary"
          :loading="isCommitting"
          @click="commitEntries"
        >
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="CheckIcon" />
          </template>
          {{ t('partner.accounting.year_end.generate_entries') }}
        </BaseButton>
      </div>

      <!-- Success message -->
      <div v-if="store.closingResult" class="p-4 bg-green-50 border border-green-200 rounded-lg">
        <div class="flex items-center">
          <BaseIcon name="CheckCircleIcon" class="h-5 w-5 text-green-500 mr-2" />
          <p class="text-sm text-green-700 font-medium">
            {{ t('partner.accounting.year_end.entries_generated', { count: store.closingResult.entry_count }) }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useYearEndClosingStore } from '@/scripts/admin/stores/year-end-closing'
import { useNotificationStore } from '@/scripts/stores/notification'

const { t } = useI18n()
const store = useYearEndClosingStore()
const notificationStore = useNotificationStore()
const isCommitting = ref(false)

async function commitEntries() {
  isCommitting.value = true
  try {
    await store.commitClosingEntries()
    notificationStore.showNotification({
      type: 'success',
      message: t('partner.accounting.year_end.entries_success'),
    })
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('partner.accounting.year_end.entries_error'),
    })
  } finally {
    isCommitting.value = false
  }
}

async function retryPreview() {
  try {
    await store.fetchClosingPreview()
  } catch {
    // Error is stored in store.lastError
  }
}

function formatMoney(amount) {
  if (amount === null || amount === undefined) return '-'
  return new Intl.NumberFormat('mk-MK', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(Math.abs(amount)) + ' МКД'
}

onMounted(async () => {
  if (!store.closingPreview) {
    try {
      await store.fetchClosingPreview()
    } catch {
      // Error is stored in store.lastError
    }
  }
})
</script>
