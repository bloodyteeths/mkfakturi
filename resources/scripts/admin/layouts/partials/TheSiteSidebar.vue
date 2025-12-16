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
      'hidden h-screen pb-32 overflow-y-auto bg-white border-r border-gray-200 border-solid md:fixed md:flex md:flex-col md:inset-y-0 pt-16 transition-all duration-300 ease-in-out',
      globalStore.isSidebarCollapsed ? 'w-16' : 'w-56 xl:w-64'
    ]"
    @mouseenter="handleMouseEnter"
    @mouseleave="handleMouseLeave"
  >
    <!-- Collapse Toggle Button -->
    <button
      @click="globalStore.toggleSidebarCollapsed()"
      class="absolute top-20 -right-3 z-50 flex items-center justify-center w-6 h-6 bg-white border border-gray-200 rounded-full shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500"
      :title="globalStore.isSidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
    >
      <BaseIcon
        :name="globalStore.isSidebarCollapsed ? 'ChevronRightIcon' : 'ChevronLeftIcon'"
        class="w-4 h-4 text-gray-500"
      />
    </button>

    <div
      v-for="menu in globalStore.menuGroups"
      :key="menu"
      class="p-0 m-0 mt-6 list-none"
    >
      <router-link
        v-for="item in menu"
        :key="item"
        :to="item.link"
        :class="[
          hasActiveUrl(item.link)
            ? 'text-primary-500 border-primary-500 bg-gray-100 '
            : 'text-black',
          'cursor-pointer hover:bg-gray-50 py-3 group flex items-center border-l-4 border-solid text-sm not-italic font-medium relative',
          globalStore.isSidebarCollapsed ? 'px-0 justify-center' : 'px-0 pl-6'
        ]"
        :title="globalStore.isSidebarCollapsed && !isHovering ? $t(item.title) : ''"
      >
        <BaseIcon
          :name="item.icon"
          :class="[
            hasActiveUrl(item.link)
              ? 'text-primary-500 group-hover:text-primary-500 '
              : 'text-gray-400 group-hover:text-black',
            'shrink-0 h-5 w-5',
            globalStore.isSidebarCollapsed && !isHovering ? '' : 'mr-4'
          ]"
        />

        <!-- Menu text - hidden when collapsed, shown on hover -->
        <span
          v-if="!globalStore.isSidebarCollapsed || isHovering"
          class="whitespace-nowrap"
        >
          {{ $t(item.title) }}
        </span>

        <!-- Tooltip for collapsed state -->
        <div
          v-if="globalStore.isSidebarCollapsed && !isHovering"
          class="absolute left-full ml-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-50 pointer-events-none"
        >
          {{ $t(item.title) }}
        </div>
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
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

const isHovering = ref(false)
let hoverTimeout = null

function hasActiveUrl(url) {
  return route.path.indexOf(url) > -1
}

function handleMouseEnter() {
  if (globalStore.isSidebarCollapsed) {
    // Small delay before expanding on hover
    hoverTimeout = setTimeout(() => {
      isHovering.value = true
    }, 200)
  }
}

function handleMouseLeave() {
  if (hoverTimeout) {
    clearTimeout(hoverTimeout)
  }
  isHovering.value = false
}
</script>
