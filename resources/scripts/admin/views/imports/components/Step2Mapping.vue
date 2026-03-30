<template>
  <div class="space-y-6">
    <!-- Step Header -->
    <div class="text-center">
      <BaseHeading tag="h2" class="text-2xl font-bold text-gray-900 mb-2">
        {{ $t('imports.map_fields') }}
      </BaseHeading>
      <p class="text-gray-600 max-w-3xl mx-auto">
        {{ $t('imports.map_fields_description_detailed') }}
      </p>
    </div>

    <!-- Auto-mapped success banner -->
    <div
      v-if="allRequiredFieldsMappedWithHighConfidence"
      class="max-w-6xl mx-auto"
    >
      <div class="bg-green-50 border border-green-200 rounded-lg p-4 flex items-center">
        <BaseIcon
          name="CheckCircleIcon"
          class="w-5 h-5 text-green-500 mr-3 flex-shrink-0"
        />
        <p class="text-sm text-green-800 font-medium">
          {{ $t('imports.auto_mapped_success') }}
        </p>
      </div>
    </div>

    <!-- Required fields inline status -->
    <div class="max-w-6xl mx-auto">
      <div
        :class="[
          'flex items-center text-sm font-medium px-4 py-2 rounded-lg',
          requiredFieldsMissing === 0
            ? 'bg-green-50 text-green-700'
            : 'bg-orange-50 text-orange-700',
        ]"
      >
        <BaseIcon
          :name="requiredFieldsMissing === 0 ? 'CheckCircleIcon' : 'ExclamationTriangleIcon'"
          :class="[
            'w-4 h-4 mr-2 flex-shrink-0',
            requiredFieldsMissing === 0 ? 'text-green-500' : 'text-orange-500',
          ]"
        />
        {{ requiredFieldsMappedCount }} {{ $t('imports.of') }} {{ totalRequiredFieldsCount }} {{ $t('imports.required_fields_mapped') }}
      </div>
    </div>

    <!-- Mapping Interface -->
    <div class="max-w-6xl mx-auto">
      <BaseCard>
        <template #header>
          <div class="flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900">
              {{ $t('imports.field_mapping') }}
            </h3>
            <div class="flex space-x-2">
              <BaseButton
                variant="secondary"
                size="sm"
                @click="applyAutoMapping"
                :disabled="importStore.isLoading"
              >
                {{ $t('imports.apply_auto_mapping') }}
              </BaseButton>
              <BaseButton
                variant="secondary"
                size="sm"
                @click="clearAllMappings"
              >
                {{ $t('imports.clear_all') }}
              </BaseButton>
            </div>
          </div>
        </template>

        <!-- Mapping Table -->
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ $t('imports.source_field') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ $t('imports.sample_data') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ $t('imports.target_field') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr
                v-for="field in importStore.detectedFields"
                :key="field.name"
                :class="{
                  'bg-green-50': isFieldMapped(field.name),
                }"
              >
                <!-- Source Field -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900">
                    {{ field.name }}
                  </div>
                </td>

                <!-- Sample Data -->
                <td class="px-6 py-4">
                  <div class="max-w-xs">
                    <div
                      v-if="field.sample_data && field.sample_data.length > 0"
                      class="space-y-1"
                    >
                      <div
                        v-for="(sample, index) in field.sample_data.slice(0, 3)"
                        :key="index"
                        class="text-xs text-gray-600 truncate bg-gray-100 px-2 py-1 rounded"
                      >
                        {{ sample }}
                      </div>
                      <div v-if="field.sample_data.length > 3" class="text-xs text-gray-400">
                        +{{ field.sample_data.length - 3 }} {{ $t('imports.more_samples') }}
                      </div>
                    </div>
                    <div v-else class="text-xs text-gray-400 italic">
                      {{ $t('imports.no_sample_data') }}
                    </div>
                  </div>
                </td>

                <!-- Target Field Selection -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <BaseSelectInput
                    :value="importStore.fieldMappings[field.name] || ''"
                    @input="(value) => updateMapping(field.name, value)"
                    :options="targetFieldOptions"
                    :placeholder="$t('imports.select_target_field')"
                    size="sm"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mapping Errors -->
        <div v-if="importStore.mappingErrors.length > 0" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
          <div class="flex">
            <BaseIcon name="ExclamationTriangleIcon" class="w-5 h-5 text-red-400 mr-3 flex-shrink-0" />
            <div>
              <h4 class="text-sm font-medium text-red-800 mb-2">
                {{ $t('imports.mapping_errors') }}
              </h4>
              <ul class="text-sm text-red-700 space-y-1">
                <li v-for="error in importStore.mappingErrors" :key="error">
                  {{ error }}
                </li>
              </ul>
            </div>
          </div>
        </div>
      </BaseCard>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, defineExpose } from 'vue'
import { useI18n } from 'vue-i18n'

// Components
import BaseHeading from '@/scripts/components/base/BaseHeading.vue'
import BaseCard from '@/scripts/components/base/BaseCard.vue'
import BaseButton from '@/scripts/components/base/BaseButton.vue'
import BaseIcon from '@/scripts/components/base/BaseIcon.vue'
import BaseSelectInput from '@/scripts/components/base/BaseSelectInput.vue'

// Store
import { useImportStore } from '@/scripts/admin/stores/import'

const { t } = useI18n()
const importStore = useImportStore()

const emit = defineEmits(['auto-advance-ready'])

// Computed
const targetFieldOptions = computed(() => [
  { label: t('imports.do_not_import'), value: '' },

  // Customer fields
  { label: '--- ' + t('imports.customer_fields') + ' ---', value: '', disabled: true },
  { label: t('imports.field_customer_name'), value: 'customer_name' },
  { label: t('imports.field_customer_email'), value: 'customer_email' },
  { label: t('imports.field_customer_phone'), value: 'customer_phone' },
  { label: t('imports.field_customer_address'), value: 'customer_address' },
  { label: t('imports.field_customer_tax_id'), value: 'customer_tax_id' },
  { label: t('imports.field_customer_website'), value: 'customer_website' },

  // Invoice fields
  { label: '--- ' + t('imports.invoice_fields') + ' ---', value: '', disabled: true },
  { label: t('imports.field_invoice_number'), value: 'invoice_number' },
  { label: t('imports.field_invoice_date'), value: 'invoice_date' },
  { label: t('imports.field_due_date'), value: 'due_date' },
  { label: t('imports.field_subtotal'), value: 'subtotal' },
  { label: t('imports.field_tax_amount'), value: 'tax_amount' },
  { label: t('imports.field_total_amount'), value: 'total_amount' },
  { label: t('imports.field_notes'), value: 'notes' },

  // Item fields
  { label: '--- ' + t('imports.item_fields') + ' ---', value: '', disabled: true },
  { label: t('imports.field_item_name'), value: 'item_name' },
  { label: t('imports.field_item_description'), value: 'item_description' },
  { label: t('imports.field_quantity'), value: 'quantity' },
  { label: t('imports.field_unit_price'), value: 'unit_price' },
  { label: t('imports.field_unit'), value: 'unit' },
  { label: t('imports.field_tax_rate'), value: 'tax_rate' },

  // Payment fields
  { label: '--- ' + t('imports.payment_fields') + ' ---', value: '', disabled: true },
  { label: t('imports.field_payment_date'), value: 'payment_date' },
  { label: t('imports.field_payment_amount'), value: 'payment_amount' },
  { label: t('imports.field_payment_method'), value: 'payment_method' },
  { label: t('imports.field_payment_reference'), value: 'payment_reference' },
])

const requiredFieldsCategories = computed(() => {
  // Get import type from importJob
  const importType = importStore.importJob?.type || 'customers'

  // Define categories based on import type
  const categoriesByType = {
    customers: [
      {
        name: 'customers',
        icon: 'UserIcon',
        fields: [
          { key: 'customer_name', required: true },
          { key: 'customer_email', required: true },
          { key: 'customer_phone', required: false },
          { key: 'customer_address', required: false },
          { key: 'customer_tax_id', required: false },
        ]
      }
    ],
    invoices: [
      {
        name: 'invoices',
        icon: 'DocumentIcon',
        fields: [
          { key: 'invoice_number', required: true },
          { key: 'customer_name', required: true },
          { key: 'invoice_date', required: true },
          { key: 'total_amount', required: true },
          { key: 'due_date', required: false },
          { key: 'subtotal', required: false },
          { key: 'tax_amount', required: false },
          { key: 'notes', required: false },
        ]
      }
    ],
    items: [
      {
        name: 'items',
        icon: 'CubeIcon',
        fields: [
          { key: 'item_name', required: true },
          { key: 'unit_price', required: true },
          { key: 'item_description', required: false },
          { key: 'unit', required: false },
          { key: 'tax_rate', required: false },
        ]
      }
    ],
    payments: [
      {
        name: 'payments',
        icon: 'CashIcon',
        fields: [
          { key: 'payment_date', required: true },
          { key: 'payment_amount', required: true },
          { key: 'payment_method', required: false },
          { key: 'payment_reference', required: false },
          { key: 'customer_name', required: false },
          { key: 'invoice_number', required: false },
        ]
      }
    ],
    expenses: [
      {
        name: 'expenses',
        icon: 'ReceiptTaxIcon',
        fields: [
          { key: 'expense_date', required: true },
          { key: 'payment_amount', required: true },
          { key: 'customer_name', required: false },
          { key: 'notes', required: false },
        ]
      }
    ],
  }

  return categoriesByType[importType] || categoriesByType.customers
})

const requiredFields = computed(() => {
  return requiredFieldsCategories.value.reduce((all, category) => {
    return all.concat(category.fields.filter(f => f.required))
  }, [])
})

const totalRequiredFieldsCount = computed(() => {
  return requiredFields.value.length
})

const requiredFieldsMappedCount = computed(() => {
  const mappedTargets = Object.values(importStore.fieldMappings)
  return requiredFields.value.filter(f => mappedTargets.includes(f.key)).length
})

const requiredFieldsMissing = computed(() => {
  return totalRequiredFieldsCount.value - requiredFieldsMappedCount.value
})

const mappedFieldsCount = computed(() => {
  return Object.keys(importStore.fieldMappings).filter(field => importStore.fieldMappings[field]).length
})

const overallMappingScore = computed(() => {
  if (importStore.detectedFields.length === 0) return 0

  const mappedCount = mappedFieldsCount.value
  const totalFields = importStore.detectedFields.length
  const requiredMapped = requiredFieldsMappedCount.value
  const totalRequired = totalRequiredFieldsCount.value

  const mappingRatio = mappedCount / totalFields
  const requiredRatio = totalRequired > 0 ? requiredMapped / totalRequired : 1

  return (mappingRatio * 0.3) + (requiredRatio * 0.7)
})

const allRequiredFieldsMappedWithHighConfidence = computed(() => {
  if (requiredFields.value.length === 0) return false

  const mappedTargets = Object.values(importStore.fieldMappings)

  // All required fields must be mapped
  const allMapped = requiredFields.value.every(f => mappedTargets.includes(f.key))
  if (!allMapped) return false

  // Every mapped field must have confidence >= 0.7
  for (const sourceField of Object.keys(importStore.fieldMappings)) {
    if (!importStore.fieldMappings[sourceField]) continue
    const confidence = getMappingConfidence(sourceField)
    if (confidence < 0.7) return false
  }

  return true
})

// Methods
const isFieldMapped = (fieldName) => {
  return importStore.fieldMappings[fieldName] && importStore.fieldMappings[fieldName] !== ''
}

const isTargetFieldMapped = (targetField) => {
  return Object.values(importStore.fieldMappings).includes(targetField)
}

const getMappingConfidence = (fieldName) => {
  // This would come from the backend mapping service
  // For now, return a mock confidence based on whether it's auto-mapped
  if (importStore.mappingSuggestions[fieldName] === importStore.fieldMappings[fieldName]) {
    return importStore.autoMappingConfidence
  }
  if (importStore.fieldMappings[fieldName]) {
    return 1.0 // Manual mapping has 100% confidence
  }
  return 0
}

const getConfidenceIcon = (confidence) => {
  if (confidence >= 0.8) return 'CheckCircleIcon'
  if (confidence >= 0.6) return 'ExclamationTriangleIcon'
  return 'XCircleIcon'
}

const updateMapping = (sourceField, targetField) => {
  importStore.updateMapping(sourceField, targetField)
}

const applySuggestion = (fieldName) => {
  const suggestion = importStore.mappingSuggestions[fieldName]
  if (suggestion) {
    importStore.updateMapping(fieldName, suggestion)
  }
}

const clearMapping = (fieldName) => {
  importStore.updateMapping(fieldName, '')
}

const applyAutoMapping = () => {
  importStore.applyAutoMapping()
}

const resetMapping = () => {
  importStore.fieldMappings = {}
  importStore.validateMappings()
  importStore.updateCanProceed()
}

const clearAllMappings = () => {
  resetMapping()
}

// Expose for parent wizard to check auto-advance readiness
const canAutoAdvance = () => allRequiredFieldsMappedWithHighConfidence.value

defineExpose({
  canAutoAdvance,
  allRequiredFieldsMappedWithHighConfidence,
})

// Lifecycle
onMounted(async () => {
  if (importStore.detectedFields.length === 0) {
    await importStore.detectFields()
  }

  // Notify parent if auto-advance is possible after fields are detected
  if (allRequiredFieldsMappedWithHighConfidence.value) {
    emit('auto-advance-ready')
  }
})
// CLAUDE-CHECKPOINT
</script>
