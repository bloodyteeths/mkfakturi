<template>
  <div class="bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-medium text-gray-900 mb-2">
      {{ t('partner.accounting.year_end.step1_title') }}
    </h3>
    <p class="text-sm text-gray-500 mb-6">
      {{ t('partner.accounting.year_end.step1_desc') }}
    </p>

    <!-- Loading -->
    <div v-if="store.isLoading" class="space-y-4">
      <div v-for="i in 6" :key="i" class="flex items-center space-x-3 animate-pulse">
        <div class="h-8 w-8 bg-gray-200 rounded-full"></div>
        <div class="h-4 bg-gray-200 rounded flex-1"></div>
      </div>
    </div>

    <!-- Checklist -->
    <div v-else-if="store.preflightData" class="space-y-3">
      <div
        v-for="check in store.preflightData.checks"
        :key="check.key"
        :class="[
          'flex items-center justify-between p-4 rounded-lg border',
          check.status === 'pass' ? 'bg-green-50 border-green-200' :
          check.status === 'warning' ? 'bg-yellow-50 border-yellow-200' :
          'bg-red-50 border-red-200',
        ]"
      >
        <div class="flex items-center space-x-3">
          <BaseIcon
            :name="check.status === 'pass' ? 'CheckCircleIcon' : check.status === 'warning' ? 'ExclamationTriangleIcon' : 'XCircleIcon'"
            :class="[
              'h-6 w-6',
              check.status === 'pass' ? 'text-green-500' :
              check.status === 'warning' ? 'text-yellow-500' :
              'text-red-500',
            ]"
          />
          <div>
            <p class="text-sm font-medium text-gray-900">{{ check.label }}</p>
            <p v-if="check.detail" class="text-xs text-gray-500 mt-0.5">{{ check.detail }}</p>
          </div>
        </div>
        <router-link
          v-if="check.link && check.status !== 'pass'"
          :to="check.link"
          class="text-xs text-primary-600 hover:text-primary-800 font-medium"
        >
          {{ t('partner.accounting.year_end.fix') }} &rarr;
        </router-link>
      </div>

      <!-- Summary -->
      <div v-if="!store.preflightData.can_proceed" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
        <p class="text-sm text-red-700 font-medium">
          {{ t('partner.accounting.year_end.cannot_proceed') }}
        </p>
      </div>

      <div v-else-if="store.preflightData.has_warnings" class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
        <p class="text-sm text-yellow-700 font-medium">
          {{ t('partner.accounting.year_end.has_warnings') }}
        </p>
      </div>

      <div v-else class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
        <p class="text-sm text-green-700 font-medium">
          {{ t('partner.accounting.year_end.all_good') }}
        </p>
      </div>
    </div>

    <!-- Error state -->
    <div v-else-if="store.lastError" class="space-y-4">
      <div class="bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex items-start">
          <BaseIcon name="ExclamationTriangleIcon" class="h-5 w-5 text-red-500 mr-2 mt-0.5" />
          <div>
            <p class="text-sm font-medium text-red-800">{{ t('partner.accounting.year_end.cannot_proceed') }}</p>
            <p class="text-sm text-red-600 mt-1">{{ store.lastError }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Reload button -->
    <div class="mt-6">
      <BaseButton variant="primary-outline" :loading="store.isLoading" @click="loadChecks">
        <template #left="slotProps">
          <BaseIcon :class="slotProps.class" name="ArrowPathIcon" />
        </template>
        {{ t('partner.accounting.year_end.recheck') }}
      </BaseButton>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useYearEndClosingStore } from '@/scripts/admin/stores/year-end-closing'

const { t } = useI18n()
const store = useYearEndClosingStore()

async function loadChecks() {
  try {
    await store.fetchPreflight()
  } catch {
    // Error is stored in store.lastError
  }
}

onMounted(() => {
  if (!store.preflightData) {
    loadChecks()
  }
})
</script>
// CLAUDE-CHECKPOINT
