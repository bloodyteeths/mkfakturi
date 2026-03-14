<template>
  <div>
    <!-- Welcome section -->
    <div class="mb-8 text-center">
      <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-primary-500 to-indigo-500 shadow-lg shadow-primary-500/25">
        <BaseIcon name="RocketLaunchIcon" class="h-8 w-8 text-white" />
      </div>
      <h2 class="text-2xl font-bold text-gray-900 tracking-tight">
        {{ $t('onboarding.step1.title') }}
      </h2>
      <p class="mx-auto mt-2 max-w-lg text-sm text-gray-500 leading-relaxed">
        {{ $t('onboarding.step1.subtitle') }}
      </p>
    </div>

    <!-- Software Source Grid -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <button
        v-for="source in sources"
        :key="source.key"
        class="group relative overflow-hidden rounded-xl border-2 p-5 text-left transition-all duration-300 hover:-translate-y-1 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
        :class="
          selectedSource === source.key
            ? 'border-primary-500 bg-primary-50 shadow-lg shadow-primary-500/10'
            : 'border-gray-100 bg-white hover:border-primary-200 hover:shadow-primary-500/5'
        "
        @click="$emit('select', source.key)"
      >
        <!-- Gradient accent bar -->
        <div
          class="absolute inset-x-0 top-0 h-1 transition-all duration-300"
          :class="[
            source.gradient,
            selectedSource === source.key ? 'opacity-100' : 'opacity-0 group-hover:opacity-60',
          ]"
        />

        <!-- Icon -->
        <div
          class="mb-3 flex h-12 w-12 items-center justify-center rounded-xl text-2xl transition-transform duration-300 group-hover:scale-110"
          :class="source.iconBg"
        >
          {{ source.emoji }}
        </div>

        <!-- Name -->
        <h3 class="mb-1 text-base font-bold text-gray-900">
          {{ source.name }}
        </h3>

        <!-- Subtitle (software company) -->
        <p v-if="source.company" class="mb-1 text-[11px] font-medium text-gray-400 uppercase tracking-wider">
          {{ source.company }}
        </p>

        <!-- Description -->
        <p class="text-xs text-gray-500 leading-relaxed">
          {{ source.description }}
        </p>

        <!-- Selected indicator -->
        <div
          v-if="selectedSource === source.key"
          class="mt-3 flex items-center gap-1.5 text-xs font-semibold text-primary-600"
        >
          <div class="flex h-5 w-5 items-center justify-center rounded-full bg-primary-500">
            <BaseIcon name="CheckIcon" class="h-3 w-3 text-white" />
          </div>
          {{ $t('onboarding.step1.selected') }}
        </div>
      </button>
    </div>

    <!-- Bottom hint -->
    <p class="mt-6 text-center text-xs text-gray-400">
      {{ $t('onboarding.step1.hint', 'Click your current software to get personalized export instructions') }}
    </p>
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
  {
    key: 'pantheon',
    name: t('onboarding.step1.pantheon_name'),
    company: 'Datalab',
    description: t('onboarding.step1.pantheon_desc'),
    emoji: '\uD83C\uDFDB\uFE0F',
    iconBg: 'bg-blue-50',
    gradient: 'bg-gradient-to-r from-blue-500 to-blue-600',
  },
  {
    key: 'zonel',
    name: t('onboarding.step1.zonel_name'),
    company: 'Zonel Software',
    description: t('onboarding.step1.zonel_desc'),
    emoji: '\uD83D\uDD27',
    iconBg: 'bg-purple-50',
    gradient: 'bg-gradient-to-r from-purple-500 to-purple-600',
  },
  {
    key: 'ekonomika',
    name: t('onboarding.step1.ekonomika_name'),
    description: t('onboarding.step1.ekonomika_desc'),
    emoji: '\uD83D\uDCCA',
    iconBg: 'bg-green-50',
    gradient: 'bg-gradient-to-r from-green-500 to-green-600',
  },
  {
    key: 'astral',
    name: t('onboarding.step1.astral_name'),
    description: t('onboarding.step1.astral_desc'),
    emoji: '\u2B50',
    iconBg: 'bg-amber-50',
    gradient: 'bg-gradient-to-r from-amber-500 to-amber-600',
  },
  {
    key: 'b2b',
    name: t('onboarding.step1.b2b_name'),
    description: t('onboarding.step1.b2b_desc'),
    emoji: '\uD83E\uDD1D',
    iconBg: 'bg-orange-50',
    gradient: 'bg-gradient-to-r from-orange-500 to-orange-600',
  },
  {
    key: 'excel',
    name: t('onboarding.step1.excel_name'),
    description: t('onboarding.step1.excel_desc'),
    emoji: '\uD83D\uDCC4',
    iconBg: 'bg-teal-50',
    gradient: 'bg-gradient-to-r from-teal-500 to-teal-600',
  },
  {
    key: 'fresh',
    name: t('onboarding.step1.fresh_name'),
    description: t('onboarding.step1.fresh_desc'),
    emoji: '\uD83C\uDF31',
    iconBg: 'bg-emerald-50',
    gradient: 'bg-gradient-to-r from-emerald-500 to-emerald-600',
  },
])
</script>
