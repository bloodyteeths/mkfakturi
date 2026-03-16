<template>
  <BasePage>
    <BasePageHeader :title="t('budgets.create')">
      <template #actions>
        <BaseButton variant="primary-outline" @click="$router.push({ name: 'budgets.index' })">
          {{ $t('general.cancel') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Mode Selection -->
    <div v-if="!mode" class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-3xl mx-auto mt-8">
      <!-- Smart Budget Card -->
      <button
        @click="mode = 'smart'"
        class="relative text-left bg-white rounded-xl border-2 border-gray-200 hover:border-primary-500 hover:shadow-lg transition-all p-6 cursor-pointer group"
      >
        <span class="absolute top-3 right-3 inline-flex items-center rounded-full bg-primary-100 px-2.5 py-0.5 text-xs font-medium text-primary-700">
          {{ t('budgets.recommended') }}
        </span>
        <div class="flex flex-col items-center text-center">
          <svg class="h-16 w-16 text-primary-500 mb-4 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z" />
          </svg>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ t('budgets.smart_budget_title') }}</h3>
          <p class="text-sm text-gray-500">{{ t('budgets.smart_budget_desc') }}</p>
        </div>
      </button>

      <!-- Advanced Mode Card -->
      <button
        @click="mode = 'advanced'"
        class="text-left bg-white rounded-xl border-2 border-gray-200 hover:border-gray-400 hover:shadow-lg transition-all p-6 cursor-pointer group"
      >
        <div class="flex flex-col items-center text-center">
          <svg class="h-16 w-16 text-gray-400 mb-4 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 0 1-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0 1 12 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25m-17.25 0h7.5c.621 0 1.125.504 1.125 1.125M3.375 8.25c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m17.25-3.75h-7.5c-.621 0-1.125.504-1.125 1.125m8.625-1.125c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125M12 10.875v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 10.875c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125M13.125 12h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125M20.625 12c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5M12 14.625v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 14.625c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125m0 0v1.5c0 .621-.504 1.125-1.125 1.125" />
          </svg>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ t('budgets.advanced_mode_title') }}</h3>
          <p class="text-sm text-gray-500">{{ t('budgets.advanced_mode_desc') }}</p>
        </div>
      </button>
    </div>

    <!-- Smart Budget Form -->
    <SmartBudgetForm
      v-if="mode === 'smart'"
      @switch-mode="switchMode"
    />

    <!-- Advanced Budget Form -->
    <AdvancedBudgetForm
      v-if="mode === 'advanced'"
      @switch-mode="switchMode"
    />
  </BasePage>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import SmartBudgetForm from './SmartBudgetForm.vue'
import AdvancedBudgetForm from './AdvancedBudgetForm.vue'

const { t } = useI18n()
const mode = ref(null)

function switchMode(newMode) {
  mode.value = newMode
}
</script>

<!-- CLAUDE-CHECKPOINT -->
