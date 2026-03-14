<template>
  <BasePage>
    <BasePageHeader :title="$t('partner.accounting.migration_hub.title')">
      <template #actions>
        <BaseButton
          variant="primary"
          @click="router.push({ name: 'imports.wizard' })"
        >
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="ArrowUpTrayIcon" />
          </template>
          {{ $t('partner.accounting.migration_hub.data_import') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Subtitle -->
    <p class="mb-2 text-sm text-gray-600">
      {{ $t('partner.accounting.migration_hub.subtitle') }}
    </p>

    <!-- Tip for partners -->
    <div v-if="isPartnerOrAccountant" class="mb-6 flex items-start gap-2 rounded-lg bg-blue-50 p-3">
      <BaseIcon name="LightBulbIcon" class="mt-0.5 h-4 w-4 flex-shrink-0 text-blue-500" />
      <p class="text-xs text-blue-700">
        {{ $t('partner.accounting.migration_hub.recommended_order') }}
      </p>
    </div>

    <!-- Migration Steps Grid -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <div
        v-for="step in visibleSteps"
        :key="step.key"
        class="group relative rounded-lg border bg-white p-5 shadow-sm transition-shadow hover:shadow-md"
        :class="step.disabled ? 'border-gray-200 opacity-60' : 'border-gray-200'"
      >
        <!-- Status Badge -->
        <div class="absolute right-3 top-3">
          <span
            v-if="!step.disabled"
            class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
            :class="getStatusClass(step.status)"
          >
            {{ getStatusLabel(step.status) }}
          </span>
          <span
            v-else
            class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500"
          >
            {{ $t('partner.accounting.migration_hub.coming_soon') }}
          </span>
        </div>

        <!-- Icon -->
        <div
          class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg"
          :class="step.iconBg"
        >
          <BaseIcon :name="step.icon" class="h-5 w-5" :class="step.iconColor" />
        </div>

        <!-- Content -->
        <h3 class="mb-1 text-sm font-semibold text-gray-900">
          {{ step.title }}
        </h3>
        <p class="mb-4 text-xs text-gray-500">
          {{ step.description }}
        </p>

        <!-- Action Button -->
        <BaseButton
          v-if="!step.disabled"
          :variant="step.status === 'completed' ? 'gray' : 'primary'"
          size="sm"
          class="w-full"
          @click="navigateToStep(step)"
        >
          {{ getActionLabel(step.status) }}
        </BaseButton>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/scripts/admin/stores/user'
import axios from 'axios'

const { t } = useI18n()
const router = useRouter()
const userStore = useUserStore()

const stepStatuses = ref({})

const isPartnerOrAccountant = computed(() => {
  const user = userStore.currentUser
  if (!user) return false
  return user.role === 'partner' || user.role === 'super admin' ||
         user.account_type === 'accountant' || user.is_partner
})

async function fetchMigrationProgress() {
  try {
    const { data } = await axios.get('/onboarding/migration-progress')
    if (data.steps) {
      stepStatuses.value = data.steps
    }
  } catch {
    // Silently fail — defaults to 'not_started'
  }
}

onMounted(fetchMigrationProgress)

// All migration steps — some are partner-only, some are for everyone
const allSteps = computed(() => [
  // Available to everyone
  {
    key: 'customers_suppliers',
    title: t('partner.accounting.migration_hub.customers_suppliers'),
    description: t('partner.accounting.migration_hub.customers_suppliers_desc'),
    icon: 'UsersIcon',
    iconBg: 'bg-blue-100',
    iconColor: 'text-blue-600',
    route: { name: 'imports.wizard' },
    status: stepStatuses.value.customers_suppliers || 'not_started',
    disabled: false,
    partnerOnly: false,
  },
  {
    key: 'products_services',
    title: t('partner.accounting.migration_hub.products_services'),
    description: t('partner.accounting.migration_hub.products_services_desc'),
    icon: 'CubeIcon',
    iconBg: 'bg-indigo-100',
    iconColor: 'text-indigo-600',
    route: { name: 'imports.wizard' },
    status: stepStatuses.value.products_services || 'not_started',
    disabled: false,
    partnerOnly: false,
  },
  {
    key: 'invoices_payments',
    title: t('partner.accounting.migration_hub.invoices_payments'),
    description: t('partner.accounting.migration_hub.invoices_payments_desc'),
    icon: 'DocumentTextIcon',
    iconBg: 'bg-orange-100',
    iconColor: 'text-orange-600',
    route: { name: 'imports.wizard' },
    status: stepStatuses.value.invoices_payments || 'not_started',
    disabled: false,
    partnerOnly: false,
  },
  // Partner/accountant only
  {
    key: 'chart_of_accounts',
    title: t('partner.accounting.migration_hub.chart_of_accounts'),
    description: t('partner.accounting.migration_hub.chart_of_accounts_desc'),
    icon: 'CalculatorIcon',
    iconBg: 'bg-purple-100',
    iconColor: 'text-purple-600',
    route: { name: 'partner.accounting.chart-of-accounts' },
    status: stepStatuses.value.chart_of_accounts || 'not_started',
    disabled: false,
    partnerOnly: true,
  },
  {
    key: 'journal_entries',
    title: t('partner.accounting.migration_hub.journal_entries'),
    description: t('partner.accounting.migration_hub.journal_entries_desc'),
    icon: 'BookOpenIcon',
    iconBg: 'bg-green-100',
    iconColor: 'text-green-600',
    route: { name: 'partner.accounting.journal-import' },
    status: stepStatuses.value.journal_entries || 'not_started',
    disabled: false,
    partnerOnly: true,
  },
  {
    key: 'opening_balances',
    title: t('partner.accounting.migration_hub.opening_balances'),
    description: t('partner.accounting.migration_hub.opening_balances_desc'),
    icon: 'ScaleIcon',
    iconBg: 'bg-yellow-100',
    iconColor: 'text-yellow-600',
    route: null,
    status: 'not_started',
    disabled: true,
    partnerOnly: true,
  },
  {
    key: 'fixed_assets',
    title: t('partner.accounting.migration_hub.fixed_assets'),
    description: t('partner.accounting.migration_hub.fixed_assets_desc'),
    icon: 'BuildingOfficeIcon',
    iconBg: 'bg-gray-100',
    iconColor: 'text-gray-600',
    route: null,
    status: 'not_started',
    disabled: true,
    partnerOnly: true,
  },
])

const visibleSteps = computed(() => {
  if (isPartnerOrAccountant.value) return allSteps.value
  return allSteps.value.filter(s => !s.partnerOnly)
})

function getStatusClass(status) {
  switch (status) {
    case 'completed': return 'bg-green-100 text-green-800'
    case 'in_progress': return 'bg-yellow-100 text-yellow-800'
    default: return 'bg-gray-100 text-gray-600'
  }
}

function getStatusLabel(status) {
  switch (status) {
    case 'completed': return t('partner.accounting.migration_hub.status_completed')
    case 'in_progress': return t('partner.accounting.migration_hub.status_in_progress')
    default: return t('partner.accounting.migration_hub.status_not_started')
  }
}

function getActionLabel(status) {
  switch (status) {
    case 'completed': return t('partner.accounting.migration_hub.view_results')
    case 'in_progress': return t('partner.accounting.migration_hub.continue_import')
    default: return t('partner.accounting.migration_hub.start_import')
  }
}

function navigateToStep(step) {
  if (step.route) {
    router.push(step.route)
  }
}
</script>
