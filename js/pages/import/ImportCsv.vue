<template>
  <div class="csv-import-wizard">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">{{ $t('import.csv_wizard_title') }}</h1>
      <p class="mt-2 text-sm text-gray-600">{{ $t('import.csv_wizard_description') }}</p>
    </div>

    <!-- Progress Steps -->
    <div class="mb-8">
      <nav aria-label="Progress">
        <ol class="flex items-center">
          <li v-for="(step, index) in steps" :key="step.id" class="relative">
            <div v-if="index !== steps.length - 1" class="absolute top-4 left-4 -ml-px mt-0.5 h-full w-0.5 bg-gray-300" aria-hidden="true"></div>
            <div class="group relative flex items-start">
              <span class="flex h-9 items-center">
                <span 
                  :class="[
                    'relative z-10 flex h-8 w-8 items-center justify-center rounded-full border-2',
                    currentStep >= index ? 'border-primary-600 bg-primary-600' : 'border-gray-300 bg-white'
                  ]"
                >
                  <CheckIcon v-if="currentStep > index" class="h-5 w-5 text-white" />
                  <span v-else-if="currentStep === index" class="h-2.5 w-2.5 rounded-full bg-white"></span>
                  <span v-else class="h-2.5 w-2.5 rounded-full bg-transparent"></span>
                </span>
              </span>
              <span class="ml-4 min-w-0 flex flex-col">
                <span 
                  :class="[
                    'text-sm font-medium',
                    currentStep >= index ? 'text-primary-600' : 'text-gray-500'
                  ]"
                >
                  {{ step.name }}
                </span>
                <span class="text-sm text-gray-500">{{ step.description }}</span>
              </span>
            </div>
          </li>
        </ol>
      </nav>
    </div>

    <!-- Step Content -->
    <div class="bg-white shadow rounded-lg">
      <!-- Step 1: File Upload -->
      <div v-if="currentStep === 0" class="p-6">
        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6">
          <div class="text-center">
            <DocumentArrowUpIcon class="mx-auto h-12 w-12 text-gray-400" />
            <div class="mt-4">
              <label for="file-upload" class="cursor-pointer">
                <span class="mt-2 block text-sm font-medium text-gray-900">
                  {{ $t('import.upload_csv_file') }}
                </span>
                <input
                  id="file-upload"
                  name="file-upload"
                  type="file"
                  accept=".csv,.txt"
                  class="sr-only"
                  @change="handleFileUpload"
                />
                <span class="mt-1 block text-sm text-gray-600">
                  {{ $t('import.supported_formats') }}
                </span>
              </label>
            </div>
          </div>
        </div>

        <!-- File Info -->
        <div v-if="selectedFile" class="mt-4 p-4 bg-gray-50 rounded-lg">
          <div class="flex items-center">
            <DocumentTextIcon class="h-6 w-6 text-gray-400" />
            <div class="ml-3">
              <p class="text-sm font-medium text-gray-900">{{ selectedFile.name }}</p>
              <p class="text-sm text-gray-500">{{ formatFileSize(selectedFile.size) }}</p>
            </div>
          </div>
        </div>

        <!-- CSV Options -->
        <div v-if="selectedFile" class="mt-6 space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <BaseInputLabel>{{ $t('import.delimiter') }}</BaseInputLabel>
              <BaseSelectInput
                v-model="csvOptions.delimiter"
                :options="delimiterOptions"
                class="mt-1"
              />
            </div>
            <div>
              <BaseInputLabel>{{ $t('import.encoding') }}</BaseInputLabel>
              <BaseSelectInput
                v-model="csvOptions.encoding"
                :options="encodingOptions"
                class="mt-1"
              />
            </div>
            <div>
              <BaseCheckbox
                v-model="csvOptions.hasHeader"
                :label="$t('import.first_row_headers')"
                class="mt-6"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Step 2: Preview & Mapping -->
      <div v-if="currentStep === 1" class="p-6">
        <div class="mb-4">
          <h3 class="text-lg font-medium text-gray-900">{{ $t('import.preview_and_mapping') }}</h3>
          <p class="text-sm text-gray-600">{{ $t('import.preview_description') }}</p>
        </div>

        <!-- Import Type Selection -->
        <div class="mb-6">
          <BaseInputLabel>{{ $t('import.import_type') }}</BaseInputLabel>
          <BaseSelectInput
            v-model="importType"
            :options="importTypeOptions"
            class="mt-1"
          />
        </div>

        <!-- Column Mapping -->
        <div v-if="previewData.length > 0" class="mb-6">
          <h4 class="text-md font-medium text-gray-900 mb-3">{{ $t('import.column_mapping') }}</h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="(column, index) in csvColumns" :key="index" class="flex items-center space-x-3">
              <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('import.csv_column') }} {{ index + 1 }}
                  <span v-if="csvOptions.hasHeader" class="text-gray-500">({{ column }})</span>
                </label>
              </div>
              <div class="flex-1">
                <BaseSelectInput
                  v-model="columnMapping[index]"
                  :options="getFieldOptions()"
                  :placeholder="$t('import.select_field')"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Preview Table -->
        <div v-if="previewData.length > 0" class="border rounded-lg overflow-hidden">
          <div class="bg-gray-50 px-4 py-2 border-b">
            <h4 class="text-sm font-medium text-gray-900">
              {{ $t('import.preview_first_rows', { count: Math.min(previewData.length, 5) }) }}
            </h4>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th v-for="(column, index) in csvColumns" :key="index" 
                      class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ csvOptions.hasHeader ? column : `${$t('import.column')} ${index + 1}` }}
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="(row, rowIndex) in previewData.slice(0, 5)" :key="rowIndex">
                  <td v-for="(cell, cellIndex) in row" :key="cellIndex" 
                      class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ cell }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Validation Errors -->
        <div v-if="validationErrors.length > 0" class="mt-4 p-4 bg-red-50 rounded-lg">
          <div class="flex">
            <ExclamationTriangleIcon class="h-5 w-5 text-red-400" />
            <div class="ml-3">
              <h3 class="text-sm font-medium text-red-800">{{ $t('import.validation_errors') }}</h3>
              <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                <li v-for="error in validationErrors" :key="error">{{ error }}</li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- Step 3: Import Configuration -->
      <div v-if="currentStep === 2" class="p-6">
        <div class="mb-4">
          <h3 class="text-lg font-medium text-gray-900">{{ $t('import.import_configuration') }}</h3>
          <p class="text-sm text-gray-600">{{ $t('import.configuration_description') }}</p>
        </div>

        <div class="space-y-6">
          <!-- Import Options -->
          <div>
            <h4 class="text-md font-medium text-gray-900 mb-3">{{ $t('import.import_options') }}</h4>
            <div class="space-y-3">
              <BaseCheckbox
                v-model="importOptions.skipDuplicates"
                :label="$t('import.skip_duplicates')"
                :description="$t('import.skip_duplicates_description')"
              />
              <BaseCheckbox
                v-model="importOptions.updateExisting"
                :label="$t('import.update_existing')"
                :description="$t('import.update_existing_description')"
              />
              <BaseCheckbox
                v-model="importOptions.dryRun"
                :label="$t('import.dry_run')"
                :description="$t('import.dry_run_description')"
              />
            </div>
          </div>

          <!-- Import Summary -->
          <div class="bg-blue-50 p-4 rounded-lg">
            <h4 class="text-md font-medium text-blue-900 mb-2">{{ $t('import.import_summary') }}</h4>
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-2 text-sm">
              <div>
                <dt class="font-medium text-blue-900">{{ $t('import.total_rows') }}:</dt>
                <dd class="text-blue-700">{{ totalRows }}</dd>
              </div>
              <div>
                <dt class="font-medium text-blue-900">{{ $t('import.import_type') }}:</dt>
                <dd class="text-blue-700">{{ getImportTypeLabel() }}</dd>
              </div>
              <div>
                <dt class="font-medium text-blue-900">{{ $t('import.mapped_columns') }}:</dt>
                <dd class="text-blue-700">{{ mappedColumnsCount }}</dd>
              </div>
              <div>
                <dt class="font-medium text-blue-900">{{ $t('import.estimated_time') }}:</dt>
                <dd class="text-blue-700">{{ getEstimatedTime() }}</dd>
              </div>
            </dl>
          </div>
        </div>
      </div>

      <!-- Navigation -->
      <div class="px-6 py-4 bg-gray-50 border-t flex justify-between">
        <BaseButton
          v-if="currentStep > 0"
          variant="outline"
          @click="previousStep"
        >
          {{ $t('general.previous_navigation') }}
        </BaseButton>
        <div v-else></div>

        <div class="flex space-x-3">
          <BaseButton
            v-if="currentStep < steps.length - 1"
            variant="primary"
            :disabled="!canProceed"
            @click="nextStep"
          >
            {{ $t('general.next_navigation') }}
          </BaseButton>
          <BaseButton
            v-else
            variant="primary"
            :loading="isImporting"
            :disabled="!canImport"
            @click="startImport"
          >
            {{ $t('import.start_import') }}
          </BaseButton>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'
import { 
  DocumentArrowUpIcon, 
  DocumentTextIcon, 
  CheckIcon, 
  ExclamationTriangleIcon 
} from '@heroicons/vue/24/outline'

const { t } = useI18n()
const notificationStore = useNotificationStore()

// Wizard steps
const steps = ref([
  { id: 'upload', name: t('import.step_upload'), description: t('import.step_upload_desc') },
  { id: 'preview', name: t('import.step_preview'), description: t('import.step_preview_desc') },
  { id: 'configure', name: t('import.step_configure'), description: t('import.step_configure_desc') }
])

const currentStep = ref(0)
const selectedFile = ref(null)
const isImporting = ref(false)

// CSV Options
const csvOptions = ref({
  delimiter: ',',
  encoding: 'UTF-8',
  hasHeader: true
})

const delimiterOptions = computed(() => [
  { value: ',', label: t('import.comma') },
  { value: ';', label: t('import.semicolon') },
  { value: '\t', label: t('import.tab') },
  { value: '|', label: t('import.pipe') }
])

const encodingOptions = computed(() => [
  { value: 'UTF-8', label: 'UTF-8' },
  { value: 'ISO-8859-1', label: 'ISO-8859-1' },
  { value: 'Windows-1252', label: 'Windows-1252' }
])

// Import configuration
const importType = ref('customers')
const importTypeOptions = computed(() => [
  { value: 'customers', label: t('import.type_customers') },
  { value: 'items', label: t('import.type_items') },
  { value: 'invoices', label: t('import.type_invoices') },
  { value: 'expenses', label: t('import.type_expenses') }
])

const importOptions = ref({
  skipDuplicates: true,
  updateExisting: false,
  dryRun: false
})

// Data
const previewData = ref([])
const csvColumns = ref([])
const columnMapping = ref({})
const validationErrors = ref([])

// Computed
const totalRows = computed(() => previewData.value.length)
const mappedColumnsCount = computed(() => Object.values(columnMapping.value).filter(v => v).length)

const canProceed = computed(() => {
  if (currentStep.value === 0) return selectedFile.value !== null
  if (currentStep.value === 1) return previewData.value.length > 0 && mappedColumnsCount.value > 0
  return true
})

const canImport = computed(() => {
  return !isImporting.value && mappedColumnsCount.value > 0 && validationErrors.value.length === 0
})

// Methods
function handleFileUpload(event) {
  const file = event.target.files[0]
  if (file) {
    selectedFile.value = file
    parseCSV(file)
  }
}

function parseCSV(file) {
  const reader = new FileReader()
  reader.onload = (e) => {
    const csv = e.target.result
    const lines = csv.split('\n').filter(line => line.trim())
    
    if (lines.length === 0) return
    
    const delimiter = csvOptions.value.delimiter
    const rows = lines.map(line => line.split(delimiter))
    
    if (csvOptions.value.hasHeader && rows.length > 0) {
      csvColumns.value = rows[0]
      previewData.value = rows.slice(1)
    } else {
      csvColumns.value = rows[0].map((_, index) => `Column ${index + 1}`)
      previewData.value = rows
    }
    
    // Initialize column mapping
    initializeColumnMapping()
  }
  reader.readAsText(file, csvOptions.value.encoding)
}

function initializeColumnMapping() {
  columnMapping.value = {}
  const fieldOptions = getFieldOptions()
  
  // Try to auto-map columns based on headers
  csvColumns.value.forEach((column, index) => {
    const normalizedColumn = column.toLowerCase().trim()
    const matchingField = fieldOptions.find(field => 
      field.label.toLowerCase().includes(normalizedColumn) ||
      normalizedColumn.includes(field.label.toLowerCase())
    )
    
    if (matchingField) {
      columnMapping.value[index] = matchingField.value
    }
  })
}

function getFieldOptions() {
  const baseFields = [
    { value: '', label: t('import.do_not_import') }
  ]
  
  switch (importType.value) {
    case 'customers':
      return baseFields.concat([
        { value: 'name', label: t('customers.name') },
        { value: 'email', label: t('customers.email') },
        { value: 'phone', label: t('customers.phone') },
        { value: 'address', label: t('customers.address') },
        { value: 'city', label: t('customers.city') },
        { value: 'country', label: t('customers.country') },
        { value: 'tax_number', label: t('customers.tax_number') }
      ])
    case 'items':
      return baseFields.concat([
        { value: 'name', label: t('items.name') },
        { value: 'description', label: t('items.description') },
        { value: 'price', label: t('items.price') },
        { value: 'unit', label: t('items.unit') },
        { value: 'tax_rate', label: t('items.tax_rate') }
      ])
    default:
      return baseFields
  }
}

function getImportTypeLabel() {
  const option = importTypeOptions.value.find(opt => opt.value === importType.value)
  return option ? option.label : importType.value
}

function getEstimatedTime() {
  const rowsPerSecond = 50 // Rough estimate
  const seconds = Math.ceil(totalRows.value / rowsPerSecond)
  return seconds < 60 ? `${seconds}s` : `${Math.ceil(seconds / 60)}min`
}

function formatFileSize(bytes) {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

function validateMapping() {
  validationErrors.value = []
  
  // Check required fields
  const requiredFields = getRequiredFields()
  const mappedFields = Object.values(columnMapping.value).filter(v => v)
  
  requiredFields.forEach(field => {
    if (!mappedFields.includes(field)) {
      validationErrors.value.push(
        t('import.required_field_missing', { field: field })
      )
    }
  })
}

function getRequiredFields() {
  switch (importType.value) {
    case 'customers':
      return ['name']
    case 'items':
      return ['name', 'price']
    default:
      return []
  }
}

function nextStep() {
  if (currentStep.value === 1) {
    validateMapping()
  }
  if (canProceed.value) {
    currentStep.value++
  }
}

function previousStep() {
  if (currentStep.value > 0) {
    currentStep.value--
  }
}

async function startImport() {
  if (!canImport.value) return
  
  try {
    isImporting.value = true
    
    // Prepare import data
    const importData = {
      file: selectedFile.value,
      options: csvOptions.value,
      type: importType.value,
      mapping: columnMapping.value,
      config: importOptions.value
    }
    
    // TODO: Call import API
    // await importStore.importCsv(importData)
    
    // Simulate import process
    await new Promise(resolve => setTimeout(resolve, 2000))
    
    notificationStore.showNotification({
      type: 'success',
      message: t('import.import_completed_successfully', { 
        count: totalRows.value 
      })
    })
    
    // Reset wizard
    resetWizard()
    
  } catch (error) {
    console.error('Import failed:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('import.import_failed') + ': ' + error.message
    })
  } finally {
    isImporting.value = false
  }
}

function resetWizard() {
  currentStep.value = 0
  selectedFile.value = null
  previewData.value = []
  csvColumns.value = []
  columnMapping.value = {}
  validationErrors.value = []
}

onMounted(() => {
  // Initialize wizard
})
</script>

<style scoped>
.csv-import-wizard {
  max-width: 4xl;
  margin: 0 auto;
  padding: 2rem;
}
</style>