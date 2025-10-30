<template>
  <div>
    <div class="grid grid-cols-1 gap-6 mt-10 lg:grid-cols-2">
      <!-- Due Invoices -->
      <section
        v-if="userStore.hasAbilities(abilities.VIEW_INVOICE)"
        class="due-invoices"
        aria-labelledby="due-invoices-heading"
      >
        <div class="relative z-10 flex flex-col items-start justify-between mb-3 space-y-2 sm:flex-row sm:items-center sm:space-y-0">
          <h2 id="due-invoices-heading" class="mb-0 text-lg sm:text-xl font-semibold leading-normal">
            {{ $t('dashboard.recent_invoices_card.title') }}
          </h2>

          <BaseButton
            size="sm"
            variant="primary-outline"
            class="w-full sm:w-auto"
            :aria-label="$t('dashboard.recent_invoices_card.view_all') + ' - ' + $t('dashboard.recent_invoices_card.title')"
            @click="$router.push('/admin/invoices')"
          >
            {{ $t('dashboard.recent_invoices_card.view_all') }}
          </BaseButton>
        </div>

        <div class="overflow-x-auto" role="region" aria-label="Due invoices table">
          <BaseTable
            :data="dashboardStore.recentDueInvoices"
            :columns="dueInvoiceColumns"
            :loading="!dashboardStore.isDashboardDataLoaded"
            class="min-w-full"
            role="table"
            aria-label="Recent due invoices"
          >
          <template #cell-user="{ row }">
            <router-link
              :to="{ path: `invoices/${row.data.id}/view` }"
              class="font-medium text-primary-500"
              :aria-label="`View invoice for ${row.data.customer.name}`"
            >
              {{ row.data.customer.name }}
            </router-link>
          </template>

          <template #cell-due_amount="{ row }">
            <BaseFormatMoney
              :amount="row.data.due_amount"
              :currency="row.data.customer.currency"
            />
          </template>

          <!-- Actions -->
          <template
            v-if="hasAtleastOneInvoiceAbility()"
            #cell-actions="{ row }"
          >
            <InvoiceDropdown :row="row.data" :table="invoiceTableComponent" />
          </template>
          </BaseTable>
        </div>
      </section>

      <!-- Recent Estimates -->
      <section
        v-if="userStore.hasAbilities(abilities.VIEW_ESTIMATE)"
        class="recent-estimates"
        aria-labelledby="recent-estimates-heading"
      >
        <div class="relative z-10 flex flex-col items-start justify-between mb-3 space-y-2 sm:flex-row sm:items-center sm:space-y-0">
          <h2 id="recent-estimates-heading" class="mb-0 text-lg sm:text-xl font-semibold leading-normal">
            {{ $t('dashboard.recent_estimate_card.title') }}
          </h2>

          <BaseButton
            variant="primary-outline"
            size="sm"
            class="w-full sm:w-auto"
            :aria-label="$t('dashboard.recent_estimate_card.view_all') + ' - ' + $t('dashboard.recent_estimate_card.title')"
            @click="$router.push('/admin/estimates')"
          >
            {{ $t('dashboard.recent_estimate_card.view_all') }}
          </BaseButton>
        </div>

        <div class="overflow-x-auto" role="region" aria-label="Recent estimates table">
          <BaseTable
            :data="dashboardStore.recentEstimates"
            :columns="recentEstimateColumns"
            :loading="!dashboardStore.isDashboardDataLoaded"
            class="min-w-full"
            role="table"
            aria-label="Recent estimates"
          >
          <template #cell-user="{ row }">
            <router-link
              :to="{ path: `estimates/${row.data.id}/view` }"
              class="font-medium text-primary-500"
              :aria-label="`View estimate for ${row.data.customer.name}`"
            >
              {{ row.data.customer.name }}
            </router-link>
          </template>

          <template #cell-total="{ row }">
            <BaseFormatMoney
              :amount="row.data.total"
              :currency="row.data.customer.currency"
            />
          </template>

          <template
            v-if="hasAtleastOneEstimateAbility()"
            #cell-actions="{ row }"
          >
            <EstimateDropdown :row="row.data" :table="estimateTableComponent" />
          </template>
          </BaseTable>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useDashboardStore } from '@/scripts/admin/stores/dashboard'
import { useI18n } from 'vue-i18n'
import { useUserStore } from '@/scripts/admin/stores/user'
import abilities from '@/scripts/admin/stub/abilities'
import InvoiceDropdown from '@/scripts/admin/components/dropdowns/InvoiceIndexDropdown.vue'
import EstimateDropdown from '@/scripts/admin/components/dropdowns/EstimateIndexDropdown.vue'

const dashboardStore = useDashboardStore()

const { t } = useI18n()
const userStore = useUserStore()

const invoiceTableComponent = ref(null)
const estimateTableComponent = ref(null)

const dueInvoiceColumns = computed(() => {
  return [
    {
      key: 'formattedDueDate',
      label: t('dashboard.recent_invoices_card.due_on'),
    },
    {
      key: 'user',
      label: t('dashboard.recent_invoices_card.customer'),
    },
    {
      key: 'due_amount',
      label: t('dashboard.recent_invoices_card.amount_due'),
    },
    {
      key: 'actions',
      tdClass: 'text-right text-sm font-medium pl-0',
      thClass: 'text-right pl-0',
      sortable: false,
    },
  ]
})

const recentEstimateColumns = computed(() => {
  return [
    {
      key: 'formattedEstimateDate',
      label: t('dashboard.recent_estimate_card.date'),
    },
    {
      key: 'user',
      label: t('dashboard.recent_estimate_card.customer'),
    },
    {
      key: 'total',
      label: t('dashboard.recent_estimate_card.amount_due'),
    },
    {
      key: 'actions',
      tdClass: 'text-right text-sm font-medium pl-0',
      thClass: 'text-right pl-0',
      sortable: false,
    },
  ]
})

function hasAtleastOneInvoiceAbility() {
  return userStore.hasAbilities([
    abilities.DELETE_INVOICE,
    abilities.EDIT_INVOICE,
    abilities.VIEW_INVOICE,
    abilities.SEND_INVOICE,
  ])
}

function hasAtleastOneEstimateAbility() {
  return userStore.hasAbilities([
    abilities.CREATE_ESTIMATE,
    abilities.EDIT_ESTIMATE,
    abilities.VIEW_ESTIMATE,
    abilities.SEND_ESTIMATE,
  ])
}
</script>
