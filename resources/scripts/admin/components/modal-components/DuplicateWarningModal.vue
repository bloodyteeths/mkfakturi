<template>
  <BaseModal :show="show" @close="$emit('close')">
    <template #header>
      <div class="flex justify-between w-full">
        <span class="flex items-center">
          <BaseIcon name="ExclamationTriangleIcon" class="w-6 h-6 text-yellow-500 mr-2" />
          {{ $t('general.duplicate_warning_title') }}
        </span>
        <BaseIcon
          name="XMarkIcon"
          class="w-6 h-6 text-gray-500 cursor-pointer"
          @click="$emit('close')"
        />
      </div>
    </template>

    <div class="p-6">
      <p class="text-gray-700 mb-4">
        {{ $t(`${entityType}.duplicate_warning_message`) }}
      </p>

      <div v-if="duplicates && duplicates.length" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
        <h4 class="font-medium text-yellow-800 mb-2">{{ $t(`${entityType}.existing_records`) }}</h4>
        <div
          v-for="duplicate in duplicates"
          :key="duplicate.id"
          class="flex justify-between items-center py-2 border-b border-yellow-200 last:border-b-0"
        >
          <div>
            <span class="text-sm font-medium text-gray-800">
              {{ duplicate.name || duplicate.bill_number || duplicate.invoice_number || duplicate.po_number || '-' }}
            </span>
            <span v-if="duplicate.email" class="text-sm text-gray-500 ml-2">
              ({{ duplicate.email }})
            </span>
            <span v-if="duplicate.tax_id" class="text-sm text-gray-500 ml-2">
              {{ $t('general.tax_id') }}: {{ duplicate.tax_id }}
            </span>
          </div>
          <div class="text-right">
            <div v-if="duplicate.total" class="text-sm font-medium text-gray-800">
              {{ formatAmount(duplicate.total) }}
            </div>
            <div v-if="duplicate.match_reason" class="text-xs text-gray-500">
              {{ getMatchReasonLabel(duplicate.match_reason) }}
            </div>
            <div v-if="duplicate.bill_date || duplicate.invoice_date || duplicate.start_date || duplicate.compensation_date" class="text-xs text-gray-500">
              {{ duplicate.bill_date || duplicate.invoice_date || duplicate.start_date || duplicate.compensation_date }}
            </div>
          </div>
        </div>
      </div>

      <p class="text-sm text-gray-600">
        {{ $t('general.duplicate_warning_confirm') }}
      </p>
    </div>

    <div class="flex justify-end p-4 border-t border-gray-200 border-solid space-x-3">
      <BaseButton
        type="button"
        variant="primary-outline"
        @click="$emit('close')"
      >
        {{ $t('general.cancel') }}
      </BaseButton>

      <BaseButton
        variant="primary"
        type="button"
        @click="$emit('confirm')"
      >
        {{ $t('general.save_anyway') }}
      </BaseButton>
    </div>
  </BaseModal>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { useCompanyStore } from '@/scripts/admin/stores/company'

const { t } = useI18n()

defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  duplicates: {
    type: Array,
    default: () => [],
  },
  entityType: {
    type: String,
    required: true,
  },
})

defineEmits(['close', 'confirm'])

const companyStore = useCompanyStore()

function formatAmount(amount) {
  if (!amount) return '-'
  const currency = companyStore.selectedCompanyCurrency
  const precision = currency?.precision ?? 2
  const symbol = currency?.symbol ?? ''
  const formatted = (amount / Math.pow(10, precision)).toFixed(precision)
  return `${symbol} ${formatted}`
}

function getMatchReasonLabel(reason) {
  const labels = {
    'exact_name': t('general.match_exact_name'),
    'similar_name': t('general.match_similar_name'),
    'transliterated_name': t('general.match_transliterated'),
    'tax_id': t('general.match_tax_id'),
    'email': t('general.match_email'),
    'phone': t('general.match_phone'),
    'similar_amount_date': t('general.match_similar_amount'),
  }
  return labels[reason] || reason
}
</script>
