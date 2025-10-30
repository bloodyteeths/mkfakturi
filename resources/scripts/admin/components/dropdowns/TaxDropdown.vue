<template>
  <BaseDropdown>
    <template #activator>
      <BaseButton variant="primary-outline" size="base">
        <BaseIcon name="ChartBarIcon" class="h-5 w-5 mr-2" />
        {{ $t('navigation.tax_tools') }}
        <BaseIcon name="ChevronDownIcon" class="h-4 w-4 ml-2" />
      </BaseButton>
    </template>

    <!-- VAT Return Generator -->
    <BaseDropdownItem @click="navigateToVatReturn">
      <BaseIcon
        name="DocumentTextIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('vat.generate_return') }}
    </BaseDropdownItem>

    <!-- Tax Types Management -->
    <BaseDropdownItem 
      v-if="userStore.hasAbilities(abilities.VIEW_TAX_TYPE)"
      @click="navigateToTaxTypes"
    >
      <BaseIcon
        name="CheckCircleIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('settings.menu_title.tax_types') }}
    </BaseDropdownItem>

    <!-- Tax Reports -->
    <BaseDropdownItem 
      v-if="userStore.hasAbilities(abilities.VIEW_FINANCIAL_REPORT)"
      @click="navigateToTaxReports"
    >
      <BaseIcon
        name="ChartBarIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('navigation.tax_reports') }}
    </BaseDropdownItem>
  </BaseDropdown>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useUserStore } from '@/scripts/admin/stores/user'
import abilities from '@/scripts/admin/stub/abilities'

const router = useRouter()
const userStore = useUserStore()

function navigateToVatReturn() {
  router.push({ name: 'vat.return' })
}

function navigateToTaxTypes() {
  router.push({ name: 'tax.types' })
}

function navigateToTaxReports() {
  router.push('/admin/reports')
}
</script>

// LLM-CHECKPOINT