<template>
  <BaseCard class="mb-4 hover:shadow-lg transition-shadow duration-200">
    <div class="p-4">
      <!-- Header: Bill Number & Status -->
      <div class="flex justify-between items-start mb-3">
        <div>
          <router-link
            :to="{ path: `/admin/bills/${bill.id}/view` }"
            class="text-lg font-semibold text-primary-500 hover:text-primary-600"
          >
            {{ bill.bill_number }}
          </router-link>
          <p class="text-sm text-gray-600 mt-1">
            {{ bill.supplier.name }}
          </p>
        </div>
        <BaseBillStatusBadge :status="bill.status" class="px-3 py-1">
          <BaseBillStatusLabel :status="bill.status" />
        </BaseBillStatusBadge>
      </div>

      <!-- Bill Details -->
      <div class="space-y-2 mb-4">
        <div class="flex justify-between text-sm">
          <span class="text-gray-500">{{ $t('bills.bill_date') }}</span>
          <span class="font-medium">{{ bill.formatted_bill_date }}</span>
        </div>
        <div class="flex justify-between text-sm">
          <span class="text-gray-500">{{ $t('bills.due_amount') }}</span>
          <div class="flex items-center">
            <BaseFormatMoney
              :amount="bill.due_amount"
              :currency="bill.currency"
              class="font-medium"
            />
            <BaseBillPaidStatusBadge
              v-if="bill.overdue"
              status="OVERDUE"
              class="px-2 py-0.5 ml-2 text-xs"
            >
              {{ $t('bills.overdue') }}
            </BaseBillPaidStatusBadge>
            <BaseBillPaidStatusBadge
              v-else-if="bill.status === 'COMPLETED'"
              status="PAID"
              class="px-2 py-0.5 ml-2 text-xs"
            >
              {{ $t('bills.paid') }}
            </BaseBillPaidStatusBadge>
          </div>
        </div>
        <div class="flex justify-between pt-2 border-t border-gray-200">
          <span class="text-gray-700 font-medium">{{ $t('bills.total') }}</span>
          <BaseFormatMoney
            :amount="bill.total"
            :currency="bill.supplier.currency"
            class="text-lg font-bold text-gray-900"
          />
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex gap-2 mt-4">
        <router-link
          :to="{ path: `/admin/bills/${bill.id}/view` }"
          class="flex-1"
        >
          <BaseButton
            variant="primary"
            size="md"
            class="w-full min-h-[44px]"
          >
            <template #left="slotProps">
              <BaseIcon name="EyeIcon" :class="slotProps.class" />
            </template>
            {{ $t('general.view') }}
          </BaseButton>
        </router-link>

        <router-link
          v-if="hasEditAbility"
          :to="{ path: `/admin/bills/${bill.id}/edit` }"
          class="flex-1"
        >
          <BaseButton
            variant="primary-outline"
            size="md"
            class="w-full min-h-[44px]"
          >
            <template #left="slotProps">
              <BaseIcon name="PencilIcon" :class="slotProps.class" />
            </template>
            {{ $t('general.edit') }}
          </BaseButton>
        </router-link>
      </div>

      <!-- Checkbox for selection (optional) -->
      <div v-if="selectable" class="absolute top-4 right-4">
        <BaseCheckbox
          :id="bill.id"
          :model-value="isSelected"
          @update:model-value="$emit('toggle-select', bill.id)"
          class="min-w-[44px] min-h-[44px] flex items-center justify-center"
        />
      </div>
    </div>
  </BaseCard>
</template>

<script setup>
import { computed } from 'vue'
import { useUserStore } from '@/scripts/admin/stores/user'
import abilities from '@/scripts/admin/stub/abilities'

const props = defineProps({
  bill: {
    type: Object,
    required: true,
  },
  selectable: {
    type: Boolean,
    default: false,
  },
  isSelected: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['toggle-select'])

const userStore = useUserStore()

const hasEditAbility = computed(() => {
  return userStore.hasAbilities(abilities.EDIT_BILL)
})
</script>

// CLAUDE-CHECKPOINT
