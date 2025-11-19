<template>
  <div class="permission-editor">
    <div v-if="loading" class="flex items-center justify-center py-8">
      <BaseLoader />
    </div>

    <div v-else>
      <!-- Full Access Toggle -->
      <div class="p-4 mb-4 bg-purple-50 border border-purple-200 rounded-lg">
        <div class="flex items-center justify-between">
          <div>
            <label class="text-sm font-medium text-purple-900">
              {{ $t('partners.permissions.full_access') }}
            </label>
            <p class="text-xs text-purple-700">
              {{ $t('partners.permissions.full_access_description') }}
            </p>
          </div>
          <BaseSwitch
            v-model="fullAccessEnabled"
            @update:modelValue="onFullAccessToggle"
          />
        </div>
      </div>

      <!-- Individual Permission Groups -->
      <div class="space-y-4">
        <div
          v-for="(group, category) in permissionGroups"
          :key="category"
          class="p-4 bg-white border border-gray-200 rounded-lg"
          :class="{ 'opacity-50 pointer-events-none': fullAccessEnabled }"
        >
          <!-- Group Header with Expand/Collapse -->
          <div class="flex items-center justify-between mb-3">
            <div class="flex items-center space-x-3 flex-1">
              <!-- Group Toggle -->
              <BaseCheckbox
                :id="`group-${category}`"
                :model-value="isGroupFullySelected(category)"
                :indeterminate="isGroupPartiallySelected(category)"
                @update:modelValue="toggleGroup(category)"
                variant="primary"
                :disabled="fullAccessEnabled"
              />

              <label
                :for="`group-${category}`"
                class="text-sm font-medium text-gray-900 cursor-pointer flex-1"
              >
                {{ group.label }}
              </label>

              <!-- Permission Count Badge -->
              <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded">
                {{ getSelectedCountInGroup(category) }} / {{ group.permissions.length }}
              </span>
            </div>

            <!-- Expand/Collapse Button -->
            <button
              type="button"
              @click="toggleGroupExpansion(category)"
              class="ml-2 text-gray-400 hover:text-gray-600"
            >
              <BaseIcon
                :name="expandedGroups[category] ? 'ChevronUpIcon' : 'ChevronDownIcon'"
                class="w-5 h-5"
              />
            </button>
          </div>

          <!-- Individual Permissions (Collapsible) -->
          <div
            v-show="expandedGroups[category]"
            class="ml-8 space-y-2 border-t border-gray-100 pt-3"
          >
            <div
              v-for="permission in group.permissions"
              :key="permission.value"
              class="flex items-center"
            >
              <BaseCheckbox
                :id="`perm-${permission.value}`"
                v-model="selectedPermissions"
                :value="permission.value"
                variant="primary"
                :disabled="fullAccessEnabled"
              />
              <label
                :for="`perm-${permission.value}`"
                class="ml-2 text-sm text-gray-700 cursor-pointer"
              >
                {{ permission.label }}
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- Selected Permissions Summary -->
      <div v-if="!fullAccessEnabled && selectedPermissions.length > 0" class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
        <div class="text-xs font-medium text-blue-900 mb-1">
          {{ $t('partners.permissions.selected_permissions') }} ({{ selectedPermissions.length }})
        </div>
        <div class="flex flex-wrap gap-1">
          <span
            v-for="perm in selectedPermissions"
            :key="perm"
            class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded"
          >
            {{ getPermissionLabel(perm) }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'

const { t } = useI18n()

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => [],
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue'])

const loading = ref(false)
const permissionGroups = ref({})
const expandedGroups = ref({})
const selectedPermissions = ref([...props.modelValue])

// Full Access state
const fullAccessEnabled = computed({
  get: () => selectedPermissions.value.includes('full_access'),
  set: (value) => {
    if (value) {
      selectedPermissions.value = ['full_access']
    } else {
      selectedPermissions.value = []
    }
  },
})

// Load available permissions from API
async function loadPermissions() {
  loading.value = true
  try {
    const response = await axios.get('/partners/permissions')
    permissionGroups.value = response.data.grouped

    // Initialize all groups as expanded by default
    Object.keys(permissionGroups.value).forEach((category) => {
      expandedGroups.value[category] = true
    })
  } catch (error) {
    console.error('Failed to load permissions:', error)
  } finally {
    loading.value = false
  }
}

function toggleGroupExpansion(category) {
  expandedGroups.value[category] = !expandedGroups.value[category]
}

function isGroupFullySelected(category) {
  if (fullAccessEnabled.value) return false

  const group = permissionGroups.value[category]
  if (!group) return false

  return group.permissions.every((perm) =>
    selectedPermissions.value.includes(perm.value)
  )
}

function isGroupPartiallySelected(category) {
  if (fullAccessEnabled.value) return false

  const group = permissionGroups.value[category]
  if (!group) return false

  const selectedCount = group.permissions.filter((perm) =>
    selectedPermissions.value.includes(perm.value)
  ).length

  return selectedCount > 0 && selectedCount < group.permissions.length
}

function getSelectedCountInGroup(category) {
  if (fullAccessEnabled.value) return 0

  const group = permissionGroups.value[category]
  if (!group) return 0

  return group.permissions.filter((perm) =>
    selectedPermissions.value.includes(perm.value)
  ).length
}

function toggleGroup(category) {
  if (fullAccessEnabled.value) return

  const group = permissionGroups.value[category]
  if (!group) return

  const allSelected = isGroupFullySelected(category)

  if (allSelected) {
    // Deselect all in group
    group.permissions.forEach((perm) => {
      const index = selectedPermissions.value.indexOf(perm.value)
      if (index > -1) {
        selectedPermissions.value.splice(index, 1)
      }
    })
  } else {
    // Select all in group
    group.permissions.forEach((perm) => {
      if (!selectedPermissions.value.includes(perm.value)) {
        selectedPermissions.value.push(perm.value)
      }
    })
  }
}

function onFullAccessToggle(value) {
  if (value) {
    // Enable full access - clear all other permissions
    selectedPermissions.value = ['full_access']
  } else {
    // Disable full access - clear selection
    selectedPermissions.value = []
  }
}

function getPermissionLabel(permissionValue) {
  for (const group of Object.values(permissionGroups.value)) {
    const perm = group.permissions.find((p) => p.value === permissionValue)
    if (perm) return perm.label
  }
  return permissionValue
}

// Watch for changes and emit to parent
watch(
  selectedPermissions,
  (newValue) => {
    emit('update:modelValue', [...newValue])
  },
  { deep: true }
)

// Watch for external changes to modelValue (only when different to avoid infinite loop)
watch(
  () => props.modelValue,
  (newValue) => {
    // Only update if the value actually changed to prevent infinite loop
    const currentValues = [...selectedPermissions.value].sort()
    const newValues = [...newValue].sort()

    if (JSON.stringify(currentValues) !== JSON.stringify(newValues)) {
      selectedPermissions.value = [...newValue]
    }
  },
  { deep: true }
)

onMounted(() => {
  loadPermissions()
})
</script>

<style scoped>
.permission-editor {
  @apply w-full;
}
</style>

<!-- CLAUDE-CHECKPOINT -->
