<template>
  <BaseModal
    :show="modalActive"
    @close="closeSendProformaInvoiceModal"
    @open="setInitialData"
  >
    <template #header>
      <div class="flex justify-between w-full">
        {{ modalTitle }}
        <BaseIcon
          name="XMarkIcon"
          class="w-6 h-6 text-gray-500 cursor-pointer"
          @click="closeSendProformaInvoiceModal"
        />
      </div>
    </template>
    <form v-if="!isPreview" action="">
      <div class="px-8 py-8 sm:p-6">
        <BaseInputGrid layout="one-column" class="col-span-7">
          <BaseInputGroup
            :label="$t('general.from')"
            required
            :error="v$.from.$error && v$.from.$errors[0].$message"
          >
            <BaseInput
              v-model="proformaInvoiceMailForm.from"
              type="text"
              :invalid="v$.from.$error"
              @input="v$.from.$touch()"
            />
          </BaseInputGroup>
          <BaseInputGroup
            :label="$t('general.to')"
            required
            :error="v$.to.$error && v$.to.$errors[0].$message"
          >
            <BaseInput
              v-model="proformaInvoiceMailForm.to"
              type="text"
              :invalid="v$.to.$error"
              @input="v$.to.$touch()"
            />
          </BaseInputGroup>
          <BaseInputGroup
            :error="v$.subject.$error && v$.subject.$errors[0].$message"
            :label="$t('general.subject')"
            required
          >
            <BaseInput
              v-model="proformaInvoiceMailForm.subject"
              type="text"
              :invalid="v$.subject.$error"
              @input="v$.subject.$touch()"
            />
          </BaseInputGroup>
          <BaseInputGroup
            :label="$t('general.body')"
            :error="v$.body.$error && v$.body.$errors[0].$message"
            required
          >
            <BaseCustomInput
              v-model="proformaInvoiceMailForm.body"
              :fields="proformaInvoiceMailFields"
            />
          </BaseInputGroup>
        </BaseInputGrid>
      </div>
      <div
        class="z-0 flex justify-end p-4 border-t border-gray-200 border-solid"
      >
        <BaseButton
          class="mr-3"
          variant="primary-outline"
          type="button"
          @click="closeSendProformaInvoiceModal"
        >
          {{ $t('general.cancel') }}
        </BaseButton>
        <BaseButton
          :loading="isLoading"
          :disabled="isLoading"
          variant="primary"
          type="button"
          class="mr-3"
          @click="submitForm"
        >
          <template #left="slotProps">
            <BaseIcon
              v-if="!isLoading"
              :class="slotProps.class"
              name="PhotoIcon"
            />
          </template>
          {{ $t('general.preview') }}
        </BaseButton>
      </div>
    </form>
    <div v-else>
      <div class="my-6 mx-4 border border-gray-200 relative">
        <BaseButton
          class="absolute top-4 right-4"
          :disabled="isLoading"
          variant="primary-outline"
          @click="cancelPreview"
        >
          <BaseIcon name="PencilIcon" class="h-5 mr-2" />
          {{ $t('general.edit') }}
        </BaseButton>

        <iframe
          :src="templateUrl"
          frameborder="0"
          class="w-full"
          style="min-height: 500px"
        ></iframe>
      </div>
      <div
        class="z-0 flex justify-end p-4 border-t border-gray-200 border-solid"
      >
        <BaseButton
          class="mr-3"
          variant="primary-outline"
          type="button"
          @click="closeSendProformaInvoiceModal"
        >
          {{ $t('general.cancel') }}
        </BaseButton>

        <BaseButton
          :loading="isLoading"
          :disabled="isLoading"
          variant="primary"
          type="button"
          @click="submitForm()"
        >
          <BaseIcon
            v-if="!isLoading"
            name="PaperAirplaneIcon"
            class="h-5 mr-2"
          />
          {{ $t('general.send') }}
        </BaseButton>
      </div>
    </div>
  </BaseModal>
</template>

<script setup>
import { ref, computed, reactive, onMounted } from 'vue'
import { useModalStore } from '@/scripts/stores/modal'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useI18n } from 'vue-i18n'
import { useProformaInvoiceStore } from '@/scripts/admin/stores/proforma-invoice'
import { useVuelidate } from '@vuelidate/core'
import { required, email, helpers } from '@vuelidate/validators'
import { useMailDriverStore } from '@/scripts/admin/stores/mail-driver'

const modalStore = useModalStore()
const companyStore = useCompanyStore()
const notificationStore = useNotificationStore()
const proformaInvoiceStore = useProformaInvoiceStore()
const mailDriverStore = useMailDriverStore()

const { t } = useI18n()
let isLoading = ref(false)
const templateUrl = ref('')
const isPreview = ref(false)

const emit = defineEmits(['update'])

const proformaInvoiceMailFields = ref([
  'customer',
  'customerCustom',
  'proformaInvoice',
  'company',
])

const proformaInvoiceMailForm = reactive({
  id: null,
  from: null,
  to: null,
  subject: t('proforma_invoices.new_proforma_invoice'),
  body: null,
})

const modalActive = computed(() => {
  return modalStore.active && modalStore.componentName === 'SendProformaInvoiceModal'
})

const modalTitle = computed(() => {
  return modalStore.title
})

const modalData = computed(() => {
  return modalStore.data
})

const rules = {
  from: {
    required: helpers.withMessage(t('validation.required'), required),
    email: helpers.withMessage(t('validation.email_incorrect'), email),
  },
  to: {
    required: helpers.withMessage(t('validation.required'), required),
    email: helpers.withMessage(t('validation.email_incorrect'), email),
  },
  subject: {
    required: helpers.withMessage(t('validation.required'), required),
  },
  body: {
    required: helpers.withMessage(t('validation.required'), required),
  },
}

const v$ = useVuelidate(
  rules,
  computed(() => proformaInvoiceMailForm)
)

function cancelPreview() {
  isPreview.value = false
}

async function setInitialData() {
  let admin = await companyStore.fetchBasicMailConfig()

  proformaInvoiceMailForm.id = modalStore.id

  if (admin.data) {
    proformaInvoiceMailForm.from = admin.data.from_mail
  }

  if (modalData.value) {
    proformaInvoiceMailForm.to = modalData.value.customer?.email
  }

  proformaInvoiceMailForm.body = companyStore.selectedCompanySettings.estimate_mail_body || 'Please find attached the proforma invoice.'
}

async function submitForm() {
  v$.value.$touch()

  if (v$.value.$invalid) {
    return true
  }

  try {
    isLoading.value = true

    if (!isPreview.value) {
      const previewResponse = await proformaInvoiceStore.previewProformaInvoice(proformaInvoiceMailForm)
      isLoading.value = false

      isPreview.value = true
      var blob = new Blob([previewResponse.data], { type: 'text/html' })
      templateUrl.value = URL.createObjectURL(blob)

      return
    }

    const response = await proformaInvoiceStore.sendProformaInvoice(proformaInvoiceMailForm)

    isLoading.value = false

    if (response.data.success) {
      emit('update', modalStore.id)
      closeSendProformaInvoiceModal()
      return true
    }
  } catch (error) {
    console.error(error)
    isLoading.value = false
    notificationStore.showNotification({
      type: 'error',
      message: t('proforma_invoices.something_went_wrong'),
    })
  }
}

function closeSendProformaInvoiceModal() {
  modalStore.closeModal()
  setTimeout(() => {
    v$.value.$reset()
    isPreview.value = false
    templateUrl.value = null
  }, 300)
}
</script>
