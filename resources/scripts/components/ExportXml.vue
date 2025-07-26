<template>
  <div class="export-xml-container">
    <!-- Export Button for Dropdown -->
    <BaseDropdownItem v-if="!standalone" @click="exportXml">
      <BaseIcon
        name="DocumentArrowDownIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('invoices.export_xml') }}
    </BaseDropdownItem>

    <!-- Standalone Export Button -->
    <BaseButton
      v-if="standalone"
      variant="primary-outline"
      class="ml-3"
      @click="exportXml"
      :loading="isExporting"
    >
      <BaseIcon name="DocumentArrowDownIcon" class="w-4 h-4 mr-2" />
      {{ $t('invoices.export_xml') }}
    </BaseButton>

    <!-- Export Modal -->
    <BaseModal
      :show="showModal"
      @close="closeModal"
      :title="$t('invoices.export_xml_title')"
      variant="sm"
    >
      <div class="px-6 pb-6">
        <div class="mb-4">
          <BaseInputLabel>{{ $t('invoices.export_format') }}</BaseInputLabel>
          <BaseSelectInput
            v-model="exportFormat"
            :options="formatOptions"
            class="mt-1"
          />
        </div>

        <div class="mb-4">
          <BaseCheckbox
            v-model="includeSignature"
            :label="$t('invoices.include_digital_signature')"
          />
          <p class="text-sm text-gray-500 mt-1">
            {{ $t('invoices.signature_help_text') }}
          </p>
        </div>

        <div class="mb-6">
          <BaseCheckbox
            v-model="validateXml"
            :label="$t('invoices.validate_xml')"
          />
          <p class="text-sm text-gray-500 mt-1">
            {{ $t('invoices.validation_help_text') }}
          </p>
        </div>

        <div class="flex justify-end space-x-3">
          <BaseButton variant="outline" @click="closeModal">
            {{ $t('general.cancel') }}
          </BaseButton>
          <BaseButton
            variant="primary"
            @click="performExport"
            :loading="isExporting"
          >
            <BaseIcon name="DocumentArrowDownIcon" class="w-4 h-4 mr-2" />
            {{ $t('invoices.download_xml') }}
          </BaseButton>
        </div>
      </div>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useInvoiceStore } from '@/scripts/admin/stores/invoice'

const props = defineProps({
  invoice: {
    type: Object,
    required: true,
  },
  standalone: {
    type: Boolean,
    default: false,
  },
})

const { t } = useI18n()
const notificationStore = useNotificationStore()
const invoiceStore = useInvoiceStore()

// Modal state
const showModal = ref(false)
const isExporting = ref(false)

// Export options
const exportFormat = ref('ubl')
const includeSignature = ref(true)
const validateXml = ref(true)

const formatOptions = computed(() => [
  { value: 'ubl', label: t('invoices.format_ubl_21') },
  { value: 'ubl_signed', label: t('invoices.format_ubl_signed') },
])

function exportXml() {
  showModal.value = true
}

function closeModal() {
  showModal.value = false
}

async function performExport() {
  if (isExporting.value) return

  try {
    isExporting.value = true

    // Prepare export parameters
    const exportParams = {
      invoice_id: props.invoice.id,
      format: exportFormat.value,
      include_signature: includeSignature.value,
      validate: validateXml.value,
    }

    // Call API to generate and download XML
    const response = await invoiceStore.exportXml(exportParams)

    if (response.data) {
      // Create download link
      const blob = new Blob([response.data], {
        type: 'application/xml',
      })

      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url

      // Generate filename
      const filename = `invoice-${props.invoice.invoice_number}-${exportFormat.value}.xml`
      link.download = filename

      // Trigger download
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      window.URL.revokeObjectURL(url)

      // Show success notification
      notificationStore.showNotification({
        type: 'success',
        message: t('invoices.xml_exported_successfully', {
          filename: filename,
        }),
      })

      closeModal()
    }
  } catch (error) {
    console.error('XML export failed:', error)

    let errorMessage = t('invoices.xml_export_failed')

    // Handle specific error types
    if (error.response?.data?.message) {
      errorMessage = error.response.data.message
    } else if (error.response?.status === 422) {
      errorMessage = t('invoices.xml_validation_failed')
    } else if (error.response?.status === 500) {
      errorMessage = t('invoices.xml_generation_failed')
    }

    notificationStore.showNotification({
      type: 'error',
      message: errorMessage,
    })
  } finally {
    isExporting.value = false
  }
}
</script>

<style scoped>
.export-xml-container {
  display: contents;
}
</style>