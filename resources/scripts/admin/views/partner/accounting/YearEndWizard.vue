<template>
  <BasePage>
    <BasePageHeader :title="t('partner.accounting.year_end.title', { year: store.year })">
      <template #actions>
        <BaseMultiselect
          v-model="store.year"
          :options="yearOptions"
          :searchable="false"
          class="w-32"
          @update:model-value="onYearChange"
        />
      </template>
    </BasePageHeader>

    <!-- Step Progress Bar -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <template v-for="(step, index) in store.stepLabels" :key="step.num">
          <!-- Step circle -->
          <div class="flex flex-col items-center">
            <div
              :class="[
                'flex items-center justify-center w-10 h-10 rounded-full text-sm font-semibold transition-colors duration-200',
                step.num < store.currentStep
                  ? 'bg-green-500 text-white'
                  : step.num === store.currentStep
                    ? 'bg-primary-500 text-white'
                    : 'bg-gray-200 text-gray-500',
              ]"
            >
              <BaseIcon
                v-if="step.num < store.currentStep"
                name="CheckIcon"
                class="h-5 w-5"
              />
              <span v-else>{{ step.num }}</span>
            </div>
            <span
              :class="[
                'mt-2 text-xs font-medium',
                step.num === store.currentStep ? 'text-primary-600' : 'text-gray-500',
              ]"
            >
              {{ t(step.key) }}
            </span>
          </div>

          <!-- Connector line -->
          <div
            v-if="index < store.stepLabels.length - 1"
            :class="[
              'flex-1 h-0.5 mx-2 -mt-6',
              step.num < store.currentStep ? 'bg-green-500' : 'bg-gray-200',
            ]"
          />
        </template>
      </div>
    </div>

    <!-- Step Content -->
    <div class="min-h-[400px]">
      <Step1Preflight v-if="store.currentStep === 1" />
      <Step2ReviewStatements v-else-if="store.currentStep === 2" />
      <Step3AdjustingEntries v-else-if="store.currentStep === 3" />
      <Step4ClosingEntries v-else-if="store.currentStep === 4" />
      <Step5Reports v-else-if="store.currentStep === 5" />
      <Step6Finalize v-else-if="store.currentStep === 6" />
    </div>

    <!-- Navigation Buttons -->
    <div class="flex justify-between mt-8 pt-6 border-t border-gray-200">
      <BaseButton
        v-if="store.currentStep > 1 && store.currentStep < 6"
        variant="gray"
        @click="store.prevStep()"
      >
        <template #left="slotProps">
          <BaseIcon :class="slotProps.class" name="ArrowLeftIcon" />
        </template>
        {{ t('partner.accounting.year_end.back') }}
      </BaseButton>
      <div v-else />

      <BaseButton
        v-if="store.currentStep < 6"
        variant="primary"
        :disabled="!store.canProceed"
        @click="store.nextStep()"
      >
        {{ t('partner.accounting.year_end.next') }}
        <template #right="slotProps">
          <BaseIcon :class="slotProps.class" name="ArrowRightIcon" />
        </template>
      </BaseButton>
    </div>
  </BasePage>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useYearEndClosingStore } from '@/scripts/admin/stores/year-end-closing'
import Step1Preflight from './year-end/Step1Preflight.vue'
import Step2ReviewStatements from './year-end/Step2ReviewStatements.vue'
import Step3AdjustingEntries from './year-end/Step3AdjustingEntries.vue'
import Step4ClosingEntries from './year-end/Step4ClosingEntries.vue'
import Step5Reports from './year-end/Step5Reports.vue'
import Step6Finalize from './year-end/Step6Finalize.vue'

const { t } = useI18n()
const store = useYearEndClosingStore()

const currentYear = new Date().getFullYear()
const yearOptions = computed(() => {
  const years = []
  for (let y = currentYear - 1; y >= currentYear - 5; y--) {
    years.push({ id: y, name: String(y) })
  }
  return years
})

function onYearChange() {
  store.reset()
}

onMounted(() => {
  store.reset()
})
</script>
// CLAUDE-CHECKPOINT
