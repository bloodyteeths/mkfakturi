<template>
  <div>
    <h2 class="mb-2 text-xl font-semibold text-gray-900">
      {{ $t('onboarding.partner.step2_title') }}
    </h2>
    <p class="mb-6 text-sm text-gray-500">
      {{ $t('onboarding.partner.step2_subtitle') }}
    </p>

    <!-- Company selector -->
    <div class="mb-6">
      <label class="mb-1 block text-sm font-medium text-gray-700">
        {{ $t('onboarding.partner.select_company') }}
      </label>
      <div v-if="companies.length > 0" class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="company in companies"
          :key="company.id"
          class="cursor-pointer rounded-lg border-2 p-3 transition-all hover:shadow-sm"
          :class="selectedCompany?.id === company.id ? 'border-primary-500 bg-primary-50' : 'border-gray-200'"
          @click="$emit('select-company', company)"
        >
          <div class="flex items-center gap-2">
            <BaseIcon name="BuildingOffice2Icon" class="h-5 w-5 text-gray-400" />
            <div>
              <p class="text-sm font-medium text-gray-900">{{ company.name }}</p>
              <p v-if="company.tax_id" class="text-xs text-gray-500">{{ company.tax_id }}</p>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="rounded-lg border border-gray-200 bg-gray-50 p-6 text-center">
        <p class="text-sm text-gray-500">{{ $t('onboarding.partner.no_companies') }}</p>
      </div>
    </div>

    <!-- Source selector (only when company selected) -->
    <div v-if="selectedCompany" class="mb-6">
      <label class="mb-2 block text-sm font-medium text-gray-700">
        {{ $t('onboarding.partner.source_question', { company: selectedCompany.name }) }}
      </label>
      <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
        <div
          v-for="source in sources"
          :key="source.key"
          class="cursor-pointer rounded-lg border-2 p-3 text-center transition-all hover:shadow-sm"
          :class="selectedSource === source.key ? 'border-primary-500 bg-primary-50' : 'border-gray-200'"
          @click="$emit('select-source', source.key)"
        >
          <span class="text-xl">{{ source.emoji }}</span>
          <p class="mt-1 text-xs font-medium text-gray-900">{{ source.name }}</p>
        </div>
      </div>
    </div>

    <!-- Next button -->
    <BaseButton
      v-if="selectedCompany"
      variant="primary"
      @click="$emit('next')"
    >
      {{ $t('onboarding.wizard.next') }}
      <template #right="slotProps">
        <BaseIcon :class="slotProps.class" name="ArrowRightIcon" />
      </template>
    </BaseButton>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineProps({
  companies: { type: Array, default: () => [] },
  selectedCompany: { type: Object, default: null },
  selectedSource: { type: String, default: null },
})

defineEmits(['select-company', 'select-source', 'next'])

const sources = computed(() => [
  { key: 'pantheon', name: 'Pantheon', emoji: '\uD83C\uDFDB\uFE0F' },
  { key: 'zonel', name: 'Helix', emoji: '\uD83D\uDD27' },
  { key: 'ekonomika', name: 'Ekonomika', emoji: '\uD83D\uDCCA' },
  { key: 'astral', name: 'Astral', emoji: '\u2B50' },
  { key: 'b2b', name: 'B2B', emoji: '\uD83E\uDD1D' },
  { key: 'excel', name: 'Excel', emoji: '\uD83D\uDCC4' },
  { key: 'fresh', name: t('onboarding.step1.fresh_name'), emoji: '\uD83C\uDF31' },
])
</script>
