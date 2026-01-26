<template>
  <BaseModal
    :show="modelValue"
    @close="closeModal"
    @update:show="$emit('update:modelValue', $event)"
  >
    <template #header>
      <h3 class="text-lg font-semibold text-gray-900">
        {{ $t('banking.categorize_transaction') }}
      </h3>
    </template>

    <div v-if="transaction" class="p-6 space-y-6">
      <!-- Transaction Summary -->
      <div class="bg-gray-50 rounded-lg p-4">
        <h4 class="text-sm font-medium text-gray-900 mb-3">
          {{ $t('banking.transaction_summary') }}
        </h4>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="text-xs text-gray-500">{{ $t('banking.date') }}</p>
            <p class="text-sm font-medium text-gray-900">
              {{ formatDate(transaction.transaction_date) }}
            </p>
          </div>
          <div>
            <p class="text-xs text-gray-500">{{ $t('banking.amount') }}</p>
            <p
              class="text-sm font-semibold"
              :class="transaction.amount > 0 ? 'text-green-600' : 'text-red-600'"
            >
              {{ formatAmount(transaction.amount, transaction.currency) }}
            </p>
          </div>
          <div class="col-span-2">
            <p class="text-xs text-gray-500">{{ $t('banking.description') }}</p>
            <p class="text-sm font-medium text-gray-900">
              {{ transaction.description || transaction.remittance_info }}
            </p>
          </div>
          <div v-if="transaction.counterparty_name" class="col-span-2">
            <p class="text-xs text-gray-500">{{ $t('banking.counterparty') }}</p>
            <p class="text-sm font-medium text-gray-900">
              {{ transaction.counterparty_name }}
            </p>
          </div>
        </div>
      </div>

      <!-- AI Suggestion (if available) -->
      <div v-if="aiSuggestion" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-start">
          <BaseIcon name="SparklesIcon" class="h-5 w-5 text-blue-600 mt-0.5 mr-3" />
          <div class="flex-1">
            <p class="text-sm font-medium text-blue-900 mb-2">
              {{ $t('banking.ai_suggestion') }}
            </p>
            <p class="text-sm text-blue-800">
              {{ aiSuggestion.category_name }}
            </p>
            <p class="text-xs text-blue-600 mt-1">
              {{ $t('banking.confidence') }}: {{ Math.round(aiSuggestion.confidence * 100) }}%
            </p>
          </div>
          <BaseButton
            variant="link"
            size="sm"
            @click="useAiSuggestion"
          >
            {{ $t('banking.use_suggestion') }}
          </BaseButton>
        </div>
      </div>

      <!-- Category Selection -->
      <BaseInputGroup :label="$t('banking.expense_category')" required>
        <BaseSelect
          v-model="selectedCategory"
          :options="categoryOptions"
          :searchable="true"
          :show-labels="false"
          :placeholder="$t('banking.select_category')"
          label="label"
          value-prop="value"
          :invalid="errors.category"
        />
        <BaseInputError v-if="errors.category">
          {{ errors.category }}
        </BaseInputError>
      </BaseInputGroup>

      <!-- Notes -->
      <BaseInputGroup :label="$t('banking.notes')">
        <BaseTextarea
          v-model="notes"
          :placeholder="$t('banking.add_notes_placeholder')"
          rows="3"
        />
      </BaseInputGroup>

      <!-- Auto-create Expense Option -->
      <div class="flex items-start">
        <BaseCheckbox
          v-model="createExpense"
          :label="$t('banking.create_expense_from_transaction')"
        />
      </div>
      <p v-if="createExpense" class="text-sm text-gray-500 ml-6 -mt-4">
        {{ $t('banking.create_expense_description') }}
      </p>

      <!-- Error Message -->
      <div v-if="errorMessage" class="p-4 bg-red-50 border border-red-200 rounded-lg">
        <div class="flex items-start">
          <BaseIcon name="ExclamationTriangleIcon" class="h-5 w-5 text-red-600 mt-0.5 mr-3" />
          <p class="text-sm text-red-800">{{ errorMessage }}</p>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-between items-center">
        <BaseButton
          v-if="!aiSuggestion"
          variant="link"
          :loading="isLoadingAiSuggestion"
          @click="fetchAiSuggestion"
        >
          <template #left="slotProps">
            <BaseIcon name="SparklesIcon" :class="slotProps.class" />
          </template>
          {{ $t('banking.get_ai_suggestion') }}
        </BaseButton>
        <div v-else></div>

        <div class="flex space-x-3">
          <BaseButton
            variant="secondary"
            @click="closeModal"
          >
            {{ $t('general.cancel') }}
          </BaseButton>
          <BaseButton
            variant="primary"
            :disabled="!selectedCategory || isSaving"
            :loading="isSaving"
            @click="saveCategorization"
          >
            {{ $t('general.save') }}
          </BaseButton>
        </div>
      </div>
    </template>
  </BaseModal>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'
import axios from 'axios'

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true
  },
  transaction: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['update:modelValue', 'categorized'])

const { t } = useI18n()
const notificationStore = useNotificationStore()

// State
const selectedCategory = ref(null)
const notes = ref('')
const createExpense = ref(false)
const aiSuggestion = ref(null)
const categories = ref([])
const isLoadingCategories = ref(false)
const isLoadingAiSuggestion = ref(false)
const isSaving = ref(false)
const errorMessage = ref(null)
const errors = ref({})

// Computed
const categoryOptions = computed(() => {
  return categories.value.map(cat => ({
    label: cat.name,
    value: cat.id
  }))
})

// Methods
const fetchCategories = async () => {
  isLoadingCategories.value = true
  try {
    const response = await axios.get('/categories')
    categories.value = response.data.data || []
  } catch (error) {
    console.error('Failed to fetch categories:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('banking.failed_to_load_categories')
    })
  } finally {
    isLoadingCategories.value = false
  }
}

const fetchAiSuggestion = async () => {
  if (!props.transaction) return

  isLoadingAiSuggestion.value = true
  errorMessage.value = null

  try {
    // Call AI categorization endpoint (using MCP tool or internal AI)
    const response = await axios.post('/banking/transactions/suggest-category', {
      transaction_id: props.transaction.id,
      description: props.transaction.description,
      amount: props.transaction.amount,
      counterparty: props.transaction.counterparty_name
    })

    if (response.data.suggestion) {
      aiSuggestion.value = response.data.suggestion
    }
  } catch (error) {
    console.error('Failed to fetch AI suggestion:', error)
    errorMessage.value = error.response?.data?.message || t('banking.ai_suggestion_failed')
  } finally {
    isLoadingAiSuggestion.value = false
  }
}

const useAiSuggestion = () => {
  if (aiSuggestion.value && aiSuggestion.value.category_id) {
    selectedCategory.value = aiSuggestion.value.category_id
  }
}

const saveCategorization = async () => {
  // Validate
  errors.value = {}
  if (!selectedCategory.value) {
    errors.value.category = t('validation.required')
    return
  }

  isSaving.value = true
  errorMessage.value = null

  try {
    await axios.patch(`/banking/transactions/${props.transaction.id}/categorize`, {
      category_id: selectedCategory.value,
      notes: notes.value,
      create_expense: createExpense.value
    })

    notificationStore.showNotification({
      type: 'success',
      message: t('banking.transaction_categorized')
    })

    emit('categorized')
    closeModal()
  } catch (error) {
    console.error('Failed to categorize transaction:', error)
    errorMessage.value = error.response?.data?.message || t('banking.categorization_failed')
  } finally {
    isSaving.value = false
  }
}

const closeModal = () => {
  emit('update:modelValue', false)
  // Reset state
  selectedCategory.value = null
  notes.value = ''
  createExpense.value = false
  aiSuggestion.value = null
  errorMessage.value = null
  errors.value = {}
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('mk-MK', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatAmount = (amount, currency) => {
  if (amount === null || amount === undefined) return '-'

  return new Intl.NumberFormat('mk-MK', {
    style: 'currency',
    currency: currency || 'MKD'
  }).format(amount)
}

// Watchers
watch(() => props.modelValue, (newVal) => {
  if (newVal && categories.value.length === 0) {
    fetchCategories()
  }
})

// Lifecycle
onMounted(() => {
  if (props.modelValue) {
    fetchCategories()
  }
})
</script>

<!-- CLAUDE-CHECKPOINT -->
