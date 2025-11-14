<template>
  <BaseCard>
    <template #header>
      <h3 class="text-lg font-semibold text-gray-900">
        {{ $t('dashboard.quick_actions') || 'Quick Actions' }}
      </h3>
    </template>

    <!-- Grid of Quick Action Buttons -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <!-- New Invoice -->
      <router-link
        v-if="userStore.hasAbilities(abilities.CREATE_INVOICE)"
        to="/admin/invoices/create"
        class="group"
      >
        <div
          class="
            flex items-center p-4 border-2 border-gray-200 rounded-lg
            hover:border-primary-500 hover:shadow-md
            transition-all duration-200 cursor-pointer
            min-h-[60px]
          "
        >
          <div class="flex-shrink-0">
            <div class="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center group-hover:bg-primary-200">
              <BaseIcon name="DocumentPlusIcon" class="h-6 w-6 text-primary-600" />
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-900 group-hover:text-primary-600">
              {{ $t('invoices.new_invoice') || 'New Invoice' }}
            </p>
            <p class="text-xs text-gray-500 hidden sm:block">
              {{ $t('dashboard.create_invoice_desc') || 'Create new invoice' }}
            </p>
          </div>
        </div>
      </router-link>

      <!-- New Customer -->
      <router-link
        v-if="userStore.hasAbilities(abilities.CREATE_CUSTOMER)"
        to="/admin/customers/create"
        class="group"
      >
        <div
          class="
            flex items-center p-4 border-2 border-gray-200 rounded-lg
            hover:border-primary-500 hover:shadow-md
            transition-all duration-200 cursor-pointer
            min-h-[60px]
          "
        >
          <div class="flex-shrink-0">
            <div class="h-10 w-10 rounded-full bg-teal-100 flex items-center justify-center group-hover:bg-teal-200">
              <BaseIcon name="UserPlusIcon" class="h-6 w-6 text-teal-600" />
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-900 group-hover:text-teal-600">
              {{ $t('customers.new_customer') || 'New Customer' }}
            </p>
            <p class="text-xs text-gray-500 hidden sm:block">
              {{ $t('dashboard.create_customer_desc') || 'Add new customer' }}
            </p>
          </div>
        </div>
      </router-link>

      <!-- Import Data -->
      <router-link
        v-if="userStore.hasAbilities(abilities.MANAGE_COMPANY)"
        to="/admin/imports"
        class="group"
      >
        <div
          class="
            flex items-center p-4 border-2 border-gray-200 rounded-lg
            hover:border-primary-500 hover:shadow-md
            transition-all duration-200 cursor-pointer
            min-h-[60px]
          "
        >
          <div class="flex-shrink-0">
            <div class="h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center group-hover:bg-purple-200">
              <BaseIcon name="ArrowDownTrayIcon" class="h-6 w-6 text-purple-600" />
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-900 group-hover:text-purple-600">
              {{ $t('imports.import_data') || 'Import Data' }}
            </p>
            <p class="text-xs text-gray-500 hidden sm:block">
              {{ $t('dashboard.import_data_desc') || 'Import from CSV' }}
            </p>
          </div>
        </div>
      </router-link>

      <!-- View Reports -->
      <router-link
        v-if="userStore.hasAbilities(abilities.VIEW_REPORT)"
        to="/admin/reports"
        class="group"
      >
        <div
          class="
            flex items-center p-4 border-2 border-gray-200 rounded-lg
            hover:border-primary-500 hover:shadow-md
            transition-all duration-200 cursor-pointer
            min-h-[60px]
          "
        >
          <div class="flex-shrink-0">
            <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center group-hover:bg-blue-200">
              <BaseIcon name="ChartBarIcon" class="h-6 w-6 text-blue-600" />
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-900 group-hover:text-blue-600">
              {{ $t('reports.reports') || 'Reports' }}
            </p>
            <p class="text-xs text-gray-500 hidden sm:block">
              {{ $t('dashboard.view_reports_desc') || 'View business reports' }}
            </p>
          </div>
        </div>
      </router-link>
    </div>
  </BaseCard>
</template>

<script setup>
import { useUserStore } from '@/scripts/admin/stores/user'
import abilities from '@/scripts/admin/stub/abilities'

const userStore = useUserStore()
</script>

// CLAUDE-CHECKPOINT
