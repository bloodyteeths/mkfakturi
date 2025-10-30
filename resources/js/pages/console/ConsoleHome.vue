<template>
  <BasePage>
    <BasePageHeader title="Accountant Console">
      <template #actions>
        <BaseButton
          v-if="consoleStore.companies.length > 1"
          variant="white"
          @click="showCompanySwitcher = true"
        >
          <template #left>
            <BuildingOfficeIcon class="h-5 w-5" />
          </template>
          Switch Company
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Current Company Info -->
    <div v-if="consoleStore.currentCompany" class="mb-8">
      <BaseCard class="p-6">
        <div class="flex items-center space-x-4">
          <div class="flex-shrink-0">
            <img
              v-if="consoleStore.currentCompany.logo"
              :src="consoleStore.currentCompany.logo"
              :alt="consoleStore.currentCompany.name"
              class="h-16 w-16 rounded-lg object-cover"
            />
            <div
              v-else
              class="h-16 w-16 rounded-lg bg-gray-200 flex items-center justify-center"
            >
              <BuildingOfficeIcon class="h-8 w-8 text-gray-400" />
            </div>
          </div>
          <div class="flex-1">
            <h2 class="text-xl font-semibold text-gray-900">
              {{ consoleStore.currentCompany.name }}
            </h2>
            <p class="text-sm text-gray-500">
              Commission Rate: {{ consoleStore.currentCompany.commission_rate }}%
            </p>
            <div v-if="consoleStore.currentCompany.address" class="text-sm text-gray-500 mt-1">
              {{ consoleStore.currentCompany.address.city }}, {{ consoleStore.currentCompany.address.country }}
            </div>
          </div>
          <div class="flex-shrink-0">
            <BaseBadge
              v-if="consoleStore.currentCompany.is_primary"
              variant="success"
            >
              Primary
            </BaseBadge>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- Companies Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
      <BaseCard
        v-for="company in consoleStore.companies"
        :key="company.id"
        class="cursor-pointer hover:shadow-lg transition-shadow duration-200"
        @click="switchToCompany(company)"
      >
        <div class="p-6">
          <div class="flex items-center space-x-3 mb-4">
            <img
              v-if="company.logo"
              :src="company.logo"
              :alt="company.name"
              class="h-10 w-10 rounded object-cover"
            />
            <div
              v-else
              class="h-10 w-10 rounded bg-gray-200 flex items-center justify-center"
            >
              <BuildingOfficeIcon class="h-5 w-5 text-gray-400" />
            </div>
            <div class="flex-1">
              <h3 class="font-medium text-gray-900">{{ company.name }}</h3>
              <p class="text-sm text-gray-500">{{ company.commission_rate }}% commission</p>
            </div>
            <BaseBadge
              v-if="company.is_primary"
              variant="success"
              size="sm"
            >
              Primary
            </BaseBadge>
          </div>
          
          <div v-if="company.address" class="text-sm text-gray-600 mb-3">
            {{ company.address.city }}, {{ company.address.country }}
          </div>
          
          <div class="flex justify-between items-center text-sm">
            <span class="text-gray-500">
              {{ company.permissions?.length || 0 }} permissions
            </span>
            <BaseButton
              size="sm"
              variant="primary-outline"
              @click.stop="switchToCompany(company)"
            >
              Manage
            </BaseButton>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- Empty State -->
    <div v-if="!consoleStore.isLoading && consoleStore.companies.length === 0" class="text-center py-12">
      <BuildingOfficeIcon class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">No companies assigned</h3>
      <p class="mt-1 text-sm text-gray-500">
        Contact your administrator to get access to company accounts.
      </p>
    </div>

    <!-- Loading State -->
    <div v-if="consoleStore.isLoading" class="text-center py-12">
      <BaseSpinner class="mx-auto" />
      <p class="mt-2 text-sm text-gray-500">Loading companies...</p>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import { BuildingOfficeIcon } from '@heroicons/vue/24/outline'
import BasePage from '@/scripts/components/base/BasePage.vue'
import BasePageHeader from '@/scripts/components/base/BasePageHeader.vue'
import BaseCard from '@/scripts/components/base/BaseCard.vue'
import BaseButton from '@/scripts/components/base/BaseButton.vue'
import BaseBadge from '@/scripts/components/base/BaseBadge.vue'
import BaseSpinner from '@/scripts/components/base/BaseSpinner.vue'

const consoleStore = useConsoleStore()
const showCompanySwitcher = ref(false)

onMounted(async () => {
  await consoleStore.fetchCompanies()
})

const switchToCompany = async (company) => {
  try {
    await consoleStore.switchCompany(company.id)
    // Redirect to company-specific dashboard or stay on console
    // This can be enhanced based on requirements
  } catch (error) {
    console.error('Failed to switch company:', error)
  }
}
</script>

