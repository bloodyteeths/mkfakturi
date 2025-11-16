<template>
  {{ labelStatus }}
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  status: {
    type: String,
    required: false,
    default: '',
  },
})

const labelStatus = computed(() => {
  switch (props.status) {
    case 'DRAFT':
      return t('general.draft')
    case 'SENT':
      return t('general.sent')
    case 'VIEWED':
      return t('bills.viewed')
    case 'COMPLETED':
      return t('bills.completed')
    case 'DUE':
      return t('general.due')
    case 'OVERDUE':
      return t('bills.overdue')
    case 'UNPAID':
      return t('bills.unpaid')
    case 'PARTIALLY_PAID':
      return t('bills.partially_paid')
    case 'PAID':
      return t('bills.paid')
    default:
      return props.status
  }
})
</script>
// CLAUDE-CHECKPOINT
