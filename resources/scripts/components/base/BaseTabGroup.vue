<template>
  <div>
    <TabGroup :default-index="defaultIndex" @change="onChange">
      <TabList
        :class="[
          'flex border-b border-grey-light',
          'relative overflow-x-auto overflow-y-hidden',
          'lg:pb-0 lg:ml-0',
        ]"
      >
        <Tab
          v-for="(tab, index) in tabs"
          v-slot="{ selected }"
          :key="index"
          as="template"
        >
          <button
            :class="[
              'px-8 py-2 text-sm leading-5 font-medium flex items-center relative border-b-2 mt-4 focus:outline-none whitespace-nowrap',
              selected
                ? ' border-primary-400 text-black font-medium'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
            ]"
          >
            {{ tab?.title }}

            <BaseBadge
              v-if="tab?.count"
              class="!rounded-full overflow-hidden ml-2"
              :variant="tab?.['count-variant']"
              default-class="flex items-center justify-center w-5 h-5 p-1 rounded-full text-medium"
            >
              {{ tab.count }}
            </BaseBadge>
          </button>
        </Tab>
      </TabList>

      <slot name="before-tabs" />

      <TabPanels>
        <slot />
      </TabPanels>
    </TabGroup>
  </div>
</template>

<script setup>
import { computed, useSlots, Fragment } from 'vue'
import { TabGroup, TabList, Tab, TabPanels } from '@headlessui/vue'

const props = defineProps({
  defaultIndex: {
    type: Number,
    default: 0,
  },
  filter: {
    type: String,
    default: null,
  },
})

const emit = defineEmits(['change'])

const slots = useSlots()

/**
 * Flatten Fragment VNodes and filter out VNodes with null props
 * (comment nodes, text nodes, whitespace, v-if=false placeholders).
 * This prevents "Cannot read properties of null (reading 'title')"
 * when accessing tab.title / tab.count in the template.
 */
function flattenSlotVNodes(vnodes) {
  const result = []
  for (const node of vnodes) {
    if (node.type === Fragment && Array.isArray(node.children)) {
      result.push(...flattenSlotVNodes(node.children))
    } else if (node.props != null) {
      result.push(node)
    }
  }
  return result
}

const tabs = computed(() => {
  const vnodes = slots.default?.() || []
  return flattenSlotVNodes(vnodes).map((tab) => tab.props)
})

function onChange(d) {
  emit('change', tabs.value[d])
}
</script>
