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
              <div class="flex items-center gap-3 shrink-0 px-4 mb-6">
                <MainLogo
                  class="block h-12 w-12"
                  variant="icon"
                  alt="Facturino Logo"
                />
                <span class="text-xl font-bold text-primary-500">Facturino</span>
              </div>

              <nav
                v-for="(menu, index) in globalStore.menuGroups"
                :key="index"
                class="mt-5 space-y-1"
              >
                <template v-if="hasSubmenus(menu)">
                  <template v-for="group in getOrganizedMenu(menu)" :key="group.key || group.item?.link">
                    <!-- Collapsible submenu header -->
                    <template v-if="group.type === 'submenu'">
                      <button
                        @click="toggleSubmenu(group.key)"
                        class="w-full cursor-pointer px-0 pl-4 py-3 border-transparent flex items-center border-l-4 border-solid text-sm not-italic font-medium"
                        :class="[
                          isSubmenuActive(group.items)
                            ? 'text-primary-500 border-primary-500 bg-gray-100'
                            : 'text-black'
                        ]"
                      >
                        <BaseIcon
                          :name="group.icon"
                          :class="[
                            isSubmenuActive(group.items) ? 'text-primary-500' : 'text-gray-400',
                            'mr-4 shrink-0 h-5 w-5'
                          ]"
                        />
                        <span class="flex-1 text-left">{{ $t(group.title) }}</span>
                        <BaseIcon
                          name="ChevronRightIcon"
                          :class="[
                            'h-4 w-4 mr-4 text-gray-400 transition-transform duration-200',
                            expandedMobileSubmenus[group.key] ? 'rotate-90' : ''
                          ]"
                        />
                      </button>
                      <!-- Submenu children -->
                      <div v-show="expandedMobileSubmenus[group.key]">
                        <router-link
                          v-for="item in group.items"
                          :key="item.link"
                          :to="item.link"
                          :class="[
                            hasActiveUrl(item.link)
                              ? 'text-primary-500 border-primary-500 bg-gray-50'
                              : 'text-gray-600 border-transparent',
                            'cursor-pointer pl-12 pr-4 py-2.5 flex items-center border-l-4 border-solid text-sm',
                          ]"
                          @click="close"
                        >
                          <BaseIcon
                            :name="item.icon"
                            :class="[
                              hasActiveUrl(item.link) ? 'text-primary-500' : 'text-gray-400',
                              'mr-3 shrink-0 h-4 w-4',
                            ]"
                          />
                          <span>{{ $t(item.title) }}</span>
                        </router-link>
                      </div>
                    </template>
                    <!-- Regular item in a mixed group -->
                    <router-link
                      v-else
                      :to="group.item.link"
                      :class="[
                        hasActiveUrl(group.item.link)
                          ? 'text-primary-500 border-primary-500 bg-gray-100'
                          : 'text-black',
                        'cursor-pointer px-0 pl-4 py-3 border-transparent flex items-start border-l-4 border-solid text-sm not-italic font-medium',
                      ]"
                      @click="close"
                    >
                      <BaseIcon
                        :name="group.item.icon"
                        :class="[
                          hasActiveUrl(group.item.link) ? 'text-primary-500' : 'text-gray-400',
                          'mr-4 shrink-0 h-5 w-5 mt-0.5',
                        ]"
                      />
                      <div>
                        <div>{{ $t(group.item.title) }}</div>
                        <div
                          v-if="getHint(group.item.title)"
                          class="text-xs font-normal text-gray-400 mt-0.5 leading-tight"
                        >
                          {{ getHint(group.item.title) }}
                        </div>
                      </div>
                    </router-link>
                  </template>
                </template>
                <!-- Regular groups (no submenus at all) -->
                <template v-else>
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
                </template>
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
import { ref, reactive, watch } from 'vue'
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

// Submenu config (same definitions as desktop sidebar)
const submenuConfig = {
  setup: { title: 'partner.accounting.submenu.setup', icon: 'WrenchScrewdriverIcon' },
  ledgers: { title: 'partner.accounting.submenu.ledgers', icon: 'BookOpenIcon' },
  reports: { title: 'partner.accounting.submenu.reports', icon: 'ChartBarSquareIcon' },
  compliance: { title: 'partner.accounting.submenu.compliance', icon: 'ShieldCheckIcon' },
  operations: { title: 'navigation.operations', icon: 'Cog6ToothIcon' },
  finance: { title: 'navigation.finance', icon: 'ChartPieIcon' },
}

const expandedMobileSubmenus = reactive({})

function hasSubmenus(menu) {
  return menu.some(item => item.submenu)
}

function isSubmenuActive(items) {
  return items.some(item => hasActiveUrl(item.link))
}

function getOrganizedMenu(menu) {
  const result = []
  const groups = {}
  const insertedGroups = new Set()

  menu.forEach(item => {
    if (item.submenu && submenuConfig[item.submenu]) {
      if (!groups[item.submenu]) {
        groups[item.submenu] = []
      }
      groups[item.submenu].push(item)

      if (!insertedGroups.has(item.submenu)) {
        insertedGroups.add(item.submenu)
        result.push({
          type: 'submenu',
          key: item.submenu,
          title: submenuConfig[item.submenu].title,
          icon: submenuConfig[item.submenu].icon,
          items: groups[item.submenu],
        })
      }
    } else {
      result.push({ type: 'item', key: item.link, item })
    }
  })

  return result
}

function toggleSubmenu(key) {
  expandedMobileSubmenus[key] = !expandedMobileSubmenus[key]
}

function getHint(titleKey) {
  const hintKey = titleKey.replace('navigation.', 'navigation_hints.')
  const hint = t(hintKey)
  return hint !== hintKey ? hint : ''
}

function open() {
  isOpen.value = true
  globalStore.setSidebarVisibility(true)

  // Auto-expand submenus with active routes
  for (const menu of globalStore.menuGroups) {
    if (!hasSubmenus(menu)) continue
    const organized = getOrganizedMenu(menu)
    for (const group of organized) {
      if (group.type === 'submenu' && isSubmenuActive(group.items)) {
        expandedMobileSubmenus[group.key] = true
      }
    }
  }
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

// CLAUDE-CHECKPOINT
</script>
