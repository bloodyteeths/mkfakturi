<template>
  <BaseMultiselect
    v-model="selectedProject"
    v-bind="$attrs"
    track-by="name"
    value-prop="id"
    label="name"
    :filter-results="false"
    resolve-on-load
    :delay="500"
    :searchable="true"
    :options="searchProjects"
    label-value="name"
    :placeholder="$t('projects.select_project')"
    :can-deselect="true"
    class="w-full"
  >
    <template #option="{ option }">
      <div class="flex flex-col">
        <span>{{ option.name }}</span>
        <span v-if="option.code" class="text-xs text-gray-500">{{ option.code }}</span>
      </div>
    </template>

    <template v-if="showAction" #action>
      <BaseSelectAction
        v-if="userStore.hasAbilities(abilities.CREATE_PROJECT)"
        @click="addProject"
      >
        <BaseIcon
          name="FolderPlusIcon"
          class="h-4 mr-2 -ml-2 text-center text-primary-400"
        />

        {{ $t('projects.add_new_project') }}
      </BaseSelectAction>
    </template>
  </BaseMultiselect>
</template>

<script setup>
import { useProjectStore } from '@/scripts/admin/stores/project'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useUserStore } from '@/scripts/admin/stores/user'
import abilities from '@/scripts/admin/stub/abilities'
import { useRouter } from 'vue-router'

const props = defineProps({
  modelValue: {
    type: [String, Number, Object],
    default: '',
  },
  fetchAll: {
    type: Boolean,
    default: false,
  },
  showAction: {
    type: Boolean,
    default: false,
  },
  status: {
    type: String,
    default: null, // Can be 'open', 'in_progress', 'completed', etc. or null for all
  },
  customerId: {
    type: [String, Number],
    default: null, // Filter by customer
  },
})

const { t } = useI18n()

const emit = defineEmits(['update:modelValue'])

const projectStore = useProjectStore()
const userStore = useUserStore()
const router = useRouter()

const selectedProject = computed({
  get: () => props.modelValue,
  set: (value) => {
    emit('update:modelValue', value)
  },
})

async function searchProjects(search) {
  let data = {
    search,
  }

  if (props.fetchAll) {
    data.limit = 'all'
  }

  if (props.status) {
    data.status = props.status
  }

  if (props.customerId) {
    data.customer_id = props.customerId
  }

  let res = await projectStore.fetchProjectList(data)

  return res.data?.data || []
}

async function addProject() {
  router.push({ name: 'projects.create' })
}
</script>
// CLAUDE-CHECKPOINT
