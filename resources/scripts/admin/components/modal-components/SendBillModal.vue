<template>
  <BaseModal
    :show="modalActive"
    @close="closeSendBillModal"
    @open="setInitialData"
  >
    <template #header>
      <div class="flex justify-between w-full">
        {{ modalTitle }}
        <BaseIcon
          name="XMarkIcon"
          class="w-6 h-6 text-gray-500 cursor-pointer"
          @click="closeSendBillModal"
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
              v-model="billMailForm.from"
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
              v-model="billMailForm.to"
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
              v-model="billMailForm.subject"
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
              v-model="billMailForm.body"
              :fields="billMailFields"
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
          @click="closeSendBillModal"
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
          @click="closeSendBillModal"
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
import { useBillsStore } from '@/scripts/admin/stores/bill'
import { useVuelidate } from '@vuelidate/core'
import { required, email, helpers } from '@vuelidate/validators'
import { useMailDriverStore } from '@/scripts/admin/stores/mail-driver'

const modalStore = useModalStore()
const companyStore = useCompanyStore()
const notificationStore = useNotificationStore()
const billsStore = useBillsStore()
const mailDriverStore = useMailDriverStore()

const { t } = useI18n()
let isLoading = ref(false)
const templateUrl = ref('')
const isPreview = ref(false)

const emit = defineEmits(['update'])

const billMailFields = ref([
  'supplier',
  'supplierCustom',
  'bill',
  'billCustom',
  'company',
])

const billMailForm = reactive({
  id: null,
  from: null,
  to: null,
  subject: t('bills.new_bill'),
  body: null,
})

const modalActive = computed(() => {
  return modalStore.active && modalStore.componentName === 'SendBillModal'
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
  computed(() => billMailForm)
)

function cancelPreview() {
  isPreview.value = false
}

async function setInitialData() {
  let admin = await companyStore.fetchBasicMailConfig()

  billMailForm.id = modalStore.id

  if (admin.data) {
    billMailForm.from = admin.data.from_mail
  }

  if (modalData.value) {
    billMailForm.to = modalData.value.supplier.email
  }

  billMailForm.body = companyStore.selectedCompanySettings.bill_mail_body
}

async function submitForm() {
  v$.value.$touch()

  if (v$.value.$invalid) {
    return true
  }

  try {
    isLoading.value = true

    if (!isPreview.value) {
      const previewResponse = await billsStore.previewBill(billMailForm)
      isLoading.value = false

      isPreview.value = true
      var blob = new Blob([previewResponse.data], { type: 'text/html' })
      templateUrl.value = URL.createObjectURL(blob)

      return
    }

    const response = await billsStore.sendBill(billMailForm)

    isLoading.value = false

    if (response.data.success) {
      emit('update', modalStore.id)
      closeSendBillModal()
      return true
    }
  } catch (error) {
    console.error(error)
    isLoading.value = false
    notificationStore.showNotification({
      type: 'error',
      message: t('bills.something_went_wrong'),
    })
  }
}

function closeSendBillModal() {
  modalStore.closeModal()
  setTimeout(() => {
    v$.value.$reset()
    isPreview.value = false
    templateUrl.value = null
  }, 300)
}
// CLAUDE-CHECKPOINT
</script>
