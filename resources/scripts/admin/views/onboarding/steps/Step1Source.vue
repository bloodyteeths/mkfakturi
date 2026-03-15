<template>
  <div>
    <h3 class="text-sm font-medium text-gray-700 mb-3">
      {{ $t('onboarding.step1.title') }}
    </h3>
    <div class="space-y-2">
      <label
        v-for="source in sources"
        :key="source.key"
        class="flex items-center gap-3 rounded-lg border px-4 py-3 cursor-pointer transition-colors"
        :class="
          selectedSource === source.key
            ? 'border-primary-500 bg-primary-50'
            : 'border-gray-200 hover:border-gray-300'
        "
      >
        <input
          type="radio"
          name="onboarding-source"
          :value="source.key"
          :checked="selectedSource === source.key"
          class="h-4 w-4 text-primary-600 border-gray-300 focus:ring-primary-500"
          @change="$emit('select', source.key)"
        />
        <div>
          <span class="text-sm font-medium text-gray-900">{{ source.name }}</span>
          <span v-if="source.company" class="ml-1.5 text-xs text-gray-400">{{ source.company }}</span>
        </div>
      </label>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineProps({
  selectedSource: {
    type: String,
    default: null,
  },
})

defineEmits(['select'])

const sources = computed(() => [
  { key: 'pantheon', name: t('onboarding.step1.pantheon_name'), company: 'Datalab' },
  { key: 'zonel', name: t('onboarding.step1.zonel_name'), company: 'Zonel Software' },
  { key: 'ekonomika', name: t('onboarding.step1.ekonomika_name') },
  { key: 'astral', name: t('onboarding.step1.astral_name') },
  { key: 'b2b', name: t('onboarding.step1.b2b_name') },
  { key: 'excel', name: t('onboarding.step1.excel_name') },
  { key: 'fresh', name: t('onboarding.step1.fresh_name') },
])
</script>
