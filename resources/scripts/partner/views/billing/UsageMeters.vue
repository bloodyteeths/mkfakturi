<template>
  <div class="space-y-3">
    <div
      v-for="meter in meters"
      :key="meter.key"
      class="flex items-center gap-4"
    >
      <div class="w-40 text-sm text-gray-600 truncate">
        {{ meter.label }}
      </div>
      <div class="flex-1">
        <div class="h-2.5 bg-gray-200 rounded-full overflow-hidden">
          <div
            class="h-full rounded-full transition-all duration-300"
            :class="getBarColor(meter.percentage)"
            :style="{ width: meter.percentage + '%' }"
          />
        </div>
      </div>
      <div class="w-24 text-right text-sm font-medium" :class="getTextColor(meter.percentage)">
        {{ meter.used }}<span class="text-gray-400">/{{ meter.limitLabel }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  usage: {
    type: Object,
    default: () => ({}),
  },
})

const meterLabels = {
  companies: 'Companies',
  ai_credits_per_month: 'AI Credits',
  bank_accounts: 'Bank Accounts',
  payroll_employees: 'Employees',
  efaktura_per_month: 'E-Faktura',
  documents_stored_per_month: 'Documents',
  client_portal_invites: 'Portal Invites',
}

const meters = computed(() => {
  return Object.entries(props.usage).map(([key, data]) => {
    const limit = data.limit
    const used = data.used || 0
    const percentage = limit === null ? 0 : Math.min(100, Math.round((used / Math.max(limit, 1)) * 100))

    return {
      key,
      label: meterLabels[key] || key,
      used,
      limit,
      limitLabel: limit === null ? '\u221E' : limit,
      percentage,
    }
  })
})

function getBarColor(pct) {
  if (pct >= 90) return 'bg-red-500'
  if (pct >= 70) return 'bg-yellow-500'
  return 'bg-primary-500'
}

function getTextColor(pct) {
  if (pct >= 90) return 'text-red-600'
  if (pct >= 70) return 'text-yellow-600'
  return 'text-gray-700'
}
</script>
