<template>
  <div>
    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-8">
      <BaseContentPlaceholders>
        <BaseContentPlaceholdersBox class="w-full h-32" />
      </BaseContentPlaceholders>
    </div>

    <!-- Not Available State -->
    <div v-else-if="!profit?.available" class="text-center py-8">
      <BaseIcon name="ExclamationTriangleIcon" class="h-12 w-12 text-gray-400 mx-auto mb-4" />
      <h3 class="text-lg font-medium text-gray-900">{{ $t('profit.not_available') }}</h3>
      <p class="text-gray-500 mt-2">
        {{ profit?.reason === 'stock_disabled'
            ? $t('profit.stock_disabled_message')
            : $t('profit.no_stock_data_message')
        }}
      </p>
    </div>

    <!-- Profit Data -->
    <template v-else>
      <!-- Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <!-- Revenue -->
        <div class="bg-blue-50 rounded-lg p-4">
          <dt class="text-sm font-medium text-blue-700">{{ $t('profit.revenue') }}</dt>
          <dd class="mt-1">
            <BaseFormatMoney
              :amount="profit.revenue"
              :currency="currency"
              class="text-xl font-bold text-blue-900"
            />
          </dd>
        </div>

        <!-- COGS -->
        <div class="bg-orange-50 rounded-lg p-4">
          <dt class="text-sm font-medium text-orange-700">{{ $t('profit.cogs') }}</dt>
          <dd class="mt-1">
            <BaseFormatMoney
              :amount="profit.cogs"
              :currency="currency"
              class="text-xl font-bold text-orange-900"
            />
          </dd>
        </div>

        <!-- Gross Profit -->
        <div :class="profitColorClass" class="rounded-lg p-4">
          <dt :class="profitLabelClass" class="text-sm font-medium">{{ $t('profit.gross_profit') }}</dt>
          <dd class="mt-1">
            <BaseFormatMoney
              :amount="profit.gross_profit"
              :currency="currency"
              :class="profitValueClass"
              class="text-xl font-bold"
            />
          </dd>
        </div>

        <!-- Margin -->
        <div :class="marginColorClass" class="rounded-lg p-4">
          <dt :class="marginLabelClass" class="text-sm font-medium">{{ $t('profit.margin') }}</dt>
          <dd class="mt-1">
            <span :class="marginValueClass" class="text-xl font-bold">
              {{ profit.margin }}%
            </span>
          </dd>
        </div>
      </div>

      <!-- Item Breakdown Table -->
      <div v-if="profit.items?.length" class="mt-6">
        <h4 class="text-sm font-medium text-gray-700 mb-3">{{ $t('profit.item_breakdown') }}</h4>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  {{ $t('items.name') }}
                </th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                  {{ $t('invoices.quantity') }}
                </th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                  {{ $t('profit.revenue') }}
                </th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                  {{ $t('profit.unit_cost') }}
                </th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                  {{ $t('profit.cogs') }}
                </th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                  {{ $t('profit.gross_profit') }}
                </th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                  {{ $t('profit.margin') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="item in profit.items" :key="item.invoice_item_id">
                <td class="px-4 py-3 text-sm text-gray-900">
                  {{ item.name }}
                  <span v-if="!item.has_cost" class="ml-2 text-xs text-gray-400">
                    ({{ $t('profit.no_cost') }})
                  </span>
                </td>
                <td class="px-4 py-3 text-sm text-right text-gray-900">
                  {{ formatNumber(item.quantity) }}
                </td>
                <td class="px-4 py-3 text-sm text-right text-gray-900">
                  <BaseFormatMoney :amount="item.revenue" :currency="currency" />
                </td>
                <td class="px-4 py-3 text-sm text-right text-gray-500">
                  <template v-if="item.has_cost">
                    <BaseFormatMoney :amount="item.unit_cost" :currency="currency" />
                  </template>
                  <span v-else>-</span>
                </td>
                <td class="px-4 py-3 text-sm text-right text-gray-900">
                  <template v-if="item.has_cost">
                    <BaseFormatMoney :amount="item.cogs" :currency="currency" />
                  </template>
                  <span v-else>-</span>
                </td>
                <td class="px-4 py-3 text-sm text-right" :class="item.gross_profit >= 0 ? 'text-green-600' : 'text-red-600'">
                  <template v-if="item.has_cost">
                    <BaseFormatMoney :amount="item.gross_profit" :currency="currency" />
                  </template>
                  <span v-else>-</span>
                </td>
                <td class="px-4 py-3 text-sm text-right" :class="item.margin >= 0 ? 'text-green-600' : 'text-red-600'">
                  <template v-if="item.has_cost">
                    {{ item.margin }}%
                  </template>
                  <span v-else>-</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Cost Source Info -->
      <div class="mt-4 text-xs text-gray-500">
        <BaseIcon name="InformationCircleIcon" class="h-4 w-4 inline mr-1" />
        {{ $t('profit.cost_source_info') }}
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  profit: {
    type: Object,
    default: null,
  },
  currency: {
    type: Object,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const profitColorClass = computed(() => {
  if (!props.profit?.available) return 'bg-gray-50'
  return props.profit.gross_profit >= 0 ? 'bg-green-50' : 'bg-red-50'
})

const profitLabelClass = computed(() => {
  if (!props.profit?.available) return 'text-gray-700'
  return props.profit.gross_profit >= 0 ? 'text-green-700' : 'text-red-700'
})

const profitValueClass = computed(() => {
  if (!props.profit?.available) return 'text-gray-900'
  return props.profit.gross_profit >= 0 ? 'text-green-900' : 'text-red-900'
})

const marginColorClass = computed(() => {
  if (!props.profit?.available) return 'bg-gray-50'
  return props.profit.margin >= 0 ? 'bg-green-50' : 'bg-red-50'
})

const marginLabelClass = computed(() => {
  if (!props.profit?.available) return 'text-gray-700'
  return props.profit.margin >= 0 ? 'text-green-700' : 'text-red-700'
})

const marginValueClass = computed(() => {
  if (!props.profit?.available) return 'text-gray-900'
  return props.profit.margin >= 0 ? 'text-green-900' : 'text-red-900'
})

function formatNumber(num) {
  if (num === null || num === undefined) return '-'
  return Number(num).toLocaleString('en-US', { maximumFractionDigits: 4 })
}
</script>
// CLAUDE-CHECKPOINT
