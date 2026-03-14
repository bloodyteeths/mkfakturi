<template>
  <div>
    <!-- Header with source badge -->
    <div class="mb-6">
      <div class="inline-flex items-center gap-2 rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700 mb-3">
        <div class="h-1.5 w-1.5 rounded-full bg-primary-500 animate-pulse" />
        {{ sourceLabel }}
      </div>
      <h2 class="text-2xl font-bold text-gray-900 tracking-tight">
        {{ $t('onboarding.step2.title', { source: sourceLabel }) }}
      </h2>
      <p class="mt-1.5 text-sm text-gray-500 leading-relaxed">
        {{ $t('onboarding.step2.subtitle') }}
      </p>
    </div>

    <!-- Export steps as timeline -->
    <div class="relative space-y-0">
      <!-- Vertical line -->
      <div class="absolute left-[19px] top-4 bottom-4 w-0.5 bg-gray-100" />

      <div
        v-for="(step, index) in guideSteps"
        :key="index"
        class="relative flex items-start gap-4 py-3"
      >
        <!-- Step number / check circle -->
        <button
          class="relative z-10 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full border-2 transition-all duration-300 cursor-pointer"
          :class="
            step.checked
              ? 'border-green-500 bg-green-500 text-white shadow-md shadow-green-500/20'
              : 'border-gray-200 bg-white text-gray-400 hover:border-primary-300 hover:text-primary-500'
          "
          @click="step.checked = !step.checked"
        >
          <BaseIcon v-if="step.checked" name="CheckIcon" class="h-5 w-5" />
          <span v-else class="text-sm font-bold">{{ index + 1 }}</span>
        </button>

        <!-- Content card -->
        <div
          class="flex-1 rounded-xl border p-4 transition-all duration-300"
          :class="
            step.checked
              ? 'border-green-100 bg-green-50/50'
              : 'border-gray-100 bg-white hover:border-gray-200 hover:shadow-sm'
          "
        >
          <p
            class="text-sm font-semibold transition-all duration-200"
            :class="step.checked ? 'text-green-700 line-through' : 'text-gray-900'"
          >
            {{ step.text }}
          </p>
          <p v-if="step.detail" class="mt-1 text-xs text-gray-500 leading-relaxed">
            {{ step.detail }}
          </p>
          <a
            v-if="step.link"
            :href="step.link"
            target="_blank"
            rel="noopener"
            class="mt-2 inline-flex items-center gap-1.5 rounded-lg bg-primary-50 px-2.5 py-1 text-xs font-medium text-primary-600 transition-colors hover:bg-primary-100 hover:text-primary-700"
          >
            <BaseIcon name="BookOpenIcon" class="h-3.5 w-3.5" />
            {{ step.linkText || $t('onboarding.step2.documentation') }}
            <BaseIcon name="ArrowTopRightOnSquareIcon" class="h-3 w-3" />
          </a>
        </div>
      </div>
    </div>

    <!-- Files to prepare — glass card -->
    <div class="mt-8 rounded-2xl border border-blue-200/60 bg-gradient-to-br from-blue-50 to-indigo-50/50 p-5 shadow-sm">
      <h3 class="mb-4 flex items-center gap-2.5 text-sm font-bold text-blue-900">
        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-500 shadow-sm shadow-blue-500/20">
          <BaseIcon name="DocumentArrowDownIcon" class="h-4 w-4 text-white" />
        </div>
        {{ $t('onboarding.step2.files_to_prepare') }}
      </h3>
      <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
        <label
          v-for="file in filesToPrepare"
          :key="file.key"
          class="flex items-center gap-3 rounded-xl border border-blue-100/60 bg-white/60 p-3 cursor-pointer transition-all hover:bg-white hover:shadow-sm"
          :class="file.checked ? 'border-blue-300 bg-blue-50/50' : ''"
        >
          <input
            v-model="file.checked"
            type="checkbox"
            class="h-4 w-4 rounded border-blue-300 text-blue-600 focus:ring-blue-500"
          />
          <div class="flex-1 min-w-0">
            <span class="text-sm font-medium" :class="file.checked ? 'text-blue-700 line-through' : 'text-blue-900'">
              {{ file.label }}
            </span>
            <div class="flex items-center gap-2 mt-0.5">
              <span class="text-[10px] font-mono text-blue-400">{{ file.format }}</span>
              <span v-if="file.optional" class="rounded-full bg-blue-100 px-1.5 py-0.5 text-[9px] font-semibold text-blue-500 uppercase tracking-wider">
                {{ $t('onboarding.step2.optional') }}
              </span>
            </div>
          </div>
        </label>
      </div>
    </div>

    <!-- Action buttons -->
    <div class="mt-8 flex gap-3">
      <BaseButton variant="primary" @click="$emit('ready')">
        <template #left="slotProps">
          <BaseIcon :class="slotProps.class" name="CheckIcon" />
        </template>
        {{ $t('onboarding.step2.files_ready') }}
      </BaseButton>
      <BaseButton variant="gray" @click="$emit('skip')">
        {{ $t('onboarding.step2.do_later') }}
      </BaseButton>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  source: {
    type: String,
    required: true,
  },
})

defineEmits(['ready', 'skip'])

const sourceLabel = computed(() => {
  const labels = {
    pantheon: 'Pantheon',
    zonel: 'Helix / Zonel',
    ekonomika: 'Ekonomika',
    astral: 'Astral',
    b2b: 'B2B',
    excel: 'Excel',
  }
  return labels[props.source] || props.source
})

const guideConfigs = {
  pantheon: [
    {
      text: t('onboarding.guides.pantheon.step1'),
      detail: t('onboarding.guides.pantheon.step1_detail'),
      link: 'https://usersite.datalab.eu/pantheonusermanual/tabid/316/language/ko-kr/topic/export-general-journal-to-txt-file/htmlid/6203/default.aspx',
      linkText: t('onboarding.step2.datalab_docs'),
    },
    {
      text: t('onboarding.guides.pantheon.step2'),
      detail: t('onboarding.guides.pantheon.step2_detail'),
      link: 'https://usersite.datalab.eu/pantheonusermanual/tabid/316/language/en-us/topic/import-export-data/htmlid/1766/default.aspx',
      linkText: t('onboarding.step2.datalab_docs'),
    },
    {
      text: t('onboarding.guides.pantheon.step3'),
      detail: t('onboarding.guides.pantheon.step3_detail'),
    },
    {
      text: t('onboarding.guides.pantheon.step4'),
      detail: t('onboarding.guides.pantheon.step4_detail'),
    },
    {
      text: t('onboarding.guides.pantheon.step5'),
      detail: t('onboarding.guides.pantheon.step5_detail'),
    },
  ],
  zonel: [
    {
      text: t('onboarding.guides.zonel.step1'),
      detail: t('onboarding.guides.zonel.step1_detail'),
      link: 'http://www.zonel.com.mk/',
      linkText: t('onboarding.step2.zonel_website'),
    },
    {
      text: t('onboarding.guides.zonel.step2'),
      detail: t('onboarding.guides.zonel.step2_detail'),
    },
    {
      text: t('onboarding.guides.zonel.step3'),
      detail: t('onboarding.guides.zonel.step3_detail'),
    },
    {
      text: t('onboarding.guides.zonel.step4'),
      detail: t('onboarding.guides.zonel.step4_detail'),
    },
  ],
  ekonomika: [
    {
      text: t('onboarding.guides.generic.step1', { software: 'Ekonomika' }),
      detail: t('onboarding.guides.generic.step1_detail'),
    },
    {
      text: t('onboarding.guides.generic.step2', { software: 'Ekonomika' }),
      detail: t('onboarding.guides.generic.step2_detail'),
    },
    {
      text: t('onboarding.guides.generic.step3'),
      detail: t('onboarding.guides.generic.step3_detail', { software: 'Ekonomika' }),
    },
  ],
  astral: [
    {
      text: t('onboarding.guides.generic.step1', { software: 'Astral' }),
      detail: t('onboarding.guides.generic.step1_detail'),
    },
    {
      text: t('onboarding.guides.generic.step2', { software: 'Astral' }),
      detail: t('onboarding.guides.generic.step2_detail'),
    },
    {
      text: t('onboarding.guides.generic.step3'),
      detail: t('onboarding.guides.generic.step3_detail', { software: 'Astral' }),
    },
  ],
  b2b: [
    {
      text: t('onboarding.guides.generic.step1', { software: 'B2B' }),
      detail: t('onboarding.guides.generic.step1_detail'),
    },
    {
      text: t('onboarding.guides.generic.step2', { software: 'B2B' }),
      detail: t('onboarding.guides.generic.step2_detail'),
    },
    {
      text: t('onboarding.guides.generic.step3'),
      detail: t('onboarding.guides.generic.step3_detail', { software: 'B2B' }),
    },
  ],
  excel: [
    {
      text: t('onboarding.guides.excel.step1'),
      detail: t('onboarding.guides.excel.step1_detail'),
    },
    {
      text: t('onboarding.guides.excel.step2'),
      detail: t('onboarding.guides.excel.step2_detail'),
    },
  ],
}

const guideSteps = reactive(
  (guideConfigs[props.source] || guideConfigs.excel).map(step => ({
    ...step,
    checked: false,
  }))
)

const filesToPrepare = reactive([
  {
    key: 'journal',
    label: t('onboarding.step2.file_journal'),
    format: props.source === 'pantheon' ? '.txt' : '.csv',
    checked: false,
    optional: false,
  },
  {
    key: 'partners',
    label: t('onboarding.step2.file_partners'),
    format: '.csv',
    checked: false,
    optional: false,
  },
  {
    key: 'chart',
    label: t('onboarding.step2.file_chart'),
    format: '.csv',
    checked: false,
    optional: true,
  },
  {
    key: 'invoices',
    label: t('onboarding.step2.file_invoices'),
    format: '.csv',
    checked: false,
    optional: true,
  },
  {
    key: 'bank',
    label: t('onboarding.step2.file_bank'),
    format: '.csv / .pdf',
    checked: false,
    optional: false,
  },
])
</script>
