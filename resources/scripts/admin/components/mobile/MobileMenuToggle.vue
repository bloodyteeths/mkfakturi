<template>
  <!-- Hamburger button — visible only on small screens -->
  <button
    data-cy="mobile-menu-toggle"
    class="
      flex
      items-center
      justify-center
      p-1
      overflow-visible
      text-sm
      ease-linear
      bg-white
      border-0
      rounded
      cursor-pointer
      md:hidden
      hover:bg-gray-100
    "
    :class="{ 'is-active': isOpen }"
    @click.prevent="open"
  >
    <BaseIcon name="Bars3Icon" class="!w-6 !h-6 text-gray-500" />
  </button>

  <!-- Slide-out menu panel -->
  <Teleport to="body">
    <TransitionRoot as="template" :show="isOpen">
      <Dialog
        as="div"
        class="fixed inset-0 z-40 flex md:hidden"
        data-cy="mobile-menu"
        @close="close"
      >
        <!-- Dark overlay backdrop -->
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

        <!-- Slide-in panel from left -->
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
            <!-- Close button -->
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
                  @click="close"
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

            <!-- Menu content -->
            <div class="flex-1 h-0 pt-5 pb-4 overflow-y-auto">
              <div class="flex items-center shrink-0 px-4 mb-6">
                <MainLogo
                  class="block h-16 w-auto"
                  variant="clear"
                  alt="Facturino Logo"
                />
              </div>

              <nav
                v-for="(menu, index) in globalStore.menuGroups"
                :key="index"
                class="mt-5 space-y-1"
              >
                <router-link
                  v-for="item in menu"
                  :key="item.name"
                  :to="item.link"
                  :class="[
                    hasActiveUrl(item.link)
                      ? 'text-primary-500 border-primary-500 bg-gray-100'
                      : 'text-black',
                    'cursor-pointer px-0 pl-4 py-3 border-transparent flex items-start border-l-4 border-solid text-sm not-italic font-medium',
                  ]"
                  @click="close"
                >
                  <BaseIcon
                    :name="item.icon"
                    :class="[
                      hasActiveUrl(item.link)
                        ? 'text-primary-500'
                        : 'text-gray-400',
                      'mr-4 shrink-0 h-5 w-5 mt-0.5',
                    ]"
                  />
                  <div>
                    <div>{{ $t(item.title) }}</div>
                    <div
                      v-if="getHint(item.title)"
                      class="text-xs font-normal text-gray-400 mt-0.5 leading-tight"
                    >
                      {{ getHint(item.title) }}
                    </div>
                  </div>
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
  </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { useGlobalStore } from '@/scripts/admin/stores/global'

import {
  Dialog,
  DialogOverlay,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'

import MainLogo from '@/scripts/components/icons/MainLogo.vue'

const route = useRoute()
const globalStore = useGlobalStore()
const { t } = useI18n()

const isOpen = ref(false)

function getHint(titleKey) {
  const hintKey = titleKey.replace('navigation.', 'navigation_hints.')
  const hint = t(hintKey)
  return hint !== hintKey ? hint : ''
}

function open() {
  isOpen.value = true
  globalStore.setSidebarVisibility(true)
}

function close() {
  isOpen.value = false
  globalStore.setSidebarVisibility(false)
}

function hasActiveUrl(url) {
  return route.path.indexOf(url) > -1
}

// Close on route change
watch(
  () => route.path,
  () => {
    if (isOpen.value) {
      close()
    }
  }
)

</script>
