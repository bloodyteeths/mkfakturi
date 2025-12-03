<template>
  <BaseModal
    :show="modalActive"
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

  <!-- Upgrade CTA Modal -->
  <UpgradeCTA
    :show="showUpgradeModal"
    @close="showUpgradeModal = false"
    required-tier="standard"
    :feature-name="$t('invoices.export_xml')"
    icon="DocumentArrowDownIcon"
    :features="[
      $t('subscriptions.features.efaktura_sending'),
      $t('subscriptions.features.qes_signing'),
      $t('subscriptions.features.multi_users'),
      $t('subscriptions.features.200_invoices'),
    ]"
  />
</template>

<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useModalStore } from '@/scripts/stores/modal'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useInvoiceStore } from '@/scripts/admin/stores/invoice'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useUserStore } from '@/scripts/admin/stores/user'
import UpgradeCTA from '@/scripts/admin/components/UpgradeCTA.vue'

const { t } = useI18n()
const modalStore = useModalStore()
const notificationStore = useNotificationStore()
const invoiceStore = useInvoiceStore()
const companyStore = useCompanyStore()
const userStore = useUserStore()

// Modal state
const isExporting = ref(false)
const showUpgradeModal = ref(false)

// Export options
const exportFormat = ref('ubl')
const includeSignature = ref(true)
const validateXml = ref(true)

const formatOptions = computed(() => [
  { value: 'ubl', label: t('invoices.format_ubl_21') },
  { value: 'ubl_signed', label: t('invoices.format_ubl_signed') },
])

const modalActive = computed(() => {
  return modalStore.active && modalStore.componentName === 'ExportXmlModal'
})

const invoice = computed(() => {
  return modalStore.data
})

// Check if company has Standard+ tier for e-Faktura
const canExportXml = computed(() => {
  // Super admin bypass
  if (userStore.currentUser?.role === 'super admin') {
    return true
  }

  const company = companyStore.selectedCompany
  if (!company || !company.subscription) {
    return false // Default to Free tier
  }

  const plan = company.subscription.plan || 'free'
  const status = company.subscription.status || 'inactive'
  const onTrial = company.subscription.on_trial || false

  // Trial users get Standard features
  if (onTrial) {
    return true
  }

  // Check plan hierarchy (Standard, Business, Max can export)
  const allowedPlans = ['standard', 'business', 'max']
  return allowedPlans.includes(plan) && ['active', 'trial'].includes(status)
})

function closeModal() {
  modalStore.closeModal()
  // Reset form state after modal closes
  setTimeout(() => {
    exportFormat.value = 'ubl'
    includeSignature.value = true
    validateXml.value = true
  }, 300)
}

async function performExport() {
  // Check tier before exporting
  if (!canExportXml.value) {
    closeModal()
    // Use setTimeout to ensure modal closes before showing upgrade CTA
    setTimeout(() => {
      showUpgradeModal.value = true
    }, 350)
    return
  }

  if (isExporting.value || !invoice.value) return

  try {
    isExporting.value = true

    // Prepare export parameters
    const exportParams = {
      invoice_id: invoice.value.id,
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
      const filename = `invoice-${invoice.value.invoice_number}-${exportFormat.value}.xml`
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

    // Handle 402 Payment Required (tier upgrade needed)
    if (error.response?.status === 402) {
      closeModal()
      setTimeout(() => {
        showUpgradeModal.value = true
      }, 350)
      return
    }

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
