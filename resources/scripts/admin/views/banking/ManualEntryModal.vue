<template>
  <BaseModal :show="modelValue" @close="close" size="lg">
    <template #header>
      <h3 class="text-lg font-medium text-gray-900">
        {{ $t('banking.manual_entry.title', 'Add Manual Transaction') }}
      </h3>
    </template>

    <form @submit.prevent="submit">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-6">
        <!-- Bank Account -->
        <BaseInputGroup
          :label="$t('banking.account')"
          required
          :error="v$.bank_account_id.$errors[0]?.$message"
          class="md:col-span-2"
        >
          <BaseMultiselect
            v-model="form.bank_account_id"
            :options="accounts"
            :searchable="true"
            track-by="id"
            label="label"
            value-prop="id"
            :placeholder="$t('banking.select_account')"
          />
        </BaseInputGroup>

        <!-- Transaction Type Toggle -->
        <BaseInputGroup
          :label="$t('banking.manual_entry.type', 'Type')"
          required
        >
          <div class="flex rounded-md shadow-sm">
            <button
              type="button"
              class="flex-1 px-4 py-2 text-sm font-medium border rounded-l-md focus:outline-none focus:ring-2 focus:ring-primary-500"
              :class="form.transaction_type === 'credit'
                ? 'bg-green-50 text-green-700 border-green-300'
                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
              @click="form.transaction_type = 'credit'"
            >
              {{ $t('banking.manual_entry.credit', 'Credit (Inflow)') }}
            </button>
            <button
              type="button"
              class="flex-1 px-4 py-2 text-sm font-medium border-t border-b border-r rounded-r-md focus:outline-none focus:ring-2 focus:ring-primary-500"
              :class="form.transaction_type === 'debit'
                ? 'bg-red-50 text-red-700 border-red-300'
                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
              @click="form.transaction_type = 'debit'"
            >
              {{ $t('banking.manual_entry.debit', 'Debit (Outflow)') }}
            </button>
          </div>
        </BaseInputGroup>

        <!-- Amount -->
        <BaseInputGroup
          :label="$t('general.amount')"
          required
          :error="v$.amount.$errors[0]?.$message"
        >
          <BaseInput
            v-model="form.amount"
            type="number"
            step="0.01"
            min="0.01"
            :placeholder="$t('banking.manual_entry.amount_placeholder', '0.00')"
          />
        </BaseInputGroup>

        <!-- Transaction Date -->
        <BaseInputGroup
          :label="$t('general.date')"
          required
          :error="v$.transaction_date.$errors[0]?.$message"
        >
          <BaseDatePicker
            v-model="form.transaction_date"
            :calendar-button="true"
            calendar-button-icon="CalendarDaysIcon"
          />
        </BaseInputGroup>

        <!-- Description -->
        <BaseInputGroup
          :label="$t('general.description')"
          required
          :error="v$.description.$errors[0]?.$message"
        >
          <BaseInput
            v-model="form.description"
            :placeholder="$t('banking.manual_entry.description_placeholder', 'Transaction description')"
          />
        </BaseInputGroup>

        <!-- Counterparty Name -->
        <BaseInputGroup
          :label="$t('banking.manual_entry.counterparty', 'Counterparty')"
        >
          <BaseInput
            v-model="form.counterparty_name"
            :placeholder="$t('banking.manual_entry.counterparty_placeholder', 'Name of sender/receiver')"
          />
        </BaseInputGroup>

        <!-- Counterparty IBAN -->
        <BaseInputGroup
          :label="$t('banking.manual_entry.iban', 'IBAN')"
        >
          <BaseInput
            v-model="form.counterparty_iban"
            :placeholder="$t('banking.manual_entry.iban_placeholder', 'MK07...')"
            maxlength="34"
          />
        </BaseInputGroup>

        <!-- Payment Reference -->
        <BaseInputGroup
          :label="$t('banking.manual_entry.reference', 'Payment Reference')"
        >
          <BaseInput
            v-model="form.payment_reference"
            :placeholder="$t('banking.manual_entry.reference_placeholder', 'Reference number')"
          />
        </BaseInputGroup>

        <!-- Remittance Info -->
        <BaseInputGroup
          :label="$t('banking.manual_entry.remittance', 'Remittance Info')"
          class="md:col-span-2"
        >
          <BaseTextarea
            v-model="form.remittance_info"
            :placeholder="$t('banking.manual_entry.remittance_placeholder', 'Additional payment details')"
            rows="2"
          />
        </BaseInputGroup>
      </div>

      <div class="flex justify-end space-x-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
        <BaseButton variant="primary-outline" type="button" @click="close">
          {{ $t('general.cancel') }}
        </BaseButton>
        <BaseButton variant="primary" type="submit" :loading="isSaving">
          {{ $t('banking.manual_entry.save', 'Add Transaction') }}
        </BaseButton>
      </div>
    </form>
  </BaseModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'
import { required, minValue, helpers } from '@vuelidate/validators'
import useVuelidate from '@vuelidate/core'
import axios from 'axios'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  accounts: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:modelValue', 'created'])

const { t } = useI18n()
const notificationStore = useNotificationStore()

const isSaving = ref(false)

function getLocalDateString() {
  const now = new Date()
  const year = now.getFullYear()
  const month = String(now.getMonth() + 1).padStart(2, '0')
  const day = String(now.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const form = ref({
  bank_account_id: null,
  transaction_type: 'credit',
  amount: null,
  transaction_date: getLocalDateString(),
  description: '',
  counterparty_name: '',
  counterparty_iban: '',
  payment_reference: '',
  remittance_info: '',
})

const rules = computed(() => ({
  bank_account_id: {
    required: helpers.withMessage(t('validation.required'), required),
  },
  amount: {
    required: helpers.withMessage(t('validation.required'), required),
    minValue: helpers.withMessage(
      t('validation.min_value', { min: 0.01 }, 'Must be greater than 0'),
      minValue(0.01)
    ),
  },
  transaction_date: {
    required: helpers.withMessage(t('validation.required'), required),
  },
  description: {
    required: helpers.withMessage(t('validation.required'), required),
  },
}))

const v$ = useVuelidate(rules, form)

// Reset form when modal opens
watch(() => props.modelValue, (newVal) => {
  if (newVal) {
    resetForm()
  }
})

function resetForm() {
  form.value = {
    bank_account_id: props.accounts.length === 1 ? props.accounts[0].id : null,
    transaction_type: 'credit',
    amount: null,
    transaction_date: getLocalDateString(),
    description: '',
    counterparty_name: '',
    counterparty_iban: '',
    payment_reference: '',
    remittance_info: '',
  }
  v$.value.$reset()
}

function close() {
  emit('update:modelValue', false)
}

async function submit() {
  const isValid = await v$.value.$validate()
  if (!isValid) return

  isSaving.value = true
  try {
    await axios.post('/banking/transactions/manual', {
      bank_account_id: form.value.bank_account_id,
      amount: form.value.amount,
      transaction_type: form.value.transaction_type,
      transaction_date: form.value.transaction_date,
      description: form.value.description,
      counterparty_name: form.value.counterparty_name || null,
      counterparty_iban: form.value.counterparty_iban || null,
      payment_reference: form.value.payment_reference || null,
      remittance_info: form.value.remittance_info || null,
    })

    notificationStore.showNotification({
      type: 'success',
      message: t('banking.manual_entry.success', 'Transaction added successfully'),
    })

    emit('created')
    close()
  } catch (error) {
    const message = error.response?.data?.error || t('banking.manual_entry.failed', 'Failed to add transaction')
    notificationStore.showNotification({
      type: 'error',
      message,
    })
  } finally {
    isSaving.value = false
  }
}
</script>

// CLAUDE-CHECKPOINT
