<template>
  <div
    class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden transition-all duration-200 hover:shadow-md"
    :class="{ 'ring-2 ring-primary-400': isExpanded }"
  >
    <!-- Card Header -->
    <div class="p-5">
      <div class="flex items-start justify-between">
        <div class="flex-1">
          <div class="flex items-center space-x-2 mb-1">
            <span
              class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold text-white"
              :class="periodBadgeClass"
            >
              {{ form.title }}
            </span>
            <span
              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
              :class="isAnnual ? 'bg-amber-50 text-amber-700' : 'bg-blue-50 text-blue-700'"
            >
              {{ isAnnual ? t('annual') : t('monthly') }}
            </span>
          </div>
          <h3 class="text-sm font-semibold text-gray-900 mt-2">{{ form.name }}</h3>
          <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ form.description }}</p>
        </div>
        <!-- Icon -->
        <div class="ml-3 flex-shrink-0">
          <div
            class="h-10 w-10 rounded-lg flex items-center justify-center"
            :class="iconBgClass"
          >
            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="mt-4 flex flex-wrap gap-2">
        <button
          class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-primary-50 text-primary-700 hover:bg-primary-100 focus:outline-none focus:ring-2 focus:ring-primary-400 transition-colors"
          :disabled="isLoading"
          @click="$emit('preview', formCode)"
        >
          <svg class="h-3.5 w-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
          {{ t('preview') }}
        </button>
        <button
          class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-red-50 text-red-700 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-400 transition-colors"
          :disabled="isLoading"
          @click="$emit('generate-pdf', formCode)"
        >
          <svg class="h-3.5 w-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
          </svg>
          PDF
        </button>
        <button
          class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-green-50 text-green-700 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-green-400 transition-colors"
          :disabled="isLoading"
          @click="$emit('download-xml', formCode)"
        >
          <svg class="h-3.5 w-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          XML
        </button>
      </div>
    </div>

    <!-- Loading indicator -->
    <div v-if="isLoading" class="px-5 pb-3">
      <div class="flex items-center space-x-2 text-xs text-gray-500">
        <svg class="animate-spin h-3.5 w-3.5 text-primary-500" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
        <span>{{ loadingText }}</span>
      </div>
    </div>

    <!-- Validation Results (after preview) -->
    <div v-if="validation && !isLoading" class="border-t border-gray-100 px-5 py-3 bg-gray-50">
      <!-- Errors -->
      <div v-if="validation.errors && validation.errors.length > 0" class="mb-2">
        <div v-for="(error, idx) in validation.errors" :key="'e'+idx" class="flex items-start space-x-1.5 text-xs text-red-600 mb-1">
          <svg class="h-3.5 w-3.5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
          <span>{{ error }}</span>
        </div>
      </div>
      <!-- Warnings -->
      <div v-if="validation.warnings && validation.warnings.length > 0" class="mb-2">
        <div v-for="(warning, idx) in validation.warnings" :key="'w'+idx" class="flex items-start space-x-1.5 text-xs text-amber-600 mb-1">
          <svg class="h-3.5 w-3.5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
          <span>{{ warning }}</span>
        </div>
      </div>
      <!-- Success -->
      <div v-if="(!validation.errors || validation.errors.length === 0) && (!validation.warnings || validation.warnings.length === 0)" class="flex items-center space-x-1.5 text-xs text-green-600">
        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
        </svg>
        <span>{{ t('validation_passed') }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import ujpMessages from '@/scripts/admin/i18n/ujp-forms.js'

const props = defineProps({
  formCode: { type: String, required: true },
  isLoading: { type: Boolean, default: false },
  loadingText: { type: String, default: '' },
  validation: { type: Object, default: null },
  isExpanded: { type: Boolean, default: false },
})

defineEmits(['preview', 'generate-pdf', 'download-xml'])

const locale = document.documentElement.lang || 'mk'

function t(key) {
  const parts = key.split('.')
  let val = ujpMessages[locale]?.ujp_forms
  let fallback = ujpMessages['en']?.ujp_forms
  for (const part of parts) {
    val = val?.[part]
    fallback = fallback?.[part]
  }
  return val || fallback || key
}

const form = {
  title: t(`forms.${props.formCode}.title`),
  name: t(`forms.${props.formCode}.name`),
  description: t(`forms.${props.formCode}.description`),
  period: t(`forms.${props.formCode}.period`),
}

const isAnnual = ['db', 'obrazec-36', 'obrazec-37'].includes(props.formCode)

const periodBadgeClass = {
  'ddv-04': 'bg-teal-600',
  'db': 'bg-purple-700',
  'obrazec-36': 'bg-indigo-600',
  'obrazec-37': 'bg-blue-600',
}[props.formCode] || 'bg-gray-600'

const iconBgClass = {
  'ddv-04': 'bg-teal-500',
  'db': 'bg-purple-600',
  'obrazec-36': 'bg-indigo-500',
  'obrazec-37': 'bg-blue-500',
}[props.formCode] || 'bg-gray-500'
</script>

<!-- CLAUDE-CHECKPOINT -->
