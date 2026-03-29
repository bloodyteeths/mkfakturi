<template>
  <BaseDropdown>
    <template #activator>
      <BaseButton v-if="route.name === 'expenses.view'" variant="primary">
        <BaseIcon name="EllipsisHorizontalIcon" class="h-5 text-white" />
      </BaseButton>
      <BaseIcon v-else name="EllipsisHorizontalIcon" class="h-5 text-gray-500" />
    </template>

    <!-- view expense -->
    <router-link
      v-if="userStore.hasAbilities(abilities.VIEW_EXPENSE)"
      :to="`/admin/expenses/${row.id}/view`"
    >
      <BaseDropdownItem>
        <BaseIcon
          name="EyeIcon"
          class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
        />
        {{ $t('expenses.view_expense') }}
      </BaseDropdownItem>
    </router-link>

    <!-- edit expense  -->
    <router-link
      v-if="userStore.hasAbilities(abilities.EDIT_EXPENSE)"
      :to="`/admin/expenses/${row.id}/edit`"
    >
      <BaseDropdownItem>
        <BaseIcon
          name="PencilIcon"
          class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
        />
        {{ $t('general.edit') }}
      </BaseDropdownItem>
    </router-link>

    <!-- clone expense -->
    <BaseDropdownItem
      v-if="userStore.hasAbilities(abilities.CREATE_EXPENSE)"
      @click="cloneExpense(row.id)"
    >
      <BaseIcon
        name="DocumentDuplicateIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('general.clone') }}
    </BaseDropdownItem>

    <!-- approve expense -->
    <BaseDropdownItem
      v-if="userStore.hasAbilities(abilities.EDIT_EXPENSE) && row.status === 'draft'"
      @click="approveExpense(row.id)"
    >
      <BaseIcon
        name="CheckCircleIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('general.approve') }}
    </BaseDropdownItem>

    <!-- post expense (approved → posted) -->
    <BaseDropdownItem
      v-if="userStore.hasAbilities(abilities.EDIT_EXPENSE) && row.status === 'approved'"
      @click="postExpense(row.id)"
    >
      <BaseIcon
        name="BookOpenIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('general.post') }}
    </BaseDropdownItem>

    <!-- download PDF -->
    <a
      :href="`/api/v1/expenses/${row.id}/rashoden-nalog`"
      target="_blank"
    >
      <BaseDropdownItem>
        <BaseIcon
          name="DocumentArrowDownIcon"
          class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
        />
        {{ $t('general.download_pdf') }}
      </BaseDropdownItem>
    </a>

    <!-- delete expense  -->
    <BaseDropdownItem
      v-if="userStore.hasAbilities(abilities.DELETE_EXPENSE)"
      @click="removeExpense(row.id)"
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
import { useDialogStore } from '@/scripts/stores/dialog'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useI18n } from 'vue-i18n'
import { useExpenseStore } from '@/scripts/admin/stores/expense'
import { useRoute, useRouter } from 'vue-router'
import { inject } from 'vue'
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
    default: null,
  },
})

const dialogStore = useDialogStore()
const notificationStore = useNotificationStore()
const { t } = useI18n()
const expenseStore = useExpenseStore()
const route = useRoute()
const router = useRouter()
const userStore = useUserStore()

const $utils = inject('utils')

function cloneExpense(id) {
  expenseStore.cloneExpense(id).then((res) => {
    if (res) {
      router.push(`/admin/expenses/${res.data.data.id}/edit`)
    }
  })
}

function approveExpense(id) {
  expenseStore.approveExpense(id).then((res) => {
    if (res) {
      props.loadData && props.loadData()
    }
  })
}

function postExpense(id) {
  expenseStore.postExpense(id).then((res) => {
    if (res) {
      props.loadData && props.loadData()
    }
  })
}

function removeExpense(id) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('expenses.confirm_delete', 1),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      size: 'lg',
      hideNoButton: false,
    })
    .then((res) => {
      if (res) {
        expenseStore.deleteExpense({ ids: [id] }).then((res) => {
          if (res) {
            props.loadData && props.loadData()
          }
        })
      }
    })
}
</script>
