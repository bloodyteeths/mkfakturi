<template>
  <BaseDropdown>
    <template #activator>
      <BaseIcon name="EllipsisHorizontalIcon" class="h-5 text-gray-500" />
    </template>

    <BaseDropdownItem @click="editDevice(row.id)">
      <BaseIcon
        name="PencilIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('general.edit') }}
    </BaseDropdownItem>

    <BaseDropdownItem @click="removeDevice(row.id)">
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
import { useI18n } from 'vue-i18n'
import { useFiscalDeviceStore } from '@/scripts/admin/stores/fiscal-device'
import { useModalStore } from '@/scripts/stores/modal'

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
    default: null,
  },
})

const dialogStore = useDialogStore()
const { t } = useI18n()
const fiscalDeviceStore = useFiscalDeviceStore()
const modalStore = useModalStore()

async function editDevice(id) {
  await fiscalDeviceStore.fetchFiscalDevice(id)
  modalStore.openModal({
    title: t('settings.fiscal_devices.edit_device'),
    componentName: 'FiscalDeviceModal',
    size: 'sm',
    refreshData: props.loadData && props.loadData,
  })
}

function removeDevice(id) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('settings.fiscal_devices.confirm_delete'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then(async (res) => {
      if (res) {
        await fiscalDeviceStore.deleteFiscalDevice(id)
        props.loadData && props.loadData()
      }
    })
}
</script>
