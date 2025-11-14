<template>
  <div class="text-center py-12 px-4">
    <!-- Error Icon based on type -->
    <div class="mx-auto h-24 w-24 mb-4 flex items-center justify-center">
      <BaseIcon
        v-if="errorType === '404'"
        name="ExclamationTriangleIcon"
        class="h-24 w-24 text-yellow-500"
      />
      <BaseIcon
        v-else-if="errorType === '500'"
        name="XCircleIcon"
        class="h-24 w-24 text-red-500"
      />
      <BaseIcon
        v-else-if="errorType === 'network'"
        name="WifiIcon"
        class="h-24 w-24 text-gray-400"
      />
      <BaseIcon
        v-else
        name="ExclamationCircleIcon"
        class="h-24 w-24 text-red-500"
      />
    </div>

    <!-- Error Title -->
    <h3 class="mt-4 text-xl font-semibold text-gray-900">
      {{ title || getDefaultTitle() }}
    </h3>

    <!-- Error Description -->
    <p class="mt-2 text-sm text-gray-600 max-w-md mx-auto">
      {{ description || getDefaultDescription() }}
    </p>

    <!-- Error Code (if provided) -->
    <p v-if="errorCode" class="mt-2 text-xs text-gray-400 font-mono">
      Error Code: {{ errorCode }}
    </p>

    <!-- Action Buttons -->
    <div class="mt-6 flex flex-col sm:flex-row gap-3 justify-center">
      <!-- Retry Button -->
      <BaseButton
        v-if="showRetry"
        variant="primary"
        @click="$emit('retry')"
        :loading="retrying"
        class="min-h-[44px]"
      >
        <template #left="slotProps">
          <BaseIcon name="ArrowPathIcon" :class="slotProps.class" />
        </template>
        {{ $t('general.retry') || 'Retry' }}
      </BaseButton>

      <!-- Go Back Button -->
      <BaseButton
        v-if="showBackButton"
        variant="primary-outline"
        @click="goBack"
        class="min-h-[44px]"
      >
        <template #left="slotProps">
          <BaseIcon name="ArrowLeftIcon" :class="slotProps.class" />
        </template>
        {{ $t('general.go_back') || 'Go Back' }}
      </BaseButton>

      <!-- Contact Support Button -->
      <BaseButton
        v-if="showContactSupport"
        variant="secondary"
        @click="$emit('contact-support')"
        class="min-h-[44px]"
      >
        <template #left="slotProps">
          <BaseIcon name="ChatBubbleLeftRightIcon" :class="slotProps.class" />
        </template>
        {{ $t('general.contact_support') || 'Contact Support' }}
      </BaseButton>
    </div>

    <!-- Custom Slot -->
    <div v-if="$slots.custom" class="mt-6">
      <slot name="custom" />
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  errorType: {
    type: String,
    default: 'generic',
    validator: (value) => ['404', '500', 'network', 'generic'].includes(value),
  },
  title: {
    type: String,
    default: '',
  },
  description: {
    type: String,
    default: '',
  },
  errorCode: {
    type: String,
    default: '',
  },
  showRetry: {
    type: Boolean,
    default: true,
  },
  showBackButton: {
    type: Boolean,
    default: true,
  },
  showContactSupport: {
    type: Boolean,
    default: false,
  },
  retrying: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['retry', 'contact-support'])

const router = useRouter()
const { t } = useI18n()

function getDefaultTitle() {
  switch (props.errorType) {
    case '404':
      return t('errors.not_found') || 'Page Not Found'
    case '500':
      return t('errors.server_error') || 'Server Error'
    case 'network':
      return t('errors.network_error') || 'Network Connection Error'
    default:
      return t('errors.something_went_wrong') || 'Something Went Wrong'
  }
}

function getDefaultDescription() {
  switch (props.errorType) {
    case '404':
      return t('errors.not_found_description') || 'The page you are looking for does not exist or has been moved.'
    case '500':
      return t('errors.server_error_description') || 'An unexpected error occurred on our server. Please try again later.'
    case 'network':
      return t('errors.network_error_description') || 'Unable to connect to the server. Please check your internet connection and try again.'
    default:
      return t('errors.generic_description') || 'An unexpected error occurred. Please try again or contact support if the problem persists.'
  }
}

function goBack() {
  router.back()
}
</script>

// CLAUDE-CHECKPOINT
