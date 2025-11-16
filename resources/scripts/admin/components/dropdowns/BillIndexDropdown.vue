<template>
  <BaseDropdown>
    <template #activator>
      <BaseButton v-if="route.name === 'bills.view'" variant="primary">
        <BaseIcon name="EllipsisHorizontalIcon" class="h-5 text-white" />
      </BaseButton>
      <BaseIcon v-else name="EllipsisHorizontalIcon" class="h-5 text-gray-500" />
    </template>

    <!-- View Bill  -->
    <router-link
      v-if="
        route.name !== 'bills.view' &&
        userStore.hasAbilities(abilities.VIEW_BILL)
      "
      :to="`/admin/bills/${row.id}/view`"
    >
      <BaseDropdownItem>
        <BaseIcon
          name="EyeIcon"
          class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
        />
        {{ $t('general.view') }}
      </BaseDropdownItem>
    </router-link>

    <!-- Edit Bill  -->
    <router-link
      v-if="userStore.hasAbilities(abilities.EDIT_BILL)"
      :to="`/admin/bills/${row.id}/edit`"
    >
      <BaseDropdownItem v-show="row.allow_edit">
        <BaseIcon
          name="PencilIcon"
          class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
        />
        {{ $t('general.edit') }}
      </BaseDropdownItem>
    </router-link>

    <!-- Clone Bill into new bill  -->
    <BaseDropdownItem
      v-if="userStore.hasAbilities(abilities.CREATE_BILL)"
      @click="cloneBillData(row)"
    >
      <BaseIcon
        name="DocumentTextIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('bills.clone_bill') }}
    </BaseDropdownItem>

    <!-- Mark as sent Bill -->
    <BaseDropdownItem v-if="canSendBill(row)" @click="onMarkAsSent(row.id)">
      <BaseIcon
        name="CheckCircleIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('bills.mark_as_sent') }}
    </BaseDropdownItem>

    <!-- Send Bill Mail  -->
    <BaseDropdownItem v-if="canSendBill(row)" @click="sendBill(row)">
      <BaseIcon
        name="PaperAirplaneIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('bills.send_bill') }}
    </BaseDropdownItem>

    <!-- Copy PDF url  -->
    <BaseDropdownItem v-if="route.name === 'bills.view'" @click="copyPdfUrl">
      <BaseIcon
        name="LinkIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('general.copy_pdf_url') }}
    </BaseDropdownItem>

    <!--  Delete Bill  -->
    <BaseDropdownItem
      v-if="userStore.hasAbilities(abilities.DELETE_BILL)"
      @click="removeBill(row.id)"
    >
      <BaseIcon
        name="TrashIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('general.delete') }}
    </BaseDropdownItem>
  </BaseDropdown>
</template>

<script setup>
import { useBillsStore as useBillStore } from '@/scripts/admin/stores/bills'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useModalStore } from '@/scripts/stores/modal'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useUserStore } from '@/scripts/admin/stores/user'
import { inject } from 'vue'
import abilities from '@/scripts/admin/stub/abilities'

const props = defineProps({
  row: {
    type: Object,
    default: null,
  },
  table: {
    type: Object,
    default: null,
  },
  loadData: {
    type: Function,
    default: () => {},
  },
})

const billStore = useBillStore()
const modalStore = useModalStore()
const notificationStore = useNotificationStore()
const dialogStore = useDialogStore()
const userStore = useUserStore()

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const utils = inject('utils')

function canSendBill(row) {
  return (
    row.status == 'DRAFT' &&
    route.name !== 'bills.view' &&
    userStore.hasAbilities(abilities.SEND_BILL)
  )
}

async function removeBill(id) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('bills.confirm_delete'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then((res) => {
      id = id
      if (res) {
        billStore.deleteBill({ ids: [id] }).then((res) => {
          if (res.data.success) {
            router.push('/admin/bills')
            props.table && props.table.refresh()

            billStore.$patch((state) => {
              state.selectedBills = []
              state.selectAllField = false
            })
          }
        })
      }
    })
}

async function cloneBillData(data) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('bills.confirm_clone'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'lg',
    })
    .then((res) => {
      if (res) {
        billStore.cloneBill(data).then((res) => {
          router.push(`/admin/bills/${res.data.data.id}/edit`)
        })
      }
    })
}

async function onMarkAsSent(id) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('bills.bill_mark_as_sent'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'lg',
    })
    .then((response) => {
      const data = {
        id: id,
        status: 'SENT',
      }
      if (response) {
        billStore.markAsSent(data).then((response) => {
          props.table && props.table.refresh()
        })
      }
    })
}

async function sendBill(bill) {
  modalStore.openModal({
    title: t('bills.send_bill'),
    componentName: 'SendBillModal',
    id: bill.id,
    data: bill,
    variant: 'sm',
  })
}

function copyPdfUrl() {
  let pdfUrl = `${window.location.origin}/bills/pdf/${props.row.unique_hash}`

  utils.copyTextToClipboard(pdfUrl)

  notificationStore.showNotification({
    type: 'success',
    message: t('general.copied_pdf_url_clipboard'),
  })
}
</script>
// CLAUDE-CHECKPOINT
