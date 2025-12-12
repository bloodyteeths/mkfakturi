<template>
  <div class="account-tree">
    <!-- Account tree list -->
    <div v-if="treeData.length > 0" class="space-y-1">
      <AccountTreeNode
        v-for="node in treeData"
        :key="node.id"
        :node="node"
        :selected-id="selectedId"
        :editable="editable"
        :level="0"
        @select="handleSelect"
        @edit="handleEdit"
        @delete="handleDelete"
      />
    </div>

    <!-- Empty state -->
    <div
      v-else
      class="flex flex-col items-center justify-center py-12 text-center"
    >
      <BaseIcon name="FolderOpenIcon" class="h-12 w-12 text-gray-400" />
      <p class="mt-2 text-sm text-gray-500">No accounts found</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import AccountTreeNode from './AccountTreeNode.vue'

const props = defineProps({
  accounts: {
    type: Array,
    required: true,
    default: () => [],
  },
  selectedId: {
    type: [Number, String],
    default: null,
  },
  editable: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['select', 'edit', 'delete'])

/**
 * Build hierarchical tree structure from flat accounts array
 */
const treeData = computed(() => {
  if (!props.accounts || props.accounts.length === 0) {
    return []
  }

  const accountMap = {}
  const rootAccounts = []

  // Create a map of all accounts
  props.accounts.forEach((account) => {
    accountMap[account.id] = {
      ...account,
      children: [],
    }
  })

  // Build the tree structure
  props.accounts.forEach((account) => {
    if (account.parent_id && accountMap[account.parent_id]) {
      accountMap[account.parent_id].children.push(accountMap[account.id])
    } else {
      rootAccounts.push(accountMap[account.id])
    }
  })

  return rootAccounts
})

function handleSelect(account) {
  emit('select', account)
}

function handleEdit(account) {
  emit('edit', account)
}

function handleDelete(account) {
  emit('delete', account)
}
</script>

<style scoped>
.account-tree {
  @apply rounded-md border border-gray-200 bg-white;
}
</style>

// CLAUDE-CHECKPOINT
