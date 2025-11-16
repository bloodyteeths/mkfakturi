<template>
  <BaseDropdown :content-loading="suppliersStore.isFetchingView">
    <template #activator>
      <BaseButton v-if="route.name === 'suppliers.view'" variant="primary">
        <BaseIcon name="EllipsisHorizontalIcon" class="h-5 text-white" />
      </BaseButton>
      <BaseIcon v-else name="EllipsisHorizontalIcon" class="h-5 text-gray-500" />
    </template>

    <!-- Edit Supplier  -->
    <router-link
      v-if="userStore.hasAbilities(abilities.EDIT_SUPPLIER)"
      :to="`/admin/suppliers/${row.id}/edit`"
    >
      <BaseDropdownItem>
        <BaseIcon
          name="PencilIcon"
          class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
        />
        {{ $t('general.edit') }}
      </BaseDropdownItem>
    </router-link>

    <!-- View Supplier -->
    <router-link
      v-if="
        route.name !== 'suppliers.view' &&
        userStore.hasAbilities(abilities.VIEW_SUPPLIER)
      "
      :to="`suppliers/${row.id}/view`"
    >
      <BaseDropdownItem>
        <BaseIcon
          name="EyeIcon"
          class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
        />
        {{ $t('general.view') }}
      </BaseDropdownItem>
    </router-link>

    <!-- Delete Supplier  -->
    <BaseDropdownItem
      v-if="userStore.hasAbilities(abilities.DELETE_SUPPLIER)"
      @click="removeSupplier(row.id)"
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
import { useSuppliersStore } from '@/scripts/admin/stores/suppliers'
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

const suppliersStore = useSuppliersStore()
const notificationStore = useNotificationStore()
const dialogStore = useDialogStore()
const userStore = useUserStore()

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const utils = inject('utils')

function removeSupplier(id) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('suppliers.confirm_delete'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then((res) => {
      if (res) {
        suppliersStore.deleteSupplier(id).then((response) => {
          if (response.data.success) {
            props.loadData && props.loadData()
            return true
          }
        })
      }
    })
}
</script>
// CLAUDE-CHECKPOINT
