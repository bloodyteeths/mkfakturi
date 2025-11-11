<template>
  <BaseBadge :variant="statusVariant" class="px-3 py-1">
    <BaseIcon v-if="statusIcon" :name="statusIcon" class="w-4 h-4 mr-1" />
    <span>{{ statusLabel }}</span>
  </BaseBadge>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  status: {
    type: String,
    required: true,
    validator: (value) => ['DRAFT', 'SIGNED', 'SUBMITTED', 'ACCEPTED', 'REJECTED'].includes(value),
  },
})

const { t } = useI18n()

const statusVariant = computed(() => {
  const variants = {
    DRAFT: 'gray',
    SIGNED: 'blue',
    SUBMITTED: 'yellow',
    ACCEPTED: 'green',
    REJECTED: 'red',
  }
  return variants[props.status] || 'gray'
})

const statusIcon = computed(() => {
  const icons = {
    DRAFT: 'DocumentTextIcon',
    SIGNED: 'ShieldCheckIcon',
    SUBMITTED: 'PaperAirplaneIcon',
    ACCEPTED: 'CheckCircleIcon',
    REJECTED: 'XCircleIcon',
  }
  return icons[props.status] || null
})

const statusLabel = computed(() => {
  return t(`e_invoice.status_${props.status.toLowerCase()}`)
})
</script>
// CLAUDE-CHECKPOINT
