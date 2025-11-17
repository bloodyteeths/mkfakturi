<template>
  <BaseModal
    :show="modalActive"
    @close="closeModal"
  >
    <template #header>
      <div class="flex justify-between w-full">
        {{ modalTitle }}
        <BaseIcon
          name="XMarkIcon"
          class="w-6 h-6 text-gray-500 cursor-pointer"
          @click="closeModal"
        />
      </div>
    </template>

    <form @submit.prevent="submitForm">
      <div class="px-8 py-8 sm:p-6">
        <BaseInputGrid layout="one-column">
          <BaseInputGroup
            :label="$t('e_invoice.select_certificate')"
            required
            :error="v$.certificate_id.$error && v$.certificate_id.$errors[0].$message"
          >
            <BaseSelect
              v-model="signForm.certificate_id"
              :options="certificates"
              :invalid="v$.certificate_id.$error"
              :loading="loadingCertificates"
              :placeholder="$t('e_invoice.select_certificate')"
              label="name"
              value-prop="id"
              @input="v$.certificate_id.$touch()"
            >
              <template #selected-option="{ option }">
                <div v-if="option">
                  <div class="font-medium">{{ option.name }}</div>
                  <div class="text-xs text-gray-500">
                    {{ $t('certificates.expires') }}: {{ formatDate(option.expires_at) }}
                  </div>
                </div>
              </template>
              <template #option="{ option }">
                <div>
                  <div class="font-medium">{{ option.name }}</div>
                  <div class="text-xs text-gray-500">
                    {{ $t('certificates.expires') }}: {{ formatDate(option.expires_at) }}
                  </div>
                </div>
              </template>
            </BaseSelect>
          </BaseInputGroup>

          <BaseInputGroup
            :label="$t('e_invoice.enter_passphrase')"
            required
            :error="v$.passphrase.$error && v$.passphrase.$errors[0].$message"
          >
            <BaseInput
              v-model="signForm.passphrase"
              type="password"
              :invalid="v$.passphrase.$error"
              :placeholder="$t('e_invoice.enter_passphrase')"
              @input="v$.passphrase.$touch()"
            />
          </BaseInputGroup>

          <BaseAlert v-if="errorMessage" variant="danger" class="mt-4">
            {{ errorMessage }}
          </BaseAlert>
        </BaseInputGrid>
      </div>

      <div class="z-0 flex justify-end p-4 border-t border-gray-200 border-solid">
        <BaseButton
          class="mr-3"
          variant="primary-outline"
          type="button"
          @click="closeModal"
        >
          {{ $t('general.cancel') }}
        </BaseButton>

        <BaseButton
          :loading="isLoading"
          :disabled="isLoading || v$.$invalid"
          variant="primary"
          type="submit"
        >
          <BaseIcon
            v-if="!isLoading"
            name="ShieldCheckIcon"
            class="h-5 mr-2"
          />
          {{ $t('e_invoice.sign') }}
        </BaseButton>
      </div>
    </form>
  </BaseModal>
</template>

<script setup>
import { ref, computed, reactive, watch } from 'vue'
import { useModalStore } from '@/scripts/stores/modal'
import { useI18n } from 'vue-i18n'
import { useEInvoiceStore } from '@/scripts/admin/stores/e-invoice'
import { useVuelidate } from '@vuelidate/core'
import { required, helpers } from '@vuelidate/validators'
import axios from 'axios'
import moment from 'moment'

const modalStore = useModalStore()
const eInvoiceStore = useEInvoiceStore()

const { t } = useI18n()
const isLoading = ref(false)
const loadingCertificates = ref(false)
const certificates = ref([])
const errorMessage = ref('')

const emit = defineEmits(['signed'])

const signForm = reactive({
  certificate_id: null,
  passphrase: '',
})

const modalActive = computed(() => {
  return modalStore.active && modalStore.componentName === 'EInvoiceSignModal'
})

const modalTitle = computed(() => {
  return modalStore.title || t('e_invoice.sign')
})

const modalData = computed(() => {
  return modalStore.data
})

const eInvoiceId = computed(() => {
  return modalStore.id
})

const rules = {
  certificate_id: {
    required: helpers.withMessage(t('validation.required'), required),
  },
  passphrase: {
    required: helpers.withMessage(t('validation.required'), required),
  },
}

const v$ = useVuelidate(rules, signForm)

async function setInitialData() {
  errorMessage.value = ''
  signForm.certificate_id = null
  signForm.passphrase = ''
  v$.value.$reset()

  await loadCertificates()
}

async function loadCertificates() {
  loadingCertificates.value = true
  try {
    console.log('[EInvoiceSignModal] Loading certificates...')
    const response = await axios.get('/certificates/current')
    console.log('[EInvoiceSignModal] API Response:', response.data)

    certificates.value = response.data.data || []
    console.log('[EInvoiceSignModal] Certificates loaded:', certificates.value)
    console.log('[EInvoiceSignModal] Certificate count:', certificates.value.length)

    // Auto-select if only one certificate
    if (certificates.value.length === 1) {
      signForm.certificate_id = certificates.value[0].id
      console.log('[EInvoiceSignModal] Auto-selected certificate ID:', signForm.certificate_id)
    } else if (certificates.value.length === 0) {
      console.warn('[EInvoiceSignModal] No certificates found!')
      errorMessage.value = t('certificates.no_certificate')
    }
  } catch (error) {
    console.error('[EInvoiceSignModal] Failed to load certificates:', error)
    console.error('[EInvoiceSignModal] Error details:', error.response?.data)
    errorMessage.value = t('e_invoice.failed_to_load_certificates')
  } finally {
    loadingCertificates.value = false
  }
}

function formatDate(date) {
  return moment(date).format('DD.MM.YYYY')
}

async function submitForm() {
  v$.value.$touch()

  if (v$.value.$invalid) {
    return
  }

  isLoading.value = true
  errorMessage.value = ''

  try {
    await eInvoiceStore.signEInvoice(eInvoiceId.value, signForm.passphrase)
    emit('signed')
    closeModal()
  } catch (error) {
    errorMessage.value = error.response?.data?.message || t('e_invoice.signing_failed')
  } finally {
    isLoading.value = false
  }
}

function closeModal() {
  modalStore.closeModal()
  signForm.certificate_id = null
  signForm.passphrase = ''
  errorMessage.value = ''
  v$.value.$reset()
}

// Watch for modal opening and load certificates ONCE
watch(modalActive, (newValue, oldValue) => {
  if (newValue && !oldValue) {
    // Modal just opened
    console.log('[EInvoiceSignModal] Modal opened, loading initial data')
    setInitialData()
  }
})
</script>
// CLAUDE-CHECKPOINT
