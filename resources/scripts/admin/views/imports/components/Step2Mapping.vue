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

    <!-- Auto-mapping Results -->
    <div v-if="importStore.autoMappingConfidence > 0" class="max-w-6xl mx-auto">
      <div
        :class="[
          'border rounded-lg p-4',
          {
            'bg-green-50 border-green-200': importStore.autoMappingConfidence >= 0.8,
            'bg-yellow-50 border-yellow-200': importStore.autoMappingConfidence >= 0.6 && importStore.autoMappingConfidence < 0.8,
            'bg-red-50 border-red-200': importStore.autoMappingConfidence < 0.6,
          }
        ]"
      >
        <div class="flex items-start">
          <BaseIcon 
            :name="getConfidenceIcon(importStore.autoMappingConfidence)"
            :class="[
              'w-5 h-5 mt-0.5 mr-3 flex-shrink-0',
              {
                'text-green-400': importStore.autoMappingConfidence >= 0.8,
                'text-yellow-400': importStore.autoMappingConfidence >= 0.6 && importStore.autoMappingConfidence < 0.8,
                'text-red-400': importStore.autoMappingConfidence < 0.6,
              }
            ]"
          />
          <div class="flex-1">
            <h4 class="text-sm font-medium mb-1">
              {{ $t('imports.auto_mapping_results') }}
            </h4>
            <p class="text-sm mb-3">
              {{ $t('imports.auto_mapping_confidence', { confidence: Math.round(importStore.autoMappingConfidence * 100) }) }}
            </p>
            <div class="flex items-center space-x-4">
              <div class="flex-1">
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div
                    :class="[
                      'h-2 rounded-full transition-all duration-300',
                      {
                        'bg-green-500': importStore.autoMappingConfidence >= 0.8,
                        'bg-yellow-500': importStore.autoMappingConfidence >= 0.6 && importStore.autoMappingConfidence < 0.8,
                        'bg-red-500': importStore.autoMappingConfidence < 0.6,
                      }
                    ]"
                    :style="{ width: `${importStore.autoMappingConfidence * 100}%` }"
                  ></div>
                </div>
              </div>
              <BaseButton
                v-if="importStore.autoMappingConfidence < 0.8"
                variant="secondary"
                size="sm"
                @click="resetMapping"
              >
                {{ $t('imports.reset_mapping') }}
              </BaseButton>
            </div>
          </div>
        </div>
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
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ $t('imports.confidence') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ $t('general.actions') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr
                v-for="field in importStore.detectedFields"
                :key="field.name"
                :class="{
                  'bg-green-50': isFieldMapped(field.name) && getMappingConfidence(field.name) >= 0.8,
                  'bg-yellow-50': isFieldMapped(field.name) && getMappingConfidence(field.name) >= 0.6 && getMappingConfidence(field.name) < 0.8,
                  'bg-red-50': isFieldMapped(field.name) && getMappingConfidence(field.name) < 0.6,
                }"
              >
                <!-- Source Field -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div>
                      <div class="text-sm font-medium text-gray-900">
                        {{ field.name }}
                      </div>
                      <div class="text-sm text-gray-500">
                        {{ field.type || 'string' }}
                      </div>
                    </div>
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

                <!-- Confidence -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div v-if="isFieldMapped(field.name)">
                    <div class="flex items-center">
                      <div class="flex-1 mr-2">
                        <div class="w-16 bg-gray-200 rounded-full h-2">
                          <div
                            :class="[
                              'h-2 rounded-full',
                              {
                                'bg-green-500': getMappingConfidence(field.name) >= 0.8,
                                'bg-yellow-500': getMappingConfidence(field.name) >= 0.6 && getMappingConfidence(field.name) < 0.8,
                                'bg-red-500': getMappingConfidence(field.name) < 0.6,
                              }
                            ]"
                            :style="{ width: `${getMappingConfidence(field.name) * 100}%` }"
                          ></div>
                        </div>
                      </div>
                      <span class="text-xs font-medium text-gray-900">
                        {{ Math.round(getMappingConfidence(field.name) * 100) }}%
                      </span>
                    </div>
                  </div>
                  <span v-else class="text-xs text-gray-400">-</span>
                </td>

                <!-- Actions -->
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div class="flex space-x-2">
                    <BaseButton
                      v-if="importStore.mappingSuggestions[field.name]"
                      variant="secondary"
                      size="xs"
                      @click="applySuggestion(field.name)"
                      :disabled="importStore.fieldMappings[field.name] === importStore.mappingSuggestions[field.name]"
                    >
                      {{ $t('imports.use_suggestion') }}
                    </BaseButton>
                    <BaseButton
                      v-if="isFieldMapped(field.name)"
                      variant="danger"
                      size="xs"
                      @click="clearMapping(field.name)"
                    >
                      {{ $t('imports.clear') }}
                    </BaseButton>
                  </div>
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

    <!-- Required Fields Guide -->
    <div class="max-w-6xl mx-auto">
      <BaseCard>
        <template #header>
          <h3 class="text-lg font-medium text-gray-900">
            {{ $t('imports.required_fields') }}
          </h3>
        </template>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div v-for="category in requiredFieldsCategories" :key="category.name" class="space-y-3">
            <h4 class="font-medium text-gray-900 flex items-center">
              <BaseIcon :name="category.icon" class="w-4 h-4 mr-2" />
              {{ $t(`imports.${category.name}`) }}
            </h4>
            <ul class="space-y-2">
              <li
                v-for="field in category.fields"
                :key="field.key"
                class="flex items-center text-sm"
              >
                <span
                  :class="[
                    'w-3 h-3 rounded-full mr-2 flex-shrink-0',
                    {
                      'bg-green-500': isTargetFieldMapped(field.key),
                      'bg-red-500': field.required && !isTargetFieldMapped(field.key),
                      'bg-gray-300': !field.required && !isTargetFieldMapped(field.key),
                    }
                  ]"
                ></span>
                <span :class="{ 'text-gray-500': !field.required }">
                  {{ $t(`imports.field_${field.key}`) }}
                  <span v-if="field.required" class="text-red-500">*</span>
                </span>
              </li>
            </ul>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- Mapping Summary -->
    <div class="max-w-6xl mx-auto">
      <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
          <div>
            <div class="text-2xl font-bold text-gray-900">{{ importStore.detectedFields.length }}</div>
            <div class="text-sm text-gray-500">{{ $t('imports.detected_fields') }}</div>
          </div>
          <div>
            <div class="text-2xl font-bold text-green-600">{{ mappedFieldsCount }}</div>
            <div class="text-sm text-gray-500">{{ $t('imports.mapped_fields') }}</div>
          </div>
          <div>
            <div class="text-2xl font-bold text-red-600">{{ requiredFieldsMissing }}</div>
            <div class="text-sm text-gray-500">{{ $t('imports.missing_required') }}</div>
          </div>
          <div>
            <div class="text-2xl font-bold text-blue-600">{{ Math.round(overallMappingScore * 100) }}%</div>
            <div class="text-sm text-gray-500">{{ $t('imports.mapping_score') }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
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
          { key: 'name', required: true },
          { key: 'email', required: true },
          { key: 'phone', required: false },
          { key: 'address', required: false },
          { key: 'vat_number', required: false },
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
          { key: 'total', required: true },
          { key: 'due_date', required: false },
          { key: 'subtotal', required: false },
          { key: 'tax', required: false },
          { key: 'status', required: false },
          { key: 'currency', required: false },
          { key: 'notes', required: false },
        ]
      }
    ],
    items: [
      {
        name: 'items',
        icon: 'CubeIcon',
        fields: [
          { key: 'name', required: true },
          { key: 'price', required: true },
          { key: 'description', required: false },
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
          { key: 'amount', required: true },
          { key: 'payment_method', required: false },
          { key: 'reference', required: false },
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
          { key: 'amount', required: true },
          { key: 'category', required: false },
          { key: 'customer_name', required: false },
          { key: 'notes', required: false },
        ]
      }
    ],
  }

  return categoriesByType[importType] || categoriesByType.customers
})

const mappedFieldsCount = computed(() => {
  return Object.keys(importStore.fieldMappings).filter(field => importStore.fieldMappings[field]).length
})

const requiredFieldsMissing = computed(() => {
  const mappedTargets = Object.values(importStore.fieldMappings)

  // Get import type from importJob
  const importType = importStore.importJob?.type || 'customers'

  // Define required fields for each import type (same as store validation)
  const requiredFieldsByType = {
    customers: ['name', 'email'],
    invoices: ['invoice_number', 'customer_name', 'invoice_date', 'total'],
    items: ['name', 'price'],
    payments: ['payment_date', 'amount'],
    expenses: ['expense_date', 'amount'],
  }

  // Get required fields for current import type
  const requiredFields = requiredFieldsByType[importType] || []

  return requiredFields.filter(field => !mappedTargets.includes(field)).length
})

const overallMappingScore = computed(() => {
  if (importStore.detectedFields.length === 0) return 0
  
  const mappedCount = mappedFieldsCount.value
  const totalFields = importStore.detectedFields.length
  const requiredMapped = requiredFieldsCategories.value.reduce((count, category) => {
    return count + category.fields.filter(field => field.required && isTargetFieldMapped(field.key)).length
  }, 0)
  const totalRequired = requiredFieldsCategories.value.reduce((count, category) => {
    return count + category.fields.filter(field => field.required).length
  }, 0)
  
  const mappingRatio = mappedCount / totalFields
  const requiredRatio = totalRequired > 0 ? requiredMapped / totalRequired : 1
  
  return (mappingRatio * 0.3) + (requiredRatio * 0.7)
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

// Lifecycle
onMounted(async () => {
  if (importStore.detectedFields.length === 0) {
    await importStore.detectFields()
  }
})
</script>