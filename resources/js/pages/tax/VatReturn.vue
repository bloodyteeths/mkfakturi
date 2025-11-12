<template>
  <div class="vat-return-generator">
    <!-- Page Header -->
    <div class="bg-white shadow-sm border-b border-gray-200 mb-6">
      <div class="max-w-7xl mx-auto px-4 py-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $t('vat.generate_return') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ $t('vat.generate_return_description') }}</p>
          </div>
          <div class="text-sm text-gray-500">
            <i class="fas fa-file-alt mr-1"></i>
            {{ $t('vat.ddv_04_format') }}
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4">
      <!-- VAT Number Warning -->
      <div v-if="!currentCompany?.vat_number" class="mb-6 bg-yellow-50 border-l-4 border-yellow-400 p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <i class="fas fa-exclamation-triangle text-yellow-400"></i>
          </div>
          <div class="ml-3">
            <p class="text-sm text-yellow-700">
              <strong class="font-medium">{{ $t('vat.vat_number_required') }}</strong>
              {{ $t('vat.vat_number_warning_message') }}
            </p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow">
        <div class="p-6">
          <!-- VAT Return Form -->
          <form @submit.prevent="generateVatReturn" class="space-y-6">
            <!-- Company Information -->
            <div class="border-b border-gray-200 pb-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('vat.company_information') }}</h3>
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('general.company_name') }}</label>
                  <div class="p-3 bg-gray-50 rounded-md text-sm text-gray-800">
                    {{ currentCompany?.name || $t('general.no_company_selected') }}
                  </div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('vat.vat_number') }}</label>
                  <div class="p-3 bg-gray-50 rounded-md text-sm text-gray-800">
                    {{ currentCompany?.vat_number || $t('vat.no_vat_number') }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Period Selection -->
            <div class="border-b border-gray-200 pb-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('vat.tax_period') }}</h3>
              <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Period Type -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('vat.period_type') }}</label>
                  <select
                    v-model="form.period_type"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    @change="onPeriodTypeChange"
                  >
                    <option value="MONTHLY">{{ $t('vat.monthly') }}</option>
                    <option value="QUARTERLY">{{ $t('vat.quarterly') }}</option>
                    <option value="CUSTOM">{{ $t('vat.custom') }}</option>
                  </select>
                </div>

                <!-- Month/Quarter/Year Selector (for MONTHLY/QUARTERLY) -->
                <div v-if="form.period_type === 'MONTHLY'" class="lg:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('vat.select_month') }}</label>
                  <div class="grid grid-cols-2 gap-3">
                    <select
                      v-model="form.selected_month"
                      @change="updateDatesFromSelection"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                      required
                    >
                      <option v-for="month in availableMonths" :key="month.value" :value="month.value">
                        {{ month.label }}
                      </option>
                    </select>
                    <select
                      v-model="form.selected_year"
                      @change="updateDatesFromSelection"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                      required
                    >
                      <option v-for="year in availableYears" :key="year" :value="year">
                        {{ year }}
                      </option>
                    </select>
                  </div>
                </div>

                <div v-if="form.period_type === 'QUARTERLY'" class="lg:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('vat.select_quarter') }}</label>
                  <div class="grid grid-cols-2 gap-3">
                    <select
                      v-model="form.selected_quarter"
                      @change="updateDatesFromSelection"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                      required
                    >
                      <option v-for="quarter in availableQuarters" :key="quarter.value" :value="quarter.value">
                        {{ quarter.label }}
                      </option>
                    </select>
                    <select
                      v-model="form.selected_year"
                      @change="updateDatesFromSelection"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                      required
                    >
                      <option v-for="year in availableYears" :key="year" :value="year">
                        {{ year }}
                      </option>
                    </select>
                  </div>
                </div>

                <!-- Custom Date Range (for CUSTOM) -->
                <template v-if="form.period_type === 'CUSTOM'">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('vat.period_start') }}</label>
                    <input
                      v-model="form.period_start"
                      type="date"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                      required
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('vat.period_end') }}</label>
                    <input
                      v-model="form.period_end"
                      type="date"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                      required
                    />
                  </div>
                </template>
              </div>

              <!-- Display selected period -->
              <div v-if="form.period_start && form.period_end" class="mt-4 p-3 bg-gray-50 rounded-md">
                <div class="text-sm text-gray-700">
                  <span class="font-medium">{{ $t('vat.selected_period') }}:</span>
                  {{ formatDate(form.period_start) }} - {{ formatDate(form.period_end) }}
                </div>
              </div>
            </div>

            <!-- VAT Summary Preview -->
            <div class="border-b border-gray-200 pb-6" v-if="vatSummary">
              <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('vat.summary_preview') }}</h3>
              <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Standard Rate (18%) -->
                <div class="bg-blue-50 p-4 rounded-lg">
                  <div class="text-sm font-medium text-blue-800 mb-2">{{ $t('vat.standard_rate') }} (18%)</div>
                  <div class="text-lg font-bold text-blue-900">
                    {{ formatMoney(vatSummary.standard?.vat_amount || 0) }} {{ currentCompany?.currency?.code || 'MKD' }}
                  </div>
                  <div class="text-xs text-blue-600">{{ vatSummary.standard?.transaction_count || 0 }} {{ $t('vat.transactions') }}</div>
                </div>

                <!-- Reduced Rate (5%) -->
                <div class="bg-green-50 p-4 rounded-lg">
                  <div class="text-sm font-medium text-green-800 mb-2">{{ $t('vat.reduced_rate') }} (5%)</div>
                  <div class="text-lg font-bold text-green-900">
                    {{ formatMoney(vatSummary.reduced?.vat_amount || 0) }} {{ currentCompany?.currency?.code || 'MKD' }}
                  </div>
                  <div class="text-xs text-green-600">{{ vatSummary.reduced?.transaction_count || 0 }} {{ $t('vat.transactions') }}</div>
                </div>

                <!-- Total VAT Due -->
                <div class="bg-gray-50 p-4 rounded-lg">
                  <div class="text-sm font-medium text-gray-800 mb-2">{{ $t('vat.total_vat_due') }}</div>
                  <div class="text-lg font-bold text-gray-900">
                    {{ formatMoney(vatSummary.total_output_vat || 0) }} {{ currentCompany?.currency?.code || 'MKD' }}
                  </div>
                  <div class="text-xs text-gray-600">{{ $t('vat.output_vat') }}</div>
                </div>
              </div>
            </div>

            <!-- Generation Options -->
            <div class="border-b border-gray-200 pb-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('vat.generation_options') }}</h3>
              <div class="space-y-4">
                <!-- Validate XML -->
                <div class="flex items-center">
                  <input 
                    v-model="form.validate_xml" 
                    type="checkbox" 
                    id="validate_xml"
                    class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                  />
                  <label for="validate_xml" class="ml-2 block text-sm text-gray-900">
                    {{ $t('vat.validate_xml_schema') }}
                  </label>
                </div>

              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between pt-6">
              <button 
                type="button" 
                @click="previewVatData"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                :disabled="isLoading"
              >
                <i class="fas fa-eye mr-2"></i>
                {{ $t('vat.preview_data') }}
              </button>

              <button 
                type="submit"
                class="px-6 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                :disabled="isLoading || !isFormValid"
              >
                <i class="fas fa-download mr-2" v-if="!isLoading"></i>
                <i class="fas fa-spinner fa-spin mr-2" v-if="isLoading"></i>
                {{ isLoading ? $t('vat.generating') : $t('vat.generate_and_download') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useNotificationStore } from '@/stores/notification'
import axios from '@/scripts/plugins/axios'

export default {
  name: 'VatReturn',
  setup() {
    const companyStore = useCompanyStore()
    const notificationStore = useNotificationStore()

    // Form data
    const form = ref({
      period_type: 'MONTHLY',
      selected_month: '',
      selected_quarter: '',
      selected_year: '',
      period_start: '',
      period_end: '',
      validate_xml: true
    })

    // State
    const isLoading = ref(false)
    const vatSummary = ref(null)

    // Computed properties
    const currentCompany = computed(() => companyStore.selectedCompany)

    const isFormValid = computed(() => {
      return form.value.period_start &&
             form.value.period_end &&
             currentCompany.value?.id
    })

    // Available years (current year and 5 years back)
    const availableYears = computed(() => {
      const currentYear = new Date().getFullYear()
      const years = []
      for (let i = 0; i <= 5; i++) {
        years.push(currentYear - i)
      }
      return years
    })

    // Available months
    const availableMonths = computed(() => {
      const months = [
        { value: 0, label: 'Јануари' },
        { value: 1, label: 'Февруари' },
        { value: 2, label: 'Март' },
        { value: 3, label: 'Април' },
        { value: 4, label: 'Мај' },
        { value: 5, label: 'Јуни' },
        { value: 6, label: 'Јули' },
        { value: 7, label: 'Август' },
        { value: 8, label: 'Септември' },
        { value: 9, label: 'Октомври' },
        { value: 10, label: 'Ноември' },
        { value: 11, label: 'Декември' }
      ]
      return months
    })

    // Available quarters
    const availableQuarters = computed(() => {
      return [
        { value: 1, label: 'Q1 (Јануари - Март)' },
        { value: 2, label: 'Q2 (Април - Јуни)' },
        { value: 3, label: 'Q3 (Јули - Септември)' },
        { value: 4, label: 'Q4 (Октомври - Декември)' }
      ]
    })

    // Methods
    const onPeriodTypeChange = () => {
      // Reset selections when period type changes
      const today = new Date()

      if (form.value.period_type === 'MONTHLY') {
        // Default to previous month
        form.value.selected_month = today.getMonth() - 1 < 0 ? 11 : today.getMonth() - 1
        form.value.selected_year = today.getMonth() - 1 < 0 ? today.getFullYear() - 1 : today.getFullYear()
      } else if (form.value.period_type === 'QUARTERLY') {
        // Default to previous quarter
        const currentQuarter = Math.floor(today.getMonth() / 3) + 1
        form.value.selected_quarter = currentQuarter - 1 < 1 ? 4 : currentQuarter - 1
        form.value.selected_year = currentQuarter - 1 < 1 ? today.getFullYear() - 1 : today.getFullYear()
      } else if (form.value.period_type === 'CUSTOM') {
        // Default to previous month for custom
        const startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1)
        const endDate = new Date(today.getFullYear(), today.getMonth(), 0)
        form.value.period_start = startDate.toISOString().split('T')[0]
        form.value.period_end = endDate.toISOString().split('T')[0]
      }

      updateDatesFromSelection()
    }

    const updateDatesFromSelection = () => {
      if (form.value.period_type === 'MONTHLY') {
        // Calculate first and last day of selected month
        const startDate = new Date(form.value.selected_year, form.value.selected_month, 1)
        const endDate = new Date(form.value.selected_year, form.value.selected_month + 1, 0)

        form.value.period_start = startDate.toISOString().split('T')[0]
        form.value.period_end = endDate.toISOString().split('T')[0]
      } else if (form.value.period_type === 'QUARTERLY') {
        // Calculate first and last day of selected quarter
        const startMonth = (form.value.selected_quarter - 1) * 3
        const startDate = new Date(form.value.selected_year, startMonth, 1)
        const endDate = new Date(form.value.selected_year, startMonth + 3, 0)

        form.value.period_start = startDate.toISOString().split('T')[0]
        form.value.period_end = endDate.toISOString().split('T')[0]
      }
      // For CUSTOM, dates are manually set by the user
    }

    const formatDate = (dateStr) => {
      if (!dateStr) return ''
      const date = new Date(dateStr)
      return date.toLocaleDateString('mk-MK', { year: 'numeric', month: 'long', day: 'numeric' })
    }

    const previewVatData = async () => {
      if (!isFormValid.value) return

      try {
        isLoading.value = true
        
        const response = await axios.post('/api/v1/tax/vat-return/preview', {
          company_id: currentCompany.value.id,
          period_start: form.value.period_start,
          period_end: form.value.period_end,
          period_type: form.value.period_type
        })

        vatSummary.value = response.data.data
        
        notificationStore.showNotification({
          type: 'success',
          message: 'VAT data preview loaded successfully'
        })
      } catch (error) {
        console.error('Failed to preview VAT data:', error)
        notificationStore.showNotification({
          type: 'error',
          message: error.response?.data?.message || 'Failed to preview VAT data'
        })
      } finally {
        isLoading.value = false
      }
    }

    const generateVatReturn = async () => {
      if (!isFormValid.value) return

      try {
        isLoading.value = true

        const response = await axios.post('/api/v1/tax/vat-return', {
          company_id: currentCompany.value.id,
          period_start: form.value.period_start,
          period_end: form.value.period_end,
          period_type: form.value.period_type,
          validate_xml: form.value.validate_xml
        }, {
          responseType: 'blob'
        })

        // Create download link
        const blob = new Blob([response.data], { type: 'application/xml' })
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        
        // Generate filename
        const companyName = currentCompany.value.name.replace(/[^a-zA-Z0-9]/g, '_')
        const period = `${form.value.period_start}_${form.value.period_end}`
        link.download = `DDV04_${companyName}_${period}.xml`
        
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)

        notificationStore.showNotification({
          type: 'success',
          message: 'VAT return XML generated and downloaded successfully'
        })
      } catch (error) {
        console.error('Failed to generate VAT return:', error)

        // Handle blob error response (when backend returns JSON error but we expect blob)
        let errorMessage = 'Failed to generate VAT return'

        if (error.response?.data instanceof Blob) {
          try {
            const text = await error.response.data.text()
            const jsonError = JSON.parse(text)
            errorMessage = jsonError.message || errorMessage
            console.error('Backend error:', jsonError)
          } catch (e) {
            console.error('Could not parse error blob:', e)
          }
        } else if (error.response?.data?.message) {
          errorMessage = error.response.data.message
        }

        notificationStore.showNotification({
          type: 'error',
          message: errorMessage
        })
      } finally {
        isLoading.value = false
      }
    }

    const formatMoney = (amount) => {
      return (amount / 100).toLocaleString('mk-MK', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      })
    }

    // Initialize form with default period
    onMounted(() => {
      onPeriodTypeChange()
    })

    return {
      form,
      isLoading,
      vatSummary,
      currentCompany,
      isFormValid,
      availableYears,
      availableMonths,
      availableQuarters,
      onPeriodTypeChange,
      updateDatesFromSelection,
      formatDate,
      previewVatData,
      generateVatReturn,
      formatMoney
    }
  }
}
</script>

<style scoped>
.vat-return-generator {
  min-height: 100vh;
  background-color: #f9fafb;
}

/* Custom focus styles for primary color consistency */
.focus\:ring-primary-500:focus {
  --tw-ring-color: #3b82f6;
}

.focus\:border-primary-500:focus {
  --tw-border-opacity: 1;
  border-color: #3b82f6;
}

.bg-primary-600 {
  background-color: #2563eb;
}

.hover\:bg-primary-700:hover {
  background-color: #1d4ed8;
}

.text-primary-600 {
  color: #2563eb;
}
</style>

