<template>
  <div>
    <h2 class="mb-2 text-xl font-semibold text-gray-900">
      {{ $t('onboarding.partner.step4_title') }}
    </h2>
    <p class="mb-6 text-sm text-gray-500">
      {{ $t('onboarding.partner.step4_subtitle') }}
    </p>

    <!-- Chart of Accounts -->
    <div class="mb-4 rounded-lg border bg-white p-4">
      <div class="flex items-center justify-between mb-2">
        <div class="flex items-center gap-2">
          <BaseIcon name="CalculatorIcon" class="h-5 w-5 text-purple-500" />
          <h3 class="text-sm font-semibold text-gray-900">
            {{ $t('onboarding.partner.chart_of_accounts') }}
          </h3>
        </div>
        <span class="flex items-center gap-1 text-xs text-green-600">
          <BaseIcon name="CheckCircleIcon" class="h-4 w-4" />
          {{ $t('onboarding.partner.auto_configured') }}
        </span>
      </div>
      <p class="text-xs text-gray-500">
        {{ $t('onboarding.partner.chart_desc') }}
      </p>
      <BaseButton
        variant="gray"
        size="sm"
        class="mt-2"
        @click="router.push({ name: 'partner.accounting.chart-of-accounts' })"
      >
        {{ $t('onboarding.partner.review_chart') }}
      </BaseButton>
    </div>

    <!-- VAT Rates -->
    <div class="mb-4 rounded-lg border bg-white p-4">
      <div class="flex items-center justify-between mb-2">
        <div class="flex items-center gap-2">
          <BaseIcon name="ReceiptPercentIcon" class="h-5 w-5 text-blue-500" />
          <h3 class="text-sm font-semibold text-gray-900">
            {{ $t('onboarding.partner.vat_rates') }}
          </h3>
        </div>
        <span class="flex items-center gap-1 text-xs text-green-600">
          <BaseIcon name="CheckCircleIcon" class="h-4 w-4" />
          {{ $t('onboarding.partner.auto_configured') }}
        </span>
      </div>
      <div class="flex gap-2 mt-1">
        <span class="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-700">18%</span>
        <span class="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-700">10%</span>
        <span class="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-700">5%</span>
        <span class="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-700">0%</span>
      </div>
    </div>

    <!-- Reporting Periods -->
    <div class="mb-4 rounded-lg border bg-white p-4">
      <div class="flex items-center gap-2 mb-2">
        <BaseIcon name="CalendarDaysIcon" class="h-5 w-5 text-orange-500" />
        <h3 class="text-sm font-semibold text-gray-900">
          {{ $t('onboarding.partner.reporting_periods') }}
        </h3>
      </div>
      <p class="text-xs text-gray-500">
        {{ $t('onboarding.partner.reporting_desc') }}
      </p>
    </div>

    <!-- Opening Balance -->
    <div class="mb-4 rounded-lg border border-yellow-200 bg-yellow-50 p-4">
      <div class="flex items-center gap-2 mb-2">
        <BaseIcon name="ScaleIcon" class="h-5 w-5 text-yellow-600" />
        <h3 class="text-sm font-semibold text-yellow-900">
          {{ $t('onboarding.partner.opening_balance') }}
        </h3>
      </div>
      <p class="text-xs text-yellow-700">
        {{ $t('onboarding.partner.opening_balance_desc') }}
      </p>
    </div>

    <BaseButton variant="primary" @click="$emit('next')">
      {{ $t('onboarding.wizard.next') }}
      <template #right="slotProps">
        <BaseIcon :class="slotProps.class" name="ArrowRightIcon" />
      </template>
    </BaseButton>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'

const { t } = useI18n()
const router = useRouter()

defineProps({
  company: { type: Object, default: null },
})

defineEmits(['next'])
</script>
