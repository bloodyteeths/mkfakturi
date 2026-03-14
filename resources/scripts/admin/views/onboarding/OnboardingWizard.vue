<template>
  <div class="onboarding-wizard min-h-screen bg-gradient-to-br from-slate-50 via-white to-primary-50/30">
    <!-- Hero Header with gradient -->
    <div class="relative overflow-hidden bg-gradient-to-r from-primary-600 via-primary-500 to-indigo-500 px-6 py-8 sm:px-10">
      <!-- Decorative background shapes -->
      <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-white/5" />
        <div class="absolute -left-10 -bottom-10 h-48 w-48 rounded-full bg-white/5" />
        <div class="absolute right-1/3 top-1/4 h-32 w-32 rounded-full bg-white/10 animate-pulse" />
      </div>

      <div class="relative mx-auto max-w-4xl">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-white sm:text-3xl tracking-tight">
              {{ t('onboarding.wizard.title') }}
            </h1>
            <p class="mt-1.5 text-sm text-primary-100/80">
              {{ stepLabels[currentStep - 1]?.label || '' }}
            </p>
          </div>
          <BaseButton
            v-if="currentStep > 1"
            class="!bg-white/10 !text-white !border-white/20 hover:!bg-white/20 backdrop-blur-sm transition-all duration-200"
            size="sm"
            @click="skipToEnd"
          >
            {{ t('onboarding.wizard.skip_for_now') }}
          </BaseButton>
        </div>

        <!-- Step Progress Bar (inside hero for visual punch) -->
        <div class="mt-8 pb-2">
          <div class="flex items-center justify-between">
            <template v-for="(step, index) in stepLabels" :key="step.num">
              <!-- Step circle -->
              <div class="flex flex-col items-center z-10">
                <div
                  :class="[
                    'flex items-center justify-center w-11 h-11 rounded-full text-sm font-bold transition-all duration-500 ease-out',
                    step.num < currentStep
                      ? 'bg-white text-primary-600 shadow-lg shadow-white/25'
                      : step.num === currentStep
                        ? 'bg-white text-primary-600 shadow-xl shadow-white/40 scale-110 ring-4 ring-white/25'
                        : 'bg-white/15 text-white/60 backdrop-blur-sm',
                  ]"
                >
                  <BaseIcon
                    v-if="step.num < currentStep"
                    name="CheckIcon"
                    class="h-5 w-5"
                  />
                  <span v-else>{{ step.num }}</span>
                </div>
                <span
                  :class="[
                    'mt-2.5 text-[11px] font-semibold text-center max-w-[80px] transition-all duration-300',
                    step.num === currentStep ? 'text-white' : 'text-white/50',
                  ]"
                >
                  {{ step.label }}
                </span>
              </div>

              <!-- Animated connector line -->
              <div
                v-if="index < stepLabels.length - 1"
                class="relative flex-1 mx-2 -mt-6"
              >
                <div class="h-0.5 w-full bg-white/15 rounded-full" />
                <div
                  class="absolute inset-y-0 left-0 h-0.5 bg-white/80 rounded-full transition-all duration-700 ease-out"
                  :style="{ width: step.num < currentStep ? '100%' : '0%' }"
                />
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- Step Content Area -->
    <div class="mx-auto max-w-4xl px-6 py-8 sm:px-10">
      <div class="min-h-[400px]">
        <Step1Source
          v-if="currentStep === 1"
          :selected-source="selectedSource"
          @select="onSourceSelect"
        />
        <Step2Guide
          v-else-if="currentStep === 2"
          :source="selectedSource"
          @ready="currentStep = 3"
          @skip="currentStep = 4"
        />
        <Step3Upload
          v-else-if="currentStep === 3"
          :source="selectedSource"
          @done="currentStep = 4"
          @skip="currentStep = 4"
        />
        <Step4Bank
          v-else-if="currentStep === 4"
          @done="currentStep = 5"
          @skip="currentStep = 5"
        />
        <Step5Summary
          v-else-if="currentStep === 5"
          @complete="onComplete"
        />
      </div>

      <!-- Navigation Buttons -->
      <div
        v-if="currentStep > 1 && currentStep < 5"
        class="mt-10 flex justify-between border-t border-gray-100 pt-6"
      >
        <BaseButton
          variant="gray"
          @click="currentStep--"
        >
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="ArrowLeftIcon" />
          </template>
          {{ t('onboarding.wizard.back') }}
        </BaseButton>

        <BaseButton
          variant="primary"
          @click="currentStep++"
        >
          {{ t('onboarding.wizard.next') }}
          <template #right="slotProps">
            <BaseIcon :class="slotProps.class" name="ArrowRightIcon" />
          </template>
        </BaseButton>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Step1Source from './steps/Step1Source.vue'
import Step2Guide from './steps/Step2Guide.vue'
import Step3Upload from './steps/Step3Upload.vue'
import Step4Bank from './steps/Step4Bank.vue'
import Step5Summary from './steps/Step5Summary.vue'

const { t } = useI18n()
const router = useRouter()

const currentStep = ref(1)
const selectedSource = ref(null)

const stepLabels = computed(() => [
  { num: 1, label: t('onboarding.wizard.step1_label') },
  { num: 2, label: t('onboarding.wizard.step2_label') },
  { num: 3, label: t('onboarding.wizard.step3_label') },
  { num: 4, label: t('onboarding.wizard.step4_label') },
  { num: 5, label: t('onboarding.wizard.step5_label') },
])

function onSourceSelect(source) {
  selectedSource.value = source
  if (source === 'fresh') {
    currentStep.value = 4
  } else {
    currentStep.value = 2
  }
  axios.post('/onboarding/source', { source }).catch(() => {})
}

function skipToEnd() {
  currentStep.value = 5
}

async function onComplete() {
  try {
    await axios.post('/onboarding/complete')
  } catch (e) {
    // Continue anyway
  }
  router.push({ name: 'dashboard' })
}

onMounted(async () => {
  try {
    const { data } = await axios.get('/onboarding/progress')
    if (data.source) {
      selectedSource.value = data.source
    }
  } catch (e) {
    // Start fresh
  }
})
</script>
