<template>
  <BaseContentPlaceholders v-if="contentLoading">
    <BaseContentPlaceholdersBox
      :rounded="true"
      class="w-full"
      style="height: 38px"
    />
  </BaseContentPlaceholders>
  <money3
    v-else
    v-model="money"
    v-bind="currencyBindings"
    :class="[inputClass, invalidClass]"
    :disabled="disabled"
  />
</template>

<script setup>
import { computed, ref } from 'vue'
import { Money3Component } from 'v-money3'
import { useCompanyStore } from '@/scripts/admin/stores/company'

let money3 = Money3Component

const props = defineProps({
  contentLoading: {
    type: Boolean,
    default: false,
  },
  modelValue: {
    type: [String, Number],
    required: true,
    default: '',
  },
  invalid: {
    type: Boolean,
    default: false,
  },
  inputClass: {
    type: String,
    default:
      'font-base block w-full sm:text-sm border-gray-200 rounded-md text-black',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  percent: {
    type: Boolean,
    default: false,
  },
  currency: {
    type: Object,
    default: null,
  },
})
const emit = defineEmits(['update:modelValue'])
const companyStore = useCompanyStore()

// FIXED: Remove hasInitialValueSet flag that was causing value emission issues
// v-money3 handles initialization correctly, we don't need to block the first emit
const money = computed({
  get: () => props.modelValue,
  set: (value) => {
    emit('update:modelValue', value)
  },
})

const currencyBindings = computed(() => {
  const currency = props.currency
    ? props.currency
    : companyStore.selectedCompanyCurrency

  const precision = parseInt(currency.precision)

  return {
    decimal: currency.decimal_separator,
    thousands: currency.thousand_separator,
    prefix: currency.symbol + ' ',
    precision: precision,
    // CRITICAL FIX: Use masked=true for zero-precision currencies (like MKD)
    // With masked=false, v-money3 treats the value as cents and divides by 100
    // With masked=true, v-money3 uses the value as-is (no conversion)
    // For MKD: User types "1200" → stored as 1200 (not 120000)
    masked: precision === 0,
  }
})

const invalidClass = computed(() => {
  if (props.invalid) {
    return 'border-red-500 ring-red-500 focus:ring-red-500 focus:border-red-500'
  }
  return 'focus:ring-primary-400 focus:border-primary-400'
})
</script>
