<template>
  <div class="flex items-center gap-2">
    <BaseBadge
      :bg-color="badgeColor"
      :text-color="textColor"
      :class="sizeClass"
    >
      <div class="flex items-center gap-1">
        <BaseIcon
          :name="iconName"
          :class="iconSizeClass"
        />
        <span>{{ confidencePercent }}%</span>
      </div>
    </BaseBadge>
    <span
      v-if="reason && showReason"
      :class="['text-xs text-gray-500', reasonSizeClass]"
      :title="reasonTooltip"
    >
      {{ reasonText }}
    </span>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  confidence: {
    type: Number,
    default: 0.3,
    validator: (value) => !isNaN(value) && value >= 0 && value <= 1,
  },
  reason: {
    type: String,
    default: null,
    validator: (value) => {
      return value === null || ['learned', 'pattern', 'default'].includes(value)
    },
  },
  showReason: {
    type: Boolean,
    default: true,
  },
  size: {
    type: String,
    default: 'base',
    validator: (value) => ['sm', 'base', 'lg'].includes(value),
  },
})

// Computed
const confidencePercent = computed(() => {
  const value = props.confidence ?? 0.3
  return isNaN(value) ? 30 : Math.round(value * 100)
})

const badgeColor = computed(() => {
  if (props.confidence >= 0.8) return 'bg-green-100'
  if (props.confidence >= 0.5) return 'bg-yellow-100'
  return 'bg-red-100'
})

const textColor = computed(() => {
  if (props.confidence >= 0.8) return 'text-green-800'
  if (props.confidence >= 0.5) return 'text-yellow-800'
  return 'text-red-800'
})

const iconName = computed(() => {
  if (props.confidence >= 0.8) return 'CheckCircleIcon'
  if (props.confidence >= 0.5) return 'ExclamationTriangleIcon'
  return 'XCircleIcon'
})

const sizeClass = computed(() => {
  switch (props.size) {
    case 'sm':
      return 'text-xs px-2 py-0.5'
    case 'lg':
      return 'text-base px-3 py-1.5'
    case 'base':
    default:
      return 'text-sm px-2.5 py-1'
  }
})

const iconSizeClass = computed(() => {
  switch (props.size) {
    case 'sm':
      return 'h-3 w-3'
    case 'lg':
      return 'h-5 w-5'
    case 'base':
    default:
      return 'h-4 w-4'
  }
})

const reasonSizeClass = computed(() => {
  switch (props.size) {
    case 'sm':
      return 'text-xs'
    case 'lg':
      return 'text-sm'
    case 'base':
    default:
      return 'text-xs'
  }
})

const reasonText = computed(() => {
  if (!props.reason) return ''

  switch (props.reason) {
    case 'learned':
      return t('partner.accounting.learned_mapping')
    case 'pattern':
      return t('partner.accounting.pattern_match')
    case 'default':
      return t('partner.accounting.default_account')
    default:
      return ''
  }
})

const reasonTooltip = computed(() => {
  if (!props.reason) return ''

  switch (props.reason) {
    case 'learned':
      return t('partner.accounting.learned_mapping_help')
    case 'pattern':
      return t('partner.accounting.pattern_match_help')
    case 'default':
      return t('partner.accounting.default_account_help')
    default:
      return ''
  }
})
</script>

// CLAUDE-CHECKPOINT
