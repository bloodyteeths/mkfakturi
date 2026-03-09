<template>
  <BaseDropdown>
    <template #activator>
      <BaseIcon name="EllipsisHorizontalIcon" class="h-5 text-gray-500" />
    </template>

    <!-- Generate Note (PDF) — only for calculated/invoiced -->
    <BaseDropdownItem
      v-if="row.status === 'calculated' || row.status === 'invoiced'"
      @click="$emit('generate', row)"
    >
      <BaseIcon
        name="DocumentArrowDownIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ t('generate_note') }}
    </BaseDropdownItem>

    <!-- Waive — for calculated/invoiced -->
    <BaseDropdownItem
      v-if="row.status === 'calculated' || row.status === 'invoiced'"
      @click="$emit('waive', row)"
    >
      <BaseIcon
        name="XCircleIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-red-500"
      />
      {{ t('waive') }}
    </BaseDropdownItem>

    <!-- Revert — for invoiced/waived -->
    <BaseDropdownItem
      v-if="row.status === 'invoiced' || row.status === 'waived'"
      @click="$emit('revert', row)"
    >
      <BaseIcon
        name="ArrowUturnLeftIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ t('revert') }}
    </BaseDropdownItem>
  </BaseDropdown>
</template>

<script setup>
import interestMessages from '@/scripts/admin/i18n/interest.js'

defineProps({
  row: {
    type: Object,
    required: true,
  },
})

defineEmits(['generate', 'waive', 'revert'])

const locale = document.documentElement.lang || 'mk'
function t(key) {
  return interestMessages[locale]?.interest?.[key]
    || interestMessages['en']?.interest?.[key]
    || key
}
</script>
