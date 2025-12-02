<template>
  <BasePage>
    <form @submit.prevent="submitPartnerData">
      <BasePageHeader :title="pageTitle">
        <BaseBreadcrumb>
          <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
          <BaseBreadcrumbItem :title="$t('partners.title', 2)" to="/admin/partners" />
          <BaseBreadcrumbItem :title="pageTitle" to="#" active />
        </BaseBreadcrumb>

        <template #actions>
          <div class="flex items-center justify-end space-x-3">
            <BaseButton
              variant="primary-outline"
              type="button"
              @click="$router.push('/admin/partners')"
            >
              {{ $t('general.cancel') }}
            </BaseButton>
            <BaseButton type="submit" :loading="isSaving" :disabled="isSaving">
              <template #left="slotProps">
                <BaseIcon name="CheckIcon" :class="slotProps.class" />
              </template>
              {{ isEdit ? $t('partners.update_partner') : $t('partners.save_partner') }}
            </BaseButton>
          </div>
        </template>
      </BasePageHeader>

      <BaseCard class="mt-5">
        <!-- Basic Info -->
        <div class="grid grid-cols-5 gap-4 mb-8">
          <h6 class="col-span-5 text-lg font-semibold text-left lg:col-span-1">
            {{ $t('partners.basic_info') }}
          </h6>

          <BaseInputGrid class="col-span-5 lg:col-span-4">
            <BaseInputGroup
              :label="$t('partners.name')"
              required
              :error="v$.partnerForm.name.$error && v$.partnerForm.name.$errors[0].$message"
            >
              <BaseInput
                v-model="partnerForm.name"
                type="text"
                name="name"
                :invalid="v$.partnerForm.name.$error"
                @input="v$.partnerForm.name.$touch()"
              />
            </BaseInputGroup>

            <BaseInputGroup
              :label="$t('partners.email')"
              required
              :error="v$.partnerForm.email.$error && v$.partnerForm.email.$errors[0].$message"
            >
              <BaseInput
                v-model="partnerForm.email"
                type="email"
                name="email"
                :invalid="v$.partnerForm.email.$error"
                @input="v$.partnerForm.email.$touch()"
              />
            </BaseInputGroup>

            <BaseInputGroup :label="$t('partners.phone')">
              <BaseInput
                v-model="partnerForm.phone"
                type="text"
                name="phone"
              />
            </BaseInputGroup>

            <BaseInputGroup :label="$t('partners.company_name')">
              <BaseInput
                v-model="partnerForm.company_name"
                type="text"
                name="company_name"
              />
            </BaseInputGroup>

            <BaseInputGroup :label="$t('partners.tax_id')">
              <BaseInput
                v-model="partnerForm.tax_id"
                type="text"
                name="tax_id"
              />
            </BaseInputGroup>

            <BaseInputGroup :label="$t('partners.registration_number')">
              <BaseInput
                v-model="partnerForm.registration_number"
                type="text"
                name="registration_number"
              />
            </BaseInputGroup>
          </BaseInputGrid>
        </div>

        <!-- Banking Info -->
        <div class="grid grid-cols-5 gap-4 mb-8">
          <h6 class="col-span-5 text-lg font-semibold text-left lg:col-span-1">
            {{ $t('partners.banking_info') }}
          </h6>

          <BaseInputGrid class="col-span-5 lg:col-span-4">
            <BaseInputGroup :label="$t('partners.bank_account')">
              <BaseInput
                v-model="partnerForm.bank_account"
                type="text"
                name="bank_account"
              />
            </BaseInputGroup>

            <BaseInputGroup :label="$t('partners.bank_name')">
              <BaseInput
                v-model="partnerForm.bank_name"
                type="text"
                name="bank_name"
              />
            </BaseInputGroup>
          </BaseInputGrid>
        </div>

        <!-- Commission & Status -->
        <div class="grid grid-cols-5 gap-4 mb-8">
          <h6 class="col-span-5 text-lg font-semibold text-left lg:col-span-1">
            {{ $t('partners.commission_status') }}
          </h6>

          <BaseInputGrid class="col-span-5 lg:col-span-4">
            <BaseInputGroup
              :label="$t('partners.commission_rate')"
              :helper-text="$t('partners.commission_rate_help')"
            >
              <BaseInput
                v-model="partnerForm.commission_rate"
                type="number"
                name="commission_rate"
                step="0.01"
                min="0"
                max="100"
              >
                <template #right>
                  <span class="text-gray-500">%</span>
                </template>
              </BaseInput>
            </BaseInputGroup>

            <BaseInputGroup :label="$t('partners.kyc_status')" required>
              <BaseSelect v-model="partnerForm.kyc_status">
                <option value="pending">{{ $t('partners.kyc.pending') }}</option>
                <option value="under_review">{{ $t('partners.kyc.under_review') }}</option>
                <option value="approved">{{ $t('partners.kyc.approved') }}</option>
                <option value="rejected">{{ $t('partners.kyc.rejected') }}</option>
              </BaseSelect>
            </BaseInputGroup>

            <BaseInputGroup :label="$t('general.status')">
              <BaseSwitch
                v-model="partnerForm.is_active"
                :label="partnerForm.is_active ? $t('general.active') : $t('general.inactive')"
              />
            </BaseInputGroup>
          </BaseInputGrid>
        </div>

        <!-- Password (only for new partners) -->
        <div v-if="!isEdit" class="grid grid-cols-5 gap-4 mb-8">
          <h6 class="col-span-5 text-lg font-semibold text-left lg:col-span-1">
            {{ $t('partners.credentials') }}
          </h6>

          <BaseInputGrid class="col-span-5 lg:col-span-4">
            <BaseInputGroup
              :label="$t('partners.password')"
              required
              :error="v$.partnerForm.password.$error && v$.partnerForm.password.$errors[0].$message"
            >
              <BaseInput
                v-model="partnerForm.password"
                type="password"
                name="password"
                autocomplete="new-password"
                :invalid="v$.partnerForm.password.$error"
                @input="v$.partnerForm.password.$touch()"
              />
            </BaseInputGroup>

            <BaseInputGroup
              :label="$t('partners.password_confirmation')"
              required
              :error="v$.partnerForm.password_confirmation.$error && v$.partnerForm.password_confirmation.$errors[0].$message"
            >
              <BaseInput
                v-model="partnerForm.password_confirmation"
                type="password"
                name="password_confirmation"
                autocomplete="new-password"
                :invalid="v$.partnerForm.password_confirmation.$error"
                @input="v$.partnerForm.password_confirmation.$touch()"
              />
            </BaseInputGroup>
          </BaseInputGrid>
        </div>

        <!-- Notes -->
        <div class="grid grid-cols-5 gap-4">
          <h6 class="col-span-5 text-lg font-semibold text-left lg:col-span-1">
            {{ $t('partners.notes') }}
          </h6>

          <BaseInputGrid class="col-span-5 lg:col-span-4">
            <BaseInputGroup :label="$t('partners.internal_notes')">
              <BaseTextarea
                v-model="partnerForm.notes"
                rows="4"
                :placeholder="$t('partners.notes_placeholder')"
              />
            </BaseInputGroup>
          </BaseInputGrid>
        </div>
      </BaseCard>
    </form>
  </BasePage>
</template>

<script setup>
import { ref, computed, reactive, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useVuelidate } from '@vuelidate/core'
import { required, email, minLength, sameAs, helpers } from '@vuelidate/validators'
import axios from 'axios'
import { useNotificationStore } from '@/scripts/stores/notification'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const notificationStore = useNotificationStore()

const isSaving = ref(false)
const isEdit = computed(() => !!route.params.id)
const pageTitle = computed(() => isEdit.value ? t('partners.edit_partner') : t('partners.new_partner'))

const partnerForm = reactive({
  name: '',
  email: '',
  phone: '',
  company_name: '',
  tax_id: '',
  registration_number: '',
  bank_account: '',
  bank_name: '',
  commission_rate: 20,
  is_active: true,
  kyc_status: 'pending',
  notes: '',
  password: '',
  password_confirmation: '',
})

const rules = computed(() => {
  const baseRules = {
    partnerForm: {
      name: {
        required: helpers.withMessage(t('validation.required'), required),
      },
      email: {
        required: helpers.withMessage(t('validation.required'), required),
        email: helpers.withMessage(t('validation.email_incorrect'), email),
      },
    },
  }

  if (!isEdit.value) {
    baseRules.partnerForm.password = {
      required: helpers.withMessage(t('validation.required'), required),
      minLength: helpers.withMessage(t('validation.password_min_length', { min: 8 }), minLength(8)),
    }
    baseRules.partnerForm.password_confirmation = {
      required: helpers.withMessage(t('validation.required'), required),
      sameAsPassword: helpers.withMessage(
        t('validation.password_confirmation_match'),
        sameAs(partnerForm.password)
      ),
    }
  }

  return baseRules
})

const v$ = useVuelidate(rules, { partnerForm })

async function fetchPartner() {
  if (!isEdit.value) return

  try {
    const response = await axios.get(`/partners/${route.params.id}`)
    const partner = response.data

    Object.keys(partnerForm).forEach((key) => {
      if (partner[key] !== undefined) {
        partnerForm[key] = partner[key]
      }
    })
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: t('partners.fetch_failed'),
    })
    router.push('/admin/partners')
  }
}

async function submitPartnerData() {
  v$.value.$touch()

  if (v$.value.$invalid) {
    return
  }

  isSaving.value = true

  try {
    const data = { ...partnerForm }

    // Remove password fields if editing
    if (isEdit.value) {
      delete data.password
      delete data.password_confirmation
    }

    if (isEdit.value) {
      await axios.put(`/partners/${route.params.id}`, data)
      notificationStore.showNotification({
        type: 'success',
        message: t('partners.updated_successfully'),
      })
    } else {
      await axios.post('/partners', data)
      notificationStore.showNotification({
        type: 'success',
        message: t('partners.created_successfully'),
      })
    }

    router.push('/admin/partners')
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('partners.save_failed'),
    })
  } finally {
    isSaving.value = false
  }
}

onMounted(() => {
  fetchPartner()
})
</script>

<!-- CLAUDE-CHECKPOINT -->
