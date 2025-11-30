<template>
  <BaseDropdown :content-loading="projectStore.isFetching">
    <template #activator>
      <BaseButton v-if="route.name === 'projects.view'" variant="primary">
        <BaseIcon name="EllipsisHorizontalIcon" class="h-5 text-white" />
      </BaseButton>
      <BaseIcon v-else name="EllipsisHorizontalIcon" class="h-5 text-gray-500" />
    </template>

    <!-- Edit Project  -->
    <router-link
      v-if="userStore.hasAbilities(abilities.EDIT_PROJECT)"
      :to="`/admin/projects/${row.id}/edit`"
    >
      <BaseDropdownItem>
        <BaseIcon
          name="PencilIcon"
          class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
        />
        {{ $t('general.edit') }}
      </BaseDropdownItem>
    </router-link>

    <!-- View Project -->
    <router-link
      v-if="
        route.name !== 'projects.view' &&
        userStore.hasAbilities(abilities.VIEW_PROJECT)
      "
      :to="`/admin/projects/${row.id}/view`"
    >
      <BaseDropdownItem>
        <BaseIcon
          name="EyeIcon"
          class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
        />
        {{ $t('general.view') }}
      </BaseDropdownItem>
    </router-link>

    <!-- Delete Project  -->
    <BaseDropdownItem
      v-if="userStore.hasAbilities(abilities.DELETE_PROJECT)"
      @click="removeProject(row.id)"
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
import { useProjectStore } from '@/scripts/admin/stores/project'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
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

const projectStore = useProjectStore()
const notificationStore = useNotificationStore()
const dialogStore = useDialogStore()
const userStore = useUserStore()

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

function removeProject(id) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('projects.confirm_delete'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then((res) => {
      if (res) {
        projectStore.deleteProjects({ ids: [id] }).then((response) => {
          if (response.data.success) {
            props.loadData && props.loadData()
            if (route.name === 'projects.view') {
              router.push('/admin/projects')
            }
            return true
          }
        })
      }
    })
}
</script>
// CLAUDE-CHECKPOINT
