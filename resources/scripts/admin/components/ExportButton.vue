<template>
  <BaseDropdown>
    <template #activator>
      <BaseButton variant="primary-outline" :loading="isExporting">
        <template #left="slotProps">
          <BaseIcon name="ArrowDownTrayIcon" :class="slotProps.class" />
        </template>
        {{ $t('general.export') }}
      </BaseButton>
    </template>

    <BaseDropdownItem @click="handleExport('csv')">
      <BaseIcon name="DocumentTextIcon" class="mr-3 text-gray-600" />
      {{ $t('general.export_csv') }}
    </BaseDropdownItem>

    <BaseDropdownItem @click="handleExport('xlsx')">
      <BaseIcon name="TableCellsIcon" class="mr-3 text-gray-600" />
      {{ $t('general.export_excel') }}
    </BaseDropdownItem>

    <BaseDropdownItem @click="handleExport('pdf')">
      <BaseIcon name="DocumentIcon" class="mr-3 text-gray-600" />
      {{ $t('general.export_pdf') }}
    </BaseDropdownItem>
  </BaseDropdown>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import axios from 'axios'

const props = defineProps({
  type: {
    type: String,
    required: true,
    validator: (value) => {
      return ['invoices', 'customers', 'suppliers', 'expenses', 'items', 'bills', 'payments', 'transactions'].includes(value)
    }
  },
  filters: {
    type: Object,
    default: () => ({})
  }
})

const { t } = useI18n()
const notificationStore = useNotificationStore()
const companyStore = useCompanyStore()

const isExporting = ref(false)

async function handleExport(format) {
  try {
    isExporting.value = true

    const companyId = companyStore.selectedCompany?.id
    if (!companyId) {
      throw new Error('No company selected')
    }

    // Prepare export parameters from filters
    const params = {}

    // Map common filter fields
    if (props.filters.from_date) {
      params.start_date = props.filters.from_date
    }
    if (props.filters.to_date) {
      params.end_date = props.filters.to_date
    }
    if (props.filters.status) {
      params.status = props.filters.status
    }

    const response = await axios.post(
      `/companies/${companyId}/exports`,
      {
        type: props.type,
        format: format,
        params: params
      }
    )

    if (response.data.export_job) {
      notificationStore.showNotification({
        type: 'success',
        message: t('general.export_started'),
      })

      // Poll for completion or show message that it will be available soon
      pollExportStatus(companyId, response.data.export_job.id)
    }
  } catch (error) {
    console.error('Export failed:', error)
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('general.export_failed'),
    })
  } finally {
    isExporting.value = false
  }
}

async function pollExportStatus(companyId, exportJobId, attempts = 0) {
  const maxAttempts = 30 // Poll for up to 1 minute (30 * 2s)

  if (attempts >= maxAttempts) {
    notificationStore.showNotification({
      type: 'info',
      message: t('general.export_taking_long'),
    })
    return
  }

  try {
    const response = await axios.get(`/companies/${companyId}/exports`)

    const exportJob = response.data.data?.find(job => job.id === exportJobId)

    if (!exportJob) {
      return
    }

    if (exportJob.status === 'completed') {
      // Download the file
      const downloadUrl = `/api/v1/companies/${companyId}/exports/${exportJobId}/download`  // Keep full path for window.location
      window.location.href = downloadUrl

      notificationStore.showNotification({
        type: 'success',
        message: t('general.export_ready'),
      })
    } else if (exportJob.status === 'failed') {
      notificationStore.showNotification({
        type: 'error',
        message: exportJob.error_message || t('general.export_failed'),
      })
    } else {
      // Still processing, poll again
      setTimeout(() => {
        pollExportStatus(companyId, exportJobId, attempts + 1)
      }, 2000) // Poll every 2 seconds
    }
  } catch (error) {
    console.error('Failed to check export status:', error)
  }
}
</script>
// CLAUDE-CHECKPOINT
