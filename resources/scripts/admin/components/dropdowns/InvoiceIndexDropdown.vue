<template>
  <BaseDropdown>
    <template #activator>
      <BaseButton v-if="route.name === 'invoices.view'" variant="primary">
        <BaseIcon name="EllipsisHorizontalIcon" class="h-5 text-white" />
      </BaseButton>
      <BaseIcon v-else name="EllipsisHorizontalIcon" class="h-5 text-gray-500" />
    </template>

    <!-- Edit Invoice  -->
    <router-link
      v-if="userStore.hasAbilities(abilities.EDIT_INVOICE)"
      :to="`/admin/invoices/${row.id}/edit`"
    >
      <BaseDropdownItem v-show="row.allow_edit">
        <BaseIcon
          name="PencilIcon"
          class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
        />
        {{ $t('general.edit') }}
      </BaseDropdownItem>
    </router-link>

    <!-- Copy PDF url  -->
    <BaseDropdownItem v-if="route.name === 'invoices.view'" @click="copyPdfUrl">
      <BaseIcon
        name="LinkIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('general.copy_pdf_url') }}
    </BaseDropdownItem>

    <!-- View Invoice  -->
    <router-link
      v-if="
        route.name !== 'invoices.view' &&
        userStore.hasAbilities(abilities.VIEW_INVOICE)
      "
      :to="`/admin/invoices/${row.id}/view`"
    >
      <BaseDropdownItem>
        <BaseIcon
          name="EyeIcon"
          class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
        />
        {{ $t('general.view') }}
      </BaseDropdownItem>
    </router-link>

    <!-- Send Invoice Mail  -->
    <BaseDropdownItem v-if="canSendInvoice(row)" @click="sendInvoice(row)">
      <BaseIcon
        name="PaperAirplaneIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('invoices.send_invoice') }}
    </BaseDropdownItem>

    <!-- Resend Invoice -->
    <BaseDropdownItem v-if="canReSendInvoice(row)" @click="sendInvoice(row)">
      <BaseIcon
        name="PaperAirplaneIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('invoices.resend_invoice') }}
    </BaseDropdownItem>

    <!-- Record payment  -->
    <router-link :to="`/admin/payments/${row.id}/create`">
      <BaseDropdownItem
        v-if="row.status == 'SENT' && route.name !== 'invoices.view'"
      >
        <BaseIcon
          name="CreditCardIcon"
          class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
        />
        {{ $t('invoices.record_payment') }}
      </BaseDropdownItem>
    </router-link>

    <!-- Mark as sent Invoice -->
    <BaseDropdownItem v-if="canSendInvoice(row)" @click="onMarkAsSent(row.id)">
      <BaseIcon
        name="CheckCircleIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('invoices.mark_as_sent') }}
    </BaseDropdownItem>

    <!-- Clone Invoice into new invoice  -->
    <BaseDropdownItem
      v-if="userStore.hasAbilities(abilities.CREATE_INVOICE)"
      @click="cloneInvoiceData(row)"
    >
      <BaseIcon
        name="DocumentTextIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('invoices.clone_invoice') }}
    </BaseDropdownItem>

    <!-- Export XML (UBL) -->
    <ExportXml v-if="canExportXml(row)" :invoice="row" />

    <!-- Download E-Invoice XML (Signed) -->
    <BaseDropdownItem
      v-if="canDownloadEInvoiceXml"
      @click="downloadEInvoiceXml"
    >
      <BaseIcon
        name="DocumentArrowDownIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('e_invoice.download_xml') }}
    </BaseDropdownItem>

    <!--  Delete Invoice  -->
    <BaseDropdownItem
      v-if="userStore.hasAbilities(abilities.DELETE_INVOICE)"
      @click="removeInvoice(row.id)"
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
import { useInvoiceStore } from '@/scripts/admin/stores/invoice'
import { useEInvoiceStore } from '@/scripts/admin/stores/e-invoice'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useModalStore } from '@/scripts/stores/modal'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useUserStore } from '@/scripts/admin/stores/user'
import { inject, ref, computed, onMounted } from 'vue'
import abilities from '@/scripts/admin/stub/abilities'
import ExportXml from '@/scripts/components/ExportXml.vue'

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

const invoiceStore = useInvoiceStore()
const eInvoiceStore = useEInvoiceStore()
const modalStore = useModalStore()
const notificationStore = useNotificationStore()
const dialogStore = useDialogStore()
const userStore = useUserStore()

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const utils = inject('utils')

const eInvoice = ref(null)
const isLoadingEInvoice = ref(false)

function canReSendInvoice(row) {
  return (
    (row.status == 'SENT' || row.status == 'VIEWED') &&
    userStore.hasAbilities(abilities.SEND_INVOICE)
  )
}

function canSendInvoice(row) {
  return (
    row.status == 'DRAFT' &&
    route.name !== 'invoices.view' &&
    userStore.hasAbilities(abilities.SEND_INVOICE)
  )
}

function canExportXml(row) {
  return (
    (row.status == 'SENT' || row.status == 'PAID' || row.status == 'VIEWED') &&
    userStore.hasAbilities(abilities.VIEW_INVOICE)
  )
}

async function removeInvoice(id) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('invoices.confirm_delete'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then((res) => {
      id = id
      if (res) {
        invoiceStore.deleteInvoice({ ids: [id] }).then((res) => {
          if (res.data.success) {
            router.push('/admin/invoices')
            props.table && props.table.refresh()

            invoiceStore.$patch((state) => {
              state.selectedInvoices = []
              state.selectAllField = false
            })
          }
        })
      }
    })
}

async function cloneInvoiceData(data) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('invoices.confirm_clone'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'lg',
    })
    .then((res) => {
      if (res) {
        invoiceStore.cloneInvoice(data).then((res) => {
          router.push(`/admin/invoices/${res.data.data.id}/edit`)
        })
      }
    })
}

async function onMarkAsSent(id) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('invoices.invoice_mark_as_sent'),
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
        invoiceStore.markAsSent(data).then((response) => {
          props.table && props.table.refresh()
        })
      }
    })
}

async function sendInvoice(invoice) {
  modalStore.openModal({
    title: t('invoices.send_invoice'),
    componentName: 'SendInvoiceModal',
    id: invoice.id,
    data: invoice,
    variant: 'sm',
  })
}

function copyPdfUrl() {
  let pdfUrl = `${window.location.origin}/invoices/pdf/${props.row.unique_hash}`

  utils.copyTextToClipboard(pdfUrl)

  notificationStore.showNotification({
    type: 'success',
    message: t('general.copied_pdf_url_clipboard'),
  })
}

// Load e-invoice status when component mounts (for dropdown on view page)
onMounted(async () => {
  if (route.name === 'invoices.view' && props.row?.id) {
    await loadEInvoiceStatus()
  }
})

// Computed property to check if e-invoice XML can be downloaded
const canDownloadEInvoiceXml = computed(() => {
  if (!eInvoice.value) return false

  // Check if user has permission and e-invoice is signed
  return (
    userStore.hasAbilities(abilities.VIEW_INVOICE) &&
    eInvoice.value.status &&
    ['SIGNED', 'SUBMITTED', 'ACCEPTED', 'REJECTED'].includes(eInvoice.value.status)
  )
})

// Load e-invoice status
async function loadEInvoiceStatus() {
  if (isLoadingEInvoice.value) return

  isLoadingEInvoice.value = true
  try {
    await eInvoiceStore.fetchEInvoiceStatus(props.row.id)
    eInvoice.value = eInvoiceStore.currentEInvoice
  } catch (error) {
    // Silently fail if no e-invoice exists
    eInvoice.value = null
  } finally {
    isLoadingEInvoice.value = false
  }
}

// Download e-invoice XML
async function downloadEInvoiceXml() {
  if (!eInvoice.value?.id) {
    notificationStore.showNotification({
      type: 'error',
      message: t('e_invoice.no_einvoice'),
    })
    return
  }

  try {
    await eInvoiceStore.downloadXml(eInvoice.value.id)
  } catch (error) {
    console.error('Download e-invoice XML failed:', error)
  }
}
</script>
// CLAUDE-CHECKPOINT
