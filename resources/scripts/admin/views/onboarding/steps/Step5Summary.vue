<template>
  <div>
    <!-- Celebration header -->
    <div class="mb-8 text-center">
      <div class="relative mx-auto mb-5 flex h-20 w-20 items-center justify-center">
        <!-- Animated rings -->
        <div class="absolute inset-0 rounded-full bg-green-100 animate-ping opacity-20" />
        <div class="absolute inset-2 rounded-full bg-green-50" />
        <div class="relative flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-green-400 to-emerald-500 shadow-lg shadow-green-500/30">
          <BaseIcon name="CheckIcon" class="h-8 w-8 text-white" />
        </div>
      </div>
      <h2 class="text-2xl font-bold text-gray-900 tracking-tight">
        {{ $t('onboarding.step5.title') }}
      </h2>
      <p class="mt-2 text-sm text-gray-500 leading-relaxed">
        {{ $t('onboarding.step5.subtitle') }}
      </p>
    </div>

    <!-- Import summary stats -->
    <div v-if="stats" class="mb-8 grid grid-cols-2 gap-3 sm:grid-cols-4">
      <div class="rounded-xl border border-gray-100 bg-white p-4 text-center shadow-sm hover:shadow-md transition-shadow">
        <p class="text-2xl font-black text-gray-900">{{ stats.customers }}</p>
        <p class="mt-0.5 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">{{ $t('onboarding.step5.customers') }}</p>
      </div>
      <div class="rounded-xl border border-gray-100 bg-white p-4 text-center shadow-sm hover:shadow-md transition-shadow">
        <p class="text-2xl font-black text-gray-900">{{ stats.suppliers }}</p>
        <p class="mt-0.5 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">{{ $t('onboarding.step5.suppliers') }}</p>
      </div>
      <div class="rounded-xl border border-gray-100 bg-white p-4 text-center shadow-sm hover:shadow-md transition-shadow">
        <p class="text-2xl font-black text-gray-900">{{ stats.invoices }}</p>
        <p class="mt-0.5 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">{{ $t('onboarding.step5.invoices') }}</p>
      </div>
      <div class="rounded-xl border border-gray-100 bg-white p-4 text-center shadow-sm hover:shadow-md transition-shadow">
        <p class="text-2xl font-black text-gray-900">{{ stats.items }}</p>
        <p class="mt-0.5 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">{{ $t('onboarding.step5.items') }}</p>
      </div>
    </div>

    <!-- Remaining checklist -->
    <div v-if="remainingSteps.length > 0" class="mb-8 rounded-2xl border border-amber-200/60 bg-gradient-to-br from-amber-50 to-yellow-50/50 p-5">
      <h3 class="mb-3 flex items-center gap-2.5 text-sm font-bold text-amber-900">
        <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-400 shadow-sm">
          <BaseIcon name="ExclamationTriangleIcon" class="h-3.5 w-3.5 text-white" />
        </div>
        {{ $t('onboarding.step5.still_needed') }}
      </h3>
      <div class="space-y-1.5">
        <div
          v-for="step in remainingSteps"
          :key="step.key"
          class="flex items-center gap-2.5 rounded-lg bg-white/50 px-3 py-2 text-sm text-amber-800"
        >
          <div class="h-1.5 w-1.5 rounded-full bg-amber-400" />
          {{ step.label }}
        </div>
      </div>
    </div>

    <!-- Quick actions -->
    <div class="mb-8">
      <h3 class="mb-4 text-sm font-bold text-gray-900 uppercase tracking-wider">
        {{ $t('onboarding.step5.next_steps') }}
      </h3>
      <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <button
          class="group flex items-center gap-4 rounded-xl border border-gray-100 bg-white p-4 text-left transition-all duration-200 hover:-translate-y-0.5 hover:border-primary-200 hover:shadow-lg hover:shadow-primary-500/5"
          @click="router.push({ name: 'invoices.create' })"
        >
          <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-primary-500 to-primary-600 shadow-md shadow-primary-500/20 transition-transform group-hover:scale-110">
            <BaseIcon name="DocumentPlusIcon" class="h-5 w-5 text-white" />
          </div>
          <div>
            <p class="text-sm font-semibold text-gray-900">{{ $t('onboarding.step5.create_invoice') }}</p>
            <p class="mt-0.5 text-[11px] text-gray-400">{{ $t('onboarding.step5.create_invoice_desc') }}</p>
          </div>
        </button>
        <button
          class="group flex items-center gap-4 rounded-xl border border-gray-100 bg-white p-4 text-left transition-all duration-200 hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-lg hover:shadow-blue-500/5"
          @click="router.push({ name: 'banking' })"
        >
          <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 shadow-md shadow-blue-500/20 transition-transform group-hover:scale-110">
            <BaseIcon name="BuildingLibraryIcon" class="h-5 w-5 text-white" />
          </div>
          <div>
            <p class="text-sm font-semibold text-gray-900">{{ $t('onboarding.step5.setup_bank') }}</p>
            <p class="mt-0.5 text-[11px] text-gray-400">{{ $t('onboarding.step5.setup_bank_desc') }}</p>
          </div>
        </button>
        <button
          class="group flex items-center gap-4 rounded-xl border border-gray-100 bg-white p-4 text-left transition-all duration-200 hover:-translate-y-0.5 hover:border-green-200 hover:shadow-lg hover:shadow-green-500/5"
          @click="router.push({ name: 'users.index' })"
        >
          <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-green-500 to-green-600 shadow-md shadow-green-500/20 transition-transform group-hover:scale-110">
            <BaseIcon name="UserPlusIcon" class="h-5 w-5 text-white" />
          </div>
          <div>
            <p class="text-sm font-semibold text-gray-900">{{ $t('onboarding.step5.invite_team') }}</p>
            <p class="mt-0.5 text-[11px] text-gray-400">{{ $t('onboarding.step5.invite_team_desc') }}</p>
          </div>
        </button>
      </div>
    </div>

    <!-- Complete button -->
    <div class="text-center">
      <BaseButton
        variant="primary"
        size="lg"
        class="!px-10 !shadow-lg !shadow-primary-500/20"
        @click="$emit('complete')"
      >
        <template #left="slotProps">
          <BaseIcon :class="slotProps.class" name="RocketLaunchIcon" />
        </template>
        {{ $t('onboarding.step5.complete') }}
      </BaseButton>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import axios from 'axios'

const { t } = useI18n()
const router = useRouter()

defineEmits(['complete'])

const progress = ref(null)
const stats = ref(null)

const remainingSteps = computed(() => {
  if (!progress.value?.steps) return []

  const stepLabels = {
    company_details: t('onboarding.checklist.step_company_details'),
    upload_logo: t('onboarding.checklist.step_upload_logo'),
    import_data: t('onboarding.checklist.step_import_data'),
    first_invoice: t('onboarding.checklist.step_first_invoice'),
    bank_account: t('onboarding.checklist.step_bank_account'),
  }

  return progress.value.steps
    .filter(s => !s.completed)
    .map(s => ({ key: s.key, label: stepLabels[s.key] || s.key }))
})

onMounted(async () => {
  try {
    const [progressRes, statsRes] = await Promise.all([
      axios.get('/onboarding/progress'),
      axios.get('/bootstrap'),
    ])

    progress.value = progressRes.data

    const company = statsRes.data?.current_company
    if (company) {
      stats.value = {
        customers: company.customers_count || 0,
        suppliers: company.suppliers_count || 0,
        invoices: company.invoices_count || 0,
        items: company.items_count || 0,
      }
    } else {
      stats.value = { customers: 0, suppliers: 0, invoices: 0, items: 0 }
    }
  } catch (e) {
    stats.value = { customers: 0, suppliers: 0, invoices: 0, items: 0 }
  }
})
</script>
