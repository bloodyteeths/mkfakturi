<template>
  <BasePage class="xl:pl-96">
    <BasePageHeader :title="pageTitle">
      <template #actions>
        <RecurringInvoiceIndexDropdown
          v-if="hasAtleastOneAbility()"
          :row="recurringInvoiceStore.newRecurringInvoice"
        />
      </template>
    </BasePageHeader>

    <RecurringInvoiceViewSidebar />

    <RecurringInvoiceInfo />
  </BasePage>
</template>

<script setup>
import { computed } from 'vue'
import { useUserStore } from '@/scripts/admin/stores/user'
import { useRecurringInvoiceStore } from '@/scripts/admin/stores/recurring-invoice'
import abilities from '@/scripts/admin/stub/abilities'

import RecurringInvoiceViewSidebar from '@/scripts/admin/views/recurring-invoices/partials/RecurringInvoiceViewSidebar.vue'
import RecurringInvoiceInfo from '@/scripts/admin/views/recurring-invoices/partials/RecurringInvoiceInfo.vue'
import RecurringInvoiceIndexDropdown from '@/scripts/admin/components/dropdowns/RecurringInvoiceIndexDropdown.vue'

const recurringInvoiceStore = useRecurringInvoiceStore()
const userStore = useUserStore()

const pageTitle = computed(() => {
  return recurringInvoiceStore.newRecurringInvoice
    ? recurringInvoiceStore.newRecurringInvoice?.customer?.name
    : ''
})

function hasAtleastOneAbility() {
  return userStore.hasAbilities([
    abilities.DELETE_RECURRING_INVOICE,
    abilities.EDIT_RECURRING_INVOICE,
  ])
}
</script>
