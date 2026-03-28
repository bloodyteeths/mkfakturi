<template>
  <BasePage>
    <BasePageHeader :title="$t('ios_title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('ios_title')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
      <BaseInputGroup label="Тип">
        <BaseMultiselect
          v-model="partnerType"
          :options="[{ id: 'customer', name: $t('customer_ios') }, { id: 'supplier', name: $t('supplier_ios') }]"
          value-prop="id"
          label="name"
          track-by="name"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('general.partner') || 'Партнер'">
        <BaseCustomerSelectInput
          v-if="partnerType === 'customer'"
          v-model="partnerId"
          value-prop="id"
          label="name"
        />
        <BaseSupplierSelectInput
          v-else
          v-model="partnerId"
          value-prop="id"
          label="name"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('ios_as_of_date')">
        <BaseInput v-model="asOfDate" type="date" />
      </BaseInputGroup>

      <div class="flex items-end gap-2">
        <BaseButton variant="primary" @click="generate">
          {{ $t('generate_report') }}
        </BaseButton>
        <BaseButton v-if="items.length" variant="primary-outline" @click="downloadPdf">
          {{ $t('download_pdf') }}
        </BaseButton>
      </div>
    </div>

    <div v-if="loading" class="mt-8 text-center text-gray-500">
      <BaseIcon name="ArrowPathIcon" class="animate-spin h-6 w-6 mx-auto" />
    </div>

    <div v-if="generated && !loading" class="mt-6">
      <div class="mb-4 p-4 bg-gray-50 rounded-lg">
        <h3 class="font-bold text-lg">{{ partnerName }}</h3>
        <p class="text-sm text-gray-500">{{ $t('ios_as_of_date') }}: {{ asOfDate }}</p>
      </div>

      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('payments.date') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('general.document') }}</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('debit_label') }}</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('credit_label') }}</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('general.balance') || 'Салдо' }}</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="(item, idx) in items" :key="idx" class="hover:bg-gray-50">
            <td class="px-4 py-2 text-sm text-gray-500">{{ idx + 1 }}</td>
            <td class="px-4 py-2 text-sm">{{ item.date }}</td>
            <td class="px-4 py-2 text-sm">{{ item.document }}</td>
            <td class="px-4 py-2 text-sm text-right">{{ item.debit ? formatAmount(item.debit) : '' }}</td>
            <td class="px-4 py-2 text-sm text-right">{{ item.credit ? formatAmount(item.credit) : '' }}</td>
            <td class="px-4 py-2 text-sm text-right font-medium">{{ formatAmount(item.running_balance) }}</td>
          </tr>
        </tbody>
        <tfoot class="bg-gray-100 font-bold">
          <tr>
            <td colspan="3" class="px-4 py-3 text-right text-sm">{{ $t('general.total') }}:</td>
            <td class="px-4 py-3 text-right text-sm">{{ formatAmount(totalDebit) }}</td>
            <td class="px-4 py-3 text-right text-sm">{{ formatAmount(totalCredit) }}</td>
            <td class="px-4 py-3 text-right text-sm text-primary-600">{{ formatAmount(netBalance) }}</td>
          </tr>
        </tfoot>
      </table>

      <div class="mt-4 p-4 border rounded-lg" :class="netBalance >= 0 ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'">
        <p class="font-bold">
          {{ netBalance >= 0 ? $t('balance_in_our_favor') : $t('balance_in_your_favor') }}:
          {{ formatAmount(Math.abs(netBalance)) }} МКД
        </p>
        <p class="text-sm text-gray-500 mt-1">{{ $t('confirm_balance_notice') }}</p>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref } from 'vue'

const partnerType = ref('customer')
const partnerId = ref('')
const asOfDate = ref(new Date().toISOString().split('T')[0])
const loading = ref(false)
const generated = ref(false)
const items = ref([])
const partnerName = ref('')
const totalDebit = ref(0)
const totalCredit = ref(0)
const netBalance = ref(0)

function formatAmount(cents) {
  return new Intl.NumberFormat('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Math.abs(cents) / 100)
}

async function generate() {
  if (!partnerId.value || !asOfDate.value) return
  loading.value = true
  try {
    const res = await window.axios.get(`/reports/ios/${partnerType.value}/${partnerId.value}`, {
      params: { as_of_date: asOfDate.value }
    })
    const data = res.data
    items.value = data.items || []
    partnerName.value = data.partner_name || ''
    totalDebit.value = data.total_debit || 0
    totalCredit.value = data.total_credit || 0
    netBalance.value = data.net_balance || 0
    generated.value = true
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function downloadPdf() {
  const url = `/api/v1/reports/ios/${partnerType.value}/${partnerId.value}/pdf?as_of_date=${asOfDate.value}`
  window.open(url, '_blank')
}
</script>
// CLAUDE-CHECKPOINT
