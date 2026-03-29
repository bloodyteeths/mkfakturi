<template>
  <!-- DESKTOP MENU -->
  <div
    :class="[
      'hidden h-screen bg-white border-r border-gray-200 border-solid md:fixed md:flex md:flex-col md:inset-y-0 pt-16 transition-all duration-200 ease-in-out',
      globalStore.isSidebarCollapsed ? 'w-16' : 'w-56 xl:w-64'
    ]"
  >
    <!-- Scrollable Menu Items -->
    <div class="flex-1 overflow-y-auto pb-32">
      <!-- Favorites section -->
      <div v-if="getFavoriteItems().length > 0" class="p-0 m-0 mt-4 list-none">
        <div
          v-if="!globalStore.isSidebarCollapsed"
          class="px-6 mb-1 text-[10px] font-semibold uppercase tracking-wider text-gray-400"
        >
          {{ $t('navigation.favorites') }}
        </div>
        <div v-else class="flex justify-center mb-1">
          <BaseIcon name="StarIcon" class="h-3 w-3 text-gray-300" />
        </div>
        <router-link
          v-for="fav in getFavoriteItems()"
          :key="fav.link"
          :to="fav.link"
          class="sidebar-item group relative flex items-center py-2 border-l-4 border-solid text-sm cursor-pointer transition-colors duration-150"
          :class="[
            hasActiveUrl(fav.link)
              ? 'text-primary-500 border-primary-500 bg-gray-100'
              : 'text-gray-700 border-transparent hover:bg-gray-50 hover:text-gray-900',
            globalStore.isSidebarCollapsed ? 'justify-center px-0' : 'pl-6 pr-4'
          ]"
          @mouseenter="showTooltip($event, fav)"
          @mouseleave="hideTooltip"
        >
          <BaseIcon
            :name="fav.icon"
            :class="[
              hasActiveUrl(fav.link) ? 'text-primary-500' : 'text-gray-400',
              'shrink-0 h-4 w-4 transition-colors duration-150',
              globalStore.isSidebarCollapsed ? '' : 'mr-3'
            ]"
          />
          <span v-if="!globalStore.isSidebarCollapsed" class="truncate flex-1 text-sm">
            {{ $t(fav.title) }}
          </span>
          <button
            v-if="!globalStore.isSidebarCollapsed"
            @click.prevent.stop="toggleFavorite(fav.link)"
            class="shrink-0 h-4 w-4 text-amber-400 opacity-0 group-hover:opacity-100 transition-opacity duration-150"
          >
            <BaseIcon name="StarIcon" class="h-4 w-4" />
          </button>
        </router-link>
        <div class="mx-4 mt-2 border-b border-gray-100"></div>
      </div>

      <div
        v-for="(menu, groupIndex) in globalStore.menuGroups"
        :key="groupIndex"
        class="p-0 m-0 mt-4 list-none"
      >
        <!-- Groups with submenus (accounting section) -->
        <template v-if="hasSubmenus(menu)">
          <template v-for="group in getOrganizedMenu(menu)" :key="group.key || group.item?.link">

            <!-- Submenu group header -->
            <template v-if="group.type === 'submenu'">
              <button
                @click="handleSubmenuClick(group.key)"
                class="w-full sidebar-item relative flex items-center py-3 border-l-4 border-solid text-sm font-medium cursor-pointer transition-colors duration-150"
                :class="[
                  isSubmenuActive(group.items)
                    ? 'text-primary-500 border-primary-500 bg-gray-100'
                    : 'text-gray-700 border-transparent hover:bg-gray-50 hover:text-gray-900',
                  globalStore.isSidebarCollapsed ? 'justify-center px-0' : 'pl-6 pr-4'
                ]"
                @mouseenter="showTooltip($event, { title: group.title })"
                @mouseleave="hideTooltip"
              >
                <BaseIcon
                  :name="group.icon"
                  :class="[
                    isSubmenuActive(group.items)
                      ? 'text-primary-500'
                      : 'text-gray-400 hover:text-gray-600',
                    'shrink-0 h-5 w-5 transition-colors duration-150',
                    globalStore.isSidebarCollapsed ? '' : 'mr-3'
                  ]"
                />
                <span
                  v-if="!globalStore.isSidebarCollapsed"
                  class="truncate flex-1 text-left"
                >
                  {{ $t(group.title) }}
                </span>
                <BaseIcon
                  v-if="!globalStore.isSidebarCollapsed"
                  name="ChevronRightIcon"
                  :class="[
                    'h-4 w-4 text-gray-400 transition-transform duration-200',
                    expandedSubmenus[group.key] ? 'rotate-90' : ''
                  ]"
                />
              </button>

              <!-- Submenu children -->
              <div v-show="expandedSubmenus[group.key] && !globalStore.isSidebarCollapsed">
                <router-link
                  v-for="item in group.items"
                  :key="item.link"
                  :to="item.link"
                  class="sidebar-item group relative flex items-center py-2.5 border-l-4 border-solid text-sm cursor-pointer transition-colors duration-150 pl-12 pr-4"
                  :class="[
                    hasActiveUrl(item.link)
                      ? 'text-primary-500 border-primary-500 bg-gray-50'
                      : 'text-gray-600 border-transparent hover:bg-gray-50 hover:text-gray-900'
                  ]"
                  @mouseenter="showTooltip($event, item)"
                  @mouseleave="hideTooltip"
                >
                  <BaseIcon
                    :name="item.icon"
                    :class="[
                      hasActiveUrl(item.link)
                        ? 'text-primary-500'
                        : 'text-gray-400',
                      'shrink-0 h-4 w-4 mr-3 transition-colors duration-150'
                    ]"
                  />
                  <span class="truncate flex-1">{{ $t(item.title) }}</span>
                  <button
                    v-if="!globalStore.isSidebarCollapsed"
                    @click.prevent.stop="toggleFavorite(item.link)"
                    class="shrink-0 h-4 w-4 transition-opacity duration-150"
                    :class="isFavorite(item.link) ? 'text-amber-400 opacity-100' : 'text-gray-300 hover:text-amber-400 opacity-0 group-hover:opacity-100'"
                  >
                    <BaseIcon name="StarIcon" class="h-4 w-4" />
                  </button>
                </router-link>
              </div>
            </template>

            <!-- Regular item in a submenu group (e.g., back to dashboard) -->
            <router-link
              v-else
              :to="group.item.link"
              class="sidebar-item group relative flex items-center py-3 border-l-4 border-solid text-sm font-medium cursor-pointer transition-colors duration-150"
              :class="[
                hasActiveUrl(group.item.link)
                  ? 'text-primary-500 border-primary-500 bg-gray-100'
                  : 'text-gray-700 border-transparent hover:bg-gray-50 hover:text-gray-900',
                globalStore.isSidebarCollapsed ? 'justify-center px-0' : 'pl-6 pr-4'
              ]"
              @mouseenter="showTooltip($event, group.item)"
              @mouseleave="hideTooltip"
            >
              <BaseIcon
                :name="group.item.icon"
                :class="[
                  hasActiveUrl(group.item.link)
                    ? 'text-primary-500'
                    : 'text-gray-400 hover:text-gray-600',
                  'shrink-0 h-5 w-5 transition-colors duration-150',
                  globalStore.isSidebarCollapsed ? '' : 'mr-3'
                ]"
              />
              <span
                v-if="!globalStore.isSidebarCollapsed"
                class="truncate flex-1"
              >
                {{ $t(group.item.title) }}
              </span>
              <button
                v-if="!globalStore.isSidebarCollapsed"
                @click.prevent.stop="toggleFavorite(group.item.link)"
                class="shrink-0 h-4 w-4 transition-opacity duration-150"
                :class="isFavorite(group.item.link) ? 'text-amber-400 opacity-100' : 'text-gray-300 hover:text-amber-400 opacity-0 group-hover:opacity-100'"
              >
                <BaseIcon name="StarIcon" class="h-4 w-4" />
              </button>
            </router-link>
          </template>
        </template>

        <!-- Regular groups (non-accounting) -->
        <template v-else>
          <router-link
            v-for="item in menu"
            :key="item.link"
            :to="item.link"
            class="sidebar-item group relative flex items-center py-3 border-l-4 border-solid text-sm font-medium cursor-pointer transition-colors duration-150"
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
              class="truncate flex-1"
            >
              {{ $t(item.title) }}
            </span>

            <!-- Favorite star toggle - only shown when expanded, visible on hover -->
            <button
              v-if="!globalStore.isSidebarCollapsed"
              @click.prevent.stop="toggleFavorite(item.link)"
              class="shrink-0 h-4 w-4 transition-opacity duration-150"
              :class="isFavorite(item.link) ? 'text-amber-400 opacity-100' : 'text-gray-300 hover:text-amber-400 opacity-0 group-hover:opacity-100'"
            >
              <BaseIcon name="StarIcon" class="h-4 w-4" />
            </button>
          </router-link>
        </template>
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
        v-if="tooltip.visible"
        class="fixed px-3 py-2 bg-gray-900 text-white text-sm rounded-md shadow-lg pointer-events-none max-w-xs"
        :style="{ top: tooltip.top + 'px', left: tooltip.left + 'px', zIndex: 9999 }"
      >
        <div v-if="globalStore.isSidebarCollapsed" class="font-semibold mb-1">{{ tooltip.name }}</div>
        <div class="text-gray-300 text-xs leading-relaxed">{{ tooltip.description }}</div>
        <!-- Tooltip arrow -->
        <div class="absolute top-1/2 -left-1 -translate-y-1/2 w-2 h-2 bg-gray-900 rotate-45"></div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { reactive, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { useGlobalStore } from '@/scripts/admin/stores/global'
import { useSidebarMenu } from '@/scripts/admin/composables/useSidebarMenu'
import { useFavorites } from '@/scripts/admin/composables/useFavorites'

const route = useRoute()
const globalStore = useGlobalStore()
const { t } = useI18n()

const {
  hasActiveUrl,
  getHintKey,
  hasSubmenus,
  isSubmenuActive,
  getOrganizedMenu,
  autoExpandActiveSubmenus,
} = useSidebarMenu()

const { favorites, isFavorite, toggleFavorite, getFavoriteItems } = useFavorites()

// Track which submenus are expanded
const expandedSubmenus = reactive({})

// Tooltip state
const tooltip = reactive({
  visible: false,
  name: '',
  description: '',
  top: 0,
  left: 0
})

let tooltipTimeout = null

// Handle submenu header click
function handleSubmenuClick(key) {
  if (globalStore.isSidebarCollapsed) {
    globalStore.setSidebarCollapsed(false)
    expandedSubmenus[key] = true
  } else {
    expandedSubmenus[key] = !expandedSubmenus[key]
  }
}

// Auto-expand on route change
watch(() => route.path, () => autoExpandActiveSubmenus(expandedSubmenus), { immediate: true })

// Also auto-expand once menu data is loaded from bootstrap
watch(() => globalStore.mainMenu.length, (len) => {
  if (len > 0) autoExpandActiveSubmenus(expandedSubmenus)
})

function showTooltip(event, item) {
  if (tooltipTimeout) {
    clearTimeout(tooltipTimeout)
  }

  const element = event.currentTarget

  tooltipTimeout = setTimeout(() => {
    if (!element || !document.body.contains(element)) return

    const rect = element.getBoundingClientRect()
    const hintKey = getHintKey(item.title)
    const description = t(hintKey)

    // Only show if we have a real description (not the raw key)
    if (description === hintKey) return

    tooltip.name = t(item.title)
    tooltip.description = description
    tooltip.top = rect.top + (rect.height / 2) - 16
    tooltip.left = rect.right + 12
    tooltip.visible = true
  }, 300)
}

function hideTooltip() {
  if (tooltipTimeout) {
    clearTimeout(tooltipTimeout)
  }
  tooltip.visible = false
}
</script>
<!-- CLAUDE-CHECKPOINT -->
