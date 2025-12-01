<template>
  <div class="grid grid-cols-12 gap-8 mt-6 mb-8">
    <BaseCustomerSelectPopup
      v-model="proformaInvoiceStore.newProformaInvoice.customer"
      :valid="v.customer_id"
      :content-loading="isLoading"
      type="proforma-invoice"
      class="col-span-12 lg:col-span-5 pr-0"
    />

    <BaseInputGrid class="col-span-12 lg:col-span-7">
      <BaseInputGroup
        :label="$t('proforma_invoices.proforma_invoice_date')"
        :content-loading="isLoading"
        required
        :error="v.proforma_invoice_date.$error && v.proforma_invoice_date.$errors[0].$message"
      >
        <BaseDatePicker
          v-model="proformaInvoiceStore.newProformaInvoice.proforma_invoice_date"
          :content-loading="isLoading"
          :calendar-button="true"
          calendar-button-icon="calendar"
        />
      </BaseInputGroup>

      <BaseInputGroup
        :label="$t('proforma_invoices.expiry_date')"
        :content-loading="isLoading"
      >
        <BaseDatePicker
          v-model="proformaInvoiceStore.newProformaInvoice.expiry_date"
          :content-loading="isLoading"
          :calendar-button="true"
          calendar-button-icon="calendar"
        />
      </BaseInputGroup>

      <BaseInputGroup
        :label="$t('proforma_invoices.proforma_invoice_number')"
        :content-loading="isLoading"
        :error="v.proforma_invoice_number.$error && v.proforma_invoice_number.$errors[0].$message"
        required
      >
        <BaseInput
          v-model="proformaInvoiceStore.newProformaInvoice.proforma_invoice_number"
          :content-loading="isLoading"
          @input="v.proforma_invoice_number.$touch()"
        />
      </BaseInputGroup>

      <ExchangeRateConverter
        :store="proformaInvoiceStore"
        store-prop="newProformaInvoice"
        :v="v"
        :is-loading="isLoading"
        :is-edit="isEdit"
        :customer-currency="proformaInvoiceStore.newProformaInvoice.currency_id"
      />

      <BaseInputGroup
        :label="$t('proforma_invoices.reference_number')"
        :content-loading="isLoading"
      >
        <BaseInput
          v-model="proformaInvoiceStore.newProformaInvoice.reference_number"
          :content-loading="isLoading"
        />
      </BaseInputGroup>

      <BaseInputGroup
        :label="$t('proforma_invoices.customer_po_number')"
        :content-loading="isLoading"
      >
        <BaseInput
          v-model="proformaInvoiceStore.newProformaInvoice.customer_po_number"
          :content-loading="isLoading"
        />
      </BaseInputGroup>
    </BaseInputGrid>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import ExchangeRateConverter from '@/scripts/admin/components/estimate-invoice-common/ExchangeRateConverter.vue'
import { useProformaInvoiceStore } from '@/scripts/admin/stores/proforma-invoice'
import { useCompanyStore } from '@/scripts/admin/stores/company'

const props = defineProps({
  v: {
    type: Object,
    default: null,
  },
  isLoading: {
    type: Boolean,
    default: false,
  },
  isEdit: {
    type: Boolean,
    default: false,
  },
})

const proformaInvoiceStore = useProformaInvoiceStore()
const companyStore = useCompanyStore()

const selectedSettings = computed(() => companyStore.selectedCompanySettings || {})
</script>
// CLAUDE-CHECKPOINT
