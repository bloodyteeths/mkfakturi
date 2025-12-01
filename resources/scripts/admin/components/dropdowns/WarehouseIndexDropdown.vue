<template>
  <BaseDropdown :content-loading="warehouseStore.isLoading">
    <template #activator>
      <BaseIcon name="EllipsisHorizontalIcon" class="h-5 text-gray-500" />
    </template>

    <!-- Edit Warehouse  -->
    <router-link
      v-if="userStore.hasAbilities(abilities.EDIT_WAREHOUSE)"
      :to="`/admin/stock/warehouses/${row.id}/edit`"
    >
      <BaseDropdownItem>
        <BaseIcon
          name="PencilIcon"
          class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
        />
        {{ $t('general.edit') }}
      </BaseDropdownItem>
    </router-link>

    <!-- Set as Default -->
    <BaseDropdownItem
      v-if="!row.is_default && userStore.hasAbilities(abilities.EDIT_WAREHOUSE)"
      @click="setAsDefault(row.id)"
    >
      <BaseIcon
        name="StarIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('warehouses.set_as_default') }}
    </BaseDropdownItem>

    <!-- Delete Warehouse  -->
    <BaseDropdownItem
      v-if="userStore.hasAbilities(abilities.DELETE_WAREHOUSE)"
      @click="removeWarehouse(row.id)"
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
import { useWarehouseStore } from '@/scripts/admin/stores/warehouse'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { useUserStore } from '@/scripts/admin/stores/user'
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

const warehouseStore = useWarehouseStore()
const notificationStore = useNotificationStore()
const dialogStore = useDialogStore()
const userStore = useUserStore()

const { t } = useI18n()
const route = useRoute()

function setAsDefault(id) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('warehouses.confirm_set_default'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'lg',
    })
    .then((res) => {
      if (res) {
        warehouseStore.setDefaultWarehouse(id).then((response) => {
          if (response.data) {
            props.loadData && props.loadData()
            return true
          }
        })
      }
    })
}

function removeWarehouse(id) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('warehouses.confirm_delete'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then((res) => {
      if (res) {
        warehouseStore.deleteWarehouse(id).then((response) => {
          if (response.data) {
            props.loadData && props.loadData()
            return true
          }
        })
      }
    })
}
</script>
// CLAUDE-CHECKPOINT
