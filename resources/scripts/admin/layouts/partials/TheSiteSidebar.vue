<template>
  <!-- MOBILE MENU -->
  <TransitionRoot as="template" :show="globalStore.isSidebarOpen">
    <Dialog
      as="div"
      class="fixed inset-0 z-40 flex md:hidden"
      @close="globalStore.setSidebarVisibility(false)"
    >
      <TransitionChild
        as="template"
        enter="transition-opacity ease-linear duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="transition-opacity ease-linear duration-300"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <DialogOverlay class="fixed inset-0 bg-gray-600 bg-opacity-75" />
      </TransitionChild>

      <TransitionChild
        as="template"
        enter="transition ease-in-out duration-300"
        enter-from="-translate-x-full"
        enter-to="translate-x-0"
        leave="transition ease-in-out duration-300"
        leave-from="translate-x-0"
        leave-to="-translate-x-full"
      >
        <div class="relative flex flex-col flex-1 w-full max-w-xs bg-white">
          <TransitionChild
            as="template"
            enter="ease-in-out duration-300"
            enter-from="opacity-0"
            enter-to="opacity-100"
            leave="ease-in-out duration-300"
            leave-from="opacity-100"
            leave-to="opacity-0"
          >
            <div class="absolute top-0 right-0 pt-2 -mr-12">
              <button
                class="
                  flex
                  items-center
                  justify-center
                  w-10
                  h-10
                  ml-1
                  rounded-full
                  focus:outline-none
                  focus:ring-2
                  focus:ring-inset
                  focus:ring-white
                "
                @click="globalStore.setSidebarVisibility(false)"
              >
                <span class="sr-only">Close sidebar</span>
                <BaseIcon
                  name="XMarkIcon"
                  class="w-6 h-6 text-white"
                  aria-hidden="true"
                />
              </button>
            </div>
          </TransitionChild>
          <div class="flex-1 h-0 pt-5 pb-4 overflow-y-auto">
            <div class="flex items-center shrink-0 px-4 mb-6">
              <MainLogo
                class="block h-16 w-auto"
                variant="clear"
                alt="Facturino Logo"
              />
            </div>

            <nav
              v-for="menu in globalStore.menuGroups"
              :key="menu"
              class="mt-5 space-y-1"
            >
              <router-link
                v-for="item in menu"
                :key="item.name"
                :to="item.link"
                :class="[
                  hasActiveUrl(item.link)
                    ? 'text-primary-500 border-primary-500 bg-gray-100 '
                    : 'text-black',
                  'cursor-pointer px-0 pl-4 py-3 border-transparent flex items-center border-l-4 border-solid text-sm not-italic font-medium',
                ]"
                @click="globalStore.setSidebarVisibility(false)"
              >
                <BaseIcon
                  :name="item.icon"
                  :class="[
                    hasActiveUrl(item.link)
                      ? 'text-primary-500 '
                      : 'text-gray-400',
                    'mr-4 shrink-0 h-5 w-5',
                  ]"
                  @click="globalStore.setSidebarVisibility(false)"
                />
                {{ $t(item.title) }}
              </router-link>
            </nav>
          </div>
        </div>
      </TransitionChild>
      <div class="shrink-0 w-14">
        <!-- Force sidebar to shrink to fit close icon -->
      </div>
    </Dialog>
  </TransitionRoot>

  <!-- DESKTOP MENU -->
  <div
    :class="[
      'hidden h-screen bg-white border-r border-gray-200 border-solid md:fixed md:flex md:flex-col md:inset-y-0 pt-16 transition-all duration-200 ease-in-out',
      globalStore.isSidebarCollapsed ? 'w-16' : 'w-56 xl:w-64'
    ]"
  >
    <!-- Scrollable Menu Items -->
    <div class="flex-1 overflow-y-auto pb-32">
      <div
        v-for="menu in globalStore.menuGroups"
        :key="menu"
        class="p-0 m-0 mt-6 list-none"
      >
        <router-link
          v-for="item in menu"
          :key="item.link"
          :to="item.link"
          class="sidebar-item relative flex items-center py-3 border-l-4 border-solid text-sm font-medium cursor-pointer transition-colors duration-150"
          :class="[
            hasActiveUrl(item.link)
              ? 'text-primary-500 border-primary-500 bg-gray-100'
              : 'text-gray-700 border-transparent hover:bg-gray-50 hover:text-gray-900',
            globalStore.isSidebarCollapsed ? 'justify-center px-0' : 'pl-6 pr-4'
          ]"
          @mouseenter="showTooltip($event, item)"
          @mouseleave="hideTooltip"
        >
          <BaseIcon
            :name="item.icon"
            :class="[
              hasActiveUrl(item.link)
                ? 'text-primary-500'
                : 'text-gray-400 hover:text-gray-600',
              'shrink-0 h-5 w-5 transition-colors duration-150',
              globalStore.isSidebarCollapsed ? '' : 'mr-3'
            ]"
          />

          <!-- Menu text - only shown when expanded -->
          <span
            v-if="!globalStore.isSidebarCollapsed"
            class="truncate"
          >
            {{ $t(item.title) }}
          </span>
        </router-link>
      </div>
    </div>

    <!-- Collapse Toggle Button - at bottom of sidebar -->
    <div class="border-t border-gray-200 p-3 bg-white">
      <button
        @click="globalStore.toggleSidebarCollapsed()"
        class="
          w-full flex items-center justify-center
          py-2 px-3 rounded-md
          text-gray-500 hover:text-gray-700 hover:bg-gray-100
          transition-colors duration-150
          focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2
        "
        :title="globalStore.isSidebarCollapsed ? $t('general.expand_sidebar') : $t('general.collapse_sidebar')"
      >
        <BaseIcon
          :name="globalStore.isSidebarCollapsed ? 'ChevronDoubleRightIcon' : 'ChevronDoubleLeftIcon'"
          class="h-5 w-5"
        />
        <span
          v-if="!globalStore.isSidebarCollapsed"
          class="ml-2 text-sm"
        >
          {{ $t('general.collapse') }}
        </span>
      </button>
    </div>
  </div>

  <!-- Tooltip Portal - rendered outside sidebar to avoid overflow clipping -->
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-150"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-100"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="tooltip.visible && globalStore.isSidebarCollapsed"
        class="fixed px-3 py-2 bg-gray-900 text-white text-sm rounded-md shadow-lg whitespace-nowrap pointer-events-none"
        :style="{ top: tooltip.top + 'px', left: tooltip.left + 'px', zIndex: 9999 }"
      >
        {{ tooltip.text }}
        <!-- Tooltip arrow -->
        <div class="absolute top-1/2 -left-1 -translate-y-1/2 w-2 h-2 bg-gray-900 rotate-45"></div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useI18n } from 'vue-i18n'
import MainLogo from '@/scripts/components/icons/MainLogo.vue'

import {
  Dialog,
  DialogOverlay,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'

import { useRoute } from 'vue-router'
import { useGlobalStore } from '@/scripts/admin/stores/global'

const route = useRoute()
const globalStore = useGlobalStore()
const { t } = useI18n()

// Tooltip state
const tooltip = reactive({
  visible: false,
  text: '',
  top: 0,
  left: 0
})

let tooltipTimeout = null

function hasActiveUrl(url) {
  return route.path.indexOf(url) > -1
}

function showTooltip(event, item) {
  if (!globalStore.isSidebarCollapsed) return

  // Clear any existing timeout
  if (tooltipTimeout) {
    clearTimeout(tooltipTimeout)
  }

  // Capture element reference before setTimeout (event.currentTarget becomes null after event processing)
  const element = event.currentTarget

  // Small delay before showing tooltip
  tooltipTimeout = setTimeout(() => {
    // Check if element still exists in DOM
    if (!element || !document.body.contains(element)) return

    const rect = element.getBoundingClientRect()
    tooltip.text = t(item.title)
    tooltip.top = rect.top + (rect.height / 2) - 16 // Center vertically
    tooltip.left = rect.right + 12 // Position to the right of sidebar
    tooltip.visible = true
  }, 100)
}

function hideTooltip() {
  if (tooltipTimeout) {
    clearTimeout(tooltipTimeout)
  }
  tooltip.visible = false
}
</script>
