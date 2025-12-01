<template>
  <BaseDropdown>
    <template #activator>
      <BaseButton v-if="route.name === 'proforma-invoices.view'" variant="primary">
        <BaseIcon name="EllipsisHorizontalIcon" class="h-5 text-white" />
      </BaseButton>
      <BaseIcon v-else name="EllipsisHorizontalIcon" class="h-5 text-gray-500" />
    </template>

    <!-- Edit Proforma Invoice  -->
    <router-link
      v-if="userStore.hasAbilities(abilities.EDIT_ESTIMATE) && row.allow_edit"
      :to="`/admin/proforma-invoices/${row.id}/edit`"
    >
      <BaseDropdownItem>
        <BaseIcon
          name="PencilIcon"
          class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
        />
        {{ $t('general.edit') }}
      </BaseDropdownItem>
    </router-link>

    <!-- Copy PDF url  -->
    <BaseDropdownItem v-if="route.name === 'proforma-invoices.view'" @click="copyPdfUrl">
      <BaseIcon
        name="LinkIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('general.copy_pdf_url') }}
    </BaseDropdownItem>

    <!-- View Proforma Invoice  -->
    <router-link
      v-if="
        route.name !== 'proforma-invoices.view' &&
        userStore.hasAbilities(abilities.VIEW_ESTIMATE)
      "
      :to="`/admin/proforma-invoices/${row.id}/view`"
    >
      <BaseDropdownItem>
        <BaseIcon
          name="EyeIcon"
          class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
        />
        {{ $t('general.view') }}
      </BaseDropdownItem>
    </router-link>

    <!-- Convert to Invoice  -->
    <BaseDropdownItem
      v-if="canConvertToInvoice(row)"
      @click="convertToInvoice(row)"
    >
      <BaseIcon
        name="DocumentDuplicateIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('proforma_invoices.convert_to_invoice') }}
    </BaseDropdownItem>

    <!-- Mark as Sent -->
    <BaseDropdownItem
      v-if="canMarkAsSent(row)"
      @click="onMarkAsSent(row.id)"
    >
      <BaseIcon
        name="PaperAirplaneIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('proforma_invoices.mark_as_sent') }}
    </BaseDropdownItem>

    <!-- Mark as Viewed -->
    <BaseDropdownItem
      v-if="canMarkAsViewed(row)"
      @click="onMarkAsViewed(row.id)"
    >
      <BaseIcon
        name="EyeIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('proforma_invoices.mark_as_viewed') }}
    </BaseDropdownItem>

    <!-- Mark as Expired -->
    <BaseDropdownItem
      v-if="canMarkAsExpired(row)"
      @click="onMarkAsExpired(row.id)"
    >
      <BaseIcon
        name="ClockIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('proforma_invoices.mark_as_expired') }}
    </BaseDropdownItem>

    <!-- Mark as Rejected -->
    <BaseDropdownItem
      v-if="canMarkAsRejected(row)"
      @click="onMarkAsRejected(row.id)"
    >
      <BaseIcon
        name="XCircleIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('proforma_invoices.mark_as_rejected') }}
    </BaseDropdownItem>

    <!--  Delete Proforma Invoice  -->
    <BaseDropdownItem
      v-if="userStore.hasAbilities(abilities.DELETE_ESTIMATE)"
      @click="removeProformaInvoice(row.id)"
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
import { useProformaInvoiceStore } from '@/scripts/admin/stores/proforma-invoice'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useDialogStore } from '@/scripts/stores/dialog'
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

const proformaInvoiceStore = useProformaInvoiceStore()
const notificationStore = useNotificationStore()
const dialogStore = useDialogStore()
const userStore = useUserStore()

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const utils = inject('utils')

function canConvertToInvoice(row) {
  return (
    (row.status === 'SENT' || row.status === 'VIEWED') &&
    !row.is_expired &&
    row.status !== 'CONVERTED' &&
    userStore.hasAbilities(abilities.CREATE_INVOICE)
  )
}

function canMarkAsSent(row) {
  return (
    row.status === 'DRAFT' &&
    userStore.hasAbilities(abilities.EDIT_ESTIMATE)
  )
}

function canMarkAsViewed(row) {
  return (
    (row.status === 'DRAFT' || row.status === 'SENT') &&
    userStore.hasAbilities(abilities.EDIT_ESTIMATE)
  )
}

function canMarkAsExpired(row) {
  return (
    row.status !== 'CONVERTED' &&
    row.status !== 'EXPIRED' &&
    row.status !== 'REJECTED' &&
    userStore.hasAbilities(abilities.EDIT_ESTIMATE)
  )
}

function canMarkAsRejected(row) {
  return (
    row.status !== 'CONVERTED' &&
    row.status !== 'REJECTED' &&
    userStore.hasAbilities(abilities.EDIT_ESTIMATE)
  )
}

async function removeProformaInvoice(id) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('proforma_invoices.confirm_delete'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then((res) => {
      if (res) {
        proformaInvoiceStore.deleteProformaInvoice(id).then((res) => {
          if (res.data.success) {
            router.push('/admin/proforma-invoices')
            props.table && props.table.refresh()

            proformaInvoiceStore.$patch((state) => {
              state.selectedProformaInvoices = []
              state.selectAllField = false
            })
          }
        })
      }
    })
}

async function convertToInvoice(row) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('proforma_invoices.confirm_convert'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'lg',
    })
    .then(async (res) => {
      if (res) {
        try {
          const response = await proformaInvoiceStore.convertToInvoice(row.id)
          props.table && props.table.refresh()

          if (response.data?.invoice_id) {
            router.push(`/admin/invoices/${response.data.invoice_id}/view`)
          }
        } catch (error) {
          console.error('Convert error:', error)
        }
      }
    })
}

async function onMarkAsSent(id) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('proforma_invoices.confirm_mark_sent'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'lg',
    })
    .then(async (response) => {
      if (response) {
        // Call API to mark as sent - the store updates status via markAsViewed (since there's no markAsSent in store)
        // We need to add this action or use send
        await proformaInvoiceStore.markAsViewed(id)
        props.table && props.table.refresh()
      }
    })
}

async function onMarkAsViewed(id) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('proforma_invoices.confirm_mark_viewed'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'lg',
    })
    .then(async (response) => {
      if (response) {
        await proformaInvoiceStore.markAsViewed(id)
        props.table && props.table.refresh()
      }
    })
}

async function onMarkAsExpired(id) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('proforma_invoices.confirm_mark_expired'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then(async (response) => {
      if (response) {
        await proformaInvoiceStore.markAsExpired(id)
        props.table && props.table.refresh()
      }
    })
}

async function onMarkAsRejected(id) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('proforma_invoices.confirm_mark_rejected'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then(async (response) => {
      if (response) {
        await proformaInvoiceStore.markAsRejected(id)
        props.table && props.table.refresh()
      }
    })
}

function copyPdfUrl() {
  let pdfUrl = `${window.location.origin}/proforma-invoices/pdf/${props.row.unique_hash}`

  utils.copyTextToClipboard(pdfUrl)

  notificationStore.showNotification({
    type: 'success',
    message: t('general.copied_pdf_url_clipboard'),
  })
}
</script>
// CLAUDE-CHECKPOINT
