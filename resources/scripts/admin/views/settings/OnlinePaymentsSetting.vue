<template>
  <form @submit.prevent="saveSettings">
    <!-- Main CASYS Setup -->
    <BaseSettingCard
      :title="t('online_payments.title')"
      :description="t('online_payments.description')"
    >
      <!-- Master toggle -->
      <BaseSwitchSection
        v-model="enabledToggle"
        :title="t('online_payments.enable_casys')"
        :description="t('online_payments.enable_casys_desc')"
      />

      <!-- Credentials (only when enabled) -->
      <template v-if="enabledToggle">
        <div class="mt-5 p-5 bg-gray-50 dark:bg-gray-800/50 rounded-xl ring-1 ring-gray-200 dark:ring-gray-700">
          <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">
            {{ t('online_payments.credentials_title') }}
          </h4>
          <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
            {{ t('online_payments.credentials_info') }}
          </p>
          <BaseInputGrid>
            <BaseInputGroup :label="t('online_payments.merchant_id')">
              <BaseInput
                v-model="form.casys_merchant_id"
                placeholder="e.g. 1234567890"
              />
            </BaseInputGroup>
            <BaseInputGroup :label="t('online_payments.merchant_name')">
              <BaseInput
                v-model="form.casys_merchant_name"
                :placeholder="t('online_payments.merchant_name_placeholder')"
              />
            </BaseInputGroup>
          </BaseInputGrid>
          <BaseInputGrid class="mt-3">
            <BaseInputGroup :label="t('online_payments.auth_key')">
              <BaseInput
                v-model="form.casys_auth_key"
                type="password"
                placeholder="••••••••"
              />
            </BaseInputGroup>
          </BaseInputGrid>

          <!-- Status indicator -->
          <div v-if="isConfigured" class="mt-4 flex items-center gap-2 text-sm text-emerald-600 dark:text-emerald-400">
            <span class="w-2 h-2 bg-emerald-500 rounded-full"></span>
            {{ t('online_payments.connected') }}
          </div>
        </div>

        <!-- Feature toggles -->
        <div class="mt-6 space-y-4">
          <BaseSwitchSection
            v-model="invoiceQrToggle"
            :title="t('online_payments.invoice_qr')"
            :description="t('online_payments.invoice_qr_desc')"
          />
          <BaseSwitchSection
            v-model="posQrToggle"
            :title="t('online_payments.pos_qr')"
            :description="t('online_payments.pos_qr_desc')"
          />
        </div>

        <!-- Accordion: How it works -->
        <div class="mt-6 space-y-2">
          <!-- Invoice instructions -->
          <div class="rounded-xl ring-1 ring-gray-200 dark:ring-gray-700 overflow-hidden">
            <button
              type="button"
              class="w-full flex items-center justify-between px-4 py-3 text-sm font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
              @click="showInvoiceGuide = !showInvoiceGuide"
            >
              {{ t('online_payments.guide_invoices') }}
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform" :class="showInvoiceGuide ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div v-if="showInvoiceGuide" class="px-4 pb-4 text-sm text-gray-600 dark:text-gray-400 space-y-2">
              <div class="flex gap-3 items-start">
                <span class="shrink-0 w-6 h-6 rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-600 text-xs font-bold flex items-center justify-center">1</span>
                <span>{{ t('online_payments.invoice_step_1') }}</span>
              </div>
              <div class="flex gap-3 items-start">
                <span class="shrink-0 w-6 h-6 rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-600 text-xs font-bold flex items-center justify-center">2</span>
                <span>{{ t('online_payments.invoice_step_2') }}</span>
              </div>
              <div class="flex gap-3 items-start">
                <span class="shrink-0 w-6 h-6 rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-600 text-xs font-bold flex items-center justify-center">3</span>
                <span>{{ t('online_payments.invoice_step_3') }}</span>
              </div>
              <div class="flex gap-3 items-start">
                <span class="shrink-0 w-6 h-6 rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-600 text-xs font-bold flex items-center justify-center">4</span>
                <span>{{ t('online_payments.invoice_step_4') }}</span>
              </div>
            </div>
          </div>

          <!-- POS instructions -->
          <div class="rounded-xl ring-1 ring-gray-200 dark:ring-gray-700 overflow-hidden">
            <button
              type="button"
              class="w-full flex items-center justify-between px-4 py-3 text-sm font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
              @click="showPosGuide = !showPosGuide"
            >
              {{ t('online_payments.guide_pos') }}
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform" :class="showPosGuide ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div v-if="showPosGuide" class="px-4 pb-4 text-sm text-gray-600 dark:text-gray-400 space-y-2">
              <div class="flex gap-3 items-start">
                <span class="shrink-0 w-6 h-6 rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-600 text-xs font-bold flex items-center justify-center">1</span>
                <span>{{ t('online_payments.pos_step_1') }}</span>
              </div>
              <div class="flex gap-3 items-start">
                <span class="shrink-0 w-6 h-6 rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-600 text-xs font-bold flex items-center justify-center">2</span>
                <span>{{ t('online_payments.pos_step_2') }}</span>
              </div>
              <div class="flex gap-3 items-start">
                <span class="shrink-0 w-6 h-6 rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-600 text-xs font-bold flex items-center justify-center">3</span>
                <span>{{ t('online_payments.pos_step_3') }}</span>
              </div>
              <div class="flex gap-3 items-start">
                <span class="shrink-0 w-6 h-6 rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-600 text-xs font-bold flex items-center justify-center">4</span>
                <span>{{ t('online_payments.pos_step_4') }}</span>
              </div>
              <div class="flex gap-3 items-start">
                <span class="shrink-0 w-6 h-6 rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-600 text-xs font-bold flex items-center justify-center">5</span>
                <span>{{ t('online_payments.pos_step_5') }}</span>
              </div>
            </div>
          </div>
        </div>
      </template>
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
const showInvoiceGuide = ref(false)
const showPosGuide = ref(false)

const form = reactive({
  casys_enabled: 'NO',
  casys_merchant_id: '',
  casys_merchant_name: '',
  casys_auth_key: '',
  casys_invoice_qr: 'NO',
  casys_pos_qr: 'NO',
})

function makeToggle(key) {
  return computed({
    get: () => form[key] === 'YES',
    set: (val) => { form[key] = val ? 'YES' : 'NO' },
  })
}

const enabledToggle = makeToggle('casys_enabled')
const invoiceQrToggle = makeToggle('casys_invoice_qr')
const posQrToggle = makeToggle('casys_pos_qr')

const isConfigured = computed(() => {
  return form.casys_merchant_id && form.casys_auth_key
})

async function saveSettings() {
  isSaving.value = true
  try {
    await companyStore.updateCompanySettings({
      data: {
        settings: {
          casys_enabled: form.casys_enabled,
          pos_casys_merchant_id: form.casys_merchant_id,
          pos_casys_merchant_name: form.casys_merchant_name,
          pos_casys_auth_key: form.casys_auth_key,
          casys_invoice_qr: form.casys_invoice_qr,
          pos_casys_qr: form.casys_pos_qr,
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
    if (s.casys_enabled) form.casys_enabled = s.casys_enabled
    if (s.pos_casys_merchant_id) form.casys_merchant_id = s.pos_casys_merchant_id
    if (s.pos_casys_merchant_name) form.casys_merchant_name = s.pos_casys_merchant_name
    if (s.pos_casys_auth_key) form.casys_auth_key = s.pos_casys_auth_key
    if (s.casys_invoice_qr) form.casys_invoice_qr = s.casys_invoice_qr
    if (s.pos_casys_qr) form.casys_pos_qr = s.pos_casys_qr
  }
})
</script>

<!-- CLAUDE-CHECKPOINT -->
