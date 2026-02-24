<template>
  <div class="relative min-w-[220px]">
    <BaseMultiselect
      :model-value="modelValue"
      :options="groupedAccounts"
      :searchable="true"
      :groups="true"
      group-label="type"
      group-options="accounts"
      group-hide-empty
      track-by="id"
      label="display_name"
      value-prop="id"
      :placeholder="$t('partner.accounting.select_account')"
      :disabled="disabled"
      @update:model-value="onAccountChange"
    >
      <template #singleLabel="{ value }">
        <span class="font-mono text-sm truncate block" :title="getAccountDisplay(value.value)">
          {{ getAccountDisplay(value.value) }}
        </span>
      </template>

      <template #option="{ option }">
        <div class="flex items-center">
          <span class="font-mono text-sm text-gray-700 shrink-0">{{ option.code }}</span>
          <span class="ml-2 text-sm text-gray-600 truncate">{{ option.name }}</span>
        </div>
      </template>
    </BaseMultiselect>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  modelValue: {
    type: Number,
    default: null,
  },
  accounts: {
    type: Array,
    default: () => [],
  },
  confidence: {
    type: Number,
    default: null,
  },
  reason: {
    type: String,
    default: null,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue', 'change'])

// Account type labels for grouping
const accountTypeLabels = {
  asset: 'Assets',
  liability: 'Liabilities',
  equity: 'Equity',
  revenue: 'Revenue',
  expense: 'Expenses',
}

// Computed
const groupedAccounts = computed(() => {
  // Guard: return empty array if accounts not loaded yet
  if (!props.accounts || !Array.isArray(props.accounts) || props.accounts.length === 0) {
    return []
  }

  // Group accounts by type
  const groups = {}

  props.accounts.forEach((account) => {
    const type = account.type || 'other'
    if (!groups[type]) {
      groups[type] = []
    }
    groups[type].push({
      ...account,
      display_name: `${account.code} - ${account.name}`,
    })
  })

  // Convert to array format expected by multiselect
  return Object.keys(groups).map((type) => ({
    type: accountTypeLabels[type] || type,
    accounts: groups[type],
  }))
})

// Methods
function getAccountDisplay(accountId) {
  if (!props.accounts || !Array.isArray(props.accounts)) return ''
  const account = props.accounts.find((a) => a.id === accountId)
  if (!account) return ''
  return `${account.code} - ${account.name}`
}

function onAccountChange(value) {
  emit('update:modelValue', value)
  emit('change', value)
}
</script>

