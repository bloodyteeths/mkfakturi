<template>
  <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
    <!-- Page heading -->
    <div class="mb-8">
      <h1 class="text-xl font-semibold text-gray-900">
        {{ t('onboarding.wizard.title') }}
      </h1>
      <p class="mt-1 text-sm text-gray-500">
        {{ t('onboarding.wizard.simple_subtitle', 'Set up your accounting in a few simple steps.') }}
      </p>
    </div>

    <!-- Source selection -->
    <Step1Source
      :selected-source="selectedSource"
      @select="onSourceSelect"
    />

    <!-- Export guide (shown when source selected, hidden for "fresh") -->
    <Step2Guide
      v-if="selectedSource && selectedSource !== 'fresh'"
      :key="selectedSource"
      :source="selectedSource"
    />

    <!-- Quick actions -->
    <div class="mt-8">
      <h3 class="text-sm font-medium text-gray-700 mb-3">
        {{ t('onboarding.simple.ready_title', 'Ready? Import your data:') }}
      </h3>
      <div class="space-y-2">
        <router-link
          v-for="action in quickActions"
          :key="action.route"
          :to="action.route"
          class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm text-gray-700 hover:border-primary-300 hover:bg-primary-50 transition-colors"
        >
          <BaseIcon :name="action.icon" class="h-4 w-4 text-gray-400" />
          <span>{{ action.label }}</span>
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import Step1Source from './steps/Step1Source.vue'
import Step2Guide from './steps/Step2Guide.vue'

const { t } = useI18n()

const selectedSource = ref(null)

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
    label: t('onboarding.simple.action_bank', 'Import bank statements'),
    icon: 'BanknotesIcon',
    route: { name: 'banking' },
  },
  {
    label: t('onboarding.simple.action_invoice', 'Create your first invoice'),
    icon: 'DocumentPlusIcon',
    route: { name: 'invoices.create' },
  },
  {
    label: t('onboarding.simple.action_company', 'Fill company details'),
    icon: 'BuildingOffice2Icon',
    route: { name: 'company.info' },
  },
])

function onSourceSelect(source) {
  selectedSource.value = source
  axios.post('/onboarding/source', { source }).catch(() => {})
}

onMounted(async () => {
  try {
    const { data } = await axios.get('/onboarding/progress')
    if (data.source) {
      selectedSource.value = data.source
    }
  } catch (e) {
    // Start fresh
  }
})
</script>
