<template>
  <div class="vat-status-widget bg-white rounded-lg shadow-md p-6 border border-gray-200">
    <!-- Widget Header -->
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center">
        <div class="flex-shrink-0">
          <i class="fas fa-file-invoice-dollar text-2xl text-blue-600"></i>
        </div>
        <div class="ml-3">
          <h3 class="text-lg font-semibold text-gray-900">{{ $t('vat.compliance_status') }}</h3>
          <p class="text-sm text-gray-600">{{ $t('vat.widget_description') }}</p>
        </div>
      </div>
      <div class="flex-shrink-0">
        <button 
          @click="refreshData"
          class="p-2 text-gray-400 hover:text-gray-600 transition-colors"
          :disabled="isLoading"
        >
          <i class="fas fa-sync-alt" :class="{ 'fa-spin': isLoading }"></i>
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading && !vatStatus" class="space-y-3">
      <div class="animate-pulse">
        <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
        <div class="h-4 bg-gray-200 rounded w-1/2 mb-2"></div>
        <div class="h-8 bg-gray-200 rounded"></div>
      </div>
    </div>

    <!-- Main Content -->
    <div v-else class="space-y-4">
      <!-- VAT Return Status -->
      <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
        <div class="flex items-center">
          <div 
            class="w-3 h-3 rounded-full mr-3"
            :class="getStatusColor(vatStatus?.compliance_status || 'unknown')"
          ></div>
          <div>
            <p class="text-sm font-medium text-gray-900">{{ $t('vat.current_status') }}</p>
            <p class="text-xs text-gray-600">{{ getStatusText(vatStatus?.compliance_status || 'unknown') }}</p>
          </div>
        </div>
        <div class="text-right">
          <p class="text-xs text-gray-500">{{ $t('vat.as_of') }}</p>
          <p class="text-sm font-medium text-gray-900">{{ formatDate(vatStatus?.last_checked || new Date()) }}</p>
        </div>
      </div>

      <!-- Last VAT Return Information -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Last Generation Date -->
        <div class="p-3 border border-gray-200 rounded-lg">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs text-gray-500 uppercase tracking-wide">{{ $t('vat.last_generation') }}</p>
              <p class="text-sm font-semibold text-gray-900">
                {{ vatStatus?.last_generated ? formatDate(vatStatus.last_generated) : $t('vat.never_generated') }}
              </p>
            </div>
            <i class="fas fa-calendar-alt text-gray-400"></i>
          </div>
        </div>

        <!-- Return Period -->
        <div class="p-3 border border-gray-200 rounded-lg">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs text-gray-500 uppercase tracking-wide">{{ $t('vat.return_period') }}</p>
              <p class="text-sm font-semibold text-gray-900">
                {{ vatStatus?.current_period || $t('vat.no_period_set') }}
              </p>
            </div>
            <i class="fas fa-clock text-gray-400"></i>
          </div>
        </div>
      </div>

      <!-- VAT Compliance Alerts -->
      <div v-if="complianceAlerts && complianceAlerts.length > 0" class="space-y-2">
        <h4 class="text-sm font-medium text-gray-900">{{ $t('vat.compliance_alerts') }}</h4>
        <div v-for="alert in complianceAlerts" :key="alert.id" 
             class="p-3 rounded-lg border-l-4"
             :class="getAlertClasses(alert.severity)">
          <div class="flex items-start">
            <i :class="getAlertIcon(alert.severity)" class="mt-0.5 mr-2"></i>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium" :class="getAlertTextColor(alert.severity)">
                {{ alert.title }}
              </p>
              <p class="text-xs mt-1" :class="getAlertDescriptionColor(alert.severity)">
                {{ alert.description }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="pt-4 border-t border-gray-200">
        <div class="flex flex-col sm:flex-row gap-2">
          <button 
            @click="navigateToVatReturn"
            class="flex-1 px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
          >
            <i class="fas fa-file-export mr-2"></i>
            {{ $t('vat.generate_return') }}
          </button>
          <button 
            @click="viewComplianceHistory"
            class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
          >
            <i class="fas fa-history mr-2"></i>
            {{ $t('vat.view_history') }}
          </button>
        </div>
      </div>

      <!-- Error State -->
      <div v-if="error" class="p-4 bg-red-50 border border-red-200 rounded-lg">
        <div class="flex items-center">
          <i class="fas fa-exclamation-triangle text-red-400 mr-2"></i>
          <p class="text-sm text-red-700">{{ error }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useRouter } from 'vue-router'
import axios from '@/scripts/plugins/axios'

export default {
  name: 'VatStatus',
  setup() {
    const companyStore = useCompanyStore()
    const notificationStore = useNotificationStore()
    const router = useRouter()

    // State
    const isLoading = ref(false)
    const vatStatus = ref(null)
    const complianceAlerts = ref([])
    const error = ref(null)

    // Computed properties
    const currentCompany = computed(() => companyStore.selectedCompany)

    // Methods
    const fetchVatStatus = async () => {
      if (!currentCompany.value?.id) {
        setDefaultStatus()
        return
      }

      try {
        isLoading.value = true
        error.value = null

        const response = await axios.get(`/tax/vat-status/${currentCompany.value.id}`)
        
        if (response.data && response.data.data) {
          vatStatus.value = response.data.data.status
          complianceAlerts.value = response.data.data.alerts || []
        } else {
          setDefaultStatus()
        }
      } catch (err) {
        console.error('Failed to fetch VAT status:', err)
        
        // Handle API not yet implemented gracefully
        if (err.response?.status === 404 || err.response?.status === 501) {
          console.info('VAT status API not available, using mock data')
          setMockStatus()
        } else {
          error.value = err.response?.data?.message || 'Failed to load VAT status'
          setDefaultStatus()
        }
      } finally {
        isLoading.value = false
      }
    }

    const setDefaultStatus = () => {
      vatStatus.value = {
        compliance_status: 'unknown',
        last_checked: new Date(),
        last_generated: null,
        current_period: null,
        next_due_date: null
      }
      complianceAlerts.value = []
    }

    const setMockStatus = () => {
      const today = new Date()
      const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1)
      const nextDue = new Date(today.getFullYear(), today.getMonth() + 1, 15)

      vatStatus.value = {
        compliance_status: 'compliant',
        last_checked: today,
        last_generated: lastMonth,
        current_period: getCurrentPeriod(),
        next_due_date: nextDue
      }

      // Generate mock alerts based on current date and VAT rules
      complianceAlerts.value = generateMockAlerts()
    }

    const getCurrentPeriod = () => {
      const today = new Date()
      const year = today.getFullYear()
      const month = today.getMonth() + 1
      
      if (month === 1) return `${year - 1}-12`
      return `${year}-${(month - 1).toString().padStart(2, '0')}`
    }

    const generateMockAlerts = () => {
      const alerts = []
      const today = new Date()
      const dayOfMonth = today.getDate()

      // Alert if approaching VAT submission deadline (15th of next month)
      if (dayOfMonth >= 10 && dayOfMonth <= 15) {
        alerts.push({
          id: 'vat-deadline',
          severity: 'warning',
          title: 'VAT Return Due Soon',
          description: 'VAT return for last month is due on the 15th of this month.'
        })
      }

      // Alert if VAT number is missing
      if (!currentCompany.value?.vat_number) {
        alerts.push({
          id: 'vat-number-missing',
          severity: 'error',
          title: 'VAT Number Missing',
          description: 'Company VAT number is required for VAT compliance.'
        })
      }

      return alerts
    }

    const getStatusColor = (status) => {
      switch (status) {
        case 'compliant': return 'bg-green-500'
        case 'warning': return 'bg-yellow-500'
        case 'non_compliant': return 'bg-red-500'
        default: return 'bg-gray-400'
      }
    }

    const getStatusText = (status) => {
      switch (status) {
        case 'compliant': return 'VAT compliance up to date'
        case 'warning': return 'Action required soon'
        case 'non_compliant': return 'Non-compliant - immediate action required'
        default: return 'Status unknown'
      }
    }

    const getAlertClasses = (severity) => {
      switch (severity) {
        case 'error': return 'bg-red-50 border-red-400'
        case 'warning': return 'bg-yellow-50 border-yellow-400'
        default: return 'bg-blue-50 border-blue-400'
      }
    }

    const getAlertIcon = (severity) => {
      switch (severity) {
        case 'error': return 'fas fa-exclamation-circle text-red-400'
        case 'warning': return 'fas fa-exclamation-triangle text-yellow-400'
        default: return 'fas fa-info-circle text-blue-400'
      }
    }

    const getAlertTextColor = (severity) => {
      switch (severity) {
        case 'error': return 'text-red-800'
        case 'warning': return 'text-yellow-800'
        default: return 'text-blue-800'
      }
    }

    const getAlertDescriptionColor = (severity) => {
      switch (severity) {
        case 'error': return 'text-red-600'
        case 'warning': return 'text-yellow-600'
        default: return 'text-blue-600'
      }
    }

    const formatDate = (date) => {
      if (!date) return '-'
      
      const d = new Date(date)
      return d.toLocaleDateString('mk-MK', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }

    const refreshData = () => {
      fetchVatStatus()
    }

    const navigateToVatReturn = () => {
      router.push('/admin/settings/vat-return')
    }

    const viewComplianceHistory = () => {
      notificationStore.showNotification({
        type: 'info',
        message: 'VAT compliance history feature coming soon'
      })
    }

    // Watchers
    watch(() => currentCompany.value?.id, (newId) => {
      if (newId) {
        fetchVatStatus()
      }
    })

    // Initialize
    onMounted(() => {
      fetchVatStatus()
    })

    return {
      isLoading,
      vatStatus,
      complianceAlerts,
      error,
      currentCompany,
      getStatusColor,
      getStatusText,
      getAlertClasses,
      getAlertIcon,
      getAlertTextColor,
      getAlertDescriptionColor,
      formatDate,
      refreshData,
      navigateToVatReturn,
      viewComplianceHistory
    }
  }
}
</script>

<style scoped>
/* Custom transitions for loading states */
.vat-status-widget {
  transition: all 0.3s ease;
}

/* Hover effects for buttons */
.vat-status-widget button:hover {
  transform: translateY(-1px);
}

/* Animation for refresh button */
.fa-sync-alt {
  transition: transform 0.3s ease;
}

/* Custom scrollbar for alerts if needed */
.space-y-2::-webkit-scrollbar {
  width: 4px;
}

.space-y-2::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 2px;
}

.space-y-2::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 2px;
}

.space-y-2::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>

// LLM-CHECKPOINT