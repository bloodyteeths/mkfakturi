<template>
  <SelectTemplateModal />
  <ItemModal />
  <TaxTypeModal />

  <BasePage class="relative invoice-create-page">
    <form @submit.prevent="submitForm">
      <BasePageHeader :title="pageTitle">
        <BaseBreadcrumb>
          <BaseBreadcrumbItem
            :title="$t('general.home')"
            to="/admin/dashboard"
          />
          <BaseBreadcrumbItem
            :title="$t('proforma_invoices.proforma_invoice', 2)"
            to="/admin/proforma-invoices"
          />
          <BaseBreadcrumbItem
            v-if="$route.name === 'proforma-invoices.edit'"
            :title="$t('proforma_invoices.edit_proforma_invoice')"
            to="#"
            active
          />
          <BaseBreadcrumbItem
            v-else
            :title="$t('proforma_invoices.new_proforma_invoice')"
            to="#"
            active
          />
        </BaseBreadcrumb>

        <template #actions>
          <router-link
            v-if="$route.name === 'proforma-invoices.edit'"
            :to="`/proforma-invoices/pdf/${proformaInvoiceStore.newProformaInvoice.unique_hash}`"
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
            {{ $t('proforma_invoices.save_proforma_invoice') }}
          </BaseButton>
        </template>
      </BasePageHeader>

      <!-- Proforma Notice Banner -->
      <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
        <div class="flex items-center">
          <BaseIcon name="ExclamationTriangleIcon" class="h-5 w-5 text-yellow-600 mr-2" />
          <span class="text-sm text-yellow-800 font-medium">
            {{ $t('proforma_invoices.not_fiscal_notice') }}
          </span>
        </div>
      </div>

      <!-- Select Customer & Basic Fields  -->
      <ProformaInvoiceBasicFields
        :v="v$"
        :is-loading="isLoadingContent"
        :is-edit="isEdit"
      />

      <BaseScrollPane>
        <!-- Proforma Invoice Items -->
        <InvoiceItems
          :currency="proformaInvoiceStore.newProformaInvoice.selectedCurrency"
          :is-loading="isLoadingContent"
          :item-validation-scope="proformaInvoiceValidationScope"
          :store="proformaInvoiceStore"
          store-prop="newProformaInvoice"
        />

        <!-- Proforma Invoice Footer Section -->
        <div
          class="
            block
            mt-10
            invoice-foot
            lg:flex lg:justify-between lg:items-start
          "
        >
          <div class="relative w-full lg:w-1/2 lg:mr-4">
            <!-- Proforma Invoice Custom Notes -->
            <NoteFields
              :store="proformaInvoiceStore"
              store-prop="newProformaInvoice"
              :fields="proformaInvoiceNoteFieldList"
              type="Estimate"
            />

            <!-- Proforma Invoice Custom Fields -->
            <InvoiceCustomFields
              type="Estimate"
              :is-edit="isEdit"
              :is-loading="isLoadingContent"
              :store="proformaInvoiceStore"
              store-prop="newProformaInvoice"
              :custom-field-scope="proformaInvoiceValidationScope"
              class="mb-6"
            />

            <!-- Proforma Invoice Template Button-->
            <SelectTemplate
              :store="proformaInvoiceStore"
              store-prop="newProformaInvoice"
              component-name="InvoiceTemplate"
              :is-mark-as-default="isMarkAsDefault"
            />
          </div>

          <InvoiceTotal
            :currency="proformaInvoiceStore.newProformaInvoice.selectedCurrency"
            :is-loading="isLoadingContent"
            :store="proformaInvoiceStore"
            store-prop="newProformaInvoice"
            tax-popup-type="invoice"
          />
        </div>
      </BaseScrollPane>
    </form>
  </BasePage>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
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

import { useProformaInvoiceStore } from '@/scripts/admin/stores/proforma-invoice'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useCustomFieldStore } from '@/scripts/admin/stores/custom-field'

import InvoiceItems from '@/scripts/admin/components/estimate-invoice-common/CreateItems.vue'
import InvoiceTotal from '@/scripts/admin/components/estimate-invoice-common/CreateTotal.vue'
import SelectTemplate from '@/scripts/admin/components/estimate-invoice-common/SelectTemplateButton.vue'
import ProformaInvoiceBasicFields from './ProformaInvoiceCreateBasicFields.vue'
import InvoiceCustomFields from '@/scripts/admin/components/custom-fields/CreateCustomFields.vue'
import NoteFields from '@/scripts/admin/components/estimate-invoice-common/CreateNotesField.vue'
import SelectTemplateModal from '@/scripts/admin/components/modal-components/SelectTemplateModal.vue'
import TaxTypeModal from '@/scripts/admin/components/modal-components/TaxTypeModal.vue'
import ItemModal from '@/scripts/admin/components/modal-components/ItemModal.vue'

const proformaInvoiceStore = useProformaInvoiceStore()
const companyStore = useCompanyStore()
const customFieldStore = useCustomFieldStore()

const { t } = useI18n()
let route = useRoute()
let router = useRouter()

const proformaInvoiceValidationScope = 'newProformaInvoice'
let isSaving = ref(false)
const isMarkAsDefault = ref(false)

const proformaInvoiceNoteFieldList = ref([
  'customer',
  'company',
  'customerCustom',
  'estimate',
  'estimateCustom',
])

let isLoadingContent = computed(
  () => proformaInvoiceStore.isFetchingProformaInvoice || proformaInvoiceStore.isFetchingInitialSettings
)

let pageTitle = computed(() =>
  isEdit.value ? t('proforma_invoices.edit_proforma_invoice') : t('proforma_invoices.new_proforma_invoice')
)

let isEdit = computed(() => route.name === 'proforma-invoices.edit')

const rules = {
  proforma_invoice_date: {
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
  proforma_invoice_number: {
    required: helpers.withMessage(t('validation.required'), required),
  },
  exchange_rate: {
    required: requiredIf(function () {
      helpers.withMessage(t('validation.required'), required)
      return proformaInvoiceStore.showExchangeRate
    }),
    decimal: helpers.withMessage(t('validation.valid_exchange_rate'), decimal),
  },
}

const v$ = useVuelidate(
  rules,
  computed(() => proformaInvoiceStore.newProformaInvoice),
  { $scope: proformaInvoiceValidationScope }
)

customFieldStore.resetCustomFields()
v$.value.$reset
proformaInvoiceStore.resetCurrentProformaInvoice()
proformaInvoiceStore.fetchProformaInvoiceInitialSettings(isEdit.value)

watch(
  () => proformaInvoiceStore.newProformaInvoice.customer,
  (newVal) => {
    if (newVal && newVal.currency) {
      proformaInvoiceStore.newProformaInvoice.selectedCurrency = newVal.currency
    } else {
      proformaInvoiceStore.newProformaInvoice.selectedCurrency =
        companyStore.selectedCompanyCurrency
    }
  }
)

async function submitForm() {
  v$.value.$touch()

  if (v$.value.$invalid) {
    console.log('Form is invalid:', v$.value.$errors)
    return false
  }

  isSaving.value = true

  let data = cloneDeep({
    ...proformaInvoiceStore.newProformaInvoice,
    sub_total: proformaInvoiceStore.getSubTotal,
    total: proformaInvoiceStore.getTotal,
    tax: proformaInvoiceStore.getTotalTax,
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
    !proformaInvoiceStore.newProformaInvoice.tax_per_item === 'YES'
    && data.taxes.length
  ){
    data.tax_type_ids = data.taxes.map(_t => _t.tax_type_id)
  }

  try {
    const action = isEdit.value
      ? proformaInvoiceStore.updateProformaInvoice
      : proformaInvoiceStore.addProformaInvoice

    const response = await action(data)

    router.push(`/admin/proforma-invoices/${response.data.data.id}/view`)
  } catch (err) {
    console.error(err)
  }

  isSaving.value = false
}
</script>
// CLAUDE-CHECKPOINT
