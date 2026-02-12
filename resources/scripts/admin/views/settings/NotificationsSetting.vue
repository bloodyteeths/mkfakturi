<template>
  <BaseSettingCard
    :title="$t('settings.notification.title')"
    :description="$t('settings.notification.description')"
  >
    <form action="" @submit.prevent="submitForm">
      <div class="grid-cols-2 col-span-1 mt-14">
        <BaseInputGroup
          :error="
            v$.notification_email.$error &&
            v$.notification_email.$errors[0].$message
          "
          :label="$t('settings.notification.email')"
          class="my-2"
          required
        >
          <BaseInput
            v-model.trim="settingsForm.notification_email"
            :invalid="v$.notification_email.$error"
            type="email"
            @input="v$.notification_email.$touch()"
          />
        </BaseInputGroup>

        <BaseButton
          :disabled="isSaving"
          :loading="isSaving"
          variant="primary"
          type="submit"
          class="mt-6"
        >
          <template #left="slotProps">
            <BaseIcon
              v-if="!isSaving"
              :class="slotProps.class"
              name="ArrowDownOnSquareIcon"
            />
          </template>

          {{ $t('settings.notification.save') }}
        </BaseButton>
      </div>
    </form>

    <BaseDivider class="mt-6 mb-2" />

    <ul class="divide-y divide-gray-200">
      <BaseSwitchSection
        v-model="invoiceViewedField"
        :title="$t('settings.notification.invoice_viewed')"
        :description="$t('settings.notification.invoice_viewed_desc')"
      />

      <BaseSwitchSection
        v-model="estimateViewedField"
        :title="$t('settings.notification.estimate_viewed')"
        :description="$t('settings.notification.estimate_viewed_desc')"
      />
    </ul>

    <!-- Viber Notifications opt-in (visible when platform has Viber enabled) -->
    <template v-if="viberAvailable">
      <BaseDivider class="mt-6 mb-2" />

      <div class="mt-4">
        <h6 class="text-sm font-semibold text-gray-700 mb-1">
          Viber Notifications
        </h6>
        <p class="text-xs text-gray-500 mb-4">
          Receive invoice and payment notifications via Viber.
        </p>

        <BaseSwitchSection
          v-model="viberOptInField"
          title="Enable Viber Notifications"
          description="Receive Viber messages for invoice deliveries, payment confirmations, and overdue reminders."
        />

        <BaseInputGroup
          v-if="viberOptInField"
          label="Viber Phone Number"
          class="my-2 max-w-sm"
        >
          <BaseInput
            v-model="settingsForm.viber_phone"
            type="tel"
            placeholder="+389 7X XXX XXX"
          />
          <p class="mt-1 text-xs text-gray-500">
            The phone number registered with your Viber account.
          </p>
        </BaseInputGroup>

        <BaseButton
          v-if="viberOptInField"
          :disabled="isSavingViber"
          :loading="isSavingViber"
          variant="primary-outline"
          class="mt-2"
          @click="saveViberPreferences"
        >
          <template #left="slotProps">
            <BaseIcon
              v-if="!isSavingViber"
              :class="slotProps.class"
              name="ArrowDownOnSquareIcon"
            />
          </template>
          Save Viber Preferences
        </BaseButton>
      </div>
    </template>
  </BaseSettingCard>
</template>

<script setup>
import { ref, onMounted, computed, reactive } from 'vue'
import { useI18n } from 'vue-i18n'
import { required, email, helpers } from '@vuelidate/validators'
import useVuelidate from '@vuelidate/core'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useNotificationStore } from '@/scripts/stores/notification'
import axios from 'axios'

const companyStore = useCompanyStore()
const notificationStore = useNotificationStore()

let isSaving = ref(false)
let isSavingViber = ref(false)
const { t } = useI18n()

// Check if Viber is enabled platform-wide
const viberAvailable = ref(false)
checkViberAvailability()

async function checkViberAvailability() {
  try {
    const { data } = await axios.get('/api/v1/viber/availability')
    viberAvailable.value = data.available === true
  } catch (e) {
    viberAvailable.value = false
  }
}

const settingsForm = reactive({
  notify_invoice_viewed:
    companyStore.selectedCompanySettings.notify_invoice_viewed,
  notify_estimate_viewed:
    companyStore.selectedCompanySettings.notify_estimate_viewed,
  notification_email: companyStore.selectedCompanySettings.notification_email,
  viber_opt_in: companyStore.selectedCompanySettings.viber_opt_in || 'NO',
  viber_phone: companyStore.selectedCompanySettings.viber_phone || '',
})

const rules = computed(() => {
  return {
    notification_email: {
      required: helpers.withMessage(t('validation.required'), required),
      email: helpers.withMessage(t('validation.email_incorrect'), email),
    },
  }
})

const v$ = useVuelidate(
  rules,
  computed(() => settingsForm)
)

const invoiceViewedField = computed({
  get: () => {
    return settingsForm.notify_invoice_viewed === 'YES'
  },
  set: async (newValue) => {
    const value = newValue ? 'YES' : 'NO'

    let data = {
      settings: {
        notify_invoice_viewed: value,
      },
    }

    settingsForm.notify_invoice_viewed = value

    await companyStore.updateCompanySettings({
      data,
      message: 'general.setting_updated',
    })
  },
})

const estimateViewedField = computed({
  get: () => {
    return settingsForm.notify_estimate_viewed === 'YES'
  },
  set: async (newValue) => {
    const value = newValue ? 'YES' : 'NO'

    let data = {
      settings: {
        notify_estimate_viewed: value,
      },
    }

    settingsForm.notify_estimate_viewed = value

    await companyStore.updateCompanySettings({
      data,
      message: 'general.setting_updated',
    })
  },
})

const viberOptInField = computed({
  get: () => settingsForm.viber_opt_in === 'YES',
  set: async (newValue) => {
    const value = newValue ? 'YES' : 'NO'
    settingsForm.viber_opt_in = value

    await companyStore.updateCompanySettings({
      data: { settings: { viber_opt_in: value } },
      message: 'general.setting_updated',
    })
  },
})

async function saveViberPreferences() {
  isSavingViber.value = true

  await companyStore.updateCompanySettings({
    data: {
      settings: {
        viber_opt_in: settingsForm.viber_opt_in,
        viber_phone: settingsForm.viber_phone,
      },
    },
    message: 'general.setting_updated',
  })

  isSavingViber.value = false
}

async function submitForm() {
  v$.value.$touch()
  if (v$.value.$invalid) {
    return true
  }

  isSaving.value = true

  const data = {
    settings: {
      notification_email: settingsForm.notification_email,
    },
  }

  await companyStore.updateCompanySettings({
    data,
    message: 'settings.notification.email_save_message',
  })

  isSaving.value = false
}
</script>
