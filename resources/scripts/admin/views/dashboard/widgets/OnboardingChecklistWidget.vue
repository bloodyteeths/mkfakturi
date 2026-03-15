<template>
  <div
    v-if="showChecklist"
    class="relative mb-6 rounded-lg border border-gray-200 bg-white p-5"
  >
    <!-- Dismiss button -->
    <button
      class="absolute right-3 top-3 rounded p-1 text-gray-400 hover:text-gray-600"
      @click="dismissChecklist"
    >
      <BaseIcon name="XMarkIcon" class="h-4 w-4" />
    </button>

    <!-- Header -->
    <h3 class="text-base font-semibold text-gray-900 mb-1">
      {{ $t('onboarding.checklist.title') }}
    </h3>
    <p class="text-sm text-gray-500 mb-4">
      {{ completedCount }} / {{ totalCount }} {{ $t('onboarding.checklist.completed') }}
    </p>

    <!-- Progress bar -->
    <div class="h-2 w-full rounded-full bg-gray-100 mb-4">
      <div
        class="h-2 rounded-full bg-primary-500 transition-all duration-500"
        :style="{ width: progressPercent + '%' }"
      />
    </div>

    <!-- Checklist items -->
    <div class="space-y-1">
      <div
        v-for="step in checklistSteps"
        :key="step.key"
        class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm"
        :class="step.completed ? '' : 'hover:bg-gray-50 cursor-pointer'"
        @click="!step.completed && navigateTo(step)"
      >
        <div
          class="flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full"
          :class="step.completed ? 'bg-green-500' : 'border border-gray-300'"
        >
          <BaseIcon
            v-if="step.completed"
            name="CheckIcon"
            class="h-3 w-3 text-white"
          />
        </div>
        <span :class="step.completed ? 'text-gray-400 line-through' : 'text-gray-700'">
          {{ step.label }}
        </span>
        <BaseIcon
          v-if="!step.completed"
          name="ChevronRightIcon"
          class="ml-auto h-4 w-4 text-gray-300"
        />
      </div>
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
