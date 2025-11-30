<template>
  <BaseModal :show="show" @close="$emit('close')">
    <template #header>
      <div class="flex justify-between w-full">
        <span class="flex items-center">
          <BaseIcon name="ExclamationTriangleIcon" class="w-6 h-6 text-yellow-500 mr-2" />
          {{ $t('expenses.duplicate_warning_title') }}
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
        {{ $t('expenses.duplicate_warning_message') }}
      </p>

      <div v-if="duplicates && duplicates.length" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
        <h4 class="font-medium text-yellow-800 mb-2">{{ $t('expenses.existing_expenses') }}</h4>
        <div
          v-for="expense in duplicates"
          :key="expense.id"
          class="flex justify-between items-center py-2 border-b border-yellow-200 last:border-b-0"
        >
          <div>
            <span class="text-sm font-medium text-gray-800">{{ expense.invoice_number }}</span>
            <span v-if="expense.category" class="text-sm text-gray-500 ml-2">
              ({{ expense.category }})
            </span>
          </div>
          <div class="text-right">
            <div class="text-sm font-medium text-gray-800">
              {{ formatAmount(expense.amount) }}
            </div>
            <div class="text-xs text-gray-500">
              {{ expense.expense_date }}
            </div>
          </div>
        </div>
      </div>

      <p class="text-sm text-gray-600">
        {{ $t('expenses.duplicate_warning_confirm') }}
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
        {{ $t('expenses.save_anyway') }}
      </BaseButton>
    </div>
  </BaseModal>
</template>

<script setup>
import { useCompanyStore } from '@/scripts/admin/stores/company'

defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  duplicates: {
    type: Array,
    default: () => [],
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
</script>
// CLAUDE-CHECKPOINT
