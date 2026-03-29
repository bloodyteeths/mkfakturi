<template>
  <BasePage class="relative">
    <BasePageHeader :title="pageTitle">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="/admin/dashboard" />
        <BaseBreadcrumbItem :title="$t('expenses.expense', 2)" to="/admin/expenses" />
        <BaseBreadcrumbItem :title="pageTitle" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <ExpenseDropdown
          v-if="expense"
          :row="expense"
          :load-data="() => {}"
        />

        <BaseButton
          v-if="userStore.hasAbilities(abilities.EDIT_EXPENSE)"
          variant="primary"
          class="ml-2"
          @click="$router.push(`/admin/expenses/${route.params.id}/edit`)"
        >
          <template #left="slotProps">
            <BaseIcon name="PencilIcon" :class="slotProps.class" />
          </template>
          {{ $t('general.edit') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <div v-if="isLoading" class="flex justify-center py-20">
      <svg class="h-6 w-6 animate-spin text-primary-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
      </svg>
    </div>

    <div v-else-if="expense" class="grid grid-cols-1 gap-6 mt-6 lg:grid-cols-2">
      <!-- Left Column: Details -->
      <BaseCard>
        <div class="grid grid-cols-2 gap-y-4 gap-x-6 p-4">
          <!-- Status Badge -->
          <div class="col-span-2 flex items-center gap-2 mb-2">
            <span
              :class="[
                'px-3 py-1 text-sm font-medium rounded-full',
                expense.status === 'posted' ? 'bg-green-100 text-green-800' :
                expense.status === 'approved' ? 'bg-blue-100 text-blue-800' :
                'bg-gray-100 text-gray-800'
              ]"
            >
              {{
                expense.status === 'posted' ? $t('general.posted') :
                expense.status === 'approved' ? $t('general.approved') :
                $t('general.draft')
              }}
            </span>
            <span class="text-sm text-gray-500">{{ expense.expense_number }}</span>
          </div>

          <div>
            <p class="text-xs text-gray-500">{{ $t('expenses.date') }}</p>
            <p class="font-medium">{{ expense.formatted_expense_date }}</p>
          </div>

          <div>
            <p class="text-xs text-gray-500">{{ $t('expenses.category') }}</p>
            <p class="font-medium">{{ expense.expense_category?.name || '-' }}</p>
          </div>

          <div>
            <p class="text-xs text-gray-500">{{ $t('expenses.supplier') }}</p>
            <p class="font-medium">{{ expense.supplier?.name || '-' }}</p>
          </div>

          <div>
            <p class="text-xs text-gray-500">{{ $t('expenses.invoice_number') }}</p>
            <p class="font-medium">{{ expense.invoice_number || '-' }}</p>
          </div>

          <div>
            <p class="text-xs text-gray-500">{{ $t('expenses.customer') }}</p>
            <p class="font-medium">{{ expense.customer?.name || '-' }}</p>
          </div>

          <div>
            <p class="text-xs text-gray-500">{{ $t('payments.payment_mode') }}</p>
            <p class="font-medium">{{ expense.payment_method?.name || '-' }}</p>
          </div>

          <div class="col-span-2" v-if="expense.notes">
            <p class="text-xs text-gray-500">{{ $t('expenses.note') }}</p>
            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ expense.notes }}</p>
          </div>
        </div>
      </BaseCard>

      <!-- Right Column: Amounts -->
      <BaseCard>
        <div class="space-y-4 p-4">
          <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">
            {{ $t('expenses.financial_details') }}
          </h3>

          <div class="flex justify-between items-center">
            <span class="text-gray-600">{{ $t('expenses.tax_base') }}</span>
            <BaseFormatMoney
              :amount="computedTaxBase"
              :currency="expense.currency"
            />
          </div>

          <div class="flex justify-between items-center">
            <span class="text-gray-600">
              {{ $t('expenses.vat_amount') }} ({{ expense.vat_rate || 18 }}%)
            </span>
            <BaseFormatMoney
              :amount="computedVatAmount"
              :currency="expense.currency"
            />
          </div>

          <div class="flex justify-between items-center border-t pt-3">
            <span class="text-lg font-bold text-gray-800">{{ $t('expenses.amount') }}</span>
            <span class="text-lg font-bold">
              <BaseFormatMoney
                :amount="expense.amount"
                :currency="expense.currency"
              />
            </span>
          </div>

          <div v-if="expense.exchange_rate && expense.exchange_rate !== 1" class="flex justify-between items-center text-sm text-gray-500">
            <span>{{ $t('general.exchange_rate') }}: {{ expense.exchange_rate }}</span>
            <BaseFormatMoney
              :amount="expense.base_amount"
            />
          </div>

          <!-- Receipt -->
          <div v-if="expense.attachment_receipt_url" class="border-t pt-4 mt-4">
            <h4 class="text-sm font-medium text-gray-700 mb-2">{{ $t('expenses.receipt') }}</h4>
            <a
              :href="`/reports/expenses/${expense.id}/download-receipt`"
              class="inline-flex items-center text-primary-500 hover:text-primary-600"
            >
              <BaseIcon name="DownloadIcon" class="h-4 mr-1" />
              {{ $t('expenses.download_receipt') }}
            </a>
          </div>

          <!-- PDF Download -->
          <div class="border-t pt-4 mt-4">
            <h4 class="text-sm font-medium text-gray-700 mb-2">{{ $t('general.documents') }}</h4>
            <a
              :href="`/api/v1/expenses/${expense.id}/rashoden-nalog`"
              target="_blank"
              class="inline-flex items-center text-primary-500 hover:text-primary-600"
            >
              <BaseIcon name="DocumentArrowDownIcon" class="h-4 mr-1" />
              {{ $t('expenses.rashoden_nalog') }}
            </a>
          </div>
        </div>
      </BaseCard>

      <!-- Actions Bar (below cards) -->
      <div v-if="expense.status !== 'posted'" class="col-span-1 lg:col-span-2 flex gap-3">
        <BaseButton
          v-if="expense.status === 'draft' && userStore.hasAbilities(abilities.EDIT_EXPENSE)"
          variant="primary-outline"
          @click="approveExpense"
        >
          <template #left="slotProps">
            <BaseIcon name="CheckCircleIcon" :class="slotProps.class" />
          </template>
          {{ $t('general.approve') }}
        </BaseButton>
        <BaseButton
          v-if="expense.status === 'approved' && userStore.hasAbilities(abilities.EDIT_EXPENSE)"
          variant="primary"
          @click="postExpense"
        >
          <template #left="slotProps">
            <BaseIcon name="BookOpenIcon" :class="slotProps.class" />
          </template>
          {{ $t('general.post') }}
        </BaseButton>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useExpenseStore } from '@/scripts/admin/stores/expense'
import { useUserStore } from '@/scripts/admin/stores/user'
import abilities from '@/scripts/admin/stub/abilities'
import ExpenseDropdown from '@/scripts/admin/components/dropdowns/ExpenseIndexDropdown.vue'

const route = useRoute()
const { t } = useI18n()
const expenseStore = useExpenseStore()
const userStore = useUserStore()

const expense = ref(null)
const isLoading = ref(true)

const pageTitle = computed(() => {
  const e = expense.value
  if (e?.expense_number) {
    return `${t('expenses.view_expense')} — ${e.expense_number}`
  }
  if (e?.id) {
    return `${t('expenses.view_expense')} #${e.id}`
  }
  return t('expenses.view_expense')
})

// Compute VAT on the fly for old expenses that have no VAT data
const computedTaxBase = computed(() => {
  const e = expense.value
  if (!e) return 0
  if (e.tax_base && e.tax_base > 0) return e.tax_base
  if (e.amount > 0 && e.vat_rate > 0) {
    return Math.round(e.amount / (1 + e.vat_rate / 100))
  }
  return e.amount || 0
})

const computedVatAmount = computed(() => {
  const e = expense.value
  if (!e) return 0
  if (e.vat_amount && e.vat_amount > 0) return e.vat_amount
  if (e.amount > 0 && e.vat_rate > 0) {
    return e.amount - computedTaxBase.value
  }
  return 0
})

onMounted(async () => {
  try {
    const response = await expenseStore.fetchExpense(route.params.id)
    expense.value = response.data.data
  } finally {
    isLoading.value = false
  }
})

async function approveExpense() {
  await expenseStore.approveExpense(route.params.id)
  expense.value = { ...expense.value, status: 'approved' }
}

async function postExpense() {
  await expenseStore.postExpense(route.params.id)
  expense.value = { ...expense.value, status: 'posted' }
}
</script>
