<template>
  <div>
    <div
      class="flex items-center px-4 py-3 hover:bg-gray-50 cursor-pointer group"
      :style="{ paddingLeft: `${depth * 24 + 16}px` }"
      @click="$emit('edit', node)"
    >
      <!-- Expand/collapse toggle -->
      <button
        v-if="node.children && node.children.length > 0"
        class="mr-2 text-gray-400 hover:text-gray-600 flex-shrink-0"
        @click.stop="isExpanded = !isExpanded"
      >
        <BaseIcon
          :name="isExpanded ? 'ChevronDownIcon' : 'ChevronRightIcon'"
          class="h-4 w-4"
        />
      </button>
      <span v-else class="mr-2 w-4 flex-shrink-0"></span>

      <!-- Color dot -->
      <span
        class="inline-block h-3 w-3 rounded-full mr-3 flex-shrink-0"
        :style="{ backgroundColor: node.color || '#6366f1' }"
      ></span>

      <!-- Code badge -->
      <span
        v-if="node.code"
        class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-mono font-medium bg-gray-100 text-gray-600 mr-3 flex-shrink-0"
      >
        {{ node.code }}
      </span>

      <!-- Name -->
      <span class="text-sm font-medium text-gray-900 flex-1 truncate">
        {{ node.name }}
      </span>

      <!-- Description (truncated) -->
      <span
        v-if="node.description"
        class="text-xs text-gray-400 truncate max-w-[200px] mr-4 hidden md:inline"
      >
        {{ node.description }}
      </span>

      <!-- Status -->
      <span
        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium mr-3 flex-shrink-0"
        :class="node.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'"
      >
        {{ node.is_active ? $t('general.active') : $t('general.inactive') }}
      </span>

      <!-- Actions (visible on hover) -->
      <div class="flex items-center space-x-1 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0">
        <button
          class="p-1 rounded text-primary-500 hover:text-primary-700 hover:bg-primary-50"
          :title="t('add_child')"
          @click.stop="$emit('add-child', node)"
        >
          <BaseIcon name="PlusIcon" class="h-4 w-4" />
        </button>
        <button
          class="p-1 rounded text-gray-400 hover:text-gray-600 hover:bg-gray-100"
          :title="$t('general.edit')"
          @click.stop="$emit('edit', node)"
        >
          <BaseIcon name="PencilIcon" class="h-4 w-4" />
        </button>
        <button
          class="p-1 rounded text-red-400 hover:text-red-600 hover:bg-red-50"
          :title="$t('general.delete')"
          @click.stop="$emit('delete', node)"
        >
          <BaseIcon name="TrashIcon" class="h-4 w-4" />
        </button>
      </div>
    </div>

    <!-- Children (recursive) -->
    <div v-if="isExpanded && node.children && node.children.length > 0">
      <CostCenterTreeNode
        v-for="child in node.children"
        :key="child.id"
        :node="child"
        :depth="depth + 1"
        @edit="(n) => $emit('edit', n)"
        @add-child="(n) => $emit('add-child', n)"
        @delete="(n) => $emit('delete', n)"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, defineOptions } from 'vue'
import ccMessages from '@/scripts/admin/i18n/cost-centers.js'

defineOptions({ name: 'CostCenterTreeNode' })

const locale = document.documentElement.lang || 'mk'
function t(key) {
  return ccMessages[locale]?.cost_centers?.[key]
    || ccMessages['en']?.cost_centers?.[key]
    || key
}

const props = defineProps({
  node: {
    type: Object,
    required: true,
  },
  depth: {
    type: Number,
    default: 0,
  },
})

defineEmits(['edit', 'add-child', 'delete'])

const isExpanded = ref(true)
</script>

<!-- CLAUDE-CHECKPOINT -->
