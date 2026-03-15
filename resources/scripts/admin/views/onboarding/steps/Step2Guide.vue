<template>
  <div class="mt-6">
    <!-- Source badge -->
    <div class="flex items-center gap-2 mb-4">
      <h3 class="text-sm font-medium text-gray-700">
        {{ $t('onboarding.step2.title', { source: sourceLabel }) }}
      </h3>
    </div>

    <!-- Export steps as numbered list -->
    <div class="rounded-lg border border-gray-200 bg-white p-4 mb-4">
      <ol class="space-y-3">
        <li
          v-for="(step, index) in guideSteps"
          :key="index"
          class="flex items-start gap-3"
        >
          <span class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-600">
            {{ index + 1 }}
          </span>
          <div class="flex-1">
            <p class="text-sm text-gray-900">{{ step.text }}</p>
            <p v-if="step.detail" class="mt-0.5 text-xs text-gray-500">{{ step.detail }}</p>
            <a
              v-if="step.link"
              :href="step.link"
              target="_blank"
              rel="noopener"
              class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-primary-600 hover:text-primary-700"
            >
              <BaseIcon name="BookOpenIcon" class="h-3.5 w-3.5" />
              {{ step.linkText || $t('onboarding.step2.documentation') }}
              <BaseIcon name="ArrowTopRightOnSquareIcon" class="h-3 w-3" />
            </a>
          </div>
        </li>
      </ol>
    </div>

    <!-- Files to prepare -->
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
      <h4 class="text-sm font-medium text-gray-700 mb-3">
        {{ $t('onboarding.step2.files_to_prepare') }}
      </h4>
      <div class="space-y-2">
        <label
          v-for="file in filesToPrepare"
          :key="file.key"
          class="flex items-center gap-2.5 text-sm cursor-pointer"
        >
          <input
            v-model="file.checked"
            type="checkbox"
            class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
          />
          <span :class="file.checked ? 'text-gray-400 line-through' : 'text-gray-700'">
            {{ file.label }}
          </span>
          <span class="text-xs text-gray-400 font-mono">{{ file.format }}</span>
          <span v-if="file.optional" class="text-[10px] text-gray-400 uppercase">
            ({{ $t('onboarding.step2.optional') }})
          </span>
        </label>
      </div>
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

const guideSteps = guideConfigs[props.source] || guideConfigs.excel

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
