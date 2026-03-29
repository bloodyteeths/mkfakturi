import { reactive, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useGlobalStore } from '@/scripts/admin/stores/global'

// Submenu group definitions (title i18n key + icon)
const submenuConfig = {
  // Main sidebar collapsible sections
  documents: { title: 'navigation.documents_group', icon: 'DocumentTextIcon' },
  contacts: { title: 'navigation.contacts', icon: 'UsersIcon' },
  money: { title: 'navigation.money', icon: 'CurrencyDollarIcon' },
  operations: { title: 'navigation.operations', icon: 'Cog6ToothIcon' },
  finance: { title: 'navigation.finance', icon: 'ChartPieIcon' },
  // Partner accounting submenus
  setup: { title: 'partner.accounting.submenu.setup', icon: 'WrenchScrewdriverIcon' },
  ledgers: { title: 'partner.accounting.submenu.ledgers', icon: 'BookOpenIcon' },
  reports: { title: 'partner.accounting.submenu.reports', icon: 'ChartBarSquareIcon' },
  compliance: { title: 'partner.accounting.submenu.compliance', icon: 'ShieldCheckIcon' },
}

export function useSidebarMenu() {
  const route = useRoute()
  const globalStore = useGlobalStore()
  const { t } = useI18n()

  function hasActiveUrl(url) {
    return route.path === url || route.path.startsWith(url + '/')
  }

  function getHintKey(titleKey) {
    return titleKey.replace('navigation.', 'navigation_hints.')
  }

  function getHint(titleKey) {
    const hintKey = getHintKey(titleKey)
    const hint = t(hintKey)
    return hint !== hintKey ? hint : ''
  }

  function hasSubmenus(menu) {
    return menu.some(item => item.submenu)
  }

  function isSubmenuActive(items) {
    return items.some(item => hasActiveUrl(item.link))
  }

  // Organize a menu group into submenu sections + loose items
  // Preserves original item order: submenu group appears at the position of its first child
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

        // Insert the submenu group at the position of the first child item
        if (!insertedGroups.has(item.submenu)) {
          insertedGroups.add(item.submenu)
          result.push({
            type: 'submenu',
            key: item.submenu,
            title: submenuConfig[item.submenu].title,
            icon: submenuConfig[item.submenu].icon,
            items: groups[item.submenu], // reference - will accumulate subsequent items
          })
        }
      } else {
        // Regular item (no submenu) - render inline
        result.push({ type: 'item', key: item.link, item })
      }
    })

    return result
  }

  // Auto-expand submenus containing the active route
  function autoExpandActiveSubmenus(expandedSubmenus) {
    for (const menu of globalStore.menuGroups) {
      if (!hasSubmenus(menu)) continue
      const organized = getOrganizedMenu(menu)
      for (const group of organized) {
        if (group.type === 'submenu' && isSubmenuActive(group.items)) {
          expandedSubmenus[group.key] = true
        }
      }
    }
  }

  return {
    submenuConfig,
    hasActiveUrl,
    getHintKey,
    getHint,
    hasSubmenus,
    isSubmenuActive,
    getOrganizedMenu,
    autoExpandActiveSubmenus,
  }
}
