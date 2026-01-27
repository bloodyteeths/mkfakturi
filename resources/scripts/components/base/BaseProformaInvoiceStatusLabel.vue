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
      return t('proforma_invoices.statuses.draft')
    case 'SENT':
      return t('proforma_invoices.statuses.sent')
    case 'VIEWED':
      return t('proforma_invoices.statuses.viewed')
    case 'EXPIRED':
      return t('proforma_invoices.statuses.expired')
    case 'CONVERTED':
      return t('proforma_invoices.statuses.converted')
    case 'REJECTED':
      return t('proforma_invoices.statuses.rejected')
    default:
      return props.status
  }
})
</script>
