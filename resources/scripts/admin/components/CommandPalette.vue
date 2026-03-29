<template>
  <Teleport to="body">
    <TransitionRoot :show="isOpen" as="template">
      <Dialog as="div" class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 md:p-20" @close="close">
        <!-- Backdrop -->
        <TransitionChild
          as="template"
          enter="ease-out duration-200"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="ease-in duration-150"
          leave-from="opacity-100"
          leave-to="opacity-0"
        >
          <DialogOverlay class="fixed inset-0 bg-gray-900/50" />
        </TransitionChild>

        <!-- Panel -->
        <TransitionChild
          as="template"
          enter="ease-out duration-200"
          enter-from="opacity-0 scale-95"
          enter-to="opacity-100 scale-100"
          leave="ease-in duration-150"
          leave-from="opacity-100 scale-100"
          leave-to="opacity-0 scale-95"
        >
          <div class="relative mx-auto max-w-xl transform rounded-xl bg-white shadow-2xl ring-1 ring-black/5 overflow-hidden">
            <!-- Search input -->
            <div class="flex items-center border-b border-gray-100 px-4">
              <BaseIcon name="MagnifyingGlassIcon" class="h-5 w-5 text-gray-400 shrink-0" />
              <input
                ref="searchInput"
                v-model="query"
                type="text"
                class="h-12 w-full border-0 bg-transparent pl-3 pr-4 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-0"
                :placeholder="$t('general.search_menu')"
                @keydown.down.prevent="moveDown"
                @keydown.up.prevent="moveUp"
                @keydown.enter.prevent="selectCurrent"
                @keydown.escape.prevent="close"
              />
              <kbd class="hidden sm:inline-flex items-center rounded border border-gray-200 px-1.5 text-[10px] font-medium text-gray-400">
                ESC
              </kbd>
            </div>

            <!-- Results -->
            <div v-if="filteredItems.length > 0" class="max-h-80 overflow-y-auto py-2">
              <div
                v-for="(item, index) in filteredItems"
                :key="item.link"
                class="flex items-center px-4 py-2.5 cursor-pointer transition-colors duration-100"
                :class="index === activeIndex ? 'bg-primary-50 text-primary-700' : 'text-gray-700 hover:bg-gray-50'"
                @click="navigateTo(item)"
                @mouseenter="activeIndex = index"
              >
                <BaseIcon
                  :name="item.icon"
                  class="h-5 w-5 shrink-0 mr-3"
                  :class="index === activeIndex ? 'text-primary-500' : 'text-gray-400'"
                />
                <div class="flex-1 min-w-0">
                  <div class="text-sm font-medium truncate">{{ getTranslatedTitle(item) }}</div>
                  <div v-if="item.submenu" class="text-xs text-gray-400 truncate">
                    {{ getSubmenuLabel(item) }}
                  </div>
                </div>
                <button
                  @click.stop="toggleFavorite(item.link)"
                  class="shrink-0 ml-2 p-1 rounded hover:bg-gray-100 transition-colors"
                  :class="isFavorite(item.link) ? 'text-amber-400' : 'text-gray-300 hover:text-amber-400'"
                >
                  <BaseIcon
                    name="StarIcon"
                    class="h-4 w-4"
                  />
                </button>
              </div>
            </div>

            <!-- Empty state -->
            <div v-else-if="query.length > 0" class="px-4 py-10 text-center">
              <BaseIcon name="MagnifyingGlassIcon" class="mx-auto h-6 w-6 text-gray-400" />
              <p class="mt-2 text-sm text-gray-500">{{ $t('general.no_results') }}</p>
            </div>

            <!-- Default: show favorites + recent -->
            <div v-else-if="favoriteItems.length > 0" class="max-h-80 overflow-y-auto py-2">
              <div class="px-4 py-1 text-[10px] font-semibold uppercase tracking-wider text-gray-400">
                {{ $t('navigation.favorites') }}
              </div>
              <div
                v-for="(item, index) in favoriteItems"
                :key="item.link"
                class="flex items-center px-4 py-2.5 cursor-pointer transition-colors duration-100"
                :class="index === activeIndex ? 'bg-primary-50 text-primary-700' : 'text-gray-700 hover:bg-gray-50'"
                @click="navigateTo(item)"
                @mouseenter="activeIndex = index"
              >
                <BaseIcon
                  :name="item.icon"
                  class="h-5 w-5 shrink-0 mr-3"
                  :class="index === activeIndex ? 'text-primary-500' : 'text-gray-400'"
                />
                <span class="text-sm font-medium truncate">{{ getTranslatedTitle(item) }}</span>
              </div>
            </div>

            <!-- Footer hint -->
            <div class="border-t border-gray-100 px-4 py-2 flex items-center gap-4 text-[10px] text-gray-400">
              <span class="flex items-center gap-1">
                <kbd class="rounded border border-gray-200 px-1 font-medium">&uarr;</kbd>
                <kbd class="rounded border border-gray-200 px-1 font-medium">&darr;</kbd>
                {{ $t('general.navigate') }}
              </span>
              <span class="flex items-center gap-1">
                <kbd class="rounded border border-gray-200 px-1 font-medium">&crarr;</kbd>
                {{ $t('general.open') }}
              </span>
            </div>
          </div>
        </TransitionChild>
      </Dialog>
    </TransitionRoot>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useGlobalStore } from '@/scripts/admin/stores/global'
import { useFavorites } from '@/scripts/admin/composables/useFavorites'

import {
  Dialog,
  DialogOverlay,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

const router = useRouter()
const globalStore = useGlobalStore()
const { t } = useI18n()
const { isFavorite, toggleFavorite, getFavoriteItems } = useFavorites()

const query = ref('')
const activeIndex = ref(0)
const searchInput = ref(null)

const isOpen = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val),
})

// All searchable items (main menu + settings menu)
const allItems = computed(() => {
  return [
    ...(globalStore.mainMenu || []),
    ...(globalStore.settingMenu || []),
  ]
})

const favoriteItems = computed(() => getFavoriteItems())

const filteredItems = computed(() => {
  if (!query.value) return []

  const q = query.value.toLowerCase().trim()
  return allItems.value
    .filter(item => {
      const title = t(item.title).toLowerCase()
      const name = (item.name || '').toLowerCase()
      return title.includes(q) || name.includes(q)
    })
    .slice(0, 10)
})

function getTranslatedTitle(item) {
  return t(item.title)
}

function getSubmenuLabel(item) {
  const labels = {
    documents: t('navigation.documents_group'),
    contacts: t('navigation.contacts'),
    money: t('navigation.money'),
    operations: t('navigation.operations'),
    finance: t('navigation.finance'),
  }
  return labels[item.submenu] || ''
}

function navigateTo(item) {
  router.push(item.link)
  close()
}

function close() {
  isOpen.value = false
  query.value = ''
  activeIndex.value = 0
}

function moveDown() {
  const items = query.value ? filteredItems.value : favoriteItems.value
  if (activeIndex.value < items.length - 1) {
    activeIndex.value++
  }
}

function moveUp() {
  if (activeIndex.value > 0) {
    activeIndex.value--
  }
}

function selectCurrent() {
  const items = query.value ? filteredItems.value : favoriteItems.value
  if (items[activeIndex.value]) {
    navigateTo(items[activeIndex.value])
  }
}

// Reset active index when results change
watch(filteredItems, () => {
  activeIndex.value = 0
})

// Focus search input when opened
watch(isOpen, (val) => {
  if (val) {
    nextTick(() => {
      searchInput.value?.focus()
    })
  }
})
</script>
