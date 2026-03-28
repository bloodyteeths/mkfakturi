<template>
  <BasePage>
    <BasePageHeader :title="$t('settings.setting', 1)" class="mb-6">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="/admin/dashboard" />
        <BaseBreadcrumbItem
          :title="$t('settings.setting', 2)"
          to="/admin/settings/account-settings"
          active
        />
      </BaseBreadcrumb>
    </BasePageHeader>

    <div class="w-full mb-6 select-wrapper lg:hidden">
      <BaseMultiselect
        v-model="currentSetting"
        :options="dropdownMenuItems"
        :can-deselect="false"
        value-prop="title"
        track-by="title"
        label="title"
        :group-select="false"
        :groups="true"
        group-label="label"
        group-options="items"
        object
        @update:modelValue="navigateToSetting"
      />
    </div>

    <div class="flex">
      <div class="hidden mt-1 lg:block min-w-[240px]">
        <template v-for="(section, sIndex) in groupedMenu" :key="sIndex">
          <p
            v-if="section.label"
            class="px-4 pt-5 pb-1 text-xs font-semibold tracking-wider text-gray-400 uppercase"
          >
            {{ section.label }}
          </p>
          <BaseList>
            <BaseListItem
              v-for="(menuItem, index) in section.items"
              :key="menuItem.link"
              :title="$t(menuItem.title)"
              :to="menuItem.link"
              :active="hasActiveUrl(menuItem.link)"
              :index="index"
              class="py-3"
            >
              <template #icon>
                <BaseIcon :name="menuItem.icon"></BaseIcon>
              </template>
            </BaseListItem>
          </BaseList>
        </template>
      </div>

      <div class="w-full overflow-hidden">
        <RouterView />
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, watchEffect, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useGlobalStore } from '@/scripts/admin/stores/global'
import BaseList from '@/scripts/components/list/BaseList.vue'
import BaseListItem from '@/scripts/components/list/BaseListItem.vue'
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

let currentSetting = ref({})

const globalStore = useGlobalStore()
const route = useRoute()
const router = useRouter()

const GROUP_LABELS = {
  account: 'settings.groups.account',
  business: 'settings.groups.business',
  team: 'settings.groups.team',
  integrations: 'settings.groups.integrations',
  system: 'settings.groups.system',
}

const groupedMenu = computed(() => {
  const sections = []
  let currentGroup = null
  let currentSection = null

  for (const item of globalStore.settingMenu) {
    const group = item.group || ''
    if (group !== currentGroup) {
      currentGroup = group
      const labelKey = GROUP_LABELS[group]
      currentSection = {
        label: labelKey ? t(labelKey) : '',
        items: [],
      }
      sections.push(currentSection)
    }
    currentSection.items.push(item)
  }
  return sections
})

const dropdownMenuItems = computed(() => {
  return globalStore.settingMenu.map((item) => {
    return Object.assign({}, item, {
      title: t(item.title),
    })
  })
})

watchEffect(() => {
  if (route.path === '/admin/settings' && !route.matched.some(r => r.path.includes('/admin/settings/'))) {
    router.replace('/admin/settings/account-settings')
  }

  const item = dropdownMenuItems.value.find((item) => {
    return item.link === route.path
  })

  currentSetting.value = item
})

function hasActiveUrl(url) {
  return route.path.indexOf(url) > -1
}

function navigateToSetting(setting) {
  return router.push(setting.link)
}
</script>
