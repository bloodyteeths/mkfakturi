<template>
  <BasePage>
    <BasePageHeader :title="pageTitle">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('suppliers.title')" to="/admin/suppliers" />
        <BaseBreadcrumbItem
          :title="supplier?.name || $t('suppliers.view_supplier')"
          to="#"
          active
        />
      </BaseBreadcrumb>

      <template #actions>
        <router-link
          v-if="userStore.hasAbilities(abilities.EDIT_SUPPLIER)"
          :to="`/admin/suppliers/${route.params.id}/edit`"
        >
          <BaseButton
            class="mr-3"
            variant="primary-outline"
            :content-loading="isLoading"
          >
            {{ $t('general.edit') }}
          </BaseButton>
        </router-link>

        <SupplierDropdown
          v-if="hasAtleastOneAbility()"
          :class="{
            'ml-3': isLoading,
          }"
          :row="supplier || {}"
          :load-data="refreshData"
        />
      </template>
    </BasePageHeader>

    <!-- Statistics Cards -->
    <div v-if="supplier" class="grid grid-cols-1 gap-4 mt-6 md:grid-cols-3">
      <BaseCard>
        <div class="p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs text-gray-500 uppercase">{{ $t('dashboard.chart_info.total_sales') }}</p>
              <p class="mt-1 text-2xl font-semibold text-gray-900">
                <BaseFormatMoney
                  :amount="supplierStats.totalPurchases"
                  :currency="supplier.currency"
                />
              </p>
            </div>
            <div class="p-3 bg-blue-100 rounded-full">
              <BaseIcon name="ShoppingCartIcon" class="w-6 h-6 text-blue-600" />
            </div>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs text-gray-500 uppercase">{{ $t('dashboard.chart_info.total_receipts') }}</p>
              <p class="mt-1 text-2xl font-semibold text-green-600">
                <BaseFormatMoney
                  :amount="supplierStats.totalPayments"
                  :currency="supplier.currency"
                />
              </p>
            </div>
            <div class="p-3 bg-green-100 rounded-full">
              <BaseIcon name="CreditCardIcon" class="w-6 h-6 text-green-600" />
            </div>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs text-gray-500 uppercase">{{ $t('customers.amount_due') }}</p>
              <p class="mt-1 text-2xl font-semibold text-red-600">
                <BaseFormatMoney
                  :amount="supplierStats.totalDue"
                  :currency="supplier.currency"
                />
              </p>
            </div>
            <div class="p-3 bg-red-100 rounded-full">
              <BaseIcon name="ExclamationTriangleIcon" class="w-6 h-6 text-red-600" />
            </div>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- Main Content Card -->
    <BaseCard v-if="supplier" class="mt-6">
      <!-- Basic Info Section -->
      <div class="pt-6 mt-5 border-t border-solid border-gray-200">
        <BaseHeading>{{ $t('customers.basic_info') }}</BaseHeading>

        <div class="grid grid-cols-1 gap-6 mt-5 md:grid-cols-2">
          <BaseDescriptionList>
            <BaseDescriptionListItem
              :label="$t('suppliers.name')"
              :content-loading="isLoading"
            >
              {{ supplier.name }}
            </BaseDescriptionListItem>
            <BaseDescriptionListItem
              :label="$t('suppliers.email')"
              :content-loading="isLoading"
            >
              {{ supplier.email || '-' }}
            </BaseDescriptionListItem>
            <BaseDescriptionListItem
              :label="$t('suppliers.tax_id')"
              :content-loading="isLoading"
            >
              {{ supplier.tax_id || '-' }}
            </BaseDescriptionListItem>
          </BaseDescriptionList>

          <BaseDescriptionList>
            <BaseDescriptionListItem
              :label="$t('wizard.currency')"
              :content-loading="isLoading"
            >
              {{
                supplier.currency
                  ? `${supplier.currency.code} (${supplier.currency.symbol})`
                  : '-'
              }}
            </BaseDescriptionListItem>
            <BaseDescriptionListItem
              :label="$t('suppliers.phone')"
              :content-loading="isLoading"
            >
              {{ supplier.phone || '-' }}
            </BaseDescriptionListItem>
            <BaseDescriptionListItem
              :label="$t('suppliers.website')"
              :content-loading="isLoading"
            >
              <a
                v-if="supplier.website"
                :href="supplier.website"
                target="_blank"
                rel="noopener noreferrer"
                class="text-primary-500 hover:underline"
              >
                {{ supplier.website }}
              </a>
              <span v-else>-</span>
            </BaseDescriptionListItem>
          </BaseDescriptionList>
        </div>
      </div>

      <!-- Address Section -->
      <div v-if="hasAddress" class="pt-6 mt-8 border-t border-gray-200">
        <BaseHeading>{{ $t('customers.address') }}</BaseHeading>
        <BaseDescriptionList class="mt-5">
          <BaseDescriptionListItem
            :label="$t('customers.billing_address')"
            :content-loading="isLoading"
          >
            <div v-if="supplier.address_street_1">
              <p>{{ supplier.address_street_1 }}</p>
              <p v-if="supplier.address_street_2">{{ supplier.address_street_2 }}</p>
              <p>
                <span v-if="supplier.city">{{ supplier.city }}</span>
                <span v-if="supplier.state">, {{ supplier.state }}</span>
                <span v-if="supplier.zip"> {{ supplier.zip }}</span>
              </p>
              <p v-if="supplier.country">{{ supplier.country }}</p>
            </div>
            <span v-else>-</span>
          </BaseDescriptionListItem>
        </BaseDescriptionList>
      </div>
    </BaseCard>
  </BasePage>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useSuppliersStore } from '@/scripts/admin/stores/suppliers'
import { useUserStore } from '@/scripts/admin/stores/user'
import SupplierDropdown from '@/scripts/admin/components/dropdowns/SupplierIndexDropdown.vue'
import abilities from '@/scripts/admin/stub/abilities'

const route = useRoute()
const router = useRouter()
const suppliersStore = useSuppliersStore()
const userStore = useUserStore()

const supplier = computed(() => suppliersStore.selectedSupplier)

const pageTitle = computed(() => {
  return supplier.value ? supplier.value.name : ''
})

const isLoading = computed(() => {
  return suppliersStore.isFetchingView || false
})

const hasAddress = computed(() => {
  if (!supplier.value) return false
  return !!(
    supplier.value.address_street_1 ||
    supplier.value.address_street_2 ||
    supplier.value.city ||
    supplier.value.state ||
    supplier.value.zip ||
    supplier.value.country
  )
})

const supplierStats = computed(() => {
  if (!supplier.value) {
    return {
      totalPurchases: 0,
      totalPayments: 0,
      totalDue: 0,
    }
  }

  // Calculate stats from bills if available
  const bills = supplier.value.bills || []
  const totalPurchases = bills.reduce((sum, bill) => sum + (bill.total || 0), 0)
  const totalPayments = bills.reduce((sum, bill) => sum + (bill.paid_amount || 0), 0)
  const totalDue = totalPurchases - totalPayments

  return {
    totalPurchases,
    totalPayments,
    totalDue,
  }
})

function hasAtleastOneAbility() {
  return userStore.hasAbilities([
    abilities.DELETE_SUPPLIER,
    abilities.EDIT_SUPPLIER,
  ])
}

function refreshData() {
  router.push('/admin/suppliers')
}

onMounted(() => {
  suppliersStore.fetchSupplier(route.params.id)
})
</script>
// CLAUDE-CHECKPOINT

