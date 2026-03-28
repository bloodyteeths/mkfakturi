<template>
  <form @submit.prevent="saveSettings">
    <BaseSettingCard
      :title="t('pos_settings.title')"
      :description="t('pos_settings.title')"
    >
      <BaseInputGrid>
        <!-- Default Payment Method -->
        <BaseInputGroup :label="t('pos_settings.default_payment')">
          <BaseMultiselect
            v-model="form.pos_default_payment"
            :options="paymentOptions"
            label="label"
            track-by="value"
            :searchable="false"
          />
        </BaseInputGroup>

        <!-- Invoice Prefix -->
        <BaseInputGroup :label="t('pos_settings.invoice_prefix')">
          <BaseInput
            v-model="form.pos_invoice_prefix"
            placeholder="POS-"
          />
        </BaseInputGroup>
      </BaseInputGrid>

      <!-- Show VAT toggle -->
      <BaseSwitchSection
        v-model="showVatToggle"
        :title="t('pos_settings.show_vat')"
        :description="t('pos_settings.show_vat')"
        class="mt-6"
      />

      <!-- Auto-print toggle -->
      <BaseSwitchSection
        v-model="autoPrintToggle"
        :title="t('pos_settings.auto_print')"
        :description="t('pos_settings.auto_print')"
        class="mt-4"
      />

      <BaseButton
        type="submit"
        :loading="isSaving"
        :disabled="isSaving"
        class="mt-6"
      >
        {{ t('general.save') }}
      </BaseButton>
    </BaseSettingCard>
  </form>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useNotificationStore } from '@/scripts/stores/notification'

const { t } = useI18n()
const companyStore = useCompanyStore()
const notificationStore = useNotificationStore()

const isSaving = ref(false)

const form = reactive({
  pos_default_payment: { label: 'Cash', value: 'cash' },
  pos_invoice_prefix: 'POS-',
  pos_show_vat: 'YES',
  pos_auto_print: 'NO',
})

const paymentOptions = [
  { label: t('pos.cash'), value: 'cash' },
  { label: t('pos.card'), value: 'card' },
]

const showVatToggle = computed({
  get: () => form.pos_show_vat === 'YES',
  set: (val) => { form.pos_show_vat = val ? 'YES' : 'NO' },
})

const autoPrintToggle = computed({
  get: () => form.pos_auto_print === 'YES',
  set: (val) => { form.pos_auto_print = val ? 'YES' : 'NO' },
})

async function saveSettings() {
  isSaving.value = true
  try {
    await companyStore.updateCompanySettings({
      data: {
        settings: {
          pos_default_payment: form.pos_default_payment?.value || 'cash',
          pos_invoice_prefix: form.pos_invoice_prefix || 'POS-',
          pos_show_vat: form.pos_show_vat,
          pos_auto_print: form.pos_auto_print,
        },
      },
      message: 'general.setting_updated',
    })
  } catch (e) {
    notificationStore.showNotification({
      type: 'error',
      message: e.message || 'Failed to save settings',
    })
  } finally {
    isSaving.value = false
  }
}

onMounted(() => {
  const company = companyStore.selectedCompany
  if (company?.settings) {
    const s = company.settings
    if (s.pos_default_payment) {
      const opt = paymentOptions.find(o => o.value === s.pos_default_payment)
      if (opt) form.pos_default_payment = opt
    }
    if (s.pos_invoice_prefix) form.pos_invoice_prefix = s.pos_invoice_prefix
    if (s.pos_show_vat) form.pos_show_vat = s.pos_show_vat
    if (s.pos_auto_print) form.pos_auto_print = s.pos_auto_print
  }
})
</script>

<!-- CLAUDE-CHECKPOINT -->
