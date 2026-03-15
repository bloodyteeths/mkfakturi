<template>
  <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
    <!-- Page heading -->
    <div class="mb-8">
      <h1 class="text-xl font-semibold text-gray-900">
        {{ t('onboarding.partner.title') }}
      </h1>
      <p class="mt-1 text-sm text-gray-500">
        {{ t('onboarding.partner.simple_subtitle', 'Get your client portfolio ready.') }}
      </p>
    </div>

    <!-- 1. Import portfolio -->
    <div class="mb-6 rounded-lg border border-gray-200 bg-white p-5">
      <h3 class="text-sm font-medium text-gray-900 mb-2">
        {{ t('onboarding.partner.step1_title') }}
      </h3>
      <p class="text-sm text-gray-500 mb-4">
        {{ t('onboarding.partner.step1_subtitle') }}
      </p>
      <div class="flex gap-2">
        <BaseButton
          variant="primary"
          size="sm"
          @click="router.push({ name: 'partner.portfolio.companies.import' })"
        >
          {{ t('onboarding.partner.import_portfolio') }}
        </BaseButton>
        <BaseButton
          variant="gray"
          size="sm"
          @click="router.push({ name: 'partner.portfolio.companies.create' })"
        >
          {{ t('onboarding.partner.add_manually') }}
        </BaseButton>
      </div>

      <!-- Show existing companies -->
      <div v-if="companies.length > 0" class="mt-4 pt-4 border-t border-gray-100">
        <p class="text-xs font-medium text-gray-500 mb-2">
          {{ t('onboarding.partner.companies_ready', { count: companies.length }) }}
        </p>
        <div class="max-h-40 overflow-y-auto space-y-1">
          <div
            v-for="company in companies"
            :key="company.id"
            class="flex items-center gap-2 text-sm text-gray-700 py-1"
          >
            <BaseIcon name="BuildingOffice2Icon" class="h-4 w-4 text-gray-400" />
            <span>{{ company.name }}</span>
            <span v-if="company.tax_id" class="text-xs text-gray-400">{{ company.tax_id }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- 2. Set up a company -->
    <div v-if="companies.length > 0" class="mb-6 rounded-lg border border-gray-200 bg-white p-5">
      <h3 class="text-sm font-medium text-gray-900 mb-3">
        {{ t('onboarding.partner.select_company_title', 'Set up a company') }}
      </h3>

      <!-- Company select -->
      <div class="mb-4">
        <label class="block text-xs font-medium text-gray-600 mb-1">
          {{ t('onboarding.partner.select_company_label', 'Company') }}
        </label>
        <select
          v-model="selectedCompanyId"
          class="block w-full rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500"
        >
          <option value="">{{ t('onboarding.partner.select_placeholder', '— Select a company —') }}</option>
          <option
            v-for="company in companies"
            :key="company.id"
            :value="company.id"
          >
            {{ company.name }}
          </option>
        </select>
      </div>

      <!-- Source selection for selected company -->
      <div v-if="selectedCompanyId">
        <label class="block text-xs font-medium text-gray-600 mb-1">
          {{ t('onboarding.partner.source_label', 'Accounting software used') }}
        </label>
        <div class="flex flex-wrap gap-2">
          <label
            v-for="source in sources"
            :key="source.key"
            class="flex items-center gap-2 rounded-lg border px-3 py-2 cursor-pointer text-sm transition-colors"
            :class="
              selectedSource === source.key
                ? 'border-primary-500 bg-primary-50'
                : 'border-gray-200 hover:border-gray-300'
            "
          >
            <input
              type="radio"
              name="partner-source"
              :value="source.key"
              :checked="selectedSource === source.key"
              class="h-3.5 w-3.5 text-primary-600 border-gray-300"
              @change="selectedSource = source.key"
            />
            {{ source.name }}
          </label>
        </div>

        <!-- Export guide for selected source -->
        <Step2Guide
          v-if="selectedSource && selectedSource !== 'fresh'"
          :key="selectedSource"
          :source="selectedSource"
        />
      </div>
    </div>

    <!-- Quick actions -->
    <div class="mt-6">
      <h3 class="text-sm font-medium text-gray-700 mb-3">
        {{ t('onboarding.simple.ready_title', 'Ready? Import your data:') }}
      </h3>
      <div class="space-y-2">
        <router-link
          v-for="action in quickActions"
          :key="action.label"
          :to="action.route"
          class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm text-gray-700 hover:border-primary-300 hover:bg-primary-50 transition-colors"
        >
          <BaseIcon :name="action.icon" class="h-4 w-4 text-gray-400" />
          <span>{{ action.label }}</span>
        </router-link>
      </div>
    </div>

    <!-- Done -->
    <div class="mt-8 flex gap-3">
      <BaseButton variant="primary" @click="onComplete">
        {{ t('onboarding.partner.go_portfolio') }}
      </BaseButton>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Step2Guide from '../../onboarding/steps/Step2Guide.vue'

const { t } = useI18n()
const router = useRouter()

const companies = ref([])
const selectedCompanyId = ref('')
const selectedSource = ref(null)

const sources = computed(() => [
  { key: 'pantheon', name: 'Pantheon' },
  { key: 'zonel', name: 'Helix / Zonel' },
  { key: 'ekonomika', name: 'Ekonomika' },
  { key: 'astral', name: 'Astral' },
  { key: 'b2b', name: 'B2B' },
  { key: 'excel', name: 'Excel' },
  { key: 'fresh', name: t('onboarding.step1.fresh_name') },
])

const quickActions = computed(() => [
  {
    label: t('onboarding.simple.action_journal', 'Import journal entries'),
    icon: 'BookOpenIcon',
    route: { name: 'partner.accounting.journal-import' },
  },
  {
    label: t('onboarding.simple.action_customers', 'Import customers & suppliers'),
    icon: 'UsersIcon',
    route: { name: 'imports.wizard' },
  },
  {
    label: t('onboarding.partner.view_chart', 'View chart of accounts'),
    icon: 'CalculatorIcon',
    route: { name: 'partner.accounting.chart-of-accounts' },
  },
  {
    label: t('onboarding.partner.go_portfolio'),
    icon: 'BuildingOffice2Icon',
    route: { name: 'partner.portfolio' },
  },
])

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
