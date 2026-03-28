<template>
  <BaseDropdown>
    <template #activator>
      <BaseIcon name="EllipsisHorizontalIcon" class="h-5 text-gray-500" />
    </template>

    <!-- View Bill -->
    <router-link
      v-if="row.bill"
      :to="`/admin/bills/${row.bill_id}/view`"
    >
      <BaseDropdownItem>
        <BaseIcon
          name="EyeIcon"
          class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
        />
        {{ bp('view_bill') }}
      </BaseDropdownItem>
    </router-link>

    <!-- Print PP30 -->
    <BaseDropdownItem
      v-if="row.bill_id"
      @click="printPP30(row)"
    >
      <BaseIcon
        name="PrinterIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ bp('print_pp30') }}
    </BaseDropdownItem>

    <!-- Расходен налог -->
    <BaseDropdownItem
      @click="printRashodenNalog(row)"
    >
      <BaseIcon
        name="DocumentTextIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ bp('rashoden_nalog') || 'Расходен налог' }}
    </BaseDropdownItem>

    <!-- Delete Payment -->
    <BaseDropdownItem @click="removeBillPayment(row)">
      <BaseIcon
        name="TrashIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('general.delete') }}
    </BaseDropdownItem>
  </BaseDropdown>
</template>

<script setup>
import { useDialogStore } from '@/scripts/stores/dialog'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useI18n } from 'vue-i18n'
import bpMessages from '@/scripts/admin/i18n/bill-payments.js'

const props = defineProps({
  row: {
    type: Object,
    default: null,
  },
  table: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['deleted'])

const dialogStore = useDialogStore()
const notificationStore = useNotificationStore()
const { t } = useI18n()

const locale = document.documentElement.lang || 'mk'
function bp(key) {
  return bpMessages[locale]?.bill_payments?.[key]
    || bpMessages['en']?.bill_payments?.[key]
    || key
}

function printPP30(payment) {
  window.open(`/api/v1/admin/bills/${payment.bill_id}/pp30`, '_blank')
}

function printRashodenNalog(payment) {
  window.open(`/api/v1/admin/bill-payments/${payment.id}/rashoden-nalog`, '_blank')
}

function removeBillPayment(payment) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: bp('confirm_delete'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      size: 'lg',
      hideNoButton: false,
    })
    .then(async (res) => {
      if (res) {
        try {
          await window.axios.post('/bill-payments/delete', {
            ids: [payment.id],
          })
          notificationStore.showNotification({
            type: 'success',
            message: bp('deleted_message'),
          })
          emit('deleted')
          props.table && props.table.refresh()
        } catch (err) {
          notificationStore.showNotification({
            type: 'error',
            message: err.response?.data?.message || bp('delete_failed'),
          })
        }
      }
    })
}
// CLAUDE-CHECKPOINT
</script>
