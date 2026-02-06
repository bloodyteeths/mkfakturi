<template>
  <BaseModal
    :show="show"
    @close="$emit('close')"
    @update:show="$emit('close')"
  >
    <template #header>
      <h3 class="text-lg font-semibold text-gray-900">
        {{ $t('banking.split_payment') }}
      </h3>
    </template>

    <div class="p-6">
      <!-- Transaction Summary -->
      <div v-if="transaction" class="mb-6 p-4 bg-gray-50 rounded-lg">
        <p class="text-sm text-gray-500">{{ $t('banking.transaction_to_split') }}</p>
        <p class="text-lg font-semibold text-green-600">
          +{{ formatMoney(transaction.amount, transaction.currency) }}
        </p>
        <p class="text-sm text-gray-900">{{ transaction.description }}</p>
        <p v-if="transaction.counterparty_name" class="text-xs text-gray-500 mt-1">
          {{ $t('banking.from') }}: {{ transaction.counterparty_name }}
        </p>
      </div>

      <!-- Split Allocations -->
      <div class="space-y-4">
        <div
          v-for="(split, index) in splits"
          :key="index"
          class="border border-gray-200 rounded-lg p-4"
        >
          <div class="flex items-center justify-between mb-3">
            <span class="text-sm font-medium text-gray-700">
              {{ $t('banking.split_allocation') }} #{{ index + 1 }}
            </span>
            <button
              v-if="splits.length > 1"
              type="button"
              class="text-red-500 hover:text-red-700 text-sm"
              @click="removeSplit(index)"
            >
              {{ $t('general.remove') }}
            </button>
          </div>

          <!-- Invoice Selection -->
          <div class="mb-3">
            <label class="block text-sm font-medium text-gray-600 mb-1">
              {{ $t('banking.select_invoice') }}
            </label>
            <select
              v-model="split.invoice_id"
              class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm"
              @change="onInvoiceSelected(index)"
            >
              <option :value="null">-- {{ $t('banking.choose_invoice') }} --</option>
              <option
                v-for="invoice in availableInvoices(index)"
                :key="invoice.id"
                :value="invoice.id"
              >
                {{ invoice.invoice_number }} - {{ invoice.customer_name }} ({{ formatMoney(invoice.total) }})
              </option>
            </select>
          </div>

          <!-- Amount Input -->
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">
              {{ $t('banking.allocated_amount') }}
            </label>
            <div class="flex items-center space-x-2">
              <input
                v-model.number="split.amount"
                type="number"
                step="0.01"
                min="0.01"
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm"
                :placeholder="$t('banking.enter_amount')"
              />
              <button
                v-if="split.invoice_id && getInvoiceTotal(split.invoice_id)"
                type="button"
                class="text-xs text-blue-600 hover:text-blue-800 whitespace-nowrap"
                @click="split.amount = getInvoiceTotal(split.invoice_id)"
              >
                {{ $t('banking.use_full_amount') }}
              </button>
            </div>
            <p v-if="split.invoice_id" class="text-xs text-gray-400 mt-1">
              {{ $t('banking.invoice_total') }}: {{ formatMoney(getInvoiceTotal(split.invoice_id)) }}
            </p>
          </div>
        </div>
      </div>

      <!-- Add Another Split Button -->
      <button
        type="button"
        class="mt-4 flex items-center text-sm text-primary-600 hover:text-primary-800"
        @click="addSplit"
      >
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        {{ $t('banking.add_another_invoice') }}
      </button>

      <!-- Running Total -->
      <div class="mt-6 p-4 rounded-lg" :class="balanceClass">
        <div class="flex justify-between items-center">
          <span class="text-sm font-medium">{{ $t('banking.running_total') }}</span>
          <span class="text-lg font-bold">
            {{ formatMoney(splitTotal) }} / {{ formatMoney(transaction?.amount || 0) }}
          </span>
        </div>
        <div class="flex justify-between items-center mt-1">
          <span class="text-xs">{{ $t('banking.remaining') }}</span>
          <span class="text-sm" :class="remainingAmountClass">
            {{ formatMoney(remainingAmount) }}
          </span>
        </div>
        <p v-if="isWithinTolerance && !isExactMatch" class="text-xs mt-2 text-yellow-700">
          {{ $t('banking.fee_tolerance_note') }}
        </p>
        <p v-if="isOverAllocated" class="text-xs mt-2 text-red-700">
          {{ $t('banking.over_allocated_warning') }}
        </p>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-end space-x-3">
        <BaseButton
          variant="secondary"
          @click="$emit('close')"
        >
          {{ $t('general.cancel') }}
        </BaseButton>
        <BaseButton
          variant="primary"
          :disabled="!canSubmit"
          :loading="isSubmitting"
          @click="submitSplit"
        >
          {{ $t('banking.confirm_split') }}
        </BaseButton>
      </div>
    </template>
  </BaseModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'
import axios from 'axios'

const { t } = useI18n()
const notificationStore = useNotificationStore()

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  transaction: {
    type: Object,
    default: null,
  },
  reconciliationId: {
    type: [Number, null],
    default: null,
  },
  unpaidInvoices: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['close', 'split-complete'])

// State
const isSubmitting = ref(false)
const splits = ref([{ invoice_id: null, amount: null }])

// Fee tolerance: 2%
const FEE_TOLERANCE = 0.02

// Reset splits when modal opens
watch(() => props.show, (newVal) => {
  if (newVal) {
    splits.value = [{ invoice_id: null, amount: null }]
  }
})

// Pre-select suggested match invoice if available
watch(() => props.transaction, (newTx) => {
  if (newTx?.suggested_match?.invoice_id) {
    splits.value = [{
      invoice_id: newTx.suggested_match.invoice_id,
      amount: newTx.suggested_match.invoice_total || null,
    }]
  }
}, { immediate: true })

// Computed
const splitTotal = computed(() => {
  return splits.value.reduce((sum, s) => sum + (parseFloat(s.amount) || 0), 0)
})

const remainingAmount = computed(() => {
  return (props.transaction?.amount || 0) - splitTotal.value
})

const isExactMatch = computed(() => {
  return Math.abs(remainingAmount.value) < 0.01
})

const isWithinTolerance = computed(() => {
  const txAmount = props.transaction?.amount || 0
  const tolerance = txAmount * FEE_TOLERANCE
  return Math.abs(remainingAmount.value) <= tolerance && !isExactMatch.value
})

const isOverAllocated = computed(() => {
  const txAmount = props.transaction?.amount || 0
  const tolerance = txAmount * FEE_TOLERANCE
  return splitTotal.value > txAmount + tolerance
})

const balanceClass = computed(() => {
  if (isExactMatch.value) return 'bg-green-50 border border-green-200'
  if (isWithinTolerance.value) return 'bg-yellow-50 border border-yellow-200'
  if (isOverAllocated.value) return 'bg-red-50 border border-red-200'
  return 'bg-gray-50 border border-gray-200'
})

const remainingAmountClass = computed(() => {
  if (isExactMatch.value) return 'text-green-600'
  if (isWithinTolerance.value) return 'text-yellow-600'
  if (isOverAllocated.value) return 'text-red-600'
  return 'text-gray-600'
})

const hasValidSplits = computed(() => {
  return splits.value.every(s => s.invoice_id && s.amount > 0)
})

const canSubmit = computed(() => {
  return hasValidSplits.value &&
    splits.value.length >= 1 &&
    !isOverAllocated.value &&
    (isExactMatch.value || isWithinTolerance.value) &&
    !isSubmitting.value
})

// Methods
const addSplit = () => {
  splits.value.push({ invoice_id: null, amount: null })
}

const removeSplit = (index) => {
  splits.value.splice(index, 1)
}

const availableInvoices = (currentIndex) => {
  const selectedIds = splits.value
    .map((s, i) => i !== currentIndex ? s.invoice_id : null)
    .filter(Boolean)
  return props.unpaidInvoices.filter(inv => !selectedIds.includes(inv.id))
}

const getInvoiceTotal = (invoiceId) => {
  const invoice = props.unpaidInvoices.find(inv => inv.id === invoiceId)
  return invoice?.total || 0
}

const onInvoiceSelected = (index) => {
  const invoiceId = splits.value[index].invoice_id
  if (invoiceId) {
    const total = getInvoiceTotal(invoiceId)
    const txAmount = props.transaction?.amount || 0
    const currentOtherSplits = splits.value
      .filter((_, i) => i !== index)
      .reduce((sum, s) => sum + (parseFloat(s.amount) || 0), 0)
    const remaining = txAmount - currentOtherSplits

    // Auto-fill with the lesser of invoice total or remaining amount
    splits.value[index].amount = parseFloat(Math.min(total, remaining).toFixed(2))
  }
}

const submitSplit = async () => {
  if (!canSubmit.value) return

  isSubmitting.value = true

  try {
    // Use the dedicated split endpoint if reconciliationId is available
    const endpoint = props.reconciliationId
      ? `/banking/reconciliation/${props.reconciliationId}/split`
      : '/banking/reconciliation/confirm-match'

    const payload = props.reconciliationId
      ? { splits: splits.value.map(s => ({ invoice_id: s.invoice_id, amount: s.amount })) }
      : {
          reconciliation_id: props.reconciliationId,
          splits: splits.value.map(s => ({ invoice_id: s.invoice_id, amount: s.amount })),
        }

    const response = await axios.post(endpoint, payload)

    if (response.data.success) {
      notificationStore.showNotification({
        type: 'success',
        message: t('banking.split_payment_success'),
      })
      emit('split-complete')
      emit('close')
    } else {
      notificationStore.showNotification({
        type: 'error',
        message: response.data.message || t('banking.split_payment_failed'),
      })
    }
  } catch (error) {
    console.error('Split payment failed:', error)
    const msg = error.response?.data?.message || t('banking.split_payment_failed')
    notificationStore.showNotification({
      type: 'error',
      message: msg,
    })
  } finally {
    isSubmitting.value = false
  }
}

const formatMoney = (amount, currency = 'MKD') => {
  if (!amount && amount !== 0) return '0.00'
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency || 'MKD',
  }).format(amount)
}
</script>

<!-- CLAUDE-CHECKPOINT -->
