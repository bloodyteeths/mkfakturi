<template>
  <BaseCard>
    <template #header>
      <h3 class="text-lg font-semibold text-gray-900">
        {{ $t('dashboard.quick_actions') || 'Quick Actions' }}
      </h3>
    </template>

    <!-- Grid of Quick Action Buttons -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
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
        v-if="userStore.hasAbilities(abilities.CREATE_ITEM)"
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
        v-if="userStore.hasAbilities(abilities.VIEW_FINANCIAL_REPORT)"
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

      <!-- Stock Receipt -->
      <router-link
        v-if="userStore.hasAbilities(abilities.CREATE_ITEM)"
        to="/admin/stock/documents/create?type=receipt"
        class="group"
      >
        <div class="flex items-center p-4 border-2 border-gray-200 rounded-lg hover:border-primary-500 hover:shadow-md transition-all duration-200 cursor-pointer min-h-[60px]">
          <div class="flex-shrink-0">
            <div class="h-10 w-10 rounded-full bg-emerald-100 flex items-center justify-center group-hover:bg-emerald-200">
              <BaseIcon name="ArrowDownOnSquareIcon" class="h-6 w-6 text-emerald-600" />
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-900 group-hover:text-emerald-600">
              {{ $t('stock.new_receipt') || 'New Stock Receipt' }}
            </p>
            <p class="text-xs text-gray-500 hidden sm:block">
              {{ $t('dashboard.create_receipt_desc') || 'Record goods received' }}
            </p>
          </div>
        </div>
      </router-link>

      <!-- Payroll -->
      <router-link
        v-if="userStore.hasAbilities(abilities.VIEW_FINANCIAL_REPORT)"
        to="/admin/payroll"
        class="group"
      >
        <div class="flex items-center p-4 border-2 border-gray-200 rounded-lg hover:border-primary-500 hover:shadow-md transition-all duration-200 cursor-pointer min-h-[60px]">
          <div class="flex-shrink-0">
            <div class="h-10 w-10 rounded-full bg-amber-100 flex items-center justify-center group-hover:bg-amber-200">
              <BaseIcon name="BanknotesIcon" class="h-6 w-6 text-amber-600" />
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-900 group-hover:text-amber-600">
              {{ $t('payroll.payroll') || 'Payroll' }}
            </p>
            <p class="text-xs text-gray-500 hidden sm:block">
              {{ $t('dashboard.payroll_desc') || 'Run monthly payroll' }}
            </p>
          </div>
        </div>
      </router-link>

      <!-- UJP Forms -->
      <router-link
        v-if="userStore.hasAbilities(abilities.VIEW_FINANCIAL_REPORT)"
        to="/admin/accounting/ujp-forms"
        class="group"
      >
        <div class="flex items-center p-4 border-2 border-gray-200 rounded-lg hover:border-primary-500 hover:shadow-md transition-all duration-200 cursor-pointer min-h-[60px]">
          <div class="flex-shrink-0">
            <div class="h-10 w-10 rounded-full bg-red-100 flex items-center justify-center group-hover:bg-red-200">
              <BaseIcon name="DocumentTextIcon" class="h-6 w-6 text-red-600" />
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-900 group-hover:text-red-600">
              {{ $t('ujp_forms.title') || 'UJP Forms' }}
            </p>
            <p class="text-xs text-gray-500 hidden sm:block">
              {{ $t('dashboard.ujp_forms_desc') || 'Tax declarations' }}
            </p>
          </div>
        </div>
      </router-link>

      <!-- Travel Order -->
      <router-link
        v-if="userStore.hasAbilities(abilities.CREATE_EXPENSE)"
        to="/admin/travel-orders/create"
        class="group"
      >
        <div class="flex items-center p-4 border-2 border-gray-200 rounded-lg hover:border-primary-500 hover:shadow-md transition-all duration-200 cursor-pointer min-h-[60px]">
          <div class="flex-shrink-0">
            <div class="h-10 w-10 rounded-full bg-cyan-100 flex items-center justify-center group-hover:bg-cyan-200">
              <BaseIcon name="TruckIcon" class="h-6 w-6 text-cyan-600" />
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-900 group-hover:text-cyan-600">
              {{ $t('travel.new_order') || 'Travel Order' }}
            </p>
            <p class="text-xs text-gray-500 hidden sm:block">
              {{ $t('dashboard.travel_order_desc') || 'Create travel order' }}
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

