<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-primary-50/30">
    <!-- Hero Header -->
    <div class="relative overflow-hidden bg-gradient-to-r from-indigo-600 via-primary-500 to-purple-500 px-6 py-8 sm:px-10">
      <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-white/5" />
        <div class="absolute -left-10 -bottom-10 h-48 w-48 rounded-full bg-white/5" />
        <div class="absolute right-1/4 top-1/3 h-32 w-32 rounded-full bg-white/10 animate-pulse" />
      </div>

      <div class="relative mx-auto max-w-4xl">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-white sm:text-3xl tracking-tight">
              {{ t('onboarding.partner.title') }}
            </h1>
            <p class="mt-1.5 text-sm text-purple-100/80">
              {{ stepLabels[currentStep - 1]?.label || '' }}
            </p>
          </div>
          <BaseButton
            v-if="currentStep > 1"
            class="!bg-white/10 !text-white !border-white/20 hover:!bg-white/20 backdrop-blur-sm"
            size="sm"
            @click="onComplete"
          >
            {{ t('onboarding.wizard.skip_for_now') }}
          </BaseButton>
        </div>

        <!-- Step Progress Bar -->
        <div class="mt-8 pb-2">
          <div class="flex items-center justify-between">
            <template v-for="(step, index) in stepLabels" :key="step.num">
              <div class="flex flex-col items-center z-10">
                <div
                  :class="[
                    'flex items-center justify-center w-11 h-11 rounded-full text-sm font-bold transition-all duration-500 ease-out',
                    step.num < currentStep
                      ? 'bg-white text-indigo-600 shadow-lg shadow-white/25'
                      : step.num === currentStep
                        ? 'bg-white text-indigo-600 shadow-xl shadow-white/40 scale-110 ring-4 ring-white/25'
                        : 'bg-white/15 text-white/60 backdrop-blur-sm',
                  ]"
                >
                  <BaseIcon v-if="step.num < currentStep" name="CheckIcon" class="h-5 w-5" />
                  <span v-else>{{ step.num }}</span>
                </div>
                <span
                  :class="[
                    'mt-2.5 text-[11px] font-semibold text-center max-w-[80px] transition-all duration-300',
                    step.num === currentStep ? 'text-white' : 'text-white/50',
                  ]"
                >
                  {{ step.label }}
                </span>
              </div>
              <div v-if="index < stepLabels.length - 1" class="relative flex-1 mx-2 -mt-6">
                <div class="h-0.5 w-full bg-white/15 rounded-full" />
                <div
                  class="absolute inset-y-0 left-0 h-0.5 bg-white/80 rounded-full transition-all duration-700 ease-out"
                  :style="{ width: step.num < currentStep ? '100%' : '0%' }"
                />
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- Step Content -->
    <div class="mx-auto max-w-4xl px-6 py-8 sm:px-10">
      <div class="min-h-[400px]">
        <!-- Step 1: Import Portfolio -->
        <div v-if="currentStep === 1">
          <div class="mb-8 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-500 shadow-lg shadow-indigo-500/25">
              <BaseIcon name="BuildingOffice2Icon" class="h-8 w-8 text-white" />
            </div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">
              {{ t('onboarding.partner.step1_title') }}
            </h2>
            <p class="mx-auto mt-2 max-w-lg text-sm text-gray-500 leading-relaxed">
              {{ t('onboarding.partner.step1_subtitle') }}
            </p>
          </div>

          <div class="flex justify-center gap-3 mb-8">
            <BaseButton
              variant="primary"
              class="!shadow-md !shadow-primary-500/15"
              @click="router.push({ name: 'partner.portfolio.companies.import' })"
            >
              <template #left="slotProps">
                <BaseIcon :class="slotProps.class" name="ArrowUpTrayIcon" />
              </template>
              {{ t('onboarding.partner.import_portfolio') }}
            </BaseButton>
            <BaseButton variant="gray" @click="router.push({ name: 'partner.portfolio.companies.create' })">
              {{ t('onboarding.partner.add_manually') }}
            </BaseButton>
          </div>

          <!-- Show existing companies -->
          <div v-if="companies.length > 0" class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <h3 class="mb-3 flex items-center gap-2 text-sm font-bold text-gray-900">
              <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-green-100">
                <BaseIcon name="CheckCircleIcon" class="h-4 w-4 text-green-600" />
              </div>
              {{ t('onboarding.partner.companies_ready', { count: companies.length }) }}
            </h3>
            <div class="max-h-48 overflow-y-auto space-y-1">
              <div
                v-for="company in companies"
                :key="company.id"
                class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
              >
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100">
                  <BaseIcon name="BuildingOffice2Icon" class="h-4 w-4 text-gray-400" />
                </div>
                <span class="font-medium">{{ company.name }}</span>
                <span v-if="company.tax_id" class="text-xs text-gray-400">{{ company.tax_id }}</span>
              </div>
            </div>
          </div>

          <div class="mt-6 text-center">
            <BaseButton variant="primary" @click="currentStep = 2">
              {{ companies.length > 0 ? t('onboarding.wizard.next') : t('onboarding.partner.skip_portfolio') }}
              <template #right="slotProps">
                <BaseIcon :class="slotProps.class" name="ArrowRightIcon" />
              </template>
            </BaseButton>
          </div>
        </div>

        <!-- Step 2: Select Company & Source -->
        <PartnerStep2Select
          v-else-if="currentStep === 2"
          :companies="companies"
          :selected-company="selectedCompany"
          :selected-source="selectedSource"
          @select-company="onSelectCompany"
          @select-source="onSelectSource"
          @next="currentStep = 3"
        />

        <!-- Step 3: Import Company Data -->
        <div v-else-if="currentStep === 3">
          <h2 class="mb-2 text-2xl font-bold text-gray-900 tracking-tight">
            {{ t('onboarding.partner.step3_title', { company: selectedCompany?.name || '' }) }}
          </h2>
          <p class="mb-6 text-sm text-gray-500 leading-relaxed">
            {{ t('onboarding.partner.step3_subtitle') }}
          </p>

          <!-- Journal import CTA -->
          <div class="mb-5 rounded-2xl border-2 border-primary-200 bg-gradient-to-br from-primary-50 to-indigo-50/50 p-5">
            <div class="flex items-start gap-4">
              <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-primary-500 to-primary-600 shadow-md shadow-primary-500/20">
                <BaseIcon name="BookOpenIcon" class="h-5 w-5 text-white" />
              </div>
              <div>
                <h3 class="text-sm font-bold text-primary-900">
                  {{ t('onboarding.partner.journal_import_title') }}
                </h3>
                <p class="mt-1 text-xs text-primary-700/70 leading-relaxed">
                  {{ t('onboarding.partner.journal_import_desc') }}
                </p>
                <BaseButton
                  variant="primary"
                  size="sm"
                  class="mt-3"
                  @click="router.push({ name: 'partner.accounting.journal-import' })"
                >
                  {{ t('onboarding.partner.open_journal_import') }}
                </BaseButton>
              </div>
            </div>
          </div>

          <!-- Other import options -->
          <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <button
              class="group flex items-center gap-3 rounded-xl border border-gray-100 bg-white p-4 text-left transition-all hover:-translate-y-0.5 hover:shadow-md hover:border-blue-200"
              @click="router.push({ name: 'imports.wizard' })"
            >
              <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-blue-50 transition-transform group-hover:scale-110">
                <BaseIcon name="UsersIcon" class="h-5 w-5 text-blue-500" />
              </div>
              <div>
                <p class="text-sm font-semibold text-gray-900">{{ t('onboarding.partner.import_customers') }}</p>
                <p class="text-[11px] text-gray-400">CSV / Excel</p>
              </div>
            </button>
            <button
              class="group flex items-center gap-3 rounded-xl border border-gray-100 bg-white p-4 text-left transition-all hover:-translate-y-0.5 hover:shadow-md hover:border-orange-200"
              @click="router.push({ name: 'imports.wizard' })"
            >
              <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-orange-50 transition-transform group-hover:scale-110">
                <BaseIcon name="DocumentTextIcon" class="h-5 w-5 text-orange-500" />
              </div>
              <div>
                <p class="text-sm font-semibold text-gray-900">{{ t('onboarding.partner.import_invoices') }}</p>
                <p class="text-[11px] text-gray-400">CSV / Excel</p>
              </div>
            </button>
          </div>

          <div class="mt-6 flex gap-3">
            <BaseButton variant="primary" @click="currentStep = 4">
              {{ t('onboarding.wizard.next') }}
              <template #right="slotProps">
                <BaseIcon :class="slotProps.class" name="ArrowRightIcon" />
              </template>
            </BaseButton>
            <BaseButton variant="gray" @click="currentStep = 4">
              {{ t('onboarding.step3.skip') }}
            </BaseButton>
          </div>
        </div>

        <!-- Step 4: Verify Accounting Setup -->
        <PartnerStep4Verify
          v-else-if="currentStep === 4"
          :company="selectedCompany"
          @next="currentStep = 5"
        />

        <!-- Step 5: Done -->
        <div v-else-if="currentStep === 5">
          <div class="mb-8 text-center">
            <div class="relative mx-auto mb-5 flex h-20 w-20 items-center justify-center">
              <div class="absolute inset-0 rounded-full bg-green-100 animate-ping opacity-20" />
              <div class="absolute inset-2 rounded-full bg-green-50" />
              <div class="relative flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-green-400 to-emerald-500 shadow-lg shadow-green-500/30">
                <BaseIcon name="CheckIcon" class="h-8 w-8 text-white" />
              </div>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">
              {{ selectedCompany
                ? t('onboarding.partner.step5_title', { company: selectedCompany.name })
                : t('onboarding.partner.step5_title_generic')
              }}
            </h2>
          </div>

          <div class="flex justify-center gap-3">
            <BaseButton
              variant="primary"
              class="!shadow-md !shadow-primary-500/15"
              @click="setupAnotherCompany"
            >
              <template #left="slotProps">
                <BaseIcon :class="slotProps.class" name="PlusIcon" />
              </template>
              {{ t('onboarding.partner.setup_another') }}
            </BaseButton>
            <BaseButton
              variant="gray"
              @click="onComplete"
            >
              {{ t('onboarding.partner.go_portfolio') }}
            </BaseButton>
          </div>
        </div>
      </div>

      <!-- Navigation -->
      <div
        v-if="currentStep > 1 && currentStep < 5"
        class="mt-10 flex justify-between border-t border-gray-100 pt-6"
      >
        <BaseButton variant="gray" @click="currentStep--">
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="ArrowLeftIcon" />
          </template>
          {{ t('onboarding.wizard.back') }}
        </BaseButton>
        <div />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import axios from 'axios'
import PartnerStep2Select from './steps/PartnerStep2Select.vue'
import PartnerStep4Verify from './steps/PartnerStep4Verify.vue'

const { t } = useI18n()
const router = useRouter()

const currentStep = ref(1)
const companies = ref([])
const selectedCompany = ref(null)
const selectedSource = ref(null)

const stepLabels = computed(() => [
  { num: 1, label: t('onboarding.partner.label_portfolio') },
  { num: 2, label: t('onboarding.partner.label_select') },
  { num: 3, label: t('onboarding.partner.label_import') },
  { num: 4, label: t('onboarding.partner.label_verify') },
  { num: 5, label: t('onboarding.partner.label_done') },
])

function onSelectCompany(company) {
  selectedCompany.value = company
}

function onSelectSource(source) {
  selectedSource.value = source
}

function setupAnotherCompany() {
  selectedCompany.value = null
  selectedSource.value = null
  currentStep.value = 2
}

async function onComplete() {
  try {
    await axios.post('/partner/onboarding/complete')
  } catch (e) {
    // Continue anyway
  }
  router.push({ name: 'partner.portfolio' })
}

async function fetchCompanies() {
  try {
    const { data } = await axios.get('/partner/portfolio-companies')
    companies.value = data.data || data || []
  } catch (e) {
    companies.value = []
  }
}

onMounted(fetchCompanies)
</script>
