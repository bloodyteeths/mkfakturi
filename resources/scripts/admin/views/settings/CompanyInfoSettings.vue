<template>
  <form @submit.prevent="updateCompanyData">
    <BaseSettingCard
      :title="$t('settings.company_info.company_info')"
      :description="$t('settings.company_info.section_description')"
    >
      <BaseInputGrid class="mt-5">
        <BaseInputGroup :label="$t('settings.company_info.company_logo')">
          <BaseFileUploader
            v-model="previewLogo"
            base64
            @change="onFileInputChange"
            @remove="onFileInputRemove"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('settings.company_info.company_stamp')">
          <BaseFileUploader
            v-model="previewStamp"
            base64
            @change="onStampInputChange"
            @remove="onStampInputRemove"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('settings.company_info.company_signature')">
          <BaseFileUploader
            v-model="previewSignature"
            base64
            @change="onSignatureInputChange"
            @remove="onSignatureInputRemove"
          />
        </BaseInputGroup>
      </BaseInputGrid>

      <BaseInputGrid class="mt-5">
        <BaseInputGroup
          :label="$t('settings.company_info.company_name')"
          :error="v$.name.$error && v$.name.$errors[0].$message"
          required
        >
          <BaseInput
            v-model="companyForm.name"
            :invalid="v$.name.$error"
            @blur="v$.name.$touch()"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('settings.company_info.phone')">
          <BaseInput v-model="companyForm.address.phone" />
        </BaseInputGroup>

        <BaseInputGroup
          :label="$t('settings.company_info.country')"
          :error="
            v$.address.country_id.$error &&
            v$.address.country_id.$errors[0].$message
          "
          required
        >
          <BaseMultiselect
            v-model="companyForm.address.country_id"
            label="name"
            :invalid="v$.address.country_id.$error"
            :options="globalStore.countries"
            value-prop="id"
            :can-deselect="true"
            :can-clear="false"
            searchable
            track-by="name"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('settings.company_info.state')">
          <BaseInput
            v-model="companyForm.address.state"
            name="state"
            type="text"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('settings.company_info.city')">
          <BaseInput v-model="companyForm.address.city" type="text" />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('settings.company_info.zip')">
          <BaseInput v-model="companyForm.address.zip" />
        </BaseInputGroup>

        <div>
          <BaseInputGroup :label="$t('settings.company_info.address')">
            <BaseTextarea
              v-model="companyForm.address.address_street_1"
              rows="2"
            />
          </BaseInputGroup>

          <BaseTextarea
            v-model="companyForm.address.address_street_2"
            rows="2"
            :row="2"
            class="mt-2"
          />
        </div>

        <div class="space-y-6">
          <BaseInputGroup :label="$t('settings.company_info.tax_id')">
            <BaseInput v-model="companyForm.tax_id" type="text" />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('settings.company_info.vat_id')">
            <BaseInput v-model="companyForm.vat_id" type="text" />
          </BaseInputGroup>

          <BaseInputGroup label="ЕМБС (Матичен број)">
            <BaseInput v-model="companyForm.registration_number" type="text" />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('settings.company_info.bank_account')">
            <BaseInput
              v-model="companyForm.bank_account"
              type="text"
              placeholder="e.g. 300000000123456"
            />
          </BaseInputGroup>
        </div>
      </BaseInputGrid>

      <BaseButton
        :loading="isSaving"
        :disabled="isSaving"
        type="submit"
        class="mt-6"
      >
        <template #left="slotProps">
          <BaseIcon v-if="!isSaving" :class="slotProps.class" name="ArrowDownOnSquareIcon" />
        </template>
        {{ $t('settings.company_info.save') }}
      </BaseButton>

      <div v-if="companyStore.companies.length !== 1" class="py-5">
        <BaseDivider class="my-4" />
        <h3 class="text-lg leading-6 font-medium text-gray-900">
          {{ $t('settings.company_info.delete_company') }}
        </h3>
        <div class="mt-2 max-w-xl text-sm text-gray-500">
          <p>
            {{ $t('settings.company_info.delete_company_description') }}
          </p>
        </div>
        <div class="mt-5">
          <button
            type="button"
            class="inline-flex items-center justify-center px-4 py-2 border border-transparent font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:text-sm"
            @click="removeCompany"
          >
            {{ $t('general.delete') }}
          </button>
        </div>
      </div>
    </BaseSettingCard>
  </form>
  <DeleteCompanyModal />
</template>

<script setup>
import { reactive, ref, inject, computed } from 'vue'
import { useGlobalStore } from '@/scripts/admin/stores/global'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useI18n } from 'vue-i18n'
import { required, minLength, helpers } from '@vuelidate/validators'
import { useVuelidate } from '@vuelidate/core'
import { useModalStore } from '@/scripts/stores/modal'
import DeleteCompanyModal from '@/scripts/admin/components/modal-components/DeleteCompanyModal.vue'

const companyStore = useCompanyStore()
const globalStore = useGlobalStore()
const modalStore = useModalStore()
const { t } = useI18n()
const utils = inject('utils')

let isSaving = ref(false)

const companyForm = reactive({
  name: null,
  logo: null,
  tax_id: null,
  vat_id: null,
  registration_number: null,
  bank_account: null,
  address: {
    address_street_1: '',
    address_street_2: '',
    website: '',
    country_id: null,
    state: '',
    city: '',
    phone: '',
    zip: '',
  },
})

utils.mergeSettings(companyForm, {
  ...companyStore.selectedCompany,
})

let previewLogo = ref([])
let logoFileBlob = ref(null)
let logoFileName = ref(null)
const isCompanyLogoRemoved = ref(false)

let previewStamp = ref([])
let stampFileBlob = ref(null)
let stampFileName = ref(null)
const isCompanyStampRemoved = ref(false)

let previewSignature = ref([])
let signatureFileBlob = ref(null)
let signatureFileName = ref(null)
const isCompanySignatureRemoved = ref(false)

if (companyForm.logo) {
  previewLogo.value.push({
    image: companyForm.logo,
  })
}

if (companyStore.selectedCompany?.stamp) {
  previewStamp.value.push({
    image: companyStore.selectedCompany.stamp,
  })
}

if (companyStore.selectedCompany?.signature) {
  previewSignature.value.push({
    image: companyStore.selectedCompany.signature,
  })
}

const rules = computed(() => {
  return {
    name: {
      required: helpers.withMessage(t('validation.required'), required),
      minLength: helpers.withMessage(
        t('validation.name_min_length'),
        minLength(3),
      ),
    },
    address: {
      country_id: {
        required: helpers.withMessage(t('validation.required'), required),
      },
    },
  }
})

const v$ = useVuelidate(
  rules,
  computed(() => companyForm),
)

globalStore.fetchCountries()

function onFileInputChange(fileName, file, fileCount, fileList) {
  logoFileName.value = fileList.name
  logoFileBlob.value = file
}

function onFileInputRemove() {
  logoFileBlob.value = null
  isCompanyLogoRemoved.value = true
}

function onStampInputChange(fileName, file, fileCount, fileList) {
  stampFileName.value = fileList.name
  stampFileBlob.value = file
}

function onStampInputRemove() {
  stampFileBlob.value = null
  isCompanyStampRemoved.value = true
}

function onSignatureInputChange(fileName, file, fileCount, fileList) {
  signatureFileName.value = fileList.name
  signatureFileBlob.value = file
}

function onSignatureInputRemove() {
  signatureFileBlob.value = null
  isCompanySignatureRemoved.value = true
}

async function updateCompanyData() {
  v$.value.$touch()

  if (v$.value.$invalid) {
    return true
  }

  isSaving.value = true

  const res = await companyStore.updateCompany(companyForm)

  if (res.data.data) {
    if (logoFileBlob.value || isCompanyLogoRemoved.value) {
      let logoData = new FormData()

      if (logoFileBlob.value) {
        logoData.append('company_logo', JSON.stringify({ data: logoFileBlob.value, name: logoFileName.value }))
      }
      logoData.append('is_company_logo_removed', isCompanyLogoRemoved.value)

      await companyStore.updateCompanyLogo(logoData)
      logoFileBlob.value = null
      isCompanyLogoRemoved.value = false
    }

    if (stampFileBlob.value || isCompanyStampRemoved.value) {
      try {
        let stampData = new FormData()

        if (stampFileBlob.value) {
          stampData.append('company_stamp', JSON.stringify({ data: stampFileBlob.value, name: stampFileName.value }))
        }
        stampData.append('is_company_stamp_removed', isCompanyStampRemoved.value)

        await companyStore.updateCompanyStamp(stampData)
        stampFileBlob.value = null
        isCompanyStampRemoved.value = false
      } catch (err) {
        console.error('Stamp upload failed:', err?.response?.data || err)
      }
    }

    if (signatureFileBlob.value || isCompanySignatureRemoved.value) {
      try {
        let signatureData = new FormData()

        if (signatureFileBlob.value) {
          signatureData.append('company_signature', JSON.stringify({ data: signatureFileBlob.value, name: signatureFileName.value }))
        }
        signatureData.append('is_company_signature_removed', isCompanySignatureRemoved.value)

        await companyStore.updateCompanySignature(signatureData)
        signatureFileBlob.value = null
        isCompanySignatureRemoved.value = false
      } catch (err) {
        console.error('Signature upload failed:', err?.response?.data || err)
      }
    }

    isSaving.value = false
  } else {
    isSaving.value = false
  }
}
function removeCompany(id) {
  modalStore.openModal({
    title: t('settings.company_info.are_you_absolutely_sure'),
    componentName: 'DeleteCompanyModal',
    size: 'sm',
  })
}
// CLAUDE-CHECKPOINT
</script>
