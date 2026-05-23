<template>
  <BasePage>
    <BasePageHeader :title="isEditing ? $t('trade.edit_import_calc') : $t('trade.new_import_calc')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('stock.title')" to="/admin/stock" />
        <BaseBreadcrumbItem :title="$t('trade.import_calc_title')" to="/admin/stock/trade/import-calculations" />
        <BaseBreadcrumbItem :title="isEditing ? $t('trade.edit_import_calc') : $t('trade.new_import_calc')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <StockTabNavigation />

    <div v-if="isLoadingEdit" class="flex items-center justify-center py-20">
      <BaseIcon name="ArrowPathIcon" class="h-8 w-8 animate-spin text-gray-400" />
    </div>

    <form v-else @submit.prevent="onSubmit">
      <!-- Document Header -->
      <BaseCard class="mb-6">
        <h3 class="mb-4 text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ $t('trade.supplier_invoice') }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">{{ $t('trade.doc_date') }}</label>
            <BaseInput v-model="form.document_date" type="date" required />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">{{ $t('trade.source_bill') }}</label>
            <BaseMultiselect
              v-model="form.supplier_bill_id"
              :options="bills"
              value-prop="id"
              label="bill_number"
              :placeholder="$t('trade.select_bill_placeholder')"
              :searchable="true"
              :canClear="true"
              @change="onBillSelected"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">{{ $t('trade.warehouse') }}</label>
            <BaseMultiselect
              v-model="form.warehouse_id"
              :options="warehouses"
              value-prop="id"
              label="name"
              placeholder="..."
              :searchable="true"
              required
            />
          </div>
        </div>

        <div v-if="!form.supplier_bill_id" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">{{ $t('trade.supplier_name') }}</label>
            <BaseInput v-model="form.supplier_name" type="text" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">{{ $t('trade.supplier_invoice_number') }}</label>
            <BaseInput v-model="form.supplier_invoice_number" type="text" />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">{{ $t('trade.currency_code') }}</label>
            <BaseInput v-model="form.currency_code" type="text" maxlength="3" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">{{ $t('trade.exchange_rate') }}</label>
            <BaseInput v-model.number="form.exchange_rate" type="number" step="0.000001" min="0" required />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">{{ $t('trade.vat_rate_label') }}</label>
            <BaseInput v-model.number="form.vat_rate" type="number" step="0.01" min="0" max="100" />
          </div>
        </div>
      </BaseCard>

      <!-- Costs -->
      <BaseCard class="mb-6">
        <h3 class="mb-4 text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ $t('trade.costs_summary') }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">{{ $t('trade.transport') }} (МКД)</label>
            <BaseInput v-model.number="form.transport_amount" type="number" step="0.01" min="0" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">{{ $t('trade.forwarding') }} (МКД)</label>
            <BaseInput v-model.number="form.forwarding_amount" type="number" step="0.01" min="0" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">{{ $t('trade.other_costs') }} (МКД)</label>
            <BaseInput v-model.number="form.other_costs_amount" type="number" step="0.01" min="0" />
          </div>
        </div>
      </BaseCard>

      <!-- Items Table -->
      <BaseCard class="mb-6">
        <h3 class="mb-4 text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ $t('trade.add_item') }}</h3>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b text-left text-gray-500">
                <th class="py-2 pr-2 w-8">#</th>
                <th class="py-2 pr-2 min-w-[200px]">{{ $t('trade.description') }}</th>
                <th class="py-2 pr-2 w-28">{{ $t('trade.tariff_heading') }}</th>
                <th class="py-2 pr-2 w-20">{{ $t('trade.quantity') }}</th>
                <th class="py-2 pr-2 w-16">{{ $t('trade.unit_label') }}</th>
                <th class="py-2 pr-2 w-28">{{ $t('trade.unit_price_fcy') }} ({{ form.currency_code }})</th>
                <th class="py-2 pr-2 w-28">{{ $t('trade.customs_duty_rate') }}</th>
                <th class="py-2 w-10"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, idx) in form.items" :key="idx" class="border-b border-gray-100">
                <td class="py-2 pr-2 text-gray-400">{{ idx + 1 }}</td>
                <td class="py-2 pr-2">
                  <BaseInput
                    v-model="item.description"
                    type="text"
                    required
                    class="text-sm"
                  />
                </td>
                <td class="py-2 pr-2">
                  <BaseInput
                    v-model="item.tariff_heading"
                    type="text"
                    required
                    class="text-sm"
                    @blur="syncTariffRate(idx)"
                  />
                </td>
                <td class="py-2 pr-2">
                  <BaseInput
                    v-model.number="item.quantity"
                    type="number"
                    step="0.0001"
                    min="0.0001"
                    required
                    class="text-sm"
                  />
                </td>
                <td class="py-2 pr-2">
                  <BaseInput
                    v-model="item.unit"
                    type="text"
                    class="text-sm"
                  />
                </td>
                <td class="py-2 pr-2">
                  <BaseInput
                    v-model.number="item.unit_price_fcy"
                    type="number"
                    step="0.01"
                    min="0"
                    required
                    class="text-sm"
                  />
                </td>
                <td class="py-2 pr-2">
                  <BaseInput
                    v-model.number="item.customs_duty_rate"
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    required
                    class="text-sm"
                    @blur="syncTariffRateFromItem(idx)"
                  />
                </td>
                <td class="py-2">
                  <button
                    v-if="form.items.length > 1"
                    type="button"
                    class="text-red-400 hover:text-red-600"
                    @click="removeItem(idx)"
                  >
                    <BaseIcon name="TrashIcon" class="h-4 w-4" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <button
          type="button"
          class="mt-3 text-sm text-primary-500 hover:text-primary-700 font-medium"
          @click="addItem"
        >
          + {{ $t('trade.add_item') }}
        </button>
      </BaseCard>

      <!-- Preview -->
      <BaseCard v-if="previewTotals" class="mb-6">
        <h3 class="mb-4 text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ $t('trade.costs_summary') }}</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
          <div>
            <span class="text-gray-500">{{ $t('trade.invoice_value_mkd') }}</span>
            <p class="font-semibold">{{ formatMoney(previewTotals.totalInvoiceMkd) }}</p>
          </div>
          <div>
            <span class="text-gray-500">{{ $t('trade.transport') }}</span>
            <p class="font-semibold">{{ formatMoney(form.transport_amount * 100) }}</p>
          </div>
          <div>
            <span class="text-gray-500">{{ $t('trade.customs_duty') }}</span>
            <p class="font-semibold">{{ formatMoney(previewTotals.customsDuty) }}</p>
          </div>
          <div>
            <span class="text-gray-500">{{ $t('trade.total_landed_cost') }}</span>
            <p class="font-semibold text-primary-600">{{ formatMoney(previewTotals.totalLanded) }}</p>
          </div>
        </div>
      </BaseCard>

      <!-- Actions -->
      <div class="flex items-center justify-end gap-3">
        <router-link to="/admin/stock/trade/import-calculations">
          <BaseButton variant="default" type="button">
            {{ $t('general.cancel') }}
          </BaseButton>
        </router-link>
        <BaseButton variant="primary" type="submit" :loading="isSaving">
          {{ isEditing ? $t('general.update') : $t('general.save') }}
        </BaseButton>
      </div>
    </form>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import StockTabNavigation from '@/scripts/admin/components/StockTabNavigation.vue'

const route = useRoute()
const router = useRouter()
const companyStore = useCompanyStore()

const isEditing = computed(() => !!route.params.id)
const isLoadingEdit = ref(false)
const isSaving = ref(false)

const bills = ref([])
const warehouses = ref([])

const apiBase = computed(() => {
  const companyId = companyStore.selectedCompany?.id
  return `/partner/companies/${companyId}/accounting`
})

const emptyItem = () => ({
  item_id: null,
  description: '',
  tariff_heading: '',
  quantity: 1,
  unit: 'ком.',
  unit_price_fcy: 0,
  customs_duty_rate: 0,
  notes: '',
})

const form = reactive({
  document_date: new Date().toISOString().split('T')[0],
  supplier_bill_id: null,
  supplier_name: '',
  supplier_invoice_number: '',
  currency_code: 'EUR',
  exchange_rate: 61.5,
  warehouse_id: null,
  transport_amount: 0,
  forwarding_amount: 0,
  other_costs_amount: 0,
  vat_rate: 18,
  notes: '',
  items: [emptyItem()],
})

const previewTotals = computed(() => {
  if (!form.items.length || !form.exchange_rate) return null

  let totalInvoiceMkd = 0
  const items = form.items.map((item) => {
    const valueFcy = item.quantity * item.unit_price_fcy
    const valueMkd = Math.round(valueFcy * form.exchange_rate * 100) // cents
    totalInvoiceMkd += valueMkd
    return { ...item, valueMkd }
  })

  const transportCents = Math.round(form.transport_amount * 100)
  const forwardingCents = Math.round(form.forwarding_amount * 100)
  const otherCents = Math.round(form.other_costs_amount * 100)

  let customsDuty = 0
  const headingGroups = {}
  items.forEach((item) => {
    const h = item.tariff_heading || '_'
    if (!headingGroups[h]) headingGroups[h] = { base: 0, rate: item.customs_duty_rate }
    const transport = totalInvoiceMkd > 0
      ? Math.round(transportCents * item.valueMkd / totalInvoiceMkd)
      : 0
    headingGroups[h].base += item.valueMkd + transport
  })
  Object.values(headingGroups).forEach((g) => {
    customsDuty += Math.round(g.base * g.rate / 100)
  })

  const totalBeforeVat = totalInvoiceMkd + transportCents + customsDuty + forwardingCents + otherCents
  const importVat = Math.round(totalBeforeVat * form.vat_rate / 100)
  const totalLanded = totalBeforeVat + importVat

  return { totalInvoiceMkd, customsDuty, totalBeforeVat, importVat, totalLanded }
})

function formatMoney(cents) {
  return (cents / 100).toLocaleString('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' МКД'
}

function addItem() {
  form.items.push(emptyItem())
}

function removeItem(idx) {
  form.items.splice(idx, 1)
}

function syncTariffRate(idx) {
  const heading = form.items[idx].tariff_heading
  if (!heading) return
  const existing = form.items.find((it, i) => i !== idx && it.tariff_heading === heading && it.customs_duty_rate > 0)
  if (existing) {
    form.items[idx].customs_duty_rate = existing.customs_duty_rate
  }
}

function syncTariffRateFromItem(idx) {
  const heading = form.items[idx].tariff_heading
  const rate = form.items[idx].customs_duty_rate
  if (!heading) return
  form.items.forEach((item, i) => {
    if (i !== idx && item.tariff_heading === heading) {
      item.customs_duty_rate = rate
    }
  })
}

async function onBillSelected(billId) {
  if (!billId) return
  try {
    const { data } = await window.axios.get(`${apiBase.value}/import-calculations/from-bill/${billId}`)
    if (data.success && data.data) {
      const d = data.data
      form.supplier_name = d.supplier_name || ''
      form.supplier_invoice_number = d.supplier_invoice_number || ''
      if (d.exchange_rate && d.exchange_rate > 1) {
        form.exchange_rate = d.exchange_rate
      }
      if (d.items && d.items.length) {
        form.items = d.items.map((bi) => ({
          item_id: bi.item_id,
          description: bi.description || '',
          tariff_heading: bi.tariff_heading || '',
          quantity: bi.quantity || 1,
          unit: bi.unit || 'ком.',
          unit_price_fcy: bi.unit_price_fcy ? bi.unit_price_fcy / 100 : 0,
          customs_duty_rate: bi.customs_duty_rate || 0,
          notes: '',
        }))
      }
    }
  } catch (e) {
    console.error('Failed to load bill items:', e)
  }
}

async function loadEditData() {
  isLoadingEdit.value = true
  try {
    const { data } = await window.axios.get(`${apiBase.value}/import-calculations/${route.params.id}`)
    if (data.success && data.data) {
      const d = data.data
      form.document_date = d.document_date?.split('T')[0] || ''
      form.supplier_bill_id = d.supplier_bill_id
      form.supplier_name = d.supplier_name || ''
      form.supplier_invoice_number = d.supplier_invoice_number || ''
      form.currency_code = d.currency_code || 'EUR'
      form.exchange_rate = parseFloat(d.exchange_rate) || 61.5
      form.warehouse_id = d.warehouse_id
      form.transport_amount = (d.transport_amount || 0) / 100
      form.forwarding_amount = (d.forwarding_amount || 0) / 100
      form.other_costs_amount = (d.other_costs_amount || 0) / 100
      form.vat_rate = parseFloat(d.vat_rate) || 18
      form.notes = d.notes || ''
      if (d.items && d.items.length) {
        form.items = d.items.map((item) => ({
          item_id: item.item_id,
          description: item.description || '',
          tariff_heading: item.tariff_heading || '',
          quantity: parseFloat(item.quantity) || 1,
          unit: item.unit || 'ком.',
          unit_price_fcy: (item.unit_price_fcy || 0) / 100,
          customs_duty_rate: parseFloat(item.customs_duty_rate) || 0,
          notes: item.notes || '',
        }))
      }
    }
  } catch (e) {
    console.error('Failed to load import calculation:', e)
  } finally {
    isLoadingEdit.value = false
  }
}

async function onSubmit() {
  isSaving.value = true
  try {
    const payload = {
      document_date: form.document_date,
      supplier_bill_id: form.supplier_bill_id || null,
      supplier_name: form.supplier_name || null,
      supplier_invoice_number: form.supplier_invoice_number || null,
      currency_code: form.currency_code,
      exchange_rate: form.exchange_rate,
      warehouse_id: form.warehouse_id,
      transport_amount: Math.round(form.transport_amount * 100),
      forwarding_amount: Math.round(form.forwarding_amount * 100),
      other_costs_amount: Math.round(form.other_costs_amount * 100),
      vat_rate: form.vat_rate,
      notes: form.notes || null,
      items: form.items.map((item) => ({
        item_id: item.item_id || null,
        tariff_heading: item.tariff_heading,
        description: item.description,
        quantity: item.quantity,
        unit: item.unit || 'ком.',
        unit_price_fcy: Math.round(item.unit_price_fcy * 100),
        customs_duty_rate: item.customs_duty_rate,
        notes: item.notes || null,
      })),
    }

    let response
    if (isEditing.value) {
      response = await window.axios.put(`${apiBase.value}/import-calculations/${route.params.id}`, payload)
    } else {
      response = await window.axios.post(`${apiBase.value}/import-calculations`, payload)
    }

    if (response.data.success) {
      const id = response.data.data?.id || route.params.id
      router.push({ name: 'stock.trade.import-calculation.view', params: { id } })
    }
  } catch (e) {
    console.error('Save error:', e)
    if (e.response?.data?.message) {
      alert(e.response.data.message)
    }
  } finally {
    isSaving.value = false
  }
}

async function loadBills() {
  try {
    const companyId = companyStore.selectedCompany?.id
    const { data } = await window.axios.get('/bills', {
      headers: { company: companyId },
      params: { limit: 'all' },
    })
    bills.value = (data.data || data.bills?.data || [])
  } catch (e) {
    console.error('Failed to load bills:', e)
  }
}

async function loadWarehouses() {
  try {
    const companyId = companyStore.selectedCompany?.id
    const { data } = await window.axios.get('/warehouses', {
      headers: { company: companyId },
    })
    warehouses.value = data.data || data.warehouses || []
  } catch (e) {
    console.error('Failed to load warehouses:', e)
  }
}

onMounted(async () => {
  await Promise.all([loadBills(), loadWarehouses()])
  if (isEditing.value) {
    await loadEditData()
  }
})
</script>

// CLAUDE-CHECKPOINT
