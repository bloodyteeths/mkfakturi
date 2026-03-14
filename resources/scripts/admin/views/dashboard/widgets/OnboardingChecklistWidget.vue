<template>
  <div
    v-if="showChecklist"
    class="relative mb-6 overflow-hidden rounded-2xl border border-primary-100 bg-gradient-to-r from-primary-50 via-white to-indigo-50/30 p-6 shadow-sm"
  >
    <!-- Decorative background -->
    <div class="absolute -right-16 -top-16 h-48 w-48 rounded-full bg-primary-100/40 pointer-events-none" />
    <div class="absolute -left-8 -bottom-8 h-32 w-32 rounded-full bg-indigo-100/30 pointer-events-none" />

    <!-- Dismiss button -->
    <button
      class="absolute right-4 top-4 rounded-lg p-1 text-gray-300 transition-colors hover:bg-gray-100 hover:text-gray-500"
      @click="dismissChecklist"
    >
      <BaseIcon name="XMarkIcon" class="h-4 w-4" />
    </button>

    <!-- Header -->
    <div class="relative mb-5 flex items-start gap-4">
      <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-primary-500 to-indigo-500 shadow-lg shadow-primary-500/20">
        <BaseIcon name="RocketLaunchIcon" class="h-6 w-6 text-white" />
      </div>
      <div>
        <h3 class="text-lg font-bold text-gray-900 tracking-tight">
          {{ $t('onboarding.checklist.title') }}
        </h3>
        <p class="mt-0.5 text-sm text-gray-500">
          {{ $t('onboarding.checklist.subtitle') }}
        </p>
      </div>
    </div>

    <!-- Progress bar -->
    <div class="relative mb-5">
      <div class="flex items-center justify-between text-xs mb-1.5">
        <span class="font-semibold text-gray-600">{{ completedCount }} / {{ totalCount }} {{ $t('onboarding.checklist.completed') }}</span>
        <span class="font-bold text-primary-600">{{ progressPercent }}%</span>
      </div>
      <div class="h-2.5 w-full rounded-full bg-gray-100">
        <div
          class="h-2.5 rounded-full bg-gradient-to-r from-primary-500 to-indigo-500 transition-all duration-700 ease-out shadow-sm"
          :style="{ width: progressPercent + '%' }"
        />
      </div>
    </div>

    <!-- Checklist items -->
    <div class="relative space-y-1.5">
      <div
        v-for="step in checklistSteps"
        :key="step.key"
        class="flex items-center gap-3 rounded-xl p-2.5 transition-all duration-200"
        :class="step.completed ? 'bg-green-50/50' : 'hover:bg-white hover:shadow-sm cursor-pointer'"
        @click="!step.completed && navigateTo(step)"
      >
        <!-- Check circle -->
        <div
          class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full transition-all duration-300"
          :class="step.completed ? 'bg-green-500 shadow-sm shadow-green-500/20' : 'border-2 border-gray-200'"
        >
          <BaseIcon
            v-if="step.completed"
            name="CheckIcon"
            class="h-3.5 w-3.5 text-white"
          />
        </div>

        <!-- Label -->
        <span
          class="flex-1 text-sm transition-colors"
          :class="step.completed ? 'text-gray-400 line-through' : 'text-gray-900 font-medium'"
        >
          {{ step.label }}
        </span>

        <!-- Arrow for incomplete -->
        <BaseIcon
          v-if="!step.completed"
          name="ChevronRightIcon"
          class="h-4 w-4 text-gray-300"
        />
      </div>
    </div>

    <!-- Setup Wizard CTA -->
    <div class="relative mt-5 flex gap-2">
      <BaseButton
        variant="primary"
        size="sm"
        class="!shadow-md !shadow-primary-500/15"
        @click="router.push({ name: 'onboarding.wizard' })"
      >
        <template #left="slotProps">
          <BaseIcon :class="slotProps.class" name="RocketLaunchIcon" />
        </template>
        {{ $t('onboarding.checklist.setup_wizard') }}
      </BaseButton>
      <BaseButton
        variant="gray"
        size="sm"
        @click="dismissChecklist"
      >
        {{ $t('onboarding.checklist.dismiss') }}
      </BaseButton>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import axios from 'axios'

const { t } = useI18n()
const router = useRouter()

const progress = ref(null)
const showChecklist = ref(false)

const checklistSteps = computed(() => {
  if (!progress.value?.steps) return []

  const stepConfig = {
    company_details: {
      label: t('onboarding.checklist.step_company_details'),
      route: { name: 'company.info' },
    },
    upload_logo: {
      label: t('onboarding.checklist.step_upload_logo'),
      route: { name: 'company.info' },
    },
    import_data: {
      label: t('onboarding.checklist.step_import_data'),
      route: { name: 'onboarding.wizard' },
    },
    first_invoice: {
      label: t('onboarding.checklist.step_first_invoice'),
      route: { name: 'invoices.create' },
    },
    bank_account: {
      label: t('onboarding.checklist.step_bank_account'),
      route: { name: 'banking' },
    },
  }

  return progress.value.steps.map(step => ({
    ...step,
    label: stepConfig[step.key]?.label || step.key,
    route: stepConfig[step.key]?.route,
  }))
})

const completedCount = computed(() => progress.value?.completed_count || 0)
const totalCount = computed(() => progress.value?.total_count || 5)
const progressPercent = computed(() =>
  Math.round((completedCount.value / totalCount.value) * 100)
)

function navigateTo(step) {
  if (step.route) {
    router.push(step.route)
  }
}

async function dismissChecklist() {
  showChecklist.value = false
  try {
    await axios.post('/onboarding/dismiss')
  } catch (e) {
    // Silently fail
  }
}

async function fetchProgress() {
  try {
    const { data } = await axios.get('/onboarding/progress')
    progress.value = data
    showChecklist.value = !data.dismissed && !data.all_completed
  } catch (e) {
    showChecklist.value = false
  }
}

onMounted(fetchProgress)
</script>
