<template>
  <form @submit.prevent="saveSettings">
    <!-- Section 1: General -->
    <BaseSettingCard
      :title="t('pos_settings.title')"
      :description="t('pos_settings.general_desc')"
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
        :description="t('pos_settings.show_vat_desc')"
        class="mt-6"
      />

      <!-- Auto-print toggle -->
      <BaseSwitchSection
        v-model="autoPrintToggle"
        :title="t('pos_settings.auto_print')"
        :description="t('pos_settings.auto_print_desc')"
        class="mt-4"
      />

      <!-- Sound toggle -->
      <BaseSwitchSection
        v-model="soundToggle"
        :title="t('pos_settings.sound')"
        :description="t('pos_settings.sound_desc')"
        class="mt-4"
      />
    </BaseSettingCard>

    <!-- Section 2: Touch & Input -->
    <BaseSettingCard
      :title="t('pos_settings.touch_input')"
      :description="t('pos_settings.touch_input_desc')"
      class="mt-6"
    >
      <!-- On-screen numpad -->
      <BaseSwitchSection
        v-model="numpadToggle"
        :title="t('pos_settings.numpad')"
        :description="t('pos_settings.numpad_desc')"
      />

      <!-- Camera barcode scanner -->
      <BaseSwitchSection
        v-model="barcodeCameraToggle"
        :title="t('pos_settings.barcode_camera')"
        :description="t('pos_settings.barcode_camera_desc')"
        class="mt-4"
      />
    </BaseSettingCard>

    <!-- Section 3: Payments -->
    <BaseSettingCard
      :title="t('pos_settings.payments')"
      :description="t('pos_settings.payments_desc')"
      class="mt-6"
    >
      <!-- Split payment -->
      <BaseSwitchSection
        v-model="splitPaymentToggle"
        :title="t('pos_settings.split_payment')"
        :description="t('pos_settings.split_payment_desc')"
      />

      <!-- CASYS QR -->
      <BaseSwitchSection
        v-model="casysQrToggle"
        :title="t('pos_settings.casys_qr')"
        :description="t('pos_settings.casys_qr_desc')"
        class="mt-4"
      />
    </BaseSettingCard>

    <!-- Section 4: Restaurant -->
    <BaseSettingCard
      :title="t('pos_settings.restaurant')"
      :description="t('pos_settings.restaurant_desc')"
      class="mt-6"
    >
      <!-- Restaurant mode toggle -->
      <BaseSwitchSection
        v-model="restaurantToggle"
        :title="t('pos_settings.restaurant_mode')"
        :description="t('pos_settings.restaurant_mode_desc')"
      />

      <!-- Restaurant sub-settings (only when restaurant mode is ON) -->
      <template v-if="restaurantToggle">
        <BaseInputGrid class="mt-4">
          <BaseInputGroup :label="t('pos_settings.table_count')">
            <BaseInput
              v-model="form.pos_table_count"
              type="number"
              min="1"
              max="200"
              placeholder="20"
            />
          </BaseInputGroup>
        </BaseInputGrid>

        <!-- Kitchen printing -->
        <BaseSwitchSection
          v-model="kitchenPrintingToggle"
          :title="t('pos_settings.kitchen_printing')"
          :description="t('pos_settings.kitchen_printing_desc')"
          class="mt-4"
        />
      </template>
    </BaseSettingCard>

    <!-- Section 5: Advanced -->
    <BaseSettingCard
      :title="t('pos_settings.advanced')"
      :description="t('pos_settings.advanced_desc')"
      class="mt-6"
    >
      <!-- Returns -->
      <BaseSwitchSection
        v-model="returnToggle"
        :title="t('pos_settings.return_enabled')"
        :description="t('pos_settings.return_enabled_desc')"
      />

      <!-- Customer display -->
      <BaseSwitchSection
        v-model="customerDisplayToggle"
        :title="t('pos_settings.customer_display')"
        :description="t('pos_settings.customer_display_desc')"
        class="mt-4"
      />
    </BaseSettingCard>

    <!-- Save Button -->
    <div class="mt-6">
      <BaseButton
        type="submit"
        :loading="isSaving"
        :disabled="isSaving"
      >
        {{ t('general.save') }}
      </BaseButton>
    </div>
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
  pos_sound_enabled: 'YES',
  pos_numpad_enabled: 'YES',
  pos_barcode_camera: 'NO',
  pos_split_payment: 'NO',
  pos_casys_qr: 'NO',
  pos_restaurant_mode: 'NO',
  pos_table_count: '20',
  pos_kitchen_printing: 'NO',
  pos_return_enabled: 'NO',
  pos_customer_display: 'NO',
})

const paymentOptions = [
  { label: t('pos.cash'), value: 'cash' },
  { label: t('pos.card'), value: 'card' },
]

// Toggle helpers (YES/NO ↔ boolean)
function makeToggle(key) {
  return computed({
    get: () => form[key] === 'YES',
    set: (val) => { form[key] = val ? 'YES' : 'NO' },
  })
}

const showVatToggle = makeToggle('pos_show_vat')
const autoPrintToggle = makeToggle('pos_auto_print')
const soundToggle = makeToggle('pos_sound_enabled')
const numpadToggle = makeToggle('pos_numpad_enabled')
const barcodeCameraToggle = makeToggle('pos_barcode_camera')
const splitPaymentToggle = makeToggle('pos_split_payment')
const casysQrToggle = makeToggle('pos_casys_qr')
const restaurantToggle = makeToggle('pos_restaurant_mode')
const kitchenPrintingToggle = makeToggle('pos_kitchen_printing')
const returnToggle = makeToggle('pos_return_enabled')
const customerDisplayToggle = makeToggle('pos_customer_display')

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
          pos_sound_enabled: form.pos_sound_enabled,
          pos_numpad_enabled: form.pos_numpad_enabled,
          pos_barcode_camera: form.pos_barcode_camera,
          pos_split_payment: form.pos_split_payment,
          pos_casys_qr: form.pos_casys_qr,
          pos_restaurant_mode: form.pos_restaurant_mode,
          pos_table_count: form.pos_table_count,
          pos_kitchen_printing: form.pos_kitchen_printing,
          pos_return_enabled: form.pos_return_enabled,
          pos_customer_display: form.pos_customer_display,
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
    if (s.pos_sound_enabled) form.pos_sound_enabled = s.pos_sound_enabled
    if (s.pos_numpad_enabled) form.pos_numpad_enabled = s.pos_numpad_enabled
    if (s.pos_barcode_camera) form.pos_barcode_camera = s.pos_barcode_camera
    if (s.pos_split_payment) form.pos_split_payment = s.pos_split_payment
    if (s.pos_casys_qr) form.pos_casys_qr = s.pos_casys_qr
    if (s.pos_restaurant_mode) form.pos_restaurant_mode = s.pos_restaurant_mode
    if (s.pos_table_count) form.pos_table_count = s.pos_table_count
    if (s.pos_kitchen_printing) form.pos_kitchen_printing = s.pos_kitchen_printing
    if (s.pos_return_enabled) form.pos_return_enabled = s.pos_return_enabled
    if (s.pos_customer_display) form.pos_customer_display = s.pos_customer_display
  }
})
</script>

<!-- CLAUDE-CHECKPOINT -->
