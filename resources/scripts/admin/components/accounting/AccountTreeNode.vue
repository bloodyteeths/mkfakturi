<template>
  <div class="account-tree-node">
    <!-- Node content -->
    <div
      class="flex items-center justify-between border-b border-gray-100 px-4 py-3 hover:bg-gray-50"
      :class="{
        'bg-primary-50': isSelected,
        'cursor-pointer': true,
      }"
      :style="{ paddingLeft: `${level * 24 + 16}px` }"
      @click="handleClick"
    >
      <!-- Expand/Collapse icon -->
      <div class="flex flex-1 items-center space-x-3">
        <button
          v-if="hasChildren"
          type="button"
          class="text-gray-400 hover:text-gray-600 focus:outline-none"
          @click.stop="toggleExpanded"
        >
          <BaseIcon
            :name="isExpanded ? 'ChevronDownIcon' : 'ChevronRightIcon'"
            class="h-4 w-4"
          />
        </button>
        <div v-else class="w-4"></div>

        <!-- Account code -->
        <span class="font-mono text-sm font-medium text-gray-900">
          {{ node.code }}
        </span>

        <!-- Account name -->
        <span class="text-sm text-gray-700">
          {{ node.name }}
        </span>

        <!-- Account type badge -->
        <BaseBadge
          :bg-color="getTypeBadgeColor(node.type)"
          :text-color="getTypeTextColor(node.type)"
          class="ml-2"
        >
          {{ getTypeLabel(node.type) }}
        </BaseBadge>

        <!-- Status indicator -->
        <span
          v-if="!node.is_active"
          class="ml-2 text-xs text-gray-400"
        >
          (Inactive)
        </span>
      </div>

      <!-- Actions -->
      <div v-if="editable" class="flex items-center space-x-2" @click.stop>
        <button
          type="button"
          class="text-gray-400 hover:text-primary-600"
          @click="handleEdit"
        >
          <BaseIcon name="PencilIcon" class="h-4 w-4" />
        </button>
        <button
          v-if="!node.system_defined"
          type="button"
          class="text-gray-400 hover:text-red-600"
          @click="handleDelete"
        >
          <BaseIcon name="TrashIcon" class="h-4 w-4" />
        </button>
      </div>
    </div>

    <!-- Children (recursive) -->
    <div v-if="hasChildren && isExpanded">
      <AccountTreeNode
        v-for="child in node.children"
        :key="child.id"
        :node="child"
        :selected-id="selectedId"
        :editable="editable"
        :level="level + 1"
        @select="$emit('select', $event)"
        @edit="$emit('edit', $event)"
        @delete="$emit('delete', $event)"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  node: {
    type: Object,
    required: true,
  },
  selectedId: {
    type: [Number, String],
    default: null,
  },
  editable: {
    type: Boolean,
    default: false,
  },
  level: {
    type: Number,
    default: 0,
  },
})

const emit = defineEmits(['select', 'edit', 'delete'])

const isExpanded = ref(true)

const isSelected = computed(() => {
  return props.selectedId === props.node.id
})

const hasChildren = computed(() => {
  return props.node.children && props.node.children.length > 0
})

function toggleExpanded() {
  isExpanded.value = !isExpanded.value
}

function handleClick() {
  emit('select', props.node)
}

function handleEdit() {
  emit('edit', props.node)
}

function handleDelete() {
  emit('delete', props.node)
}

function getTypeBadgeColor(type) {
  const colors = {
    asset: 'bg-blue-100',
    liability: 'bg-red-100',
    equity: 'bg-purple-100',
    revenue: 'bg-green-100',
    expense: 'bg-orange-100',
  }
  return colors[type] || 'bg-gray-100'
}

function getTypeTextColor(type) {
  const colors = {
    asset: 'text-blue-800',
    liability: 'text-red-800',
    equity: 'text-purple-800',
    revenue: 'text-green-800',
    expense: 'text-orange-800',
  }
  return colors[type] || 'text-gray-800'
}

function getTypeLabel(type) {
  const labels = {
    asset: t('settings.accounts.type_asset'),
    liability: t('settings.accounts.type_liability'),
    equity: t('settings.accounts.type_equity'),
    revenue: t('settings.accounts.type_revenue'),
    expense: t('settings.accounts.type_expense'),
  }
  return labels[type] || type
}
</script>

// CLAUDE-CHECKPOINT
