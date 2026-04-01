<template>
  <SelectTemplateModal />
  <ItemModal />
  <TaxTypeModal />
  <SalesTax
    v-if="salesTaxEnabled && (!isLoadingContent || route.query.customer)"
    :store="invoiceStore"
    :is-edit="isEdit"
    store-prop="newInvoice"
    :customer="invoiceStore.newInvoice.customer"
  />

  <BasePage class="relative invoice-create-page">
    <form @submit.prevent="submitForm">
      <BasePageHeader :title="pageTitle">
        <BaseBreadcrumb>
          <BaseBreadcrumbItem
            :title="$t('general.home')"
            to="/admin/dashboard"
          />
          <BaseBreadcrumbItem
            :title="$t('invoices.invoice', 2)"
            to="/admin/invoices"
          />
          <BaseBreadcrumbItem
            v-if="$route.name === 'invoices.edit'"
            :title="$t('invoices.edit_invoice')"
            to="#"
            active
          />
          <BaseBreadcrumbItem
            v-else
            :title="$t('invoices.new_invoice')"
            to="#"
            active
          />
        </BaseBreadcrumb>

        <template #actions>
          <router-link
            v-if="$route.name === 'invoices.edit'"
            :to="`/invoices/pdf/${invoiceStore.newInvoice.unique_hash}`"
            target="_blank"
          >
            <BaseButton class="mr-3" variant="primary-outline" type="button">
              <span class="flex">
                {{ $t('general.view_pdf') }}
              </span>
            </BaseButton>
          </router-link>

          <BaseButton
            :loading="isSaving"
            :disabled="isSaving"
            variant="primary"
            type="submit"
          >
            <template #left="slotProps">
              <BaseIcon
                v-if="!isSaving"
                name="ArrowDownOnSquareIcon"
                :class="slotProps.class"
              />
            </template>
            {{ $t('invoices.save_invoice') }}
          </BaseButton>
        </template>
      </BasePageHeader>

      <!-- Select Customer & Basic Fields  -->
      <InvoiceBasicFields
        :v="v$"
        :is-loading="isLoadingContent"
        :is-edit="isEdit"
      />

      <BaseScrollPane>
        <!-- Barcode Scanner Mode Toggle -->
        <div class="px-6 mb-4">
          <ScannerModeToggle
            :is-enabled="scannerEnabled"
            :is-processing="scannerProcessing"
            :last-scanned-item="lastScannedItem"
            :error="scannerError"
            :scan-count="scanCount"
            @toggle="toggleScanner"
          />
        </div>

        <!-- Invoice Items -->
        <InvoiceItems
          :currency="invoiceStore.newInvoice.selectedCurrency"
          :is-loading="isLoadingContent"
          :item-validation-scope="invoiceValidationScope"
          :store="invoiceStore"
          store-prop="newInvoice"
        />

        <!-- Invoice Footer Section -->
        <div
          class="
            block
            mt-10
            invoice-foot
            lg:flex lg:justify-between lg:items-start
          "
        >
          <div class="relative w-full lg:w-1/2 lg:mr-4">
            <!-- Invoice Custom Notes -->
            <NoteFields
              :store="invoiceStore"
              store-prop="newInvoice"
              :fields="invoiceNoteFieldList"
              type="Invoice"
            />

            <!-- Source Document Upload -->
            <BaseInputGroup
              :label="$t('invoices.source_document', 'Source Document')"
              class="mb-4"
            >
              <BaseFileUploader
                v-model="sourceDocumentFiles"
                accept="image/*,.pdf,.doc,.docx"
                @change="onSourceDocumentChange"
                @remove="onSourceDocumentRemove"
              />
            </BaseInputGroup>

            <!-- Invoice Custom Fields -->
            <InvoiceCustomFields
              type="Invoice"
              :is-edit="isEdit"
              :is-loading="isLoadingContent"
              :store="invoiceStore"
              store-prop="newInvoice"
              :custom-field-scope="invoiceValidationScope"
              class="mb-6"
            />

            <!-- Invoice Template Button-->
            <SelectTemplate
              :store="invoiceStore"
              store-prop="newInvoice"
              component-name="InvoiceTemplate"
              :is-mark-as-default="isMarkAsDefault"
            />
          </div>

          <InvoiceTotal
            :currency="invoiceStore.newInvoice.selectedCurrency"
            :is-loading="isLoadingContent"
            :store="invoiceStore"
            store-prop="newInvoice"
            tax-popup-type="invoice"
          />

          <div
            v-if="invoiceStore.newInvoice.type === 'advance'"
            class="px-4 py-2 mt-2 text-xs text-yellow-700 bg-yellow-50 border border-yellow-200 rounded"
          >
            {{ $t('invoices.advance_vat_note', 'ДДВ е пресметан но не е вклучен во износот за плаќање. Купувачот плаќа ДДВ еднаш, при финалната фактура.') }}
          </div>
        </div>
      </BaseScrollPane>
    </form>

    <DuplicateWarningModal
      :show="showDuplicateWarning"
      :duplicates="duplicateRecords"
      entity-type="invoices"
      @close="closeDuplicateWarning"
      @confirm="saveWithDuplicate"
    />
  </BasePage>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  required,
  maxLength,
  helpers,
  requiredIf,
  decimal,
} from '@vuelidate/validators'
import useVuelidate from '@vuelidate/core'
import { cloneDeep } from 'lodash'

import { useInvoiceStore } from '@/scripts/admin/stores/invoice'
import { useModuleStore } from '@/scripts/admin/stores/module'
import { useNotesStore } from '@/scripts/admin/stores/note'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useCustomFieldStore } from '@/scripts/admin/stores/custom-field'
import { useCustomerStore } from '@/scripts/admin/stores/customer'
import { useModalStore } from '@/scripts/stores/modal'
import { useTaxTypeStore } from '@/scripts/admin/stores/tax-type'
import { useReceiptScannerStore } from '@/scripts/admin/stores/receipt-scanner'
import invoiceItemStub from '@/scripts/admin/stub/invoice-item'

import InvoiceItems from '@/scripts/admin/components/estimate-invoice-common/CreateItems.vue'
import InvoiceTotal from '@/scripts/admin/components/estimate-invoice-common/CreateTotal.vue'
import SelectTemplate from '@/scripts/admin/components/estimate-invoice-common/SelectTemplateButton.vue'
import InvoiceBasicFields from './InvoiceCreateBasicFields.vue'
import InvoiceCustomFields from '@/scripts/admin/components/custom-fields/CreateCustomFields.vue'
import NoteFields from '@/scripts/admin/components/estimate-invoice-common/CreateNotesField.vue'
import SelectTemplateModal from '@/scripts/admin/components/modal-components/SelectTemplateModal.vue'
import TaxTypeModal from '@/scripts/admin/components/modal-components/TaxTypeModal.vue'
import ItemModal from '@/scripts/admin/components/modal-components/ItemModal.vue'
import SalesTax from '@/scripts/admin/components/estimate-invoice-common/SalesTax.vue'
import ScannerModeToggle from '@/scripts/admin/components/ScannerModeToggle.vue'
import DuplicateWarningModal from '@/scripts/admin/components/modal-components/DuplicateWarningModal.vue'
import { useBarcodeScanner } from '@/scripts/admin/composables/useBarcodeScanner'
import Guid from 'guid'
import TaxStub from '@/scripts/admin/stub/tax'

const invoiceStore = useInvoiceStore()
const companyStore = useCompanyStore()
const customFieldStore = useCustomFieldStore()
const moduleStore = useModuleStore()
const notesStore = useNotesStore()
const receiptScannerStore = useReceiptScannerStore()

const { t } = useI18n()
let route = useRoute()
let router = useRouter()

const invoiceValidationScope = 'newInvoice'
let isSaving = ref(false)
const isMarkAsDefault = ref(false)
const showDuplicateWarning = ref(false)
const duplicateRecords = ref([])
const sourceDocumentFiles = ref([])
const sourceDocumentFile = ref(null)

function onSourceDocumentChange(fileName, file) {
  sourceDocumentFile.value = file
}

function onSourceDocumentRemove() {
  sourceDocumentFile.value = null
}

// Barcode Scanner Integration
function handleScannedItem(item) {
  // Add scanned item to invoice
  const newItem = {
    id: Guid.raw(),
    item_id: item.id,
    name: item.name,
    description: item.description || '',
    quantity: 1,
    price: item.price,
    discount_type: 'fixed',
    discount_val: 0,
    discount: 0,
    total: item.price,
    totalTax: 0,
    totalSimpleTax: 0,
    totalCompoundTax: 0,
    tax: 0,
    taxes: item.taxes?.length > 0
      ? item.taxes.map(tax => ({ ...tax, id: Guid.raw() }))
      : [{ ...TaxStub, id: Guid.raw() }],
    sku: item.sku || '',
    barcode: item.barcode || '',
    track_quantity: item.track_quantity || false,
    unit_name: item.unit?.name || '',
    warehouse_id: null
  }

  invoiceStore.$patch((state) => {
    // Check if an empty item row exists at the end
    const items = state.newInvoice.items
    const lastItem = items[items.length - 1]

    if (lastItem && !lastItem.item_id && !lastItem.name) {
      // Replace empty item
      items[items.length - 1] = newItem
    } else {
      // Add new item
      items.push(newItem)
    }
  })

  // Refresh totals
  invoiceStore.updateItem({
    ...newItem,
    index: invoiceStore.newInvoice.items.length - 1,
    sub_total: newItem.price * newItem.quantity
  })
}

const {
  isEnabled: scannerEnabled,
  isProcessing: scannerProcessing,
  lastScannedItem,
  error: scannerError,
  scanCount,
  toggleScanner
} = useBarcodeScanner({
  onItemFound: handleScannedItem,
  playSound: true
})

const invoiceNoteFieldList = ref([
  'customer',
  'company',
  'customerCustom',
  'invoice',
  'invoiceCustom',
])

let isLoadingContent = computed(
  () => invoiceStore.isFetchingInvoice || invoiceStore.isFetchingInitialSettings
)

let pageTitle = computed(() => {
  if (isEdit.value) return t('invoices.edit_invoice')
  if (invoiceStore.newInvoice.type === 'advance') return t('invoices.new_advance_invoice')
  return t('invoices.new_invoice')
})

const salesTaxEnabled = computed(() => {
  return (
    companyStore.selectedCompanySettings.sales_tax_us_enabled === 'YES' &&
    moduleStore.salesTaxUSEnabled
  )
})

let isEdit = computed(() => route.name === 'invoices.edit')

const rules = {
  invoice_date: {
    required: helpers.withMessage(t('validation.required'), required),
  },
  reference_number: {
    maxLength: helpers.withMessage(
      t('validation.price_maxlength'),
      maxLength(255)
    ),
  },
  customer_id: {
    required: helpers.withMessage(t('validation.required'), required),
  },
  invoice_number: {
    required: helpers.withMessage(t('validation.required'), required),
  },
  exchange_rate: {
    required: requiredIf(function () {
      helpers.withMessage(t('validation.required'), required)
      return invoiceStore.showExchangeRate
    }),
    decimal: helpers.withMessage(t('validation.valid_exchange_rate'), decimal),
  },
}

const v$ = useVuelidate(
  rules,
  computed(() => invoiceStore.newInvoice),
  { $scope: invoiceValidationScope }
)

customFieldStore.resetCustomFields()
v$.value.$reset
invoiceStore.resetCurrentInvoice()

// Set type from query param (e.g., ?type=advance)
if (route.query.type === 'advance') {
  invoiceStore.newInvoice.type = 'advance'
}

invoiceStore.fetchInvoiceInitialSettings(isEdit.value)

watch(
  () => invoiceStore.newInvoice.customer,
  (newVal) => {
    if (newVal && newVal.currency) {
      invoiceStore.newInvoice.selectedCurrency = newVal.currency
    } else {
      invoiceStore.newInvoice.selectedCurrency =
        companyStore.selectedCompanyCurrency
    }
  }
)

// Consume scanned invoice data after initial settings have loaded
const stopScanWatch = watch(
  () => invoiceStore.isFetchingInitialSettings,
  (newVal, oldVal) => {
    if (oldVal === true && newVal === false && !isEdit.value) {
      const scannedData = receiptScannerStore.consumeScannedInvoiceData()
      if (scannedData) {
        const si = scannedData.invoice
        if (si.invoice_number) invoiceStore.newInvoice.invoice_number = si.invoice_number
        if (si.invoice_date) invoiceStore.newInvoice.invoice_date = si.invoice_date
        if (si.due_date) invoiceStore.newInvoice.due_date = si.due_date
        if (si.notes) invoiceStore.newInvoice.notes = si.notes

        if (scannedData.items?.length > 0) {
          // Fetch tax types to match OCR-extracted DDV
          const taxTypeStore = useTaxTypeStore()
          taxTypeStore.fetchTaxTypes({ limit: 'all' }).then(() => {
            invoiceStore.newInvoice.items = scannedData.items.map((item) => {
              let tax = { ...TaxStub, id: Guid.raw() }
              const subtotal = (item.price || 0) * (item.quantity || 1)
              if (item.tax && subtotal > 0) {
                const rate = Math.round((item.tax / subtotal) * 100)
                const match = taxTypeStore.taxTypes.find(
                  (t) => t.percent && Math.abs(t.percent - rate) < 2
                )
                if (match) {
                  tax = {
                    ...TaxStub,
                    id: Guid.raw(),
                    tax_type_id: match.id,
                    name: match.name,
                    percent: match.percent,
                    compound_tax: match.compound_tax || false,
                  }
                }
              }
              return {
                ...invoiceItemStub,
                id: Guid.raw(),
                name: item.name || '',
                description: item.description || '',
                quantity: item.quantity || 1,
                price: Math.round((item.price || 0) * 100),
                taxes: [tax],
              }
            })
          })
        }

        // Search for existing customer by scanned name
        if (si.customer_name) {
          const customerStore = useCustomerStore()
          customerStore.fetchCustomers({ display_name: si.customer_name }).then(() => {
            if (customerStore.customers.length > 0) {
              invoiceStore.selectCustomer(customerStore.customers[0].id)
            } else {
              // No match — open "Add Customer" modal pre-filled with scanned name
              customerStore.currentCustomer.name = si.customer_name
              const modalStore = useModalStore()
              modalStore.openModal({
                title: t('customers.add_customer'),
                componentName: 'CustomerModal',
                variant: 'md',
              })
            }
          })
        }
      }
      // Also check for AI draft pre-fill
      if (!scannedData && route.query.draft_id) {
        loadAiDraft(route.query.draft_id)
      }
      stopScanWatch()
    }
  }
)

/**
 * Load AI-generated draft data to pre-fill the invoice form.
 */
async function loadAiDraft(draftId) {
  try {
    const { data } = await (await import('axios')).default.get(`/ai/drafts/${draftId}`)
    const d = data.entity_data || {}

    // Pre-fill customer
    if (d.customer_id) {
      invoiceStore.selectCustomer(d.customer_id)
    } else if (d.customer_name) {
      const customerStore = useCustomerStore()
      await customerStore.fetchCustomers({ display_name: d.customer_name })
      if (customerStore.customers.length > 0) {
        invoiceStore.selectCustomer(customerStore.customers[0].id)
      }
    }

    // Pre-fill dates
    if (d.date) invoiceStore.newInvoice.invoice_date = d.date
    if (d.due_date) invoiceStore.newInvoice.due_date = d.due_date
    if (d.notes) invoiceStore.newInvoice.notes = d.notes

    // Pre-fill items
    if (d.items?.length > 0) {
      invoiceStore.newInvoice.items = d.items.map((item) => ({
        ...invoiceItemStub,
        id: Guid.raw(),
        item_id: item.item_id || null,
        name: item.name || '',
        description: item.description || '',
        quantity: item.quantity || 1,
        price: item.unit_price || 0, // Already in cents from AI
        taxes: [{ ...TaxStub, id: Guid.raw() }],
      }))
    }

    // Mark draft as used
    try {
      await (await import('axios')).default.post(`/ai/drafts/${draftId}/use`)
    } catch (e) { /* non-critical */ }
  } catch (err) {
    console.error('[AI Draft] Failed to load draft:', err)
  }
}

async function submitForm(allowDuplicate = false) {
  if (typeof allowDuplicate !== 'boolean') allowDuplicate = false
  v$.value.$touch()

  if (v$.value.$invalid) {
    return false
  }

  isSaving.value = true

  let data = cloneDeep({
    ...invoiceStore.newInvoice,
    sub_total: invoiceStore.getSubTotal,
    total: invoiceStore.getTotal,
    tax: invoiceStore.getTotalTax,
  })
  if (data.discount_per_item === 'YES') {
    data.items.forEach((item, index) => {
      if (item.discount_type === 'fixed'){
        data.items[index].discount = item.discount * 100
      }
    })
  }
  else {
    if (data.discount_type === 'fixed'){
      data.discount = data.discount * 100
    }
  }
    if (
    !invoiceStore.newInvoice.tax_per_item === 'YES'
    && data.taxes.length
  ){
    data.tax_type_ids = data.taxes.map(_t => _t.tax_type_id)
  }

  try {
    const action = isEdit.value
      ? invoiceStore.updateInvoice
      : (d) => invoiceStore.addInvoice(d, allowDuplicate)

    const response = await action(data)

    if (response?.data?.is_duplicate_warning) {
      isSaving.value = false
      duplicateRecords.value = response.data.duplicates || []
      showDuplicateWarning.value = true
      return
    }

    // Upload source document if selected
    const invoiceId = response.data.data.id
    if (sourceDocumentFile.value) {
      try {
        const formData = new FormData()
        formData.append('source_document', sourceDocumentFile.value)
        const axios = (await import('axios')).default
        await axios.post(`/invoices/${invoiceId}/upload-document`, formData)
      } catch (e) {
        console.warn('Source document upload failed:', e)
      }
    }

    router.push(`/admin/invoices/${invoiceId}/view`)
  } catch (err) {
    // Error handled by store/notification system
  }

  isSaving.value = false
}

function closeDuplicateWarning() {
  showDuplicateWarning.value = false
  duplicateRecords.value = []
}

function saveWithDuplicate() {
  closeDuplicateWarning()
  submitForm(true)
}
</script>
