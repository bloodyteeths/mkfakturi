<template>
  <div class="bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-medium text-gray-900 mb-2">
      {{ t('partner.accounting.year_end.step3_title') }}
    </h3>
    <p class="text-sm text-gray-500 mb-6">
      {{ t('partner.accounting.year_end.step3_desc') }}
    </p>

    <!-- Entry Form -->
    <div class="border border-gray-200 rounded-lg p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <BaseInputGroup :label="t('partner.accounting.year_end.debit_account')">
          <AccountDropdown
            v-model="newEntry.debitAccountId"
            :accounts="store.accounts"
          />
        </BaseInputGroup>
        <BaseInputGroup :label="t('partner.accounting.year_end.credit_account')">
          <AccountDropdown
            v-model="newEntry.creditAccountId"
            :accounts="store.accounts"
          />
        </BaseInputGroup>
        <BaseInputGroup :label="t('partner.accounting.year_end.amount_mkd')">
          <BaseInput
            v-model="newEntry.amount"
            type="number"
            :placeholder="t('partner.accounting.year_end.amount_placeholder')"
          />
        </BaseInputGroup>
        <BaseInputGroup :label="t('partner.accounting.year_end.description')">
          <BaseInput
            v-model="newEntry.description"
            :placeholder="t('partner.accounting.year_end.description_placeholder', { year: new Date().getFullYear() - 1 })"
          />
        </BaseInputGroup>
      </div>
      <div class="mt-4">
        <BaseButton variant="primary-outline" @click="addEntry" :disabled="!isEntryValid">
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="PlusIcon" />
          </template>
          {{ t('partner.accounting.year_end.add_entry') }}
        </BaseButton>
      </div>
    </div>

    <!-- Added Entries List -->
    <div v-if="entries.length > 0" class="mb-6">
      <h4 class="text-sm font-medium text-gray-700 mb-3">{{ t('partner.accounting.year_end.added_entries') }}</h4>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('partner.accounting.year_end.description') }}</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('partner.accounting.year_end.debit') }}</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('partner.accounting.year_end.credit') }}</th>
              <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('partner.accounting.year_end.amount') }}</th>
              <th class="px-4 py-2"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="(entry, i) in entries" :key="i">
              <td class="px-4 py-2 text-sm text-gray-900">{{ entry.description }}</td>
              <td class="px-4 py-2 text-sm text-gray-600">{{ entry.debitDisplay }}</td>
              <td class="px-4 py-2 text-sm text-gray-600">{{ entry.creditDisplay }}</td>
              <td class="px-4 py-2 text-sm text-right text-gray-900">{{ formatMoney(entry.amount) }}</td>
              <td class="px-4 py-2 text-right">
                <button class="text-red-500 hover:text-red-700" @click="removeEntry(i)">
                  <BaseIcon name="TrashIcon" class="h-4 w-4" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Skip message -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
      <p class="text-sm text-blue-700">
        {{ t('partner.accounting.year_end.skip_message') }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useYearEndClosingStore } from '@/scripts/admin/stores/year-end-closing'
import AccountDropdown from '@/scripts/admin/components/accounting/AccountDropdown.vue'

const { t } = useI18n()
const store = useYearEndClosingStore()
const entries = ref([])

const newEntry = ref({
  debitAccountId: null,
  creditAccountId: null,
  amount: '',
  description: '',
})

const isEntryValid = computed(() => {
  return newEntry.value.debitAccountId && newEntry.value.creditAccountId &&
    newEntry.value.amount && parseFloat(newEntry.value.amount) > 0
})

function getAccountDisplay(accountId) {
  const account = store.accounts.find((a) => a.id === accountId)
  if (!account) return String(accountId)
  return `${account.code} - ${account.name}`
}

function addEntry() {
  if (!isEntryValid.value) return
  entries.value.push({
    debitAccountId: newEntry.value.debitAccountId,
    creditAccountId: newEntry.value.creditAccountId,
    debitDisplay: getAccountDisplay(newEntry.value.debitAccountId),
    creditDisplay: getAccountDisplay(newEntry.value.creditAccountId),
    amount: parseFloat(newEntry.value.amount),
    description: newEntry.value.description,
  })
  newEntry.value = { debitAccountId: null, creditAccountId: null, amount: '', description: '' }
}

function removeEntry(index) {
  entries.value.splice(index, 1)
}

function formatMoney(amount) {
  if (!amount) return '-'
  return new Intl.NumberFormat('mk-MK', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(amount) + ' МКД'
}

onMounted(() => {
  if (store.accounts.length === 0) {
    store.fetchAccounts()
  }
})
</script>
